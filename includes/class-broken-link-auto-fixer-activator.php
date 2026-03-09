<?php
/**
 * Fired during plugin activation.
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
 * Fired during plugin activation.
 *
 * Creates the custom database table and sets default options.
 *
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/includes
 * @author     Bikas Kumar <bikas@codesala.in>
 */
class Broken_Link_Auto_Fixer_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Creates the wp_broken_links database table and registers default settings
	 * and a scheduled daily cron event.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-database.php';
		Broken_Link_Auto_Fixer_Database::create_table();

		// Set default plugin options if not already set.
		$defaults = array(
			'auto_scan_enabled'    => 0,
			'scan_frequency'       => 'daily',
			'max_links_per_scan'   => 100,
			'email_notifications'  => 0,
			'notification_email'   => get_option( 'admin_email' ),
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'blaf_' . $key ) ) {
				add_option( 'blaf_' . $key, $value );
			}
		}

		// Schedule cron job if auto scan is enabled.
		if ( ! wp_next_scheduled( 'blaf_scheduled_scan' ) ) {
			wp_schedule_event( time(), 'daily', 'blaf_scheduled_scan' );
		}
	}
}
