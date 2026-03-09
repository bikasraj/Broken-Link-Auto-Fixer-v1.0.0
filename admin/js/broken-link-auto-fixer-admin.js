/**
 * Admin JavaScript for Broken Link Auto Fixer
 *
 * Handles:
 *  - Batched AJAX scanning with progress display
 *  - Replace URL modal/prompt flow
 *  - Remove link with confirmation
 *  - Ignore link
 *  - Clear all results
 *  - Save settings
 *
 * @link    https://codesala.in
 * @since   1.0.0
 * @package Broken_Link_Auto_Fixer
 * @author  Bikas Kumar <bikas@codesala.in>
 */
( function ( $ ) {
    'use strict';

    // ── Aliases ────────────────────────────────────────────────────────
    var ajaxUrl = blafData.ajax_url;
    var strings = blafData.strings;

    // ── Scan flow ──────────────────────────────────────────────────────
    var scanOffset     = 0;
    var totalChecked   = 0;
    var totalBroken    = 0;
    var scanInProgress = false;

    $( '#blaf-start-scan' ).on( 'click', function () {
        if ( scanInProgress ) { return; }

        // Reset state.
        scanOffset     = 0;
        totalChecked   = 0;
        totalBroken    = 0;
        scanInProgress = true;

        $( '#blaf-scan-progress' ).show();
        $( '#blaf-progress-bar' ).css( 'width', '5%' );
        $( '#blaf-scan-status' ).text( strings.scanning );
        $( '#blaf-scan-message' ).hide().removeClass( 'blaf-notice-success blaf-notice-error' );
        $( '#blaf-start-scan' ).addClass( 'blaf-spinning' );

        runScanBatch();
    } );

    /**
     * Recursively sends AJAX scan batches until done === true.
     */
    function runScanBatch() {
        $.ajax( {
            url    : ajaxUrl,
            method : 'POST',
            data   : {
                action : 'blaf_run_scan',
                nonce  : blafData.scan_nonce,
                offset : scanOffset,
            }
        } )
        .done( function ( response ) {
            if ( ! response.success ) {
                scanFailed( response.data.message || strings.scan_error );
                return;
            }

            var data = response.data;
            totalChecked += data.checked;
            totalBroken  += data.broken;

            // Animate progress bar (indeterminate pulse between 10–90%).
            var fakePercent = Math.min( 90, 10 + ( scanOffset * 2 ) );
            $( '#blaf-progress-bar' ).css( 'width', fakePercent + '%' );
            $( '#blaf-scan-status' ).text(
                strings.scanning + ' — ' + totalChecked + ' links checked, ' + totalBroken + ' broken found'
            );

            if ( data.done ) {
                scanComplete();
            } else {
                scanOffset += 10; // 10 posts per batch (matches scanner).
                runScanBatch();
            }
        } )
        .fail( function () {
            scanFailed( strings.scan_error );
        } );
    }

    function scanComplete() {
        scanInProgress = false;
        $( '#blaf-progress-bar' ).css( 'width', '100%' );
        $( '#blaf-start-scan' ).removeClass( 'blaf-spinning' );

        setTimeout( function () {
            $( '#blaf-scan-progress' ).hide();
            $( '#blaf-progress-bar' ).css( 'width', '0%' );
        }, 800 );

        showMessage(
            strings.scan_complete + ' — ' + totalBroken + ' broken link(s) found.',
            'success'
        );

        // Update total count badge.
        $( '#blaf-total-count' ).text( totalBroken );

        // Reload table area after a short delay.
        setTimeout( function () {
            location.reload();
        }, 1500 );
    }

    function scanFailed( msg ) {
        scanInProgress = false;
        $( '#blaf-progress-bar' ).css( 'width', '0%' );
        $( '#blaf-scan-progress' ).hide();
        $( '#blaf-start-scan' ).removeClass( 'blaf-spinning' );
        showMessage( msg, 'error' );
    }

    // ── Replace URL ────────────────────────────────────────────────────
    $( document ).on( 'click', '.blaf-btn-replace', function () {
        var btn      = $( this );
        var recordId = btn.data( 'id' );
        var oldUrl   = btn.data( 'url' );

        // eslint-disable-next-line no-alert
        var newUrl = window.prompt( strings.enter_new_url, oldUrl );
        if ( ! newUrl || newUrl === oldUrl ) { return; }

        btn.addClass( 'blaf-spinning' );

        $.ajax( {
            url    : ajaxUrl,
            method : 'POST',
            data   : {
                action    : 'blaf_replace_url',
                nonce     : blafData.fix_nonce,
                record_id : recordId,
                new_url   : newUrl,
            }
        } )
        .done( function ( response ) {
            btn.removeClass( 'blaf-spinning' );
            if ( response.success ) {
                fadeRemoveRow( recordId );
                showMessage( strings.replace_success, 'success' );
            } else {
                showMessage( response.data.message, 'error' );
            }
        } )
        .fail( function () {
            btn.removeClass( 'blaf-spinning' );
            showMessage( strings.scan_error, 'error' );
        } );
    } );

    // ── Remove Link ────────────────────────────────────────────────────
    $( document ).on( 'click', '.blaf-btn-remove', function () {
        // eslint-disable-next-line no-alert
        if ( ! window.confirm( strings.confirm_remove ) ) { return; }

        var btn      = $( this );
        var recordId = btn.data( 'id' );

        btn.addClass( 'blaf-spinning' );

        $.ajax( {
            url    : ajaxUrl,
            method : 'POST',
            data   : {
                action    : 'blaf_remove_link',
                nonce     : blafData.fix_nonce,
                record_id : recordId,
            }
        } )
        .done( function ( response ) {
            btn.removeClass( 'blaf-spinning' );
            if ( response.success ) {
                fadeRemoveRow( recordId );
                showMessage( strings.remove_success, 'success' );
            } else {
                showMessage( response.data.message, 'error' );
            }
        } )
        .fail( function () {
            btn.removeClass( 'blaf-spinning' );
            showMessage( strings.scan_error, 'error' );
        } );
    } );

    // ── Ignore Link ────────────────────────────────────────────────────
    $( document ).on( 'click', '.blaf-btn-ignore', function () {
        var btn      = $( this );
        var recordId = btn.data( 'id' );

        btn.addClass( 'blaf-spinning' );

        $.ajax( {
            url    : ajaxUrl,
            method : 'POST',
            data   : {
                action    : 'blaf_ignore_link',
                nonce     : blafData.fix_nonce,
                record_id : recordId,
            }
        } )
        .done( function ( response ) {
            btn.removeClass( 'blaf-spinning' );
            if ( response.success ) {
                fadeRemoveRow( recordId );
            } else {
                showMessage( response.data.message, 'error' );
            }
        } );
    } );

    // ── Clear Results ──────────────────────────────────────────────────
    $( '#blaf-clear-results' ).on( 'click', function () {
        // eslint-disable-next-line no-alert
        if ( ! window.confirm( strings.confirm_clear ) ) { return; }

        var btn = $( this );
        btn.addClass( 'blaf-spinning' );

        $.ajax( {
            url    : ajaxUrl,
            method : 'POST',
            data   : {
                action : 'blaf_clear_results',
                nonce  : blafData.scan_nonce,
            }
        } )
        .done( function () {
            btn.removeClass( 'blaf-spinning' );
            location.reload();
        } );
    } );

    // ── Save Settings ──────────────────────────────────────────────────
    $( '#blaf-save-settings' ).on( 'click', function () {
        var btn = $( this );
        btn.addClass( 'blaf-spinning' );

        $.ajax( {
            url    : ajaxUrl,
            method : 'POST',
            data   : {
                action               : 'blaf_save_settings',
                nonce                : blafData.settings_nonce,
                auto_scan_enabled    : $( '#blaf-auto-scan' ).is( ':checked' ) ? 1 : 0,
                scan_frequency       : $( '#blaf-scan-frequency' ).val(),
                max_links_per_scan   : $( '#blaf-max-links' ).val(),
                email_notifications  : $( '#blaf-email-notifications' ).is( ':checked' ) ? 1 : 0,
                notification_email   : $( '#blaf-notification-email' ).val(),
            }
        } )
        .done( function ( response ) {
            btn.removeClass( 'blaf-spinning' );
            if ( response.success ) {
                showMessage( strings.settings_saved, 'success', '#blaf-settings-message' );
            } else {
                showMessage( response.data.message, 'error', '#blaf-settings-message' );
            }
        } )
        .fail( function () {
            btn.removeClass( 'blaf-spinning' );
        } );
    } );

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Fade out and remove a table row by record ID.
     *
     * @param {number} recordId
     */
    function fadeRemoveRow( recordId ) {
        var row = $( '#blaf-row-' + recordId );
        row.addClass( 'blaf-row-fading' );
        setTimeout( function () { row.remove(); }, 420 );
    }

    /**
     * Show a notice message.
     *
     * @param {string} msg
     * @param {string} type     'success' | 'error'
     * @param {string} selector Optional jQuery selector (default: '#blaf-scan-message').
     */
    function showMessage( msg, type, selector ) {
        var $el = $( selector || '#blaf-scan-message' );
        $el
            .removeClass( 'blaf-notice-success blaf-notice-error' )
            .addClass( 'blaf-notice-' + type )
            .text( msg )
            .show();

        setTimeout( function () { $el.fadeOut(); }, 5000 );
    }

} )( jQuery );
