<?php
/**
 * Runs the Theme Check plugin against the active theme and exits non-zero on failure.
 *
 * @package The_Drafting_Table
 */

$checkbase_file = WP_PLUGIN_DIR . '/theme-check/checkbase.php';

if ( ! file_exists( $checkbase_file ) ) {
	fwrite( STDERR, "Theme Check plugin is not installed.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI-only status output.
	exit( 1 );
}

require_once $checkbase_file;

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_glob -- Theme Check exposes its checks as individual PHP files on disk.
foreach ( glob( WP_PLUGIN_DIR . '/theme-check/checks/*.php' ) as $check_file ) {
	require_once $check_file;
}

global $themechecks;

$theme_slug = get_stylesheet();
$theme      = wp_get_theme( $theme_slug );
$pass       = run_themechecks_against_theme( $theme, $theme_slug );

$check_errors = array();

foreach ( $themechecks as $check ) {
	if ( $check instanceof themecheck ) {
		$check_error = (array) $check->getError();

		if ( ! empty( $check_error ) ) {
			$check_errors = array_unique( array_merge( $check_errors, $check_error ) );
		}
	}
}

sort( $check_errors );

$counts = array(
	'REQUIRED'    => 0,
	'RECOMMENDED' => 0,
	'WARNING'     => 0,
	'INFO'        => 0,
);

foreach ( $check_errors as $error_message ) {
	$clean_error = wp_strip_all_tags( $error_message );

	foreach ( array_keys( $counts ) as $level ) {
		if ( false !== strpos( $clean_error, $level ) ) {
			++$counts[ $level ];
			echo esc_html( $clean_error ) . "\n\n";
			break;
		}
	}
}

echo 'Pass: ' . esc_html( $pass ? 'YES' : 'NO' ) . "\n";
echo 'Counts: ';

foreach ( $counts as $level => $count ) {
	echo esc_html( $level ) . '=' . absint( $count ) . ' ';
}

echo "\n";

exit( $pass ? 0 : 1 );
