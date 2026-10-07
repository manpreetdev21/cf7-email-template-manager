<?php
/**
 * One submission's answers and files, as a label/value table.
 *
 * Shared by the submission screen and the expandable row in the list, so both
 * show exactly the same thing.
 *
 * @package CF7_Email_Template_Manager
 *
 * @var array $cf7etm_entry Submission, as returned by CF7ETM_Submissions::get().
 */

defined( 'ABSPATH' ) || exit;

$cf7etm_entry_answers = CF7ETM_Submissions::answers( $cf7etm_entry );
$cf7etm_entry_files   = CF7ETM_Submissions::files( $cf7etm_entry );

if ( ! $cf7etm_entry_answers && ! $cf7etm_entry_files ) :
	?>
	<p class="cf7etm-muted"><?php esc_html_e( 'This submission had no fields.', 'cf7-email-template-manager' ); ?></p>
	<?php
	return;
endif;
?>
<table class="cf7etm-entry">
	<tbody>
		<?php foreach ( $cf7etm_entry_answers as $cf7etm_entry_name => $cf7etm_entry_value ) : ?>
			<tr>
				<th scope="row">
					<?php echo esc_html( CF7ETM_CF7_Bridge::friendly_label( $cf7etm_entry_name ) ); ?>
					<code>[<?php echo esc_html( $cf7etm_entry_name ); ?>]</code>
				</th>
				<td>
					<?php $cf7etm_entry_flat = CF7ETM_Submissions::flatten( $cf7etm_entry_value ); ?>
					<?php if ( '' === trim( $cf7etm_entry_flat ) ) : ?>
						<span class="cf7etm-muted">&mdash;</span>
					<?php else : ?>
						<div class="cf7etm-entry__value"><?php echo nl2br( esc_html( $cf7etm_entry_flat ) ); ?></div>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>

		<?php foreach ( $cf7etm_entry_files as $cf7etm_entry_name => $cf7etm_entry_list ) : ?>
			<tr>
				<th scope="row">
					<?php echo esc_html( CF7ETM_CF7_Bridge::friendly_label( $cf7etm_entry_name ) ); ?>
					<code>[<?php echo esc_html( $cf7etm_entry_name ); ?>]</code>
				</th>
				<td>
					<ul class="cf7etm-list-plain">
						<?php foreach ( $cf7etm_entry_list as $cf7etm_entry_file ) : ?>
							<li>
								<?php if ( '' !== $cf7etm_entry_file['path'] && CF7ETM_Submissions::file_path( $cf7etm_entry_file['path'] ) ) : ?>
									<a class="cf7etm-btn cf7etm-btn--small"
										href="<?php echo esc_url( CF7ETM_Submissions::download_url( $cf7etm_entry['id'], $cf7etm_entry_name, $cf7etm_entry_file['index'] ) ); ?>">
										<span class="dashicons dashicons-download" aria-hidden="true"></span>
										<?php echo esc_html( $cf7etm_entry_file['name'] ); ?>
									</a>
									<?php if ( $cf7etm_entry_file['size'] ) : ?>
										<span class="cf7etm-muted"><?php echo esc_html( size_format( $cf7etm_entry_file['size'] ) ); ?></span>
									<?php endif; ?>
								<?php else : ?>
									<?php echo esc_html( $cf7etm_entry_file['name'] ); ?>
									<span class="cf7etm-muted">
										<?php
										echo 'type' === $cf7etm_entry_file['error']
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
