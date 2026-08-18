<?php
/**
 * Bootstrap, environment guards and shared settings access.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

class CF7ETM_Plugin {

	/** Oldest Contact Form 7 we integrate with. */
	const MIN_CF7 = '5.8';

	/** Option holding the plugin settings array. */
	const SETTINGS = 'cf7etm_settings';

	/**
	 * Loads the plugin once WordPress and Contact Form 7 are available.
	 */
	public static function boot() {
		load_plugin_textdomain(
			'cf7-email-template-manager',
			false,
			dirname( plugin_basename( CF7ETM_FILE ) ) . '/languages'
		);

		if ( ! self::cf7_supported() ) {
			add_action( 'admin_notices', array( __CLASS__, 'render_requirement_notice' ) );
			return;
		}

		require_once CF7ETM_DIR . 'includes/class-template-post-type.php';
		require_once CF7ETM_DIR . 'includes/class-branding.php';
		require_once CF7ETM_DIR . 'includes/class-renderer.php';
		require_once CF7ETM_DIR . 'includes/class-cf7-bridge.php';

		// Post types belong on `init`: $wp_rewrite does not exist yet on plugins_loaded.
		add_action( 'init', array( 'CF7ETM_Template_Post_Type', 'init' ) );

		CF7ETM_CF7_Bridge::init();

		if ( is_admin() ) {
			require_once CF7ETM_DIR . 'admin/class-admin.php';
			require_once CF7ETM_DIR . 'admin/class-ajax.php';

			CF7ETM_Admin::init();
			CF7ETM_Ajax::init();
		}
	}

	/**
	 * Seeds starter templates and default settings on first activation.
	 */
	public static function activate() {
		if ( ! self::cf7_supported() ) {
			return; // Nothing to seed yet; boot() will nag in the admin.
		}

		require_once CF7ETM_DIR . 'includes/class-template-post-type.php';
		CF7ETM_Template_Post_Type::init();

		add_option( self::SETTINGS, self::default_settings() );

		if ( ! get_option( 'cf7etm_seeded' ) ) {
			require_once CF7ETM_DIR . 'includes/class-branding.php';
			require_once CF7ETM_DIR . 'includes/starter-templates.php';
			cf7etm_install_starter_templates();
			update_option( 'cf7etm_seeded', CF7ETM_VERSION );
		}
	}

	/**
	 * Whether a supported Contact Form 7 is active.
	 *
	 * @return bool
	 */
	public static function cf7_supported() {
		return defined( 'WPCF7_VERSION' )
			&& class_exists( 'WPCF7_ContactForm' )
			&& version_compare( WPCF7_VERSION, self::MIN_CF7, '>=' );
	}

	/**
	 * Admin notice shown when Contact Form 7 is missing or too old.
	 */
	public static function render_requirement_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$message = defined( 'WPCF7_VERSION' )
			? sprintf(
				/* translators: 1: required CF7 version, 2: installed CF7 version */
				__( 'CF7 Email Template Manager needs Contact Form 7 %1$s or newer. Version %2$s is installed.', 'cf7-email-template-manager' ),
				self::MIN_CF7,
				WPCF7_VERSION
			)
			: __( 'CF7 Email Template Manager needs the Contact Form 7 plugin to be installed and active.', 'cf7-email-template-manager' );

		wp_admin_notice( esc_html( $message ), array( 'type' => 'warning' ) );
	}

	/**
	 * Capability required to manage templates. Reuses Contact Form 7's own
	 * capability so existing form editors get access without extra setup.
	 *
	 * @return string
	 */
	public static function cap() {
		return 'wpcf7_edit_contact_forms';
	}

	/**
	 * Dies with a friendly message when the current user lacks access.
	 */
	public static function require_cap() {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die(
				esc_html__( 'You do not have permission to manage email templates.', 'cf7-email-template-manager' ),
				403
			);
		}
	}

	/**
	 * Default plugin settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'default_type'       => 'html',
			'default_sender'     => '',
			'test_recipient'     => '',
			'debug'              => 0,
			'delete_on_uninstall' => 0,
		);
	}

	/**
	 * All settings, merged over defaults.
	 *
	 * @return array
	 */
	public static function settings() {
		return wp_parse_args( (array) get_option( self::SETTINGS, array() ), self::default_settings() );
	}

	/**
	 * A single setting value.
	 *
	 * @param string $key     Setting name.
	 * @param mixed  $default Fallback when unset.
	 * @return mixed
	 */
	public static function setting( $key, $default = '' ) {
		$settings = self::settings();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Writes a line to the debug log when debug mode is enabled.
	 * Email bodies are never logged.
	 *
	 * @param string $message Context line.
	 */
	public static function log( $message ) {
		if ( ! self::setting( 'debug' ) ) {
			return;
		}

		$log = (array) get_option( 'cf7etm_log', array() );

		$log[] = array(
			'time'    => time(),
			'message' => (string) $message,
		);

		// Keep the log bounded; this is a diagnostic aid, not an archive.
		update_option( 'cf7etm_log', array_slice( $log, -200 ), false );
	}

	/**
	 * Admin URL for one of the plugin screens.
	 *
	 * @param string $page Page slug suffix, e.g. 'templates'.
	 * @param array  $args Extra query arguments.
	 * @return string
	 */
	public static function url( $page = '', $args = array() ) {
		$slug = 'cf7etm' . ( $page ? '-' . $page : '' );
		return add_query_arg( array( 'page' => $slug ) + $args, admin_url( 'admin.php' ) );
	}
}
