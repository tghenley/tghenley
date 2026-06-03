<?php
/**
 * Activation / deactivation: create database tables and defaults.
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install routines.
 */
class HSMS_Install {

	/**
	 * Slots table name.
	 *
	 * @return string
	 */
	public static function slots_table() {
		global $wpdb;
		return $wpdb->prefix . 'hsms_slots';
	}

	/**
	 * Bookings table name.
	 *
	 * @return string
	 */
	public static function bookings_table() {
		global $wpdb;
		return $wpdb->prefix . 'hsms_bookings';
	}

	/**
	 * Run on activation.
	 */
	public static function activate() {
		self::create_tables();

		if ( false === get_option( HSMS_OPTION ) ) {
			add_option( HSMS_OPTION, hsms_default_settings() );
		}

		// The CPT isn't registered yet on activation, so register then flush.
		HSMS_CPT_Manager::register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Run on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Create custom tables via dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$slots           = self::slots_table();
		$bookings        = self::bookings_table();

		$sql_slots = "CREATE TABLE {$slots} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id bigint(20) unsigned NOT NULL,
			start_time datetime NOT NULL,
			end_time datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			PRIMARY KEY  (id),
			KEY session_id (session_id),
			KEY status (status)
		) {$charset_collate};";

		$sql_bookings = "CREATE TABLE {$bookings} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id bigint(20) unsigned NOT NULL,
			slot_id bigint(20) unsigned NOT NULL,
			client_name varchar(190) NOT NULL DEFAULT '',
			client_email varchar(190) NOT NULL DEFAULT '',
			client_phone varchar(60) NOT NULL DEFAULT '',
			notes text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			amount_cents bigint(20) NOT NULL DEFAULT 0,
			payment_method varchar(20) NOT NULL DEFAULT 'invoice',
			payment_link text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slot_id (slot_id),
			KEY session_id (session_id)
		) {$charset_collate};";

		dbDelta( $sql_slots );
		dbDelta( $sql_bookings );
	}
}
