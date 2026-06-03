<?php
/**
 * Scheduled SMS reminders, driven by WP-Cron.
 *
 * Every hour we look for bookings whose slot starts within the configured
 * reminder window and that haven't been reminded yet, then text them once.
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reminder scheduler + worker.
 */
class HSMS_Reminders {

	const HOOK = 'hsms_send_reminders';

	/**
	 * Register the cron callback and make sure the event is scheduled.
	 */
	public function init() {
		add_action( self::HOOK, array( __CLASS__, 'run' ) );

		// Self-heal: ensure the recurring event exists (e.g. for installs that
		// activated before this feature, or if it was cleared).
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			self::schedule();
		}
	}

	/**
	 * Schedule the hourly reminder event.
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', self::HOOK );
		}
	}

	/**
	 * Clear the scheduled event.
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Cron worker: send any due reminders.
	 */
	public static function run() {
		if ( ! hsms_sms_configured() || '1' !== (string) hsms_get_setting( 'sms_send_reminder', '1' ) ) {
			return;
		}

		$hours = (int) hsms_get_setting( 'sms_reminder_hours', 24 );
		if ( $hours <= 0 ) {
			$hours = 24;
		}

		// Work in the site-local "wall clock" space that slot times are stored in.
		$now       = current_time( 'mysql' );
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( $now ) + ( $hours * HOUR_IN_SECONDS ) );

		$due = HSMS_Bookings::due_for_reminder( $now, $threshold );
		foreach ( $due as $booking ) {
			$session = get_post( $booking->session_id );
			if ( ! $session ) {
				// Orphan booking — mark reminded so we don't re-scan it forever.
				HSMS_Bookings::mark_reminded( $booking->id );
				continue;
			}

			$to = hsms_normalize_phone( $booking->client_phone );
			if ( '' === $to ) {
				HSMS_Bookings::mark_reminded( $booking->id );
				continue;
			}

			$result = HSMS_SMS::send( $to, HSMS_SMS::reminder_text( $session, $booking ) );

			// Mark as reminded on success, or on a permanent (non-network) error
			// such as an invalid number, so we don't retry a doomed send forever.
			if ( true === $result || ( is_wp_error( $result ) && 'twilio_error' === $result->get_error_code() ) ) {
				HSMS_Bookings::mark_reminded( $booking->id );
			}
		}
	}
}
