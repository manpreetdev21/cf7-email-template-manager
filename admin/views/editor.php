<?php
/**
 * Template editor screen.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading which template to edit.
$template_id = isset( $_GET['template'] ) ? absint( $_GET['template'] ) : 0;

$template = $template_id ? CF7ETM_Template_Post_Type::get( $template_id ) : null;

if ( ! $template ) {
	$template = array(
		'id'            => 0,
		'name'          => '',
		'body'          => '',
		'description'   => '',
		'status'        => 'publish',
		'type'          => CF7ETM_Plugin::setting( 'default_type', 'html' ),
		'subject'       => '',
		'preview_text'  => '',
		'recipient'     => '',
		'sender'        => '',
		'headers'       => '',
		'exclude_blank' => 1,
		'category'      => '',
		'form_context'  => 0,
	);
}

$forms = CF7ETM_CF7_Bridge::forms();

// Pick the most useful form to validate against: the stored one, then a form
// this template is already assigned to, then the first available form.
$form_context = (int) $template['form_context'];

if ( ! $form_context || ! isset( $forms[ $form_context ] ) ) {
	$using        = $template['id'] ? CF7ETM_CF7_Bridge::forms_using( $template['id'] ) : array();
	$form_context = $using ? (int) $using[0] : (int) ( array_key_first( $forms ) ?? 0 );
}

$assigned_to = $template['id'] ? CF7ETM_CF7_Bridge::forms_using( $template['id'] ) : array();

$status_modifier = match ( $template['status'] ) {
	'publish' => 'success',
	'private' => 'neutral',
	default   => 'warning',
};
?>
<div class="wrap cf7etm cf7etm-editor"
	data-template-id="<?php echo esc_attr( (string) $template['id'] ); ?>"
	data-form-id="<?php echo esc_attr( (string) $form_context ); ?>">

	<?php CF7ETM_Admin::flash(); ?>

	<div class="cf7etm-editor__bar">
		<div class="cf7etm-editor__identity">
			<a class="cf7etm-editor__back" href="<?php echo esc_url( CF7ETM_Plugin::url( 'templates' ) ); ?>"
				aria-label="<?php esc_attr_e( 'Back to templates', 'cf7-email-template-manager' ); ?>">
				<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
			</a>
			<div>
				<label class="screen-reader-text" for="cf7etm-name"><?php esc_html_e( 'Template name', 'cf7-email-template-manager' ); ?></label>
				<input type="text" id="cf7etm-name" class="cf7etm-editor__name" data-field="name"
					value="<?php echo esc_attr( $template['name'] ); ?>"
					placeholder="<?php esc_attr_e( 'Untitled template', 'cf7-email-template-manager' ); ?>" />
				<div class="cf7etm-editor__meta">
					<span class="cf7etm-badge cf7etm-badge--<?php echo esc_attr( $status_modifier ); ?>" data-status-badge>
						<?php echo esc_html( CF7ETM_Template_Post_Type::status_label( $template['status'] ) ); ?>
					</span>
					<span class="cf7etm-muted" data-dirty-flag hidden><?php esc_html_e( 'Unsaved changes', 'cf7-email-template-manager' ); ?></span>
				</div>
			</div>
		</div>

		<div class="cf7etm-editor__actions">
			<button type="button" class="cf7etm-btn" data-action="preview">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<?php esc_html_e( 'Preview', 'cf7-email-template-manager' ); ?>
			</button>
			<button type="button" class="cf7etm-btn" data-action="send-test">
				<span class="dashicons dashicons-email" aria-hidden="true"></span>
				<?php esc_html_e( 'Send Test', 'cf7-email-template-manager' ); ?>
			</button>
			<button type="button" class="cf7etm-btn cf7etm-btn--primary" data-action="save">
				<?php esc_html_e( 'Save', 'cf7-email-template-manager' ); ?>
			</button>
		</div>
	</div>

	<div class="cf7etm-editor__layout">

		<!-- LEFT: available tags -->
		<aside class="cf7etm-panel cf7etm-tags" aria-label="<?php esc_attr_e( 'Available tags', 'cf7-email-template-manager' ); ?>">
			<div class="cf7etm-panel__head">
				<h2><?php esc_html_e( 'Available Tags', 'cf7-email-template-manager' ); ?></h2>
			</div>

			<div class="cf7etm-panel__body">
				<p class="cf7etm-field">
					<label for="cf7etm-form-context"><?php esc_html_e( 'Detect tags from', 'cf7-email-template-manager' ); ?></label>
					<select id="cf7etm-form-context" data-field="form_context">
						<option value="0"><?php esc_html_e( '— Select a contact form —', 'cf7-email-template-manager' ); ?></option>
						<?php foreach ( $forms as $id => $title ) : ?>
							<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( $form_context, $id ); ?>>
								<?php echo esc_html( $title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="cf7etm-help"><?php esc_html_e( 'Used to detect tags and to check the template. It does not assign the template.', 'cf7-email-template-manager' ); ?></span>
				</p>

				<p class="cf7etm-field">
					<label class="screen-reader-text" for="cf7etm-tag-search"><?php esc_html_e( 'Search tags', 'cf7-email-template-manager' ); ?></label>
					<input type="search" id="cf7etm-tag-search" placeholder="<?php esc_attr_e( 'Search tags…', 'cf7-email-template-manager' ); ?>" />
				</p>

				<div class="cf7etm-tags__recent" data-recent-tags hidden>
					<h3><?php esc_html_e( 'Recently used', 'cf7-email-template-manager' ); ?></h3>
					<div class="cf7etm-tags__list" data-recent-list></div>
				</div>

				<div class="cf7etm-tags__group">
					<h3><?php esc_html_e( 'Form Fields', 'cf7-email-template-manager' ); ?></h3>
					<div class="cf7etm-tags__list" data-form-tags>
						<p class="cf7etm-muted"><?php esc_html_e( 'Select a contact form to see its fields.', 'cf7-email-template-manager' ); ?></p>
					</div>
				</div>

				<div class="cf7etm-tags__group">
					<h3><?php esc_html_e( 'System Tags', 'cf7-email-template-manager' ); ?></h3>
					<div class="cf7etm-tags__list">
						<?php foreach ( CF7ETM_CF7_Bridge::special_tags() as $tag => $label ) : ?>
							<?php require CF7ETM_DIR . 'admin/views/partial-tag.php'; ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="cf7etm-tags__group">
					<h3><?php esc_html_e( 'Branding', 'cf7-email-template-manager' ); ?></h3>
					<div class="cf7etm-tags__list">
						<?php foreach ( CF7ETM_Branding::tags() as $tag => $label ) : ?>
							<?php require CF7ETM_DIR . 'admin/views/partial-tag.php'; ?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</aside>

		<!-- CENTRE: the email itself -->
		<main class="cf7etm-panel cf7etm-compose">
			<div class="cf7etm-panel__body">

				<div class="cf7etm-alert cf7etm-alert--warning" data-unknown-tags hidden>
					<p>
						<strong><?php esc_html_e( 'Warning', 'cf7-email-template-manager' ); ?></strong>
						<?php esc_html_e( 'This template contains tags that are not available in the selected Contact Form 7 form.', 'cf7-email-template-manager' ); ?>
					</p>
					<div class="cf7etm-tags__list" data-unknown-list></div>
					<p class="cf7etm-help"><?php esc_html_e( 'Nothing is removed automatically. Keep a tag if you plan to add the field, or remove it from the template.', 'cf7-email-template-manager' ); ?></p>
				</div>

				<div class="cf7etm-alert cf7etm-alert--info" data-new-tags hidden>
					<p data-new-tags-message></p>
					<div class="cf7etm-tags__list" data-new-tags-list></div>
				</div>

				<p class="cf7etm-field">
					<label for="cf7etm-subject"><?php esc_html_e( 'Subject', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-subject" data-field="subject" data-insertable="1"
						value="<?php echo esc_attr( $template['subject'] ); ?>"
						placeholder="<?php esc_attr_e( 'New enquiry from [your-name]', 'cf7-email-template-manager' ); ?>" />
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-preview-text"><?php esc_html_e( 'Preview Text', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-preview-text" data-field="preview_text" data-insertable="1"
						value="<?php echo esc_attr( $template['preview_text'] ); ?>" />
					<span class="cf7etm-help"><?php esc_html_e( 'The short line inboxes show next to the subject. Optional.', 'cf7-email-template-manager' ); ?></span>
				</p>

				<div class="cf7etm-field cf7etm-field--grow">
					<label for="cf7etm-body"><?php esc_html_e( 'Email Body', 'cf7-email-template-manager' ); ?></label>
					<textarea id="cf7etm-body" data-field="body" data-insertable="1" rows="24"
						spellcheck="false"><?php echo esc_textarea( $template['body'] ); ?></textarea>
					<span class="cf7etm-help"><?php esc_html_e( 'Click any tag on the left to insert it where your cursor is.', 'cf7-email-template-manager' ); ?></span>
				</div>

			</div>
		</main>

		<!-- RIGHT: settings -->
		<aside class="cf7etm-panel cf7etm-settings-panel" aria-label="<?php esc_attr_e( 'Template settings', 'cf7-email-template-manager' ); ?>">
			<div class="cf7etm-panel__head">
				<h2><?php esc_html_e( 'Settings', 'cf7-email-template-manager' ); ?></h2>
			</div>

			<div class="cf7etm-panel__body">
				<p class="cf7etm-field">
					<label for="cf7etm-type"><?php esc_html_e( 'Template Type', 'cf7-email-template-manager' ); ?></label>
					<select id="cf7etm-type" data-field="type">
						<option value="html" <?php selected( $template['type'], 'html' ); ?>><?php esc_html_e( 'HTML', 'cf7-email-template-manager' ); ?></option>
						<option value="text" <?php selected( $template['type'], 'text' ); ?>><?php esc_html_e( 'Plain Text', 'cf7-email-template-manager' ); ?></option>
					</select>
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-status"><?php esc_html_e( 'Status', 'cf7-email-template-manager' ); ?></label>
					<select id="cf7etm-status" data-field="status">
						<option value="publish" <?php selected( $template['status'], 'publish' ); ?>><?php esc_html_e( 'Active', 'cf7-email-template-manager' ); ?></option>
						<option value="draft" <?php selected( $template['status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'cf7-email-template-manager' ); ?></option>
						<option value="private" <?php selected( $template['status'], 'private' ); ?>><?php esc_html_e( 'Inactive', 'cf7-email-template-manager' ); ?></option>
					</select>
					<span class="cf7etm-help"><?php esc_html_e( 'Only active templates are used when a form is submitted.', 'cf7-email-template-manager' ); ?></span>
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-category"><?php esc_html_e( 'Category', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-category" data-field="category"
						value="<?php echo esc_attr( $template['category'] ); ?>"
						placeholder="<?php esc_attr_e( 'Admin, Customer…', 'cf7-email-template-manager' ); ?>" />
				</p>

				<hr class="cf7etm-rule" />

				<p class="cf7etm-field">
					<label for="cf7etm-recipient"><?php esc_html_e( 'To', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-recipient" data-field="recipient" data-insertable="1"
						value="<?php echo esc_attr( $template['recipient'] ); ?>"
						placeholder="<?php esc_attr_e( 'Leave empty to keep the form’s own recipient', 'cf7-email-template-manager' ); ?>" />
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-sender"><?php esc_html_e( 'From', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-sender" data-field="sender" data-insertable="1"
						value="<?php echo esc_attr( $template['sender'] ); ?>"
						placeholder="<?php esc_attr_e( 'Leave empty to keep the form’s own sender', 'cf7-email-template-manager' ); ?>" />
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-headers"><?php esc_html_e( 'Additional Headers', 'cf7-email-template-manager' ); ?></label>
					<textarea id="cf7etm-headers" data-field="headers" rows="3" data-insertable="1"
						placeholder="Reply-To: [your-email]"><?php echo esc_textarea( $template['headers'] ); ?></textarea>
					<span class="cf7etm-help"><?php esc_html_e( 'One header per line, for example Reply-To or Cc.', 'cf7-email-template-manager' ); ?></span>
				</p>

				<p class="cf7etm-field cf7etm-field--check">
					<label for="cf7etm-exclude-blank">
						<input type="checkbox" id="cf7etm-exclude-blank" data-field="exclude_blank"
							<?php checked( (int) $template['exclude_blank'], 1 ); ?> />
						<?php esc_html_e( 'Hide empty fields', 'cf7-email-template-manager' ); ?>
					</label>
					<span class="cf7etm-help"><?php esc_html_e( 'Removes lines whose tags came back empty.', 'cf7-email-template-manager' ); ?></span>
				</p>

				<hr class="cf7etm-rule" />

				<p class="cf7etm-field">
					<label for="cf7etm-description"><?php esc_html_e( 'Description', 'cf7-email-template-manager' ); ?></label>
					<textarea id="cf7etm-description" data-field="description" rows="3"><?php echo esc_textarea( $template['description'] ); ?></textarea>
				</p>

				<div class="cf7etm-field">
					<span class="cf7etm-field__label"><?php esc_html_e( 'Assigned Forms', 'cf7-email-template-manager' ); ?></span>
					<?php if ( $assigned_to ) : ?>
						<ul class="cf7etm-list-plain">
							<?php foreach ( $assigned_to as $form_id ) : ?>
								<li><?php echo esc_html( $forms[ $form_id ] ?? sprintf( '#%d', $form_id ) ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="cf7etm-muted"><?php esc_html_e( 'Not assigned to any form yet.', 'cf7-email-template-manager' ); ?></p>
					<?php endif; ?>
					<a class="cf7etm-btn cf7etm-btn--small" href="<?php echo esc_url( CF7ETM_Plugin::url( 'assignments' ) ); ?>">
						<?php esc_html_e( 'Manage assignments', 'cf7-email-template-manager' ); ?>
					</a>
				</div>
			</div>
		</aside>

	</div>
</div>
