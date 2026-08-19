<?php
/**
 * The templates list table.
 *
 * Extending WP_List_Table buys search, pagination, sortable columns, bulk
 * actions, screen options and accessibility for free; the plugin stylesheet
 * handles the rest of the look.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CF7ETM_Templates_List_Table extends WP_List_Table {

	/** Counts used by the view links. */
	private $counts = array();

	/**
	 * Sets up the table.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'cf7etm_template',
				'plural'   => 'cf7etm_templates',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'name'     => __( 'Template Name', 'cf7-email-template-manager' ),
			'type'     => __( 'Type', 'cf7-email-template-manager' ),
			'forms'    => __( 'Assigned Forms', 'cf7-email-template-manager' ),
			'files'    => __( 'File Support', 'cf7-email-template-manager' ),
			'status'   => __( 'Status', 'cf7-email-template-manager' ),
			'modified' => __( 'Updated', 'cf7-email-template-manager' ),
			'author'   => __( 'Author', 'cf7-email-template-manager' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'name'     => array( 'title', false ),
			'modified' => array( 'modified', true ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'activate'   => __( 'Activate', 'cf7-email-template-manager' ),
			'deactivate' => __( 'Deactivate', 'cf7-email-template-manager' ),
			'delete'     => __( 'Delete', 'cf7-email-template-manager' ),
		);
	}

	/**
	 * Filter links above the table.
	 *
	 * @return array
	 */
	protected function get_views() {
		$current = $this->current_filter();

		$links = array(
			'all'      => __( 'All', 'cf7-email-template-manager' ),
			'publish'  => __( 'Active', 'cf7-email-template-manager' ),
			'private'  => __( 'Inactive', 'cf7-email-template-manager' ),
			'draft'    => __( 'Draft', 'cf7-email-template-manager' ),
			'html'     => __( 'HTML', 'cf7-email-template-manager' ),
			'text'     => __( 'Plain Text', 'cf7-email-template-manager' ),
			'assigned' => __( 'Assigned', 'cf7-email-template-manager' ),
			'unused'   => __( 'Unused', 'cf7-email-template-manager' ),
			'files'    => __( 'Contains File Uploads', 'cf7-email-template-manager' ),
			'nofiles'  => __( 'No File Uploads', 'cf7-email-template-manager' ),
		);

		$views = array();

		foreach ( $links as $key => $label ) {
			$url = CF7ETM_Plugin::url( 'templates', 'all' === $key ? array() : array( 'filter' => $key ) );

			$count = isset( $this->counts[ $key ] ) ? ' <span class="count">(' . (int) $this->counts[ $key ] . ')</span>' : '';

			$views[ $key ] = sprintf(
				'<a href="%s"%s>%s%s</a>',
				esc_url( $url ),
				$current === $key ? ' class="current" aria-current="page"' : '',
				esc_html( $label ),
				$count
			);
		}

		return $views;
	}

	/**
	 * The active filter key.
	 *
	 * @return string
	 */
	private function current_filter() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
		$filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';

		$allowed = array( 'all', 'publish', 'private', 'draft', 'html', 'text', 'assigned', 'unused', 'files', 'nofiles' );

		return in_array( $filter, $allowed, true ) ? $filter : 'all';
	}

	/**
	 * Loads items for the current page.
	 */
	public function prepare_items() {
		$this->process_bulk_action();

		$per_page = $this->get_items_per_page( 'cf7etm_per_page', 20 );
		$filter   = $this->current_filter();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only search.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only ordering.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'modified';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only ordering.
		$order = ( isset( $_REQUEST['order'] ) && 'asc' === strtolower( wp_unslash( $_REQUEST['order'] ) ) ) ? 'ASC' : 'DESC';

		$args = array(
			'post_type'      => CF7ETM_Template_Post_Type::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => $per_page,
			'paged'          => $this->get_pagenum(),
			'orderby'        => in_array( $orderby, array( 'title', 'modified' ), true ) ? $orderby : 'modified',
			'order'          => $order,
			's'              => $search,
		);

		$assigned = CF7ETM_CF7_Bridge::assigned_template_ids();

		switch ( $filter ) {
			case 'publish':
			case 'draft':
			case 'private':
				$args['post_status'] = array( $filter );
				break;

			case 'html':
			case 'text':
				$args['meta_key']   = '_cf7etm_type';
				$args['meta_value'] = $filter;
				break;

			case 'assigned':
				// post__in must never be empty, or WP_Query ignores it.
				$args['post__in'] = $assigned ? $assigned : array( 0 );
				break;

			case 'unused':
				if ( $assigned ) {
					$args['post__not_in'] = $assigned;
				}
				break;

			case 'files':
				$args['meta_key']   = '_cf7etm_has_files';
				$args['meta_value'] = 1;
				break;

			case 'nofiles':
				// Templates saved before the flag existed have no row at all.
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => '_cf7etm_has_files',
						'value'   => 1,
						'compare' => '!=',
					),
					array(
						'key'     => '_cf7etm_has_files',
						'compare' => 'NOT EXISTS',
					),
				);
				break;
		}

		$query = new WP_Query( $args );

		$this->items  = $query->posts;
		$this->counts = $this->build_counts( $assigned );

		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page'    => $per_page,
				'total_pages' => $query->max_num_pages,
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
	}

	/**
	 * Counts for the filter links.
	 *
	 * @param array $assigned Assigned template IDs.
	 * @return array
	 */
	private function build_counts( $assigned ) {
		$counts     = CF7ETM_Template_Post_Type::counts();
		$with_files = CF7ETM_Template_Post_Type::count_with_files();

		return array(
			'all'      => $counts['total'],
			'publish'  => $counts['publish'],
			'private'  => $counts['private'],
			'draft'    => $counts['draft'],
			'html'     => CF7ETM_Template_Post_Type::count_by_type( 'html' ),
			'text'     => CF7ETM_Template_Post_Type::count_by_type( 'text' ),
			'assigned' => count( $assigned ),
			'unused'   => max( 0, $counts['total'] - count( $assigned ) ),
			'files'    => $with_files,
			'nofiles'  => max( 0, $counts['total'] - $with_files ),
		);
	}

	/**
	 * Runs the selected bulk action.
	 */
	public function process_bulk_action() {
		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );
		CF7ETM_Plugin::require_cap();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked immediately above.
		$ids = array_map( 'absint', (array) ( $_REQUEST['template'] ?? array() ) );
		$ids = array_filter( $ids );

		foreach ( $ids as $id ) {
			if ( ! CF7ETM_Template_Post_Type::get( $id ) ) {
				continue;
			}

			if ( 'delete' === $action ) {
				// Assigned templates are protected from bulk deletion too.
				if ( ! CF7ETM_CF7_Bridge::forms_using( $id ) ) {
					wp_trash_post( $id );
				}

				continue;
			}

			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => 'activate' === $action ? 'publish' : 'private',
				)
			);
		}
	}

	/**
	 * Checkbox column.
	 *
	 * @param WP_Post $item Template post.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="template[]" value="%d" aria-label="%s" />',
			(int) $item->ID,
			/* translators: %s: template name */
			esc_attr( sprintf( __( 'Select %s', 'cf7-email-template-manager' ), $item->post_title ) )
		);
	}

	/**
	 * Name column, with row actions.
	 *
	 * @param WP_Post $item Template post.
	 * @return string
	 */
	public function column_name( $item ) {
		$edit_url = CF7ETM_Plugin::url( 'template-edit', array( 'template' => $item->ID ) );

		$name = sprintf(
			'<a class="row-title" href="%s">%s</a>',
			esc_url( $edit_url ),
			esc_html( $item->post_title ? $item->post_title : __( '(no name)', 'cf7-email-template-manager' ) )
		);

		if ( $item->post_excerpt ) {
			$name .= '<div class="cf7etm-muted">' . esc_html( wp_trim_words( $item->post_excerpt, 16 ) ) . '</div>';
		}

		$actions = array(
			'edit'      => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'cf7-email-template-manager' ) ),
			'preview'   => sprintf(
				'<a href="%s#preview" data-cf7etm-preview="%d">%s</a>',
				esc_url( $edit_url ),
				(int) $item->ID,
				esc_html__( 'Preview', 'cf7-email-template-manager' )
			),
			'duplicate' => sprintf(
				'<a href="#" data-cf7etm-duplicate="%d">%s</a>',
				(int) $item->ID,
				esc_html__( 'Duplicate', 'cf7-email-template-manager' )
			),
			'assign'    => sprintf(
				'<a href="%s">%s</a>',
				esc_url( CF7ETM_Plugin::url( 'assignments', array( 'template' => $item->ID ) ) ),
				esc_html__( 'Assign', 'cf7-email-template-manager' )
			),
			'delete'    => sprintf(
				'<a href="#" class="cf7etm-danger-link" data-cf7etm-delete="%d" data-name="%s">%s</a>',
				(int) $item->ID,
				esc_attr( $item->post_title ),
				esc_html__( 'Delete', 'cf7-email-template-manager' )
			),
		);

		return $name . $this->row_actions( $actions );
	}

	/**
	 * Type column.
	 *
	 * @param WP_Post $item Template post.
	 * @return string
	 */
	public function column_type( $item ) {
		$type = get_post_meta( $item->ID, '_cf7etm_type', true );

		return 'text' === $type
			? '<span class="cf7etm-badge cf7etm-badge--neutral">' . esc_html__( 'Plain Text', 'cf7-email-template-manager' ) . '</span>'
			: '<span class="cf7etm-badge cf7etm-badge--info">' . esc_html__( 'HTML', 'cf7-email-template-manager' ) . '</span>';
	}

	/**
	 * Assigned forms column.
	 *
	 * @param WP_Post $item Template post.
	 * @return string
	 */
	public function column_forms( $item ) {
		$form_ids = CF7ETM_CF7_Bridge::forms_using( $item->ID );

		if ( ! $form_ids ) {
			return '<span class="cf7etm-muted">' . esc_html__( 'Not assigned', 'cf7-email-template-manager' ) . '</span>';
		}

		$forms = CF7ETM_CF7_Bridge::forms();
		$names = array();

		foreach ( $form_ids as $form_id ) {
			$names[] = esc_html( $forms[ $form_id ] ?? sprintf( '#%d', $form_id ) );
		}

		return implode( '<br />', $names );
	}

	/**
	 * File support column.
	 *
	 * @param WP_Post $item Template post.
	 * @return string
	 */
	public function column_files( $item ) {
		$spec = trim( (string) get_post_meta( $item->ID, '_cf7etm_attachments', true ) );

		if ( '' === $spec ) {
			return '<span class="cf7etm-muted">' . esc_html__( 'None', 'cf7-email-template-manager' ) . '</span>';
		}

		$count = count( explode( "\n", $spec ) );

		return sprintf(
			'<span class="cf7etm-badge cf7etm-badge--info">%s</span>',
			esc_html(
				sprintf(
					/* translators: %d: number of file upload fields attached */
					_n( '%d file field', '%d file fields', $count, 'cf7-email-template-manager' ),
					$count
				)
			)
		);
	}

	/**
	 * Status column. Never colour alone: the label carries the meaning.
	 *
	 * @param WP_Post $item Template post.
	 * @return string
	 */
	public function column_status( $item ) {
		$modifier = match ( $item->post_status ) {
			'publish' => 'success',
			'private' => 'neutral',
			default   => 'warning',
		};

		return sprintf(
			'<span class="cf7etm-badge cf7etm-badge--%s">%s</span>',
			esc_attr( $modifier ),
			esc_html( CF7ETM_Template_Post_Type::status_label( $item->post_status ) )
		);
	}

	/**
	 * Updated column.
	 *
	 * @param WP_Post $item Template post.
	 * @return string
	 */
	public function column_modified( $item ) {
		$timestamp = get_post_timestamp( $item, 'modified' );

		if ( ! $timestamp ) {
			return '—';
		}

		return sprintf(
			'<span title="%s">%s</span>',
			esc_attr( wp_date( 'Y-m-d H:i', $timestamp ) ),
			esc_html(
				sprintf(
					/* translators: %s: human-readable time difference */
					__( '%s ago', 'cf7-email-template-manager' ),
					human_time_diff( $timestamp )
				)
			)
		);
	}

	/**
	 * Author column.
	 *
	 * @param WP_Post $item Template post.
	 * @return string
	 */
	public function column_author( $item ) {
		$author = get_the_author_meta( 'display_name', $item->post_author );

		return $author ? esc_html( $author ) : '—';
	}

	/**
	 * Empty state.
	 */
	public function no_items() {
		// Rendered by the view so the markup stays in one place.
		echo '';
	}
}
