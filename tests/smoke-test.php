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
		'form' => "[text* your-name]\n[email* your-email]\n[tel your-phone]\n[textarea your-message]",
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

$xss = CF7ETM_Template_Post_Type::sanitize_body( '<p onclick="evil()">hi</p><script>alert(1)</script><iframe src="x"></iframe>', 'html' );

cf7etm_check( 'Scripts stripped from bodies', ! str_contains( $xss, '<script' ) && ! str_contains( $xss, '<iframe' ) && ! str_contains( $xss, 'onclick' ), $xss );

/* -------------------------------------------------------------------------
 * Tag detection and validation
 * ---------------------------------------------------------------------- */

$mail_tags = CF7ETM_CF7_Bridge::mail_tags( $form_id );

cf7etm_check(
	'CF7 mail-tags detected',
	array_diff( array( 'your-name', 'your-email', 'your-phone', 'your-message' ), $mail_tags ) === array(),
	implode( ', ', $mail_tags )
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
 * Clean up
 * ---------------------------------------------------------------------- */

wp_delete_post( $template_id, true );
wp_delete_post( $form_id, true );

cf7etm_check( 'Deleting a form prunes its assignments', array() === CF7ETM_CF7_Bridge::for_form( $form_id ) );

echo str_repeat( '-', 60 ) . "\n";
printf( "%d checks, %d failures\n\n", $checks, $failures );

exit( $failures ? 1 : 0 );
