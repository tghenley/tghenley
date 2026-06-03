<?php
/**
 * Uninstall cleanup — runs when the plugin is deleted from WordPress.
 * Removes plugin data (sessions, slots, bookings, settings).
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete custom tables.
$slots    = $wpdb->prefix . 'hsms_slots';
$bookings = $wpdb->prefix . 'hsms_bookings';
$wpdb->query( "DROP TABLE IF EXISTS {$bookings}" ); // phpcs:ignore WordPress.DB
$wpdb->query( "DROP TABLE IF EXISTS {$slots}" ); // phpcs:ignore WordPress.DB

// Delete all mini_session posts and their meta.
$session_ids = get_posts(
	array(
		'post_type'   => 'mini_session',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);
foreach ( $session_ids as $id ) {
	wp_delete_post( $id, true );
}

// Delete settings.
delete_option( 'hsms_settings' );
