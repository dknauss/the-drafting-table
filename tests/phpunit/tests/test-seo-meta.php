<?php
/**
 * Tests for companion SEO/meta output.
 */

class TheDraftingTable_SeoMeta_Test extends WP_UnitTestCase {
	/**
	 * Ensure theme + plugin are active.
	 */
	public function set_up(): void {
		parent::set_up();
		switch_theme( 'the-drafting-table' );
	}

	public function test_seo_meta_uses_excerpt_for_singular_posts() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => 'Excerpt Entry',
				'post_excerpt' => 'Excerpt description for SEO.',
				'post_content' => 'Long body that should not override excerpt.',
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		ob_start();
		the_drafting_table_seo_meta();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'meta name="description"', $output );
		$this->assertStringContainsString( 'Excerpt description for SEO.', $output );
		$this->assertStringNotContainsString( 'Long body that should not override excerpt.', $output );
	}

	public function test_seo_meta_falls_back_to_trimmed_content_when_excerpt_is_empty() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => 'Content Entry',
				'post_excerpt' => '',
				'post_content' => 'Alpha beta gamma delta epsilon zeta eta theta iota kappa lambda mu nu xi omicron pi rho sigma tau.',
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		ob_start();
		the_drafting_table_seo_meta();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'meta name="description"', $output );
		$this->assertStringContainsString( 'Alpha beta gamma', $output );
	}

	public function test_seo_meta_uses_site_description_on_home() {
		update_option( 'blogdescription', 'Drafting table test tagline.' );
		$this->go_to( home_url( '/' ) );

		ob_start();
		the_drafting_table_seo_meta();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'Drafting table test tagline.', $output );
	}

	public function test_seo_meta_uses_archive_description_on_category_archives() {
		$category = self::factory()->category->create(
			array(
				'name'        => 'Material Studies',
				'slug'        => 'material-studies',
				'description' => 'Archive description text.',
			)
		);
		$post_id  = self::factory()->post->create(
			array(
				'post_status' => 'publish',
			)
		);
		wp_set_post_terms( $post_id, array( $category ), 'category' );

		$this->go_to( get_term_link( (int) $category, 'category' ) );

		ob_start();
		the_drafting_table_seo_meta();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'Archive description text.', $output );
	}

	public function test_structured_data_outputs_website_json_ld_on_front_page() {
		update_option( 'blogname', 'The Drafting Table' );
		update_option( 'blogdescription', 'Notes on structure.' );
		$this->go_to( home_url( '/' ) );

		ob_start();
		the_drafting_table_structured_data();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'application/ld+json', $output );
		$this->assertStringContainsString( '"@type":"WebSite"', $output );
		$this->assertStringContainsString( '"potentialAction"', $output );
	}

	public function test_structured_data_outputs_article_json_ld_on_single_post() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Concrete Poetics',
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		ob_start();
		the_drafting_table_structured_data();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( '"@type":"Article"', $output );
		$this->assertStringContainsString( '"headline":"Concrete Poetics"', $output );
	}

	public function test_noindex_meta_only_outputs_on_paged_requests() {
		update_option( 'posts_per_page', 1 );

		for ( $i = 0; $i < 2; $i++ ) {
			self::factory()->post->create(
				array(
					'post_type'   => 'post',
					'post_status' => 'publish',
				)
			);
		}

		$this->go_to( home_url( '/?paged=2' ) );
		ob_start();
		the_drafting_table_noindex_paged();
		$paged_output = (string) ob_get_clean();

		$this->go_to( home_url( '/' ) );
		ob_start();
		the_drafting_table_noindex_paged();
		$front_output = (string) ob_get_clean();

		$this->assertStringContainsString( 'noindex, follow', $paged_output );
		$this->assertSame( '', trim( $front_output ) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_seo_meta_bails_when_external_seo_plugin_is_detected() {
		if ( ! class_exists( 'All_in_One_SEO_Pack', false ) ) {
			eval( 'class All_in_One_SEO_Pack {}' );
		}

		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_excerpt' => 'Should not render due to SEO plugin class.',
			)
		);
		$this->go_to( get_permalink( $post_id ) );

		ob_start();
		the_drafting_table_seo_meta();
		$output = trim( (string) ob_get_clean() );

		$this->assertSame( '', $output );
	}
}
