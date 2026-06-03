<?php
/**
 * Template: list of upcoming sessions.
 *
 * @var WP_Post[] $sessions
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$studio  = hsms_get_setting( 'studio_name', "Henley Studio" );
$tagline = hsms_get_setting( 'tagline', 'Mini Sessions' );
?>
<div class="hsms">
	<header class="hsms-hero">
		<p class="hsms-eyebrow"><?php echo esc_html( $tagline ); ?></p>
		<h2 class="hsms-hero-title"><?php echo esc_html( sprintf( /* translators: %s studio name */ __( 'Book your moment with %s', 'henley-studio-mini-sessions' ), $studio ) ); ?></h2>
		<p class="hsms-hero-sub"><?php esc_html_e( 'Short, beautiful photo sessions — pick a date, choose a time that suits you, and we’ll take care of the rest. Spots are limited and go quickly.', 'henley-studio-mini-sessions' ); ?></p>
	</header>

	<?php if ( empty( $sessions ) ) : ?>
		<div class="hsms-empty">
			<h3><?php esc_html_e( 'No sessions open right now', 'henley-studio-mini-sessions' ); ?></h3>
			<p><?php esc_html_e( 'New mini sessions are added regularly — check back soon.', 'henley-studio-mini-sessions' ); ?></p>
		</div>
	<?php else : ?>
		<div class="hsms-grid-cards">
			<?php
			foreach ( $sessions as $session ) :
				$date     = get_post_meta( $session->ID, '_hsms_session_date', true );
				$location = get_post_meta( $session->ID, '_hsms_location', true );
				$price    = (int) get_post_meta( $session->ID, '_hsms_price_cents', true );
				$deposit  = (int) get_post_meta( $session->ID, '_hsms_deposit_cents', true );
				$currency = get_post_meta( $session->ID, '_hsms_currency', true );
				$counts   = HSMS_Slots::counts( $session->ID );
				$sold_out = 0 === $counts['open'];
				$from     = $deposit > 0 ? $deposit : $price;
				$ts       = strtotime( $date );
				?>
				<a class="hsms-card" href="<?php echo esc_url( hsms_session_url( $session ) ); ?>">
					<div class="hsms-card-media">
						<?php if ( has_post_thumbnail( $session->ID ) ) : ?>
							<?php echo get_the_post_thumbnail( $session->ID, 'medium_large', array( 'class' => 'hsms-card-img' ) ); ?>
						<?php else : ?>
							<span class="hsms-card-day"><?php echo $ts ? esc_html( gmdate( 'j', $ts ) ) : '★'; ?></span>
						<?php endif; ?>
					</div>
					<div class="hsms-card-body">
						<p class="hsms-eyebrow"><?php echo esc_html( hsms_format_date( $date ) ); ?></p>
						<h3 class="hsms-card-title"><?php echo esc_html( get_the_title( $session ) ); ?></h3>
						<?php if ( $location ) : ?>
							<p class="hsms-card-loc"><?php echo esc_html( $location ); ?></p>
						<?php endif; ?>
						<p class="hsms-card-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $session->post_content ), 22 ) ); ?></p>
						<div class="hsms-card-foot">
							<span class="hsms-price">
								<?php
								if ( $from > 0 ) {
									echo $deposit > 0 ? esc_html__( 'From ', 'henley-studio-mini-sessions' ) : '';
									echo esc_html( hsms_format_money( $from, $currency ) );
									echo $deposit > 0 ? ' ' . esc_html__( 'deposit', 'henley-studio-mini-sessions' ) : '';
								} else {
									esc_html_e( 'Free', 'henley-studio-mini-sessions' );
								}
								?>
							</span>
							<?php if ( $sold_out ) : ?>
								<span class="hsms-badge hsms-badge-out"><?php esc_html_e( 'Sold out', 'henley-studio-mini-sessions' ); ?></span>
							<?php else : ?>
								<span class="hsms-badge hsms-badge-open"><?php echo esc_html( sprintf( /* translators: 1: open 2: total */ __( '%1$d of %2$d left', 'henley-studio-mini-sessions' ), $counts['open'], $counts['total'] ) ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
