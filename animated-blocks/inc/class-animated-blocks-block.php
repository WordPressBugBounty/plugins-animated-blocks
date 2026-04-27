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

		if ( isset( $attributes['delay'] ) && '' !== $attributes['delay'] ) {
			$props['data-scroll-delay'] = (string) $attributes['delay'];
		}

		if ( isset( $attributes['threshold'] ) && '' !== $attributes['threshold'] ) {
			$props['data-scroll-threshold'] = (string) $attributes['threshold'];
		}

		if ( isset( $attributes['offsetTop'] ) && '' !== $attributes['offsetTop'] ) {
			$props['data-scroll-offset-top'] = (string) $attributes['offsetTop'];
		}

		if ( isset( $attributes['duration'] ) && '' !== $attributes['duration'] ) {
			$props['style'] = 'animation-duration:' . ( (float) $attributes['duration'] / 1000 ) . 's';
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
			$classes[] = $attributes['animation'];
		}

		if ( ! empty( $attributes['customClass'] ) ) {
			$classes[] = $attributes['customClass'];
		}

		$classes = array_filter( array_map( 'trim', $classes ) );

		return ! empty( $classes ) ? implode( ' ', $classes ) : '';
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
		$pattern = '/\s' . preg_quote( $name, '/' ) . '="([^"]*)"/';

		if ( preg_match( $pattern, $attributes, $match ) ) {
			$merged_value = $value;

			if ( 'class' === $name ) {
				$merged_value = trim( $match[1] . ' ' . $value );
			} elseif ( 'style' === $name ) {
				$existing     = rtrim( trim( $match[1] ), ';' );
				$incoming     = ltrim( trim( $value ), ';' );
				$merged_value = trim( $existing . ';' . $incoming, ';' );
			}

			return preg_replace(
				$pattern,
				' ' . $name . '="' . esc_attr( $merged_value ) . '"',
				$attributes,
				1
			);
		}

		return $attributes . ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
}
