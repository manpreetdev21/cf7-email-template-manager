<?php
/**
 * Admin menus, screens, assets and the non-AJAX form handlers.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

class CF7ETM_Admin {

	/** Our page hook suffixes, filled in by register_menu(). */
	private static $hooks = array();

	/**
	 * Hooks the admin layer.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );

		add_action( 'admin_post_cf7etm_save_branding', array( __CLASS__, 'handle_save_branding' ) );
		add_action( 'admin_post_cf7etm_save_settings', array( __CLASS__, 'handle_save_settings' ) );
		add_action( 'admin_post_cf7etm_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_cf7etm_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_post_cf7etm_clear_log', array( __CLASS__, 'handle_clear_log' ) );
	}

	/**
	 * Registers the top-level menu and its submenus.
	 */
	public static function register_menu() {
		$cap = CF7ETM_Plugin::cap();

		self::$hooks['dashboard'] = add_menu_page(
			__( 'CF7 Email Templates', 'cf7-email-template-manager' ),
			__( 'CF7 Email Templates', 'cf7-email-template-manager' ),
			$cap,
			'cf7etm',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-email-alt',
			30
		);

		$submenus = array(
			'cf7etm'                => array( __( 'Dashboard', 'cf7-email-template-manager' ), 'render_dashboard' ),
			'cf7etm-templates'      => array( __( 'Email Templates', 'cf7-email-template-manager' ), 'render_templates' ),
			'cf7etm-template-edit'  => array( __( 'Add New', 'cf7-email-template-manager' ), 'render_editor' ),
			'cf7etm-assignments'    => array( __( 'Assignments', 'cf7-email-template-manager' ), 'render_assignments' ),
			'cf7etm-branding'       => array( __( 'Global Branding', 'cf7-email-template-manager' ), 'render_branding' ),
			'cf7etm-settings'       => array( __( 'Settings', 'cf7-email-template-manager' ), 'render_settings' ),
			'cf7etm-tools'          => array( __( 'Tools', 'cf7-email-template-manager' ), 'render_tools' ),
		);

		foreach ( $submenus as $slug => $config ) {
			list( $title, $callback ) = $config;

			$hook = add_submenu_page(
				'cf7etm',
				$title,
				$title,
				$cap,
				$slug,
				array( __CLASS__, $callback )
			);

			self::$hooks[ $slug ] = $hook;
		}

		add_action( 'load-' . self::$hooks['cf7etm-templates'], array( __CLASS__, 'load_templates_screen' ) );
	}

	/**
	 * Sets up per-page screen options for the templates list.
	 */
	public static function load_templates_screen() {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Templates per page', 'cf7-email-template-manager' ),
				'default' => 20,
				'option'  => 'cf7etm_per_page',
			)
		);
	}

	/**
	 * Whether the current screen belongs to this plugin.
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool
	 */
	private static function is_plugin_screen( $hook ) {
		return in_array( $hook, self::$hooks, true );
	}

	/**
	 * Loads CSS and JS on this plugin's screens only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( $hook ) {
		if ( ! self::is_plugin_screen( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'cf7etm-admin',
			CF7ETM_URL . 'assets/css/admin.css',
			array(),
			CF7ETM_VERSION
		);

		wp_enqueue_script(
			'cf7etm-admin',
			CF7ETM_URL . 'assets/js/admin.js',
			array(),
			CF7ETM_VERSION,
			true
		);

		wp_localize_script(
			'cf7etm-admin',
			'cf7etm',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cf7etm_admin' ),
				'i18n'    => array(
					'saving'       => __( 'Saving…', 'cf7-email-template-manager' ),
					'saved'        => __( 'Template saved.', 'cf7-email-template-manager' ),
					'error'        => __( 'Something went wrong. Please try again.', 'cf7-email-template-manager' ),
					'confirm'      => __( 'Are you sure?', 'cf7-email-template-manager' ),
					'cancel'       => __( 'Cancel', 'cf7-email-template-manager' ),
					'deleteTitle'  => __( 'Delete template', 'cf7-email-template-manager' ),
					'deleteBody'   => __( 'Are you sure you want to delete this template? This cannot be undone.', 'cf7-email-template-manager' ),
					'deleteButton' => __( 'Delete template', 'cf7-email-template-manager' ),
					'unsaved'      => __( 'You have unsaved changes.', 'cf7-email-template-manager' ),
					'copied'       => __( 'Tag copied to clipboard.', 'cf7-email-template-manager' ),
					'testSent'     => __( 'Test email sent.', 'cf7-email-template-manager' ),
					'testPrompt'   => __( 'Send the test email to:', 'cf7-email-template-manager' ),
					'sendTest'     => __( 'Send test', 'cf7-email-template-manager' ),
					'previewTitle' => __( 'Email preview', 'cf7-email-template-manager' ),
					'close'        => __( 'Close', 'cf7-email-template-manager' ),
					'save'         => __( 'Save', 'cf7-email-template-manager' ),
					'subject'      => __( 'Subject:', 'cf7-email-template-manager' ),
					'remove'       => __( 'Remove', 'cf7-email-template-manager' ),
					'keep'         => __( 'Keep', 'cf7-email-template-manager' ),
					'replaceWith'  => __( 'Replace with…', 'cf7-email-template-manager' ),
					'noTags'       => __( 'This form has no fields that can be used in an email.', 'cf7-email-template-manager' ),
					'newTagsOne'   => __( '1 form tag is available but not used in this template.', 'cf7-email-template-manager' ),
					/* translators: %d: number of unused form tags */
					'newTagsMany'  => __( '%d form tags are available but not used in this template.', 'cf7-email-template-manager' ),
				),
			)
		);

		if ( self::$hooks['cf7etm-template-edit'] === $hook ) {
			// Core already ships CodeMirror; no third-party editor needed.
			$settings = wp_enqueue_code_editor(
				array(
					'type'       => 'text/html',
					'codemirror' => array(
						'lineNumbers' => true,
						'lineWrapping' => true,
					),
				)
			);

			wp_enqueue_script(
				'cf7etm-editor',
				CF7ETM_URL . 'assets/js/editor.js',
				array( 'cf7etm-admin' ),
				CF7ETM_VERSION,
				true
			);

			wp_localize_script(
				'cf7etm-editor',
				'cf7etmEditor',
				array(
					// False when the user turned syntax highlighting off; the
					// editor then falls back to a plain textarea.
					'codeEditor' => $settings ? $settings : false,
				)
			);
		}

		if ( self::$hooks['cf7etm-branding'] === $hook ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_media();
		}
	}

	/* ---------------------------------------------------------------------
	 * Screens
	 * ------------------------------------------------------------------ */

	/**
	 * Renders one of the view files.
	 *
	 * @param string $view View file base name.
	 * @param array  $data Variables extracted into the view.
	 */
	private static function view( $view, $data = array() ) {
		CF7ETM_Plugin::require_cap();

		$file = CF7ETM_DIR . 'admin/views/' . $view . '.php';

		if ( ! file_exists( $file ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled, internal view data.
		extract( $data );

		require $file;
	}

	/** Dashboard screen. */
	public static function render_dashboard() {
		self::view( 'dashboard' );
	}

	/** Templates list screen. */
	public static function render_templates() {
		self::view( 'templates' );
	}

	/** Template editor screen. */
	public static function render_editor() {
		self::view( 'editor' );
	}

	/** Assignments screen. */
	public static function render_assignments() {
		self::view( 'assignments' );
	}

	/** Global branding screen. */
	public static function render_branding() {
		self::view( 'branding' );
	}

	/** Settings screen. */
	public static function render_settings() {
		self::view( 'settings' );
	}

	/** Tools screen. */
	public static function render_tools() {
		self::view( 'tools' );
	}

	/**
	 * Shared page header markup.
	 *
	 * @param string $title   Screen title.
	 * @param string $actions Optional HTML for the right-hand actions.
	 */
	public static function header( $title, $actions = '' ) {
		printf(
			'<div class="cf7etm-header"><div><h1 class="cf7etm-header__title">%s</h1></div><div class="cf7etm-header__actions">%s</div></div>',
			esc_html( $title ),
			wp_kses_post( $actions )
		);
	}

	/**
	 * Prints an admin notice queued through the redirect URL.
	 */
	public static function flash() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only message key.
		$notice = isset( $_GET['cf7etm_notice'] ) ? sanitize_key( wp_unslash( $_GET['cf7etm_notice'] ) ) : '';

		if ( ! $notice ) {
			return;
		}

		$messages = array(
			'branding_saved' => array( 'success', __( 'Branding saved.', 'cf7-email-template-manager' ) ),
			'settings_saved' => array( 'success', __( 'Settings saved.', 'cf7-email-template-manager' ) ),
			'imported'       => array( 'success', __( 'Templates imported.', 'cf7-email-template-manager' ) ),
			'import_failed'  => array( 'error', __( 'That file could not be imported. Please upload a valid export file.', 'cf7-email-template-manager' ) ),
			'log_cleared'    => array( 'success', __( 'Debug log cleared.', 'cf7-email-template-manager' ) ),
			'template_saved' => array( 'success', __( 'Template saved.', 'cf7-email-template-manager' ) ),
		);

		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}

		list( $type, $message ) = $messages[ $notice ];

		printf(
			'<div class="cf7etm-alert cf7etm-alert--%s" role="status">%s</div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	/* ---------------------------------------------------------------------
	 * Form handlers
	 * ------------------------------------------------------------------ */

	/**
	 * Verifies nonce and capability for an admin-post request.
	 *
	 * @param string $action Nonce action.
	 */
	private static function verify( $action ) {
		CF7ETM_Plugin::require_cap();
		check_admin_referer( $action );
	}

	/**
	 * Redirects back to a plugin screen with a notice.
	 *
	 * @param string $page   Page slug suffix.
	 * @param string $notice Notice key.
	 */
	private static function redirect( $page, $notice ) {
		wp_safe_redirect( CF7ETM_Plugin::url( $page, array( 'cf7etm_notice' => $notice ) ) );
		exit;
	}

	/** Saves the global branding form. */
	public static function handle_save_branding() {
		self::verify( 'cf7etm_save_branding' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field is sanitized in CF7ETM_Branding::save().
		CF7ETM_Branding::save( wp_unslash( (array) ( $_POST['branding'] ?? array() ) ) );

		self::redirect( 'branding', 'branding_saved' );
	}

	/** Saves the settings form. */
	public static function handle_save_settings() {
		self::verify( 'cf7etm_save_settings' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field by field below.
		$input = wp_unslash( (array) ( $_POST['settings'] ?? array() ) );

		$clean = array(
			'default_type'        => ( isset( $input['default_type'] ) && 'text' === $input['default_type'] ) ? 'text' : 'html',
			'default_sender'      => sanitize_text_field( $input['default_sender'] ?? '' ),
			'test_recipient'      => sanitize_email( $input['test_recipient'] ?? '' ),
			'debug'               => empty( $input['debug'] ) ? 0 : 1,
			'delete_on_uninstall' => empty( $input['delete_on_uninstall'] ) ? 0 : 1,
		);

		update_option( CF7ETM_Plugin::SETTINGS, $clean );

		self::redirect( 'settings', 'settings_saved' );
	}

	/** Streams a JSON export of all templates. */
	public static function handle_export() {
		self::verify( 'cf7etm_export' );

		$templates = array();

		foreach ( array_keys( CF7ETM_Template_Post_Type::options() ) as $id ) {
			$template = CF7ETM_Template_Post_Type::get( $id );

			if ( ! $template ) {
				continue;
			}

			unset( $template['id'], $template['author'], $template['modified'], $template['form_context'] );

			$templates[] = $template;
		}

		$payload = array(
			'format'    => 'cf7etm',
			'version'   => CF7ETM_VERSION,
			'exported'  => gmdate( 'c' ),
			'branding'  => CF7ETM_Branding::get(),
			'templates' => $templates,
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=cf7-email-templates-' . gmdate( 'Y-m-d' ) . '.json' );

		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/** Imports templates from an uploaded JSON file. */
	public static function handle_import() {
		self::verify( 'cf7etm_import' );

		if ( empty( $_FILES['import_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
			self::redirect( 'tools', 'import_failed' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- server-side temp path from $_FILES.
		$raw = file_get_contents( $_FILES['import_file']['tmp_name'] );

		$data = json_decode( (string) $raw, true );

		if ( ! is_array( $data ) || 'cf7etm' !== ( $data['format'] ?? '' ) || empty( $data['templates'] ) || ! is_array( $data['templates'] ) ) {
			self::redirect( 'tools', 'import_failed' );
		}

		$imported = 0;

		foreach ( $data['templates'] as $template ) {
			if ( ! is_array( $template ) || empty( $template['name'] ) ) {
				continue;
			}

			// Never trust an ID from a file: always insert a new template.
			$template['id'] = 0;

			// Imported templates land as drafts so nothing goes live unreviewed.
			$template['status'] = 'draft';

			if ( ! is_wp_error( CF7ETM_Template_Post_Type::save( $template ) ) ) {
				++$imported;
			}
		}

		self::redirect( 'tools', $imported ? 'imported' : 'import_failed' );
	}

	/** Empties the debug log. */
	public static function handle_clear_log() {
		self::verify( 'cf7etm_clear_log' );

		delete_option( 'cf7etm_log' );

		self::redirect( 'tools', 'log_cleared' );
	}
}
