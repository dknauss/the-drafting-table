<?php
/**
 * Theme functions and definitions.
 *
 * @package The_Drafting_Table
 */

require_once __DIR__ . '/inc/styles.php';
require_once __DIR__ . '/inc/theme-assets-rewrite.php';

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

if ( ! function_exists( 'the_drafting_table_register_block_styles' ) ) {
	/**
	 * Registers custom block styles.
	 *
	 * Blueprint Frame: applies the double-border blueprint treatment to images.
	 * Vellum Overlay: applies the sepia vellum overlay treatment to images.
	 */
	function the_drafting_table_register_block_styles() {
		register_block_style(
			'core/image',
			array(
				'name'  => 'blueprint-frame',
				'label' => esc_html__( 'Blueprint Frame', 'the-drafting-table' ),
			)
		);
		register_block_style(
			'core/image',
			array(
				'name'  => 'vellum-overlay',
				'label' => esc_html__( 'Vellum Overlay', 'the-drafting-table' ),
			)
		);
		register_block_style(
			'core/quote',
			array(
				'name'  => 'parchment',
				'label' => esc_html__( 'Parchment', 'the-drafting-table' ),
			)
		);
	}
}
add_action( 'init', 'the_drafting_table_register_block_styles' );

if ( ! function_exists( 'the_drafting_table_get_featured_entry_post_id' ) ) {
	/**
	 * Returns the configured featured post ID for front-page hero treatment.
	 *
	 * @return int
	 */
	function the_drafting_table_get_featured_entry_post_id() {
		$featured_ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_key'       => '_the_drafting_table_featured_entry',
				'meta_value'     => '1',
			)
		);

		return empty( $featured_ids ) ? 0 : (int) $featured_ids[0];
	}
}

