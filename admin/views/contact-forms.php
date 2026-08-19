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

$forms       = CF7ETM_CF7_Bridge::forms();
$assignments = CF7ETM_CF7_Bridge::assignments();

$slots = array(
	'admin'    => __( 'Admin Email', 'cf7-email-template-manager' ),
	'customer' => __( 'Customer Email', 'cf7-email-template-manager' ),
);
?>
<div class="wrap cf7etm">

	<?php
	CF7ETM_Admin::header( __( 'Contact Forms', 'cf7-email-template-manager' ) );
	CF7ETM_Admin::flash();
	?>

	<?php if ( ! $forms ) : ?>

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
						<?php foreach ( $slots as $label ) : ?>
							<th scope="col"><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $forms as $form_id => $title ) :
						$tags    = CF7ETM_CF7_Bridge::form_tags( $form_id );
						$files   = CF7ETM_CF7_Bridge::file_fields( $form_id );
						$current = $assignments[ $form_id ] ?? array();
						?>
						<tr>
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

							<td data-label="<?php esc_attr_e( 'Fields', 'cf7-email-template-manager' ); ?>">
								<?php if ( $tags ) : ?>
									<div class="cf7etm-muted">
										<?php
										$names = array();

										foreach ( $tags as $tag ) {
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
								<?php if ( $files ) : ?>
									<?php foreach ( $files as $name ) : ?>
										<span class="cf7etm-badge cf7etm-badge--info"><?php echo esc_html( '[' . $name . ']' ); ?></span>
									<?php endforeach; ?>
								<?php else : ?>
									<span class="cf7etm-muted"><?php esc_html_e( 'None', 'cf7-email-template-manager' ); ?></span>
								<?php endif; ?>
							</td>

							<?php
							foreach ( $slots as $slot => $label ) :
								$assigned = (int) ( $current[ $slot ] ?? 0 );
								$template = $assigned ? CF7ETM_Template_Post_Type::get( $assigned ) : null;
								?>
								<td data-label="<?php echo esc_attr( $label ); ?>">
									<?php if ( $template ) : ?>
										<a href="<?php echo esc_url( CF7ETM_Plugin::url( 'template-edit', array( 'template' => $assigned ) ) ); ?>">
											<?php echo esc_html( $template['name'] ); ?>
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
