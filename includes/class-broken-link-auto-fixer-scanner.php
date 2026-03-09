<?php
/**
 * Link Scanner Engine.
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
 * Scans WordPress posts and pages for broken links.
 *
 * Fetches post content, extracts all anchor href values via regex,
 * checks each URL with wp_remote_get(), and stores broken ones in
 * the custom database table.
 *
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/includes
 * @author     Bikas Kumar <bikas@codesala.in>
 */
class Broken_Link_Auto_Fixer_Scanner {

	/**
	 * HTTP status codes that indicate a broken link.
	 *
	 * @since    1.0.0
	 * @var      array
	 */
	private $broken_codes = array( 0, 400, 401, 403, 404, 408, 410, 500, 502, 503, 504 );

	/**
	 * Maximum number of links to scan per batch (configurable via settings).
	 *
	 * @since    1.0.0
	 * @var      int
	 */
	private $max_links;

	/**
	 * Constructor — loads settings.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->max_links = absint( get_option( 'blaf_max_links_per_scan', 100 ) );
	}

	/**
	 * Run a full scan across all published posts and pages.
	 *
	 * Returns a summary array with counts for reporting / AJAX responses.
	 *
	 * @since    1.0.0
	 * @param    int $offset  Number of posts to skip (for batch/AJAX scanning).
	 * @return   array  { checked: int, broken: int, done: bool }
	 */
	public function run_scan( $offset = 0 ) {
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-database.php';

		$summary = array(
			'checked' => 0,
			'broken'  => 0,
			'done'    => false,
		);

		$batch_size = 10; // posts per AJAX batch.

		$posts = get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'numberposts'    => $batch_size,
			'offset'         => absint( $offset ),
			'fields'         => 'all',
		) );

		if ( empty( $posts ) ) {
			$summary['done'] = true;
			return $summary;
		}

		$links_checked = 0;

		foreach ( $posts as $post ) {
			$links = $this->extract_links( $post->post_content );

			foreach ( $links as $link ) {
				if ( $links_checked >= $this->max_links ) {
					break 2; // Stop scanning once we hit the limit.
				}

				$url        = $link['url'];
				$anchor     = $link['anchor'];
				$http_code  = $this->check_url( $url );

				$links_checked++;
				$summary['checked']++;

				if ( in_array( $http_code, $this->broken_codes, true ) ) {
					// Only insert if not already tracked.
					if ( ! Broken_Link_Auto_Fixer_Database::link_exists( $post->ID, $url ) ) {
						Broken_Link_Auto_Fixer_Database::insert_broken_link( array(
							'post_id'    => $post->ID,
							'post_title' => $post->post_title,
							'broken_url' => $url,
							'anchor_text'=> $anchor,
							'http_code'  => $http_code,
						) );
					}
					$summary['broken']++;
				}
			}
		}

		// If fewer posts than batch size returned, scan is complete.
		if ( count( $posts ) < $batch_size ) {
			$summary['done'] = true;
		}

		// Trigger email notification if any broken links were found.
		if ( $summary['broken'] > 0 ) {
			$this->send_email_notification( $summary['broken'] );
		}

		return $summary;
	}

	/**
	 * Extract all <a href="..."> links from HTML content.
	 *
	 * Uses a regex that handles both single and double-quoted href values.
	 * Only returns absolute HTTP/HTTPS URLs to avoid scanning internal anchors,
	 * mailto: links, or relative paths.
	 *
	 * @since    1.0.0
	 * @param    string $content  Post HTML content.
	 * @return   array  Array of ['url' => string, 'anchor' => string].
	 */
	public function extract_links( $content ) {
		$links = array();

		// Match <a href="...">...</a> patterns.
		if ( preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$url    = trim( $match[1] );
				$anchor = wp_strip_all_tags( $match[2] );

				// Only check absolute HTTP/HTTPS URLs.
				if ( preg_match( '/^https?:\/\//i', $url ) ) {
					$links[] = array(
						'url'    => $url,
						'anchor' => $anchor,
					);
				}
			}
		}

		return $links;
	}

	/**
	 * Send an HTTP HEAD (or GET fallback) request to check a URL status.
	 *
	 * Uses wp_remote_head() for efficiency. Falls back to wp_remote_get()
	 * for servers that block HEAD requests. Returns 0 for timeouts/unreachable hosts.
	 *
	 * @since    1.0.0
	 * @param    string $url  The URL to check.
	 * @return   int    HTTP status code, or 0 on failure/timeout.
	 */
	public function check_url( $url ) {
		$args = array(
			'timeout'     => 10,
			'redirection' => 3,
			'sslverify'   => false,
			'user-agent'  => 'Mozilla/5.0 (compatible; BrokenLinkAutoFixer/' . BROKEN_LINK_AUTO_FIXER_VERSION . '; +https://codesala.in)',
		);

		$response = wp_remote_head( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			// Fallback to GET if HEAD fails.
			$response = wp_remote_get( esc_url_raw( $url ), $args );
		}

		if ( is_wp_error( $response ) ) {
			return 0; // Unreachable.
		}

		return (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Send an email alert to the admin when broken links are found.
	 *
	 * Only sends if email notifications are enabled in settings.
	 *
	 * @since    1.0.0
	 * @param    int $count  Number of new broken links found.
	 */
	private function send_email_notification( $count ) {
		if ( ! get_option( 'blaf_email_notifications', 0 ) ) {
			return;
		}

		$to      = sanitize_email( get_option( 'blaf_notification_email', get_option( 'admin_email' ) ) );
		$subject = sprintf(
			/* translators: %d: number of broken links */
			__( '[%s] Broken Link Auto Fixer: %d new broken link(s) found', 'broken-link-auto-fixer' ),
			get_bloginfo( 'name' ),
			$count
		);

		$body  = sprintf(
			/* translators: %d: number of broken links */
			__( "Hello,\n\nThe Broken Link Auto Fixer scan has detected %d new broken link(s) on your website.\n\nPlease log in to your WordPress admin panel to review and fix them:\n%s\n\n-- Broken Link Auto Fixer\nPowered by Codesala | https://codesala.in", 'broken-link-auto-fixer' ),
			$count,
			admin_url( 'admin.php?page=broken-link-auto-fixer' )
		);

		wp_mail( $to, $subject, $body );
	}
}
