<?php
/**
 * Assignments screen: which template each contact form uses.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

$cf7etm_forms       = CF7ETM_CF7_Bridge::forms();
$cf7etm_assignments = CF7ETM_CF7_Bridge::assignments();
$cf7etm_options     = CF7ETM_Template_Post_Type::options( true );

// An assigned template that was later deactivated must still appear here, or
// the row shows "No template" while the form is still marked as managed.
foreach ( CF7ETM_CF7_Bridge::assigned_template_ids() as $cf7etm_assigned_id ) {
	if ( isset( $cf7etm_options[ $cf7etm_assigned_id ] ) ) {
		continue;
	}

	$cf7etm_stale = CF7ETM_Template_Post_Type::get( $cf7etm_assigned_id );

	if ( $cf7etm_stale ) {
		$cf7etm_options[ $cf7etm_assigned_id ] = sprintf(
			/* translators: 1: template name, 2: status label, e.g. Inactive */
			__( '%1$s (%2$s)', 'cf7-email-template-manager' ),
			$cf7etm_stale['name'],
			CF7ETM_Template_Post_Type::status_label( $cf7etm_stale['status'] )
		);
	}
}

// The templates list links here with ?template=N to assign that template.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preselection.
$cf7etm_preselect = isset( $_GET['template'] ) ? absint( $_GET['template'] ) : 0;

if ( $cf7etm_preselect && ! isset( $cf7etm_options[ $cf7etm_preselect ] ) ) {
	$cf7etm_preselect = 0;
}

