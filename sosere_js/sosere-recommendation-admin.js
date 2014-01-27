// Uploading files
var file_frame;
jQuery('#default_thumb_button').live('click', function( event ){
 
	event.preventDefault();
	 
	// If the media frame already exists, reopen it.
	if ( file_frame ) {
		file_frame.open();
		return;
	}
	 
	// Create the media frame.
	file_frame = wp.media.frames.file_frame = wp.media({
		title: jQuery( this ).data( 'uploader_title' ),
		button: {
		text: jQuery( this ).data( 'uploader_button_text' ),
		},
		multiple: false 
	});
	 
	// When an image is selected, run a callback.
	file_frame.on( 'select', function() {
		// multiple is set to false so only get one image from the uploader
		attachment = file_frame.state().get('selection').first().toJSON();
		 
		// set attachment.id and attachment.url
		jQuery('#default_thumbnail_img').attr('src', attachment.sizes.thumbnail.url);
		jQuery('#default_thumbnail_img_url').val(attachment.sizes.thumbnail.url);
		jQuery('#default_thumbnail_img_id').val(attachment.id);
	});
	 
	// Finally, open the modal
	file_frame.open();
});
