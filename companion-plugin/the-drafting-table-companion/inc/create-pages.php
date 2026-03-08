<?php
/**
 * Demo content installer for The Drafting Table theme.
 *
 * On theme activation, displays an admin notice offering to install
 * sample pages and journal entries. Content is created only when the
 * user explicitly clicks the "Install Demo Content" button.
 *
 * @package The_Drafting_Table
 */

// -------------------------------------------------------------------------
// Activation flag — set on theme switch so the notice appears once.
// -------------------------------------------------------------------------

if ( ! function_exists( 'the_drafting_table_demo_notice_init' ) ) {
	/**
	 * On theme switch, mark demo content as pending if not yet installed.
	 *
	 * @return void
	 */
	function the_drafting_table_demo_notice_init() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

		if ( ! get_option( 'the_drafting_table_demo_installed' ) ) {
			update_option( 'the_drafting_table_demo_pending', '1' );
		}
	}
}
add_action( 'after_switch_theme', 'the_drafting_table_demo_notice_init' );

// -------------------------------------------------------------------------
// Admin notice — shown until the user installs or dismisses.
// -------------------------------------------------------------------------

if ( ! function_exists( 'the_drafting_table_demo_admin_notice' ) ) {
	/**
	 * Display the demo content installation notice in the WordPress admin.
	 *
	 * @return void
	 */
	function the_drafting_table_demo_admin_notice() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		if ( ! get_option( 'the_drafting_table_demo_pending' ) ) {
			return;
		}

		if ( get_option( 'the_drafting_table_demo_installed' ) ) {
			delete_option( 'the_drafting_table_demo_pending' );
			return;
		}

		$install_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=the_drafting_table_install_demo' ),
			'the_drafting_table_install_demo'
		);

		$dismiss_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=the_drafting_table_dismiss_demo' ),
			'the_drafting_table_dismiss_demo'
		);
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'The Drafting Table', 'the-drafting-table' ); ?></strong> &mdash;
				<?php esc_html_e( 'Would you like to install sample pages and journal entries to preview the theme?', 'the-drafting-table' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $install_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Install Demo Content', 'the-drafting-table' ); ?>
				</a>
				&nbsp;
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button">
					<?php esc_html_e( 'No thanks', 'the-drafting-table' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'the_drafting_table_demo_admin_notice' );

// -------------------------------------------------------------------------
// Admin POST handlers — process install and dismiss requests.
// -------------------------------------------------------------------------

if ( ! function_exists( 'the_drafting_table_handle_install_demo' ) ) {
	/**
	 * Handle the demo content installation form submission.
	 *
	 * @return void
	 */
	function the_drafting_table_handle_install_demo() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			wp_safe_redirect( admin_url( 'themes.php' ) );
			exit;
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'the-drafting-table' ) );
		}

		check_admin_referer( 'the_drafting_table_install_demo' );

		$previous_state = the_drafting_table_capture_demo_site_state();
		$install_result = the_drafting_table_install_demo_content();

		if ( is_wp_error( $install_result ) ) {
			update_option( 'the_drafting_table_demo_pending', '1' );
			update_option(
				'the_drafting_table_demo_manifest',
				the_drafting_table_collect_demo_manifest( 0, $previous_state )
			);
			set_transient(
				'the_drafting_table_demo_install_issues',
				(array) $install_result->get_error_data( 'the_drafting_table_demo_install_failed' ),
				10 * MINUTE_IN_SECONDS
			);
			wp_safe_redirect( admin_url( 'themes.php?the_drafting_table_demo=failed' ) );
			exit;
		}

		update_option(
			'the_drafting_table_demo_manifest',
			the_drafting_table_collect_demo_manifest(
				! empty( $install_result['logo_id'] ) ? (int) $install_result['logo_id'] : 0,
				$previous_state
			)
		);
		update_option( 'the_drafting_table_demo_installed', '1' );
		delete_option( 'the_drafting_table_demo_pending' );
		delete_transient( 'the_drafting_table_demo_install_issues' );

		wp_safe_redirect( admin_url( 'themes.php?the_drafting_table_demo=installed' ) );
		exit;
	}
}
add_action( 'admin_post_the_drafting_table_install_demo', 'the_drafting_table_handle_install_demo' );

if ( ! function_exists( 'the_drafting_table_handle_dismiss_demo' ) ) {
	/**
	 * Handle dismissal of the demo content notice without installing.
	 *
	 * @return void
	 */
	function the_drafting_table_handle_dismiss_demo() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			wp_safe_redirect( admin_url( 'themes.php' ) );
			exit;
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'the-drafting-table' ) );
		}

		check_admin_referer( 'the_drafting_table_dismiss_demo' );

		delete_option( 'the_drafting_table_demo_pending' );

		wp_safe_redirect( admin_url( 'themes.php' ) );
		exit;
	}
}
add_action( 'admin_post_the_drafting_table_dismiss_demo', 'the_drafting_table_handle_dismiss_demo' );

