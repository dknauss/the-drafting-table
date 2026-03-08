<?php
/**
 * Tests for marked query-loop behavior in functions.php.
 */

class TheDraftingTable_QueryLoopMarkers_Test extends WP_UnitTestCase {
	/**
	 * Ensure the theme is active so functions.php is loaded.
	 */
	public function set_up(): void {
		parent::set_up();
		switch_theme( 'the-drafting-table' );
	}

	public function test_front_hero_query_uses_featured_entry_marker() {
		$featured_id = self::factory()->post->create(
			array(
				'post_title'  => 'Featured Entry',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $featured_id, '_the_drafting_table_featured_entry', '1' );

		$front_page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Front',
				'post_status' => 'publish',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page_id );
		$this->go_to( home_url( '/' ) );

		$block = new WP_Block(
			array(
				'blockName' => 'core/query',
				'attrs'     => array(
					'namespace' => 'the-drafting-table/front-hero',
				),
			),
			array()
		);

		$query = the_drafting_table_filter_query_loop_vars( array(), $block );

		$this->assertSame( array( $featured_id ), $query['post__in'] );
		$this->assertSame( 'post__in', $query['orderby'] );
	}

	public function test_front_rail_excludes_featured_entry() {
		$featured_id = self::factory()->post->create(
			array(
				'post_title'  => 'Featured Entry',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $featured_id, '_the_drafting_table_featured_entry', '1' );

		$front_page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Front',
				'post_status' => 'publish',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page_id );
		$this->go_to( home_url( '/' ) );

		$block = new WP_Block(
			array(
				'blockName' => 'core/query',
				'attrs'     => array(
					'className' => 'is-the-drafting-table-front-rail',
				),
			),
			array()
		);

		$query = the_drafting_table_filter_query_loop_vars( array(), $block );

		$this->assertContains( $featured_id, $query['post__not_in'] );
	}

	public function test_query_has_marker_matches_namespace_or_class_tokens() {
		$hero_namespace_block = new WP_Block(
			array(
				'blockName' => 'core/query',
				'attrs'     => array(
					'namespace' => 'the-drafting-table/front-hero',
				),
			),
			array()
		);
		$front_rail_class_block = new WP_Block(
			array(
				'blockName' => 'core/query',
				'attrs'     => array(
					'className' => 'wp-block-query is-the-drafting-table-front-rail extra-class',
				),
			),
			array()
		);

		$this->assertTrue(
			the_drafting_table_query_has_marker(
				$hero_namespace_block,
				'the-drafting-table/front-hero',
				'is-the-drafting-table-front-hero'
			)
		);

		$this->assertTrue(
			the_drafting_table_query_has_marker(
				$front_rail_class_block,
				'the-drafting-table/front-rail',
				'is-the-drafting-table-front-rail'
			)
		);
	}

	public function test_front_hero_query_falls_back_to_latest_post_when_no_marker_is_set() {
		$front_page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Front',
				'post_status' => 'publish',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page_id );
		$this->go_to( home_url( '/' ) );

		$block = new WP_Block(
			array(
				'blockName' => 'core/query',
				'attrs'     => array(
					'namespace' => 'the-drafting-table/front-hero',
				),
			),
			array()
		);

		$query = the_drafting_table_filter_query_loop_vars( array(), $block );

		$this->assertArrayNotHasKey( 'post__in', $query );
		$this->assertSame( 'date', $query['orderby'] );
		$this->assertSame( 'DESC', $query['order'] );
	}

	public function test_hide_empty_meta_returns_empty_string_when_dynamic_value_is_missing() {
		$block = array(
			'blockName' => 'core/group',
			'attrs'     => array(
				'className' => 'meta-pair',
			),
		);
		$content_with_value = '<p class="meta-label">Date</p><p>May 18, 2025</p>';
		$content_empty      = '<p class="meta-label">Date</p><p></p>';

		$this->assertSame( $content_with_value, the_drafting_table_hide_empty_meta( $content_with_value, $block ) );
		$this->assertSame( '', the_drafting_table_hide_empty_meta( $content_empty, $block ) );
	}
}
