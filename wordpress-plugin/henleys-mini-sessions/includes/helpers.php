<?php
/**
 * Shared helpers: settings access, money/date formatting, link building.
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const HMS_CPT          = 'mini_session';
const HMS_OPTION       = 'hms_settings';
const HMS_BOOK_ACTION  = 'hms_book';
const HMS_STATUS_VALUES = array( 'pending', 'confirmed', 'paid', 'cancelled' );

/**
 * Default plugin settings, merged with stored values.
 *
 * @return array
 */
function hms_default_settings() {
	return array(
		'studio_name'          => "Henley Studio",
		'tagline'              => 'Mini Sessions',
		'contact_email'        => get_option( 'admin_email' ),
		'default_currency'     => 'AUD',
		'booking_page_id'      => 0,
		'square_access_token'  => '',
		'square_location_id'   => '',
		'square_environment'   => 'sandbox',
		'mailerlite_api_key'   => '',
		'mailerlite_group_id'  => '',
		'mailerlite_consent_label' => 'Add me to the list for news and future mini sessions.',
	);
}

/**
 * Get all settings (defaults merged with saved options).
 *
 * @return array
 */
function hms_get_settings() {
	$saved = get_option( HMS_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, hms_default_settings() );
}

/**
 * Get a single setting value.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function hms_get_setting( $key, $default = '' ) {
	$settings = hms_get_settings();
	return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $default;
}

/**
 * Whether Square is configured (both token and location id present).
 *
 * @return bool
 */
function hms_square_configured() {
	return '' !== hms_get_setting( 'square_access_token', '' ) && '' !== hms_get_setting( 'square_location_id', '' );
}

/**
 * Active payment mode: "square" when configured, otherwise "invoice".
 *
 * @return string
 */
function hms_payment_mode() {
	return hms_square_configured() ? 'square' : 'invoice';
}

/**
 * Whether MailerLite newsletter sign-up is configured (API key present).
 *
 * @return bool
 */
function hms_mailerlite_configured() {
	return '' !== hms_get_setting( 'mailerlite_api_key', '' );
}

/**
 * Convert a dollar value (string or float) to integer cents.
 *
 * @param mixed $value Dollar amount.
 * @return int
 */
function hms_dollars_to_cents( $value ) {
	$n = is_numeric( $value ) ? (float) $value : 0.0;
	if ( $n < 0 ) {
		$n = 0.0;
	}
	return (int) round( $n * 100 );
}

/**
 * Format integer cents as a currency string.
 *
 * @param int    $cents    Amount in cents.
 * @param string $currency ISO currency code.
 * @return string
 */
function hms_format_money( $cents, $currency ) {
	$currency = strtoupper( $currency ? $currency : 'AUD' );
	$amount   = $cents / 100;

	if ( class_exists( 'NumberFormatter' ) ) {
		$fmt = new NumberFormatter( get_locale() ? str_replace( '_', '-', get_locale() ) : 'en', NumberFormatter::CURRENCY );
		$out = $fmt->formatCurrency( $amount, $currency );
		if ( false !== $out ) {
			return $out;
		}
	}
	return number_format_i18n( $amount, 2 ) . ' ' . $currency;
}

/**
 * Format a stored "Y-m-d" date for display.
 *
 * @param string $ymd Date string.
 * @return string
 */
function hms_format_date( $ymd ) {
	$ts = strtotime( $ymd );
	if ( ! $ts ) {
		return $ymd;
	}
	return date_i18n( 'l, j F Y', $ts );
}

/**
 * Format a stored "Y-m-d H:i:s" time for display.
 *
 * @param string $datetime Datetime string.
 * @return string
 */
function hms_format_time( $datetime ) {
	$ts = strtotime( $datetime );
	if ( ! $ts ) {
		return $datetime;
	}
	return date_i18n( get_option( 'time_format', 'g:i a' ), $ts );
}

/**
 * Format a slot start/end pair as a range.
 *
 * @param string $start Start datetime.
 * @param string $end   End datetime.
 * @return string
 */
function hms_format_time_range( $start, $end ) {
	return hms_format_time( $start ) . ' – ' . hms_format_time( $end );
}

/**
 * Base URL of the page that hosts the [mini_sessions] shortcode.
 *
 * @return string
 */
function hms_base_url() {
	$page_id = (int) hms_get_setting( 'booking_page_id', 0 );
	if ( $page_id ) {
		$url = get_permalink( $page_id );
		if ( $url ) {
			return $url;
		}
	}
	// Fallback: the current page (if any), else the home URL.
	$queried = get_queried_object_id();
	if ( $queried ) {
		$url = get_permalink( $queried );
		if ( $url ) {
			return $url;
		}
	}
	return home_url( '/' );
}

/**
 * Build a URL on the booking page with the given query args.
 *
 * @param array $args Query arguments.
 * @return string
 */
function hms_url( $args = array() ) {
	return add_query_arg( $args, hms_base_url() );
}

/**
 * URL for a single session view.
 *
 * @param WP_Post|int $session Session post or ID.
 * @return string
 */
function hms_session_url( $session ) {
	$post = get_post( $session );
	if ( ! $post ) {
		return hms_base_url();
	}
	return hms_url( array( 'hms_session' => $post->post_name ) );
}

/**
 * URL for a booking confirmation view.
 *
 * @param int $booking_id Booking ID.
 * @return string
 */
function hms_booking_url( $booking_id ) {
	return hms_url( array( 'hms_booking' => (int) $booking_id ) );
}
