<?php
/**
 * Templates list screen.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

require_once CF7ETM_DIR . 'admin/class-templates-list-table.php';

$table = new CF7ETM_Templates_List_Table();
$table->prepare_items();

$has_any = CF7ETM_Template_Post_Type::counts()['total'] > 0;
?>
<div class="wrap cf7etm">

	<?php
	CF7ETM_Admin::header(
		__( 'Email Templates', 'cf7-email-template-manager' ),
		sprintf(
			'<a class="cf7etm-btn cf7etm-btn--primary" href="%s"><span class="dashicons dashicons-plus-alt2"></span>%s</a>',
			esc_url( CF7ETM_Plugin::url( 'template-edit' ) ),
			esc_html__( 'Add New Template', 'cf7-email-template-manager' )
		)
	);

	CF7ETM_Admin::flash();
	?>

	<?php if ( ! $has_any ) : ?>

		<div class="cf7etm-empty">
			<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
			<h2><?php esc_html_e( 'No templates yet.', 'cf7-email-template-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create your first reusable Contact Form 7 email template.', 'cf7-email-template-manager' ); ?></p>
			<a class="cf7etm-btn cf7etm-btn--primary" href="<?php echo esc_url( CF7ETM_Plugin::url( 'template-edit' ) ); ?>">
				<?php esc_html_e( 'Create Template', 'cf7-email-template-manager' ); ?>
			</a>
		</div>

	<?php else : ?>

		<div class="cf7etm-card cf7etm-card--flush cf7etm-list">
			<form method="get">
				<input type="hidden" name="page" value="cf7etm-templates" />
				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter, preserved across search.
				$filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : '';

				if ( $filter ) {
					printf( '<input type="hidden" name="filter" value="%s" />', esc_attr( $filter ) );
				}

				$table->views();
				$table->search_box( __( 'Search templates', 'cf7-email-template-manager' ), 'cf7etm-search' );
				?>
			</form>

			<form method="post">
				<?php
				// WP_List_Table::display() emits the bulk-action nonce itself.
				$table->display();
				?>
			</form>

			<?php if ( ! $table->has_items() ) : ?>
				<div class="cf7etm-empty cf7etm-empty--inline">
					<h2><?php esc_html_e( 'Nothing matches that filter.', 'cf7-email-template-manager' ); ?></h2>
					<p><?php esc_html_e( 'Try a different search term or clear the filter.', 'cf7-email-template-manager' ); ?></p>
					<a class="cf7etm-btn" href="<?php echo esc_url( CF7ETM_Plugin::url( 'templates' ) ); ?>">
						<?php esc_html_e( 'Clear filters', 'cf7-email-template-manager' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

	<?php endif; ?>

</div>
