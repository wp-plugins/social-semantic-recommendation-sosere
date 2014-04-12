// Uploading files
var sosere_wp_file_frame;
jQuery('#default_thumb_button').live('click', function( event ){
	// prevent default
	event.preventDefault();
	// reopen 
	if ( sosere_wp_file_frame ) {
		sosere_wp_file_frame.open();
		return;
	}
	 
	// Create the media frame.
	sosere_wp_file_frame = wp.media.frames.sosere_wp_file_frame = wp.media({
		title: jQuery( this ).data( 'uploader_title' ),
		button: {
		text: jQuery( this ).data( 'uploader_button_text' ),
		},
		multiple: false 
	});
	 
	// When a thumbnail is selected, run a callback.
	sosere_wp_file_frame.on( 'select', function() {
		// multiple is set to false so only get one image from the uploader
		attachment = sosere_wp_file_frame.state().get('selection').first().toJSON();
		//sosere-thumb
		if ( attachment.sizes.sosere_thumb ) {
			jQuery('#default_thumbnail_img').attr('src', attachment.sizes.sosere_thumb.url);
			jQuery('#default_thumbnail_img_url').val(attachment.sizes.sosere_thumb.url);
		} else if ( attachment.sizes.thumbnail ) {
			jQuery('#default_thumbnail_img').attr('src', attachment.sizes.thumbnail.url);
			jQuery('#default_thumbnail_img_url').val(attachment.sizes.thumbnail.url);
		} else {
			jQuery('#default_thumbnail_img').attr('src', attachment.sizes.full.url);
			jQuery('#default_thumbnail_img_url').val(attachment.sizes.full.url);
		}
		// set attachment.id and attachment.url
		
		jQuery('#default_thumbnail_img_id').val(attachment.id);
	});
	 
	// Finally, open the modal
	sosere_wp_file_frame.open();
});