if ( ! function_exists( 'the_drafting_table_demo_success_notice' ) ) {
	/**
	 * Show a one-time success notice after demo content is installed.
	 *
	 * @return void
	 */
	function the_drafting_table_demo_success_notice() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag set by our own nonce-verified install handler via wp_safe_redirect.
		if ( empty( $_GET['the_drafting_table_demo'] ) || 'installed' !== $_GET['the_drafting_table_demo'] ) {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Demo content installed.', 'the-drafting-table' ); ?></strong>
				<?php esc_html_e( 'Sample pages and journal entries have been added to your site.', 'the-drafting-table' ); ?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'the_drafting_table_demo_success_notice' );

if ( ! function_exists( 'the_drafting_table_demo_failure_notice' ) ) {
	/**
	 * Show an actionable error notice when demo installation is incomplete.
	 *
	 * @return void
	 */
	function the_drafting_table_demo_failure_notice() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag set by our own nonce-verified install handler via wp_safe_redirect.
		if ( empty( $_GET['the_drafting_table_demo'] ) || 'failed' !== $_GET['the_drafting_table_demo'] ) {
			return;
		}

		$issues = get_transient( 'the_drafting_table_demo_install_issues' );
		$issues = is_array( $issues ) ? $issues : array();
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Demo content was only partially installed.', 'the-drafting-table' ); ?></strong>
				<?php esc_html_e( 'Please resolve the issues below and run the installer again.', 'the-drafting-table' ); ?>
			</p>
			<?php if ( ! empty( $issues ) ) : ?>
				<ul style="margin-left:1.25em;list-style:disc;">
					<?php foreach ( $issues as $issue ) : ?>
						<li><?php echo esc_html( $issue ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'the_drafting_table_demo_failure_notice' );

if ( ! function_exists( 'the_drafting_table_demo_management_notice' ) ) {
	/**
	 * Show management actions once demo content has been installed.
	 *
	 * @return void
	 */
	function the_drafting_table_demo_management_notice() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

		if ( ! current_user_can( 'edit_theme_options' ) || ! get_option( 'the_drafting_table_demo_installed' ) ) {
			return;
		}

		$remove_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=the_drafting_table_remove_demo' ),
			'the_drafting_table_remove_demo'
		);
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Demo content tools', 'the-drafting-table' ); ?></strong>
				<?php esc_html_e( 'Need a clean slate? Remove the imported demo content and restore the previous reading/logo settings.', 'the-drafting-table' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $remove_url ); ?>" class="button">
					<?php esc_html_e( 'Remove Demo Content', 'the-drafting-table' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'the_drafting_table_demo_management_notice' );

if ( ! function_exists( 'the_drafting_table_handle_remove_demo' ) ) {
	/**
	 * Handle removal of installer-created demo content.
	 *
	 * @return void
	 */
	function the_drafting_table_handle_remove_demo() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			wp_safe_redirect( admin_url( 'themes.php' ) );
			exit;
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'the-drafting-table' ) );
		}

		check_admin_referer( 'the_drafting_table_remove_demo' );

		$remove_result = the_drafting_table_remove_demo_content();

		if ( is_wp_error( $remove_result ) ) {
			set_transient(
				'the_drafting_table_demo_remove_issues',
				(array) $remove_result->get_error_data( 'the_drafting_table_demo_remove_failed' ),
				10 * MINUTE_IN_SECONDS
			);
			wp_safe_redirect( admin_url( 'themes.php?the_drafting_table_demo=remove_failed' ) );
			exit;
		}

		delete_transient( 'the_drafting_table_demo_remove_issues' );
		wp_safe_redirect( admin_url( 'themes.php?the_drafting_table_demo=removed' ) );
		exit;
	}
}
add_action( 'admin_post_the_drafting_table_remove_demo', 'the_drafting_table_handle_remove_demo' );

if ( ! function_exists( 'the_drafting_table_demo_removed_notice' ) ) {
	/**
	 * Show a one-time success notice when demo content is removed.
	 *
	 * @return void
	 */
	function the_drafting_table_demo_removed_notice() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag set by our own nonce-verified remove handler via wp_safe_redirect.
		if ( empty( $_GET['the_drafting_table_demo'] ) || 'removed' !== $_GET['the_drafting_table_demo'] ) {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Demo content removed.', 'the-drafting-table' ); ?></strong>
				<?php esc_html_e( 'Installer-created posts, pages, media, and terms have been cleaned up.', 'the-drafting-table' ); ?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'the_drafting_table_demo_removed_notice' );

if ( ! function_exists( 'the_drafting_table_demo_remove_failure_notice' ) ) {
	/**
	 * Show actionable errors when demo removal is incomplete.
	 *
	 * @return void
	 */
	function the_drafting_table_demo_remove_failure_notice() {
		if ( ! the_drafting_table_companion_is_theme_active() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag set by our own nonce-verified remove handler via wp_safe_redirect.
		if ( empty( $_GET['the_drafting_table_demo'] ) || 'remove_failed' !== $_GET['the_drafting_table_demo'] ) {
			return;
		}

		$issues = get_transient( 'the_drafting_table_demo_remove_issues' );
		$issues = is_array( $issues ) ? $issues : array();
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Demo content removal was incomplete.', 'the-drafting-table' ); ?></strong>
				<?php esc_html_e( 'Please resolve the issues below and run the remover again.', 'the-drafting-table' ); ?>
			</p>
			<?php if ( ! empty( $issues ) ) : ?>
				<ul style="margin-left:1.25em;list-style:disc;">
					<?php foreach ( $issues as $issue ) : ?>
						<li><?php echo esc_html( $issue ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'the_drafting_table_demo_remove_failure_notice' );

if ( ! function_exists( 'the_drafting_table_capture_demo_site_state' ) ) {
	/**
	 * Capture mutable site settings before demo install modifies them.
	 *
	 * @return array<string, int|string>
	 */
	function the_drafting_table_capture_demo_site_state() {
		return array(
			'show_on_front'  => (string) get_option( 'show_on_front', 'posts' ),
			'page_on_front'  => (int) get_option( 'page_on_front', 0 ),
			'page_for_posts' => (int) get_option( 'page_for_posts', 0 ),
			'custom_logo'    => (int) get_theme_mod( 'custom_logo', 0 ),
		);
	}
}

if ( ! function_exists( 'the_drafting_table_collect_demo_manifest' ) ) {
	/**
	 * Collect installer-managed IDs so demo content can be removed safely.
	 *
	 * @param int                      $logo_id        Imported logo attachment ID.
	 * @param array<string, int>|array $previous_state Site settings captured pre-install.
	 * @return array<string, mixed>
	 */
	function the_drafting_table_collect_demo_manifest( $logo_id = 0, $previous_state = array() ) {
		$post_ids = get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_the_drafting_table_demo_content',
				'meta_value'     => '1',
			)
		);

		$asset_ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_the_drafting_table_demo_asset',
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

		$term_ids = array();
		foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
			$tax_term_ids = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
					'meta_query' => array(
						array(
							'key'   => '_the_drafting_table_demo_content',
							'value' => '1',
						),
					),
				)
			);

			if ( ! is_wp_error( $tax_term_ids ) ) {
				$term_ids = array_merge( $term_ids, array_map( 'absint', $tax_term_ids ) );
			}
		}

		return array(
			'post_ids'       => array_values( array_unique( array_map( 'absint', $post_ids ) ) ),
			'asset_ids'      => array_values( array_unique( array_map( 'absint', $asset_ids ) ) ),
			'featured_ids'   => array_values( array_unique( array_map( 'absint', $featured_ids ) ) ),
			'term_ids'       => array_values( array_unique( array_map( 'absint', $term_ids ) ) ),
			'logo_id'        => absint( $logo_id ),
			'previous_state' => is_array( $previous_state ) ? $previous_state : array(),
		);
	}
}

