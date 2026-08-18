<?php
/**
 * A single insertable tag chip.
 *
 * Expects $tag (tag name without brackets) and $label (friendly name).
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="cf7etm-tag" data-tag="<?php echo esc_attr( $tag ); ?>" data-search="<?php echo esc_attr( strtolower( $label . ' ' . $tag ) ); ?>">
	<button type="button" class="cf7etm-tag__insert" data-insert="<?php echo esc_attr( $tag ); ?>"
		title="<?php echo esc_attr( sprintf( '[%s]', $tag ) ); ?>">
		<span class="cf7etm-tag__label"><?php echo esc_html( $label ); ?></span>
		<code class="cf7etm-tag__code">[<?php echo esc_html( $tag ); ?>]</code>
	</button>
	<button type="button" class="cf7etm-tag__copy" data-copy="<?php echo esc_attr( $tag ); ?>"
		aria-label="<?php echo esc_attr( sprintf( /* translators: %s: tag name */ __( 'Copy [%s]', 'cf7-email-template-manager' ), $tag ) ); ?>">
		<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
	</button>
</span>
