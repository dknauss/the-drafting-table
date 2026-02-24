<?php
/**
 * Theme functions and definitions.
 *
 * @package The_Drafting_Table
 */

require_once get_template_directory() . '/inc/styles.php';
require_once get_template_directory() . '/inc/theme-assets-rewrite.php';
require_once get_template_directory() . '/inc/create-pages.php';

if ( ! function_exists( 'the_drafting_table_setup' ) ) {
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 */
	function the_drafting_table_setup() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
				'navigation-widgets',
			)
		);
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 80,
				'width'       => 280,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
	}
}
add_action( 'after_setup_theme', 'the_drafting_table_setup' );

if ( ! function_exists( 'the_drafting_table_register_patterns' ) ) {
	/**
	 * Registers the theme pattern category.
	 */
	function the_drafting_table_register_patterns() {
		register_block_pattern_category(
			'the-drafting-table',
			array( 'label' => esc_html__( 'The Drafting Table', 'the-drafting-table' ) )
		);
	}
}
add_action( 'init', 'the_drafting_table_register_patterns' );

if ( ! function_exists( 'the_drafting_table_hide_empty_meta' ) ) {
	/**
	 * Hides empty meta pair containers when dynamic block content is empty.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string Modified block content, or empty string if no dynamic content.
	 */
	function the_drafting_table_hide_empty_meta( $block_content, $block ) {
		if ( empty( $block['attrs']['className'] ) ) {
			return $block_content;
		}

		$class = $block['attrs']['className'];

		if ( false === strpos( $class, 'meta-pair' ) ) {
			return $block_content;
		}

		$temp         = preg_replace( '/<p[^>]*>.*?<\/p>/s', '', $block_content, 1 );
		$text_content = trim( wp_strip_all_tags( $temp ) );

		if ( empty( $text_content ) ) {
			return '';
		}

		return $block_content;
	}
}
add_filter( 'render_block_core/group', 'the_drafting_table_hide_empty_meta', 10, 2 );

if ( ! function_exists( 'the_drafting_table_seo_meta' ) ) {
	/**
	 * Outputs SEO meta tags in the document head.
	 */
	function the_drafting_table_seo_meta() {
		// Defer to dedicated SEO plugins to avoid duplicate meta descriptions.
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANKMATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || class_exists( 'All_in_One_SEO_Pack' ) ) {
			return;
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post && ! empty( $post->post_excerpt ) ) {
				printf(
					'<meta name="description" content="%s" />' . "\n",
					esc_attr( wp_strip_all_tags( $post->post_excerpt ) )
				);
			} elseif ( $post && ! empty( $post->post_content ) ) {
				$description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
				printf(
					'<meta name="description" content="%s" />' . "\n",
					esc_attr( $description )
				);
			}
		} elseif ( is_home() || is_front_page() ) {
			$description = get_bloginfo( 'description', 'display' );
			if ( $description ) {
				printf(
					'<meta name="description" content="%s" />' . "\n",
					esc_attr( $description )
				);
			}
		} elseif ( is_archive() ) {
			$description = get_the_archive_description();
			if ( $description ) {
				printf(
					'<meta name="description" content="%s" />' . "\n",
					esc_attr( wp_strip_all_tags( $description ) )
				);
			}
		}
	}
}
add_action( 'wp_head', 'the_drafting_table_seo_meta', 1 );

if ( ! function_exists( 'the_drafting_table_structured_data' ) ) {
	/**
	 * Outputs JSON-LD structured data for better SEO and AI/GEO discoverability.
	 */
	function the_drafting_table_structured_data() {
		if ( is_front_page() || is_home() ) {
			$data = array(
				'@context' => 'https://schema.org',
				'@type'    => 'WebSite',
				'name'     => get_bloginfo( 'name' ),
				'url'      => home_url( '/' ),
			);

			$description = get_bloginfo( 'description', 'display' );
			if ( $description ) {
				$data['description'] = $description;
			}

			$data['potentialAction'] = array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			);

			printf(
				'<script type="application/ld+json">%s</script>' . "\n",
				wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			);
		} elseif ( is_singular( 'post' ) ) {
			$post = get_queried_object();

			$data = array(
				'@context'      => 'https://schema.org',
				'@type'         => 'Article',
				'headline'      => get_the_title( $post ),
				'url'           => get_permalink( $post ),
				'datePublished' => get_the_date( 'c', $post ),
				'dateModified'  => get_the_modified_date( 'c', $post ),
				'author'        => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				),
				'publisher'     => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
					'url'   => home_url( '/' ),
				),
			);

			if ( has_post_thumbnail( $post ) ) {
				$data['image'] = get_the_post_thumbnail_url( $post, 'full' );
			}

			if ( ! empty( $post->post_excerpt ) ) {
				$data['description'] = wp_strip_all_tags( $post->post_excerpt );
			}

			printf(
				'<script type="application/ld+json">%s</script>' . "\n",
				wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			);
		}
	}
}
add_action( 'wp_head', 'the_drafting_table_structured_data', 2 );

