<?php 
class Skyword_Shortcode {

	function __construct() {
		@add_shortcode( 'cf', array($this, 'customfields_shortcode') );
		@add_shortcode( 'skyword_tracking', array($this, 'skyword_tracking') );
	}

	function customfields_shortcode( $atts, $text ) {
		global $post;
		return get_post_meta( $post->ID, $text, true );
	}

	function skyword_tracking($atts){
		global $post;

		if(!isset($atts['id'])){
			$atts['id'] = get_post_meta($post->ID, 'skyword_content_id', true);
		}

		return "<script async='' type='text/javascript' src='//tracking.skyword.com/tracker.js?contentId={$atts['id']}'></script>";
	}
}
global $custom_shortcodes;
$custom_shortcodes = new Skyword_Shortcode;