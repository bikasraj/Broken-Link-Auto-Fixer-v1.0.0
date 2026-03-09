<?php
/**
 * AJAX Handler for the plugin.
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
 * Handles all AJAX actions for the Broken Link Auto Fixer.
 *
 * All methods verify a nonce and check manage_options capability
 * before performing any operation.
 *
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/includes
 * @author     Bikas Kumar <bikas@codesala.in>
 */
class Broken_Link_Auto_Fixer_Ajax_Handler {

	/**
	 * Register all AJAX hooks.
	 *
	 * Called from the main plugin class hooks definition.
	 *
	 * @since    1.0.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_blaf_run_scan',      array( $this, 'ajax_run_scan' ) );
		add_action( 'wp_ajax_blaf_replace_url',   array( $this, 'ajax_replace_url' ) );
		add_action( 'wp_ajax_blaf_remove_link',   array( $this, 'ajax_remove_link' ) );
		add_action( 'wp_ajax_blaf_ignore_link',   array( $this, 'ajax_ignore_link' ) );
		add_action( 'wp_ajax_blaf_clear_results', array( $this, 'ajax_clear_results' ) );
		add_action( 'wp_ajax_blaf_save_settings', array( $this, 'ajax_save_settings' ) );
	}

	// ----------------------------------------------------------------
	// SCAN
	// ----------------------------------------------------------------

	/**
	 * Run a scan batch via AJAX.
	 *
	 * Accepts a 'offset' POST parameter so the JS can call this
	 * repeatedly until done=true is returned.
	 *
	 * @since    1.0.0
	 */
	public function ajax_run_scan() {
		$this->verify_request( 'blaf_scan_nonce' );

		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-scanner.php';

		$offset  = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$scanner = new Broken_Link_Auto_Fixer_Scanner();
		$result  = $scanner->run_scan( $offset );

		wp_send_json_success( $result );
	}

	// ----------------------------------------------------------------
	// REPLACE URL
	// ----------------------------------------------------------------

