<?php
/**
 * Asset registration and editor-only enqueueing.
 *
 * @package AnimatedBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin assets.
 */
class Animated_Blocks_Assets {

	/**
	 * Registers plugin styles and scripts.
	 *
	 * @return void
	 */
	public static function register() {
		$view_script_path = Animated_Blocks::path() . 'build/view.js';
		$view_asset_path  = Animated_Blocks::path() . 'build/view.asset.php';
		$view_asset       = file_exists( $view_asset_path )
			? require $view_asset_path
			: array(
					'dependencies' => array(),
					'version'      => Animated_Blocks::VERSION,
				);

		wp_register_style(
			'ab-animate',
			Animated_Blocks::url() . 'assets/css/animate.min.css',
			array(),
			Animated_Blocks::VERSION
		);

		wp_register_style(
			'ab-frontend',
			Animated_Blocks::url() . 'build/style-index.css',
			array(),
			Animated_Blocks::VERSION
		);

		if ( file_exists( $view_script_path ) ) {
			wp_register_script(
				'ab-view',
				Animated_Blocks::url() . 'build/view.js',
				$view_asset['dependencies'],
				$view_asset['version'],
				true
			);
		}
	}

	/**
	 * Enqueues animation styles inside the editor content canvas.
	 *
	 * @return void
	 */
	public static function enqueue_editor_block_assets() {
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style( 'ab-animate' );
		wp_enqueue_style( 'ab-frontend' );
	}

	/**
	 * Passes plugin settings into the block editor.
	 *
	 * @return void
	 */
	public static function enqueue_editor_settings() {
		wp_add_inline_script(
			'wp-blocks',
			'window.abAnimatedBlocksSettings = ' . wp_json_encode(
				array(
					'enableAllBlocks' => Animated_Blocks_Settings::is_all_blocks_enabled(),
				)
			) . ';',
			'before'
		);
	}
}
