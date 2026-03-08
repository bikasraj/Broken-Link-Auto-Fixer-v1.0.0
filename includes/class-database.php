<?php
/**
 * Database Class — BLAF_Database
 *
 * Handles creation, querying, inserting, and deleting records
 * in the wp_broken_links custom table.
 *
 * @package BrokenLinkAutoFixer
 * @author  Bikas Kumar <bikas@codesala.in>
 * @company CodeSala — codesala.in
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLAF_Database {

	/**
	 * Returns the full prefixed table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . BLAF_TABLE_NAME;
	}

	// ─── Table Creation ──────────────────────────────────────────────────────

	/**
	 * Create (or upgrade) the wp_broken_links table using dbDelta.
	 *
	 * Column purposes:
	 *  id          — Auto-increment primary key for unique row identification.
	 *  post_id     — FK to wp_posts; lets us load/update the source post.
	 *  post_title  — Cached title so we can display it without a JOIN.
	 *  broken_url  — The URL that returned a non-200 HTTP status code.
	 *  status      — Workflow state: 'broken', 'fixed', 'ignored'.
	 *  http_code   — The actual HTTP response code (404, 500, 0 = timeout…).
	 *  date_found  — Timestamp of when the scanner first detected this link.
	 */
	public static function create_table() {
		global $wpdb;

		$table      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			post_title  VARCHAR(255)        NOT NULL DEFAULT '',
			broken_url  TEXT                NOT NULL,
			status      VARCHAR(20)         NOT NULL DEFAULT 'broken',
			http_code   SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
			date_found  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY status  (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// ─── CRUD Helpers ────────────────────────────────────────────────────────

	/**
	 * Insert a broken-link record (skip duplicates for same post + URL).
	 *
	 * @param int    $post_id
	 * @param string $post_title
	 * @param string $broken_url
	 * @param int    $http_code
	 * @return int|false Inserted row ID or false on failure / duplicate.
	 */
	public static function insert_link( $post_id, $post_title, $broken_url, $http_code ) {
		global $wpdb;
		$table = self::table_name();

		// Avoid duplicates: same post + same URL already in table.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE post_id = %d AND broken_url = %s LIMIT 1",
				$post_id,
				$broken_url
			)
		);

		if ( $exists ) {
			// Update the http_code and date_found on re-scan.
			$wpdb->update(
				$table,
				array(
					'http_code'  => absint( $http_code ),
					'status'     => 'broken',
					'date_found' => current_time( 'mysql' ),
				),
				array( 'id' => $exists ),
				array( '%d', '%s', '%s' ),
				array( '%d' )
			);
			return (int) $exists;
		}

		$inserted = $wpdb->insert(
			$table,
			array(
				'post_id'    => absint( $post_id ),
				'post_title' => sanitize_text_field( $post_title ),
				'broken_url' => esc_url_raw( $broken_url ),
				'status'     => 'broken',
				'http_code'  => absint( $http_code ),
				'date_found' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Retrieve all broken-link records with optional filters.
	 *
	 * @param array $args {
	 *   @type string $status   Filter by status. Default 'broken'.
	 *   @type int    $limit    Max rows. Default 500.
	 *   @type int    $offset   Offset for pagination. Default 0.
	 * }
	 * @return array
	 */
	public static function get_links( $args = array() ) {
		global $wpdb;
		$table = self::table_name();

		$defaults = array(
			'status' => '',
			'limit'  => 500,
			'offset' => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$where = '';
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where    = 'WHERE status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}

		$params[] = absint( $args['limit'] );
		$params[] = absint( $args['offset'] );

		$sql = "SELECT * FROM {$table} {$where} ORDER BY date_found DESC LIMIT %d OFFSET %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Get a single link record by ID.
	 *
	 * @param int $id
	 * @return object|null
	 */
	public static function get_link( $id ) {
		global $wpdb;
		$table = self::table_name();
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) )
		);
	}

	/**
	 * Update the status of a link record.
	 *
	 * @param int    $id
	 * @param string $status  'broken' | 'fixed' | 'ignored'
	 * @return bool
	 */
	public static function update_status( $id, $status ) {
		global $wpdb;
		$table          = self::table_name();
		$allowed        = array( 'broken', 'fixed', 'ignored' );
		$sanitized_status = in_array( $status, $allowed, true ) ? $status : 'broken';

		return (bool) $wpdb->update(
			$table,
			array( 'status' => $sanitized_status ),
			array( 'id'     => absint( $id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete a record by ID.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete_link( $id ) {
		global $wpdb;
		$table = self::table_name();
		return (bool) $wpdb->delete(
			$table,
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);
	}

	/**
	 * Count all broken links.
	 *
	 * @return int
	 */
	public static function count_broken() {
		global $wpdb;
		$table = self::table_name();
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'broken' )
		);
	}

	/**
	 * Drop the table entirely (used on uninstall).
	 *
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
