<?php
/**
 * Tests for companion demo installer utilities.
 */

class TheDraftingTable_DemoInstaller_Test extends WP_UnitTestCase {
	/**
	 * Ensure theme/plugin functions are available for each test.
	 */
	public function set_up(): void {
		parent::set_up();
		switch_theme( 'the-drafting-table' );
	}

	public function test_created_pages_are_marked_for_cleanup() {
		$page_ids = the_drafting_table_create_pages();

		$this->assertNotEmpty( $page_ids['about'] );
		$this->assertSame( '1', get_post_meta( (int) $page_ids['about'], '_the_drafting_table_demo_content', true ) );
	}

	public function test_remove_demo_content_restores_state_and_deletes_marked_posts() {
		$demo_post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $demo_post_id, '_the_drafting_table_demo_content', '1' );

		update_option( 'the_drafting_table_demo_manifest', array(
			'post_ids'       => array( $demo_post_id ),
			'asset_ids'      => array(),
			'term_ids'       => array(),
			'featured_ids'   => array(),
			'previous_state' => array(
				'show_on_front'  => 'posts',
				'page_on_front'  => 0,
				'page_for_posts' => 0,
				'custom_logo'    => 0,
			),
		) );
		update_option( 'the_drafting_table_demo_installed', '1' );
		update_option( 'show_on_front', 'page' );

		$result = the_drafting_table_remove_demo_content();

		$this->assertTrue( $result );
		$this->assertNull( get_post( $demo_post_id ) );
		$this->assertSame( 'posts', get_option( 'show_on_front' ) );
		$this->assertSame( '1', get_option( 'the_drafting_table_demo_pending' ) );
		$this->assertFalse( get_option( 'the_drafting_table_demo_installed', false ) );
	}
}
