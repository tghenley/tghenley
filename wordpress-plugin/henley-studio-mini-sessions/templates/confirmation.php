<?php
/**
 * Template: booking confirmation.
 *
 * @var object       $booking Booking row joined with slot times.
 * @var WP_Post|null $session
 * @var bool         $paid
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$currency = $session ? get_post_meta( $session->ID, '_hsms_currency', true ) : 'AUD';
$deposit  = $session ? (int) get_post_meta( $session->ID, '_hsms_deposit_cents', true ) : 0;
$date     = $session ? get_post_meta( $session->ID, '_hsms_session_date', true ) : '';
$location = $session ? get_post_meta( $session->ID, '_hsms_location', true ) : '';
$mode     = hsms_payment_mode();

$needs_payment = ( $booking->amount_cents > 0 ) && ( 'paid' !== $booking->status ) && ! $paid;
?>
<div class="hsms">
	<div class="hsms-confirm">
		<div class="hsms-confirm-head">
			<div class="hsms-check">✓</div>
			<h2><?php echo $paid ? esc_html__( 'Payment received!', 'henley-studio-mini-sessions' ) : esc_html__( 'You’re booked in', 'henley-studio-mini-sessions' ); ?></h2>
			<p><?php echo wp_kses_post( sprintf( /* translators: %s email */ __( 'A confirmation has been sent to <strong>%s</strong>.', 'henley-studio-mini-sessions' ), esc_html( $booking->client_email ) ) ); ?></p>
		</div>

		<div class="hsms-confirm-body">
			<?php
			$rows = array(
				__( 'Session', 'henley-studio-mini-sessions' ) => $session ? get_the_title( $session ) : '',
				__( 'Date', 'henley-studio-mini-sessions' )    => hsms_format_date( $date ),
				__( 'Time', 'henley-studio-mini-sessions' )    => hsms_format_time_range( $booking->slot_start, $booking->slot_end ),
			);
			if ( $location ) {
				$rows[ __( 'Location', 'henley-studio-mini-sessions' ) ] = $location;
			}
			$rows[ __( 'Name', 'henley-studio-mini-sessions' ) ] = $booking->client_name;
			if ( $booking->amount_cents > 0 ) {
				$rows[ $deposit > 0 ? __( 'Deposit', 'henley-studio-mini-sessions' ) : __( 'Total', 'henley-studio-mini-sessions' ) ] = hsms_format_money( $booking->amount_cents, $currency );
			}
			foreach ( $rows as $label => $value ) :
				?>
				<div class="hsms-confirm-row">
					<span class="hsms-eyebrow"><?php echo esc_html( $label ); ?></span>
					<span><?php echo esc_html( $value ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ( $needs_payment ) : ?>
				<div class="hsms-paybox">
					<?php if ( 'square' === $mode && $booking->payment_link ) : ?>
						<p><?php echo esc_html( sprintf( /* translators: %s amount */ __( 'One last step — secure your spot by paying %s via Square.', 'henley-studio-mini-sessions' ), hsms_format_money( $booking->amount_cents, $currency ) ) ); ?></p>
						<a class="hsms-btn hsms-btn-primary" href="<?php echo esc_url( $booking->payment_link ); ?>">
							<?php echo esc_html( sprintf( /* translators: %s amount */ __( 'Pay %s now', 'henley-studio-mini-sessions' ), hsms_format_money( $booking->amount_cents, $currency ) ) ); ?>
						</a>
						<p class="hsms-hint"><?php esc_html_e( 'Payments are processed securely by Square.', 'henley-studio-mini-sessions' ); ?></p>
					<?php else : ?>
						<p><?php echo wp_kses_post( sprintf( /* translators: 1: amount 2: email */ __( 'We’ll email an invoice for %1$s to <strong>%2$s</strong> shortly. Your spot is held in the meantime.', 'henley-studio-mini-sessions' ), esc_html( hsms_format_money( $booking->amount_cents, $currency ) ), esc_html( $booking->client_email ) ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php elseif ( $booking->amount_cents > 0 ) : ?>
				<div class="hsms-paid"><?php esc_html_e( 'Thank you! Your payment is all sorted. We can’t wait to see you.', 'henley-studio-mini-sessions' ); ?></div>
			<?php endif; ?>

			<p class="hsms-back"><a href="<?php echo esc_url( hsms_base_url() ); ?>"><?php esc_html_e( 'Back to all sessions', 'henley-studio-mini-sessions' ); ?></a></p>
		</div>
	</div>
</div>
