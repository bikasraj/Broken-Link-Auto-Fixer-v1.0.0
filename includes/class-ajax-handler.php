<?php
/**
 * AJAX Handler Class — BLAF_Ajax_Handler
 *
 * Handles all admin-ajax.php actions for:
 *  - Starting / progressing a background scan.
 *  - Replacing a broken URL in post content.
 *  - Removing a broken link (keeping anchor text).
 *  - Ignoring / deleting a record.
 *
 * @package BrokenLinkAutoFixer
 * @author  Bikas Kumar <bikas@codesala.in>
 * @company CodeSala — codesala.in
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLAF_Ajax_Handler {

	/**
	 * Constructor — register all AJAX actions.
	 */
	public function __construct() {
		// Scan actions.
		add_action( 'wp_ajax_blaf_start_scan',    array( $this, 'start_scan' ) );
		add_action( 'wp_ajax_blaf_scan_progress', array( $this, 'scan_progress' ) );

		// Fix actions.
		add_action( 'wp_ajax_blaf_replace_url',   array( $this, 'replace_url' ) );
		add_action( 'wp_ajax_blaf_remove_link',   array( $this, 'remove_link' ) );
		add_action( 'wp_ajax_blaf_ignore_link',   array( $this, 'ignore_link' ) );
		add_action( 'wp_ajax_blaf_delete_record', array( $this, 'delete_record' ) );
	}

	// ─── Shared Security ─────────────────────────────────────────────────────

	/**
	 * Verify nonce and capability, then return the sanitized POST value.
	 * Calls wp_send_json_error and exits on failure.
	 */
	private function verify_request() {
		if ( ! check_ajax_referer( 'blaf_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'broken-link-auto-fixer' ) ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'broken-link-auto-fixer' ) ) );
		}
	}

	// ─── Scan ────────────────────────────────────────────────────────────────

	/**
	 * AJAX: Start a full site scan.
	 *
	 * The scan is executed synchronously in this request; for very large
	 * sites a batch/queue approach (batched via repeated AJAX calls) is
	 * used — see scan_progress().
	 */
	public function start_scan() {
		$this->verify_request();

		if ( ! get_option( 'blaf_scan_enabled', 1 ) ) {
			wp_send_json_error( array( 'message' => __( 'Scanning is disabled in Settings.', 'broken-link-auto-fixer' ) ) );
		}

		$scanner = new BLAF_Link_Scanner();
		$result  = $scanner->run_full_scan();

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( array( 'message' => $result['error'] ) );
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: links checked, 2: broken links found */
				__( 'Scan complete. %1$d links checked, %2$d broken links found.', 'broken-link-auto-fixer' ),
				$result['checked'],
				$result['found']
			),
			'found'   => $result['found'],
			'checked' => $result['checked'],
		) );
	}

	/**
	 * AJAX: Return current scan status (for a polling progress bar).
	 */
	public function scan_progress() {
		$this->verify_request();

		$running   = (bool) get_transient( 'blaf_scan_running' );
		$last_scan = get_option( 'blaf_last_scan', '' );
		$total     = BLAF_Database::count_broken();

		wp_send_json_success( array(
			'running'   => $running,
			'last_scan' => $last_scan,
			'total'     => $total,
		) );
	}

	// ─── Fix: Replace URL ────────────────────────────────────────────────────

	/**
	 * AJAX: Replace a broken URL with a new one inside the post content.
	 *
	 * Expected POST fields:
	 *   record_id   int     — ID in wp_broken_links table.
	 *   new_url     string  — The replacement URL.
	 */
	public function replace_url() {
		$this->verify_request();

		$record_id = absint( $_POST['record_id'] ?? 0 );
		$new_url   = esc_url_raw( sanitize_text_field( $_POST['new_url'] ?? '' ) );

		if ( ! $record_id || empty( $new_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid input.', 'broken-link-auto-fixer' ) ) );
		}

		$record = BLAF_Database::get_link( $record_id );
		if ( ! $record ) {
			wp_send_json_error( array( 'message' => __( 'Record not found.', 'broken-link-auto-fixer' ) ) );
		}

		$post = get_post( $record->post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'broken-link-auto-fixer' ) ) );
		}

		// Replace the broken URL with the new one in post content.
		$old_url     = $record->broken_url;
		$new_content = str_replace(
			array( esc_attr( $old_url ), $old_url ),
			array( esc_attr( $new_url ), $new_url ),
			$post->post_content
		);

		if ( $new_content === $post->post_content ) {
			wp_send_json_error( array( 'message' => __( 'The broken URL was not found in the post content.', 'broken-link-auto-fixer' ) ) );
		}

		// Update the post.
		$result = wp_update_post( array(
			'ID'           => $post->ID,
			'post_content' => $new_content,
		), true );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Mark the record as fixed.
		BLAF_Database::update_status( $record_id, 'fixed' );

		wp_send_json_success( array(
			'message' => __( 'URL replaced successfully and post updated.', 'broken-link-auto-fixer' ),
		) );
	}

	// ─── Fix: Remove Link ────────────────────────────────────────────────────

	/**
	 * AJAX: Remove the <a> tag wrapping the broken URL but keep the anchor text.
	 *
	 * Expected POST fields:
	 *   record_id   int  — ID in wp_broken_links table.
	 */
	public function remove_link() {
		$this->verify_request();

		$record_id = absint( $_POST['record_id'] ?? 0 );
		if ( ! $record_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid input.', 'broken-link-auto-fixer' ) ) );
		}

		$record = BLAF_Database::get_link( $record_id );
		if ( ! $record ) {
			wp_send_json_error( array( 'message' => __( 'Record not found.', 'broken-link-auto-fixer' ) ) );
		}

		$post = get_post( $record->post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'broken-link-auto-fixer' ) ) );
		}

		// Use regex to strip the <a href="broken_url">…</a> but keep inner text.
		$broken_url_escaped = preg_quote( $record->broken_url, '/' );
		$pattern     = '/<a\s[^>]*href=["\']' . $broken_url_escaped . '["\'][^>]*>(.*?)<\/a>/is';
		$new_content = preg_replace( $pattern, '$1', $post->post_content );

		if ( null === $new_content || $new_content === $post->post_content ) {
			wp_send_json_error( array( 'message' => __( 'Link was not found in post content.', 'broken-link-auto-fixer' ) ) );
		}

		$result = wp_update_post( array(
			'ID'           => $post->ID,
			'post_content' => $new_content,
		), true );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		BLAF_Database::update_status( $record_id, 'fixed' );

		wp_send_json_success( array(
			'message' => __( 'Link removed; anchor text preserved.', 'broken-link-auto-fixer' ),
		) );
	}

	// ─── Ignore / Delete ─────────────────────────────────────────────────────

	/**
	 * AJAX: Mark a broken link record as "ignored".
	 */
	public function ignore_link() {
		$this->verify_request();

		$record_id = absint( $_POST['record_id'] ?? 0 );
		if ( ! $record_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid input.', 'broken-link-auto-fixer' ) ) );
		}

		BLAF_Database::update_status( $record_id, 'ignored' );
		wp_send_json_success( array( 'message' => __( 'Link marked as ignored.', 'broken-link-auto-fixer' ) ) );
	}

	/**
	 * AJAX: Hard-delete a broken link record from the database.
	 */
	public function delete_record() {
		$this->verify_request();

		$record_id = absint( $_POST['record_id'] ?? 0 );
		if ( ! $record_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid input.', 'broken-link-auto-fixer' ) ) );
		}

		BLAF_Database::delete_link( $record_id );
		wp_send_json_success( array( 'message' => __( 'Record deleted.', 'broken-link-auto-fixer' ) ) );
	}
}
