<?php

/**
 */
class KT_Media_Custom_Links {

	private static $instance = null;

	public function __construct() {
		// Add the filter for editing the custom url field
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_media_link_input' ), null, 2 );
		
		// Add the filter for saving the custom url field
		add_filter( 'attachment_fields_to_save', array( $this, 'add_media_link_save' ), null , 2 );
		

	} // end __construct

	public function add_media_link_input( $form_fields, $post ) {
		$form_fields['gallery_link_url'] = array(
			'label' => __( 'Gallery Link URL', 'kadence-galleries' ),
			'input' => 'text',
			'value' => get_post_meta( $post->ID, '_gallery_link_url', true )
		);
		// Gallery Link Target field
		$target_value = get_post_meta( $post->ID, '_gallery_link_target', true );
		$form_fields['gallery_link_target'] = array(
			'label' => __( 'Gallery Link Target', 'kadence-galleries' ),
			'input'	=> 'html',
			'html'	=> '
				<select name="attachments['.$post->ID.'][gallery_link_target]" id="attachments['.$post->ID.'][gallery_link_target]">
					<option value="">'.__( 'Do Not Change', 'kadence-galleries' ).'</option>
					<option value="_self"'.($target_value == '_self' ? ' selected="selected"' : '').'>'.__( 'Same Window', 'kadence-galleries' ).'</option>
					<option value="_blank"'.($target_value == '_blank' ? ' selected="selected"' : '').'>'.__( 'New Window', 'kadence-galleries' ).'</option>
				</select>'
		);
		return $form_fields;
	}


	public function add_media_link_save( $post, $attachment ) {

		if ( isset( $attachment['gallery_link_url'] ) ) {
			update_post_meta( $post['ID'], '_gallery_link_url', $attachment['gallery_link_url'] );
		}
		if ( isset( $attachment['gallery_link_target'] ) ) {
			update_post_meta( $post['ID'], '_gallery_link_target', $attachment['gallery_link_target'] );
		}
		return $post;
	}


	public static function get_instance() {

		if ( null == self::$instance )
			self::$instance = new self;

		return self::$instance;

	} // end get_instance;


}


KT_Media_Custom_Links::get_instance();
