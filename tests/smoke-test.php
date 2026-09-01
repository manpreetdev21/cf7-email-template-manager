<?php
/**
 * End-to-end smoke test.
 *
 * Runs against the real WordPress install and the real Contact Form 7, because
 * the whole plugin hangs off CF7's own APIs — mocking them would only test the
 * mock. Creates a throwaway form and template, then cleans both up.
 *
 * Usage:  php tests/smoke-test.php
 *
 * @package CF7_Email_Template_Manager
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Run this from the command line.' );
}

$root = dirname( __DIR__, 4 );

// Needed so the "stand down on CF7 admin screens" guard can be exercised.
define( 'WP_ADMIN', true );

require_once $root . '/wp-load.php';

$failures = 0;
$checks   = 0;

/**
 * Asserts a condition and reports it.
 *
 * @param string $label     What is being checked.
 * @param bool   $condition Result.
 * @param string $detail    Extra context shown on failure.
 */
function cf7etm_check( $label, $condition, $detail = '' ) {
	global $failures, $checks;

	++$checks;

	if ( $condition ) {
		echo "  PASS  $label\n";
		return;
	}

	++$failures;
	echo "  FAIL  $label" . ( $detail ? "\n        $detail" : '' ) . "\n";
}

echo "\nCF7 Email Template Manager — smoke test\n";
echo str_repeat( '-', 60 ) . "\n";

cf7etm_check( 'Contact Form 7 is active', CF7ETM_Plugin::cf7_supported() );
cf7etm_check( 'Template post type registered', post_type_exists( 'cf7etm_template' ) );

/* -------------------------------------------------------------------------
 * Fixtures
 * ---------------------------------------------------------------------- */

$form = WPCF7_ContactForm::get_template( array( 'title' => 'CF7ETM Smoke Form' ) );

$form->set_properties(
	array(
		'form' => "[text* your-name]\n[email* your-email]\n[tel your-phone]\n[textarea your-message]\n[file* your-resume]\n[file docs]",
		'mail' => array(
			'subject'            => 'ORIGINAL SUBJECT',
			'sender'             => 'Original <original@example.com>',
			'recipient'          => 'original-recipient@example.com',
			'body'               => 'ORIGINAL BODY',
			'additional_headers' => 'Reply-To: [your-email]',
			'attachments'        => '',
			'use_html'           => 0,
			'exclude_blank'      => 0,
		),
	)
);

$form_id = $form->save();

cf7etm_check( 'Test contact form created', $form_id > 0 );

$template_id = CF7ETM_Template_Post_Type::save(
	array(
		'name'          => 'CF7ETM Smoke Template',
		'type'          => 'html',
		'status'        => 'publish',
		'subject'       => 'New enquiry from [your-name]',
		'preview_text'  => 'Someone contacted you',
		'body'          => '<!doctype html><html><body><table><tr><td>Hello [your-name] at [cf7etm_company_name], reply to [your-email]. [company]</td></tr></table></body></html>',
		'headers'       => 'Reply-To: [your-email]',
		'attachments'   => "[your-resume]
../../wp-config.php
[9bad]
[docs]
[your-resume]",
		'exclude_blank' => 1,
	)
);

cf7etm_check( 'Template created', ! is_wp_error( $template_id ), is_wp_error( $template_id ) ? $template_id->get_error_message() : '' );

$stored = CF7ETM_Template_Post_Type::get( $template_id );

/* -------------------------------------------------------------------------
 * Sanitising
 * ---------------------------------------------------------------------- */

cf7etm_check( 'Doctype survives sanitising', str_starts_with( strtolower( ltrim( $stored['body'] ) ), '<!doctype' ), $stored['body'] );
cf7etm_check( 'Email-safe HTML survives sanitising', str_contains( $stored['body'], '<table>' ) );
cf7etm_check( 'CF7 tags are preserved verbatim', str_contains( $stored['body'], '[your-name]' ) );

cf7etm_check(
	'Attachment spec keeps file tags and drops the rest',
	"[your-resume]\n[docs]" === $stored['attachments'],
	str_replace( "\n", ' | ', $stored['attachments'] )
);

