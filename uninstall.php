<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes the custom database table and all plugin options from wp_options.
 *
 * @link    https://codesala.in
 * @since   1.0.0
 * @package Broken_Link_Auto_Fixer
 * @author  Bikas Kumar <bikas@codesala.in>
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop the custom broken_links table.
$table_name = $wpdb->prefix . 'broken_links';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange

// Delete all plugin options.
$options = array(
	'blaf_auto_scan_enabled',
	'blaf_scan_frequency',
	'blaf_max_links_per_scan',
	'blaf_email_notifications',
	'blaf_notification_email',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clear any scheduled cron events.
wp_clear_scheduled_hook( 'blaf_scheduled_scan' );
