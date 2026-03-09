<?php
/**
 * The core plugin class.
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
 * The core plugin class.
 *
 * Maintains the plugin name, version, and orchestrates all hooks via
 * the Loader. Loads all dependencies and wires admin + AJAX hooks.
 *
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/includes
 * @author     Bikas Kumar <bikas@codesala.in>
 */
class Broken_Link_Auto_Fixer {

	/**
	 * The loader that maintains and registers all hooks.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Broken_Link_Auto_Fixer_Loader $loader
	 */
	protected $loader;

	/**
	 * The unique plugin identifier.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name
	 */
	protected $plugin_name;

	/**
	 * Current plugin version.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version
	 */
	protected $version;

	/**
	 * Define core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = defined( 'BROKEN_LINK_AUTO_FIXER_VERSION' ) ? BROKEN_LINK_AUTO_FIXER_VERSION : '1.0.0';
		$this->plugin_name = 'broken-link-auto-fixer';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_ajax_hooks();
		$this->define_cron_hooks();
	}

	/**
	 * Load all required dependencies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-loader.php';
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-i18n.php';
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-database.php';
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-scanner.php';
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'includes/class-broken-link-auto-fixer-ajax-handler.php';
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'admin/class-broken-link-auto-fixer-admin.php';
		require_once BROKEN_LINK_AUTO_FIXER_PATH . 'public/class-broken-link-auto-fixer-public.php';

		$this->loader = new Broken_Link_Auto_Fixer_Loader();
	}

	/**
	 * Define the locale for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Broken_Link_Auto_Fixer_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all admin-area hooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Broken_Link_Auto_Fixer_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu',            $plugin_admin, 'add_admin_menu' );
		$this->loader->add_filter( 'plugin_action_links_' . BROKEN_LINK_AUTO_FIXER_BASENAME, $plugin_admin, 'add_plugin_action_links' );
	}

	/**
	 * Register all AJAX hooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_ajax_hooks() {
		$ajax_handler = new Broken_Link_Auto_Fixer_Ajax_Handler();
		$ajax_handler->register_hooks();
	}

	/**
	 * Register the scheduled cron scan hook.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_cron_hooks() {
		add_action( 'blaf_scheduled_scan', array( $this, 'run_scheduled_scan' ) );
	}

	/**
	 * Execute the full scan when triggered by WP-Cron.
	 *
	 * Runs through all posts in batches until done.
	 *
	 * @since    1.0.0
	 */
	public function run_scheduled_scan() {
		if ( ! get_option( 'blaf_auto_scan_enabled', 0 ) ) {
			return;
		}

		$scanner = new Broken_Link_Auto_Fixer_Scanner();
		$offset  = 0;

		do {
			$result  = $scanner->run_scan( $offset );
			$offset += 10;
		} while ( ! $result['done'] );
	}

	/**
	 * Run the loader to execute all registered hooks.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Get the plugin name.
	 *
	 * @since     1.0.0
	 * @return    string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get the loader instance.
	 *
	 * @since     1.0.0
	 * @return    Broken_Link_Auto_Fixer_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Get the plugin version.
	 *
	 * @since     1.0.0
	 * @return    string
	 */
	public function get_version() {
		return $this->version;
	}
}