cf7etm_check(
	'Attachment spec never accepts a bare file path',
	! str_contains( $stored['attachments'], 'wp-config' ),
	$stored['attachments']
);

cf7etm_check(
	'File flag derived from the attachment spec',
	1 === (int) get_post_meta( $template_id, '_cf7etm_has_files', true )
		&& CF7ETM_Template_Post_Type::count_with_files() > 0
);

$xss = CF7ETM_Template_Post_Type::sanitize_body( '<p onclick="evil()">hi</p><script>alert(1)</script><iframe src="x"></iframe>', 'html' );

cf7etm_check( 'Scripts stripped from bodies', ! str_contains( $xss, '<script' ) && ! str_contains( $xss, '<iframe' ) && ! str_contains( $xss, 'onclick' ), $xss );

// The visual builder stores what it needs in data attributes, so kses has to
// let them through or every saved layout comes back unreadable.
$blocks = CF7ETM_Template_Post_Type::sanitize_body(
	'<table><tr><td data-cf7etm-block="text" data-align="center" style="padding:8px;">hi</td></tr></table>',
	'html'
);

cf7etm_check(
	'Builder block markers survive sanitising',
	str_contains( $blocks, 'data-cf7etm-block="text"' ) && str_contains( $blocks, 'data-align="center"' ),
	$blocks
);

/* -------------------------------------------------------------------------
 * Tag detection and validation
 * ---------------------------------------------------------------------- */

$mail_tags = CF7ETM_CF7_Bridge::mail_tags( $form_id );

cf7etm_check(
	'CF7 mail-tags detected',
	array_diff( array( 'your-name', 'your-email', 'your-phone', 'your-message' ), $mail_tags ) === array(),
	implode( ', ', $mail_tags )
);

$file_types = CF7ETM_CF7_Bridge::file_tag_types();

cf7etm_check(
	'File tag types come from CF7 own feature flag',
	array_diff( array( 'file', 'file*' ), $file_types ) === array(),
	implode( ', ', $file_types )
);

$file_fields = CF7ETM_CF7_Bridge::file_fields( $form_id );

cf7etm_check(
	'File upload fields detected',
	array_diff( array( 'your-resume', 'docs' ), $file_fields ) === array() && 2 === count( $file_fields ),
	implode( ', ', $file_fields )
);

cf7etm_check(
	'Ordinary fields are not flagged as uploads',
	! array_intersect( array( 'your-name', 'your-email', 'your-message' ), $file_fields )
);

$bad_attach = CF7ETM_CF7_Bridge::invalid_attachments( "[your-resume]\n[your-message]", $form_id );

cf7etm_check(
	'Attachment naming a non-file field is flagged',
	array( 'your-message' ) === $bad_attach,
	implode( ', ', $bad_attach )
);

$unknown = CF7ETM_CF7_Bridge::unknown_tags( $stored['body'], $form_id );

cf7etm_check( 'Unknown tag [company] flagged', in_array( 'company', $unknown, true ), implode( ', ', $unknown ) );
cf7etm_check( 'Known and branding tags not flagged', ! array_intersect( array( 'your-name', 'your-email', 'cf7etm_company_name' ), $unknown ) );

$unused = CF7ETM_CF7_Bridge::unused_tags( $stored['body'], $form_id );

cf7etm_check( 'Unused form tags reported', in_array( 'your-message', $unused, true ) && ! in_array( 'your-name', $unused, true ), implode( ', ', $unused ) );

cf7etm_check( 'Friendly labels derived', 'Email' === CF7ETM_CF7_Bridge::friendly_label( 'your-email' ) );

/* -------------------------------------------------------------------------
 * Branding and sample rendering
 * ---------------------------------------------------------------------- */

$branded = CF7ETM_Branding::replace( '[cf7etm_company_name] / [cf7etm_year]', true );

cf7etm_check( 'Branding tags resolved', ! str_contains( $branded, '[cf7etm_' ), $branded );

