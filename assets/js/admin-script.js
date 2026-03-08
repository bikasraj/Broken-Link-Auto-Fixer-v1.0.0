/**
 * Broken Link Auto Fixer — Admin JavaScript
 *
 * Handles:
 *  - Start Scan button + progress animation
 *  - Replace URL modal
 *  - Remove link / Ignore / Delete actions
 *
 * All AJAX data (ajax_url, nonce, i18n strings) is injected via
 * wp_localize_script() as the global `blaf_ajax` object.
 *
 * @package BrokenLinkAutoFixer
 * @author  Bikas Kumar <bikas@codesala.in>
 * @company CodeSala — codesala.in
 */

/* global blaf_ajax, jQuery */
( function ( $ ) {
	'use strict';

	// ── Cached DOM references ───────────────────────────────────────────────
	var $scanBtn        = $( '#blaf-start-scan' );
	var $progressWrap   = $( '#blaf-progress-wrapper' );
	var $progressBar    = $( '#blaf-progress-bar' );
	var $progressText   = $( '#blaf-progress-text' );
	var $scanResult     = $( '#blaf-scan-result' );
	var $modalOverlay   = $( '#blaf-modal-overlay' );
	var $newUrlInput    = $( '#blaf-new-url' );
	var $confirmReplace = $( '#blaf-confirm-replace' );
	var $cancelReplace  = $( '#blaf-cancel-replace' );
	var $modalResult    = $( '#blaf-modal-result' );
	var currentRecordId = null;

	// ── Helper: show/hide notice ────────────────────────────────────────────
	function showNotice( $el, message, type ) {
		$el
			.removeClass( 'is-success is-error' )
			.addClass( type === 'success' ? 'is-success' : 'is-error' )
			.text( message )
			.show();
	}

	// ── Helper: animate progress bar ───────────────────────────────────────
	function animateProgress( target, duration, callback ) {
		var current = parseInt( $progressBar.css( 'width' ) ) || 0;
		var step    = ( target - current ) / ( duration / 50 );

		var interval = setInterval( function () {
			current += step;
			if ( ( step > 0 && current >= target ) || ( step < 0 && current <= target ) ) {
				current = target;
				clearInterval( interval );
				if ( typeof callback === 'function' ) {
					callback();
				}
			}
			$progressBar.css( 'width', current + '%' );
		}, 50 );
	}

	// ── Scan Button ─────────────────────────────────────────────────────────
	$scanBtn.on( 'click', function () {
		$scanBtn.prop( 'disabled', true ).text( blaf_ajax.scan_text );
		$scanResult.hide();
		$progressBar.css( 'width', '0%' );
		$progressWrap.show();

		// Animate to 30% quickly to indicate start.
		animateProgress( 30, 600 );

		$.ajax( {
			url:    blaf_ajax.ajax_url,
			method: 'POST',
			data: {
				action: 'blaf_start_scan',
				nonce:  blaf_ajax.nonce,
			},
			success: function ( response ) {
				animateProgress( 100, 800, function () {
					$progressWrap.hide();

					if ( response.success ) {
						showNotice( $scanResult, response.data.message, 'success' );
						// Update total count badge without page reload.
						if ( typeof response.data.found !== 'undefined' ) {
							$( '#blaf-total-count' ).text( response.data.found );
							$( '.blaf-badge' ).text( response.data.found );
						}
						// Reload the table to show new results.
						setTimeout( function () {
							window.location.reload();
						}, 1800 );
					} else {
						showNotice( $scanResult, response.data.message || blaf_ajax.error_text, 'error' );
					}
				} );
			},
			error: function () {
				$progressWrap.hide();
				showNotice( $scanResult, blaf_ajax.error_text, 'error' );
			},
			complete: function () {
				$scanBtn.prop( 'disabled', false ).text( blaf_ajax.done_text );
				setTimeout( function () {
					$scanBtn.html( '<span class="dashicons dashicons-search"></span> Scan Website Now' );
				}, 3000 );
			},
		} );

		// Poll progress every 2 s while scan is running.
		var pollTimer = setInterval( function () {
			$.post( blaf_ajax.ajax_url, {
				action: 'blaf_scan_progress',
				nonce:  blaf_ajax.nonce,
			}, function ( res ) {
				if ( res.success && ! res.data.running ) {
					clearInterval( pollTimer );
				}
				if ( res.success && res.data.last_scan ) {
					$( '.blaf-stat-last-scan' ).text( res.data.last_scan );
				}
			} );
		}, 2000 );
	} );

	// ── Replace URL — open modal ─────────────────────────────────────────────
	$( document ).on( 'click', '.blaf-btn-replace', function () {
		currentRecordId = $( this ).data( 'id' );
		var oldUrl      = $( this ).data( 'url' );

		$modalOverlay.find( '.blaf-modal-old-url' ).text( oldUrl );
		$newUrlInput.val( '' );
		$modalResult.hide();
		$modalOverlay.show();
		$newUrlInput.focus();
	} );

	// Cancel modal.
	$cancelReplace.on( 'click', function () {
		$modalOverlay.hide();
		currentRecordId = null;
	} );

	// Close modal on overlay click.
	$modalOverlay.on( 'click', function ( e ) {
		if ( $( e.target ).is( $modalOverlay ) ) {
			$modalOverlay.hide();
			currentRecordId = null;
		}
	} );

	// Close modal on Escape key.
	$( document ).on( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && $modalOverlay.is( ':visible' ) ) {
			$modalOverlay.hide();
			currentRecordId = null;
		}
	} );

	// Confirm replace.
	$confirmReplace.on( 'click', function () {
		var newUrl = $newUrlInput.val().trim();
		if ( ! newUrl ) {
			showNotice( $modalResult, 'Please enter a new URL.', 'error' );
			return;
		}

		$confirmReplace.prop( 'disabled', true ).text( 'Replacing…' );

		$.ajax( {
			url:    blaf_ajax.ajax_url,
			method: 'POST',
			data: {
				action:    'blaf_replace_url',
				nonce:     blaf_ajax.nonce,
				record_id: currentRecordId,
				new_url:   newUrl,
			},
			success: function ( response ) {
				if ( response.success ) {
					showNotice( $modalResult, response.data.message, 'success' );
					fadeOutRow( currentRecordId );
					setTimeout( function () {
						$modalOverlay.hide();
					}, 1200 );
				} else {
					showNotice( $modalResult, response.data.message, 'error' );
				}
			},
			error: function () {
				showNotice( $modalResult, blaf_ajax.error_text, 'error' );
			},
			complete: function () {
				$confirmReplace.prop( 'disabled', false ).text( 'Replace' );
			},
		} );
	} );

	// ── Remove Link ─────────────────────────────────────────────────────────
	$( document ).on( 'click', '.blaf-btn-remove', function () {
		var $btn      = $( this );
		var recordId  = $btn.data( 'id' );
		var confirmMsg = $btn.data( 'confirm' ) || 'Remove this link from post content?';

		if ( ! window.confirm( confirmMsg ) ) {
			return;
		}

		$btn.prop( 'disabled', true ).text( '…' );

		$.ajax( {
			url:    blaf_ajax.ajax_url,
			method: 'POST',
			data: {
				action:    'blaf_remove_link',
				nonce:     blaf_ajax.nonce,
				record_id: recordId,
			},
			success: function ( response ) {
				if ( response.success ) {
					fadeOutRow( recordId );
				} else {
					alert( response.data.message );
					$btn.prop( 'disabled', false ).text( 'Remove Link' );
				}
			},
			error: function () {
				alert( blaf_ajax.error_text );
				$btn.prop( 'disabled', false ).text( 'Remove Link' );
			},
		} );
	} );

	// ── Ignore ───────────────────────────────────────────────────────────────
	$( document ).on( 'click', '.blaf-btn-ignore', function () {
		var $btn     = $( this );
		var recordId = $btn.data( 'id' );

		$btn.prop( 'disabled', true ).text( '…' );

		$.post( blaf_ajax.ajax_url, {
			action:    'blaf_ignore_link',
			nonce:     blaf_ajax.nonce,
			record_id: recordId,
		}, function ( response ) {
			if ( response.success ) {
				fadeOutRow( recordId );
			} else {
				alert( response.data.message );
				$btn.prop( 'disabled', false ).text( 'Ignore' );
			}
		} );
	} );

	// ── Delete Record ────────────────────────────────────────────────────────
	$( document ).on( 'click', '.blaf-btn-delete', function () {
		var $btn      = $( this );
		var recordId  = $btn.data( 'id' );
		var confirmMsg = $btn.data( 'confirm' ) || 'Delete this record?';

		if ( ! window.confirm( confirmMsg ) ) {
			return;
		}

		$btn.prop( 'disabled', true ).text( '…' );

		$.post( blaf_ajax.ajax_url, {
			action:    'blaf_delete_record',
			nonce:     blaf_ajax.nonce,
			record_id: recordId,
		}, function ( response ) {
			if ( response.success ) {
				fadeOutRow( recordId );
			} else {
				alert( response.data.message );
				$btn.prop( 'disabled', false ).text( 'Delete' );
			}
		} );
	} );

	// ── Helpers ─────────────────────────────────────────────────────────────

	/**
	 * Fade out and remove a table row by record ID.
	 *
	 * @param {number} recordId
	 */
	function fadeOutRow( recordId ) {
		var $row = $( '#blaf-row-' + recordId );
		$row.addClass( 'blaf-row-removing' );
		setTimeout( function () {
			$row.remove();
			updateBadgeCount( -1 );
		}, 450 );
	}

	/**
	 * Increment / decrement the broken-link count badge.
	 *
	 * @param {number} delta
	 */
	function updateBadgeCount( delta ) {
		var $badge    = $( '.blaf-badge' );
		var $counter  = $( '#blaf-total-count' );
		var current   = parseInt( $badge.text(), 10 ) || 0;
		var newCount  = Math.max( 0, current + delta );
		$badge.text( newCount );
		$counter.text( newCount );
	}

} )( jQuery );