if ( ! function_exists( 'the_drafting_table_restore_demo_site_state' ) ) {
	/**
	 * Restore reading settings and custom logo captured pre-install.
	 *
	 * @param array<string, int|string> $state Captured previous settings.
	 * @return void
	 */
	function the_drafting_table_restore_demo_site_state( $state ) {
		$state = is_array( $state ) ? $state : array();

		$show_on_front = ! empty( $state['show_on_front'] ) ? (string) $state['show_on_front'] : 'posts';
		update_option( 'show_on_front', in_array( $show_on_front, array( 'posts', 'page' ), true ) ? $show_on_front : 'posts' );
		update_option( 'page_on_front', ! empty( $state['page_on_front'] ) ? absint( $state['page_on_front'] ) : 0 );
		update_option( 'page_for_posts', ! empty( $state['page_for_posts'] ) ? absint( $state['page_for_posts'] ) : 0 );

		if ( isset( $state['custom_logo'] ) ) {
			set_theme_mod( 'custom_logo', absint( $state['custom_logo'] ) );
		} else {
			remove_theme_mod( 'custom_logo' );
		}
	}
}

if ( ! function_exists( 'the_drafting_table_remove_demo_content' ) ) {
	/**
	 * Removes installer-created demo content and restores prior site settings.
	 *
	 * @return true|WP_Error
	 */
	function the_drafting_table_remove_demo_content() {
		$manifest = get_option( 'the_drafting_table_demo_manifest' );
		$manifest = is_array( $manifest ) ? $manifest : array();
		$issues   = array();

		$post_ids = array_map( 'absint', (array) ( $manifest['post_ids'] ?? array() ) );
		foreach ( $post_ids as $post_id ) {
			if ( $post_id && false === wp_delete_post( $post_id, true ) ) {
				$issues[] = __( 'One or more demo posts/pages could not be deleted.', 'the-drafting-table' );
				break;
			}
		}

		$asset_ids = array_map( 'absint', (array) ( $manifest['asset_ids'] ?? array() ) );
		foreach ( $asset_ids as $asset_id ) {
			if ( $asset_id && false === wp_delete_attachment( $asset_id, true ) ) {
				$issues[] = __( 'One or more demo media assets could not be deleted.', 'the-drafting-table' );
				break;
			}
		}

		$term_ids = array_map( 'absint', (array) ( $manifest['term_ids'] ?? array() ) );
		foreach ( $term_ids as $term_id ) {
			$taxonomy = null;

			if ( term_exists( $term_id, 'category' ) ) {
				$taxonomy = 'category';
			} elseif ( term_exists( $term_id, 'post_tag' ) ) {
				$taxonomy = 'post_tag';
			}

			if ( ! $taxonomy ) {
				continue;
			}

			$deleted = wp_delete_term( $term_id, $taxonomy );
			if ( is_wp_error( $deleted ) || ! $deleted ) {
				$issues[] = __( 'One or more demo terms could not be deleted.', 'the-drafting-table' );
				break;
			}
		}

		$featured_ids = array_map( 'absint', (array) ( $manifest['featured_ids'] ?? array() ) );
		foreach ( $featured_ids as $featured_id ) {
			delete_post_meta( $featured_id, '_the_drafting_table_featured_entry' );
		}

		the_drafting_table_restore_demo_site_state( (array) ( $manifest['previous_state'] ?? array() ) );

		delete_option( 'the_drafting_table_demo_installed' );
		update_option( 'the_drafting_table_demo_pending', '1' );
		delete_option( 'the_drafting_table_demo_manifest' );
		delete_transient( 'the_drafting_table_demo_install_issues' );

		if ( ! empty( $issues ) ) {
			return new WP_Error(
				'the_drafting_table_demo_remove_failed',
				__( 'The demo remover completed with errors.', 'the-drafting-table' ),
				$issues
			);
		}

		return true;
	}
}

// -------------------------------------------------------------------------
// Content creation — called only from the install handler above.
// -------------------------------------------------------------------------

