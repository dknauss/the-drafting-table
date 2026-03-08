<?php
/**
 * Plugin Name: The Drafting Table Companion
 * Description: Optional demo content onboarding and SEO/meta utilities for The Drafting Table theme.
 * Version: 0.1.0
 * Author: Dan Knauss
 * License: GPL-2.0-or-later
 * Text Domain: the-drafting-table
 *
 * @package The_Drafting_Table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'the_drafting_table_companion_is_theme_active' ) ) {
	/**
	 * Returns true when The Drafting Table (or its child theme) is active.
	 *
	 * @return bool
	 */
	function the_drafting_table_companion_is_theme_active() {
		$theme = wp_get_theme();

		if ( 'the-drafting-table' === $theme->get_stylesheet() ) {
			return true;
		}

		if ( $theme->parent() && 'the-drafting-table' === $theme->parent()->get_stylesheet() ) {
			return true;
		}

		return false;
	}
}

require_once __DIR__ . '/inc/create-pages.php';
require_once __DIR__ . '/inc/seo-meta.php';
