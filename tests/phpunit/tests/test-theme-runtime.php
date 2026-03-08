<?php
/**
 * Tests for theme runtime helpers in functions.php and inc/*.php.
 */

class TheDraftingTable_ThemeRuntime_Test extends WP_UnitTestCase {
	/**
	 * Ensure the block theme and companion plugin functions are loaded.
	 */
	public function set_up(): void {
		parent::set_up();
		switch_theme( 'the-drafting-table' );
	}

	public function test_rewrite_block_urls_replaces_theme_protocol_tokens() {
		$input = '<a href="theme:./docs/file.pdf">Docs</a><img src="theme:./assets/images/demo.svg" /><div style="background-image:url(\'theme:./assets/images/bg.svg\')"></div>';

		$output = the_drafting_table_rewrite_block_urls( $input );
		$base   = get_stylesheet_directory_uri();

		$this->assertStringContainsString( 'href="' . $base . '/docs/file.pdf"', $output );
		$this->assertStringContainsString( 'src="' . $base . '/assets/images/demo.svg"', $output );
		$this->assertStringContainsString( "url('" . $base . '/assets/images/bg.svg\')', $output );
	}

	public function test_rewrite_block_urls_returns_content_without_theme_tokens() {
		$input = '<p>No rewrite needed.</p>';

		$this->assertSame( $input, the_drafting_table_rewrite_block_urls( $input ) );
	}

	public function test_featured_image_caption_injects_caption_on_single_posts() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => 'Captioned Entry',
				'post_content' => 'Body content.',
			)
		);

		$attachment_id = self::factory()->post->create(
			array(
				'post_type'    => 'attachment',
				'post_status'  => 'inherit',
				'post_mime_type' => 'image/jpeg',
				'post_excerpt' => 'A crafted caption.',
			)
		);
		update_post_meta( $post_id, '_thumbnail_id', $attachment_id );

		$this->go_to( get_permalink( $post_id ) );

		$input  = '<figure class="wp-block-post-featured-image"><img src="https://example.com/image.jpg" alt="" /></figure>';
		$output = the_drafting_table_featured_image_caption(
			$input,
			array( 'blockName' => 'core/post-featured-image' )
		);

		$this->assertStringContainsString( '<figcaption class="wp-element-caption">', $output );
		$this->assertStringContainsString( 'featured-image-fig-label', $output );
		$this->assertStringContainsString( 'A crafted caption.', $output );
	}

	public function test_featured_image_caption_skips_output_when_native_caption_exists() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		$input = '<figure><figcaption class="wp-element-caption">Native</figcaption></figure>';

		$this->assertSame(
			$input,
			the_drafting_table_featured_image_caption( $input, array( 'blockName' => 'core/post-featured-image' ) )
		);
	}

	public function test_featured_image_caption_skips_non_singular_views() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$this->go_to( home_url( '/' ) );

		$input = '<figure class="wp-block-post-featured-image"><img src="https://example.com/image.jpg" alt="" /></figure>';

		$this->assertSame(
			$input,
			the_drafting_table_featured_image_caption(
				$input,
				array( 'blockName' => 'core/post-featured-image' )
			)
		);
	}

	public function test_post_incipit_wraps_first_clause_on_single_post() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		$input = '<p>First clause, then the rest.</p><p>Second paragraph.</p>';

		$output = the_drafting_table_post_incipit( $input );

		$this->assertStringContainsString( '<span class="post-incipit">First clause,</span>', $output );
		$this->assertStringContainsString( '<p>Second paragraph.</p>', $output );
	}

	public function test_post_incipit_does_not_run_off_single_post_views() {
		$this->go_to( home_url( '/' ) );

		$input = '<p>First clause, then the rest.</p>';

		$this->assertSame( $input, the_drafting_table_post_incipit( $input ) );
	}

	public function test_preload_fonts_outputs_expected_font_hints() {
		ob_start();
		the_drafting_table_preload_fonts();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'rel="preload"', $output );
		$this->assertStringContainsString( 'courier-prime-v11-regular-latin.woff2', $output );
		$this->assertStringContainsString( 'bodoni-moda-v28-latin.woff2', $output );
		$this->assertStringContainsString( 'bodoni-moda-v28-italic-latin.woff2', $output );
		$this->assertStringContainsString( 'josefin-sans-v34-latin.woff2', $output );
	}

	public function test_enqueue_editor_rewrite_script_registers_inline_base_url() {
		the_drafting_table_enqueue_editor_rewrite_script();

		$this->assertTrue( wp_script_is( 'theme-assets-editor-rewrite', 'enqueued' ) );

		$wp_scripts = wp_scripts();
		$before     = $wp_scripts->get_data( 'theme-assets-editor-rewrite', 'before' );
		$before     = is_array( $before ) ? implode( "\n", $before ) : (string) $before;

		$this->assertStringContainsString( 'window.THEME_ASSETS_BASE_URL=', $before );
		$this->assertStringContainsString( wp_json_encode( get_stylesheet_directory_uri() ), $before );
	}

	public function test_enqueue_styles_registers_main_stylesheet_handle() {
		the_drafting_table_enqueue_styles();

		$this->assertTrue( wp_style_is( 'the-drafting-table-style', 'enqueued' ) );
	}
}
