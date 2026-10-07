<?php
/**
 * A single insertable tag chip.
 *
 * Expects $cf7etm_tag (tag name without brackets) and $cf7etm_label (friendly name).
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="cf7etm-tag" data-tag="<?php echo esc_attr( $cf7etm_tag ); ?>" data-search="<?php echo esc_attr( strtolower( $cf7etm_label . ' ' . $cf7etm_tag ) ); ?>">
	<button type="button" class="cf7etm-tag__insert" data-insert="<?php echo esc_attr( $cf7etm_tag ); ?>"
		title="<?php echo esc_attr( sprintf( '[%s]', $cf7etm_tag ) ); ?>">
		<span class="cf7etm-tag__label"><?php echo esc_html( $cf7etm_label ); ?></span>
		<code class="cf7etm-tag__code">[<?php echo esc_html( $cf7etm_tag ); ?>]</code>
	</button>
	<button type="button" class="cf7etm-tag__copy" data-copy="<?php echo esc_attr( $cf7etm_tag ); ?>"
		aria-label="<?php echo esc_attr( sprintf( /* translators: %s: tag name */ __( 'Copy [%s]', 'cf7-email-template-manager' ), $cf7etm_tag ) ); ?>">
		<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
	</button>
</span>
