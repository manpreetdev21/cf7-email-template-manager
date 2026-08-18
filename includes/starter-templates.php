<?php
/**
 * The eight starter templates seeded on first activation.
 *
 * Every HTML starter is a complete <html> document on purpose: CF7's
 * WPCF7_Mail::htmlize() only skips its own wrapper when the body already
 * matches <html>...</html>, so partial documents would end up double-wrapped.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wraps content in the shared, email-safe HTML shell.
 *
 * @param string $heading Panel heading.
 * @param string $inner   Inner HTML for the content cell.
 * @return string
 */
function cf7etm_html_shell( $heading, $inner ) {
	return '<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>' . $heading . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f1f1;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f1f1;padding:24px 12px;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background-color:#ffffff;border:1px solid #e0e0e0;border-radius:6px;overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Helvetica,Arial,sans-serif;color:#1d2327;">

<tr>
<td style="background-color:[cf7etm_primary_color];padding:24px 32px;" align="left">
[cf7etm_logo]
</td>
</tr>

<tr>
<td style="padding:32px;">
<h1 style="margin:0 0 16px;font-size:20px;line-height:1.3;font-weight:600;color:[cf7etm_secondary_color];">' . $heading . '</h1>
' . $inner . '
</td>
</tr>

<tr>
<td style="padding:20px 32px;background-color:#fafafa;border-top:1px solid #e0e0e0;font-size:12px;line-height:1.6;color:#646970;">
<p style="margin:0 0 8px;">[cf7etm_footer_text]</p>
<p style="margin:0 0 8px;">[cf7etm_address]</p>
<p style="margin:0;">[cf7etm_social_links]</p>
<p style="margin:8px 0 0;">&copy; [cf7etm_year] [cf7etm_company_name] &nbsp;·&nbsp; <a href="[cf7etm_website]" style="color:[cf7etm_primary_color];text-decoration:none;">[cf7etm_website]</a></p>
</td>
</tr>

</table>
</td>
</tr>
</table>
</body>
</html>';
}

/**
 * Builds a two-column details table from label => tag pairs.
 *
 * @param array $rows Label => CF7 tag markup.
 * @return string
 */
function cf7etm_html_rows( $rows ) {
	$html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;font-size:14px;line-height:1.6;">';

	foreach ( $rows as $label => $tag ) {
		$html .= '
<tr>
<td width="140" valign="top" style="padding:10px 12px 10px 0;border-bottom:1px solid #f0f0f1;color:#646970;font-weight:600;">' . $label . '</td>
<td valign="top" style="padding:10px 0;border-bottom:1px solid #f0f0f1;color:#1d2327;">' . $tag . '</td>
</tr>';
	}

	return $html . '
</table>';
}

/**
 * The starter template definitions.
 *
 * @return array
 */
