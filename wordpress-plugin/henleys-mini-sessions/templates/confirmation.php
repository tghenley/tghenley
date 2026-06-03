<?php
/**
 * Template: booking confirmation.
 *
 * @var object       $booking Booking row joined with slot times.
 * @var WP_Post|null $session
 * @var bool         $paid
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$currency = $session ? get_post_meta( $session->ID, '_hms_currency', true ) : 'AUD';
$deposit  = $session ? (int) get_post_meta( $session->ID, '_hms_deposit_cents', true ) : 0;
$date     = $session ? get_post_meta( $session->ID, '_hms_session_date', true ) : '';
$location = $session ? get_post_meta( $session->ID, '_hms_location', true ) : '';
$mode     = hms_payment_mode();

$needs_payment = ( $booking->amount_cents > 0 ) && ( 'paid' !== $booking->status ) && ! $paid;
?>
<div class="hms">
	<div class="hms-confirm">
		<div class="hms-confirm-head">
			<div class="hms-check">✓</div>
			<h2><?php echo $paid ? esc_html__( 'Payment received!', 'henleys-mini-sessions' ) : esc_html__( 'You’re booked in', 'henleys-mini-sessions' ); ?></h2>
			<p><?php echo wp_kses_post( sprintf( /* translators: %s email */ __( 'A confirmation has been sent to <strong>%s</strong>.', 'henleys-mini-sessions' ), esc_html( $booking->client_email ) ) ); ?></p>
		</div>

		<div class="hms-confirm-body">
			<?php
			$rows = array(
				__( 'Session', 'henleys-mini-sessions' ) => $session ? get_the_title( $session ) : '',
				__( 'Date', 'henleys-mini-sessions' )    => hms_format_date( $date ),
				__( 'Time', 'henleys-mini-sessions' )    => hms_format_time_range( $booking->slot_start, $booking->slot_end ),
			);
			if ( $location ) {
				$rows[ __( 'Location', 'henleys-mini-sessions' ) ] = $location;
			}
			$rows[ __( 'Name', 'henleys-mini-sessions' ) ] = $booking->client_name;
			if ( $booking->amount_cents > 0 ) {
				$rows[ $deposit > 0 ? __( 'Deposit', 'henleys-mini-sessions' ) : __( 'Total', 'henleys-mini-sessions' ) ] = hms_format_money( $booking->amount_cents, $currency );
			}
			foreach ( $rows as $label => $value ) :
				?>
				<div class="hms-confirm-row">
					<span class="hms-eyebrow"><?php echo esc_html( $label ); ?></span>
					<span><?php echo esc_html( $value ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ( $needs_payment ) : ?>
				<div class="hms-paybox">
					<?php if ( 'square' === $mode && $booking->payment_link ) : ?>
						<p><?php echo esc_html( sprintf( /* translators: %s amount */ __( 'One last step — secure your spot by paying %s via Square.', 'henleys-mini-sessions' ), hms_format_money( $booking->amount_cents, $currency ) ) ); ?></p>
						<a class="hms-btn hms-btn-primary" href="<?php echo esc_url( $booking->payment_link ); ?>">
							<?php echo esc_html( sprintf( /* translators: %s amount */ __( 'Pay %s now', 'henleys-mini-sessions' ), hms_format_money( $booking->amount_cents, $currency ) ) ); ?>
						</a>
						<p class="hms-hint"><?php esc_html_e( 'Payments are processed securely by Square.', 'henleys-mini-sessions' ); ?></p>
					<?php else : ?>
						<p><?php echo wp_kses_post( sprintf( /* translators: 1: amount 2: email */ __( 'We’ll email an invoice for %1$s to <strong>%2$s</strong> shortly. Your spot is held in the meantime.', 'henleys-mini-sessions' ), esc_html( hms_format_money( $booking->amount_cents, $currency ) ), esc_html( $booking->client_email ) ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php elseif ( $booking->amount_cents > 0 ) : ?>
				<div class="hms-paid"><?php esc_html_e( 'Thank you! Your payment is all sorted. We can’t wait to see you.', 'henleys-mini-sessions' ); ?></div>
			<?php endif; ?>

			<p class="hms-back"><a href="<?php echo esc_url( hms_base_url() ); ?>"><?php esc_html_e( 'Back to all sessions', 'henleys-mini-sessions' ); ?></a></p>
		</div>
	</div>
</div>
