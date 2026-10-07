<?php
/**
 * Submissions screen: the log of everything visitors have sent.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

require_once CF7ETM_DIR . 'admin/class-submissions-list-table.php';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading which entry to show.
$cf7etm_entry_id = isset( $_GET['entry'] ) && ! isset( $_GET['entry_action'] ) ? absint( $_GET['entry'] ) : 0;

$cf7etm_entry = $cf7etm_entry_id ? CF7ETM_Submissions::get( $cf7etm_entry_id ) : null;
?>
<div class="wrap cf7etm">

	<?php if ( $cf7etm_entry ) : ?>

		<?php
		CF7ETM_Admin::header(
			__( 'Submission', 'cf7-email-template-manager' ),
			sprintf(
				'<a class="cf7etm-btn" href="%s">%s</a>',
				esc_url( CF7ETM_Plugin::url( 'submissions', array( 'form' => $cf7etm_entry['form_id'] ) ) ),
				esc_html__( 'Back to submissions', 'cf7-email-template-manager' )
			)
		);

		CF7ETM_Admin::flash();

		$cf7etm_stamp = strtotime( $cf7etm_entry['submitted_at'] );
		?>

		<div class="cf7etm-card cf7etm-card--flush">
			<table class="cf7etm-entry">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Form', 'cf7-email-template-manager' ); ?></th>
						<td><?php echo esc_html( $cf7etm_entry['form_title'] ? $cf7etm_entry['form_title'] : sprintf( '#%d', $cf7etm_entry['form_id'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Submitted', 'cf7-email-template-manager' ); ?></th>
						<td><?php echo esc_html( $cf7etm_stamp ? wp_date( 'Y-m-d H:i:s', $cf7etm_stamp ) : $cf7etm_entry['submitted_at'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email', 'cf7-email-template-manager' ); ?></th>
						<td>
							<span class="cf7etm-badge cf7etm-badge--<?php echo 'sent' === $cf7etm_entry['status'] ? 'success' : 'danger'; ?>">
								<?php
								echo 'sent' === $cf7etm_entry['status']
									? esc_html__( 'Sent', 'cf7-email-template-manager' )
									: esc_html__( 'Not sent', 'cf7-email-template-manager' );
								?>
							</span>
						</td>
					</tr>
					<?php if ( $cf7etm_entry['remote_ip'] ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'IP address', 'cf7-email-template-manager' ); ?></th>
							<td><?php echo esc_html( $cf7etm_entry['remote_ip'] ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="cf7etm-card cf7etm-card--flush">
			<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Submitted Data', 'cf7-email-template-manager' ); ?></h2></div>

			<?php require CF7ETM_DIR . 'admin/views/partial-entry-data.php'; ?>
		</div>

	<?php else : ?>

		<?php
		$cf7etm_table = new CF7ETM_Submissions_List_Table();
		$cf7etm_table->prepare_items();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filters, carried into the export link.
		$cf7etm_export_args = array_filter(
			array(
				'action' => 'cf7etm_export_entries',
				'form'   => $cf7etm_table->current_form(),
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter, carried into the export link.
				'status' => isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : '',
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter, carried into the export link.
				's'      => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
			),
			static fn( $value ) => '' !== $value && 0 !== $value
		);

		CF7ETM_Admin::header(
			__( 'Form Submissions', 'cf7-email-template-manager' ),
			$cf7etm_table->has_items()
				? sprintf(
					'<a class="cf7etm-btn" href="%s"><span class="dashicons dashicons-media-spreadsheet"></span>%s</a>',
					esc_url( wp_nonce_url( add_query_arg( $cf7etm_export_args, admin_url( 'admin-post.php' ) ), 'cf7etm_export_entries' ) ),
					esc_html__( 'Export CSV', 'cf7-email-template-manager' )
				)
				: ''
		);

		CF7ETM_Admin::flash();
		?>

		<div class="cf7etm-card cf7etm-card--flush cf7etm-list">
			<form method="get">
				<input type="hidden" name="page" value="cf7etm-submissions" />
				<input type="hidden" name="form" value="<?php echo esc_attr( (string) $cf7etm_table->current_form() ); ?>" />
				<?php
				$cf7etm_table->views();
				$cf7etm_table->search_box( __( 'Search submissions', 'cf7-email-template-manager' ), 'cf7etm-entry-search' );
				?>
			</form>

			<form method="post">
				<input type="hidden" name="page" value="cf7etm-submissions" />
				<input type="hidden" name="form" value="<?php echo esc_attr( (string) $cf7etm_table->current_form() ); ?>" />
				<div class="cf7etm-list__scroll">
					<?php $cf7etm_table->display(); ?>
				</div>
			</form>
		</div>

		<p class="cf7etm-muted">
			<?php esc_html_e( 'Every submission that passes Contact Form 7\'s validation and spam checks is stored here, whether or not the email went out.', 'cf7-email-template-manager' ); ?>
		</p>

	<?php endif; ?>

</div>
