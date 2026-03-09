=== Broken Link Auto Fixer ===
Contributors: bikaskumar
Tags: broken links, link checker, 404 links, dead links, link fixer
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scan your WordPress posts and pages for broken links (404, 500 errors) and fix or remove them directly from the admin dashboard.

== Description ==

**Broken Link Auto Fixer** is a lightweight WordPress plugin that automatically scans all your posts and pages for broken links (HTTP 404, 500, and other error codes), and lets you fix them — right from your WordPress dashboard.

Built by **Bikas Kumar** at [Codesala](https://codesala.in) using the WordPress Plugin Boilerplate.

= Key Features =

* **One-click scan** — Scan your entire site with a single button click.
* **AJAX batched scanning** — Scans in small batches to avoid server timeouts. Shows a live progress bar.
* **Replace URL** — Replace a broken URL with a working one. Post content is updated automatically.
* **Remove Link** — Remove a broken link's anchor tag while keeping the visible text.
* **Ignore Link** — Mark a link as ignored so it won't clutter your results.
* **HTTP status display** — See the exact error code (404, 500, 403, Timeout, etc.) for each broken link.
* **Scheduled automatic scanning** — Set up hourly, daily, or weekly automated scans via WP-Cron.
* **Email notifications** — Receive an email alert whenever broken links are found.
* **Clean uninstall** — Removes all database tables and plugin options on uninstall.
* **WordPress Coding Standards** — Built with security best practices: nonces, capability checks, sanitization, and prepared SQL queries.

= Use Cases =

* SEO managers who want to keep all outbound and internal links healthy.
* Content editors maintaining large blog archives.
* Site owners who want automated weekly health checks.

== Installation ==

1. Upload the `broken-link-auto-fixer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Broken Links → Dashboard** in your WordPress admin menu.
4. Click **Scan Website for Broken Links** to run your first scan.
5. Configure automated scanning under **Broken Links → Settings**.

== Frequently Asked Questions ==

= Does this plugin slow down my website? =
No. Scanning happens only when you click the scan button (or when WP-Cron triggers it). It does not affect your front-end performance at all.

= How does the scanner check links? =
It uses WordPress's built-in `wp_remote_head()` (with a fallback to `wp_remote_get()`) to send HTTP requests to each URL and checks the response code.

= What HTTP codes are considered "broken"? =
0 (timeout/unreachable), 400, 401, 403, 404, 408, 410, 500, 502, 503, 504.

= Will it fix internal links too? =
Yes — the scanner checks all absolute `http://` and `https://` URLs found in post content, whether they are internal or external.

= Does it scan custom post types? =
The current version scans Posts and Pages. Support for custom post types is planned for a future release.

= What happens when I uninstall? =
The plugin removes its custom database table (`wp_broken_links`) and all options from `wp_options`. Your posts and pages are untouched.

== Screenshots ==

1. Dashboard showing the broken links table with Replace, Remove, and Ignore actions.
2. Scan in progress with animated progress bar.
3. Settings page with scheduling and email notification options.

== Changelog ==

= 1.0.0 =
* Initial release.
* AJAX-powered batched scanner.
* Replace URL, Remove Link, Ignore Link actions.
* Scheduled scanning via WP-Cron.
* Email notifications on broken link detection.
* Settings page with frequency and email configuration.
* Full uninstall cleanup.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
