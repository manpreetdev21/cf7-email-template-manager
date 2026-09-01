<?php
/**
 * Submissions screen: the log of everything visitors have sent.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

require_once CF7ETM_DIR . 'admin/class-submissions-list-table.php';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading which entry to show.
$entry_id = isset( $_GET['entry'] ) && ! isset( $_GET['entry_action'] ) ? absint( $_GET['entry'] ) : 0;

$entry = $entry_id ? CF7ETM_Submissions::get( $entry_id ) : null;
?>
<div class="wrap cf7etm">

	<?php if ( $entry ) : ?>

		<?php
		CF7ETM_Admin::header(
			__( 'Submission', 'cf7-email-template-manager' ),
			sprintf(
				'<a class="cf7etm-btn" href="%s">%s</a>',
				esc_url( CF7ETM_Plugin::url( 'submissions', array( 'form' => $entry['form_id'] ) ) ),
				esc_html__( 'Back to submissions', 'cf7-email-template-manager' )
			)
		);

		CF7ETM_Admin::flash();

		$stamp = strtotime( $entry['submitted_at'] );
		?>

		<div class="cf7etm-card cf7etm-card--flush">
			<table class="cf7etm-entry">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Form', 'cf7-email-template-manager' ); ?></th>
						<td><?php echo esc_html( $entry['form_title'] ? $entry['form_title'] : sprintf( '#%d', $entry['form_id'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Submitted', 'cf7-email-template-manager' ); ?></th>
						<td><?php echo esc_html( $stamp ? wp_date( 'Y-m-d H:i:s', $stamp ) : $entry['submitted_at'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email', 'cf7-email-template-manager' ); ?></th>
						<td>
							<span class="cf7etm-badge cf7etm-badge--<?php echo 'sent' === $entry['status'] ? 'success' : 'danger'; ?>">
								<?php
								echo 'sent' === $entry['status']
									? esc_html__( 'Sent', 'cf7-email-template-manager' )
									: esc_html__( 'Not sent', 'cf7-email-template-manager' );
								?>
							</span>
						</td>
					</tr>
					<?php if ( $entry['remote_ip'] ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'IP address', 'cf7-email-template-manager' ); ?></th>
							<td><?php echo esc_html( $entry['remote_ip'] ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="cf7etm-card cf7etm-card--flush">
			<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Submitted Data', 'cf7-email-template-manager' ); ?></h2></div>

			<?php if ( ! $entry['fields'] && ! $entry['files'] ) : ?>
				<p class="cf7etm-muted"><?php esc_html_e( 'This submission had no fields.', 'cf7-email-template-manager' ); ?></p>
			<?php else : ?>
				<table class="cf7etm-entry">
					<tbody>
						<?php foreach ( $entry['fields'] as $name => $value ) : ?>
							<tr>
								<th scope="row">
									<?php echo esc_html( CF7ETM_CF7_Bridge::friendly_label( $name ) ); ?>
									<code>[<?php echo esc_html( $name ); ?>]</code>
								</th>
								<td>
									<?php $flat = CF7ETM_Submissions::flatten( $value ); ?>
									<?php if ( '' === trim( $flat ) ) : ?>
										<span class="cf7etm-muted">&mdash;</span>
									<?php else : ?>
										<div class="cf7etm-entry__value"><?php echo nl2br( esc_html( $flat ) ); ?></div>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>

						<?php foreach ( CF7ETM_Submissions::files( $entry ) as $name => $files ) : ?>
							<tr>
								<th scope="row">
									<?php echo esc_html( CF7ETM_CF7_Bridge::friendly_label( $name ) ); ?>
									<code>[<?php echo esc_html( $name ); ?>]</code>
								</th>
								<td>
									<ul class="cf7etm-list-plain">
										<?php foreach ( $files as $file ) : ?>
											<li>
												<?php if ( '' !== $file['path'] && CF7ETM_Submissions::file_path( $file['path'] ) ) : ?>
													<a class="cf7etm-btn cf7etm-btn--small"
														href="<?php echo esc_url( CF7ETM_Submissions::download_url( $entry['id'], $name, $file['index'] ) ); ?>">
														<span class="dashicons dashicons-download" aria-hidden="true"></span>
														<?php echo esc_html( $file['name'] ); ?>
													</a>
													<?php if ( $file['size'] ) : ?>
														<span class="cf7etm-muted"><?php echo esc_html( size_format( $file['size'] ) ); ?></span>
													<?php endif; ?>
												<?php else : ?>
													<?php echo esc_html( $file['name'] ); ?>
													<span class="cf7etm-muted">
														<?php
														echo 'type' === $file['error']
															? esc_html__( '— not stored, WordPress does not allow this file type', 'cf7-email-template-manager' )
															: esc_html__( '— file no longer on disk', 'cf7-email-template-manager' );
														?>
													</span>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

	<?php else : ?>

		<?php
		$table = new CF7ETM_Submissions_List_Table();
		$table->prepare_items();

		CF7ETM_Admin::header( __( 'Form Submissions', 'cf7-email-template-manager' ) );
		CF7ETM_Admin::flash();
		?>

		<div class="cf7etm-card cf7etm-card--flush cf7etm-list">
			<form method="get">
				<input type="hidden" name="page" value="cf7etm-submissions" />
				<input type="hidden" name="form" value="<?php echo esc_attr( (string) $table->current_form() ); ?>" />
				<?php
				$table->views();
				$table->search_box( __( 'Search submissions', 'cf7-email-template-manager' ), 'cf7etm-entry-search' );
				?>
			</form>

			<form method="post">
				<input type="hidden" name="page" value="cf7etm-submissions" />
				<input type="hidden" name="form" value="<?php echo esc_attr( (string) $table->current_form() ); ?>" />
				<div class="cf7etm-list__scroll">
					<?php $table->display(); ?>
				</div>
			</form>
		</div>

		<p class="cf7etm-muted">
			<?php esc_html_e( 'Every submission that passes Contact Form 7\'s validation and spam checks is stored here, whether or not the email went out.', 'cf7-email-template-manager' ); ?>
		</p>

	<?php endif; ?>

</div>
