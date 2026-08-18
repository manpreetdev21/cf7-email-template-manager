<?php
/**
 * Tools screen: import, export, system status and the debug log.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

$log      = array_reverse( (array) get_option( 'cf7etm_log', array() ) );
$counts   = CF7ETM_Template_Post_Type::counts();
$debug_on = (bool) CF7ETM_Plugin::setting( 'debug' );
?>
<div class="wrap cf7etm">

	<?php
	CF7ETM_Admin::header( __( 'Tools', 'cf7-email-template-manager' ) );
	CF7ETM_Admin::flash();
	?>

	<div class="cf7etm-columns">

		<div class="cf7etm-card">
			<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Export', 'cf7-email-template-manager' ); ?></h2></div>
			<p class="cf7etm-muted">
				<?php
				printf(
					/* translators: %d: number of templates */
					esc_html( _n( 'Download all %d template as a JSON file.', 'Download all %d templates as a JSON file.', $counts['total'], 'cf7-email-template-manager' ) ),
					(int) $counts['total']
				);
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cf7etm_export" />
				<?php wp_nonce_field( 'cf7etm_export' ); ?>
				<button type="submit" class="cf7etm-btn" <?php disabled( 0 === $counts['total'] ); ?>>
					<span class="dashicons dashicons-download" aria-hidden="true"></span>
					<?php esc_html_e( 'Export templates', 'cf7-email-template-manager' ); ?>
				</button>
			</form>
		</div>

		<div class="cf7etm-card">
			<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Import', 'cf7-email-template-manager' ); ?></h2></div>
			<p class="cf7etm-muted"><?php esc_html_e( 'Upload a file exported from this plugin. Imported templates arrive as drafts so you can review them before they go live.', 'cf7-email-template-manager' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cf7etm_import" />
				<?php wp_nonce_field( 'cf7etm_import' ); ?>
				<p class="cf7etm-field">
					<label for="cf7etm-import-file"><?php esc_html_e( 'Template file (.json)', 'cf7-email-template-manager' ); ?></label>
					<input type="file" id="cf7etm-import-file" name="import_file" accept="application/json,.json" required />
				</p>
				<button type="submit" class="cf7etm-btn">
					<span class="dashicons dashicons-upload" aria-hidden="true"></span>
					<?php esc_html_e( 'Import templates', 'cf7-email-template-manager' ); ?>
				</button>
			</form>
		</div>

		<div class="cf7etm-card">
			<div class="cf7etm-card__head"><h2><?php esc_html_e( 'System Status', 'cf7-email-template-manager' ); ?></h2></div>
			<table class="cf7etm-table cf7etm-table--plain">
				<tbody>
					<?php
					$rows = array(
						__( 'Plugin version', 'cf7-email-template-manager' )      => CF7ETM_VERSION,
						__( 'WordPress', 'cf7-email-template-manager' )           => get_bloginfo( 'version' ),
						__( 'Contact Form 7', 'cf7-email-template-manager' )      => defined( 'WPCF7_VERSION' ) ? WPCF7_VERSION : __( 'Not active', 'cf7-email-template-manager' ),
						__( 'PHP', 'cf7-email-template-manager' )                 => PHP_VERSION,
						__( 'Templates', 'cf7-email-template-manager' )           => number_format_i18n( $counts['total'] ),
						__( 'Managed forms', 'cf7-email-template-manager' )       => number_format_i18n( count( CF7ETM_CF7_Bridge::assignments() ) ),
						__( 'Debug logging', 'cf7-email-template-manager' )       => $debug_on ? __( 'On', 'cf7-email-template-manager' ) : __( 'Off', 'cf7-email-template-manager' ),
					);

					foreach ( $rows as $label => $value ) :
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td><?php echo esc_html( $value ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	</div>

	<div class="cf7etm-card cf7etm-card--flush">
		<div class="cf7etm-card__head">
			<h2><?php esc_html_e( 'Debug Log', 'cf7-email-template-manager' ); ?></h2>
			<?php if ( $log ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="cf7etm_clear_log" />
					<?php wp_nonce_field( 'cf7etm_clear_log' ); ?>
					<button type="submit" class="cf7etm-btn cf7etm-btn--small"><?php esc_html_e( 'Clear log', 'cf7-email-template-manager' ); ?></button>
				</form>
			<?php endif; ?>
		</div>

		<?php if ( ! $debug_on && ! $log ) : ?>
			<div class="cf7etm-empty cf7etm-empty--inline">
				<h2><?php esc_html_e( 'Logging is off.', 'cf7-email-template-manager' ); ?></h2>
				<p><?php esc_html_e( 'Turn on debug logging in Settings to record which templates were applied.', 'cf7-email-template-manager' ); ?></p>
				<a class="cf7etm-btn" href="<?php echo esc_url( CF7ETM_Plugin::url( 'settings' ) ); ?>">
					<?php esc_html_e( 'Open Settings', 'cf7-email-template-manager' ); ?>
				</a>
			</div>
		<?php elseif ( ! $log ) : ?>
			<div class="cf7etm-empty cf7etm-empty--inline">
				<h2><?php esc_html_e( 'Nothing logged yet.', 'cf7-email-template-manager' ); ?></h2>
				<p><?php esc_html_e( 'Entries appear here after a managed form is submitted.', 'cf7-email-template-manager' ); ?></p>
			</div>
		<?php else : ?>
			<table class="cf7etm-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'When', 'cf7-email-template-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Event', 'cf7-email-template-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_slice( $log, 0, 50 ) as $entry ) : ?>
						<tr>
							<td data-label="<?php esc_attr_e( 'When', 'cf7-email-template-manager' ); ?>" class="cf7etm-nowrap">
								<?php echo esc_html( wp_date( 'Y-m-d H:i:s', (int) $entry['time'] ) ); ?>
							</td>
							<td data-label="<?php esc_attr_e( 'Event', 'cf7-email-template-manager' ); ?>">
								<?php echo esc_html( $entry['message'] ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

</div>
