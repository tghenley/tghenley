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
 * Text Domain:       henleys-mini-sessions
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'HMS_VERSION', '1.0.0' );
define( 'HMS_FILE', __FILE__ );
define( 'HMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HMS_URL', plugin_dir_url( __FILE__ ) );

require_once HMS_DIR . 'includes/helpers.php';
require_once HMS_DIR . 'includes/class-hms-install.php';
require_once HMS_DIR . 'includes/class-hms-cpt.php';
require_once HMS_DIR . 'includes/class-hms-slots.php';
require_once HMS_DIR . 'includes/class-hms-bookings.php';
require_once HMS_DIR . 'includes/class-hms-square.php';
require_once HMS_DIR . 'includes/class-hms-mailerlite.php';
require_once HMS_DIR . 'includes/class-hms-shortcodes.php';
require_once HMS_DIR . 'includes/class-hms-form-handler.php';
require_once HMS_DIR . 'includes/class-hms-admin.php';
require_once HMS_DIR . 'includes/class-hms-plugin.php';

register_activation_hook( __FILE__, array( 'HMS_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HMS_Install', 'deactivate' ) );

// Boot the plugin once all plugins are loaded.
add_action( 'plugins_loaded', array( 'HMS_Plugin', 'instance' ) );
