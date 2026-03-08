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

	public function test_assign_demo_media_reports_missing_posts_for_unmapped_install_sets() {
		$result = the_drafting_table_assign_demo_media( array() );

		$this->assertSame( 0, $result['assigned'] );
		$this->assertCount( 8, $result['missing_posts'] );
		$this->assertSame( array(), $result['failed_assets'] );
	}

	public function test_assign_demo_media_reports_failed_asset_when_source_file_is_missing() {
		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
				'post_name'   => 'character-of-board-formed-concrete',
			)
		);

		$missing_asset_filter = static function ( $path, $file ) {
			if ( 'assets/images/demo-board-formed-concrete.svg' === $file ) {
				return '/tmp/the-drafting-table-missing.svg';
			}

			return $path;
		};
		add_filter( 'theme_file_path', $missing_asset_filter, 10, 2 );

		$result = the_drafting_table_assign_demo_media(
			array(
				'character-of-board-formed-concrete' => $post_id,
			)
		);

		remove_filter( 'theme_file_path', $missing_asset_filter, 10 );

		$this->assertContains( 'board-formed-concrete', $result['failed_assets'] );
		$this->assertSame( 0, $result['assigned'] );
		$this->assertSame( 0, get_post_thumbnail_id( $post_id ) );
	}

	public function test_remove_demo_content_returns_error_and_still_restores_state_on_delete_failure() {
		$demo_post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$block_delete_filter = static function ( $delete, $post, $force_delete ) use ( $demo_post_id ) {
			if ( $force_delete && $post instanceof WP_Post && (int) $post->ID === (int) $demo_post_id ) {
				return false;
			}

			return $delete;
		};
		add_filter( 'pre_delete_post', $block_delete_filter, 10, 3 );

		update_option(
			'the_drafting_table_demo_manifest',
			array(
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
			)
		);
		update_option( 'the_drafting_table_demo_installed', '1' );
		update_option( 'show_on_front', 'page' );

		$result = the_drafting_table_remove_demo_content();
		remove_filter( 'pre_delete_post', $block_delete_filter, 10 );

		$this->assertWPError( $result );
		$this->assertSame( 'the_drafting_table_demo_remove_failed', $result->get_error_code() );
		$this->assertSame( 'posts', get_option( 'show_on_front' ) );
		$this->assertSame( '1', get_option( 'the_drafting_table_demo_pending' ) );
		$this->assertFalse( get_option( 'the_drafting_table_demo_manifest', false ) );
	}

	public function test_install_demo_content_is_idempotent_across_repeated_runs() {
		$theme_path_filter = static function ( $path, $file ) {
			return dirname( __DIR__, 3 ) . '/' . ltrim( (string) $file, '/' );
		};
		add_filter( 'theme_file_path', $theme_path_filter, 10, 2 );

		$first_run = the_drafting_table_install_demo_content();
		$this->assertNotWPError( $first_run );

		$second_run = the_drafting_table_install_demo_content();
		$this->assertNotWPError( $second_run );

		remove_filter( 'theme_file_path', $theme_path_filter, 10 );

		$this->assertSame( $first_run['page_ids']['about'], $second_run['page_ids']['about'] );
		$this->assertSame(
			$first_run['post_ids']['glass-transparency-dissolution-walls'],
			$second_run['post_ids']['glass-transparency-dissolution-walls']
		);

		$demo_pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_the_drafting_table_demo_content',
				'meta_value'     => '1',
			)
		);

		$demo_posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_the_drafting_table_demo_content',
				'meta_value'     => '1',
			)
		);

		$featured_ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_the_drafting_table_featured_entry',
				'meta_value'     => '1',
			)
		);

		$this->assertCount( 4, $demo_pages );
		$this->assertCount( count( the_drafting_table_get_sample_posts_data() ), $demo_posts );
		$this->assertCount( 1, $featured_ids );
	}

	public function test_mark_demo_featured_post_is_idempotent_when_post_is_already_marked() {
		$featured_post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$post_ids = array(
			'glass-transparency-dissolution-walls' => $featured_post_id,
		);

		$this->assertTrue( the_drafting_table_mark_demo_featured_post( $post_ids ) );
		$this->assertTrue( the_drafting_table_mark_demo_featured_post( $post_ids ) );
		$this->assertSame( '1', get_post_meta( $featured_post_id, '_the_drafting_table_featured_entry', true ) );
	}
}
