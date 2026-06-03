<?php
/**
 * Main plugin orchestrator — instantiates and wires up the components.
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin bootstrap (singleton).
 */
class HMS_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var HMS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get (and on first call, build) the instance.
	 *
	 * @return HMS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up components.
	 */
	private function __construct() {
		load_plugin_textdomain( 'henleys-mini-sessions', false, dirname( plugin_basename( HMS_FILE ) ) . '/languages' );

		( new HMS_CPT_Manager() )->init();
		( new HMS_Shortcodes() )->init();
		( new HMS_Form_Handler() )->init();

		if ( is_admin() ) {
			( new HMS_Admin() )->init();
		}
	}
}
