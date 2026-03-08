<?php
/**
 * Plugin Name:       Broken Link Auto Fixer
 * Plugin URI:        https://wordpress.org/plugins/broken-link-auto-fixer/
 * Description:       Scan WordPress posts and pages to detect broken links (404 errors) and fix them directly from the dashboard.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Bikas Kumar
 * Author URI:        https://profiles.wordpress.org/bikas-kumar-codesala/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       broken-link-auto-fixer
 * Domain Path:       /languages
 *
 * @package BrokenLinkAutoFixer
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Plugin Constants ────────────────────────────────────────────────────────

define( 'BLAF_VERSION',     '1.0.0' );
define( 'BLAF_PLUGIN_FILE', __FILE__ );
define( 'BLAF_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'BLAF_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'BLAF_TABLE_NAME',  'broken_links' ); // wpdb->prefix will be prepended at runtime.

// ─── Autoload Includes ───────────────────────────────────────────────────────

require_once BLAF_PLUGIN_DIR . 'includes/class-database.php';
require_once BLAF_PLUGIN_DIR . 'includes/class-link-scanner.php';
require_once BLAF_PLUGIN_DIR . 'includes/class-admin-page.php';
require_once BLAF_PLUGIN_DIR . 'includes/class-ajax-handler.php';

// ─── Activation Hook ─────────────────────────────────────────────────────────

/**
 * Runs on plugin activation.
 * Creates the custom database table and sets default options.
 *
 * @return void
 */
function blaf_activate() {
	BLAF_Database::create_table();

	// Default plugin options.
	$defaults = array(
		'blaf_auto_scan'    => 0,
		'blaf_max_links'    => 100,
		'blaf_email_alerts' => 1,
		'blaf_alert_email'  => get_option( 'admin_email' ),
		'blaf_scan_enabled' => 1,
	);
	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( $key ) ) {
			add_option( $key, $value );
		}
	}

	// Schedule daily cron if auto-scan is enabled.
	if ( ! wp_next_scheduled( 'blaf_daily_scan_event' ) ) {
		wp_schedule_event( time(), 'daily', 'blaf_daily_scan_event' );
	}
}
register_activation_hook( BLAF_PLUGIN_FILE, 'blaf_activate' );

// ─── Deactivation Hook ───────────────────────────────────────────────────────

/**
 * Runs on plugin deactivation.
 * Clears the scheduled cron job.
 *
 * @return void
 */
function blaf_deactivate() {
	$timestamp = wp_next_scheduled( 'blaf_daily_scan_event' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'blaf_daily_scan_event' );
	}
}
register_deactivation_hook( BLAF_PLUGIN_FILE, 'blaf_deactivate' );

// ─── Cron Job Callback ───────────────────────────────────────────────────────

/**
 * Execute the scheduled link scan via WP-Cron.
 *
 * @return void
 */
function blaf_run_scheduled_scan() {
	if ( get_option( 'blaf_auto_scan' ) && get_option( 'blaf_scan_enabled' ) ) {
		$scanner = new BLAF_Link_Scanner();
		$scanner->run_full_scan();
	}
}
add_action( 'blaf_daily_scan_event', 'blaf_run_scheduled_scan' );

// ─── Bootstrap Classes ───────────────────────────────────────────────────────

/**
 * Instantiate admin page and AJAX handler after all plugins are loaded.
 *
 * @return void
 */
function blaf_init() {
	new BLAF_Admin_Page();
	new BLAF_Ajax_Handler();
}
add_action( 'plugins_loaded', 'blaf_init' );

// ─── Load Text Domain ────────────────────────────────────────────────────────

/**
 * Load the plugin's text domain for translations.
 *
 * @return void
 */
function blaf_load_textdomain() {
	load_plugin_textdomain(
		'broken-link-auto-fixer',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'blaf_load_textdomain' );

// ─── Enqueue Admin Scripts & Styles ──────────────────────────────────────────

/**
 * Enqueue CSS and JS only on the plugin's admin pages.
 *
 * @param string $hook The current admin page hook suffix.
 * @return void
 */
function blaf_enqueue_admin_assets( $hook ) {
	// Only load on our plugin pages.
	if ( false === strpos( $hook, 'broken-link' ) && false === strpos( $hook, 'blaf-settings' ) ) {
		return;
	}

	wp_enqueue_style(
		'blaf-admin-style',
		BLAF_PLUGIN_URL . 'assets/css/admin-style.css',
		array(),
		BLAF_VERSION
	);

	wp_enqueue_script(
		'blaf-admin-script',
		BLAF_PLUGIN_URL . 'assets/js/admin-script.js',
		array( 'jquery' ),
		BLAF_VERSION,
		true
	);

	// Pass data to JavaScript.
	wp_localize_script(
		'blaf-admin-script',
		'blaf_ajax',
		array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'blaf_nonce' ),
			/* translators: Button label shown while scanning is in progress. */
			'scan_text'  => __( 'Scanning...', 'broken-link-auto-fixer' ),
			/* translators: Button label shown after scan completes. */
			'done_text'  => __( 'Scan Complete!', 'broken-link-auto-fixer' ),
			/* translators: Generic error message shown in UI. */
			'error_text' => __( 'An error occurred. Please try again.', 'broken-link-auto-fixer' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'blaf_enqueue_admin_assets' );
