<?php
/**
 * Plugin Name:       Henley Studio — Mini Sessions
 * Plugin URI:        https://henleystudio.com
 * Description:       Sell and manage photography mini sessions. Clients pick a time slot and pay a deposit via Square (or you invoice them). Add the [mini_sessions] shortcode to any page.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Henley Studio
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       henley-studio-mini-sessions
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'HSMS_VERSION', '1.0.0' );
define( 'HSMS_FILE', __FILE__ );
define( 'HSMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HSMS_URL', plugin_dir_url( __FILE__ ) );

require_once HSMS_DIR . 'includes/helpers.php';
require_once HSMS_DIR . 'includes/class-hsms-install.php';
require_once HSMS_DIR . 'includes/class-hsms-cpt.php';
require_once HSMS_DIR . 'includes/class-hsms-slots.php';
require_once HSMS_DIR . 'includes/class-hsms-bookings.php';
require_once HSMS_DIR . 'includes/class-hsms-square.php';
require_once HSMS_DIR . 'includes/class-hsms-mailerlite.php';
require_once HSMS_DIR . 'includes/class-hsms-shortcodes.php';
require_once HSMS_DIR . 'includes/class-hsms-form-handler.php';
require_once HSMS_DIR . 'includes/class-hsms-admin.php';
require_once HSMS_DIR . 'includes/class-hsms-plugin.php';

register_activation_hook( __FILE__, array( 'HSMS_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HSMS_Install', 'deactivate' ) );

// Boot the plugin once all plugins are loaded.
add_action( 'plugins_loaded', array( 'HSMS_Plugin', 'instance' ) );