$cf7etm_slots = array(
	'admin'    => __( 'Admin Email Template', 'cf7-email-template-manager' ),
	'customer' => __( 'Customer Email Template', 'cf7-email-template-manager' ),
);
?>
<div class="wrap cf7etm cf7etm-assignments">

	<?php
	CF7ETM_Admin::header( __( 'Assignments', 'cf7-email-template-manager' ) );
	CF7ETM_Admin::flash();
	?>

	<div class="cf7etm-alert cf7etm-alert--info">
		<?php esc_html_e( 'Assigning a template does not change your Contact Form 7 mail settings. They stay exactly as they are and take over again the moment you detach.', 'cf7-email-template-manager' ); ?>
	</div>

	<?php if ( $cf7etm_preselect ) : ?>
		<div class="cf7etm-alert cf7etm-alert--info">
			<?php
			printf(
				/* translators: %s: template name */
				esc_html__( '%s is pre-selected below. Pick the form and email it should handle, then press Apply Template.', 'cf7-email-template-manager' ),
				'<strong>' . esc_html( $cf7etm_options[ $cf7etm_preselect ] ) . '</strong>'
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( ! $cf7etm_forms ) : ?>

		<div class="cf7etm-empty">
			<span class="dashicons dashicons-feedback" aria-hidden="true"></span>
			<h2><?php esc_html_e( 'No contact forms found.', 'cf7-email-template-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create a form in Contact Form 7 first, then come back to assign a template to it.', 'cf7-email-template-manager' ); ?></p>
			<a class="cf7etm-btn cf7etm-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7-new' ) ); ?>">
				<?php esc_html_e( 'Create a contact form', 'cf7-email-template-manager' ); ?>
			</a>
		</div>

	<?php elseif ( ! $cf7etm_options ) : ?>

		<div class="cf7etm-empty">
			<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
			<h2><?php esc_html_e( 'No active templates.', 'cf7-email-template-manager' ); ?></h2>
			<p><?php esc_html_e( 'Only active templates can be assigned to a form. Create one, or set an existing template to Active.', 'cf7-email-template-manager' ); ?></p>
			<a class="cf7etm-btn cf7etm-btn--primary" href="<?php echo esc_url( CF7ETM_Plugin::url( 'template-edit' ) ); ?>">
				<?php esc_html_e( 'Create Template', 'cf7-email-template-manager' ); ?>
			</a>
		</div>

	<?php else : ?>

		<div class="cf7etm-card cf7etm-card--flush">
			<table class="cf7etm-table cf7etm-table--assignments">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Contact Form', 'cf7-email-template-manager' ); ?></th>
						<?php foreach ( $cf7etm_slots as $cf7etm_label ) : ?>
							<th scope="col"><?php echo esc_html( $cf7etm_label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $cf7etm_forms as $cf7etm_form_id => $title ) : ?>
						<?php $cf7etm_current = $cf7etm_assignments[ $cf7etm_form_id ] ?? array(); ?>
						<tr data-form-id="<?php echo esc_attr( (string) $cf7etm_form_id ); ?>">
							<td data-label="<?php esc_attr_e( 'Contact Form', 'cf7-email-template-manager' ); ?>">
								<strong><?php echo esc_html( $title ); ?></strong>
								<?php if ( $cf7etm_current ) : ?>
									<span class="cf7etm-badge cf7etm-badge--success"><?php esc_html_e( 'Managed', 'cf7-email-template-manager' ); ?></span>
								<?php endif; ?>
								<div class="cf7etm-muted">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7&post=' . $cf7etm_form_id . '&action=edit' ) ); ?>">
										<?php esc_html_e( 'Edit in Contact Form 7', 'cf7-email-template-manager' ); ?>
									</a>
								</div>
							</td>

							<?php foreach ( $cf7etm_slots as $cf7etm_slot => $cf7etm_label ) : ?>
								<?php $cf7etm_assigned = (int) ( $cf7etm_current[ $cf7etm_slot ] ?? 0 ); ?>
								<td data-label="<?php echo esc_attr( $cf7etm_label ); ?>">
									<div class="cf7etm-assign" data-slot="<?php echo esc_attr( $cf7etm_slot ); ?>">
										<label class="screen-reader-text" for="cf7etm-select-<?php echo esc_attr( $cf7etm_form_id . '-' . $cf7etm_slot ); ?>">
											<?php
											printf(
												/* translators: 1: slot label, 2: form title */
												esc_html__( '%1$s for %2$s', 'cf7-email-template-manager' ),
												esc_html( $cf7etm_label ),
												esc_html( $title )
											);
											?>
										</label>
										<select id="cf7etm-select-<?php echo esc_attr( $cf7etm_form_id . '-' . $cf7etm_slot ); ?>" data-template-select>
											<option value="0"><?php esc_html_e( '— No template —', 'cf7-email-template-manager' ); ?></option>
											<?php
											// An empty admin slot takes the preselection from ?template=N.
											$cf7etm_chosen = $cf7etm_assigned;

											if ( ! $cf7etm_chosen && $cf7etm_preselect && 'admin' === $cf7etm_slot ) {
												$cf7etm_chosen = $cf7etm_preselect;
											}

											foreach ( $cf7etm_options as $id => $cf7etm_name ) :
												?>
												<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( $cf7etm_chosen, $id ); ?>>
													<?php echo esc_html( $cf7etm_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>

										<div class="cf7etm-assign__actions">
											<button type="button" class="cf7etm-btn cf7etm-btn--small cf7etm-btn--primary" data-action="apply">
												<?php esc_html_e( 'Apply Template', 'cf7-email-template-manager' ); ?>
											</button>
											<button type="button" class="cf7etm-btn cf7etm-btn--small" data-action="detach" <?php disabled( ! $cf7etm_assigned ); ?>>
												<?php esc_html_e( 'Detach', 'cf7-email-template-manager' ); ?>
											</button>
										</div>

										<?php if ( $cf7etm_assigned ) : ?>
											<p class="cf7etm-help">
												<a href="<?php echo esc_url( CF7ETM_Plugin::url( 'template-edit', array( 'template' => $cf7etm_assigned ) ) ); ?>">
													<?php esc_html_e( 'Edit template', 'cf7-email-template-manager' ); ?>
												</a>
											</p>
											<?php
											$cf7etm_assigned_template = CF7ETM_Template_Post_Type::get( $cf7etm_assigned );

											// Only active templates take over a live form, so an
											// inactive one here means Contact Form 7 is still sending.
											if ( $cf7etm_assigned_template && 'publish' !== $cf7etm_assigned_template['status'] ) :
												?>
												<p class="cf7etm-alert cf7etm-alert--warning">
													<?php
													printf(
														/* translators: %s: status label, e.g. Draft */
														esc_html__( 'This template is %s, so Contact Form 7 is still sending this email. Set it to Active to use it.', 'cf7-email-template-manager' ),
														esc_html( CF7ETM_Template_Post_Type::status_label( $cf7etm_assigned_template['status'] ) )
													);
													?>
												</p>
												<?php
											endif;

											// Mailing the visitor's own upload back to them is
											// occasionally wanted and often a mistake. Warn, never block.
											if (
												'customer' === $cf7etm_slot
												&& $cf7etm_assigned_template
												&& '' !== trim( (string) $cf7etm_assigned_template['attachments'] )
											) :
												?>
												<p class="cf7etm-alert cf7etm-alert--warning">
													<?php esc_html_e( 'This template attaches the visitor’s uploaded files, and this email goes to the visitor.', 'cf7-email-template-manager' ); ?>
												</p>
											<?php endif; ?>
										<?php elseif ( 'customer' === $cf7etm_slot ) : ?>
											<p class="cf7etm-help"><?php esc_html_e( 'Optional. Sends a confirmation to the visitor.', 'cf7-email-template-manager' ); ?></p>
										<?php endif; ?>
									</div>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	<?php endif; ?>

</div>
