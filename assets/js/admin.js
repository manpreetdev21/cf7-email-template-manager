/**
 * CF7 Email Template Manager — shared admin behaviour.
 *
 * Plain ES2020, no framework and no build step. window.cf7etm carries the
 * AJAX URL, the nonce and the translated strings.
 */
( function () {
	'use strict';

	const i18n = ( window.cf7etm && window.cf7etm.i18n ) || {};

	/* ------------------------------------------------------------------ */
	/* AJAX                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Posts to one of the plugin's AJAX endpoints.
	 *
	 * @param {string} action Action name without the cf7etm_ prefix.
	 * @param {Object} data   Payload; nested objects are sent as form fields.
	 * @return {Promise<Object>} The data half of the JSON response.
	 */
	function post( action, data ) {
		const body = new FormData();

		body.append( 'action', 'cf7etm_' + action );
		body.append( 'nonce', window.cf7etm.nonce );

		append( body, data || {}, null );

		return fetch( window.cf7etm.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} )
			.then( ( response ) => response.json() )
			.then( ( json ) => {
				if ( ! json || ! json.success ) {
					throw new Error( ( json && json.data && json.data.message ) || i18n.error );
				}

				return json.data;
			} );
	}

	/**
	 * Flattens nested objects into PHP-style form field names.
	 *
	 * @param {FormData} body   Target.
	 * @param {Object}   values Source values.
	 * @param {string}   prefix Parent key, if any.
	 */
	function append( body, values, prefix ) {
		Object.keys( values ).forEach( ( key ) => {
			const name = prefix ? prefix + '[' + key + ']' : key;
			const value = values[ key ];

			if ( value !== null && typeof value === 'object' && ! ( value instanceof Blob ) ) {
				append( body, value, name );
				return;
			}

			body.append( name, value === null || value === undefined ? '' : value );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Toasts                                                              */
	/* ------------------------------------------------------------------ */

	let toastHost = null;

	/**
	 * Shows a non-blocking notification.
	 *
	 * @param {string} message Text to show.
	 * @param {string} type    success | error | warning | info.
	 */
	function toast( message, type ) {
		if ( ! toastHost ) {
			toastHost = document.createElement( 'div' );
			toastHost.className = 'cf7etm-toasts cf7etm';
			toastHost.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( toastHost );
		}

		const node = document.createElement( 'div' );

		node.className = 'cf7etm-toast cf7etm-toast--' + ( type || 'success' );
		node.textContent = message;
		toastHost.appendChild( node );

		setTimeout( () => node.remove(), 4500 );
	}

	/* ------------------------------------------------------------------ */
	/* Modal                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Opens an accessible modal dialog.
	 *
	 * @param {Object} options title, body (HTML string or Node), buttons, wide.
	 * @return {Object} A handle with a close() method.
	 */
	function modal( options ) {
		const previous = document.activeElement;

		const overlay = document.createElement( 'div' );
		overlay.className = 'cf7etm-modal cf7etm' + ( options.wide ? ' cf7etm-modal--wide' : '' );

		const box = document.createElement( 'div' );
		box.className = 'cf7etm-modal__box';
		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', 'true' );
		box.setAttribute( 'aria-label', options.title || '' );

		const head = document.createElement( 'div' );
		head.className = 'cf7etm-modal__head';
		head.innerHTML = '<h2></h2>';
		head.querySelector( 'h2' ).textContent = options.title || '';

		const close = document.createElement( 'button' );
		close.type = 'button';
		close.className = 'cf7etm-modal__close';
		close.setAttribute( 'aria-label', i18n.close || 'Close' );
		close.innerHTML = '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>';
		head.appendChild( close );

		const body = document.createElement( 'div' );
		body.className = 'cf7etm-modal__body' + ( options.flush ? ' cf7etm-modal__body--flush' : '' );

		if ( options.body instanceof Node ) {
			body.appendChild( options.body );
		} else {
			body.textContent = options.body || '';
		}

		box.appendChild( head );
		box.appendChild( body );

		if ( options.buttons && options.buttons.length ) {
			const foot = document.createElement( 'div' );
			foot.className = 'cf7etm-modal__foot';

			options.buttons.forEach( ( config ) => {
				const button = document.createElement( 'button' );

				button.type = 'button';
				button.className = 'cf7etm-btn ' + ( config.className || '' );
				button.textContent = config.label;
				button.addEventListener( 'click', () => config.onClick ? config.onClick( handle ) : handle.close() );
				foot.appendChild( button );
			} );

			box.appendChild( foot );
		}

		overlay.appendChild( box );
		document.body.appendChild( overlay );

		const handle = {
			overlay: overlay,
			body: body,
			close: function () {
				overlay.remove();
				document.removeEventListener( 'keydown', onKeydown, true );

				if ( previous && previous.focus ) {
					previous.focus();
				}
			},
		};

		/**
		 * Escape closes; Tab stays inside the dialog.
		 *
		 * @param {KeyboardEvent} event Key event.
		 */
		function onKeydown( event ) {
			if ( event.key === 'Escape' ) {
				event.preventDefault();
				handle.close();
				return;
			}

			if ( event.key !== 'Tab' ) {
				return;
			}

			const focusable = box.querySelectorAll( 'button, [href], input, select, textarea, iframe, [tabindex]:not([tabindex="-1"])' );

			if ( ! focusable.length ) {
				return;
			}

			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];

			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		}

		close.addEventListener( 'click', handle.close );
		overlay.addEventListener( 'mousedown', ( event ) => {
			if ( event.target === overlay ) {
				handle.close();
			}
		} );
		document.addEventListener( 'keydown', onKeydown, true );

		( box.querySelector( '.cf7etm-modal__foot .cf7etm-btn' ) || close ).focus();

		return handle;
	}

	/**
	 * Confirmation dialog for destructive actions.
	 *
	 * @param {Object}   options  title, message, confirmLabel, danger.
	 * @param {Function} onAccept Called when the user confirms.
	 */
	function confirmAction( options, onAccept ) {
		modal( {
			title: options.title || i18n.confirm,
			body: options.message || '',
			buttons: [
				{
					label: i18n.cancel || 'Cancel',
					onClick: ( handle ) => handle.close(),
				},
				{
					label: options.confirmLabel || i18n.confirm,
					className: options.danger === false ? 'cf7etm-btn--primary' : 'cf7etm-btn--danger',
					onClick: ( handle ) => {
						handle.close();
						onAccept();
					},
				},
			],
		} );
	}

	/* ------------------------------------------------------------------ */
	/* List table row actions                                              */
	/* ------------------------------------------------------------------ */

	document.addEventListener( 'click', ( event ) => {
		const duplicate = event.target.closest( '[data-cf7etm-duplicate]' );

		if ( duplicate ) {
			event.preventDefault();

			post( 'duplicate_template', { template_id: duplicate.dataset.cf7etmDuplicate } )
				.then( ( data ) => {
					toast( data.message, 'success' );
					window.location.href = data.editUrl;
				} )
				.catch( ( error ) => toast( error.message, 'error' ) );

			return;
		}

		const remove = event.target.closest( '[data-cf7etm-delete]' );

		if ( remove ) {
			event.preventDefault();

			const id = remove.dataset.cf7etmDelete;
			const name = remove.dataset.name || '';

			confirmAction(
				{
					title: i18n.deleteTitle,
					message: name ? i18n.deleteBody + ' (' + name + ')' : i18n.deleteBody,
					confirmLabel: i18n.deleteButton,
				},
				() => {
					post( 'delete_template', { template_id: id } )
						.then( ( data ) => {
							toast( data.message, 'success' );

							const row = remove.closest( 'tr' );

							if ( row ) {
								row.remove();
							}
						} )
						.catch( ( error ) => toast( error.message, 'error' ) );
				}
			);
		}
	} );

	/* ------------------------------------------------------------------ */
	/* Assignments screen                                                  */
	/* ------------------------------------------------------------------ */

	document.querySelectorAll( '.cf7etm-assign' ).forEach( ( cell ) => {
		const row = cell.closest( 'tr' );
		const select = cell.querySelector( '[data-template-select]' );
		const apply = cell.querySelector( '[data-action="apply"]' );
		const detach = cell.querySelector( '[data-action="detach"]' );

		if ( ! row || ! select ) {
			return;
		}

		const payload = () => ( {
			form_id: row.dataset.formId,
			slot: cell.dataset.slot,
			template_id: select.value,
		} );

		if ( apply ) {
			apply.addEventListener( 'click', () => {
				if ( select.value === '0' ) {
					// "No template" through Apply means detach.
					detach.click();
					return;
				}

				apply.disabled = true;

				post( 'assign', payload() )
					.then( ( data ) => {
						toast( data.message, 'success' );
						detach.disabled = false;
					} )
					.catch( ( error ) => toast( error.message, 'error' ) )
					.finally( () => {
						apply.disabled = false;
					} );
			} );
		}

		if ( detach ) {
			detach.addEventListener( 'click', () => {
				detach.disabled = true;

				post( 'detach', { form_id: row.dataset.formId, slot: cell.dataset.slot } )
					.then( ( data ) => {
						toast( data.message, 'success' );
						select.value = '0';
					} )
					.catch( ( error ) => {
						toast( error.message, 'error' );
						detach.disabled = false;
					} );
			} );
		}
	} );

	/* ------------------------------------------------------------------ */
	/* Branding screen                                                     */
	/* ------------------------------------------------------------------ */

	const mediaButton = document.querySelector( '[data-media-choose]' );

	if ( mediaButton && window.wp && window.wp.media ) {
		const field = document.querySelector( '[data-media-field]' );
		const preview = document.querySelector( '[data-media-preview]' );

		let frame = null;

		mediaButton.addEventListener( 'click', () => {
			if ( ! frame ) {
				frame = window.wp.media( { multiple: false, library: { type: 'image' } } );

				frame.on( 'select', () => {
					const attachment = frame.state().get( 'selection' ).first().toJSON();

					field.value = attachment.url;

					if ( preview ) {
						preview.src = attachment.url;
						preview.hidden = false;
					}
				} );
			}

			frame.open();
		} );
	}

	if ( window.jQuery && document.querySelector( '.cf7etm-color' ) ) {
		window.jQuery( '.cf7etm-color' ).wpColorPicker();
	}

	/* ------------------------------------------------------------------ */
	/* Export                                                              */
	/* ------------------------------------------------------------------ */

	window.cf7etmUI = { post, toast, modal, confirmAction };
} )();
