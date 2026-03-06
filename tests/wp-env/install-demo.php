<?php
/**
 * Bootstraps a clean demo install inside wp-env.
 *
 * @package The_Drafting_Table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'the_drafting_table_install_demo_content' ) ) {
	fwrite( STDERR, "The Drafting Table installer functions are not loaded.\n" );
	exit( 1 );
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
update_option( 'permalink_structure', '/%postname%/' );

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
