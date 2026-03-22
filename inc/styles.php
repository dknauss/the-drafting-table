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
		add_editor_style(
			array(
				'style.css',
				'editor-style.css',
			)
		);
	}
}
add_action( 'after_setup_theme', 'the_drafting_table_editor_styles' );

if ( ! function_exists( 'the_drafting_table_enqueue_styles' ) ) {
	/**
	 * Enqueues the front-end theme assets.
	 */
	function the_drafting_table_enqueue_styles() {
		$theme_version      = wp_get_theme()->get( 'Version' );
		$stylesheet_path    = get_stylesheet_directory() . '/style.css';
		$stylesheet_version = file_exists( $stylesheet_path ) ? (string) filemtime( $stylesheet_path ) : $theme_version;
		$submenu_script     = 'assets/js/navigation-submenu-placement.js';
		$submenu_path       = get_theme_file_path( $submenu_script );
		$submenu_version    = file_exists( $submenu_path ) ? (string) filemtime( $submenu_path ) : $theme_version;

		wp_enqueue_style(
			'the-drafting-table-style',
			get_stylesheet_uri(),
			array(),
			$stylesheet_version
		);

		wp_enqueue_script(
			'the-drafting-table-navigation-submenu-placement',
			get_theme_file_uri( $submenu_script ),
			array(),
			$submenu_version,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'the_drafting_table_enqueue_styles' );

if ( ! function_exists( 'the_drafting_table_preload_fonts' ) ) {
	/**
	 * Outputs <link rel="preload"> hints for the three critical woff2 font
	 * files that affect LCP: Courier Prime regular (body text), Bodoni Moda
	 * regular (headings), and Bodoni Moda italic (heading emphasis).
	 *
	 * WordPress does not automatically emit preload hints for fonts declared
	 * in theme.json fontFamilies, so we add them manually here.
	 *
	 * @return void
	 */
	function the_drafting_table_preload_fonts() {
		$fonts_url      = get_theme_file_uri( 'fonts/' );
		$critical_fonts = array(
			'courier-prime-v11-regular-latin.woff2',
			'bodoni-moda-v28-latin.woff2',
			'bodoni-moda-v28-italic-latin.woff2',
			'josefin-sans-v34-latin.woff2',
		);
		foreach ( $critical_fonts as $font_file ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous">' . "\n",
				esc_url( $fonts_url . $font_file )
			);
		}
	}
}
add_action( 'wp_head', 'the_drafting_table_preload_fonts', 2 );
