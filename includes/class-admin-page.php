<?php
/**
 * Admin Page Class — BLAF_Admin_Page
 *
 * Registers the WordPress admin menu items and renders the
 * main dashboard page and settings page.
 *
 * @package BrokenLinkAutoFixer
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin menu registration and page rendering.
 */
class BLAF_Admin_Page {

	/**
	 * Constructor — wire up admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_blaf_save_settings', array( $this, 'save_settings' ) );
	}

	// ─── Menu Registration ───────────────────────────────────────────────────

	/**
	 * Register admin menu and sub-menu pages.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Broken Links', 'broken-link-auto-fixer' ),
			__( 'Broken Links', 'broken-link-auto-fixer' ),
			'manage_options',
			'broken-link-auto-fixer',
			array( $this, 'render_dashboard' ),
			'dashicons-editor-unlink',
			80
		);

		add_submenu_page(
			'broken-link-auto-fixer',
			__( 'Dashboard', 'broken-link-auto-fixer' ),
			__( 'Dashboard', 'broken-link-auto-fixer' ),
			'manage_options',
			'broken-link-auto-fixer',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'broken-link-auto-fixer',
			__( 'Settings', 'broken-link-auto-fixer' ),
			__( 'Settings', 'broken-link-auto-fixer' ),
			'manage_options',
			'blaf-settings',
			array( $this, 'render_settings' )
		);
	}

	// ─── Dashboard Page ──────────────────────────────────────────────────────

	/**
	 * Render the main broken-links dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'broken-link-auto-fixer' ) );
		}

		$links     = BLAF_Database::get_links( array( 'status' => 'broken' ) );
		$total     = BLAF_Database::count_broken();
		$last_scan = get_option( 'blaf_last_scan', __( 'Never', 'broken-link-auto-fixer' ) );

		// Include the view template.
		require_once BLAF_PLUGIN_DIR . 'admin/admin-page.php';
	}

	// ─── Settings Page ───────────────────────────────────────────────────────

	/**
	 * Render the plugin settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'broken-link-auto-fixer' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$saved = isset( $_GET['saved'] ) && '1' === $_GET['saved'];
		?>
		<div class="wrap blaf-wrap">
			<h1><?php esc_html_e( 'Broken Link Auto Fixer &mdash; Settings', 'broken-link-auto-fixer' ); ?></h1>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'broken-link-auto-fixer' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'blaf_save_settings', 'blaf_settings_nonce' ); ?>
				<input type="hidden" name="action" value="blaf_save_settings">

				<table class="form-table blaf-settings-table" role="presentation">

					<tr>
						<th scope="row">
							<label for="blaf_scan_enabled"><?php esc_html_e( 'Enable Scanning', 'broken-link-auto-fixer' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="blaf_scan_enabled" name="blaf_scan_enabled" value="1"
								<?php checked( 1, get_option( 'blaf_scan_enabled', 1 ) ); ?>>
							<span class="description"><?php esc_html_e( 'Allow the plugin to scan links.', 'broken-link-auto-fixer' ); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="blaf_auto_scan"><?php esc_html_e( 'Automatic Daily Scan', 'broken-link-auto-fixer' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="blaf_auto_scan" name="blaf_auto_scan" value="1"
								<?php checked( 1, get_option( 'blaf_auto_scan', 0 ) ); ?>>
							<span class="description"><?php esc_html_e( 'Run a scan automatically every 24 hours via WP-Cron.', 'broken-link-auto-fixer' ); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="blaf_max_links"><?php esc_html_e( 'Maximum Links Per Scan', 'broken-link-auto-fixer' ); ?></label>
						</th>
						<td>
							<input type="number" id="blaf_max_links" name="blaf_max_links" min="10" max="1000" step="10"
								value="<?php echo esc_attr( get_option( 'blaf_max_links', 100 ) ); ?>" class="small-text">
							<span class="description"><?php esc_html_e( 'Limit the number of links checked per scan to avoid timeouts.', 'broken-link-auto-fixer' ); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="blaf_email_alerts"><?php esc_html_e( 'Email Alerts', 'broken-link-auto-fixer' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="blaf_email_alerts" name="blaf_email_alerts" value="1"
								<?php checked( 1, get_option( 'blaf_email_alerts', 1 ) ); ?>>
							<span class="description"><?php esc_html_e( 'Send an email when broken links are detected.', 'broken-link-auto-fixer' ); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="blaf_alert_email"><?php esc_html_e( 'Alert Email Address', 'broken-link-auto-fixer' ); ?></label>
						</th>
						<td>
							<input type="email" id="blaf_alert_email" name="blaf_alert_email"
								value="<?php echo esc_attr( get_option( 'blaf_alert_email', get_option( 'admin_email' ) ) ); ?>"
								class="regular-text">
						</td>
					</tr>

				</table>

				<?php submit_button( __( 'Save Settings', 'broken-link-auto-fixer' ) ); ?>
			</form>
		</div>
		<?php
	}

	// ─── Settings Save Handler ───────────────────────────────────────────────

	/**
	 * Process the settings form submission.
	 *
	 * @return void
	 */
	public function save_settings() {
		// Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'broken-link-auto-fixer' ) );
		}

		// Nonce verification.
		check_admin_referer( 'blaf_save_settings', 'blaf_settings_nonce' );

		// Save each setting with proper sanitization.
		update_option( 'blaf_scan_enabled', isset( $_POST['blaf_scan_enabled'] ) ? 1 : 0 );
		update_option( 'blaf_auto_scan',    isset( $_POST['blaf_auto_scan'] )    ? 1 : 0 );
		update_option( 'blaf_email_alerts', isset( $_POST['blaf_email_alerts'] ) ? 1 : 0 );

		$max_links = isset( $_POST['blaf_max_links'] ) ? absint( wp_unslash( $_POST['blaf_max_links'] ) ) : 100;
		update_option( 'blaf_max_links', $max_links );

		$alert_email = isset( $_POST['blaf_alert_email'] ) ? sanitize_email( wp_unslash( $_POST['blaf_alert_email'] ) ) : '';
		update_option( 'blaf_alert_email', $alert_email );

		// Reschedule cron based on new setting.
		$timestamp = wp_next_scheduled( 'blaf_daily_scan_event' );
		if ( get_option( 'blaf_auto_scan' ) ) {
			if ( ! $timestamp ) {
				wp_schedule_event( time(), 'daily', 'blaf_daily_scan_event' );
			}
		} else {
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'blaf_daily_scan_event' );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=blaf-settings&saved=1' ) );
		exit;
	}
}
