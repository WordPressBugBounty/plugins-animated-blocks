<?php

/**
 * Plugin Name: Animated Blocks on Scroll
 * Plugin URI: https://wordpress.org/plugins/animated-blocks/
 * Description: Add scroll based animations to Gutenberg blocks.
 * Author: Virgiliu Diaconu
 * Author URI: http://virgiliudiaconu.com/
 * Requires at least: 5.9
 * Requires PHP: 7.0
 * Version: 1.1.6
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: animated-blocks
 *
 * @package AnimatedBlocks
 */

if (! defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/inc/class-animated-blocks-settings.php';
require_once __DIR__ . '/inc/class-animated-blocks-assets.php';
require_once __DIR__ . '/inc/class-animated-blocks-block.php';

/**
 * Main plugin bootstrap.
 */
class Animated_Blocks
{

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.6';

	/**
	 * Gets the plugin file path.
	 *
	 * @return string
	 */
	public static function file()
	{
		return __FILE__;
	}

	/**
	 * Gets the plugin directory path.
	 *
	 * @return string
	 */
	public static function path()
	{
		return plugin_dir_path(self::file());
	}

	/**
	 * Gets the plugin directory URL.
	 *
	 * @return string
	 */
	public static function url()
	{
		return plugin_dir_url(self::file());
	}

	/**
	 * Registers plugin hooks.
	 *
	 * @return void
	 */
	public static function register()
	{
		add_action('init', array('Animated_Blocks_Assets', 'register'));
		add_action('init', array('Animated_Blocks_Block', 'register'));
		add_action('admin_init', array('Animated_Blocks_Settings', 'register'));
		add_action('admin_menu', array('Animated_Blocks_Settings', 'register_page'));
		add_action('enqueue_block_assets', array('Animated_Blocks_Assets', 'enqueue_editor_block_assets'));
		add_action('enqueue_block_editor_assets', array('Animated_Blocks_Assets', 'enqueue_editor_settings'));
		add_filter('register_block_type_args', array('Animated_Blocks_Block', 'add_animation_attributes'), 10, 2);
		add_filter('render_block', array('Animated_Blocks_Block', 'maybe_animate_block'), 10, 2);
	}
}

Animated_Blocks::register();
