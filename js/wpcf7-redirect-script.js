jQuery(document).ready(function() {
    wpcf7_redirect_mailsent_handler();
});

function wpcf7_redirect_mailsent_handler() {
	document.addEventListener( 'wpcf7mailsent', function( event ) {
		var data = {
			'action': 'is_multisite',
		};
		form = wpcf7_redirect_forms [ event.detail.contactFormId ];
		
		jQuery.post(form.ajaxurl, data, function (is_multisite) {
			var url = window.location.protocol + window.location.hostname;
			var port = window.location.port;
			// Script to run after sent.
			if ( form.after_sent_script ) {
				eval( form.after_sent_script );
			}


			if (is_multisite == "TRUE" && form.use_relative_url) {
				if (form.use_relative_url && form.relative_url || form.use_relative_url && form.relative_url && form.use_external_url) {
					if (form.http_build_query) {
						// Build http query
						http_query = jQuery.param(event.detail.inputs, true);
						form.relative_url = form.relative_url + '?' + http_query;
					}
					if (!form.open_in_new_tab) {
						// Open in current tab
						location.href = url + form.relative_url + port;
					} else {
						// Open in external tab
						window.open(url + form.relative_url + port);
					}
				}
			} else {
			// Redirect to external URL.
			if ( form.use_external_url && form.external_url ) {
				if ( form.http_build_query ) {
					// Build http query
					http_query = jQuery.param( event.detail.inputs, true );
					form.external_url = form.external_url + '?' + http_query;
				}

				if ( ! form.open_in_new_tab ) {
					// Open in current tab
					location.href = form.external_url;
				} else {
					// Open in external tab
					window.open( form.external_url );
				}
			}

			// Redirect to a page in this site.
			else if ( form.thankyou_page_url ) {
				if ( form.http_build_query ) {
					// Build http query
					http_query = jQuery.param( event.detail.inputs, true );
					form.thankyou_page_url = form.thankyou_page_url + '?' + http_query;
				}

				if ( ! form.open_in_new_tab ) {
					// Open in current tab
					location.href = form.thankyou_page_url;
				} else {
					// Open in new tab
					window.open( form.thankyou_page_url );
				}
			}

	}, false );
	});
}
