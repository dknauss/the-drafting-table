<?php
/**
 * Tests for companion installer notices and state/manifest helpers.
 */

class TheDraftingTable_DemoAdminAndState_Test extends WP_UnitTestCase {
	/**
	 * Ensure the block theme and companion plugin are loaded for each test.
	 */
	public function set_up(): void {
		parent::set_up();
		switch_theme( 'the-drafting-table' );
	}

	public function tear_down(): void {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	public function test_demo_admin_notice_is_hidden_without_theme_capability() {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		update_option( 'the_drafting_table_demo_pending', '1' );
		delete_option( 'the_drafting_table_demo_installed' );

		ob_start();
		the_drafting_table_demo_admin_notice();
		$output = trim( (string) ob_get_clean() );

		$this->assertSame( '', $output );
	}

	public function test_demo_admin_notice_is_visible_for_theme_admins_when_pending() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		update_option( 'the_drafting_table_demo_pending', '1' );
		delete_option( 'the_drafting_table_demo_installed' );

		ob_start();
		the_drafting_table_demo_admin_notice();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'Install Demo Content', $output );
		$this->assertStringContainsString( 'the_drafting_table_install_demo', $output );
	}

	public function test_demo_admin_notice_clears_pending_when_demo_already_installed() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		update_option( 'the_drafting_table_demo_pending', '1' );
		update_option( 'the_drafting_table_demo_installed', '1' );

		ob_start();
		the_drafting_table_demo_admin_notice();
		$output = trim( (string) ob_get_clean() );

		$this->assertSame( '', $output );
		$this->assertFalse( get_option( 'the_drafting_table_demo_pending', false ) );
	}

	public function test_collect_demo_manifest_includes_marked_posts_assets_terms_and_featured_ids() {
		$demo_page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $demo_page_id, '_the_drafting_table_demo_content', '1' );

		$demo_post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $demo_post_id, '_the_drafting_table_demo_content', '1' );

		$asset_id = self::factory()->post->create(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/svg+xml',
			)
		);
		update_post_meta( $asset_id, '_the_drafting_table_demo_asset', 'board-formed-concrete' );

		$featured_post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $featured_post_id, '_the_drafting_table_featured_entry', '1' );

		$term = wp_insert_term(
			'Manifest Category',
			'category',
			array(
				'description' => 'Installer tag.',
				'slug'        => 'manifest-category',
			)
		);
		$this->assertNotWPError( $term );
		add_term_meta( (int) $term['term_id'], '_the_drafting_table_demo_content', '1', true );

		$manifest = the_drafting_table_collect_demo_manifest(
			444,
			array(
				'show_on_front'  => 'posts',
				'page_on_front'  => 11,
				'page_for_posts' => 12,
				'custom_logo'    => 13,
			)
		);

		$this->assertContains( $demo_page_id, $manifest['post_ids'] );
		$this->assertContains( $demo_post_id, $manifest['post_ids'] );
		$this->assertContains( $asset_id, $manifest['asset_ids'] );
		$this->assertContains( $featured_post_id, $manifest['featured_ids'] );
		$this->assertContains( (int) $term['term_id'], $manifest['term_ids'] );
		$this->assertSame( 444, $manifest['logo_id'] );
		$this->assertSame( 'posts', $manifest['previous_state']['show_on_front'] );
	}

	public function test_restore_demo_site_state_sanitizes_invalid_values_and_restores_logo_from_state() {
		set_theme_mod( 'custom_logo', 999 );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', 555 );
		update_option( 'page_for_posts', 556 );

		the_drafting_table_restore_demo_site_state(
			array(
				'show_on_front'  => 'invalid',
				'page_on_front'  => 'not-a-number',
				'page_for_posts' => 'also-not-a-number',
				'custom_logo'    => 0,
			)
		);

		$this->assertSame( 'posts', get_option( 'show_on_front' ) );
		$this->assertSame( 0, (int) get_option( 'page_on_front' ) );
		$this->assertSame( 0, (int) get_option( 'page_for_posts' ) );
		$this->assertSame( 0, (int) get_theme_mod( 'custom_logo', 0 ) );
	}

	public function test_configure_reading_settings_falls_back_to_about_and_journal_slugs() {
		$about_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'About',
				'post_name'   => 'about',
			)
		);
		$journal_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Journal',
				'post_name'   => 'journal',
			)
		);

		$result = the_drafting_table_configure_reading_settings( array() );

		$this->assertTrue( $result );
		$this->assertSame( 'page', get_option( 'show_on_front' ) );
		$this->assertSame( $about_id, (int) get_option( 'page_on_front' ) );
		$this->assertSame( $journal_id, (int) get_option( 'page_for_posts' ) );
	}

	public function test_mark_demo_featured_post_replaces_existing_marker() {
		$existing_featured_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $existing_featured_id, '_the_drafting_table_featured_entry', '1' );

		$new_featured_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$result = the_drafting_table_mark_demo_featured_post(
			array(
				'glass-transparency-dissolution-walls' => $new_featured_id,
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '', (string) get_post_meta( $existing_featured_id, '_the_drafting_table_featured_entry', true ) );
		$this->assertSame( '1', get_post_meta( $new_featured_id, '_the_drafting_table_featured_entry', true ) );
	}

	public function test_remove_demo_content_deletes_terms_and_assets_and_restores_state() {
		$demo_post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$asset_id = self::factory()->post->create(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/svg+xml',
			)
		);

		$featured_only_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $featured_only_id, '_the_drafting_table_featured_entry', '1' );

		$term = wp_insert_term(
			'Demo Tag',
			'post_tag',
			array(
				'slug' => 'demo-tag',
			)
		);
		$this->assertNotWPError( $term );

		update_option(
			'the_drafting_table_demo_manifest',
			array(
				'post_ids'       => array( $demo_post_id ),
				'asset_ids'      => array( $asset_id ),
				'term_ids'       => array( (int) $term['term_id'] ),
				'featured_ids'   => array( $featured_only_id ),
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

		$this->assertTrue( $result );
		$this->assertNull( get_post( $demo_post_id ) );
		$this->assertNull( get_post( $asset_id ) );
		$this->assertEmpty( term_exists( (int) $term['term_id'], 'post_tag' ) );
		$this->assertSame( '', (string) get_post_meta( $featured_only_id, '_the_drafting_table_featured_entry', true ) );
		$this->assertSame( 'posts', get_option( 'show_on_front' ) );
		$this->assertSame( '1', get_option( 'the_drafting_table_demo_pending' ) );
	}
}