$sample = CF7ETM_Renderer::sample_render_data( $stored, $form_id );

cf7etm_check( 'Sample render replaces form tags', ! str_contains( $sample['body'], '[your-name]' ) && str_contains( $sample['body'], 'John Smith' ) );
cf7etm_check( 'Sample render types email fields', str_contains( CF7ETM_Renderer::sample_values( $form_id )['your-email'], '@' ) );
cf7etm_check( 'Preheader injected into HTML body', str_contains( $sample['body'], 'Someone contacted you' ) );

/* -------------------------------------------------------------------------
 * Assignment and runtime injection — the core guarantee
 * ---------------------------------------------------------------------- */

$original_meta = get_post_meta( $form_id, '_mail', true );

cf7etm_check( 'Assignment stored', true === CF7ETM_CF7_Bridge::assign( $form_id, 'admin', $template_id ) );

$live = WPCF7_ContactForm::get_instance( $form_id );
$mail = $live->prop( 'mail' );

cf7etm_check( 'Template body injected into CF7', str_contains( $mail['body'], 'Hello [your-name]' ), substr( $mail['body'], 0, 80 ) );
cf7etm_check( 'Template subject injected', 'New enquiry from [your-name]' === $mail['subject'], $mail['subject'] );
cf7etm_check( 'HTML mode enabled for HTML templates', 1 === (int) $mail['use_html'] );
cf7etm_check( 'Exclude-blank carried across', 1 === (int) $mail['exclude_blank'] );
cf7etm_check( 'Recipient falls back to the form when the template is silent', 'original-recipient@example.com' === $mail['recipient'], $mail['recipient'] );
cf7etm_check( 'Template headers applied', str_contains( $mail['additional_headers'], 'Reply-To' ) );

cf7etm_check(
	'Attachment spec reaches CF7 mail property',
	str_contains( $mail['attachments'], '[your-resume]' )
		&& str_contains( $mail['attachments'], '[docs]' ),
	$mail['attachments']
);

/* An empty spec must leave whatever Contact Form 7 already had. */
update_post_meta( $template_id, '_cf7etm_attachments', '' );

$fallback = CF7ETM_Renderer::to_mail_array(
	$template_id,
	array( 'attachments' => '[form-own-file]' ),
	'admin'
);

cf7etm_check(
	'Empty attachment spec falls back to the form own value',
	'[form-own-file]' === $fallback['attachments'],
	$fallback['attachments']
);

update_post_meta( $template_id, '_cf7etm_attachments', "[your-resume]\n[docs]" );

cf7etm_check(
	'CF7 database row is untouched',
	get_post_meta( $form_id, '_mail', true ) === $original_meta && 'ORIGINAL BODY' === $original_meta['body'],
	'CF7 mail meta changed — this must never happen'
);

/* Customer slot must switch CF7's mail_2 on. */
CF7ETM_CF7_Bridge::assign( $form_id, 'customer', $template_id );

$live2  = WPCF7_ContactForm::get_instance( $form_id );
$mail_2 = $live2->prop( 'mail_2' );

cf7etm_check( 'Customer email (mail_2) activated', ! empty( $mail_2['active'] ) );
cf7etm_check( 'Customer recipient defaults to the visitor', str_contains( $mail_2['recipient'], '[your-email]' ), $mail_2['recipient'] );

/* -------------------------------------------------------------------------
 * The guard that stops CF7's own save() persisting our template
 * ---------------------------------------------------------------------- */

$_REQUEST['page'] = 'wpcf7';

$guarded = CF7ETM_CF7_Bridge::filter_properties(
	array( 'mail' => $original_meta, 'mail_2' => array() ),
	WPCF7_ContactForm::get_instance( $form_id )
);

cf7etm_check(
	'Filter stands down on CF7 edit screens',
	'ORIGINAL BODY' === $guarded['mail']['body'],
	'CF7 admin save would overwrite the original mail config'
);

unset( $_REQUEST['page'] );

/* -------------------------------------------------------------------------
 * Inactive templates must not take over a live form
 * ---------------------------------------------------------------------- */