	/**
	 * Replace a broken URL with a new URL inside the post content.
	 *
	 * @since    1.0.0
	 */
	public function ajax_replace_url() {
		$this->verify_request( 'blaf_fix_nonce' );

		$record_id  = isset( $_POST['record_id'] )  ? absint( $_POST['record_id'] )                  : 0;
		$new_url    = isset( $_POST['new_url'] )     ? esc_url_raw( sanitize_text_field( wp_unslash( $_POST['new_url'] ) ) )    : '';

		if ( ! $record_id || ! $new_url ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'broken-link-auto-fixer' ) ) );
		}

		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-database.php';

		global $wpdb;
		$table   = $wpdb->prefix . 'broken_links';
		$record  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $record_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $record ) {
			wp_send_json_error( array( 'message' => __( 'Record not found.', 'broken-link-auto-fixer' ) ) );
		}

		$post = get_post( $record->post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'broken-link-auto-fixer' ) ) );
		}

		// Replace old URL with new URL in post content.
		$updated_content = str_replace(
			esc_url( $record->broken_url ),
			esc_url( $new_url ),
			$post->post_content
		);

		// Also try raw URL replacement in case it wasn't encoded.
		$updated_content = str_replace(
			$record->broken_url,
			$new_url,
			$updated_content
		);

		wp_update_post( array(
			'ID'           => $post->ID,
			'post_content' => $updated_content,
		) );

		Broken_Link_Auto_Fixer_Database::update_status( $record_id, 'fixed' );

		wp_send_json_success( array( 'message' => __( 'URL replaced successfully.', 'broken-link-auto-fixer' ) ) );
	}

	// ----------------------------------------------------------------
	// REMOVE LINK (keep anchor text)
	// ----------------------------------------------------------------

	/**
	 * Remove an anchor tag but keep the visible anchor text in post content.
	 *
	 * @since    1.0.0
	 */
	public function ajax_remove_link() {
		$this->verify_request( 'blaf_fix_nonce' );

		$record_id = isset( $_POST['record_id'] ) ? absint( $_POST['record_id'] ) : 0;

		if ( ! $record_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'broken-link-auto-fixer' ) ) );
		}

		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-database.php';

		global $wpdb;
		$table  = $wpdb->prefix . 'broken_links';
		$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $record_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $record ) {
			wp_send_json_error( array( 'message' => __( 'Record not found.', 'broken-link-auto-fixer' ) ) );
		}

		$post = get_post( $record->post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'broken-link-auto-fixer' ) ) );
		}

		// Remove <a ...> tags but preserve the inner anchor text.
		$pattern         = '/<a\s[^>]*href=["\']' . preg_quote( $record->broken_url, '/' ) . '["\'][^>]*>(.*?)<\/a>/is';
		$updated_content = preg_replace( $pattern, '$1', $post->post_content );

		wp_update_post( array(
			'ID'           => $post->ID,
			'post_content' => $updated_content,
		) );

		Broken_Link_Auto_Fixer_Database::update_status( $record_id, 'removed' );

		wp_send_json_success( array( 'message' => __( 'Link removed successfully. Anchor text preserved.', 'broken-link-auto-fixer' ) ) );
	}

	// ----------------------------------------------------------------
	// IGNORE LINK
	// ----------------------------------------------------------------

	/**
	 * Mark a broken link record as ignored (won't show in main list).
	 *
	 * @since    1.0.0
	 */
	public function ajax_ignore_link() {
		$this->verify_request( 'blaf_fix_nonce' );

		$record_id = isset( $_POST['record_id'] ) ? absint( $_POST['record_id'] ) : 0;

		if ( ! $record_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'broken-link-auto-fixer' ) ) );
		}

		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-database.php';
		Broken_Link_Auto_Fixer_Database::update_status( $record_id, 'ignored' );

		wp_send_json_success( array( 'message' => __( 'Link marked as ignored.', 'broken-link-auto-fixer' ) ) );
	}

	// ----------------------------------------------------------------
	// CLEAR ALL RESULTS
	// ----------------------------------------------------------------

	/**
	 * Clear all broken link records from the database.
	 *
	 * @since    1.0.0
	 */
	public function ajax_clear_results() {
		$this->verify_request( 'blaf_scan_nonce' );
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-database.php';
		Broken_Link_Auto_Fixer_Database::clear_all();
		wp_send_json_success( array( 'message' => __( 'All results cleared.', 'broken-link-auto-fixer' ) ) );
	}

	// ----------------------------------------------------------------
	// SAVE SETTINGS
	// ----------------------------------------------------------------

	/**
	 * Save plugin settings via AJAX.
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_settings() {
		$this->verify_request( 'blaf_settings_nonce' );

		update_option( 'blaf_auto_scan_enabled',   isset( $_POST['auto_scan_enabled'] )  ? 1 : 0 );
		update_option( 'blaf_max_links_per_scan',  absint( $_POST['max_links_per_scan'] ?? 100 ) );
		update_option( 'blaf_email_notifications', isset( $_POST['email_notifications'] ) ? 1 : 0 );
		update_option( 'blaf_notification_email',  sanitize_email( wp_unslash( $_POST['notification_email'] ?? get_option( 'admin_email' ) ) ) );

		$frequency = sanitize_text_field( wp_unslash( $_POST['scan_frequency'] ?? 'daily' ) );
		$allowed   = array( 'hourly', 'twicedaily', 'daily', 'weekly' );
		if ( ! in_array( $frequency, $allowed, true ) ) {
			$frequency = 'daily';
		}
		update_option( 'blaf_scan_frequency', $frequency );

		// Reschedule cron with new frequency.
		wp_clear_scheduled_hook( 'blaf_scheduled_scan' );
		if ( get_option( 'blaf_auto_scan_enabled' ) ) {
			wp_schedule_event( time(), $frequency, 'blaf_scheduled_scan' );
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'broken-link-auto-fixer' ) ) );
	}

	// ----------------------------------------------------------------
	// HELPERS
	// ----------------------------------------------------------------

	/**
	 * Verify nonce and capability for an AJAX request.
	 *
	 * Dies with a JSON error response if verification fails.
	 *
	 * @since    1.0.0
	 * @param    string $nonce_action  The nonce action string.
	 */
	private function verify_request( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'broken-link-auto-fixer' ) ), 403 );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'broken-link-auto-fixer' ) ), 403 );
		}
	}
}
