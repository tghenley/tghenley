<?php
/**
 * Template: list of upcoming sessions.
 *
 * @var WP_Post[] $sessions
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$studio  = hms_get_setting( 'studio_name', "Henley's Studio" );
$tagline = hms_get_setting( 'tagline', 'Mini Sessions' );
?>
<div class="hms">
	<header class="hms-hero">
		<p class="hms-eyebrow"><?php echo esc_html( $tagline ); ?></p>
		<h2 class="hms-hero-title"><?php echo esc_html( sprintf( /* translators: %s studio name */ __( 'Book your moment with %s', 'henleys-mini-sessions' ), $studio ) ); ?></h2>
		<p class="hms-hero-sub"><?php esc_html_e( 'Short, beautiful photo sessions — pick a date, choose a time that suits you, and we’ll take care of the rest. Spots are limited and go quickly.', 'henleys-mini-sessions' ); ?></p>
	</header>

	<?php if ( empty( $sessions ) ) : ?>
		<div class="hms-empty">
			<h3><?php esc_html_e( 'No sessions open right now', 'henleys-mini-sessions' ); ?></h3>
			<p><?php esc_html_e( 'New mini sessions are added regularly — check back soon.', 'henleys-mini-sessions' ); ?></p>
		</div>
	<?php else : ?>
		<div class="hms-grid-cards">
			<?php
			foreach ( $sessions as $session ) :
				$date     = get_post_meta( $session->ID, '_hms_session_date', true );
				$location = get_post_meta( $session->ID, '_hms_location', true );
				$price    = (int) get_post_meta( $session->ID, '_hms_price_cents', true );
				$deposit  = (int) get_post_meta( $session->ID, '_hms_deposit_cents', true );
				$currency = get_post_meta( $session->ID, '_hms_currency', true );
				$counts   = HMS_Slots::counts( $session->ID );
				$sold_out = 0 === $counts['open'];
				$from     = $deposit > 0 ? $deposit : $price;
				$ts       = strtotime( $date );
				?>
				<a class="hms-card" href="<?php echo esc_url( hms_session_url( $session ) ); ?>">
					<div class="hms-card-media">
						<?php if ( has_post_thumbnail( $session->ID ) ) : ?>
							<?php echo get_the_post_thumbnail( $session->ID, 'medium_large', array( 'class' => 'hms-card-img' ) ); ?>
						<?php else : ?>
							<span class="hms-card-day"><?php echo $ts ? esc_html( gmdate( 'j', $ts ) ) : '★'; ?></span>
						<?php endif; ?>
					</div>
					<div class="hms-card-body">
						<p class="hms-eyebrow"><?php echo esc_html( hms_format_date( $date ) ); ?></p>
						<h3 class="hms-card-title"><?php echo esc_html( get_the_title( $session ) ); ?></h3>
						<?php if ( $location ) : ?>
							<p class="hms-card-loc"><?php echo esc_html( $location ); ?></p>
						<?php endif; ?>
						<p class="hms-card-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $session->post_content ), 22 ) ); ?></p>
						<div class="hms-card-foot">
							<span class="hms-price">
								<?php
								if ( $from > 0 ) {
									echo $deposit > 0 ? esc_html__( 'From ', 'henleys-mini-sessions' ) : '';
									echo esc_html( hms_format_money( $from, $currency ) );
									echo $deposit > 0 ? ' ' . esc_html__( 'deposit', 'henleys-mini-sessions' ) : '';
								} else {
									esc_html_e( 'Free', 'henleys-mini-sessions' );
								}
								?>
							</span>
							<?php if ( $sold_out ) : ?>
								<span class="hms-badge hms-badge-out"><?php esc_html_e( 'Sold out', 'henleys-mini-sessions' ); ?></span>
							<?php else : ?>
								<span class="hms-badge hms-badge-open"><?php echo esc_html( sprintf( /* translators: 1: open 2: total */ __( '%1$d of %2$d left', 'henleys-mini-sessions' ), $counts['open'], $counts['total'] ) ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
