<?php
/**
 * Runs the Theme Check plugin against the active theme and exits non-zero on failure.
 *
 * @package The_Drafting_Table
 */

if ( ! function_exists( 'the_drafting_table_themecheck_load' ) ) {
	/**
	 * Loads Theme Check core + checks.
	 *
	 * @return bool
	 */
	function the_drafting_table_themecheck_load() {
		$checkbase_file = WP_PLUGIN_DIR . '/theme-check/checkbase.php';
		if ( ! file_exists( $checkbase_file ) ) {
			fwrite( STDERR, "Theme Check plugin is not installed.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI-only status output.
			return false;
		}

		require_once $checkbase_file;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_glob -- Theme Check exposes its checks as individual PHP files on disk.
		foreach ( glob( WP_PLUGIN_DIR . '/theme-check/checks/*.php' ) as $check_file ) {
			require_once $check_file;
		}

		return true;
	}
}

if ( ! function_exists( 'the_drafting_table_run_themecheck' ) ) {
	/**
	 * Executes Theme Check and prints summarized results.
	 *
	 * @param string $theme_slug Theme slug to validate.
	 * @param string $theme_root Optional theme root to resolve the slug from.
	 * @return array{pass:bool, counts:array<string,int>}
	 */
	function the_drafting_table_run_themecheck( $theme_slug = '', $theme_root = '' ) {
		$counts = array(
			'REQUIRED'    => 0,
			'RECOMMENDED' => 0,
			'WARNING'     => 0,
			'INFO'        => 0,
		);

		if ( ! the_drafting_table_themecheck_load() ) {
			$counts['REQUIRED'] = 1;
			echo 'Pass: NO' . "\n";
			echo 'Counts: REQUIRED=1 RECOMMENDED=0 WARNING=0 INFO=0' . "\n";
			return array(
				'pass'   => false,
				'counts' => $counts,
			);
		}

		global $themechecks;

		$theme_slug = $theme_slug ? sanitize_key( $theme_slug ) : get_stylesheet();
		$theme_root = $theme_root ? wp_normalize_path( untrailingslashit( (string) $theme_root ) ) : '';
		$theme      = $theme_root ? wp_get_theme( $theme_slug, $theme_root ) : wp_get_theme( $theme_slug );

		if ( ! $theme->exists() ) {
			$counts['REQUIRED'] = 1;
			echo 'REQUIRED: Theme slug could not be resolved for Theme Check.' . "\n\n";
			echo 'Pass: NO' . "\n";
			echo 'Counts: REQUIRED=1 RECOMMENDED=0 WARNING=0 INFO=0' . "\n";
			return array(
				'pass'   => false,
				'counts' => $counts,
			);
		}

		$pass = run_themechecks_against_theme( $theme, $theme_slug );

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

		return array(
			'pass'   => (bool) $pass,
			'counts' => $counts,
		);
	}
}

if ( ! defined( 'THE_DRAFTING_TABLE_THEMECHECK_AUTO_RUN' ) || THE_DRAFTING_TABLE_THEMECHECK_AUTO_RUN ) {
	$theme_slug = defined( 'THE_DRAFTING_TABLE_THEMECHECK_THEME_SLUG' ) ? (string) constant( 'THE_DRAFTING_TABLE_THEMECHECK_THEME_SLUG' ) : '';
	$theme_root = defined( 'THE_DRAFTING_TABLE_THEMECHECK_THEME_ROOT' ) ? (string) constant( 'THE_DRAFTING_TABLE_THEMECHECK_THEME_ROOT' ) : '';
	$result     = the_drafting_table_run_themecheck( $theme_slug, $theme_root );
	exit( ! empty( $result['pass'] ) ? 0 : 1 );
}
