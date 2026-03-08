<?php
/**
 * Uninstall Script — Broken Link Auto Fixer
 *
 * Runs when the plugin is deleted via the WordPress admin.
 * Removes the custom database table and all plugin options.
 *
 * @package BrokenLinkAutoFixer
 * @author  Bikas Kumar <bikas@codesala.in>
 * @company CodeSala — codesala.in
 */

// Only execute if WordPress initiated this uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ── Load the database class so we can use drop_table(). ──────────────────────
require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';

// ── Drop the custom table. ───────────────────────────────────────────────────
BLAF_Database::drop_table();

// ── Remove all plugin options. ───────────────────────────────────────────────
$options = array(
	'blaf_auto_scan',
	'blaf_max_links',
	'blaf_email_alerts',
	'blaf_alert_email',
	'blaf_scan_enabled',
	'blaf_last_scan',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// ── Remove scheduled cron event. ─────────────────────────────────────────────
$timestamp = wp_next_scheduled( 'blaf_daily_scan_event' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'blaf_daily_scan_event' );
}
