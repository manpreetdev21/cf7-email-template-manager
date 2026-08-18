<?php
/**
 * Plugin Name:       CF7 Email Template Manager
 * Plugin URI:        https://example.com/cf7-email-template-manager
 * Description:       Reusable, brandable email templates for Contact Form 7. Design once, assign to any form — without ever overwriting Contact Form 7's own mail settings.
 * Version:           1.0.0
 * Author:            Manpreet Singh
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cf7-email-template-manager
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'CF7ETM_VERSION', '1.0.0' );
define( 'CF7ETM_FILE', __FILE__ );
define( 'CF7ETM_DIR', plugin_dir_path( __FILE__ ) );
define( 'CF7ETM_URL', plugin_dir_url( __FILE__ ) );

require_once CF7ETM_DIR . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( 'CF7ETM_Plugin', 'boot' ) );
register_activation_hook( __FILE__, array( 'CF7ETM_Plugin', 'activate' ) );
