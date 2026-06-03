<?php
/**
 * Slot generation and queries.
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slot data access.
 */
class HMS_Slots {

	/**
	 * Build slot start/end pairs for a session from its schedule meta.
	 *
	 * @param int $session_id Session post ID.
	 * @return array[] Array of array( 'start' => 'Y-m-d H:i:s', 'end' => ... ).
	 */
	public static function build( $session_id ) {
		$date     = get_post_meta( $session_id, '_hms_session_date', true );
		$start    = get_post_meta( $session_id, '_hms_start_time', true );
		$end      = get_post_meta( $session_id, '_hms_end_time', true );
		$duration = (int) get_post_meta( $session_id, '_hms_slot_duration', true );
		$break    = (int) get_post_meta( $session_id, '_hms_break_min', true );

		$out = array();
		if ( ! $date || ! $start || ! $end || $duration <= 0 ) {
			return $out;
		}

		$cursor  = strtotime( $date . ' ' . $start );
		$day_end = strtotime( $date . ' ' . $end );
		if ( ! $cursor || ! $day_end ) {
			return $out;
		}

		$guard = 0;
		while ( $guard < 500 ) {
			$guard++;
			$slot_end = $cursor + ( $duration * MINUTE_IN_SECONDS );
			if ( $slot_end > $day_end ) {
				break;
			}
			$out[] = array(
				'start' => gmdate( 'Y-m-d H:i:s', $cursor ),
				'end'   => gmdate( 'Y-m-d H:i:s', $slot_end ),
			);
			$cursor = $slot_end + ( $break * MINUTE_IN_SECONDS );
		}
		return $out;
	}

	/**
	 * Insert generated slots for a session.
	 *
	 * @param int $session_id Session post ID.
	 * @return int Number of slots created.
	 */
	public static function generate( $session_id ) {
		global $wpdb;
		$table = HMS_Install::slots_table();
		$slots = self::build( $session_id );

		$created = 0;
		foreach ( $slots as $slot ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				array(
					'session_id' => $session_id,
					'start_time' => $slot['start'],
					'end_time'   => $slot['end'],
					'status'     => 'open',
				),
				array( '%d', '%s', '%s', '%s' )
			);
			$created++;
		}
		return $created;
	}

	/**
	 * Delete all slots for a session and regenerate (only safe with no bookings).
	 *
	 * @param int $session_id Session post ID.
	 */
	public static function regenerate( $session_id ) {
		global $wpdb;
		$table = HMS_Install::slots_table();
		$wpdb->delete( $table, array( 'session_id' => $session_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::generate( $session_id );
	}

	/**
	 * All slots for a session, ordered by start time.
	 *
	 * @param int $session_id Session post ID.
	 * @return array
	 */
	public static function for_session( $session_id ) {
		global $wpdb;
		$table = HMS_Install::slots_table();
		return $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare( "SELECT * FROM {$table} WHERE session_id = %d ORDER BY start_time ASC", $session_id )
		);
	}

	/**
	 * A single slot.
	 *
	 * @param int $slot_id Slot ID.
	 * @return object|null
	 */
	public static function get( $slot_id ) {
		global $wpdb;
		$table = HMS_Install::slots_table();
		return $wpdb->get_row( // phpcs:ignore WordPress.DB
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $slot_id )
		);
	}

	/**
	 * Total and open slot counts for a session.
	 *
	 * @param int $session_id Session post ID.
	 * @return array{total:int,open:int}
	 */
	public static function counts( $session_id ) {
		global $wpdb;
		$table = HMS_Install::slots_table();
		$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open FROM {$table} WHERE session_id = %d",
				$session_id
			)
		);
		return array(
			'total' => $row ? (int) $row->total : 0,
			'open'  => $row ? (int) $row->open : 0,
		);
	}
}
