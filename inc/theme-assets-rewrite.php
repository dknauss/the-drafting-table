<?php
/**
 * Rewrites theme:./ URLs in block content.
 *
 * Allows theme asset relative paths in templates, e.g. theme:./assets/image.png.
 * Front-end rewriting is handled via a render_block filter; editor rewriting is
 * handled by a small JS higher-order component loaded only in the block editor.
 *
 * Named functions are used instead of anonymous closures so that child themes
 * and plugins can unhook these filters if needed.
 *
 * @package The_Drafting_Table
 */

if ( ! function_exists( 'the_drafting_table_rewrite_block_urls' ) ) {
	/**
	 * Rewrites theme:./ URLs in rendered block HTML on the front end.
	 *
	 * @param string $content The rendered block HTML.
	 * @return string Block HTML with theme:./ URLs replaced by absolute URIs.
	 */
	function the_drafting_table_rewrite_block_urls( $content ) {
		if ( ! $content || false === strpos( $content, 'theme:./' ) ) {
			return $content;
		}
		$base    = get_stylesheet_directory_uri();
		$content = preg_replace( '/(src|href)=(["\']?)theme:\.\//', '$1=$2' . $base . '/', $content );
		$content = preg_replace( '/url\((["\']?)theme:\.\//', 'url($1' . $base . '/', $content );
		return $content;
	}
}
add_filter( 'render_block', 'the_drafting_table_rewrite_block_urls' );

if ( ! function_exists( 'the_drafting_table_enqueue_editor_rewrite_script' ) ) {
	/**
	 * Enqueues the JS higher-order component that rewrites theme:./ URLs in the editor.
	 */
	function the_drafting_table_enqueue_editor_rewrite_script() {
		wp_enqueue_script(
			'theme-assets-editor-rewrite',
			get_stylesheet_directory_uri() . '/inc/theme-assets-editor-rewrite.js',
			array( 'wp-hooks', 'wp-compose', 'wp-element' ),
			'1.0.0',
			true
		);
		wp_add_inline_script(
			'theme-assets-editor-rewrite',
			'window.THEME_ASSETS_BASE_URL=' . wp_json_encode( get_stylesheet_directory_uri() ) . ';',
			'before'
		);
	}
}
add_action( 'enqueue_block_editor_assets', 'the_drafting_table_enqueue_editor_rewrite_script' );
