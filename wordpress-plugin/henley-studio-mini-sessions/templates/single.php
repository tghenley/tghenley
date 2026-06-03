<?php
/**
 * Template: single session detail + booking form.
 *
 * @var WP_Post $session
 * @var array   $slots
 * @var string  $error
 * @var int     $preselect
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$date      = get_post_meta( $session->ID, '_hsms_session_date', true );
$location  = get_post_meta( $session->ID, '_hsms_location', true );
$price     = (int) get_post_meta( $session->ID, '_hsms_price_cents', true );
$deposit   = (int) get_post_meta( $session->ID, '_hsms_deposit_cents', true );
$currency  = get_post_meta( $session->ID, '_hsms_currency', true );
$duration  = (int) get_post_meta( $session->ID, '_hsms_slot_duration', true );
$collect   = $deposit > 0 ? $deposit : $price;
$mode      = hsms_payment_mode();

$open_slots = array_filter(
	$slots,
	static function ( $s ) {
		return 'open' === $s->status;
	}
);

if ( $collect <= 0 ) {
	$pay_note = __( 'This session is free — just reserve your spot and we’ll be in touch.', 'henley-studio-mini-sessions' );
} elseif ( 'square' === $mode ) {
	$what     = $deposit > 0 ? __( 'a deposit', 'henley-studio-mini-sessions' ) : __( 'payment', 'henley-studio-mini-sessions' );
	$pay_note = sprintf( /* translators: 1: deposit/payment 2: amount */ __( 'After you reserve, you’ll be sent to a secure Square page to pay %1$s of %2$s and lock in your time.', 'henley-studio-mini-sessions' ), $what, hsms_format_money( $collect, $currency ) );
} else {
	$what     = $deposit > 0 ? __( 'a deposit invoice', 'henley-studio-mini-sessions' ) : __( 'an invoice', 'henley-studio-mini-sessions' );
	$pay_note = sprintf( /* translators: 1: invoice phrase 2: amount */ __( 'After you reserve, we’ll email you %1$s for %2$s to confirm your spot.', 'henley-studio-mini-sessions' ), $what, hsms_format_money( $collect, $currency ) );
}
?>
<div class="hsms">
	<p class="hsms-back"><a href="<?php echo esc_url( hsms_base_url() ); ?>">&larr; <?php esc_html_e( 'All sessions', 'henley-studio-mini-sessions' ); ?></a></p>

	<div class="hsms-detail">
		<div class="hsms-detail-info">
			<p class="hsms-eyebrow"><?php echo esc_html( hsms_format_date( $date ) ); ?></p>
			<h2 class="hsms-detail-title"><?php echo esc_html( get_the_title( $session ) ); ?></h2>
			<?php if ( $location ) : ?>
				<p class="hsms-detail-loc">📍 <?php echo esc_html( $location ); ?></p>
			<?php endif; ?>

			<?php if ( has_post_thumbnail( $session->ID ) ) : ?>
				<div class="hsms-detail-photo"><?php echo get_the_post_thumbnail( $session->ID, 'large' ); ?></div>
			<?php endif; ?>

			<?php if ( trim( $session->post_content ) ) : ?>
				<div class="hsms-detail-desc"><?php echo wp_kses_post( wpautop( $session->post_content ) ); ?></div>
			<?php endif; ?>

			<div class="hsms-facts">
				<div class="hsms-fact">
					<span class="hsms-eyebrow"><?php esc_html_e( 'Session length', 'henley-studio-mini-sessions' ); ?></span>
					<strong><?php echo esc_html( sprintf( /* translators: %d minutes */ __( '%d min', 'henley-studio-mini-sessions' ), $duration ) ); ?></strong>
				</div>
				<div class="hsms-fact">
					<span class="hsms-eyebrow"><?php esc_html_e( 'Price', 'henley-studio-mini-sessions' ); ?></span>
					<strong><?php echo $price > 0 ? esc_html( hsms_format_money( $price, $currency ) ) : esc_html__( 'Free', 'henley-studio-mini-sessions' ); ?></strong>
				</div>
				<?php if ( $deposit > 0 ) : ?>
					<div class="hsms-fact">
						<span class="hsms-eyebrow"><?php esc_html_e( 'Deposit today', 'henley-studio-mini-sessions' ); ?></span>
						<strong><?php echo esc_html( hsms_format_money( $deposit, $currency ) ); ?></strong>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="hsms-booking">
			<?php if ( empty( $open_slots ) ) : ?>
				<div class="hsms-card-panel hsms-soldout">
					<h3><?php esc_html_e( 'This session is fully booked', 'henley-studio-mini-sessions' ); ?></h3>
					<p><?php esc_html_e( 'Check back soon — cancellations do happen, and we add new dates often.', 'henley-studio-mini-sessions' ); ?></p>
				</div>
			<?php else : ?>
				<form class="hsms-card-panel hsms-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'hsms_book' ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( HSMS_BOOK_ACTION ); ?>" />
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session->ID ); ?>" />
					<input type="hidden" name="slot_id" id="hsms-slot-id" value="<?php echo esc_attr( $preselect ); ?>" />

					<h3 class="hsms-step"><?php esc_html_e( '1. Choose a time', 'henley-studio-mini-sessions' ); ?></h3>
					<div class="hsms-slots">
						<?php foreach ( $open_slots as $slot ) : ?>
							<button type="button" class="hsms-slot<?php echo ( (int) $preselect === (int) $slot->id ) ? ' is-selected' : ''; ?>" data-slot="<?php echo esc_attr( $slot->id ); ?>">
								<?php echo esc_html( hsms_format_time_range( $slot->start_time, $slot->end_time ) ); ?>
							</button>
						<?php endforeach; ?>
					</div>

					<h3 class="hsms-step"><?php esc_html_e( '2. Your details', 'henley-studio-mini-sessions' ); ?></h3>
					<label class="hsms-label" for="hsms-name"><?php esc_html_e( 'Name', 'henley-studio-mini-sessions' ); ?></label>
					<input class="hsms-input" id="hsms-name" name="client_name" required />

					<div class="hsms-row">
						<div>
							<label class="hsms-label" for="hsms-email"><?php esc_html_e( 'Email', 'henley-studio-mini-sessions' ); ?></label>
							<input class="hsms-input" id="hsms-email" name="client_email" type="email" required />
						</div>
						<div>
							<label class="hsms-label" for="hsms-phone"><?php esc_html_e( 'Phone', 'henley-studio-mini-sessions' ); ?></label>
							<input class="hsms-input" id="hsms-phone" name="client_phone" />
						</div>
					</div>

					<label class="hsms-label" for="hsms-notes"><?php esc_html_e( 'Anything we should know?', 'henley-studio-mini-sessions' ); ?></label>
					<textarea class="hsms-input" id="hsms-notes" name="notes" rows="3"></textarea>

					<?php if ( hsms_mailerlite_configured() ) : ?>
						<label class="hsms-consent">
							<input type="checkbox" name="marketing_consent" value="1" />
							<span><?php echo esc_html( hsms_get_setting( 'mailerlite_consent_label', 'Add me to the list for news and future mini sessions.' ) ); ?></span>
						</label>
					<?php endif; ?>

					<p class="hsms-paynote"><?php echo esc_html( $pay_note ); ?></p>

					<?php if ( $error ) : ?>
						<p class="hsms-error"><?php echo esc_html( $error ); ?></p>
					<?php endif; ?>

					<button type="submit" class="hsms-btn hsms-btn-primary" id="hsms-submit" <?php echo $preselect ? '' : 'disabled'; ?>>
						<?php esc_html_e( 'Reserve my spot', 'henley-studio-mini-sessions' ); ?>
					</button>
					<p class="hsms-hint" id="hsms-hint" <?php echo $preselect ? 'style="display:none"' : ''; ?>><?php esc_html_e( 'Select a time above to continue.', 'henley-studio-mini-sessions' ); ?></p>
				</form>

				<script>
				( function () {
					var form = document.currentScript.previousElementSibling;
					var field = document.getElementById( 'hsms-slot-id' );
					var submit = document.getElementById( 'hsms-submit' );
					var hint = document.getElementById( 'hsms-hint' );
					form.querySelectorAll( '.hsms-slot' ).forEach( function ( btn ) {
						btn.addEventListener( 'click', function () {
							form.querySelectorAll( '.hsms-slot' ).forEach( function ( b ) { b.classList.remove( 'is-selected' ); } );
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