if ( ! function_exists( 'the_drafting_table_configure_reading_settings' ) ) {
	/**
	 * Configures WordPress Reading Settings after demo content is installed.
	 *
	 * Sets the front page to a static page (activating front-page.html) and
	 * sets the posts page to the Journal page (activating home.html for the
	 * blog listing). Uses installer-created page IDs when available and falls
	 * back to slug lookups for idempotent reruns.
	 *
	 * @param array<string, int> $page_ids Created page IDs keyed by slug.
	 * @return bool True when both required pages could be mapped.
	 */
	function the_drafting_table_configure_reading_settings( $page_ids = array() ) {
		// Set a static front page so front-page.html template activates.
		update_option( 'show_on_front', 'page' );

		// Point "Front page" to About if no dedicated homepage page exists.
		// The front-page.html template renders regardless of which page is
		// designated — it is used whenever show_on_front = 'page'.
		$front_page_id = ! empty( $page_ids['about'] ) ? absint( $page_ids['about'] ) : 0;
		if ( ! $front_page_id ) {
			$front_page = get_page_by_path( 'about' );
			if ( $front_page ) {
				$front_page_id = (int) $front_page->ID;
			}
		}
		if ( $front_page_id ) {
			update_option( 'page_on_front', $front_page_id );
		}

		// Point "Posts page" to Journal so home.html (Blog Home) activates.
		$posts_page_id = ! empty( $page_ids['journal'] ) ? absint( $page_ids['journal'] ) : 0;
		if ( ! $posts_page_id ) {
			$posts_page = get_page_by_path( 'journal' );
			if ( $posts_page ) {
				$posts_page_id = (int) $posts_page->ID;
			}
		}
		if ( $posts_page_id ) {
			update_option( 'page_for_posts', $posts_page_id );
		}

		return (bool) ( $front_page_id && $posts_page_id );
	}
}

if ( ! function_exists( 'the_drafting_table_install_demo_content' ) ) {
	/**
	 * Runs demo installation and returns a success payload or WP_Error.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	function the_drafting_table_install_demo_content() {
		$page_ids = the_drafting_table_create_pages();
		$post_ids = the_drafting_table_create_sample_posts();
		$issues   = array();

		foreach ( array( 'about', 'journal' ) as $required_page_slug ) {
			if ( empty( $page_ids[ $required_page_slug ] ) ) {
				/* translators: %s: required page slug. */
				$issues[] = sprintf( __( 'Could not create or find the required "%s" page.', 'the-drafting-table' ), $required_page_slug );
			}
		}

		$logo_id = the_drafting_table_install_demo_branding();
		if ( ! $logo_id ) {
			$issues[] = __( 'Could not import the bundled site logo.', 'the-drafting-table' );
		}

		$media_result = the_drafting_table_assign_demo_media( $post_ids );
		if ( ! empty( $media_result['missing_posts'] ) ) {
			$issues[] = __( 'One or more demo posts are missing, so featured images were not fully assigned.', 'the-drafting-table' );
		}
		if ( ! empty( $media_result['failed_assets'] ) ) {
			$issues[] = __( 'One or more bundled images failed to import into the Media Library.', 'the-drafting-table' );
		}

		if ( ! the_drafting_table_mark_demo_featured_post( $post_ids ) ) {
			$issues[] = __( 'Could not mark the featured demo journal entry.', 'the-drafting-table' );
		}

		if ( ! the_drafting_table_configure_reading_settings( $page_ids ) ) {
			$issues[] = __( 'Could not set the front page and posts page reading settings.', 'the-drafting-table' );
		}

		if ( ! empty( $issues ) ) {
			return new WP_Error(
				'the_drafting_table_demo_install_failed',
				__( 'The demo installer completed with errors.', 'the-drafting-table' ),
				$issues
			);
		}

		return array(
			'page_ids' => $page_ids,
			'post_ids' => $post_ids,
			'logo_id'  => $logo_id,
		);
	}
}

if ( ! function_exists( 'the_drafting_table_get_demo_asset_definitions' ) ) {
	/**
	 * Returns the bundled placeholder assets used by the demo installer.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function the_drafting_table_get_demo_asset_definitions() {
		return array(
			'fallingwater-logo'        => array(
				'file'   => 'assets/images/fallingwater-logo.svg',
				'title'  => 'The Drafting Table Logo',
				'alt'    => 'Fallingwater-inspired site logo',
				'mime'   => 'image/svg+xml',
				'width'  => 500,
				'height' => 500,
			),
			'board-formed-concrete'    => array(
				'file'   => 'assets/images/demo-board-formed-concrete.svg',
				'title'  => 'Board-Formed Concrete Study',
				'alt'    => 'Board-formed concrete elevation study',
				'mime'   => 'image/svg+xml',
				'width'  => 1600,
				'height' => 1200,
			),
			'ridgeline-dwelling'       => array(
				'file'   => 'assets/images/demo-ridgeline-dwelling.svg',
				'title'  => 'Ridgeline Dwelling Study',
				'alt'    => 'Hillside residence study at sunrise',
				'mime'   => 'image/svg+xml',
				'width'  => 1600,
				'height' => 1200,
			),
			'copper-roof-study'        => array(
				'file'   => 'assets/images/demo-copper-roof-study.svg',
				'title'  => 'Copper Roof Study',
				'alt'    => 'Standing-seam copper roof detail drawing',
				'mime'   => 'image/svg+xml',
				'width'  => 1600,
				'height' => 1200,
			),
			'drawing-hand-study'       => array(
				'file'   => 'assets/images/demo-drawing-hand-study.svg',
				'title'  => 'Drawing Hand Study',
				'alt'    => 'Sketchbook page with a drafting hand and pencil',
				'mime'   => 'image/svg+xml',
				'width'  => 1600,
				'height' => 1200,
			),
			'timber-joinery-study'     => array(
				'file'   => 'assets/images/demo-timber-joinery-study.svg',
				'title'  => 'Timber Joinery Study',
				'alt'    => 'Joinery diagram showing interlocking timber members',
				'mime'   => 'image/svg+xml',
				'width'  => 1600,
				'height' => 1200,
			),
			'glass-transparency-study' => array(
				'file'   => 'assets/images/demo-glass-transparency-study.svg',
				'title'  => 'Glass Transparency Study',
				'alt'    => 'Perspective sketch of a transparent glass wall facing landscape',
				'mime'   => 'image/svg+xml',
				'width'  => 1600,
				'height' => 1200,
			),
		);
	}
}

if ( ! function_exists( 'the_drafting_table_import_demo_asset' ) ) {
	/**
	 * Imports a bundled asset into the Media Library.
	 *
	 * @param string $asset_key Asset key from the demo asset definitions.
	 * @return int Attachment ID, or 0 on failure.
	 */
	function the_drafting_table_import_demo_asset( $asset_key ) {
		$existing_ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_the_drafting_table_demo_asset',
				'meta_value'     => $asset_key,
			)
		);

		if ( ! empty( $existing_ids ) ) {
			return (int) $existing_ids[0];
		}

		$assets = the_drafting_table_get_demo_asset_definitions();

		if ( empty( $assets[ $asset_key ] ) ) {
			return 0;
		}

		$asset       = $assets[ $asset_key ];
		$source_path = get_theme_file_path( $asset['file'] );

		if ( ! file_exists( $source_path ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a trusted local SVG bundled with the theme package.
		$contents = file_get_contents( $source_path );

		if ( false === $contents ) {
			return 0;
		}

		$upload = wp_upload_bits( wp_basename( $asset['file'] ), null, $contents );

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => sanitize_text_field( $asset['title'] ),
				'post_status'    => 'inherit',
				'post_mime_type' => sanitize_mime_type( $asset['mime'] ),
			),
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}

		wp_update_attachment_metadata(
			$attachment_id,
			array(
				'width'  => absint( $asset['width'] ),
				'height' => absint( $asset['height'] ),
				'file'   => _wp_relative_upload_path( $upload['file'] ),
			)
		);

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $asset['alt'] ) );
		update_post_meta( $attachment_id, '_the_drafting_table_demo_asset', sanitize_key( $asset_key ) );

		return (int) $attachment_id;
	}
}

