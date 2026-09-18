<?php
/**
 * One submission's answers and files, as a label/value table.
 *
 * Shared by the submission screen and the expandable row in the list, so both
 * show exactly the same thing.
 *
 * @package CF7_Email_Template_Manager
 *
 * @var array $entry Submission, as returned by CF7ETM_Submissions::get().
 */

defined( 'ABSPATH' ) || exit;

$entry_answers = CF7ETM_Submissions::answers( $entry );
$entry_files   = CF7ETM_Submissions::files( $entry );

if ( ! $entry_answers && ! $entry_files ) :
	?>
	<p class="cf7etm-muted"><?php esc_html_e( 'This submission had no fields.', 'cf7-email-template-manager' ); ?></p>
	<?php
	return;
endif;
?>
<table class="cf7etm-entry">
	<tbody>
		<?php foreach ( $entry_answers as $entry_name => $entry_value ) : ?>
			<tr>
				<th scope="row">
					<?php echo esc_html( CF7ETM_CF7_Bridge::friendly_label( $entry_name ) ); ?>
					<code>[<?php echo esc_html( $entry_name ); ?>]</code>
				</th>
				<td>
					<?php $entry_flat = CF7ETM_Submissions::flatten( $entry_value ); ?>
					<?php if ( '' === trim( $entry_flat ) ) : ?>
						<span class="cf7etm-muted">&mdash;</span>
					<?php else : ?>
						<div class="cf7etm-entry__value"><?php echo nl2br( esc_html( $entry_flat ) ); ?></div>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>

		<?php foreach ( $entry_files as $entry_name => $entry_list ) : ?>
			<tr>
				<th scope="row">
					<?php echo esc_html( CF7ETM_CF7_Bridge::friendly_label( $entry_name ) ); ?>
					<code>[<?php echo esc_html( $entry_name ); ?>]</code>
				</th>
				<td>
					<ul class="cf7etm-list-plain">
						<?php foreach ( $entry_list as $entry_file ) : ?>
							<li>
								<?php if ( '' !== $entry_file['path'] && CF7ETM_Submissions::file_path( $entry_file['path'] ) ) : ?>
									<a class="cf7etm-btn cf7etm-btn--small"
										href="<?php echo esc_url( CF7ETM_Submissions::download_url( $entry['id'], $entry_name, $entry_file['index'] ) ); ?>">
										<span class="dashicons dashicons-download" aria-hidden="true"></span>
										<?php echo esc_html( $entry_file['name'] ); ?>
									</a>
									<?php if ( $entry_file['size'] ) : ?>
										<span class="cf7etm-muted"><?php echo esc_html( size_format( $entry_file['size'] ) ); ?></span>
									<?php endif; ?>
								<?php else : ?>
									<?php echo esc_html( $entry_file['name'] ); ?>
									<span class="cf7etm-muted">
										<?php
										echo 'type' === $entry_file['error']
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
