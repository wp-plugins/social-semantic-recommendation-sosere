/**
* Menu functions
* @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
* @package: sosere-ee
* © Arthur Kaiser, all rights reserved
*/
jQuery( '.sosere-nav-tab-wrapper a' ).click( function ( ) {
	jQuery( '.sosere-nav-tab-wrapper a').removeClass( 'nav-tab-active' );
	jQuery( this ).addClass( 'nav-tab-active' );
	var tab_index = this.id.replace( 'sosere-nav-tab-', '' );
	jQuery( 'div .wrap form table').hide();
	jQuery( 'div .wrap form table:eq('+tab_index+')' ).show();
});

var current_hash = window.location.hash.replace( '#', '' );
var current_tab = jQuery.inArray(current_hash, sosere_nav_tabs);

if ( current_tab == -1) {
 current_tab = 0;
}
jQuery( '.sosere-nav-tab-wrapper a' ).removeClass( 'nav-tab-active' );
jQuery( '.sosere-nav-tab-wrapper a' ).each( function( index ) {

	if ( index == current_tab ) {
		jQuery( '.sosere-nav-tab-wrapper a:eq('+index+')' ).addClass( 'nav-tab-active' );
		jQuery( 'div .wrap form table:eq('+index+')' ).show();
	}else {
		jQuery( 'div .wrap form table:eq('+index+')' ).hide();
	}
});


/**
*	Options functions
*/


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
/*
* exclude entries
*/
// exclude tags
jQuery( "#settings_list_include_tags, #settings_list_exclude_tags" ).sortable({
	connectWith: ".settings_list_tags",
	cursor: 'move',
    update: function() {
		jQuery( 'input#exclude_tags' ).val( jQuery( '#settings_list_exclude_tags' ).sortable( 'toArray' ).join( ',' ).replace( /[a-zA-Z_]/gi, "" ) ); 
	}
}).disableSelection();



//exclude_tags
