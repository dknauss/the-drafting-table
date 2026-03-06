<?php
/**
 * Optional SEO/meta helpers for The Drafting Table.
 *
 * @package The_Drafting_Table
 */

if ( ! function_exists( 'the_drafting_table_seo_meta' ) ) {
	/**
	 * Outputs SEO meta tags in the document head.
	 */
	function the_drafting_table_seo_meta() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

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
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

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

if ( ! function_exists( 'the_drafting_table_noindex_paged' ) ) {
	/**
	 * Output a noindex meta tag on paginated archive pages (page 2, 3, ...).
	 */
	function the_drafting_table_noindex_paged() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

		if ( is_paged() ) {
			echo '<meta name="robots" content="noindex, follow">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string, no user input
		}
	}
}
add_action( 'wp_head', 'the_drafting_table_noindex_paged', 1 );