wp_update_post( array( 'ID' => $template_id, 'post_status' => 'private' ) );

cf7etm_check( 'Inactive template is not applied', null === CF7ETM_Renderer::to_mail_array( $template_id, $original_meta, 'admin' ) );

wp_update_post( array( 'ID' => $template_id, 'post_status' => 'publish' ) );

/* -------------------------------------------------------------------------
 * Duplication, deletion guard and detach
 * ---------------------------------------------------------------------- */

$copy_id = CF7ETM_Template_Post_Type::duplicate( $template_id );
$copy    = CF7ETM_Template_Post_Type::get( $copy_id );

cf7etm_check( 'Duplicate copies the body', $copy && $copy['body'] === $stored['body'] );
cf7etm_check( 'Duplicate lands as a draft', $copy && 'draft' === $copy['status'] );
cf7etm_check( 'Duplicate copies the attachment spec', $copy && $copy['attachments'] === $stored['attachments'] );

cf7etm_check( 'Assigned template is reported as in use', count( CF7ETM_CF7_Bridge::forms_using( $template_id ) ) === 1 );

CF7ETM_CF7_Bridge::detach( $form_id, 'admin' );
CF7ETM_CF7_Bridge::detach( $form_id, 'customer' );

$restored = WPCF7_ContactForm::get_instance( $form_id )->prop( 'mail' );

cf7etm_check( 'Detach restores CF7 own settings', 'ORIGINAL BODY' === $restored['body'], $restored['body'] );
cf7etm_check( 'Assignment removed', array() === CF7ETM_CF7_Bridge::for_form( $form_id ) );

/* Deleting a template must prune its assignments. */
CF7ETM_CF7_Bridge::assign( $form_id, 'admin', $copy_id );
wp_update_post( array( 'ID' => $copy_id, 'post_status' => 'publish' ) );
CF7ETM_CF7_Bridge::assign( $form_id, 'admin', $copy_id );
wp_delete_post( $copy_id, true );

cf7etm_check( 'Deleting a template prunes its assignments', array() === CF7ETM_CF7_Bridge::for_form( $form_id ) );

/* -------------------------------------------------------------------------
 * Screen option and branding round-trip
 * ---------------------------------------------------------------------- */

cf7etm_check(
	'Templates-per-page screen option is saved',
	35 === apply_filters( 'set_screen_option_cf7etm_per_page', false, 'cf7etm_per_page', '35' )
);

cf7etm_check(
	'Absurd per-page values are clamped',
	200 === apply_filters( 'set_screen_option_cf7etm_per_page', false, 'cf7etm_per_page', '99999' )
);

$branding_before = CF7ETM_Branding::get();

CF7ETM_Branding::save( array_merge( $branding_before, array( 'company_name' => 'Imported Co', 'primary_color' => '#abcdef' ) ) );

$branding_after = CF7ETM_Branding::get();

cf7etm_check( 'Branding import restores text values', 'Imported Co' === $branding_after['company_name'] );
cf7etm_check( 'Branding import restores colours', '#abcdef' === $branding_after['primary_color'] );

CF7ETM_Branding::save( array_merge( $branding_before, array( 'primary_color' => 'not-a-colour' ) ) );

cf7etm_check( 'Invalid colour falls back to the default', '#2271b1' === CF7ETM_Branding::get()['primary_color'] );

CF7ETM_Branding::save( $branding_before );

/* -------------------------------------------------------------------------
 * Submissions log
 * ---------------------------------------------------------------------- */

CF7ETM_Submissions::install();

global $wpdb;

cf7etm_check(
	'Submissions table exists',
	CF7ETM_Submissions::table() === $wpdb->get_var(
		$wpdb->prepare( 'SHOW TABLES LIKE %s', CF7ETM_Submissions::table() )
	)
);

$entry_before = CF7ETM_Submissions::count();

