<?php
/**
 * Dashboard screen.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

$cf7etm_counts       = CF7ETM_Template_Post_Type::counts();
$cf7etm_assigned_ids = CF7ETM_CF7_Bridge::assigned_template_ids();
$cf7etm_forms        = CF7ETM_CF7_Bridge::forms();
$cf7etm_assignments  = CF7ETM_CF7_Bridge::assignments();

$cf7etm_cards = array(
	array(
		'icon'  => 'dashicons-email-alt',
		'count' => $cf7etm_counts['total'],
		'label' => __( 'Total Templates', 'cf7-email-template-manager' ),
		'desc'  => __( 'Every template in your library.', 'cf7-email-template-manager' ),
		'link'  => CF7ETM_Plugin::url( 'templates' ),
		'cta'   => __( 'Manage templates', 'cf7-email-template-manager' ),
	),
	array(
		'icon'  => 'dashicons-yes-alt',
		'count' => $cf7etm_counts['publish'],
		'label' => __( 'Active Templates', 'cf7-email-template-manager' ),
		'desc'  => __( 'Ready to be assigned to a form.', 'cf7-email-template-manager' ),
		'link'  => CF7ETM_Plugin::url( 'templates', array( 'filter' => 'publish' ) ),
		'cta'   => __( 'View active', 'cf7-email-template-manager' ),
	),
	array(
		'icon'  => 'dashicons-admin-links',
		'count' => count( $cf7etm_assignments ),
		'label' => __( 'Assigned Forms', 'cf7-email-template-manager' ),
		'desc'  => __( 'Contact forms using a template.', 'cf7-email-template-manager' ),
		'link'  => CF7ETM_Plugin::url( 'assignments' ),
		'cta'   => __( 'View assignments', 'cf7-email-template-manager' ),
	),
	array(
		'icon'  => 'dashicons-archive',
		'count' => max( 0, $cf7etm_counts['total'] - count( $cf7etm_assigned_ids ) ),
		'label' => __( 'Unused Templates', 'cf7-email-template-manager' ),
		'desc'  => __( 'Not assigned to any form yet.', 'cf7-email-template-manager' ),
		'link'  => CF7ETM_Plugin::url( 'templates', array( 'filter' => 'unused' ) ),
		'cta'   => __( 'View unused', 'cf7-email-template-manager' ),
	),
	array(
		'icon'  => 'dashicons-feedback',
		'count' => count( $cf7etm_forms ),
		'label' => __( 'Contact Forms', 'cf7-email-template-manager' ),
		'desc'  => __( 'Forms detected in Contact Form 7.', 'cf7-email-template-manager' ),
		'link'  => admin_url( 'admin.php?page=wpcf7' ),
		'cta'   => __( 'Open Contact Form 7', 'cf7-email-template-manager' ),
	),
	array(
		'icon'  => 'dashicons-editor-code',
		'count' => CF7ETM_Template_Post_Type::count_by_type( 'html' ),
		'label' => __( 'HTML Templates', 'cf7-email-template-manager' ),
		'desc'  => __( 'Designed, branded email layouts.', 'cf7-email-template-manager' ),
		'link'  => CF7ETM_Plugin::url( 'templates', array( 'filter' => 'html' ) ),
		'cta'   => __( 'View HTML', 'cf7-email-template-manager' ),
	),
	array(
		'icon'  => 'dashicons-editor-alignleft',
		'count' => CF7ETM_Template_Post_Type::count_by_type( 'text' ),
		'label' => __( 'Plain Text Templates', 'cf7-email-template-manager' ),
		'desc'  => __( 'Readable in every mail client.', 'cf7-email-template-manager' ),
		'link'  => CF7ETM_Plugin::url( 'templates', array( 'filter' => 'text' ) ),
		'cta'   => __( 'View plain text', 'cf7-email-template-manager' ),
	),
	array(
		'icon'  => 'dashicons-paperclip',
		'count' => CF7ETM_Template_Post_Type::count_with_files(),
		'label' => __( 'File Upload Templates', 'cf7-email-template-manager' ),
		'desc'  => __( 'Templates that attach uploaded files.', 'cf7-email-template-manager' ),
		'link'  => CF7ETM_Plugin::url( 'templates', array( 'filter' => 'files' ) ),
		'cta'   => __( 'View file templates', 'cf7-email-template-manager' ),
	),
);
?>
<div class="wrap cf7etm">

	<?php
	CF7ETM_Admin::header(
		__( 'Dashboard', 'cf7-email-template-manager' ),
		sprintf(
			'<a class="cf7etm-btn cf7etm-btn--primary" href="%s"><span class="dashicons dashicons-plus-alt2"></span>%s</a>',
			esc_url( CF7ETM_Plugin::url( 'template-edit' ) ),
			esc_html__( 'Add New Template', 'cf7-email-template-manager' )
		)
	);

	CF7ETM_Admin::flash();
	?>

	<?php if ( 0 === $cf7etm_counts['total'] ) : ?>

		<div class="cf7etm-empty">
			<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
			<h2><?php esc_html_e( 'No templates yet.', 'cf7-email-template-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create your first reusable Contact Form 7 email template.', 'cf7-email-template-manager' ); ?></p>
			<a class="cf7etm-btn cf7etm-btn--primary" href="<?php echo esc_url( CF7ETM_Plugin::url( 'template-edit' ) ); ?>">
				<?php esc_html_e( 'Create Template', 'cf7-email-template-manager' ); ?>
			</a>
		</div>

	<?php else : ?>

		<div class="cf7etm-grid">
			<?php foreach ( $cf7etm_cards as $cf7etm_card ) : ?>
				<div class="cf7etm-card cf7etm-stat">
					<div class="cf7etm-stat__top">
						<span class="dashicons <?php echo esc_attr( $cf7etm_card['icon'] ); ?>" aria-hidden="true"></span>
						<span class="cf7etm-stat__count"><?php echo esc_html( number_format_i18n( $cf7etm_card['count'] ) ); ?></span>
					</div>
					<h2 class="cf7etm-stat__label"><?php echo esc_html( $cf7etm_card['label'] ); ?></h2>
					<p class="cf7etm-muted"><?php echo esc_html( $cf7etm_card['desc'] ); ?></p>
					<a class="cf7etm-stat__link" href="<?php echo esc_url( $cf7etm_card['link'] ); ?>">
						<?php echo esc_html( $cf7etm_card['cta'] ); ?> <span aria-hidden="true">&rarr;</span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>

		<?php
		$cf7etm_recent = get_posts(
			array(
				'post_type'      => CF7ETM_Template_Post_Type::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 5,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		?>

		<?php if ( $cf7etm_recent ) : ?>
			<div class="cf7etm-card cf7etm-card--flush">
				<div class="cf7etm-card__head">
					<h2><?php esc_html_e( 'Recently updated', 'cf7-email-template-manager' ); ?></h2>
					<a href="<?php echo esc_url( CF7ETM_Plugin::url( 'templates' ) ); ?>">
						<?php esc_html_e( 'View all', 'cf7-email-template-manager' ); ?>
					</a>
				</div>
				<table class="cf7etm-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Template', 'cf7-email-template-manager' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'cf7-email-template-manager' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Updated', 'cf7-email-template-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $cf7etm_recent as $post ) : ?>
							<?php
							$cf7etm_modifier = match ( $post->post_status ) {
								'publish' => 'success',
								'private' => 'neutral',
								default   => 'warning',
							};
							?>
							<tr>
								<td data-label="<?php esc_attr_e( 'Template', 'cf7-email-template-manager' ); ?>">
									<a href="<?php echo esc_url( CF7ETM_Plugin::url( 'template-edit', array( 'template' => $post->ID ) ) ); ?>">
										<?php echo esc_html( $post->post_title ); ?>
									</a>
								</td>
								<td data-label="<?php esc_attr_e( 'Status', 'cf7-email-template-manager' ); ?>">
									<span class="cf7etm-badge cf7etm-badge--<?php echo esc_attr( $cf7etm_modifier ); ?>">
										<?php echo esc_html( CF7ETM_Template_Post_Type::status_label( $post->post_status ) ); ?>
									</span>
								</td>
								<td data-label="<?php esc_attr_e( 'Updated', 'cf7-email-template-manager' ); ?>">
									<?php
									$cf7etm_timestamp = get_post_timestamp( $post, 'modified' );

									echo esc_html(
										$cf7etm_timestamp
											? sprintf(
												/* translators: %s: human-readable time difference */
												__( '%s ago', 'cf7-email-template-manager' ),
												human_time_diff( $cf7etm_timestamp )
											)
											: '—'
									);
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

	<?php endif; ?>

	<?php if ( ! $cf7etm_forms ) : ?>
		<div class="cf7etm-alert cf7etm-alert--warning">
			<?php
			printf(
				/* translators: %s: link to Contact Form 7 */
				esc_html__( 'No contact forms found yet. %s to create one before assigning templates.', 'cf7-email-template-manager' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=wpcf7-new' ) ) . '">' . esc_html__( 'Open Contact Form 7', 'cf7-email-template-manager' ) . '</a>'
			);
			?>
		</div>
	<?php endif; ?>

</div>