if ( ! function_exists( 'the_drafting_table_query_has_marker' ) ) {
	/**
	 * Checks query block attrs for a stable marker namespace or class token.
	 *
	 * @param WP_Block $block             Query block instance.
	 * @param string   $namespace_marker  Expected namespace marker.
	 * @param string   $class_marker      Expected class token.
	 * @return bool
	 */
	function the_drafting_table_query_has_marker( $block, $namespace_marker, $class_marker ) {
		$attrs        = ! empty( $block->parsed_block['attrs'] ) ? (array) $block->parsed_block['attrs'] : array();
		$attr_ns      = ! empty( $attrs['namespace'] ) ? (string) $attrs['namespace'] : '';
		$attr_classes = ! empty( $attrs['className'] ) ? (string) $attrs['className'] : '';

		if ( $namespace_marker && $namespace_marker === $attr_ns ) {
			return true;
		}

		if ( $class_marker && false !== strpos( $attr_classes, $class_marker ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'the_drafting_table_filter_query_loop_vars' ) ) {
	/**
	 * Applies deterministic query-loop behavior for marked front-page loops.
	 *
	 * @param array<string, mixed> $query Parsed query vars from Query Loop.
	 * @param WP_Block             $block Block instance for the loop.
	 * @return array<string, mixed>
	 */
	function the_drafting_table_filter_query_loop_vars( $query, $block ) {
		$featured_post_id = the_drafting_table_get_featured_entry_post_id();

		$is_hero_query       = the_drafting_table_query_has_marker(
			$block,
			'the-drafting-table/front-hero',
			'is-the-drafting-table-front-hero'
		);
		$is_front_rail_query = the_drafting_table_query_has_marker(
			$block,
			'the-drafting-table/front-rail',
			'is-the-drafting-table-front-rail'
		);

		if ( $is_hero_query && is_front_page() ) {
			$query['post_type']           = 'post';
			$query['posts_per_page']      = 1;
			$query['ignore_sticky_posts'] = 1;

			if ( $featured_post_id ) {
				$query['post__in'] = array( $featured_post_id );
				$query['orderby']  = 'post__in';
			} else {
				$query['orderby'] = 'date';
				$query['order']   = 'DESC';
			}
		}

		// Front-page journal rail: avoid duplicating the hero post.
		if ( $is_front_rail_query && is_front_page() && $featured_post_id ) {
			$excluded_ids          = array_map( 'absint', (array) ( $query['post__not_in'] ?? array() ) );
			$excluded_ids[]        = $featured_post_id;
			$query['post__not_in'] = array_values( array_unique( $excluded_ids ) );
		}

		return $query;
	}
}
add_filter( 'query_loop_block_query_vars', 'the_drafting_table_filter_query_loop_vars', 10, 2 );

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

if ( ! function_exists( 'the_drafting_table_featured_image_caption' ) ) {
	/**
	 * Injects the attachment caption as a semantic <figcaption> inside the
	 * featured image <figure> on single posts.
	 *
	 * Proper <figure>/<figcaption> nesting satisfies HTML5 semantics, search
	 * engines, and screen readers (the caption is automatically associated with
	 * the image by the browser). A str_replace on the closing tag is used
	 * instead of a regex so there is nothing fragile to maintain over time.
	 *
	 * FSE context notes:
	 * - In FSE template rendering $GLOBALS['post'] holds the wp_template object,
	 *   not the article, so get_the_ID() reliably returns the wrong value.
	 *   The third render_block filter arg ($wp_block, added in WP 5.9) exposes
	 *   the block's rendering context, which carries the correct postId.
	 * - is_singular() gates output to singular post views only, so archive
	 *   query loops are skipped without relying on brittle query IDs.
	 *
	 * The function bails early if a native caption is already present so that
	 * future WordPress showCaption support on core/post-featured-image will not
	 * produce duplicate captions.
	 *
	 * Caption text is set per-image in Media > Library > [image] > Caption.
	 * The block editor preview of this caption is provided by the JS HOC in
	 * assets/js/editor.js, loaded via the_drafting_table_enqueue_editor_assets().
	 *
	 * @param string        $block_content Rendered block HTML.
	 * @param array         $block         Parsed block data (name, attrs, etc.).
	 * @param WP_Block|null $wp_block      Block instance with rendering context (WP 5.9+).
	 * @return string Modified block HTML with <figcaption> injected.
	 */
	function the_drafting_table_featured_image_caption( $block_content, $block, $wp_block = null ) {
		if ( 'core/post-featured-image' !== $block['blockName'] ) {
			return $block_content;
		}

		// Only inject on single post pages. is_singular() checks the main
		// WP_Query and is reliable in FSE; it returns false on archive,
		// index, and search pages so loop cards are never affected.
		if ( ! is_singular( 'post' ) ) {
			return $block_content;
		}

		// Prefer the block's postId context over the global post (FSE-safe).
		$post_id = ( $wp_block instanceof WP_Block && ! empty( $wp_block->context['postId'] ) )
			? (int) $wp_block->context['postId']
			: (int) get_the_ID();

		if ( ! $post_id ) {
			return $block_content;
		}

		// Only inject on standard posts, not pages or custom post types.
		$post_type = ( $wp_block instanceof WP_Block && ! empty( $wp_block->context['postType'] ) )
			? $wp_block->context['postType']
			: get_post_type( $post_id );

		if ( 'post' !== $post_type ) {
			return $block_content;
		}

		// Bail if WordPress has already rendered a native caption (forward-compat).
		if ( false !== strpos( $block_content, 'wp-element-caption' ) ) {
			return $block_content;
		}

		$thumb_id = get_post_thumbnail_id( $post_id );

		if ( ! $thumb_id ) {
			return $block_content;
		}

		$caption = wp_get_attachment_caption( $thumb_id );

		if ( ! $caption || ! trim( wp_strip_all_tags( $caption ) ) ) {
			return $block_content;
		}

		/*
		 * Build a "Fig. 1" label span. The featured image is always the first
		 * figure on the single-post page, so the number is always 1.
		 * Using a real DOM element (not a CSS ::before) keeps the label
		 * readable by screen readers and translatable via the WP i18n API.
		 */
		$fig_label = sprintf(
			'<span class="featured-image-fig-label">%s</span>',
			/* translators: Figure number label prepended to the featured image caption. %d is the figure number. */
			esc_html( sprintf( __( 'Fig. %d', 'the-drafting-table' ), 1 ) )
		);

		/*
		 * Insert the figcaption immediately before the closing </figure> tag.
		 * The block outputs exactly one <figure>, so str_replace is safe here.
		 * wp_kses_post() allows links and inline formatting that attribution
		 * captions commonly contain.
		 */
		return str_replace(
			'</figure>',
			'<figcaption class="wp-element-caption">'
				. $fig_label
				. wp_kses_post( $caption )
				. '</figcaption></figure>',
			$block_content
		);
	}
}
add_filter( 'render_block', 'the_drafting_table_featured_image_caption', 10, 3 );

/**
 * Inject an incipit span into the opening clause of the first post paragraph.
 *
 * Wraps text before (and including) the first sentence-level punctuation mark
 * in <span class="post-incipit"> so the opening phrase can be styled in
 * Josefin Sans uppercase via CSS — replicating the classical broadsheet /
 * manuscript incipit treatment without relying on ::first-line (which is
 * bounded by rendered line width, not syntax).
 *
 * The regex matches:
 *   Group 1 — opening <p> tag with any attributes
 *   Group 2 — text content before the first punctuation (no HTML tags)
 *   Group 3 — the punctuation character itself (, . ; : ! ?)
 *
 * preg_replace() limit of 1 affects only the first <p> in the content.
 * is_singular('post') guard prevents the filter from running on archives.
 *
 * @param string $content Post content HTML.
 * @return string Modified content HTML.
 */
function the_drafting_table_post_incipit( $content ) {
	if ( ! is_singular( 'post' ) ) {
		return $content;
	}

	$content = preg_replace(
		'/(<p(?:\s[^>]*)?>)([^<,\.;:!?]*)([,\.;:!?])/u',
		'$1<span class="post-incipit">$2$3</span>',
		$content,
		1
	);

	return $content;
}
add_filter( 'the_content', 'the_drafting_table_post_incipit', 20 );
