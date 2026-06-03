<?php
/**
 * Twilio SMS integration via the REST API (no SDK needed).
 *
 * @link https://www.twilio.com/docs/sms/api/message-resource#create-a-message-resource
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Twilio SMS client.
 */
class HSMS_SMS {

	/**
	 * Send an SMS to an E.164 number.
	 *
	 * @param string $to_e164 Destination number in E.164 format (+61...).
	 * @param string $body    Message text.
	 * @return true|WP_Error
	 */
	public static function send( $to_e164, $body ) {
		if ( ! hsms_sms_configured() ) {
			return new WP_Error( 'not_configured', 'Twilio is not configured.' );
		}
		if ( '' === $to_e164 ) {
			return new WP_Error( 'no_recipient', 'No valid phone number.' );
		}

		$sid   = hsms_get_setting( 'twilio_account_sid', '' );
		$token = hsms_get_setting( 'twilio_auth_token', '' );
		$from  = hsms_get_setting( 'twilio_from', '' );

		$response = wp_remote_post(
			'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $sid ) . '/Messages.json',
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'    => array(
					'To'   => $to_e164,
					'From' => $from,
					'Body' => $body,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$data    = json_decode( wp_remote_retrieve_body( $response ), true );
			$message = isset( $data['message'] ) ? $data['message'] : ( 'Twilio returned status ' . $code );
			return new WP_Error( 'twilio_error', $message );
		}

		return true;
	}

	/**
	 * Build the booking-confirmation SMS text for a booking.
	 *
	 * @param WP_Post $session Session post.
	 * @param object  $booking Booking row with slot_start/slot_end.
	 * @return string
	 */
	public static function confirmation_text( $session, $booking ) {
		$studio   = hsms_get_setting( 'studio_name', 'Henley Studio' );
		$first    = self::first_name( $booking->client_name );
		$date     = hsms_format_date( get_post_meta( $session->ID, '_hsms_session_date', true ) );
		$range    = hsms_format_time_range( $booking->slot_start, $booking->slot_end );
		$location = get_post_meta( $session->ID, '_hsms_location', true );

		$msg = sprintf(
			/* translators: 1: first name, 2: studio, 3: session, 4: date, 5: time range */
			__( "Hi %1\$s, you're booked with %2\$s for %3\$s on %4\$s at %5\$s.", 'henley-studio-mini-sessions' ),
			$first,
			$studio,
			$session->post_title,
			$date,
			$range
		);
		if ( $location ) {
			$msg .= ' ' . $location . '.';
		}
		return $msg . ' ' . __( 'See you then!', 'henley-studio-mini-sessions' );
	}

	/**
	 * Build the reminder SMS text for a booking.
	 *
	 * @param WP_Post $session Session post.
	 * @param object  $booking Booking row with slot_start/slot_end.
	 * @return string
	 */
	public static function reminder_text( $session, $booking ) {
		$studio   = hsms_get_setting( 'studio_name', 'Henley Studio' );
		$date     = hsms_format_date( get_post_meta( $session->ID, '_hsms_session_date', true ) );
		$range    = hsms_format_time_range( $booking->slot_start, $booking->slot_end );
		$location = get_post_meta( $session->ID, '_hsms_location', true );

		$msg = sprintf(
			/* translators: 1: studio, 2: session, 3: date, 4: time range */
			__( 'Reminder from %1$s: your %2$s session is %3$s at %4$s.', 'henley-studio-mini-sessions' ),
			$studio,
			$session->post_title,
			$date,
			$range
		);
		if ( $location ) {
			$msg .= ' ' . $location . '.';
		}
		return $msg;
	}

	/**
	 * First word of a name (for friendlier SMS).
	 *
	 * @param string $name Full name.
	 * @return string
	 */
	private static function first_name( $name ) {
		$parts = preg_split( '/\s+/', trim( (string) $name ) );
		return $parts ? $parts[0] : $name;
	}
}
