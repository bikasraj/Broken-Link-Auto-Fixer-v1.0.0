<?php
/**
 * Uninstall Script — Broken Link Auto Fixer
 *
 * Runs when the plugin is deleted via the WordPress admin.
 * Removes the custom database table and all plugin options.
 *
 * This file is intentionally self-contained and does NOT load any
 * plugin classes, so it cannot cause fatal errors regardless of
 * the environment or PHP version in use.
 *
 * @package BrokenLinkAutoFixer
 */

// Only execute if WordPress initiated this uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ── Drop the custom database table. ─────────────────────────────────────────
$table = $wpdb->prefix . 'broken_links';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

// ── Delete all plugin options. ───────────────────────────────────────────────
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

// ── Clear the scheduled cron event. ─────────────────────────────────────────
$timestamp = wp_next_scheduled( 'blaf_daily_scan_event' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'blaf_daily_scan_event' );
}
