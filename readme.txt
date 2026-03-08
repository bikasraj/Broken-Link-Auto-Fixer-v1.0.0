=== Broken Link Auto Fixer ===
Contributors:      bikas-kumar-codesala
Tags:              broken links, link checker, 404 links, SEO, link fixer
Requires at least: 5.8
Tested up to:      6.7
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Scan your WordPress site for broken links (404, 500 errors) and fix or remove them directly from the dashboard.

== Description ==

**Broken Link Auto Fixer** is a powerful yet easy-to-use plugin that keeps your website free of dead links — protecting your SEO rankings and improving the user experience.

= Key Features =

* **One-Click Scan** — Instantly scan all posts and pages for broken links.
* **Background Scanning** — AJAX-powered scanning with a live progress bar so your browser never freezes.
* **Replace URL** — Replace a broken URL with a working one directly from the dashboard; post content updates automatically.
* **Remove Link** — Strip the `<a>` tag while preserving the anchor text.
* **HTTP Status Codes** — See the exact error code (404, 500, timeout) for every broken link.
* **Automatic Daily Scan** — Schedule a WP-Cron job to scan every 24 hours automatically.
* **Email Alerts** — Receive an email when new broken links are detected.
* **Batch Scanning** — Set a maximum link limit per scan to avoid server timeouts.
* **Ignore / Delete Records** — Mark links as ignored or delete records you no longer need.
* **Modern Dashboard** — Clean, WordPress-native UI with statistics bar, sortable table, and modal popups.
* **Secure** — Nonce verification, capability checks, prepared SQL statements, and output escaping throughout.

== Installation ==

**Automatic Installation (Recommended)**

1. Log in to your WordPress admin dashboard.
2. Navigate to **Plugins → Add New**.
3. Search for "Broken Link Auto Fixer".
4. Click **Install Now**, then **Activate**.

**Manual Installation**

1. Download the plugin ZIP file.
2. Navigate to **Plugins → Add New → Upload Plugin**.
3. Select the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

**FTP Installation**

1. Extract the ZIP file.
2. Upload the `broken-link-auto-fixer` folder to `/wp-content/plugins/`.
3. Activate the plugin from **Plugins → Installed Plugins**.

== Frequently Asked Questions ==

= How do I scan my website? =

After activation, go to **Broken Links** in the admin sidebar and click the **Scan Website Now** button. The progress bar will animate while your links are being checked.

= Does the scanner check external links? =

Yes. The scanner checks all absolute HTTP/HTTPS links found in post content, including external websites.

= Will scanning slow down my server? =

Each link is checked with a lightweight HEAD request (falling back to GET if needed). You can limit the number of links checked per scan via **Settings → Maximum Links Per Scan**. For large sites, use the automatic daily scan (cron) to spread the load over time.

= What HTTP status codes are considered "broken"? =

By default: 0 (timeout/connection error), 400, 401, 403, 404, 405, 408, 410, 500, 502, 503, 504.

= What does "Remove Link" do? =

It strips the `<a>` tag from the post content but preserves the anchor text (the visible link label). The post is updated automatically.

= What does "Replace URL" do? =

It replaces the broken URL in the post's content with the new URL you enter. The post is saved automatically.

= Is my data deleted when I uninstall the plugin? =

Yes. The plugin's custom database table (`wp_broken_links`) and all options are removed on uninstall.

= Does the plugin support multisite? =

Single-site is officially supported in v1.0.0. Multisite support is planned for a future release.

= Is there a premium version? =

No. This plugin is completely free and open source under the GPL v2 or later license.

== Screenshots ==

1. Dashboard — Broken links table with HTTP codes, post titles, and action buttons.
2. Scan Progress — Live progress bar while a scan is running.
3. Replace URL Modal — Enter a replacement URL directly from the dashboard.
4. Settings Page — Configure auto-scan, email alerts, and scan limits.

== Changelog ==

= 1.0.0 =
* Initial release.
* Full site scanning via AJAX with progress bar.
* Replace URL and Remove Link fix options.
* Email alerts for broken link detection.
* Automatic daily scan via WP-Cron.
* Settings page: auto-scan, max links, email alerts.
* Nonce-secured AJAX endpoints.
* Uninstall cleanup (table + options).

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade required.