if ( ! function_exists( 'the_drafting_table_install_demo_branding' ) ) {
	/**
	 * Applies the bundled site logo for demo installs.
	 *
	 * @return int Imported logo attachment ID, or 0 on failure.
	 */
	function the_drafting_table_install_demo_branding() {
		$logo_id = the_drafting_table_import_demo_asset( 'fallingwater-logo' );

		if ( $logo_id ) {
			set_theme_mod( 'custom_logo', $logo_id );
		}

		return $logo_id;
	}
}

if ( ! function_exists( 'the_drafting_table_assign_demo_media' ) ) {
	/**
	 * Assigns placeholder artwork to the sample journal posts.
	 *
	 * @param array<string, int> $post_ids Sample post IDs keyed by slug.
	 * @return array<string, array<int, string>|int>
	 */
	function the_drafting_table_assign_demo_media( $post_ids ) {
		$post_assets = array(
			'character-of-board-formed-concrete'   => 'board-formed-concrete',
			'morning-light-ridgeline-site'         => 'ridgeline-dwelling',
			'cantilever-as-architectural-gesture'  => 'ridgeline-dwelling',
			'selecting-copper-assembly-hall-roof'  => 'copper-roof-study',
			'drawing-by-hand-digital-age'          => 'drawing-hand-study',
			'timber-joinery-japanese-tradition'    => 'timber-joinery-study',
			'limestone-walls-memory-of-the-sea'    => 'board-formed-concrete',
			'glass-transparency-dissolution-walls' => 'glass-transparency-study',
		);

		$attachment_ids = array();
		$result         = array(
			'assigned'      => 0,
			'missing_posts' => array(),
			'failed_assets' => array(),
		);

		foreach ( $post_assets as $slug => $asset_key ) {
			if ( empty( $post_ids[ $slug ] ) ) {
				$result['missing_posts'][] = $slug;
				continue;
			}

			if ( empty( $attachment_ids[ $asset_key ] ) ) {
				$attachment_ids[ $asset_key ] = the_drafting_table_import_demo_asset( $asset_key );
			}

			if ( empty( $attachment_ids[ $asset_key ] ) ) {
				$result['failed_assets'][] = $asset_key;
				continue;
			}

			if ( false === set_post_thumbnail( $post_ids[ $slug ], $attachment_ids[ $asset_key ] ) ) {
				$result['failed_assets'][] = $asset_key;
				continue;
			}

			++$result['assigned'];
		}

		$result['missing_posts'] = array_values( array_unique( $result['missing_posts'] ) );
		$result['failed_assets'] = array_values( array_unique( $result['failed_assets'] ) );

		return $result;
	}
}

if ( ! function_exists( 'the_drafting_table_get_demo_featured_post_id' ) ) {
	/**
	 * Returns the configured featured demo post ID, if one is marked.
	 *
	 * @return int
	 */
	function the_drafting_table_get_demo_featured_post_id() {
		$featured_ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_key'       => '_the_drafting_table_featured_entry',
				'meta_value'     => '1',
			)
		);

		if ( empty( $featured_ids ) ) {
			return 0;
		}

		return (int) $featured_ids[0];
	}
}

if ( ! function_exists( 'the_drafting_table_mark_demo_featured_post' ) ) {
	/**
	 * Marks the lead demo journal entry for the front-page hero query.
	 *
	 * @param array<string, int> $post_ids Sample post IDs keyed by slug.
	 * @return bool
	 */
	function the_drafting_table_mark_demo_featured_post( $post_ids ) {
		$featured_slug = 'glass-transparency-dissolution-walls';

		if ( empty( $post_ids[ $featured_slug ] ) ) {
			return false;
		}

		$featured_post_id = (int) $post_ids[ $featured_slug ];
		$existing_ids     = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_the_drafting_table_featured_entry',
				'meta_value'     => '1',
			)
		);

		foreach ( $existing_ids as $existing_id ) {
			if ( $featured_post_id !== (int) $existing_id ) {
				delete_post_meta( (int) $existing_id, '_the_drafting_table_featured_entry' );
			}
		}

		return false !== update_post_meta( $featured_post_id, '_the_drafting_table_featured_entry', '1' );
	}
}

