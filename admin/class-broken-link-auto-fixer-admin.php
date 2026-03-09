<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://codesala.in
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/admin
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers the admin menu pages, enqueues assets, and
 * provides the Settings/Dashboard page rendering.
 *
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/admin
 * @author     Bikas Kumar <bikas@codesala.in>
 */
class Broken_Link_Auto_Fixer_Admin {

	/**
	 * The plugin name.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	private $plugin_name;

	/**
	 * The plugin version.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	private $version;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name  Plugin slug.
	 * @param    string $version      Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue admin stylesheets.
	 *
	 * Only loads on plugin's own admin pages to avoid conflicts.
	 *
	 * @since    1.0.0
	 * @param    string $hook  Current admin page hook.
	 */
	public function enqueue_styles( $hook ) {
		if ( false === strpos( $hook, $this->plugin_name ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			BROKEN_LINK_AUTO_FIXER_URL . 'admin/css/broken-link-auto-fixer-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueue admin JavaScript.
	 *
	 * Passes PHP data to JS via wp_localize_script.
	 *
	 * @since    1.0.0
	 * @param    string $hook  Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( false === strpos( $hook, $this->plugin_name ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			BROKEN_LINK_AUTO_FIXER_URL . 'admin/js/broken-link-auto-fixer-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'blafData',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'scan_nonce'     => wp_create_nonce( 'blaf_scan_nonce' ),
				'fix_nonce'      => wp_create_nonce( 'blaf_fix_nonce' ),
				'settings_nonce' => wp_create_nonce( 'blaf_settings_nonce' ),
				'strings'        => array(
					'scanning'        => __( 'Scanning... please wait', 'broken-link-auto-fixer' ),
					'scan_complete'   => __( 'Scan complete!', 'broken-link-auto-fixer' ),
					'scan_error'      => __( 'Scan failed. Please try again.', 'broken-link-auto-fixer' ),
					'confirm_clear'   => __( 'Are you sure you want to clear all results?', 'broken-link-auto-fixer' ),
					'confirm_remove'  => __( 'This will remove the link from the post content. Continue?', 'broken-link-auto-fixer' ),
					'enter_new_url'   => __( 'Enter the replacement URL:', 'broken-link-auto-fixer' ),
					'replace_success' => __( 'URL replaced successfully!', 'broken-link-auto-fixer' ),
					'remove_success'  => __( 'Link removed successfully!', 'broken-link-auto-fixer' ),
					'settings_saved'  => __( 'Settings saved!', 'broken-link-auto-fixer' ),
				),
			)
		);
	}

	/**
	 * Register the admin menu items.
	 *
	 * Adds a top-level menu with a Dashboard sub-page and a Settings sub-page.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Broken Link Auto Fixer', 'broken-link-auto-fixer' ),
			__( 'Broken Links', 'broken-link-auto-fixer' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'render_dashboard_page' ),
			'dashicons-editor-unlink',
			80
		);

		add_submenu_page(
			$this->plugin_name,
			__( 'Broken Links Dashboard', 'broken-link-auto-fixer' ),
			__( 'Dashboard', 'broken-link-auto-fixer' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			$this->plugin_name,
			__( 'Broken Link Auto Fixer Settings', 'broken-link-auto-fixer' ),
			__( 'Settings', 'broken-link-auto-fixer' ),
			'manage_options',
			$this->plugin_name . '-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Add plugin action links on Plugins page.
	 *
	 * @since    1.0.0
	 * @param    array $links  Existing action links.
	 * @return   array
	 */
	public function add_plugin_action_links( $links ) {
		$action_links = array(
			'<a href="' . admin_url( 'admin.php?page=' . $this->plugin_name ) . '">' . __( 'Dashboard', 'broken-link-auto-fixer' ) . '</a>',
			'<a href="' . admin_url( 'admin.php?page=' . $this->plugin_name . '-settings' ) . '">' . __( 'Settings', 'broken-link-auto-fixer' ) . '</a>',
		);
		return array_merge( $action_links, $links );
	}

	/**
	 * Render the main dashboard / broken links table page.
	 *
	 * @since    1.0.0
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'broken-link-auto-fixer' ) );
		}
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'admin/partials/broken-link-auto-fixer-admin-display.php';
	}

	/**
	 * Render the settings page.
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'broken-link-auto-fixer' ) );
		}
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'admin/partials/broken-link-auto-fixer-settings-display.php';
	}
}
