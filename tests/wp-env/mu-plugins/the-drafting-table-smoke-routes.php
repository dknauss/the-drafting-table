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

		return $query_vars;
	}
}
add_filter( 'query_vars', 'the_drafting_table_smoke_register_preview_var' );

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
