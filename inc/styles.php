<?php
/**
 * Theme styles and editor styles setup.
 * Enqueues the main stylesheet on the front-end and registers it as an editor style.
 *
 * @package The_Drafting_Table
 */

if ( ! function_exists( 'the_drafting_table_editor_styles' ) ) {
	/**
	 * Registers the editor stylesheet and enables editor-styles support.
	 */
	function the_drafting_table_editor_styles() {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'style.css' );
	}
}
add_action( 'after_setup_theme', 'the_drafting_table_editor_styles' );

if ( ! function_exists( 'the_drafting_table_enqueue_styles' ) ) {
	/**
	 * Enqueues the theme stylesheet on the front-end.
	 */
	function the_drafting_table_enqueue_styles() {
		wp_enqueue_style(
			'the-drafting-table-style',
			get_stylesheet_uri(),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'the_drafting_table_enqueue_styles' );
