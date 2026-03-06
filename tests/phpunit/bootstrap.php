<?php
/**
 * PHPUnit bootstrap for The Drafting Table.
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test suite not found at {$_tests_dir}. Set WP_TESTS_DIR.\n" );
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load theme + companion plugin functions for tests.
 */
function the_drafting_table_tests_load_companion_plugin() {
	require_once dirname( __DIR__, 2 ) . '/functions.php';
	require_once dirname( __DIR__, 2 ) . '/companion-plugin/the-drafting-table-companion/the-drafting-table-companion.php';
}

tests_add_filter( 'muplugins_loaded', 'the_drafting_table_tests_load_companion_plugin' );

require_once $_tests_dir . '/includes/bootstrap.php';