if ( ! function_exists( 'the_drafting_table_mark_demo_sticky_post' ) ) {
	/**
	 * Back-compat wrapper kept for older scripts/tests.
	 *
	 * @param array<string, int> $post_ids Sample post IDs keyed by slug.
	 * @return bool
	 */
	function the_drafting_table_mark_demo_sticky_post( $post_ids ) {
		return the_drafting_table_mark_demo_featured_post( $post_ids );
	}
}

if ( ! function_exists( 'the_drafting_table_create_pages' ) ) {
	/**
	 * Creates the About, Projects, Journal, and Principles starter pages.
	 *
	 * Skips any page whose slug already exists to prevent duplicates.
	 *
	 * @return array<string, int>
	 */
	function the_drafting_table_create_pages() {
		$page_ids = array();

		$pages = array(
			array(
				'title'    => 'About',
				'slug'     => 'about',
				'template' => 'page-about',
				'content'  => '<!-- wp:paragraph {"textColor":"ink-light","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.75"}}} -->' . "\n" .
								'<p class="has-ink-light-color has-text-color has-courier-prime-font-family" style="font-size:0.9375rem;line-height:1.75">The Drafting Table was founded in a converted carriage house on the outskirts of Madison, Wisconsin. From the beginning, our work has been guided by a single conviction: that architecture should serve the lives it shelters and honor the land upon which it stands.</p>' . "\n" .
								'<!-- /wp:paragraph -->',
			),
			array(
				'title'    => 'Projects',
				'slug'     => 'projects',
				'template' => 'page-projects',
				'content'  => '<!-- wp:paragraph {"textColor":"ink-light","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.75"}}} -->' . "\n" .
								'<p class="has-ink-light-color has-text-color has-courier-prime-font-family" style="font-size:0.9375rem;line-height:1.75">Each project in our archive represents a unique conversation between client, site, and the possibilities of material and structure.</p>' . "\n" .
								'<!-- /wp:paragraph -->',
			),
			array(
				'title'    => 'Journal',
				'slug'     => 'journal',
				'template' => 'page-journal',
				'content'  => '<!-- wp:paragraph {"textColor":"ink-light","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.75"}}} -->' . "\n" .
								'<p class="has-ink-light-color has-text-color has-courier-prime-font-family" style="font-size:0.9375rem;line-height:1.75">The journal is where we think in public. Here you will find reflections on the design process, observations from site visits, and notes on materials and craft.</p>' . "\n" .
								'<!-- /wp:paragraph -->',
			),
			array(
				'title'    => 'Principles',
				'slug'     => 'principles',
				'template' => 'page-principles',
				'content'  => '<!-- wp:paragraph {"textColor":"ink-light","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.75"}}} -->' . "\n" .
								'<p class="has-ink-light-color has-text-color has-courier-prime-font-family" style="font-size:0.9375rem;line-height:1.75">These are the convictions that guide our practice — not rules imposed from without, but principles discovered through the work itself. They are held lightly, revised willingly, and applied with care.</p>' . "\n" .
								'<!-- /wp:paragraph -->',
			),
		);

		foreach ( $pages as $page_data ) {
			$existing = get_page_by_path( $page_data['slug'] );

			if ( $existing ) {
				$page_ids[ $page_data['slug'] ] = (int) $existing->ID;
				continue;
			}

				$page_id = wp_insert_post(
					array(
						'post_title'   => sanitize_text_field( $page_data['title'] ),
						'post_name'    => sanitize_title( $page_data['slug'] ),
						'post_content' => wp_kses_post( $page_data['content'] ),
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'meta_input'   => array(
							'_wp_page_template' => sanitize_text_field( $page_data['template'] ),
							'_the_drafting_table_demo_content' => '1',
						),
					)
				);

			if ( ! is_wp_error( $page_id ) ) {
				update_post_meta( $page_id, '_wp_page_template', sanitize_text_field( $page_data['template'] ) );
				update_post_meta( $page_id, '_the_drafting_table_demo_content', '1' );
				$page_ids[ $page_data['slug'] ] = (int) $page_id;
			}
		}

		return $page_ids;
	}
}

