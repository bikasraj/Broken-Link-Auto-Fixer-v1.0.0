<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://codesala.in
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/public
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The public-facing functionality.
 *
 * This plugin has no public-facing features.
 * This class is retained to follow the WordPress Plugin Boilerplate pattern.
 *
 * @since      1.0.0
 * @package    Broken_Link_Auto_Fixer
 * @subpackage Broken_Link_Auto_Fixer/public
 * @author     Bikas Kumar <bikas@codesala.in>
 */
class Broken_Link_Auto_Fixer_Public {

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/** No public-facing stylesheets needed. */
	public function enqueue_styles() {}

	/** No public-facing scripts needed. */
	public function enqueue_scripts() {}
}
