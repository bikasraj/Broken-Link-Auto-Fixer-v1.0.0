<?php
/**
 * Database management class.
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
 * Handles all custom database operations for the plugin.
 *
 * Creates and manages the wp_broken_links table which stores
 * every detected broken link along with its metadata.
 *
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/includes
 * @author     Bikas Kumar <bikas@codesala.in>
 */
class Broken_Link_Auto_Fixer_Database {

	/**
	 * Create the custom database table using dbDelta.
	 *
	 * Table: {prefix}broken_links
	 * - id          : Auto-increment primary key.
	 * - post_id      : ID of the WordPress post/page containing the link.
	 * - post_title   : Title of the post (cached for display without extra queries).
	 * - broken_url   : The full URL that returned an error.
	 * - anchor_text  : Visible anchor text of the link (needed to remove link but keep text).
	 * - status       : Workflow status: 'broken', 'fixed', 'ignored', 'removed'.
	 * - http_code    : HTTP response code (e.g. 404, 500, 0 for timeout).
	 * - date_found   : Timestamp when the broken link was first detected.
	 *
	 * @since    1.0.0
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'broken_links';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id     BIGINT(20) UNSIGNED NOT NULL,
			post_title  VARCHAR(400)        NOT NULL DEFAULT '',
			broken_url  TEXT                NOT NULL,
			anchor_text VARCHAR(500)        NOT NULL DEFAULT '',
			status      VARCHAR(20)         NOT NULL DEFAULT 'broken',
			http_code   SMALLINT(6)         NOT NULL DEFAULT 0,
			date_found  DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY status  (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the custom database table.
	 *
	 * Called from uninstall.php only.
	 *
	 * @since    1.0.0
	 */
	public static function drop_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'broken_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Insert a broken link record.
	 *
	 * @since    1.0.0
	 * @param    array $data  Associative array of column => value pairs.
	 * @return   int|false    Inserted row ID on success, false on failure.
	 */
	public static function insert_broken_link( array $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'broken_links';

		$insert = $wpdb->insert(
			$table_name,
			array(
				'post_id'    => absint( $data['post_id'] ),
				'post_title' => sanitize_text_field( $data['post_title'] ),
				'broken_url' => esc_url_raw( $data['broken_url'] ),
				'anchor_text'=> sanitize_text_field( $data['anchor_text'] ),
				'status'     => 'broken',
				'http_code'  => absint( $data['http_code'] ),
				'date_found' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return $insert ? $wpdb->insert_id : false;
	}

	/**
	 * Check if a URL is already recorded as broken for a given post.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id     Post ID.
	 * @param    string $broken_url  The URL to check.
	 * @return   bool
	 */
	public static function link_exists( $post_id, $broken_url ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'broken_links';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND broken_url = %s AND status = 'broken'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $post_id ),
				esc_url_raw( $broken_url )
			)
		);

		return $count > 0;
	}

	/**
	 * Retrieve all broken links with optional pagination.
	 *
	 * @since    1.0.0
	 * @param    int $per_page  Results per page.
	 * @param    int $page      Current page number.
	 * @return   array
	 */
	public static function get_broken_links( $per_page = 20, $page = 1 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'broken_links';
		$offset     = ( absint( $page ) - 1 ) * absint( $per_page );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE status = 'broken' ORDER BY date_found DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $per_page ),
				$offset
			)
		);
	}

	/**
	 * Count total broken links.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	public static function count_broken_links() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'broken_links';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'broken'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Update the status of a broken link record.
	 *
	 * @since    1.0.0
	 * @param    int    $id      Record ID.
	 * @param    string $status  New status: 'fixed' | 'removed' | 'ignored'.
	 * @return   int|false
	 */
	public static function update_status( $id, $status ) {
		global $wpdb;
		$table_name     = $wpdb->prefix . 'broken_links';
		$allowed_status = array( 'fixed', 'removed', 'ignored', 'broken' );

		if ( ! in_array( $status, $allowed_status, true ) ) {
			return false;
		}

		return $wpdb->update(
			$table_name,
			array( 'status' => $status ),
			array( 'id'     => absint( $id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete a single broken link record by ID.
	 *
	 * @since    1.0.0
	 * @param    int $id  Record ID.
	 * @return   int|false
	 */
	public static function delete_link( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'broken_links';

		return $wpdb->delete(
			$table_name,
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);
	}

	/**
	 * Clear all records from the broken links table.
	 *
	 * @since    1.0.0
	 */
	public static function clear_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'broken_links';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