if ( ! function_exists( 'the_drafting_table_create_sample_posts' ) ) {
	/**
	 * Creates sample journal posts with categories and tags.
	 *
	 * Skips any post whose slug already exists to prevent duplicates.
	 *
	 * @return array<string, int>
	 */
	function the_drafting_table_create_sample_posts() {
		$post_ids = array();

		$categories = array(
			'design-notes'     => 'Design Notes',
			'material-studies' => 'Material Studies',
			'site-visits'      => 'Site Visits',
			'studio-life'      => 'Studio Life',
			'theory'           => 'Theory',
		);

		$cat_ids = array();
		foreach ( $categories as $slug => $name ) {
				$existing = get_term_by( 'slug', $slug, 'category' );
			if ( $existing ) {
				$cat_ids[ $slug ] = $existing->term_id;
			} else {
				$term = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $term ) ) {
					$cat_ids[ $slug ] = $term['term_id'];
					update_term_meta( $term['term_id'], '_the_drafting_table_demo_content', '1' );
				}
			}
		}

		$tags = array(
			'concrete'       => 'Concrete',
			'timber'         => 'Timber',
			'limestone'      => 'Limestone',
			'prairie-style'  => 'Prairie Style',
			'organic'        => 'Organic Architecture',
			'cantilever'     => 'Cantilever',
			'natural-light'  => 'Natural Light',
			'landscape'      => 'Landscape',
			'sustainability' => 'Sustainability',
			'craft'          => 'Craft',
			'hand-drawing'   => 'Hand Drawing',
			'detailing'      => 'Detailing',
			'formwork'       => 'Formwork',
			'copper'         => 'Copper',
			'glass'          => 'Glass',
		);

		$tag_ids = array();
		foreach ( $tags as $slug => $name ) {
				$existing = get_term_by( 'slug', $slug, 'post_tag' );
			if ( $existing ) {
				$tag_ids[ $slug ] = $existing->term_id;
			} else {
				$term = wp_insert_term( $name, 'post_tag', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $term ) ) {
					$tag_ids[ $slug ] = $term['term_id'];
					update_term_meta( $term['term_id'], '_the_drafting_table_demo_content', '1' );
				}
			}
		}

		$posts = the_drafting_table_get_sample_posts_data();

		foreach ( $posts as $post_data ) {
			$existing = get_page_by_path( $post_data['slug'], OBJECT, 'post' );
			if ( $existing ) {
				$post_ids[ $post_data['slug'] ] = (int) $existing->ID;
				continue;
			}

			$post_cats = array();
			foreach ( $post_data['categories'] as $cat_slug ) {
				if ( isset( $cat_ids[ $cat_slug ] ) ) {
					$post_cats[] = $cat_ids[ $cat_slug ];
				}
			}

			$post_tags = array();
			foreach ( $post_data['tags'] as $tag_slug ) {
				if ( isset( $tag_ids[ $tag_slug ] ) ) {
					$post_tags[] = $tag_ids[ $tag_slug ];
				}
			}

				$post_id = wp_insert_post(
					array(
						'post_title'    => sanitize_text_field( $post_data['title'] ),
						'post_name'     => sanitize_title( $post_data['slug'] ),
						'post_content'  => wp_kses_post( $post_data['content'] ),
						'post_excerpt'  => sanitize_text_field( $post_data['excerpt'] ),
						'post_status'   => 'publish',
						'post_type'     => 'post',
						'post_date'     => sanitize_text_field( $post_data['date'] ),
						'post_category' => array_map( 'absint', $post_cats ),
						'meta_input'    => array(
							'_the_drafting_table_demo_content' => '1',
						),
					)
				);

			if ( ! is_wp_error( $post_id ) && ! empty( $post_tags ) ) {
				wp_set_post_terms( $post_id, $post_tags, 'post_tag' );
			}

			if ( ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_the_drafting_table_demo_content', '1' );
				$post_ids[ $post_data['slug'] ] = (int) $post_id;
			}
		}

		return $post_ids;
	}
}

