jQuery(document).ready(function($) {
	jQuery( '[name="wpcf7-redirect-open-in-new-tab"]' ).change(function() {
        if ( jQuery( this ).is( ":checked" ) ) {
            jQuery( '.field-notice-alert' ).removeClass( 'field-notice-hidden' );
        } else {
        	jQuery( '.field-notice-alert' ).addClass( 'field-notice-hidden' );
        }
    });

    if ( jQuery( '[name="wpcf7-redirect-open-in-new-tab"]' ).is( ":checked" ) ) {
    	jQuery( '.field-notice-alert' ).removeClass( 'field-notice-hidden' );
    }
});