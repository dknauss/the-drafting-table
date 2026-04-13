<?php
/**
 * Plugin Name: The Drafting Table Smoke Routes
 * Description: Test-only routes for rendering block-template previews in wp-env.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'the_drafting_table_smoke_register_preview_var' ) ) {
	/**
	 * Registers the preview query var for smoke-test routes.
	 *
	 * @param string[] $query_vars Registered query vars.
	 * @return string[]
	 */
	function the_drafting_table_smoke_register_preview_var( $query_vars ) {
		$query_vars[] = 'the_drafting_table_preview_template';
		$query_vars[] = 'the_drafting_table_smoke_post';

		return $query_vars;
	}
}
add_filter( 'query_vars', 'the_drafting_table_smoke_register_preview_var' );

if ( ! function_exists( 'the_drafting_table_smoke_map_featured_post_query' ) ) {
	/**
	 * Maps smoke query vars to deterministic demo post queries.
	 *
	 * @param array<string, mixed> $query_vars Main query vars.
	 * @return array<string, mixed>
	 */
	function the_drafting_table_smoke_map_featured_post_query( $query_vars ) {
		if ( empty( $query_vars['the_drafting_table_smoke_post'] ) ) {
			return $query_vars;
		}

		$smoke_post = sanitize_key( (string) $query_vars['the_drafting_table_smoke_post'] );
		if ( 'featured' !== $smoke_post ) {
			return $query_vars;
		}

		$featured_post_id = function_exists( 'the_drafting_table_get_demo_featured_post_id' )
			? (int) the_drafting_table_get_demo_featured_post_id()
			: 0;

		if ( $featured_post_id <= 0 ) {
			return $query_vars;
		}

		unset( $query_vars['name'], $query_vars['post_type'] );
		$query_vars['p'] = $featured_post_id;

		return $query_vars;
	}
}
add_filter( 'request', 'the_drafting_table_smoke_map_featured_post_query' );

if ( ! function_exists( 'the_drafting_table_smoke_add_rewrite_rules' ) ) {
	/**
	 * Adds a pretty permalink for the template preview route.
	 *
	 * @return void
	 */
	function the_drafting_table_smoke_add_rewrite_rules() {
		add_rewrite_rule(
			'^__drafting-table/index-preview/?$',
			'index.php?the_drafting_table_preview_template=index',
			'top'
		);
		add_rewrite_rule(
			'^__drafting-table/home-preview/?$',
			'index.php?the_drafting_table_preview_template=home',
			'top'
		);
	}
}
add_action( 'init', 'the_drafting_table_smoke_add_rewrite_rules' );

if ( ! function_exists( 'the_drafting_table_smoke_render_template_preview' ) ) {
	/**
	 * Renders the requested block template file in a minimal document shell.
	 *
	 * @return void
	 */
	function the_drafting_table_smoke_render_template_preview() {
		$template_slug = sanitize_key( (string) get_query_var( 'the_drafting_table_preview_template' ) );

		if ( '' === $template_slug ) {
			return;
		}

		$template_path = get_theme_file_path( 'templates/' . $template_slug . '.html' );
		if ( ! file_exists( $template_path ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Test-only preview route reads a theme template file directly.
		$template_markup = file_get_contents( $template_path );
		if ( false === $template_markup ) {
			status_header( 500 );
			exit;
		}

		status_header( 200 );
		nocache_headers();

		$body_classes = implode(
			' ',
			array_map(
				'sanitize_html_class',
				get_body_class( array( 'the-drafting-table-template-preview' ) )
			)
		);

		echo '<!doctype html><html ' . get_language_attributes() . '><head>';
		echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
		wp_head();
		echo '</head><body class="' . esc_attr( $body_classes ) . '">';
		echo do_blocks( $template_markup );
		wp_footer();
		echo '</body></html>';
		exit;
	}
}
add_action( 'template_redirect', 'the_drafting_table_smoke_render_template_preview', 0 );

if ( ! function_exists( 'the_drafting_table_smoke_disable_canonical_redirects' ) ) {
	/**
	 * Keeps query-based smoke routes on their original URLs.
	 *
	 * CI environments can fail Apache-level pretty-permalink rewrites even when
	 * WordPress query routes resolve correctly. Returning false here keeps smoke
	 * requests on query URLs like `/?p=123` and `/?cat=9`.
	 *
	 * @param string|false|null $redirect_url Candidate canonical URL.
	 * @return string|false|null
	 */
	function the_drafting_table_smoke_disable_canonical_redirects( $redirect_url ) {
		if ( ! isset( $_GET ) || ! is_array( $_GET ) ) {
			return $redirect_url;
		}

		$query_routes = array(
			'p',
			'pagename',
			'page_id',
			'the_drafting_table_smoke_post',
			'name',
			'post_type',
			'cat',
			'category_name',
			'the_drafting_table_preview_template',
		);

		foreach ( $query_routes as $query_key ) {
			if ( array_key_exists( $query_key, $_GET ) ) {
				return false;
			}
		}

		return $redirect_url;
	}
}
add_filter( 'redirect_canonical', 'the_drafting_table_smoke_disable_canonical_redirects' );

if ( ! function_exists( 'the_drafting_table_smoke_register_rest_routes' ) ) {
	/**
	 * Registers smoke-only REST endpoints for deterministic assertions.
	 *
	 * @return void
	 */
	function the_drafting_table_smoke_register_rest_routes() {
		register_rest_route(
			'the-drafting-table-smoke/v1',
			'/demo-state',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => function () {
					$featured_query = new WP_Query(
						array(
							'post_type'      => 'post',
							'post_status'    => 'publish',
							'posts_per_page' => 1,
							'fields'         => 'ids',
							'meta_key'       => '_the_drafting_table_featured_entry',
							'meta_value'     => '1',
							'no_found_rows'  => true,
						)
					);

					$demo_content_query = new WP_Query(
						array(
							'post_type'      => array( 'post', 'page' ),
							'post_status'    => 'any',
							'posts_per_page' => -1,
							'fields'         => 'ids',
							'meta_key'       => '_the_drafting_table_demo_content',
							'meta_value'     => '1',
							'no_found_rows'  => true,
						)
					);

					$demo_featured_query = new WP_Query(
						array(
							'post_type'      => 'post',
							'post_status'    => 'publish',
							'posts_per_page' => 1,
							'fields'         => 'ids',
							'meta_query'     => array(
								'relation' => 'AND',
								array(
									'key'   => '_the_drafting_table_featured_entry',
									'value' => '1',
								),
								array(
									'key'   => '_the_drafting_table_demo_content',
									'value' => '1',
								),
							),
							'no_found_rows'  => true,
						)
					);

					$featured_post_id = ! empty( $featured_query->posts ) ? (int) $featured_query->posts[0] : 0;
					$demo_featured_post_id = ! empty( $demo_featured_query->posts ) ? (int) $demo_featured_query->posts[0] : 0;
					$demo_content_ids = array_map( 'intval', (array) $demo_content_query->posts );

					return rest_ensure_response(
						array(
							'featured_post_id' => $featured_post_id,
							'demo_featured_post_id' => $demo_featured_post_id,
							'demo_content_ids' => $demo_content_ids,
							'demo_content_count' => count( $demo_content_ids ),
						)
					);
				},
			)
		);
	}
}
add_action( 'rest_api_init', 'the_drafting_table_smoke_register_rest_routes' );
