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
}
