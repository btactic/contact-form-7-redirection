jQuery(document).ready(function() {
    wpcf7_redirect_mailsent_handler();
});

function wpcf7_redirect_mailsent_handler() {
	document.addEventListener( 'wpcf7mailsent', function( event ) {
		form = wpcf7_redirect_forms [ event.detail.contactFormId ];
		
		// Run after sent script.
		if ( form.after_sent_script ) {
			eval( form.after_sent_script );
		}

		// Redirect to external URL.
		if ( form.use_external_url && form.external_url ) {
			if ( ! form.open_in_new_tab ) {
				location = form.external_url;
			} else {
				window.open( form.external_url );
			}
		}
		
		// Redirect to a page in this site.
		else if ( form.thankyou_page_url ) {
			if ( ! form.open_in_new_tab ) {
	    		location = form.thankyou_page_url;
	    	} else {
	    		window.open( form.thankyou_page_url );
	    	}
		}

	}, false );
}
