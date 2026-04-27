<?php
/**
 * Plugin settings.
 *
 * @package AnimatedBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin settings and settings page output.
 */
class Animated_Blocks_Settings {

	/**
	 * Option name for enabling animation controls on all blocks.
	 *
	 * @var string
	 */
	const ALL_BLOCKS_OPTION = 'ab_enable_all_blocks';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const SETTINGS_SLUG = 'animated-blocks';

	/**
	 * Registers plugin settings.
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			'animated_blocks_settings',
			self::ALL_BLOCKS_OPTION,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( __CLASS__, 'sanitize_boolean_setting' ),
				'default'           => true,
			)
		);

		add_settings_section(
			'animated_blocks_general',
			__( 'General', 'animated-blocks' ),
			'__return_false',
			self::SETTINGS_SLUG
		);

		add_settings_field(
			self::ALL_BLOCKS_OPTION,
			__( 'Animation Controls', 'animated-blocks' ),
			array( __CLASS__, 'render_all_blocks_field' ),
			self::SETTINGS_SLUG,
			'animated_blocks_general'
		);
	}

	/**
	 * Registers the plugin settings page.
	 *
	 * @return void
	 */
	public static function register_page() {
		add_options_page(
			__( 'Animated Blocks', 'animated-blocks' ),
			__( 'Animated Blocks', 'animated-blocks' ),
			'manage_options',
			self::SETTINGS_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<style>
				.ab-settings-description {
					max-width: 640px;
				}
			</style>
			<h1><?php echo esc_html__( 'Animated Blocks', 'animated-blocks' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'animated_blocks_settings' );
				do_settings_sections( self::SETTINGS_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the all blocks toggle field.
	 *
	 * @return void
	 */
	public static function render_all_blocks_field() {
		$enabled = self::is_all_blocks_enabled();
		?>
		<label for="<?php echo esc_attr( self::ALL_BLOCKS_OPTION ); ?>">
			<input
				id="<?php echo esc_attr( self::ALL_BLOCKS_OPTION ); ?>"
				name="<?php echo esc_attr( self::ALL_BLOCKS_OPTION ); ?>"
				type="checkbox"
				value="1"
				<?php checked( $enabled ); ?>
			/>
			<?php echo esc_html__( 'Show animation controls on all blocks', 'animated-blocks' ); ?>
		</label>
		<p class="description ab-settings-description">
			<?php echo esc_html__( 'When disabled, only blocks that already have saved animation settings will continue showing these controls until those settings are cleared.', 'animated-blocks' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitizes a checkbox-backed boolean option.
	 *
	 * @param mixed $value Raw option value.
	 * @return bool
	 */
	public static function sanitize_boolean_setting( $value ) {
		return ! empty( $value );
	}

	/**
	 * Determines whether all-block animation controls are enabled.
	 *
	 * @return bool
	 */
	public static function is_all_blocks_enabled() {
		$option = get_option( self::ALL_BLOCKS_OPTION, null );

		if ( null === $option ) {
			return true;
		}

		return rest_sanitize_boolean( $option );
	}
}