if ( ! function_exists( 'the_drafting_table_get_sample_posts_data' ) ) {
	/**
	 * Returns the sample posts data array.
	 *
	 * @return array
	 */
	function the_drafting_table_get_sample_posts_data() {
		$p_open  = '<!-- wp:paragraph -->' . "\n" . '<p>';
		$p_close = '</p>' . "\n" . '<!-- /wp:paragraph -->';

		$h2_open  = '<!-- wp:heading {"level":2} -->' . "\n" . '<h2 class="wp-block-heading">';
		$h2_close = '</h2>' . "\n" . '<!-- /wp:heading -->';

		$q_open  = '<!-- wp:quote {"style":{"spacing":{"padding":{"left":"2rem"}}}} -->' . "\n" . '<blockquote class="wp-block-quote" style="padding-left:2rem"><!-- wp:paragraph -->' . "\n" . '<p>';
		$q_mid   = '</p>' . "\n" . '<!-- /wp:paragraph --><cite>';
		$q_close = '</cite></blockquote>' . "\n" . '<!-- /wp:quote -->';

		return array(
			array(
				'title'      => 'On the Character of Board-Formed Concrete',
				'slug'       => 'character-of-board-formed-concrete',
				'date'       => '2025-01-15 09:30:00',
				'categories' => array( 'material-studies' ),
				'tags'       => array( 'concrete', 'formwork', 'craft', 'detailing' ),
				'content'    => $p_open . 'There is a particular quality to concrete that has been cast against rough-sawn boards. The grain of the wood transfers to the surface of the wall, leaving a ghost image of the formwork in the hardened material.' . $p_close . "\n\n" .
					$p_open . 'We have been experimenting with different board species for our formwork. Douglas fir leaves a bold, pronounced grain. Cedar produces something softer, more subtle.' . $p_close . "\n\n" .
					$h2_open . 'The Sand Aggregate Question' . $h2_close . "\n\n" .
					$p_open . 'For the Meadow House, we sourced sand from a local glacial deposit. The aggregate carries a warm, amber undertone that gives the finished concrete the color of the surrounding sandstone bluffs.' . $p_close . "\n\n" .
					$q_open . 'The surface of a wall is its face. It should tell you honestly what lies behind it and how it came to be.' . $q_mid . 'Studio notes, January 2025' . $q_close,
				'excerpt'    => 'There is a particular quality to concrete that has been cast against rough-sawn boards. The grain of the wood transfers to the surface of the wall.',
			),
			array(
				'title'      => 'Morning Light at the Ridgeline Site',
				'slug'       => 'morning-light-ridgeline-site',
				'date'       => '2025-02-03 07:15:00',
				'categories' => array( 'site-visits' ),
				'tags'       => array( 'natural-light', 'landscape', 'prairie-style', 'organic' ),
				'content'    => $p_open . 'Arrived at the Ridgeline site before dawn this morning. The purpose of these early visits is to understand how the land wakes up: where the first light falls, how the shadows retreat across the hillside.' . $p_close . "\n\n" .
					$p_open . 'At 6:47 am, the sun crested the eastern tree line and sent a long gold wash across the meadow. The proposed living room faces this direction.' . $p_close . "\n\n" .
					$h2_open . 'Wind and the Oak Grove' . $h2_close . "\n\n" .
					$p_open . 'The prevailing wind comes from the northwest. The oak grove on the western slope acts as a natural windbreak, but there is a gap between the two largest bur oaks where the breeze funnels through with surprising force.' . $p_close,
				'excerpt'    => 'Arrived at the Ridgeline site before dawn this morning. The purpose of these early visits is to understand how the land wakes up.',
			),
			array(
				'title'      => 'The Cantilever as Architectural Gesture',
				'slug'       => 'cantilever-as-architectural-gesture',
				'date'       => '2025-02-22 14:00:00',
				'categories' => array( 'theory', 'design-notes' ),
				'tags'       => array( 'cantilever', 'prairie-style', 'organic', 'craft' ),
				'content'    => $p_open . 'The cantilever is more than a structural technique. It is a declaration of intent. When a roof extends beyond its supporting wall, it says to the landscape: I am reaching toward you.' . $p_close . "\n\n" .
					$h2_open . 'Structural Honesty' . $h2_close . "\n\n" .
					$p_open . 'A cantilever should express its structural logic. The visitor should be able to read the forces at work: the compression in the back span, the tension in the top reinforcement.' . $p_close . "\n\n" .
					$q_open . 'The cantilever is the architecture of generosity: the building gives more shelter than it takes ground.' . $q_mid . 'From a lecture, February 2025' . $q_close,
				'excerpt'    => 'The cantilever is more than a structural technique. It is a declaration of intent.',
			),
			array(
				'title'      => 'Selecting Copper for the Assembly Hall Roof',
				'slug'       => 'selecting-copper-assembly-hall-roof',
				'date'       => '2025-03-10 11:00:00',
				'categories' => array( 'material-studies', 'design-notes' ),
				'tags'       => array( 'copper', 'craft', 'detailing', 'sustainability' ),
				'content'    => $p_open . 'We have been deliberating on the roof material for the Lakeside Assembly Hall for several weeks now. The shortlist came down to three options: standing-seam copper, weathering steel, and zinc.' . $p_close . "\n\n" .
					$h2_open . 'The Lake Context' . $h2_close . "\n\n" .
					$p_open . 'The Assembly Hall sits on the shore of Lake Mendota. Against the backdrop of mature oaks and the grey-blue of the lake, a verdigris roof will appear almost natural.' . $p_close,
				'excerpt'    => 'We have been deliberating on the roof material for the Lakeside Assembly Hall. Today we committed to copper.',
			),
			array(
				'title'      => 'Drawing by Hand in a Digital Age',
				'slug'       => 'drawing-by-hand-digital-age',
				'date'       => '2025-03-28 16:30:00',
				'categories' => array( 'studio-life', 'theory' ),
				'tags'       => array( 'hand-drawing', 'craft', 'organic' ),
				'content'    => $p_open . 'A question we are asked frequently: why do you still draw by hand? The software exists. The efficiency gains are documented. All of this is true, and yet we persist with graphite and vellum.' . $p_close . "\n\n" .
					$h2_open . 'The Productive Imprecision' . $h2_close . "\n\n" .
					$p_open . 'A hand drawing is productively imprecise. It suggests rather than specifies. This ambiguity is valuable in the early stages of design because it leaves room for discovery.' . $p_close . "\n\n" .
					$q_open . 'The pencil is an extension of the thinking hand. It does not merely record decisions; it participates in making them.' . $q_mid . 'From the drafting table, March 2025' . $q_close,
				'excerpt'    => 'A question we are asked frequently: why do you still draw by hand? The reason is not nostalgia.',
			),
			array(
				'title'      => 'Timber Joinery and the Japanese Tradition',
				'slug'       => 'timber-joinery-japanese-tradition',
				'date'       => '2025-04-12 10:00:00',
				'categories' => array( 'material-studies', 'theory' ),
				'tags'       => array( 'timber', 'craft', 'detailing', 'sustainability' ),
				'content'    => $p_open . 'Marcus returned from his research trip to Kyoto last week, and the studio has been in a productive ferment ever since. He brought back detailed sketches of traditional Japanese timber joints.' . $p_close . "\n\n" .
					$h2_open . 'Adapting the Tradition' . $h2_close . "\n\n" .
					$p_open . 'We are not proposing to replicate Japanese joinery wholesale. The structural demands of our buildings differ from the Japanese context. But the principle is transferable: that wood can be shaped to hold itself together.' . $p_close,
				'excerpt'    => 'Marcus returned from his research trip to Kyoto, and the studio has been in a productive ferment.',
			),
			array(
				'title'      => 'Limestone Walls and the Memory of the Sea',
				'slug'       => 'limestone-walls-memory-of-the-sea',
				'date'       => '2025-04-30 08:45:00',
				'categories' => array( 'material-studies', 'site-visits' ),
				'tags'       => array( 'limestone', 'landscape', 'organic', 'craft' ),
				'content'    => $p_open . 'Visited the Lannon quarry this morning to select stone for the Ridgeline Dwelling foundation walls. The quarry cuts through Silurian dolomite, deposited four hundred million years ago.' . $p_close . "\n\n" .
					$h2_open . 'Coursing and Character' . $h2_close . "\n\n" .
					$p_open . 'We will use random-coursed ashlar, which means the stone will be cut to varying heights and laid in irregular horizontal courses.' . $p_close,
				'excerpt'    => 'Visited the Lannon quarry this morning to select stone for the Ridgeline Dwelling.',
			),
			array(
				'title'      => 'Glass, Transparency, and the Dissolution of Walls',
				'slug'       => 'glass-transparency-dissolution-walls',
				'date'       => '2025-05-18 13:00:00',
				'categories' => array( 'design-notes', 'theory' ),
				'tags'       => array( 'glass', 'natural-light', 'cantilever', 'prairie-style' ),
				'content'    => $p_open . 'The great innovation of modern architecture was the liberation of the wall from its structural obligations. Once the load was carried by a frame, the wall could disappear entirely.' . $p_close . "\n\n" .
					$h2_open . 'The Frame Within the Frame' . $h2_close . "\n\n" .
					$p_open . 'Every window is a composition. Its proportions, its mullion pattern, its relationship to the wall in which it sits, all shape what the inhabitant sees.' . $p_close,
				'excerpt'    => 'The great innovation of modern architecture was the liberation of the wall from its structural obligations.',
			),
		);
	}
}
