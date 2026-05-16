<?php
/**
 * Block registration and frontend render helpers.
 *
 * @package AnimatedBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles block registration and frontend animation props.
 */
class Animated_Blocks_Block {

	/**
	 * Registers the block from metadata.
	 *
	 * @return void
	 */
	public static function register() {
		register_block_type( Animated_Blocks::path() . 'build' );
	}

	/**
	 * Adds animation attributes to server-registered blocks.
	 *
	 * @param array  $args Block registration arguments.
	 * @param string $block_type Block type name.
	 * @return array
	 */
	public static function add_animation_attributes( $args, $block_type ) {
		if ( ! self::is_extendable_block( $block_type ) ) {
			return $args;
		}

		if ( empty( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
			$args['attributes'] = array();
		}

		$args['attributes'] = array_merge(
			self::get_animation_attribute_definitions(),
			$args['attributes']
		);

		return $args;
	}

	/**
	 * Determines whether a block can receive animation controls.
	 *
	 * @param string $block_type Block type name.
	 * @return bool
	 */
	private static function is_extendable_block( $block_type ) {
		return 'ab/animate' !== $block_type && 'core/block' !== $block_type;
	}

	/**
	 * Gets the animation attribute schema shared by extended blocks.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_animation_attribute_definitions() {
		return array(
			'animation'   => array(
				'type' => 'string',
			),
			'customClass' => array(
				'type' => 'string',
			),
			'duration'    => array(
				'type' => 'string',
			),
			'delay'       => array(
				'type' => 'string',
			),
			'threshold'   => array(
				'type' => 'number',
			),
			'offsetTop'   => array(
				'type' => 'string',
			),
			'hideEl'      => array(
				'type'    => 'boolean',
				'default' => false,
			),
		);
	}

	/**
	 * Injects animation attributes into block markup and loads assets on demand.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block Parsed block data.
	 * @return string
	 */
	public static function maybe_animate_block( $block_content, $block ) {
		$attributes = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$props      = self::get_animation_props( $attributes );

		if ( empty( $props ) ) {
			return $block_content;
		}

		wp_enqueue_style( 'ab-animate' );
		wp_enqueue_style( 'ab-frontend' );

		if ( wp_script_is( 'ab-view', 'registered' ) ) {
			wp_enqueue_script( 'ab-view' );
		}

		if ( 'ab/animate' === ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		return self::inject_props_into_first_tag( $block_content, $props );
	}

	/**
	 * Builds frontend animation props from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return array<string, string>
	 */
	private static function get_animation_props( $attributes ) {
		$scroll_classes = self::get_scroll_animation_classes( $attributes );

		if ( empty( $scroll_classes ) ) {
			return array();
		}

		$props = array(
			'class'             => 'ab-has-animation',
			'data-scroll-class' => $scroll_classes,
		);

		if ( ! empty( $attributes['hideEl'] ) ) {
			$props['class'] .= ' ab-is-hidden';
		}

		$delay = self::get_numeric_attribute( $attributes, 'delay' );
		if ( null !== $delay ) {
			$props['data-scroll-delay'] = $delay;
		}

		$threshold = self::get_numeric_attribute( $attributes, 'threshold' );
		if ( null !== $threshold ) {
			$props['data-scroll-threshold'] = $threshold;
		}

		$offset_top = self::get_numeric_attribute( $attributes, 'offsetTop' );
		if ( null !== $offset_top ) {
			$props['data-scroll-offset-top'] = $offset_top;
		}

		$duration = self::get_numeric_attribute( $attributes, 'duration' );
		if ( null !== $duration ) {
			$props['style'] = 'animation-duration:' . ( (float) $duration / 1000 ) . 's';
		}

		return $props;
	}

	/**
	 * Builds the CSS classes applied to animated blocks.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private static function get_scroll_animation_classes( $attributes ) {
		$classes = array();

		if ( ! empty( $attributes['animation'] ) ) {
			$classes = array_merge(
				$classes,
				self::sanitize_class_list( $attributes['animation'] )
			);
		}

		if ( ! empty( $attributes['customClass'] ) ) {
			$classes = array_merge(
				$classes,
				self::normalize_custom_class_list( $attributes['customClass'] )
			);
		}

		return ! empty( $classes ) ? implode( ' ', $classes ) : '';
	}

	/**
	 * Gets a numeric block attribute as a string when valid.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $name Attribute name.
	 * @return string|null
	 */
	private static function get_numeric_attribute( $attributes, $name ) {
		if ( ! isset( $attributes[ $name ] ) || '' === $attributes[ $name ] || ! is_numeric( $attributes[ $name ] ) ) {
			return null;
		}

		return (string) $attributes[ $name ];
	}

	/**
	 * Sanitizes a whitespace-separated CSS class list.
	 *
	 * @param mixed $class_list Raw class list.
	 * @return array<int,string>
	 */
	private static function sanitize_class_list( $class_list ) {
		$classes = preg_split( '/\s+/', (string) $class_list );
		$classes = array_map( 'sanitize_html_class', $classes );
		$classes = array_filter( array_map( 'trim', $classes ) );

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Normalizes custom animation classes without stripping valid framework syntax.
	 *
	 * @param mixed $class_list Raw class list.
	 * @return array<int,string>
	 */
	private static function normalize_custom_class_list( $class_list ) {
		$classes = preg_split( '/\s+/', (string) $class_list );
		$classes = array_filter( array_map( 'trim', $classes ) );

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Injects attributes into the first HTML tag of a rendered block.
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string,string> $props Attributes to merge into the first tag.
	 * @return string
	 */
	private static function inject_props_into_first_tag( $block_content, $props ) {
		if ( '' === trim( $block_content ) ) {
			return $block_content;
		}

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new WP_HTML_Tag_Processor( $block_content );

			if ( ! $processor->next_tag() ) {
				return $block_content;
			}

			foreach ( $props as $name => $value ) {
				if ( 'class' === $name ) {
					foreach ( self::sanitize_class_list( $value ) as $class_name ) {
						$processor->add_class( $class_name );
					}

					continue;
				}

				if ( 'style' === $name ) {
					$existing = $processor->get_attribute( 'style' );
					$existing = is_string( $existing ) ? rtrim( trim( $existing ), ';' ) : '';
					$incoming = ltrim( trim( $value ), ';' );
					$value    = trim( $existing . ';' . $incoming, ';' );
				}

				$processor->set_attribute( $name, $value );
			}

			return $processor->get_updated_html();
		}

		return preg_replace_callback(
			'/<([a-zA-Z0-9:-]+)\b([^>]*)>/',
			function ( $matches ) use ( $props ) {
				$tag_name   = $matches[1];
				$attributes = $matches[2];

				foreach ( $props as $name => $value ) {
					$attributes = self::merge_attribute( $attributes, $name, $value );
				}

				return '<' . $tag_name . $attributes . '>';
			},
			$block_content,
			1
		);
	}

	/**
	 * Merges an attribute into an existing HTML attribute string.
	 *
	 * @param string $attributes Existing raw attribute string.
	 * @param string $name Attribute name.
	 * @param string $value Attribute value.
	 * @return string
	 */
	private static function merge_attribute( $attributes, $name, $value ) {
		$pattern = '/\s' . preg_quote( $name, '/' ) . '\s*=\s*([\'"])(.*?)\1/s';

		if ( preg_match( $pattern, $attributes ) ) {
			return preg_replace_callback(
				$pattern,
				function ( $match ) use ( $name, $value ) {
					$quote        = $match[1];
					$merged_value = $value;

					if ( 'class' === $name ) {
						$merged_value = trim( $match[2] . ' ' . $value );
					} elseif ( 'style' === $name ) {
						$existing     = rtrim( trim( $match[2] ), ';' );
						$incoming     = ltrim( trim( $value ), ';' );
						$merged_value = trim( $existing . ';' . $incoming, ';' );
					}

					return ' ' . $name . '=' . $quote . esc_attr( $merged_value ) . $quote;
				},
				$attributes,
				1
			);
		}

		return $attributes . ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
}
