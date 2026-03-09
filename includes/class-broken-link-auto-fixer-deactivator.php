<?php
/**
 * Fired during plugin deactivation.
 *
 * @link       https://codesala.in
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin deactivation.
 *
 * Clears any scheduled cron events registered by this plugin.
 *
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/includes
 * @author     Bikas Kumar <bikas@codesala.in>
 */
class Broken_Link_Auto_Fixer_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Removes the scheduled cron job to prevent orphan events.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'blaf_scheduled_scan' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'blaf_scheduled_scan' );
		}
	}
}
