<?php
/**
 * Square "Payment Links" integration via the REST API (no SDK needed).
 * Square hosts the checkout page and handles all card data / PCI compliance;
 * we only ever receive a hosted URL back.
 *
 * @link https://developer.squareup.com/reference/square/checkout-api/create-payment-link
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Square payment links.
 */
class HMS_Square {

	/**
	 * API base for the configured environment.
	 *
	 * @return string
	 */
	private static function base_url() {
		$env = strtolower( hms_get_setting( 'square_environment', 'sandbox' ) );
		return ( 'production' === $env )
			? 'https://connect.squareup.com'
			: 'https://connect.squareupsandbox.com';
	}

	/**
	 * Create a hosted Square checkout link.
	 *
	 * @param array $args description, amount_cents, currency, buyer_email, redirect_url.
	 * @return string|WP_Error Hosted checkout URL or error.
	 */
	public static function create_payment_link( $args ) {
		if ( ! hms_square_configured() ) {
			return new WP_Error( 'not_configured', 'Square is not configured.' );
		}
		$amount = (int) $args['amount_cents'];
		if ( $amount <= 0 ) {
			return new WP_Error( 'zero_amount', 'Nothing to charge.' );
		}

		$body = array(
			'idempotency_key' => wp_generate_uuid4(),
			'quick_pay'       => array(
				'name'        => mb_substr( (string) $args['description'], 0, 255 ),
				'price_money' => array(
					'amount'   => $amount,
					'currency' => strtoupper( $args['currency'] ),
				),
				'location_id' => hms_get_setting( 'square_location_id', '' ),
			),
			'checkout_options' => array(
				'ask_for_shipping_address' => false,
			),
		);

		if ( ! empty( $args['redirect_url'] ) ) {
			$body['checkout_options']['redirect_url'] = $args['redirect_url'];
		}
		if ( ! empty( $args['buyer_email'] ) ) {
			$body['pre_populated_data'] = array( 'buyer_email' => $args['buyer_email'] );
		}

		$response = wp_remote_post(
			self::base_url() . '/v2/online-checkout/payment-links',
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type'   => 'application/json',
					'Square-Version' => '2024-10-17',
					'Authorization'  => 'Bearer ' . hms_get_setting( 'square_access_token', '' ),
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 || empty( $data['payment_link']['url'] ) ) {
			$detail = isset( $data['errors'][0]['detail'] ) ? $data['errors'][0]['detail'] : ( 'Square returned status ' . $code );
			return new WP_Error( 'square_error', $detail );
		}

		return $data['payment_link']['url'];
	}
}
