<?php
/**
 * Assignments screen: which template each contact form uses.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

$forms       = CF7ETM_CF7_Bridge::forms();
$assignments = CF7ETM_CF7_Bridge::assignments();
$options     = CF7ETM_Template_Post_Type::options( true );

$slots = array(
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

	<?php if ( ! $forms ) : ?>

		<div class="cf7etm-empty">
			<span class="dashicons dashicons-feedback" aria-hidden="true"></span>
			<h2><?php esc_html_e( 'No contact forms found.', 'cf7-email-template-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create a form in Contact Form 7 first, then come back to assign a template to it.', 'cf7-email-template-manager' ); ?></p>
			<a class="cf7etm-btn cf7etm-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7-new' ) ); ?>">
				<?php esc_html_e( 'Create a contact form', 'cf7-email-template-manager' ); ?>
			</a>
		</div>

	<?php elseif ( ! $options ) : ?>

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
						<?php foreach ( $slots as $label ) : ?>
							<th scope="col"><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $forms as $form_id => $title ) : ?>
						<?php $current = $assignments[ $form_id ] ?? array(); ?>
						<tr data-form-id="<?php echo esc_attr( (string) $form_id ); ?>">
							<td data-label="<?php esc_attr_e( 'Contact Form', 'cf7-email-template-manager' ); ?>">
								<strong><?php echo esc_html( $title ); ?></strong>
								<?php if ( $current ) : ?>
									<span class="cf7etm-badge cf7etm-badge--success"><?php esc_html_e( 'Managed', 'cf7-email-template-manager' ); ?></span>
								<?php endif; ?>
								<div class="cf7etm-muted">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7&post=' . $form_id . '&action=edit' ) ); ?>">
										<?php esc_html_e( 'Edit in Contact Form 7', 'cf7-email-template-manager' ); ?>
									</a>
								</div>
							</td>

							<?php foreach ( $slots as $slot => $label ) : ?>
								<?php $assigned = (int) ( $current[ $slot ] ?? 0 ); ?>
								<td data-label="<?php echo esc_attr( $label ); ?>">
									<div class="cf7etm-assign" data-slot="<?php echo esc_attr( $slot ); ?>">
										<label class="screen-reader-text" for="cf7etm-select-<?php echo esc_attr( $form_id . '-' . $slot ); ?>">
											<?php
											printf(
												/* translators: 1: slot label, 2: form title */
												esc_html__( '%1$s for %2$s', 'cf7-email-template-manager' ),
												esc_html( $label ),
												esc_html( $title )
											);
											?>
										</label>
										<select id="cf7etm-select-<?php echo esc_attr( $form_id . '-' . $slot ); ?>" data-template-select>
											<option value="0"><?php esc_html_e( '— No template —', 'cf7-email-template-manager' ); ?></option>
											<?php foreach ( $options as $id => $name ) : ?>
												<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( $assigned, $id ); ?>>
													<?php echo esc_html( $name ); ?>
												</option>
											<?php endforeach; ?>
										</select>

										<div class="cf7etm-assign__actions">
											<button type="button" class="cf7etm-btn cf7etm-btn--small cf7etm-btn--primary" data-action="apply">
												<?php esc_html_e( 'Apply Template', 'cf7-email-template-manager' ); ?>
											</button>
											<button type="button" class="cf7etm-btn cf7etm-btn--small" data-action="detach" <?php disabled( ! $assigned ); ?>>
												<?php esc_html_e( 'Detach', 'cf7-email-template-manager' ); ?>
											</button>
										</div>

										<?php if ( $assigned ) : ?>
											<p class="cf7etm-help">
												<a href="<?php echo esc_url( CF7ETM_Plugin::url( 'template-edit', array( 'template' => $assigned ) ) ); ?>">
													<?php esc_html_e( 'Edit template', 'cf7-email-template-manager' ); ?>
												</a>
											</p>
										<?php elseif ( 'customer' === $slot ) : ?>
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
