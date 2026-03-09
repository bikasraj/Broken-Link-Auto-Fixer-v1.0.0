<?php
/**
 * Settings page template for the admin area.
 *
 * @link       https://codesala.in
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$auto_scan    = get_option( 'blaf_auto_scan_enabled', 0 );
$frequency    = get_option( 'blaf_scan_frequency', 'daily' );
$max_links    = get_option( 'blaf_max_links_per_scan', 100 );
$email_notif  = get_option( 'blaf_email_notifications', 0 );
$notif_email  = get_option( 'blaf_notification_email', get_option( 'admin_email' ) );
?>
<div class="wrap blaf-wrap">

	<h1 class="blaf-page-title">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'Broken Link Auto Fixer — Settings', 'broken-link-auto-fixer' ); ?>
	</h1>

	<div id="blaf-settings-message" class="blaf-notice" style="display:none;"></div>

	<div class="blaf-card blaf-settings-card">
		<table class="form-table blaf-settings-table" role="presentation">

			<tr>
				<th scope="row">
					<label for="blaf-auto-scan"><?php esc_html_e( 'Automatic Daily Scan', 'broken-link-auto-fixer' ); ?></label>
				</th>
				<td>
					<label class="blaf-toggle">
						<input type="checkbox" id="blaf-auto-scan" name="auto_scan_enabled" value="1" <?php checked( 1, $auto_scan ); ?>>
						<span class="blaf-toggle-slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Automatically scan for broken links on a schedule.', 'broken-link-auto-fixer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="blaf-scan-frequency"><?php esc_html_e( 'Scan Frequency', 'broken-link-auto-fixer' ); ?></label>
				</th>
				<td>
					<select id="blaf-scan-frequency" name="scan_frequency">
						<option value="hourly"     <?php selected( $frequency, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'broken-link-auto-fixer' ); ?></option>
						<option value="twicedaily" <?php selected( $frequency, 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'broken-link-auto-fixer' ); ?></option>
						<option value="daily"      <?php selected( $frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'broken-link-auto-fixer' ); ?></option>
						<option value="weekly"     <?php selected( $frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'broken-link-auto-fixer' ); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="blaf-max-links"><?php esc_html_e( 'Maximum Links Per Scan', 'broken-link-auto-fixer' ); ?></label>
				</th>
				<td>
					<input type="number" id="blaf-max-links" name="max_links_per_scan" value="<?php echo esc_attr( $max_links ); ?>" min="10" max="1000" step="10" class="small-text">
					<p class="description"><?php esc_html_e( 'Limit the number of links checked per scan batch (10–1000).', 'broken-link-auto-fixer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="blaf-email-notifications"><?php esc_html_e( 'Email Notifications', 'broken-link-auto-fixer' ); ?></label>
				</th>
				<td>
					<label class="blaf-toggle">
						<input type="checkbox" id="blaf-email-notifications" name="email_notifications" value="1" <?php checked( 1, $email_notif ); ?>>
						<span class="blaf-toggle-slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Send an email alert when broken links are detected.', 'broken-link-auto-fixer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="blaf-notification-email"><?php esc_html_e( 'Notification Email', 'broken-link-auto-fixer' ); ?></label>
				</th>
				<td>
					<input type="email" id="blaf-notification-email" name="notification_email" value="<?php echo esc_attr( $notif_email ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Email address that will receive broken link alerts.', 'broken-link-auto-fixer' ); ?></p>
				</td>
			</tr>

		</table>

		<p class="submit">
			<button id="blaf-save-settings" class="button button-primary">
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Save Settings', 'broken-link-auto-fixer' ); ?>
			</button>
		</p>
	</div>

	<div class="blaf-card blaf-info-card">
		<h2><?php esc_html_e( 'About Broken Link Auto Fixer', 'broken-link-auto-fixer' ); ?></h2>
		<p><?php esc_html_e( 'Version:', 'broken-link-auto-fixer' ); ?> <strong><?php echo esc_html( BROKEN_LINK_AUTO_FIXER_VERSION ); ?></strong></p>
		<p><?php esc_html_e( 'Author:', 'broken-link-auto-fixer' ); ?> <a href="https://codesala.in" target="_blank" rel="noopener noreferrer">Bikas Kumar &mdash; Codesala</a></p>
		<p>
			<a href="https://codesala.in" class="button" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit Codesala', 'broken-link-auto-fixer' ); ?></a>
		</p>
	</div>

</div><!-- .blaf-wrap -->
