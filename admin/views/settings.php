<?php
/**
 * Settings screen.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

$settings = CF7ETM_Plugin::settings();
?>
<div class="wrap cf7etm">

	<?php
	CF7ETM_Admin::header( __( 'Settings', 'cf7-email-template-manager' ) );
	CF7ETM_Admin::flash();
	?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="cf7etm_save_settings" />
		<?php wp_nonce_field( 'cf7etm_save_settings' ); ?>

		<div class="cf7etm-columns">

			<div class="cf7etm-card">
				<div class="cf7etm-card__head"><h2><?php esc_html_e( 'General', 'cf7-email-template-manager' ); ?></h2></div>

				<p class="cf7etm-field">
					<label for="cf7etm-default-type"><?php esc_html_e( 'Default Email Format', 'cf7-email-template-manager' ); ?></label>
					<select id="cf7etm-default-type" name="settings[default_type]">
						<option value="html" <?php selected( $settings['default_type'], 'html' ); ?>><?php esc_html_e( 'HTML', 'cf7-email-template-manager' ); ?></option>
						<option value="text" <?php selected( $settings['default_type'], 'text' ); ?>><?php esc_html_e( 'Plain Text', 'cf7-email-template-manager' ); ?></option>
					</select>
					<span class="cf7etm-help"><?php esc_html_e( 'Used when you create a new template.', 'cf7-email-template-manager' ); ?></span>
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-default-sender"><?php esc_html_e( 'Default Sender', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-default-sender" name="settings[default_sender]"
						value="<?php echo esc_attr( $settings['default_sender'] ); ?>"
						placeholder="<?php echo esc_attr( sprintf( '%s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ) ); ?>" />
					<span class="cf7etm-help"><?php esc_html_e( 'Used when neither the template nor the form sets a From address.', 'cf7-email-template-manager' ); ?></span>
				</p>
			</div>

			<div class="cf7etm-card">
				<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Email', 'cf7-email-template-manager' ); ?></h2></div>

				<p class="cf7etm-field">
					<label for="cf7etm-test-recipient"><?php esc_html_e( 'Test Email Recipient', 'cf7-email-template-manager' ); ?></label>
					<input type="email" id="cf7etm-test-recipient" name="settings[test_recipient]"
						value="<?php echo esc_attr( $settings['test_recipient'] ); ?>"
						placeholder="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" />
					<span class="cf7etm-help"><?php esc_html_e( 'Where test emails go by default. Leave empty to use your own address.', 'cf7-email-template-manager' ); ?></span>
				</p>

				<p class="cf7etm-help">
					<?php esc_html_e( 'Templates are sent by Contact Form 7 using WordPress mail, so any SMTP plugin you already use keeps working. This plugin never stores or displays mail server credentials.', 'cf7-email-template-manager' ); ?>
				</p>
			</div>

			<div class="cf7etm-card">
				<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Advanced', 'cf7-email-template-manager' ); ?></h2></div>

				<p class="cf7etm-field cf7etm-field--check">
					<label for="cf7etm-debug">
						<input type="checkbox" id="cf7etm-debug" name="settings[debug]" value="1" <?php checked( (int) $settings['debug'], 1 ); ?> />
						<?php esc_html_e( 'Enable debug logging', 'cf7-email-template-manager' ); ?>
					</label>
					<span class="cf7etm-help"><?php esc_html_e( 'Records which template was applied to which form, and whether sending succeeded. Email content is never logged.', 'cf7-email-template-manager' ); ?></span>
				</p>

				<p class="cf7etm-field cf7etm-field--check">
					<label for="cf7etm-delete-data">
						<input type="checkbox" id="cf7etm-delete-data" name="settings[delete_on_uninstall]" value="1"
							<?php checked( (int) $settings['delete_on_uninstall'], 1 ); ?> />
						<?php esc_html_e( 'Delete all plugin data when the plugin is uninstalled', 'cf7-email-template-manager' ); ?>
					</label>
					<span class="cf7etm-help"><?php esc_html_e( 'Off by default. Your templates survive deactivating or deleting the plugin unless you turn this on.', 'cf7-email-template-manager' ); ?></span>
				</p>
			</div>

		</div>

		<p class="cf7etm-form-actions">
			<button type="submit" class="cf7etm-btn cf7etm-btn--primary"><?php esc_html_e( 'Save Settings', 'cf7-email-template-manager' ); ?></button>
		</p>
	</form>
</div>