function cf7etm_starter_templates() {
	$paragraph = 'style="margin:0 0 16px;font-size:14px;line-height:1.7;"';
	$button    = static function ( $label, $href ) {
		return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 16px;">
<tr><td align="center" bgcolor="[cf7etm_primary_color]" style="border-radius:4px;">
<a href="' . $href . '" style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">' . $label . '</a>
</td></tr>
</table>';
	};

	return array(

		array(
			'name'         => __( 'Contact Form Notification', 'cf7-email-template-manager' ),
			'type'         => 'html',
			'category'     => __( 'Admin', 'cf7-email-template-manager' ),
			'subject'      => __( 'New enquiry from [your-name]', 'cf7-email-template-manager' ),
			'preview_text' => __( 'A new message was submitted on your website.', 'cf7-email-template-manager' ),
			'description'  => __( 'Admin notification listing every submitted field.', 'cf7-email-template-manager' ),
			'headers'      => 'Reply-To: [your-email]',
			'body'         => cf7etm_html_shell(
				__( 'New Contact Enquiry', 'cf7-email-template-manager' ),
				'<p ' . $paragraph . '>' . __( 'Someone has submitted the contact form on [cf7etm_company_name].', 'cf7-email-template-manager' ) . '</p>'
				. cf7etm_html_rows(
					array(
						__( 'Name', 'cf7-email-template-manager' )    => '[your-name]',
						__( 'Email', 'cf7-email-template-manager' )   => '<a href="mailto:[your-email]" style="color:[cf7etm_primary_color];">[your-email]</a>',
						__( 'Phone', 'cf7-email-template-manager' )   => '[your-phone]',
						__( 'Subject', 'cf7-email-template-manager' ) => '[your-subject]',
						__( 'Message', 'cf7-email-template-manager' ) => '[your-message]',
					)
				)
				. '<p style="margin:20px 0 0;font-size:12px;color:#646970;">' . __( 'Received [_date] at [_time] from [_remote_ip] on [_url]', 'cf7-email-template-manager' ) . '</p>'
			),
		),

		array(
			'name'         => __( 'Customer Thank You', 'cf7-email-template-manager' ),
			'type'         => 'html',
			'category'     => __( 'Customer', 'cf7-email-template-manager' ),
			'recipient'    => '[your-email]',
			'subject'      => __( 'Thank you for contacting us', 'cf7-email-template-manager' ),
			'preview_text' => __( 'We have received your message and will reply shortly.', 'cf7-email-template-manager' ),
			'description'  => __( 'Confirmation sent to the person who filled in the form.', 'cf7-email-template-manager' ),
			'body'         => cf7etm_html_shell(
				__( 'Thank you for getting in touch', 'cf7-email-template-manager' ),
				'<p ' . $paragraph . '>' . __( 'Hello [your-name],', 'cf7-email-template-manager' ) . '</p>'
				. '<p ' . $paragraph . '>' . __( 'Thank you for contacting [cf7etm_company_name]. We have received your message and a member of our team will reply as soon as possible.', 'cf7-email-template-manager' ) . '</p>'
				. '<p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#646970;">' . __( 'Your message', 'cf7-email-template-manager' ) . '</p>'
				. '<div style="padding:16px;background-color:#f6f7f7;border-left:3px solid [cf7etm_primary_color];font-size:14px;line-height:1.7;">[your-message]</div>'
				. '<p style="margin:16px 0 0;font-size:14px;line-height:1.7;">' . __( 'Kind regards,', 'cf7-email-template-manager' ) . '<br />[cf7etm_company_name]</p>'
			),
		),

		array(
			'name'         => __( 'Quote Request', 'cf7-email-template-manager' ),
			'type'         => 'html',
			'category'     => __( 'Admin', 'cf7-email-template-manager' ),
			'subject'      => __( 'Quote request from [your-name]', 'cf7-email-template-manager' ),
			'preview_text' => __( 'A new quote request is waiting for you.', 'cf7-email-template-manager' ),
			'description'  => __( 'Admin notification for pricing and quote enquiries.', 'cf7-email-template-manager' ),
			'headers'      => 'Reply-To: [your-email]',
			'body'         => cf7etm_html_shell(
				__( 'New Quote Request', 'cf7-email-template-manager' ),
				'<p ' . $paragraph . '>' . __( 'A visitor has requested a quote.', 'cf7-email-template-manager' ) . '</p>'
				. cf7etm_html_rows(
					array(
						__( 'Name', 'cf7-email-template-manager' )    => '[your-name]',
						__( 'Company', 'cf7-email-template-manager' ) => '[company]',
						__( 'Email', 'cf7-email-template-manager' )   => '<a href="mailto:[your-email]" style="color:[cf7etm_primary_color];">[your-email]</a>',
						__( 'Phone', 'cf7-email-template-manager' )   => '[your-phone]',
						__( 'Budget', 'cf7-email-template-manager' )  => '[budget]',
						__( 'Details', 'cf7-email-template-manager' ) => '[your-message]',
					)
				)
			),
		),

		array(
			'name'         => __( 'Booking Request', 'cf7-email-template-manager' ),
			'type'         => 'html',
			'category'     => __( 'Admin', 'cf7-email-template-manager' ),
			'subject'      => __( 'Booking request from [your-name]', 'cf7-email-template-manager' ),
			'preview_text' => __( 'A new booking request has come in.', 'cf7-email-template-manager' ),
			'description'  => __( 'Admin notification for appointment and booking forms.', 'cf7-email-template-manager' ),
			'headers'      => 'Reply-To: [your-email]',
			'body'         => cf7etm_html_shell(
				__( 'New Booking Request', 'cf7-email-template-manager' ),
				cf7etm_html_rows(
					array(
						__( 'Name', 'cf7-email-template-manager' )     => '[your-name]',
						__( 'Email', 'cf7-email-template-manager' )    => '<a href="mailto:[your-email]" style="color:[cf7etm_primary_color];">[your-email]</a>',
						__( 'Phone', 'cf7-email-template-manager' )    => '[your-phone]',
						__( 'Date', 'cf7-email-template-manager' )     => '[booking-date]',
						__( 'Time', 'cf7-email-template-manager' )     => '[booking-time]',
						__( 'People', 'cf7-email-template-manager' )   => '[guests]',
						__( 'Requests', 'cf7-email-template-manager' ) => '[your-message]',
					)
				)
				. '<p style="margin:20px 0 0;font-size:12px;color:#646970;">' . __( 'Submitted [_date] at [_time].', 'cf7-email-template-manager' ) . '</p>'
			),
		),

		array(
			'name'         => __( 'Support Request', 'cf7-email-template-manager' ),
			'type'         => 'html',
			'category'     => __( 'Admin', 'cf7-email-template-manager' ),
			'subject'      => __( 'Support request: [your-subject]', 'cf7-email-template-manager' ),
			'preview_text' => __( 'A customer needs help.', 'cf7-email-template-manager' ),
			'description'  => __( 'Admin notification for help desk and support forms.', 'cf7-email-template-manager' ),
			'headers'      => 'Reply-To: [your-email]',
			'body'         => cf7etm_html_shell(
				__( 'New Support Request', 'cf7-email-template-manager' ),
				cf7etm_html_rows(
					array(
						__( 'From', 'cf7-email-template-manager' )     => '[your-name] &lt;[your-email]&gt;',
						__( 'Subject', 'cf7-email-template-manager' )  => '[your-subject]',
						__( 'Priority', 'cf7-email-template-manager' ) => '[priority]',
						__( 'Details', 'cf7-email-template-manager' )  => '[your-message]',
					)
				)
				. $button( __( 'Reply to customer', 'cf7-email-template-manager' ), 'mailto:[your-email]' )
			),
		),

		array(
			'name'         => __( 'Newsletter Signup', 'cf7-email-template-manager' ),
			'type'         => 'html',
			'category'     => __( 'Customer', 'cf7-email-template-manager' ),
			'recipient'    => '[your-email]',
			'subject'      => __( 'You are subscribed', 'cf7-email-template-manager' ),
			'preview_text' => __( 'Welcome aboard — your subscription is confirmed.', 'cf7-email-template-manager' ),
			'description'  => __( 'Confirmation for newsletter and mailing list signups.', 'cf7-email-template-manager' ),
			'body'         => cf7etm_html_shell(
				__( 'Welcome aboard', 'cf7-email-template-manager' ),
				'<p ' . $paragraph . '>' . __( 'Hello [your-name],', 'cf7-email-template-manager' ) . '</p>'
				. '<p ' . $paragraph . '>' . __( 'Thanks for subscribing to updates from [cf7etm_company_name]. We will send you news now and then — never spam.', 'cf7-email-template-manager' ) . '</p>'
				. $button( __( 'Visit our website', 'cf7-email-template-manager' ), '[cf7etm_website]' )
			),
		),

		array(
			'name'         => __( 'Simple Notification', 'cf7-email-template-manager' ),
			'type'         => 'text',
			'category'     => __( 'Admin', 'cf7-email-template-manager' ),
			'subject'      => __( 'New enquiry from [your-name]', 'cf7-email-template-manager' ),
			'description'  => __( 'Plain-text admin notification. Works in every mail client.', 'cf7-email-template-manager' ),
			'headers'      => 'Reply-To: [your-email]',
			'body'         => __(
				'New Contact Enquiry
==================

Name:  [your-name]
Email: [your-email]
Phone: [your-phone]

Message:
[your-message]

--
Sent from [_site_title] ([_site_url])
Received [_date] at [_time] from [_remote_ip]',
				'cf7-email-template-manager'
			),
		),

		array(
			'name'        => __( 'Blank Template', 'cf7-email-template-manager' ),
			'type'        => 'html',
			'category'    => __( 'Starter', 'cf7-email-template-manager' ),
			'status'      => 'draft',
			'subject'     => '',
			'description' => __( 'An empty branded shell to build your own layout in.', 'cf7-email-template-manager' ),
			'body'        => cf7etm_html_shell(
				__( 'Heading', 'cf7-email-template-manager' ),
				'<p style="margin:0 0 16px;font-size:14px;line-height:1.7;">' . __( 'Write your email here, and insert form tags from the sidebar.', 'cf7-email-template-manager' ) . '</p>'
			),
		),
	);
}

/**
 * Inserts the starter templates. Runs once, on first activation.
 *
 * @return int Number of templates created.
 */
function cf7etm_install_starter_templates() {
	$created = 0;

	foreach ( cf7etm_starter_templates() as $template ) {
		$id = CF7ETM_Template_Post_Type::save(
			wp_parse_args(
				$template,
				array(
					'status'        => 'publish',
					'recipient'     => '',
					'sender'        => '',
					'headers'       => '',
					'preview_text'  => '',
					'exclude_blank' => 1,
				)
			)
		);

		if ( ! is_wp_error( $id ) ) {
			++$created;
		}
	}

	return $created;
}
