=== CF7 Email Template Manager ===
Contributors: manpreetsingh
Tags: contact form 7, email template, html email, cf7, email
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Reusable, brandable email templates for Contact Form 7. Design once, assign to any form.

== Description ==

CF7 Email Template Manager is a template layer for Contact Form 7. Contact Form 7 keeps doing what it does best — rendering, validating and submitting forms, and sending the mail. This plugin manages what those emails look like.

**Your Contact Form 7 settings are never overwritten.** Templates are applied at runtime, so your form's own Mail configuration stays exactly as you left it and takes over again the moment you detach a template.

Features:

* Reusable HTML and plain-text email templates
* Automatic detection of every mail-tag on a form, using Contact Form 7's own API
* Click-to-insert tags with friendly names, search and recently used
* Warnings when a template uses a tag the form does not have — nothing is ever removed for you
* A notice when a form gains new fields your template is not using yet
* Separate admin notification and customer confirmation templates
* Global branding: logo, colours, footer, address and social links across every template
* Preview with realistic sample data, and test emails
* Duplicate, search, filter, bulk actions, JSON import and export
* Eight starter templates

== Installation ==

1. Install and activate Contact Form 7.
2. Upload this plugin to `/wp-content/plugins/` and activate it.
3. Go to **CF7 Email Templates** in the admin menu.

== Frequently Asked Questions ==

= Does this change my Contact Form 7 mail settings? =

No. Templates are injected when a form is used, not written into Contact Form 7's database. Detaching a template restores your original settings instantly, because they were never touched.

= What happens if I edit the Mail tab in Contact Form 7 while a template is assigned? =

Nothing breaks. The plugin steps aside on Contact Form 7's own screens, so saving there edits your Contact Form 7 settings as usual. A notice tells you a template is currently in charge of what actually gets sent.

= Will my SMTP plugin still work? =

Yes. Contact Form 7 still sends the mail through WordPress, so any SMTP plugin keeps working. This plugin never stores or displays mail credentials.

= What happens to my templates if I delete the plugin? =

They stay in the database. Data is only removed if you tick "Delete all plugin data" in Settings → Advanced first.

== Changelog ==

= 1.0.0 =
* Initial release.
