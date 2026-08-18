<?php
/**
 * Global branding screen.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

$branding = CF7ETM_Branding::get();
?>
<div class="wrap cf7etm cf7etm-branding">

	<?php
	CF7ETM_Admin::header( __( 'Global Branding', 'cf7-email-template-manager' ) );
	CF7ETM_Admin::flash();
	?>

	<div class="cf7etm-alert cf7etm-alert--info">
		<?php esc_html_e( 'These values fill in the branding tags used by your templates, so one change updates every email at once.', 'cf7-email-template-manager' ); ?>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cf7etm-branding__form">
		<input type="hidden" name="action" value="cf7etm_save_branding" />
		<?php wp_nonce_field( 'cf7etm_save_branding' ); ?>

		<div class="cf7etm-columns">

			<div class="cf7etm-card">
				<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Company', 'cf7-email-template-manager' ); ?></h2></div>

				<p class="cf7etm-field">
					<label for="cf7etm-company-name"><?php esc_html_e( 'Company Name', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-company-name" name="branding[company_name]"
						value="<?php echo esc_attr( $branding['company_name'] ); ?>" />
					<span class="cf7etm-help"><code>[cf7etm_company_name]</code></span>
				</p>

				<div class="cf7etm-field">
					<label for="cf7etm-logo"><?php esc_html_e( 'Logo', 'cf7-email-template-manager' ); ?></label>
					<div class="cf7etm-media">
						<input type="url" id="cf7etm-logo" name="branding[logo]" data-media-field
							value="<?php echo esc_attr( $branding['logo'] ); ?>" />
						<button type="button" class="cf7etm-btn cf7etm-btn--small" data-media-choose>
							<?php esc_html_e( 'Choose image', 'cf7-email-template-manager' ); ?>
						</button>
					</div>
					<span class="cf7etm-help">
						<?php esc_html_e( 'Use a hosted image; email clients cannot read local files.', 'cf7-email-template-manager' ); ?>
						<code>[cf7etm_logo]</code>
					</span>
					<?php if ( $branding['logo'] ) : ?>
						<img class="cf7etm-media__preview" src="<?php echo esc_url( $branding['logo'] ); ?>" alt="" data-media-preview />
					<?php else : ?>
						<img class="cf7etm-media__preview" src="" alt="" data-media-preview hidden />
					<?php endif; ?>
				</div>

				<p class="cf7etm-field">
					<label for="cf7etm-website"><?php esc_html_e( 'Website URL', 'cf7-email-template-manager' ); ?></label>
					<input type="url" id="cf7etm-website" name="branding[website]"
						value="<?php echo esc_attr( $branding['website'] ); ?>" />
					<span class="cf7etm-help"><code>[cf7etm_website]</code></span>
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-address"><?php esc_html_e( 'Company Address', 'cf7-email-template-manager' ); ?></label>
					<textarea id="cf7etm-address" name="branding[address]" rows="3"><?php echo esc_textarea( $branding['address'] ); ?></textarea>
					<span class="cf7etm-help"><code>[cf7etm_address]</code></span>
				</p>
			</div>

			<div class="cf7etm-card">
				<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Appearance', 'cf7-email-template-manager' ); ?></h2></div>

				<p class="cf7etm-field">
					<label for="cf7etm-primary-color"><?php esc_html_e( 'Primary Colour', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-primary-color" name="branding[primary_color]" class="cf7etm-color"
						value="<?php echo esc_attr( $branding['primary_color'] ); ?>" data-default-color="#2271b1" />
					<span class="cf7etm-help"><code>[cf7etm_primary_color]</code></span>
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-secondary-color"><?php esc_html_e( 'Secondary Colour', 'cf7-email-template-manager' ); ?></label>
					<input type="text" id="cf7etm-secondary-color" name="branding[secondary_color]" class="cf7etm-color"
						value="<?php echo esc_attr( $branding['secondary_color'] ); ?>" data-default-color="#1d2327" />
					<span class="cf7etm-help"><code>[cf7etm_secondary_color]</code></span>
				</p>

				<p class="cf7etm-field">
					<label for="cf7etm-footer-text"><?php esc_html_e( 'Footer Text', 'cf7-email-template-manager' ); ?></label>
					<textarea id="cf7etm-footer-text" name="branding[footer_text]" rows="3"><?php echo esc_textarea( $branding['footer_text'] ); ?></textarea>
					<span class="cf7etm-help"><code>[cf7etm_footer_text]</code></span>
				</p>
			</div>

			<div class="cf7etm-card">
				<div class="cf7etm-card__head"><h2><?php esc_html_e( 'Social Links', 'cf7-email-template-manager' ); ?></h2></div>

				<?php
				$socials = array(
					'social_facebook'  => __( 'Facebook', 'cf7-email-template-manager' ),
					'social_twitter'   => __( 'X', 'cf7-email-template-manager' ),
					'social_linkedin'  => __( 'LinkedIn', 'cf7-email-template-manager' ),
					'social_instagram' => __( 'Instagram', 'cf7-email-template-manager' ),
				);

				foreach ( $socials as $key => $label ) :
					?>
					<p class="cf7etm-field">
						<label for="cf7etm-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
						<input type="url" id="cf7etm-<?php echo esc_attr( $key ); ?>" name="branding[<?php echo esc_attr( $key ); ?>]"
							value="<?php echo esc_attr( $branding[ $key ] ); ?>" placeholder="https://" />
					</p>
				<?php endforeach; ?>

				<p class="cf7etm-help">
					<?php esc_html_e( 'Only the networks you fill in are shown.', 'cf7-email-template-manager' ); ?>
					<code>[cf7etm_social_links]</code>
				</p>
			</div>

		</div>

		<p class="cf7etm-form-actions">
			<button type="submit" class="cf7etm-btn cf7etm-btn--primary"><?php esc_html_e( 'Save Branding', 'cf7-email-template-manager' ); ?></button>
		</p>
	</form>
</div>
