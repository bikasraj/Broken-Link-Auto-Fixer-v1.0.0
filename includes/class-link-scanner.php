<?php
/**
 * Link Scanner Class — BLAF_Link_Scanner
 *
 * Fetches all published posts/pages, extracts hyperlinks from content,
 * performs HTTP HEAD/GET requests to verify each URL, and stores broken
 * links in the custom database table.
 *
 * @package BrokenLinkAutoFixer
 * @author  Bikas Kumar <bikas@codesala.in>
 * @company CodeSala — codesala.in
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLAF_Link_Scanner {

	/**
	 * Maximum number of links to check per batch (reads from settings).
	 *
	 * @var int
	 */
	private $max_links;

	/**
	 * HTTP codes considered "broken" links.
	 *
	 * @var array
	 */
	private $broken_codes = array( 0, 400, 401, 403, 404, 405, 408, 410, 500, 502, 503, 504 );

	/**
	 * In-memory cache to avoid re-checking the same URL twice in one scan.
	 *
	 * @var array
	 */
	private $url_cache = array();

	/**
	 * Constructor — load settings.
	 */
	public function __construct() {
		$this->max_links = absint( get_option( 'blaf_max_links', 100 ) );
		if ( $this->max_links < 1 ) {
			$this->max_links = 100;
		}
	}

	// ─── Public API ──────────────────────────────────────────────────────────

	/**
	 * Run a full site scan.
	 * Fetches posts in batches and checks every link.
	 *
	 * @return array { found: int, checked: int }
	 */
	public function run_full_scan() {
		// Prevent overlapping scans via a transient lock.
		if ( get_transient( 'blaf_scan_running' ) ) {
			return array( 'error' => __( 'A scan is already running.', 'broken-link-auto-fixer' ) );
		}
		set_transient( 'blaf_scan_running', 1, HOUR_IN_SECONDS );

		$stats    = array( 'found' => 0, 'checked' => 0 );
		$posts    = $this->get_all_posts();
		$checked  = 0;

		foreach ( $posts as $post ) {
			$links = $this->extract_links( $post->post_content );

			foreach ( $links as $url ) {
				if ( $checked >= $this->max_links ) {
					break 2; // Exit both loops.
				}

				$http_code = $this->check_url( $url );
				$checked++;

				if ( in_array( $http_code, $this->broken_codes, true ) ) {
					BLAF_Database::insert_link( $post->ID, $post->post_title, $url, $http_code );
					$stats['found']++;
				}
			}
		}

		$stats['checked'] = $checked;

		// Cache last scan time.
		update_option( 'blaf_last_scan', current_time( 'mysql' ) );

		// Send email alert if broken links were found.
		if ( $stats['found'] > 0 && get_option( 'blaf_email_alerts' ) ) {
			$this->send_email_alert( $stats['found'] );
		}

		delete_transient( 'blaf_scan_running' );

		return $stats;
	}

	/**
	 * Scan a single post by ID.
	 *
	 * @param int $post_id
	 * @return array { found: int, checked: int }
	 */
	public function scan_post( $post_id ) {
		$post = get_post( absint( $post_id ) );
		if ( ! $post ) {
			return array( 'error' => __( 'Post not found.', 'broken-link-auto-fixer' ) );
		}

		$stats   = array( 'found' => 0, 'checked' => 0 );
		$links   = $this->extract_links( $post->post_content );

		foreach ( $links as $url ) {
			$http_code = $this->check_url( $url );
			$stats['checked']++;

			if ( in_array( $http_code, $this->broken_codes, true ) ) {
				BLAF_Database::insert_link( $post->ID, $post->post_title, $url, $http_code );
				$stats['found']++;
			}
		}

		return $stats;
	}

	// ─── Private Helpers ─────────────────────────────────────────────────────

	/**
	 * Retrieve all published posts and pages.
	 *
	 * @return WP_Post[]
	 */
	private function get_all_posts() {
		return get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'all',
		) );
	}

	/**
	 * Extract all href values from HTML content using a regex.
	 *
	 * @param string $content  Post content (HTML).
	 * @return array           Unique, absolute URLs.
	 */
	public function extract_links( $content ) {
		if ( empty( $content ) ) {
			return array();
		}

		$urls = array();

		// Match all href="..." values.
		preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches );

		if ( empty( $matches[1] ) ) {
			return array();
		}

		foreach ( $matches[1] as $url ) {
			$url = trim( $url );

			// Skip empty, anchor-only, mailto, tel, javascript links.
			if ( empty( $url )
				|| 0 === strpos( $url, '#' )
				|| 0 === strpos( $url, 'mailto:' )
				|| 0 === strpos( $url, 'tel:' )
				|| 0 === strpos( $url, 'javascript:' )
			) {
				continue;
			}

			// Only check absolute URLs.
			if ( 0 !== strpos( $url, 'http' ) ) {
				continue;
			}

			$urls[] = esc_url_raw( $url );
		}

		return array_unique( $urls );
	}

	/**
	 * Check a URL via HTTP HEAD (falls back to GET) and return the status code.
	 *
	 * Uses in-memory cache so the same URL is only requested once per scan.
	 *
	 * @param string $url
	 * @return int HTTP status code; 0 on timeout / connection error.
	 */
	public function check_url( $url ) {
		if ( isset( $this->url_cache[ $url ] ) ) {
			return $this->url_cache[ $url ];
		}

		$args = array(
			'timeout'    => 10,
			'method'     => 'HEAD',
			'user-agent' => 'BrokenLinkAutoFixer/1.0 (WordPress; +https://codesala.in)',
			'sslverify'  => false,
			'redirection'=> 5,
		);

		$response = wp_remote_head( $url, $args );

		// Some servers don't support HEAD — fall back to GET.
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 405 ) {
			$args['method'] = 'GET';
			$response       = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			$code = 0; // Treat connection errors as broken.
		} else {
			$code = (int) wp_remote_retrieve_response_code( $response );
		}

		$this->url_cache[ $url ] = $code;

		return $code;
	}

	// ─── Email Alert ─────────────────────────────────────────────────────────

	/**
	 * Send an email notification to the admin when broken links are found.
	 *
	 * @param int $count  Number of broken links found.
	 * @return void
	 */
	private function send_email_alert( $count ) {
		$to      = sanitize_email( get_option( 'blaf_alert_email', get_option( 'admin_email' ) ) );
		$subject = sprintf(
			/* translators: %d: number of broken links */
			__( '[%s] %d Broken Links Detected', 'broken-link-auto-fixer' ),
			get_bloginfo( 'name' ),
			$count
		);

		// Fetch the latest broken links for the email body.
		$links   = BLAF_Database::get_links( array( 'status' => 'broken', 'limit' => 20 ) );
		$rows    = '';
		foreach ( $links as $link ) {
			$rows .= sprintf(
				"  - [%d] %s\n    URL: %s\n    Page: %s\n\n",
				$link->http_code,
				esc_html( $link->post_title ),
				esc_url( $link->broken_url ),
				esc_url( get_permalink( $link->post_id ) )
			);
		}

		$message = sprintf(
			/* translators: 1: site name, 2: count, 3: link rows, 4: dashboard URL */
			__(
				"Hello,\n\nThe Broken Link Auto Fixer plugin has detected %1\$d broken link(s) on %2\$s.\n\n%3\$s\nView and fix them in your dashboard:\n%4\$s\n\n— Broken Link Auto Fixer by CodeSala (codesala.in)",
				'broken-link-auto-fixer'
			),
			$count,
			get_bloginfo( 'name' ),
			$rows,
			admin_url( 'admin.php?page=broken-link-auto-fixer' )
		);

		wp_mail( $to, $subject, $message );
	}
}
