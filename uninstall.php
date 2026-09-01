<?php
/**
 * Uninstall routine.
 *
 * Templates are only destroyed when the administrator explicitly opted in
 * under Settings → Advanced. The default is to leave everything alone.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$cf7etm_settings = (array) get_option( 'cf7etm_settings', array() );

if ( empty( $cf7etm_settings['delete_on_uninstall'] ) ) {
	return;
}

$cf7etm_templates = get_posts(
	array(
		'post_type'      => 'cf7etm_template',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $cf7etm_templates as $cf7etm_id ) {
	wp_delete_post( $cf7etm_id, true );
}

// The submissions log lives in its own table.
global $wpdb;

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- own table, uninstall only.
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7etm_submissions' );

// Uploaded files kept alongside the submissions.
$cf7etm_uploads = wp_upload_dir();
$cf7etm_dir     = untrailingslashit( $cf7etm_uploads['basedir'] ) . '/cf7etm-submissions';

if ( is_dir( $cf7etm_dir ) ) {
	$cf7etm_items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $cf7etm_dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $cf7etm_items as $cf7etm_item ) {
		if ( $cf7etm_item->isDir() ) {
			rmdir( $cf7etm_item->getPathname() );
		} else {
			wp_delete_file( $cf7etm_item->getPathname() );
		}
	}

	rmdir( $cf7etm_dir );
}

foreach ( array( 'cf7etm_settings', 'cf7etm_branding', 'cf7etm_assignments', 'cf7etm_log', 'cf7etm_seeded', 'cf7etm_db_version' ) as $cf7etm_option ) {
	delete_option( $cf7etm_option );
}

// Per-user screen option for the templates list.
delete_metadata( 'user', 0, 'cf7etm_per_page', '', true );
delete_metadata( 'user', 0, 'cf7etm_entries_per_page', '', true );
