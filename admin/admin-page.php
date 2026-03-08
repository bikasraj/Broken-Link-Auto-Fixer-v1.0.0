<?php
/**
 * Admin Dashboard Page Template
 *
 * Renders the Broken Links dashboard table and scan controls.
 *
 * Variables available (set by BLAF_Admin_Page::render_dashboard):
 *   $links     array   — Broken link records from the DB.
 *   $total     int     — Total broken link count.
 *   $last_scan string  — Date/time of last scan or "Never".
 *
 * @package BrokenLinkAutoFixer
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap blaf-wrap">

	<!-- ── Header ────────────────────────────────────────────── -->
	<div class="blaf-header">
		<div class="blaf-header-left">
			<span class="dashicons dashicons-editor-unlink blaf-icon"></span>
			<h1><?php esc_html_e( 'Broken Link Auto Fixer', 'broken-link-auto-fixer' ); ?></h1>
		</div>
		<div class="blaf-header-right">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=blaf-settings' ) ); ?>" class="button">
				<?php esc_html_e( 'Settings', 'broken-link-auto-fixer' ); ?>
			</a>
		</div>
	</div>

	<!-- ── Stats Bar ─────────────────────────────────────────── -->
	<div class="blaf-stats-bar">
		<div class="blaf-stat-card">
			<span class="blaf-stat-number" id="blaf-total-count"><?php echo esc_html( $total ); ?></span>
			<span class="blaf-stat-label"><?php esc_html_e( 'Broken Links', 'broken-link-auto-fixer' ); ?></span>
		</div>
		<div class="blaf-stat-card">
			<span class="blaf-stat-number"><?php echo esc_html( get_option( 'blaf_max_links', 100 ) ); ?></span>
			<span class="blaf-stat-label"><?php esc_html_e( 'Max per Scan', 'broken-link-auto-fixer' ); ?></span>
		</div>
		<div class="blaf-stat-card">
			<span class="blaf-stat-number blaf-stat-last-scan">
				<?php echo esc_html( $last_scan ); ?>
			</span>
			<span class="blaf-stat-label"><?php esc_html_e( 'Last Scan', 'broken-link-auto-fixer' ); ?></span>
		</div>
	</div>

	<!-- ── Scan Controls ─────────────────────────────────────── -->
	<div class="blaf-scan-box">
		<button id="blaf-start-scan" class="button button-primary button-hero">
			<span class="dashicons dashicons-search"></span>
			<?php esc_html_e( 'Scan Website Now', 'broken-link-auto-fixer' ); ?>
		</button>

		<!-- Progress bar (hidden until scan starts) -->
		<div id="blaf-progress-wrapper" style="display:none;">
			<div class="blaf-progress-bar-outer">
				<div class="blaf-progress-bar-inner" id="blaf-progress-bar"></div>
			</div>
			<p id="blaf-progress-text" class="blaf-progress-text">
				<?php esc_html_e( 'Scanning links, please wait...', 'broken-link-auto-fixer' ); ?>
			</p>
		</div>

		<!-- Result message -->
		<div id="blaf-scan-result" class="blaf-notice" style="display:none;"></div>
	</div>

	<!-- ── Broken Links Table ─────────────────────────────────── -->
	<h2 class="blaf-section-title">
		<?php esc_html_e( 'Detected Broken Links', 'broken-link-auto-fixer' ); ?>
		<span class="blaf-badge"><?php echo esc_html( $total ); ?></span>
	</h2>

	<?php if ( empty( $links ) ) : ?>
		<div class="blaf-empty-state">
			<span class="dashicons dashicons-yes-alt blaf-empty-icon"></span>
			<p><?php esc_html_e( 'No broken links found. Run a scan to check your site!', 'broken-link-auto-fixer' ); ?></p>
		</div>

	<?php else : ?>
		<!-- Replace-URL Modal -->
		<div id="blaf-modal-overlay" class="blaf-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="blaf-modal-title">
			<div class="blaf-modal">
				<h3 id="blaf-modal-title"><?php esc_html_e( 'Replace Broken URL', 'broken-link-auto-fixer' ); ?></h3>
				<p class="blaf-modal-old-url"></p>
				<label for="blaf-new-url"><?php esc_html_e( 'New URL:', 'broken-link-auto-fixer' ); ?></label>
				<input type="url" id="blaf-new-url" class="regular-text" placeholder="https://example.com/new-page">
				<div class="blaf-modal-actions">
					<button id="blaf-confirm-replace" class="button button-primary">
						<?php esc_html_e( 'Replace', 'broken-link-auto-fixer' ); ?>
					</button>
					<button id="blaf-cancel-replace" class="button">
						<?php esc_html_e( 'Cancel', 'broken-link-auto-fixer' ); ?>
					</button>
				</div>
				<div id="blaf-modal-result" class="blaf-notice" style="display:none;"></div>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped blaf-links-table">
			<thead>
				<tr>
					<th scope="col" class="blaf-col-id"><?php esc_html_e( '#', 'broken-link-auto-fixer' ); ?></th>
					<th scope="col" class="blaf-col-code"><?php esc_html_e( 'HTTP Code', 'broken-link-auto-fixer' ); ?></th>
					<th scope="col" class="blaf-col-title"><?php esc_html_e( 'Post / Page', 'broken-link-auto-fixer' ); ?></th>
					<th scope="col" class="blaf-col-url"><?php esc_html_e( 'Broken URL', 'broken-link-auto-fixer' ); ?></th>
					<th scope="col" class="blaf-col-date"><?php esc_html_e( 'Date Found', 'broken-link-auto-fixer' ); ?></th>
					<th scope="col" class="blaf-col-actions"><?php esc_html_e( 'Actions', 'broken-link-auto-fixer' ); ?></th>
				</tr>
			</thead>
			<tbody id="blaf-links-tbody">
			<?php foreach ( $links as $link ) :
				$http_class = ( (int) $link->http_code >= 500 ) ? 'blaf-code-server' : 'blaf-code-client';
				if ( 0 === (int) $link->http_code ) {
					$http_class = 'blaf-code-timeout';
				}
			?>
				<tr id="blaf-row-<?php echo esc_attr( $link->id ); ?>">
					<td><?php echo esc_html( $link->id ); ?></td>
					<td>
						<span class="blaf-http-badge <?php echo esc_attr( $http_class ); ?>">
							<?php echo esc_html( $link->http_code ? $link->http_code : 'ERR' ); ?>
						</span>
					</td>
					<td>
						<strong><?php echo esc_html( $link->post_title ); ?></strong><br>
						<a href="<?php echo esc_url( get_permalink( $link->post_id ) ); ?>" target="_blank" rel="noopener noreferrer" class="blaf-view-post">
							<?php esc_html_e( 'View Post', 'broken-link-auto-fixer' ); ?> &#8599;
						</a>
					</td>
					<td>
						<a href="<?php echo esc_url( $link->broken_url ); ?>" target="_blank" rel="noopener noreferrer" class="blaf-broken-url" title="<?php echo esc_attr( $link->broken_url ); ?>">
							<?php echo esc_html( mb_strimwidth( $link->broken_url, 0, 60, '...' ) ); ?>
						</a>
					</td>
					<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $link->date_found ) ) ); ?></td>
					<td class="blaf-action-cell">
						<button class="button button-small blaf-btn-replace"
							data-id="<?php echo esc_attr( $link->id ); ?>"
							data-url="<?php echo esc_attr( $link->broken_url ); ?>">
							<?php esc_html_e( 'Replace URL', 'broken-link-auto-fixer' ); ?>
						</button>

						<button class="button button-small blaf-btn-remove"
							data-id="<?php echo esc_attr( $link->id ); ?>"
							data-confirm="<?php esc_attr_e( 'Remove this link from post content?', 'broken-link-auto-fixer' ); ?>">
							<?php esc_html_e( 'Remove Link', 'broken-link-auto-fixer' ); ?>
						</button>

						<button class="button button-small blaf-btn-ignore"
							data-id="<?php echo esc_attr( $link->id ); ?>">
							<?php esc_html_e( 'Ignore', 'broken-link-auto-fixer' ); ?>
						</button>

						<button class="button button-small blaf-btn-delete"
							data-id="<?php echo esc_attr( $link->id ); ?>"
							data-confirm="<?php esc_attr_e( 'Delete this record from the database?', 'broken-link-auto-fixer' ); ?>">
							<?php esc_html_e( 'Delete', 'broken-link-auto-fixer' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

</div><!-- .blaf-wrap -->
