<?php
/**
 * The submissions list table.
 *
 * With no form selected it lists every submission. Pick a form and the table
 * grows a column for each of that form's fields, so one screen shows the whole
 * enquiry rather than a summary.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CF7ETM_Submissions_List_Table extends WP_List_Table {

	/** Field name => label for the selected form. */
	private $fields = array();

	/** Forms that have submissions. */
	private $forms = array();

	/**
	 * Sets up the table.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'cf7etm_entry',
				'plural'   => 'cf7etm_entries',
				'ajax'     => false,
			)
		);
	}

	/**
	 * The form being viewed, or 0 for all of them.
	 *
	 * @return int
	 */
	public function current_form() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
		return isset( $_REQUEST['form'] ) ? absint( $_REQUEST['form'] ) : 0;
	}

	/**
	 * The status filter.
	 *
	 * @return string
	 */
	private function current_status() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
		$status = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : '';

		return in_array( $status, array( 'sent', 'failed' ), true ) ? $status : '';
	}

	/**
	 * Columns. Field columns only appear once a form is chosen.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'submitted_at' => __( 'Submitted', 'cf7-email-template-manager' ),
		);

		if ( ! $this->current_form() ) {
			$columns['form_title'] = __( 'Form', 'cf7-email-template-manager' );
		}

		$columns['status'] = __( 'Email', 'cf7-email-template-manager' );

		foreach ( $this->fields as $name => $label ) {
			$columns[ 'field-' . $name ] = $label;
		}

		$columns['details'] = __( 'Submitted Data', 'cf7-email-template-manager' );

		return $columns;
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'submitted_at' => array( 'submitted_at', true ),
			'form_title'   => array( 'form_title', false ),
			'status'       => array( 'status', false ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array( 'delete' => __( 'Delete', 'cf7-email-template-manager' ) );
	}

	/**
	 * One link per form that has submissions.
	 *
	 * @return array
	 */
	protected function get_views() {
		$current = $this->current_form();

		$views = array(
			'all' => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( CF7ETM_Plugin::url( 'submissions' ) ),
				$current ? '' : ' class="current" aria-current="page"',
				esc_html__( 'All Forms', 'cf7-email-template-manager' ),
				CF7ETM_Submissions::count()
			),
		);

		foreach ( $this->forms as $form_id => $form ) {
			$views[ 'form-' . $form_id ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( CF7ETM_Plugin::url( 'submissions', array( 'form' => $form_id ) ) ),
				$current === $form_id ? ' class="current" aria-current="page"' : '',
				esc_html( $form['title'] ? $form['title'] : sprintf( '#%d', $form_id ) ),
				$form['count']
			);
		}

		return $views;
	}

	/**
	 * Status filter above the table.
	 *
	 * @param string $which Top or bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$status  = $this->current_status();
		$options = array(
			''       => __( 'Any email result', 'cf7-email-template-manager' ),
			'sent'   => __( 'Email sent', 'cf7-email-template-manager' ),
			'failed' => __( 'Email failed', 'cf7-email-template-manager' ),
		);

		echo '<div class="alignleft actions">';
		echo '<label class="screen-reader-text" for="cf7etm-status">' .
			esc_html__( 'Filter by email result', 'cf7-email-template-manager' ) . '</label>';
		echo '<select name="status" id="cf7etm-status">';

		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $status, $value, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
		submit_button( __( 'Filter', 'cf7-email-template-manager' ), '', 'filter_action', false );
		echo '</div>';
	}

	/**
	 * Loads items for the current page.
	 */
	public function prepare_items() {
		$this->process_bulk_action();

		$this->forms = CF7ETM_Submissions::forms();

		$per_page = $this->get_items_per_page( 'cf7etm_entries_per_page', 20 );
		$form_id  = $this->current_form();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only search.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only ordering.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'submitted_at';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only ordering.
		$order = isset( $_REQUEST['order'] ) ? sanitize_key( wp_unslash( $_REQUEST['order'] ) ) : 'desc';

		$results = CF7ETM_Submissions::query(
			array(
				'form_id'  => $form_id,
				'status'   => $this->current_status(),
				'search'   => $search,
				'per_page' => $per_page,
				'page'     => $this->get_pagenum(),
				'orderby'  => $orderby,
				'order'    => $order,
			)
		);

		$this->items = $results['items'];

		// Field columns only make sense when every row is the same form.
		$this->fields = $form_id ? CF7ETM_Submissions::field_columns( $form_id, $this->items ) : array();

		$this->set_pagination_args(
			array(
				'total_items' => $results['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $results['total'] / max( 1, $per_page ) ),
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
	}

	/**
	 * Handles the delete bulk action.
	 */
	public function process_bulk_action() {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );
		CF7ETM_Plugin::require_cap();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked immediately above.
		CF7ETM_Submissions::delete( (array) ( $_REQUEST['entry'] ?? array() ) );
	}

	/**
	 * Checkbox column.
	 *
	 * @param array $item Submission.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="entry[]" value="%d" />', $item['id'] );
	}

	/**
	 * Date column, with the row actions.
	 *
	 * @param array $item Submission.
	 * @return string
	 */
	public function column_submitted_at( $item ) {
		$stamp = strtotime( $item['submitted_at'] );

		$view = CF7ETM_Plugin::url( 'submissions', array( 'entry' => $item['id'] ) );

		$delete = wp_nonce_url(
			CF7ETM_Plugin::url( 'submissions', array( 'entry_action' => 'delete', 'entry' => $item['id'] ) ),
			'cf7etm_delete_entry_' . $item['id']
		);

		$actions = array(
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view ), esc_html__( 'View', 'cf7-email-template-manager' ) ),
			'delete' => sprintf(
				'<a href="%s" class="cf7etm-danger-link" data-cf7etm-confirm="%s">%s</a>',
				esc_url( $delete ),
				esc_attr__( 'Delete this submission? This cannot be undone.', 'cf7-email-template-manager' ),
				esc_html__( 'Delete', 'cf7-email-template-manager' )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $view ),
			esc_html( $stamp ? wp_date( 'Y-m-d H:i', $stamp ) : $item['submitted_at'] ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Form column.
	 *
	 * @param array $item Submission.
	 * @return string
	 */
	public function column_form_title( $item ) {
		$title = $item['form_title'] ? $item['form_title'] : sprintf( '#%d', $item['form_id'] );

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( CF7ETM_Plugin::url( 'submissions', array( 'form' => $item['form_id'] ) ) ),
			esc_html( $title )
		);
	}

	/**
	 * Email result column.
	 *
	 * @param array $item Submission.
	 * @return string
	 */
	public function column_status( $item ) {
		$sent = 'sent' === $item['status'];

		return sprintf(
			'<span class="cf7etm-badge cf7etm-badge--%s">%s</span>',
			$sent ? 'success' : 'danger',
			$sent
				? esc_html__( 'Sent', 'cf7-email-template-manager' )
				: esc_html__( 'Not sent', 'cf7-email-template-manager' )
		);
	}

	/**
	 * Everything else: a field column, or the all-forms summary.
	 *
	 * @param array  $item   Submission.
	 * @param string $column Column key.
	 * @return string
	 */
	public function column_default( $item, $column ) {
		if ( 'details' === $column ) {
			return sprintf(
				'<button type="button" class="cf7etm-btn cf7etm-btn--small" data-cf7etm-entry-toggle aria-expanded="false" aria-controls="cf7etm-entry-%1$d"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>%2$s</button>',
				$item['id'],
				esc_html__( 'Details', 'cf7-email-template-manager' )
			);
		}

		if ( ! str_starts_with( $column, 'field-' ) ) {
			return '';
		}

		$name = substr( $column, 6 );

		$files = CF7ETM_Submissions::files( $item );

		if ( isset( $files[ $name ] ) ) {
			return self::file_links( $item['id'], $name, $files[ $name ] );
		}

		if ( ! isset( $item['fields'][ $name ] ) ) {
			return '<span class="cf7etm-muted">&mdash;</span>';
		}

		$value = CF7ETM_Submissions::flatten( $item['fields'][ $name ] );

		return '' === trim( $value )
			? '<span class="cf7etm-muted">&mdash;</span>'
			: esc_html( self::shorten( $value ) );
	}

	/**
	 * Paperclip links to the stored copies of what was uploaded.
	 *
	 * @param int    $entry_id Submission ID.
	 * @param string $field    Field name.
	 * @param array  $files    Files on that field.
	 * @return string
	 */
	private static function file_links( $entry_id, $field, $files ) {
		$links = array();

		foreach ( $files as $file ) {
			if ( '' === $file['path'] ) {
				$links[] = sprintf(
					'<span class="cf7etm-muted" title="%s">%s</span>',
					esc_attr__( 'This file type is not stored.', 'cf7-email-template-manager' ),
					esc_html( $file['name'] )
				);
				continue;
			}

			$links[] = sprintf(
				'<a href="%s"><span class="dashicons dashicons-paperclip" aria-hidden="true"></span>%s</a>',
				esc_url( CF7ETM_Submissions::download_url( $entry_id, $field, $file['index'] ) ),
				esc_html( $file['name'] )
			);
		}

		return implode( ', ', $links );
	}

	/**
	 * Each row is followed by a hidden one holding the whole submission, so
	 * Details opens it in place instead of sending anyone to another screen.
	 *
	 * @param array $item Submission.
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';

		printf(
			'<tr class="cf7etm-entry__row" id="cf7etm-entry-%1$d" hidden><td colspan="%2$d">',
			(int) $item['id'],
			(int) $this->get_column_count()
		);

		$entry = $item;
		require CF7ETM_DIR . 'admin/views/partial-entry-data.php';

		echo '</td></tr>';
	}

	/**
	 * Trims a long answer for the table; the full text is on the detail view.
	 *
	 * @param string $value Field value.
	 * @return string
	 */
	private static function shorten( $value ) {
		$value = trim( preg_replace( '/\s+/', ' ', $value ) );

		return mb_strlen( $value ) > 60 ? mb_substr( $value, 0, 60 ) . '…' : $value;
	}

	/**
	 * Message shown when nothing matches.
	 */
	public function no_items() {
		esc_html_e( 'No submissions yet.', 'cf7-email-template-manager' );
	}
}
