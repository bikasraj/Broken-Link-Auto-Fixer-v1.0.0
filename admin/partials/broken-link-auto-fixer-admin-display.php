<?php
/**
 * Dashboard page template for the admin area.
 *
 * @link       https://codesala.in
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-database.php';

$per_page     = 20;
$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
$broken_links = Broken_Link_Auto_Fixer_Database::get_broken_links( $per_page, $current_page );
$total_count  = Broken_Link_Auto_Fixer_Database::count_broken_links();
$total_pages  = (int) ceil( $total_count / $per_page );
?>
<div class="wrap blaf-wrap">

	<h1 class="blaf-page-title">
		<span class="dashicons dashicons-editor-unlink"></span>
		<?php esc_html_e( 'Broken Link Auto Fixer', 'broken-link-auto-fixer' ); ?>
		<span class="blaf-version">v<?php echo esc_html( BROKEN_LINK_AUTO_FIXER_VERSION ); ?></span>
	</h1>

	<p class="blaf-tagline">
		<?php esc_html_e( 'Powered by', 'broken-link-auto-fixer' ); ?>
		<a href="https://codesala.in" target="_blank" rel="noopener noreferrer">Codesala</a>
		&mdash; <?php esc_html_e( 'Author: Bikas Kumar', 'broken-link-auto-fixer' ); ?>
	</p>

	<!-- ── Scan Controls ─────────────────────────────────────── -->
	<div class="blaf-card blaf-scan-card">
		<div class="blaf-scan-controls">
			<button id="blaf-start-scan" class="button button-primary button-hero">
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'Scan Website for Broken Links', 'broken-link-auto-fixer' ); ?>
			</button>
			<button id="blaf-clear-results" class="button button-secondary">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Clear All Results', 'broken-link-auto-fixer' ); ?>
			</button>
		</div>

		<!-- Progress bar (hidden by default) -->
		<div id="blaf-scan-progress" class="blaf-progress-wrap" style="display:none;">
			<div class="blaf-progress-bar-outer">
				<div id="blaf-progress-bar" class="blaf-progress-bar-inner"></div>
			</div>
			<p id="blaf-scan-status" class="blaf-scan-status"></p>
		</div>

		<div id="blaf-scan-message" class="blaf-notice" style="display:none;"></div>
	</div>

	<!-- ── Stats Bar ─────────────────────────────────────────── -->
	<div class="blaf-stats-bar">
		<div class="blaf-stat-box">
			<span class="blaf-stat-number" id="blaf-total-count"><?php echo esc_html( $total_count ); ?></span>
			<span class="blaf-stat-label"><?php esc_html_e( 'Broken Links', 'broken-link-auto-fixer' ); ?></span>
		</div>
	</div>

	<!-- ── Broken Links Table ────────────────────────────────── -->
	<div class="blaf-card">
		<?php if ( empty( $broken_links ) ) : ?>
			<div class="blaf-empty-state">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e( 'No broken links found. Click "Scan Website" to run a scan.', 'broken-link-auto-fixer' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped blaf-table" id="blaf-links-table">
				<thead>
					<tr>
						<th scope="col" class="column-id"><?php esc_html_e( 'ID', 'broken-link-auto-fixer' ); ?></th>
						<th scope="col" class="column-post"><?php esc_html_e( 'Post / Page', 'broken-link-auto-fixer' ); ?></th>
						<th scope="col" class="column-url"><?php esc_html_e( 'Broken URL', 'broken-link-auto-fixer' ); ?></th>
						<th scope="col" class="column-anchor"><?php esc_html_e( 'Anchor Text', 'broken-link-auto-fixer' ); ?></th>
						<th scope="col" class="column-code"><?php esc_html_e( 'HTTP Code', 'broken-link-auto-fixer' ); ?></th>
						<th scope="col" class="column-date"><?php esc_html_e( 'Date Found', 'broken-link-auto-fixer' ); ?></th>
						<th scope="col" class="column-actions"><?php esc_html_e( 'Actions', 'broken-link-auto-fixer' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $broken_links as $link ) : ?>
						<tr id="blaf-row-<?php echo esc_attr( $link->id ); ?>" class="blaf-link-row">
							<td class="column-id"><?php echo esc_html( $link->id ); ?></td>
							<td class="column-post">
								<a href="<?php echo esc_url( get_edit_post_link( $link->post_id ) ); ?>" target="_blank">
									<?php echo esc_html( $link->post_title ); ?>
								</a>
								<br>
								<small><?php echo esc_html( __( 'Post ID: ', 'broken-link-auto-fixer' ) . $link->post_id ); ?></small>
							</td>
							<td class="column-url">
								<a href="<?php echo esc_url( $link->broken_url ); ?>" target="_blank" rel="noopener noreferrer" class="blaf-broken-url">
									<?php echo esc_html( $link->broken_url ); ?>
								</a>
							</td>
							<td class="column-anchor"><?php echo esc_html( $link->anchor_text ?: '—' ); ?></td>
							<td class="column-code">
								<span class="blaf-http-badge blaf-code-<?php echo esc_attr( $link->http_code ); ?>">
									<?php echo esc_html( $link->http_code ?: 'Timeout' ); ?>
								</span>
							</td>
							<td class="column-date">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $link->date_found ) ) ); ?>
							</td>
							<td class="column-actions">
								<button
									class="button blaf-btn-replace"
									data-id="<?php echo esc_attr( $link->id ); ?>"
									data-url="<?php echo esc_attr( $link->broken_url ); ?>"
									title="<?php esc_attr_e( 'Replace URL', 'broken-link-auto-fixer' ); ?>">
									<span class="dashicons dashicons-edit"></span>
									<?php esc_html_e( 'Replace', 'broken-link-auto-fixer' ); ?>
								</button>
								<button
									class="button blaf-btn-remove"
									data-id="<?php echo esc_attr( $link->id ); ?>"
									title="<?php esc_attr_e( 'Remove link, keep text', 'broken-link-auto-fixer' ); ?>">
									<span class="dashicons dashicons-no-alt"></span>
									<?php esc_html_e( 'Remove', 'broken-link-auto-fixer' ); ?>
								</button>
								<button
									class="button blaf-btn-ignore"
									data-id="<?php echo esc_attr( $link->id ); ?>"
									title="<?php esc_attr_e( 'Ignore this link', 'broken-link-auto-fixer' ); ?>">
									<span class="dashicons dashicons-hidden"></span>
									<?php esc_html_e( 'Ignore', 'broken-link-auto-fixer' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="blaf-pagination tablenav">
					<div class="tablenav-pages">
						<?php
						$page_links = paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total'     => $total_pages,
							'current'   => $current_page,
						) );
						echo $page_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div><!-- .blaf-card -->

</div><!-- .blaf-wrap -->
