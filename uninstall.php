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

foreach ( array( 'cf7etm_settings', 'cf7etm_branding', 'cf7etm_assignments', 'cf7etm_log', 'cf7etm_seeded' ) as $cf7etm_option ) {
	delete_option( $cf7etm_option );
}
