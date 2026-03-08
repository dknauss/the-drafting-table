<?php
/**
 * Runs Theme Check against the built WP.org package profile.
 *
 * @package The_Drafting_Table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'the_drafting_table_themecheck_delete_dir' ) ) {
	/**
	 * Recursively deletes a directory.
	 *
	 * @param string $dir Directory path.
	 * @return void
	 */
	function the_drafting_table_themecheck_delete_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				rmdir( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- CLI-only test utility.
			} else {
				unlink( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_unlink -- CLI-only test utility.
			}
		}

		rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- CLI-only test utility.
	}
}

if ( ! function_exists( 'the_drafting_table_themecheck_copy_dir' ) ) {
	/**
	 * Recursively copies one directory into another.
	 *
	 * @param string $source Source directory.
	 * @param string $target Target directory.
	 * @return bool
	 */
	function the_drafting_table_themecheck_copy_dir( $source, $target ) {
		if ( ! is_dir( $source ) ) {
			return false;
		}

		if ( ! is_dir( $target ) && ! wp_mkdir_p( $target ) ) {
			return false;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $item ) {
			$relative_path = substr( $item->getPathname(), strlen( $source ) + 1 );
			$target_path   = $target . DIRECTORY_SEPARATOR . $relative_path;

			if ( $item->isDir() ) {
				if ( ! is_dir( $target_path ) && ! mkdir( $target_path, 0755, true ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- CLI-only test utility.
					return false;
				}
			} elseif ( ! copy( $item->getPathname(), $target_path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- CLI-only test utility.
				return false;
			}
		}

		return true;
	}
}

$theme_root   = trailingslashit( get_theme_root() );
$source_dir   = $theme_root . 'the-drafting-table/dist/wporg/the-drafting-table';
$target_slug  = 'the-drafting-table-wporg-check';
$target_dir   = $theme_root . $target_slug;
$runner_file  = $theme_root . 'the-drafting-table/run-themecheck.php';
$active_theme = get_stylesheet();

if ( ! is_dir( $source_dir ) ) {
	fwrite( STDERR, "WP.org package directory was not found: {$source_dir}\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI-only status output.
	exit( 1 );
}

if ( ! file_exists( $runner_file ) ) {
	fwrite( STDERR, "Theme check runner was not found: {$runner_file}\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI-only status output.
	exit( 1 );
}

the_drafting_table_themecheck_delete_dir( $target_dir );
if ( ! the_drafting_table_themecheck_copy_dir( $source_dir, $target_dir ) ) {
	fwrite( STDERR, "Could not copy built package into a temporary theme directory.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI-only status output.
	exit( 1 );
}

$result = array(
	'pass' => false,
);

try {
	switch_theme( $target_slug );

	define( 'THE_DRAFTING_TABLE_THEMECHECK_AUTO_RUN', false );
	define( 'THE_DRAFTING_TABLE_THEMECHECK_THEME_SLUG', $target_slug );
	require_once $runner_file;

	$result = the_drafting_table_run_themecheck( $target_slug );
} finally {
	switch_theme( $active_theme );
	the_drafting_table_themecheck_delete_dir( $target_dir );
}

exit( ! empty( $result['pass'] ) ? 0 : 1 );
