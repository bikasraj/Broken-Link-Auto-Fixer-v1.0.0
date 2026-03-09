<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://codesala.in
 * @since             1.0.0
 * @package           Broken_Link_Auto_Fixer
 *
 * @wordpress-plugin
 * Plugin Name:       Broken Link Auto Fixer
 * Plugin URI:        https://codesala.in/products/broken-link-auto-fixer-wordpress-plugin-to-detect-and-fix-broken-links/
 * Description:       Scan WordPress posts and pages for broken links (404, 500 errors) and fix or remove them directly from the admin dashboard. Supports scheduled scans, email alerts, and batch processing.
 * Version:           1.0.0
 * Author:            Bikas Kumar
 * Author URI:        https://codesala.in/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       broken-link-auto-fixer
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'BROKEN_LINK_AUTO_FIXER_VERSION', '1.0.0' );
define( 'BROKEN_LINK_AUTO_FIXER_PATH', plugin_dir_path( __FILE__ ) );
define( 'BROKEN_LINK_AUTO_FIXER_URL', plugin_dir_url( __FILE__ ) );
define( 'BROKEN_LINK_AUTO_FIXER_BASENAME', plugin_basename( __FILE__ ) );

function activate_broken_link_auto_fixer() {
	require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-activator.php';
	Broken_Link_Auto_Fixer_Activator::activate();
}

function deactivate_broken_link_auto_fixer() {
	require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-deactivator.php';
	Broken_Link_Auto_Fixer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_broken_link_auto_fixer' );
register_deactivation_hook( __FILE__, 'deactivate_broken_link_auto_fixer' );

require BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer.php';

function run_broken_link_auto_fixer() {
	$plugin = new Broken_Link_Auto_Fixer();
	$plugin->run();
}
run_broken_link_auto_fixer();
