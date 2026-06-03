<?php
/**
 * MailerLite newsletter integration via the MailerLite REST API.
 * Used to add booking clients (with their consent) to a MailerLite group.
 *
 * @link https://developers.mailerlite.com/docs/subscribers.html
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MailerLite API client.
 */
class HSMS_MailerLite {

	/**
	 * Upsert a subscriber and (optionally) add them to the configured group.
	 *
	 * Best-effort: failures are returned as WP_Error but should never block a
	 * booking. Uses the current MailerLite API (connect.mailerlite.com).
	 *
	 * @param string $email Subscriber email.
	 * @param string $name  Subscriber name.
	 * @param string $phone Subscriber phone (optional).
	 * @return true|WP_Error
	 */
	public static function subscribe( $email, $name = '', $phone = '' ) {
		if ( ! hsms_mailerlite_configured() ) {
			return new WP_Error( 'not_configured', 'MailerLite is not configured.' );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'bad_email', 'Invalid email.' );
		}

		$fields = array();
		if ( '' !== $name ) {
			$fields['name'] = $name;
		}
		if ( '' !== $phone ) {
			$fields['phone'] = $phone;
		}

		$body = array( 'email' => $email );
		if ( $fields ) {
			$body['fields'] = $fields;
		}
		$group = hsms_get_setting( 'mailerlite_group_id', '' );
		if ( '' !== $group ) {
			$body['groups'] = array( $group );
		}

		$response = wp_remote_post(
			'https://connect.mailerlite.com/api/subscribers',
			array(
				'timeout' => 8,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . hsms_get_setting( 'mailerlite_api_key', '' ),
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$data   = json_decode( wp_remote_retrieve_body( $response ), true );
			$detail = isset( $data['message'] ) ? $data['message'] : ( 'MailerLite returned status ' . $code );
			return new WP_Error( 'mailerlite_error', $detail );
		}

		return true;
	}
}