$wpdb->insert(
	CF7ETM_Submissions::table(),
	array(
		'form_id'      => $form_id,
		'form_title'   => 'CF7ETM Smoke Form',
		'status'       => 'sent',
		'fields'       => wp_json_encode(
			array(
				'your-name'    => 'John Smith',
				'your-message' => "Line one\nLine two",
				'gone-field'   => array( 'a', 'b' ),
			)
		),
		'files'        => wp_json_encode( array( 'your-file' => array( 'cv.pdf' ) ) ),
		'remote_ip'    => '203.0.113.42',
		'submitted_at' => current_time( 'mysql' ),
	)
);

$entry_id = (int) $wpdb->insert_id;
$stored   = CF7ETM_Submissions::get( $entry_id );

cf7etm_check( 'Submission stored and read back', $stored && 'John Smith' === $stored['fields']['your-name'], wp_json_encode( $stored ) );
cf7etm_check( 'Multi-value answers survive the round trip', $stored && array( 'a', 'b' ) === $stored['fields']['gone-field'] );
cf7etm_check( 'Uploaded file names are kept', $stored && array( 'cv.pdf' ) === $stored['files']['your-file'] );

$listed = CF7ETM_Submissions::query( array( 'form_id' => $form_id ) );

cf7etm_check( 'Submission listed for its form', 1 === $listed['total'] );

cf7etm_check(
	'Search matches stored answers',
	1 === CF7ETM_Submissions::query( array( 'search' => 'John Smith' ) )['total']
);

cf7etm_check(
	'Search ignores answers that are not there',
	0 === CF7ETM_Submissions::query( array( 'search' => 'nobody-by-that-name' ) )['total']
);

$columns = CF7ETM_Submissions::field_columns( $form_id, $listed['items'] );

cf7etm_check(
	'Columns cover the form fields and any extras in the data',
	isset( $columns['your-name'], $columns['your-message'], $columns['gone-field'] ),
	implode( ', ', array_keys( $columns ) )
);

cf7etm_check( 'Forms with submissions are listed', isset( CF7ETM_Submissions::forms()[ $form_id ] ) );

CF7ETM_Submissions::delete( array( $entry_id ) );

cf7etm_check(
	'Submission deleted',
	null === CF7ETM_Submissions::get( $entry_id ) && $entry_before === CF7ETM_Submissions::count()
);

/*
 * The capture hook itself, driven through Contact Form 7's own submit() so
 * the test exercises the real path. wp_mail is short-circuited: this checks
 * that a submission is logged, not that the server can send email.
 *
 * Its own form, because the fixture above requires a file upload that a
 * command-line submission cannot provide.
 */
$live = WPCF7_ContactForm::get_template( array( 'title' => 'CF7ETM Capture Form' ) );

$live->set_properties(
	array( 'form' => "[text* your-name]\n[email* your-email]\n[textarea your-message]" )
);

$live_id = $live->save();

// A command-line post has no browser fingerprint, which CF7 reads as spam.
add_filter( 'wpcf7_skip_spam_check', '__return_true' );
add_filter( 'pre_wp_mail', '__return_true' );

$_POST = array(
	'_wpcf7'          => $live_id,
	'_wpcf7_version'  => WPCF7_VERSION,
	'_wpcf7_locale'   => 'en_US',
	'_wpcf7_unit_tag' => 'wpcf7-f' . $live_id . '-o1',
	'your-name'       => 'Jane Tester',
	'your-email'      => 'jane@example.com',
	'your-message'    => "First line\nSecond line",
);

$submit_result = WPCF7_ContactForm::get_instance( $live_id )->submit();

$_POST = array();

remove_filter( 'pre_wp_mail', '__return_true' );
remove_filter( 'wpcf7_skip_spam_check', '__return_true' );

$captured = CF7ETM_Submissions::query( array( 'form_id' => $live_id ) );
$logged   = $captured['items'][0] ?? null;

cf7etm_check(
	'A real submission is captured',
	1 === $captured['total'],
	'CF7 returned: ' . wp_json_encode( $submit_result )
);

cf7etm_check(
	'Captured answers match what was posted',
	$logged && 'Jane Tester' === ( $logged['fields']['your-name'] ?? '' )
		&& "First line\nSecond line" === ( $logged['fields']['your-message'] ?? '' ),
	wp_json_encode( $logged['fields'] ?? array() )
);

