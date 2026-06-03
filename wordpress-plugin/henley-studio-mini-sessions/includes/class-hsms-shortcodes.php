<?php
/**
 * The [mini_sessions] shortcode: renders the session list, a single session
 * with its booking form, and the booking confirmation — all on one page,
 * switching on query args.
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end shortcode controller.
 */
class HSMS_Shortcodes {

	/**
	 * Register the shortcode and assets.
	 */
	public function init() {
		add_shortcode( 'mini_sessions', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register (but don't force-load) the stylesheet.
	 */
	public function register_assets() {
		wp_register_style( 'hsms-frontend', HSMS_URL . 'assets/style.css', array(), HSMS_VERSION );
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public function render( $atts ) {
		wp_enqueue_style( 'hsms-frontend' );

		// Confirmation view.
		$booking_id = isset( $_GET['hsms_booking'] ) ? absint( $_GET['hsms_booking'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $booking_id ) {
			$booking = HSMS_Bookings::get_with_slot( $booking_id );
			if ( $booking ) {
				$session = get_post( $booking->session_id );
				return self::template(
					'confirmation',
					array(
						'booking' => $booking,
						'session' => $session,
						'paid'    => isset( $_GET['paid'] ) && '1' === $_GET['paid'], // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					)
				);
			}
		}

		// Single session + booking form.
		$slug = isset( $_GET['hsms_session'] ) ? sanitize_title( wp_unslash( $_GET['hsms_session'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $slug ) {
			$session = self::get_session_by_slug( $slug );
			if ( $session ) {
				$error = isset( $_GET['hsms_error'] ) ? sanitize_text_field( wp_unslash( $_GET['hsms_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$preselect = isset( $_GET['hsms_slot'] ) ? absint( $_GET['hsms_slot'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return self::template(
					'single',
					array(
						'session'   => $session,
						'slots'     => HSMS_Slots::for_session( $session->ID ),
						'error'     => $error,
						'preselect' => $preselect,
					)
				);
			}
		}

		// Default: the list of upcoming sessions.
		return self::template( 'list', array( 'sessions' => self::upcoming_sessions() ) );
	}

	/**
	 * Look up a published session by its slug.
	 *
	 * @param string $slug Post slug.
	 * @return WP_Post|null
	 */
	public static function get_session_by_slug( $slug ) {
		$posts = get_posts(
			array(
				'name'        => $slug,
				'post_type'   => HSMS_CPT,
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);
		return $posts ? $posts[0] : null;
	}

	/**
	 * Query upcoming, published sessions ordered by date.
	 *
	 * @return WP_Post[]
	 */
	public static function upcoming_sessions() {
		return get_posts(
			array(
				'post_type'      => HSMS_CPT,
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'meta_key'       => '_hsms_session_date', // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'     => '_hsms_session_date',
						'value'   => gmdate( 'Y-m-d', current_time( 'timestamp' ) - DAY_IN_SECONDS ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			)
		);
	}

	/**
	 * Render a template file with the given variables, returning its output.
	 *
	 * @param string $name Template name (without extension).
	 * @param array  $vars Variables to expose.
	 * @return string
	 */
	public static function template( $name, $vars = array() ) {
		$file = HSMS_DIR . 'templates/' . $name . '.php';
		if ( ! file_exists( $file ) ) {
			return '';
		}
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars );
		ob_start();
		include $file;
		return ob_get_clean();
	}
}
