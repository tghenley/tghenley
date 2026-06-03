<?php
/**
 * Template: single session detail + booking form.
 *
 * @var WP_Post $session
 * @var array   $slots
 * @var string  $error
 * @var int     $preselect
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$date      = get_post_meta( $session->ID, '_hms_session_date', true );
$location  = get_post_meta( $session->ID, '_hms_location', true );
$price     = (int) get_post_meta( $session->ID, '_hms_price_cents', true );
$deposit   = (int) get_post_meta( $session->ID, '_hms_deposit_cents', true );
$currency  = get_post_meta( $session->ID, '_hms_currency', true );
$duration  = (int) get_post_meta( $session->ID, '_hms_slot_duration', true );
$collect   = $deposit > 0 ? $deposit : $price;
$mode      = hms_payment_mode();

$open_slots = array_filter(
	$slots,
	static function ( $s ) {
		return 'open' === $s->status;
	}
);

if ( $collect <= 0 ) {
	$pay_note = __( 'This session is free — just reserve your spot and we’ll be in touch.', 'henleys-mini-sessions' );
} elseif ( 'square' === $mode ) {
	$what     = $deposit > 0 ? __( 'a deposit', 'henleys-mini-sessions' ) : __( 'payment', 'henleys-mini-sessions' );
	$pay_note = sprintf( /* translators: 1: deposit/payment 2: amount */ __( 'After you reserve, you’ll be sent to a secure Square page to pay %1$s of %2$s and lock in your time.', 'henleys-mini-sessions' ), $what, hms_format_money( $collect, $currency ) );
} else {
	$what     = $deposit > 0 ? __( 'a deposit invoice', 'henleys-mini-sessions' ) : __( 'an invoice', 'henleys-mini-sessions' );
	$pay_note = sprintf( /* translators: 1: invoice phrase 2: amount */ __( 'After you reserve, we’ll email you %1$s for %2$s to confirm your spot.', 'henleys-mini-sessions' ), $what, hms_format_money( $collect, $currency ) );
}
?>
<div class="hms">
	<p class="hms-back"><a href="<?php echo esc_url( hms_base_url() ); ?>">&larr; <?php esc_html_e( 'All sessions', 'henleys-mini-sessions' ); ?></a></p>

	<div class="hms-detail">
		<div class="hms-detail-info">
			<p class="hms-eyebrow"><?php echo esc_html( hms_format_date( $date ) ); ?></p>
			<h2 class="hms-detail-title"><?php echo esc_html( get_the_title( $session ) ); ?></h2>
			<?php if ( $location ) : ?>
				<p class="hms-detail-loc">📍 <?php echo esc_html( $location ); ?></p>
			<?php endif; ?>

			<?php if ( has_post_thumbnail( $session->ID ) ) : ?>
				<div class="hms-detail-photo"><?php echo get_the_post_thumbnail( $session->ID, 'large' ); ?></div>
			<?php endif; ?>

			<?php if ( trim( $session->post_content ) ) : ?>
				<div class="hms-detail-desc"><?php echo wp_kses_post( wpautop( $session->post_content ) ); ?></div>
			<?php endif; ?>

			<div class="hms-facts">
				<div class="hms-fact">
					<span class="hms-eyebrow"><?php esc_html_e( 'Session length', 'henleys-mini-sessions' ); ?></span>
					<strong><?php echo esc_html( sprintf( /* translators: %d minutes */ __( '%d min', 'henleys-mini-sessions' ), $duration ) ); ?></strong>
				</div>
				<div class="hms-fact">
					<span class="hms-eyebrow"><?php esc_html_e( 'Price', 'henleys-mini-sessions' ); ?></span>
					<strong><?php echo $price > 0 ? esc_html( hms_format_money( $price, $currency ) ) : esc_html__( 'Free', 'henleys-mini-sessions' ); ?></strong>
				</div>
				<?php if ( $deposit > 0 ) : ?>
					<div class="hms-fact">
						<span class="hms-eyebrow"><?php esc_html_e( 'Deposit today', 'henleys-mini-sessions' ); ?></span>
						<strong><?php echo esc_html( hms_format_money( $deposit, $currency ) ); ?></strong>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="hms-booking">
			<?php if ( empty( $open_slots ) ) : ?>
				<div class="hms-card-panel hms-soldout">
					<h3><?php esc_html_e( 'This session is fully booked', 'henleys-mini-sessions' ); ?></h3>
					<p><?php esc_html_e( 'Check back soon — cancellations do happen, and we add new dates often.', 'henleys-mini-sessions' ); ?></p>
				</div>
			<?php else : ?>
				<form class="hms-card-panel hms-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'hms_book' ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( HMS_BOOK_ACTION ); ?>" />
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session->ID ); ?>" />
					<input type="hidden" name="slot_id" id="hms-slot-id" value="<?php echo esc_attr( $preselect ); ?>" />

					<h3 class="hms-step"><?php esc_html_e( '1. Choose a time', 'henleys-mini-sessions' ); ?></h3>
					<div class="hms-slots">
						<?php foreach ( $open_slots as $slot ) : ?>
							<button type="button" class="hms-slot<?php echo ( (int) $preselect === (int) $slot->id ) ? ' is-selected' : ''; ?>" data-slot="<?php echo esc_attr( $slot->id ); ?>">
								<?php echo esc_html( hms_format_time_range( $slot->start_time, $slot->end_time ) ); ?>
							</button>
						<?php endforeach; ?>
					</div>

					<h3 class="hms-step"><?php esc_html_e( '2. Your details', 'henleys-mini-sessions' ); ?></h3>
					<label class="hms-label" for="hms-name"><?php esc_html_e( 'Name', 'henleys-mini-sessions' ); ?></label>
					<input class="hms-input" id="hms-name" name="client_name" required />

					<div class="hms-row">
						<div>
							<label class="hms-label" for="hms-email"><?php esc_html_e( 'Email', 'henleys-mini-sessions' ); ?></label>
							<input class="hms-input" id="hms-email" name="client_email" type="email" required />
						</div>
						<div>
							<label class="hms-label" for="hms-phone"><?php esc_html_e( 'Phone', 'henleys-mini-sessions' ); ?></label>
							<input class="hms-input" id="hms-phone" name="client_phone" />
						</div>
					</div>

					<label class="hms-label" for="hms-notes"><?php esc_html_e( 'Anything we should know?', 'henleys-mini-sessions' ); ?></label>
					<textarea class="hms-input" id="hms-notes" name="notes" rows="3"></textarea>

					<?php if ( hms_mailerlite_configured() ) : ?>
						<label class="hms-consent">
							<input type="checkbox" name="marketing_consent" value="1" />
							<span><?php echo esc_html( hms_get_setting( 'mailerlite_consent_label', 'Add me to the list for news and future mini sessions.' ) ); ?></span>
						</label>
					<?php endif; ?>

					<p class="hms-paynote"><?php echo esc_html( $pay_note ); ?></p>

					<?php if ( $error ) : ?>
						<p class="hms-error"><?php echo esc_html( $error ); ?></p>
					<?php endif; ?>

					<button type="submit" class="hms-btn hms-btn-primary" id="hms-submit" <?php echo $preselect ? '' : 'disabled'; ?>>
						<?php esc_html_e( 'Reserve my spot', 'henleys-mini-sessions' ); ?>
					</button>
					<p class="hms-hint" id="hms-hint" <?php echo $preselect ? 'style="display:none"' : ''; ?>><?php esc_html_e( 'Select a time above to continue.', 'henleys-mini-sessions' ); ?></p>
				</form>

				<script>
				( function () {
					var form = document.currentScript.previousElementSibling;
					var field = document.getElementById( 'hms-slot-id' );
					var submit = document.getElementById( 'hms-submit' );
					var hint = document.getElementById( 'hms-hint' );
					form.querySelectorAll( '.hms-slot' ).forEach( function ( btn ) {
						btn.addEventListener( 'click', function () {
							form.querySelectorAll( '.hms-slot' ).forEach( function ( b ) { b.classList.remove( 'is-selected' ); } );
							btn.classList.add( 'is-selected' );
							field.value = btn.getAttribute( 'data-slot' );
							submit.disabled = false;
							if ( hint ) { hint.style.display = 'none'; }
						} );
					} );
				} )();
				</script>
			<?php endif; ?>
		</div>
	</div>
</div>
