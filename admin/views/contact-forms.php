<?php
/**
 * Contact Forms overview: what each form offers, and which templates it uses.
 *
 * Read-only. Everything here is detected from Contact Form 7's own API, so a
 * form that gains a field shows the change without anything being saved.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

$cf7etm_forms       = CF7ETM_CF7_Bridge::forms();
$cf7etm_assignments = CF7ETM_CF7_Bridge::assignments();

$cf7etm_slots = array(
	'admin'    => __( 'Admin Email', 'cf7-email-template-manager' ),
	'customer' => __( 'Customer Email', 'cf7-email-template-manager' ),
);
?>
<div class="wrap cf7etm">

	<?php
	CF7ETM_Admin::header( __( 'Contact Forms', 'cf7-email-template-manager' ) );
	CF7ETM_Admin::flash();
	?>

	<?php if ( ! $cf7etm_forms ) : ?>

		<div class="cf7etm-empty">
			<span class="dashicons dashicons-feedback" aria-hidden="true"></span>
			<h2><?php esc_html_e( 'No contact forms found.', 'cf7-email-template-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create a form in Contact Form 7 and its fields will be detected here automatically.', 'cf7-email-template-manager' ); ?></p>
			<a class="cf7etm-btn cf7etm-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7-new' ) ); ?>">
				<?php esc_html_e( 'Create a contact form', 'cf7-email-template-manager' ); ?>
			</a>
		</div>

	<?php else : ?>

		<div class="cf7etm-card cf7etm-card--flush">
			<table class="cf7etm-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Contact Form', 'cf7-email-template-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Fields', 'cf7-email-template-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'File Uploads', 'cf7-email-template-manager' ); ?></th>
						<?php foreach ( $cf7etm_slots as $cf7etm_label ) : ?>
							<th scope="col"><?php echo esc_html( $cf7etm_label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $cf7etm_forms as $cf7etm_form_id => $title ) :
						$cf7etm_tags    = CF7ETM_CF7_Bridge::form_tags( $cf7etm_form_id );
						$cf7etm_files   = CF7ETM_CF7_Bridge::file_fields( $cf7etm_form_id );
						$cf7etm_current = $cf7etm_assignments[ $cf7etm_form_id ] ?? array();
						?>
						<tr>
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

							<td data-label="<?php esc_attr_e( 'Fields', 'cf7-email-template-manager' ); ?>">
								<?php if ( $cf7etm_tags ) : ?>
									<div class="cf7etm-muted">
										<?php
										$names = array();

										foreach ( $cf7etm_tags as $tag ) {
											if ( empty( $tag['is_file'] ) ) {
												$names[] = '[' . $tag['name'] . ']';
											}
										}

										echo esc_html( $names ? implode( ', ', $names ) : __( 'None', 'cf7-email-template-manager' ) );
										?>
									</div>
								<?php else : ?>
									<span class="cf7etm-muted"><?php esc_html_e( 'None', 'cf7-email-template-manager' ); ?></span>
								<?php endif; ?>
							</td>

							<td data-label="<?php esc_attr_e( 'File Uploads', 'cf7-email-template-manager' ); ?>">
								<?php if ( $cf7etm_files ) : ?>
									<?php foreach ( $cf7etm_files as $cf7etm_name ) : ?>
										<span class="cf7etm-badge cf7etm-badge--info"><?php echo esc_html( '[' . $cf7etm_name . ']' ); ?></span>
									<?php endforeach; ?>
								<?php else : ?>
									<span class="cf7etm-muted"><?php esc_html_e( 'None', 'cf7-email-template-manager' ); ?></span>
								<?php endif; ?>
							</td>

							<?php
							foreach ( $cf7etm_slots as $cf7etm_slot => $cf7etm_label ) :
								$cf7etm_assigned = (int) ( $cf7etm_current[ $cf7etm_slot ] ?? 0 );
								$cf7etm_template = $cf7etm_assigned ? CF7ETM_Template_Post_Type::get( $cf7etm_assigned ) : null;
								?>
								<td data-label="<?php echo esc_attr( $cf7etm_label ); ?>">
									<?php if ( $cf7etm_template ) : ?>
										<a href="<?php echo esc_url( CF7ETM_Plugin::url( 'template-edit', array( 'template' => $cf7etm_assigned ) ) ); ?>">
											<?php echo esc_html( $cf7etm_template['name'] ); ?>
										</a>
									<?php else : ?>
										<span class="cf7etm-muted"><?php esc_html_e( 'Contact Form 7 default', 'cf7-email-template-manager' ); ?></span>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<p class="cf7etm-help">
			<a class="cf7etm-btn" href="<?php echo esc_url( CF7ETM_Plugin::url( 'assignments' ) ); ?>">
				<?php esc_html_e( 'Manage assignments', 'cf7-email-template-manager' ); ?>
			</a>
		</p>

	<?php endif; ?>

</div>
