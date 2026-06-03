<?php
/**
 * Handles front-end booking submissions and admin status changes
 * via admin-post.php.
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form handlers.
 */
class HSMS_Form_Handler {

	/**
	 * Register handlers.
	 */
	public function init() {
		add_action( 'admin_post_nopriv_' . HSMS_BOOK_ACTION, array( $this, 'handle_booking' ) );
		add_action( 'admin_post_' . HSMS_BOOK_ACTION, array( $this, 'handle_booking' ) );
		add_action( 'admin_post_hsms_update_status', array( $this, 'handle_status_update' ) );
	}

	/**
	 * Process a public booking submission.
	 */
	public function handle_booking() {
		check_admin_referer( 'hsms_book' );

		$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
		$slot_id    = isset( $_POST['slot_id'] ) ? absint( $_POST['slot_id'] ) : 0;
		$name       = isset( $_POST['client_name'] ) ? sanitize_text_field( wp_unslash( $_POST['client_name'] ) ) : '';
		$email       = isset( $_POST['client_email'] ) ? sanitize_email( wp_unslash( $_POST['client_email'] ) ) : '';
		$phone      = isset( $_POST['client_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['client_phone'] ) ) : '';
		$notes      = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		$session = get_post( $session_id );

		// Validate.
		if ( ! $session || HSMS_CPT !== $session->post_type || 'publish' !== $session->post_status ) {
			$this->fail( $session, __( 'This session is no longer available.', 'henley-studio-mini-sessions' ) );
		}
		if ( strlen( $name ) < 2 ) {
			$this->fail( $session, __( 'Please enter your name.', 'henley-studio-mini-sessions' ), $slot_id );
		}
		if ( ! is_email( $email ) ) {
			$this->fail( $session, __( 'Please enter a valid email address.', 'henley-studio-mini-sessions' ), $slot_id );
		}

		$slot = HSMS_Slots::get( $slot_id );
		if ( ! $slot || (int) $slot->session_id !== $session_id || 'open' !== $slot->status ) {
			$this->fail( $session, __( 'Sorry, that time has just been taken. Please choose another.', 'henley-studio-mini-sessions' ) );
		}

		$price    = (int) get_post_meta( $session_id, '_hsms_price_cents', true );
		$deposit  = (int) get_post_meta( $session_id, '_hsms_deposit_cents', true );
		$currency = get_post_meta( $session_id, '_hsms_currency', true );
		$amount   = $deposit > 0 ? $deposit : $price;
		$mode     = hsms_payment_mode();

		$booking_id = HSMS_Bookings::create(
			array(
				'session_id'     => $session_id,
				'slot_id'        => $slot_id,
				'client_name'    => $name,
				'client_email'   => $email,
				'client_phone'   => $phone,
				'notes'          => $notes,
				'amount_cents'   => $amount,
				'payment_method' => $mode,
			)
		);

		if ( is_wp_error( $booking_id ) ) {
			$this->fail( $session, $booking_id->get_error_message() );
		}

		// Notify the studio and the client.
		$this->send_notifications( $session, $booking_id );

		// Add to the newsletter if they consented (best-effort — never blocks).
		if ( ! empty( $_POST['marketing_consent'] ) && hsms_mailerlite_configured() ) {
			HSMS_MailerLite::subscribe( $email, $name, $phone );
		}

		// Generate a Square payment link when applicable.
		if ( 'square' === $mode && $amount > 0 ) {
			$label = $deposit > 0 ? __( 'Deposit', 'henley-studio-mini-sessions' ) : __( 'Payment', 'henley-studio-mini-sessions' );
			$link  = HSMS_Square::create_payment_link(
				array(
					'description'  => $session->post_title . ' — ' . $label,
					'amount_cents' => $amount,
					'currency'     => $currency,
					'buyer_email'  => $email,
					'redirect_url' => add_query_arg( 'paid', '1', hsms_booking_url( $booking_id ) ),
				)
			);
			if ( ! is_wp_error( $link ) ) {
				HSMS_Bookings::set_payment_link( $booking_id, $link );
			}
		}

		wp_safe_redirect( hsms_booking_url( $booking_id ) );
		exit;
	}

	/**
	 * Email the studio and the client about a new booking.
	 *
	 * @param WP_Post $session    Session.
	 * @param int     $booking_id Booking ID.
	 */
	private function send_notifications( $session, $booking_id ) {
		$booking = HSMS_Bookings::get_with_slot( $booking_id );
		if ( ! $booking ) {
			return;
		}
		$studio = hsms_get_setting( 'studio_name', "Henley Studio" );
		$when   = hsms_format_date( get_post_meta( $session->ID, '_hsms_session_date', true ) ) . ', ' . hsms_format_time_range( $booking->slot_start, $booking->slot_end );

		// To the studio.
		$admin_email = hsms_get_setting( 'contact_email', get_option( 'admin_email' ) );
		wp_mail(
			$admin_email,
			sprintf( /* translators: %s session title */ __( 'New booking: %s', 'henley-studio-mini-sessions' ), $session->post_title ),
			sprintf(
				"%s\n%s\n\n%s: %s\n%s: %s\n%s: %s\n\n%s:\n%s",
				$session->post_title,
				$when,
				__( 'Name', 'henley-studio-mini-sessions' ),
				$booking->client_name,
				__( 'Email', 'henley-studio-mini-sessions' ),
				$booking->client_email,
				__( 'Phone', 'henley-studio-mini-sessions' ),
				$booking->client_phone,
				__( 'Notes', 'henley-studio-mini-sessions' ),
				$booking->notes
			)
		);

		// To the client.
		wp_mail(
			$booking->client_email,
			sprintf( /* translators: %s studio name */ __( 'Your booking with %s', 'henley-studio-mini-sessions' ), $studio ),
			sprintf(
				/* translators: 1: name, 2: session, 3: when, 4: studio */
				__( "Hi %1\$s,\n\nThanks for booking %2\$s.\nWhen: %3\$s\n\nWe'll be in touch with any details. Reply to this email if you need to make a change.\n\n%4\$s", 'henley-studio-mini-sessions' ),
				$booking->client_name,
				$session->post_title,
				$when,
				$studio
			)
		);
	}

	/**
	 * Redirect back to the session with an error message.
	 *
	 * @param WP_Post|null $session Session post.
	 * @param string       $message Error message.
	 * @param int          $slot_id Slot to preselect (optional).
	 */
	private function fail( $session, $message, $slot_id = 0 ) {
		$url = $session ? hsms_session_url( $session ) : hsms_base_url();
		$url = add_query_arg( 'hsms_error', rawurlencode( $message ), $url );
		if ( $slot_id ) {
			$url = add_query_arg( 'hsms_slot', $slot_id, $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Admin: change a booking's status.
	 */
	public function handle_status_update() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'henley-studio-mini-sessions' ) );
		}
		check_admin_referer( 'hsms_update_status' );

		$id     = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		if ( $id && $status ) {
			HSMS_Bookings::update_status( $id, $status );
		}

		$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : admin_url( 'edit.php?post_type=' . HSMS_CPT . '&page=hsms-bookings' );
		wp_safe_redirect( $redirect );
		exit;
	}
}
