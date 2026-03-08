<?php
/**
 * Bootstraps a clean demo install inside wp-env.
 *
 * @package The_Drafting_Table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! function_exists( 'activate_plugin' ) ) {
	fwrite( STDERR, "WordPress plugin APIs are unavailable.\n" );
	exit( 1 );
}

if ( ! is_plugin_active( 'the-drafting-table-companion/the-drafting-table-companion.php' ) ) {
	$activation_result = activate_plugin( 'the-drafting-table-companion/the-drafting-table-companion.php' );

	if ( is_wp_error( $activation_result ) ) {
		fwrite( STDERR, "Could not activate The Drafting Table Companion plugin.\n" );
		exit( 1 );
	}
}

if ( ! function_exists( 'the_drafting_table_install_demo_content' ) ) {
	fwrite( STDERR, "The Drafting Table installer functions are not loaded.\n" );
	exit( 1 );
}

if ( ! function_exists( 'the_drafting_table_load_wp_importer' ) ) {
	/**
	 * Loads WordPress importer classes in CLI-safe order.
	 *
	 * @return bool
	 */
	function the_drafting_table_load_wp_importer() {
		if ( class_exists( 'WP_Import' ) ) {
			return true;
		}

		if ( ! class_exists( 'WP_Importer' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		}

		$importer_dir = WP_PLUGIN_DIR . '/wordpress-importer';
		if ( ! is_dir( $importer_dir ) ) {
			return false;
		}

		$required_files = array(
			$importer_dir . '/compat.php',
			$importer_dir . '/parsers/class-wxr-parser.php',
			$importer_dir . '/parsers/class-wxr-parser-simplexml.php',
			$importer_dir . '/parsers/class-wxr-parser-xml.php',
			$importer_dir . '/parsers/class-wxr-parser-regex.php',
			$importer_dir . '/parsers/class-wxr-parser-xml-processor.php',
			$importer_dir . '/class-wp-import.php',
		);

		if ( ! class_exists( 'WordPress\XML\XMLProcessor' ) && file_exists( $importer_dir . '/php-toolkit/load.php' ) ) {
			require_once $importer_dir . '/php-toolkit/load.php';
		}

		foreach ( $required_files as $required_file ) {
			if ( ! file_exists( $required_file ) ) {
				return false;
			}
			require_once $required_file;
		}

		return class_exists( 'WP_Import' );
	}
}

if ( ! the_drafting_table_load_wp_importer() ) {
	fwrite( STDERR, "WordPress Importer plugin is not available.\n" );
	exit( 1 );
}

if ( ! function_exists( 'the_drafting_table_demo_upload_mimes' ) ) {
	/**
	 * Allows bundled SVG fixtures used by the demo installer.
	 *
	 * @param array<string, string> $mimes Allowed MIME map.
	 * @return array<string, string>
	 */
	function the_drafting_table_demo_upload_mimes( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}
}
add_filter( 'upload_mimes', 'the_drafting_table_demo_upload_mimes' );

if ( ! function_exists( 'the_drafting_table_import_wxr_fixture' ) ) {
	/**
	 * Imports a WXR file into the current wp-env site.
	 *
	 * @param string $fixture_path Absolute path to WXR fixture.
	 * @return true|WP_Error
	 */
	function the_drafting_table_import_wxr_fixture( $fixture_path ) {
		if ( ! file_exists( $fixture_path ) ) {
			return new WP_Error( 'the_drafting_table_missing_fixture', 'Fixture file is missing: ' . $fixture_path );
		}

		$importer                    = new WP_Import();
		$importer->fetch_attachments = false;

		ob_start();
		$import_result = $importer->import( $fixture_path );
		ob_end_clean();

		if ( is_wp_error( $import_result ) ) {
			return $import_result;
		}

		return true;
	}
}

$starter_post = get_page_by_path( 'hello-world', OBJECT, 'post' );
if ( $starter_post ) {
	wp_delete_post( $starter_post->ID, true );
}

$starter_page = get_page_by_path( 'sample-page' );
if ( $starter_page ) {
	wp_delete_post( $starter_page->ID, true );
}

$auto_drafts = get_posts(
	array(
		'post_type'      => array( 'post', 'page' ),
		'post_status'    => 'auto-draft',
		'posts_per_page' => -1,
	)
);

foreach ( $auto_drafts as $auto_draft ) {
	wp_delete_post( $auto_draft->ID, true );
}

update_option( 'blogname', 'The Drafting Table' );
update_option( 'blogdescription', 'Notes on Form, Nature, and the Drawn Line' );

$editor_user_id = username_exists( 'editor' );
if ( ! $editor_user_id ) {
	$editor_user_id = wp_create_user( 'editor', 'password', 'editor@example.com' );
}
if ( $editor_user_id && ! is_wp_error( $editor_user_id ) ) {
	$editor_user = get_user_by( 'id', (int) $editor_user_id );
	if ( $editor_user instanceof WP_User ) {
		$editor_user->set_role( 'editor' );
	}
}

global $wp_rewrite;
if ( isset( $wp_rewrite ) && $wp_rewrite instanceof WP_Rewrite ) {
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
} else {
	update_option( 'permalink_structure', '/%postname%/' );
}

$fixtures = array(
	get_theme_file_path( 'tests/fixtures/themeunittestdata.wordpress.xml' ),
	get_theme_file_path( 'tests/fixtures/64-block-test-data.xml' ),
	get_theme_file_path( 'tests/fixtures/a11y-theme-unit-test-data.xml' ),
);

$fixture_hashes = array();
foreach ( $fixtures as $fixture_file ) {
	$fixture_hashes[] = file_exists( $fixture_file ) ? md5_file( $fixture_file ) : 'missing';
}
$fixture_set_hash = md5( implode( '|', $fixture_hashes ) );
$imported_hash    = (string) get_option( 'the_drafting_table_imported_fixture_hash', '' );

if ( $fixture_set_hash !== $imported_hash ) {
	foreach ( $fixtures as $fixture_file ) {
		$fixture_result = the_drafting_table_import_wxr_fixture( $fixture_file );

		if ( is_wp_error( $fixture_result ) ) {
			fwrite( STDERR, wp_strip_all_tags( $fixture_result->get_error_message() ) . "\n" );
			exit( 1 );
		}
	}

	update_option( 'the_drafting_table_imported_fixture_hash', $fixture_set_hash );
}

$install_result = the_drafting_table_install_demo_content();
if ( is_wp_error( $install_result ) ) {
	foreach ( (array) $install_result->get_error_data( 'the_drafting_table_demo_install_failed' ) as $issue ) {
		fwrite( STDERR, wp_strip_all_tags( (string) $issue ) . "\n" );
	}
	exit( 1 );
}

update_option( 'the_drafting_table_demo_installed', '1' );
delete_option( 'the_drafting_table_demo_pending' );

flush_rewrite_rules();

if ( ! function_exists( 'save_mod_rewrite_rules' ) ) {
	require_once ABSPATH . 'wp-admin/includes/misc.php';
}

if ( function_exists( 'save_mod_rewrite_rules' ) ) {
	save_mod_rewrite_rules();
}