cf7etm_check( 'The email result is recorded', $logged && 'sent' === $logged['status'] );

cf7etm_check(
	'Contact Form 7 internals are not stored as answers',
	$logged && ! array_filter( array_keys( $logged['fields'] ), static fn( $key ) => str_starts_with( $key, '_' ) ),
	implode( ', ', array_keys( $logged['fields'] ?? array() ) )
);

CF7ETM_Submissions::delete( array( $logged['id'] ?? 0 ) );
wp_delete_post( $live_id, true );

/*
 * Uploaded files: Contact Form 7 deletes its own copy when the request ends,
 * so the copy that matters is ours.
 */
$source_dir  = wp_upload_dir()['basedir'] . '/cf7etm-test-source';
$source_file = $source_dir . '/notes.txt';
$blocked     = $source_dir . '/payload.php';

wp_mkdir_p( $source_dir );
file_put_contents( $source_file, 'attached file body' );
file_put_contents( $blocked, '<?php // should never be stored' );

$stored = CF7ETM_Submissions::store_files(
	array(
		'your-doc'  => array( $source_file ),
		'your-code' => array( $blocked ),
	)
);

$kept_path = CF7ETM_Submissions::file_path( $stored['your-doc'][0]['path'] ?? '' );

cf7etm_check( 'Uploaded file is copied somewhere permanent', '' !== $kept_path, wp_json_encode( $stored ) );

cf7etm_check(
	'The stored copy holds the original bytes',
	$kept_path && 'attached file body' === file_get_contents( $kept_path )
);

cf7etm_check(
	'The original name is kept for display',
	'notes.txt' === ( $stored['your-doc'][0]['name'] ?? '' )
);

cf7etm_check(
	'A file type WordPress will not allow is recorded but never stored',
	'' === ( $stored['your-code'][0]['path'] ?? 'x' )
		&& 'type' === ( $stored['your-code'][0]['error'] ?? '' ),
	wp_json_encode( $stored['your-code'] ?? array() )
);

cf7etm_check(
	'The upload folder is closed to the web',
	file_exists( CF7ETM_Submissions::upload_dir() . '/.htaccess' )
		&& file_exists( CF7ETM_Submissions::upload_dir() . '/index.html' )
);

cf7etm_check(
	'A path climbing out of the upload folder is refused',
	'' === CF7ETM_Submissions::file_path( '../../../wp-config.php' )
);

$wpdb->insert(
	CF7ETM_Submissions::table(),
	array(
		'form_id'      => $form_id,
		'form_title'   => 'CF7ETM Smoke Form',
		'status'       => 'sent',
		'fields'       => wp_json_encode( array( 'your-name' => 'Ada Upload' ) ),
		'files'        => wp_json_encode( $stored ),
		'remote_ip'    => '203.0.113.7',
		'submitted_at' => current_time( 'mysql' ),
	)
);

$file_entry = CF7ETM_Submissions::get( (int) $wpdb->insert_id );
$listed_file = CF7ETM_Submissions::files( $file_entry )['your-doc'][0] ?? array();

cf7etm_check(
	'Stored files read back with a download index',
	isset( $listed_file['index'] ) && 'notes.txt' === $listed_file['name']
);

CF7ETM_Submissions::delete( array( $file_entry['id'] ) );

cf7etm_check( 'Deleting a submission removes its files', ! file_exists( $kept_path ) );

wp_delete_file( $source_file );
wp_delete_file( $blocked );

/* -------------------------------------------------------------------------
 * Clean up
 * ---------------------------------------------------------------------- */

wp_delete_post( $template_id, true );
wp_delete_post( $form_id, true );

cf7etm_check( 'Deleting a form prunes its assignments', array() === CF7ETM_CF7_Bridge::for_form( $form_id ) );

echo str_repeat( '-', 60 ) . "\n";
printf( "%d checks, %d failures\n\n", $checks, $failures );

exit( $failures ? 1 : 0 );
