<?php
/**
 * Main plugin orchestrator — instantiates and wires up the components.
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin bootstrap (singleton).
 */
class HSMS_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var HSMS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get (and on first call, build) the instance.
	 *
	 * @return HSMS_Plugin
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
		load_plugin_textdomain( 'henley-studio-mini-sessions', false, dirname( plugin_basename( HSMS_FILE ) ) . '/languages' );

		( new HSMS_CPT_Manager() )->init();
		( new HSMS_Shortcodes() )->init();
		( new HSMS_Form_Handler() )->init();

		if ( is_admin() ) {
			( new HSMS_Admin() )->init();
		}
	}
}
