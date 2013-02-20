<?php 
class CustomShortcodes {

	function __construct() {
		@add_shortcode('cf', array($this, 'customfields_shortcode'));
	}
	function customfields_shortcode($atts, $text) {
		global $post;
		return get_post_meta($post->ID, $text, true);
	}
}
global $custom_shortcodes;
$custom_shortcodes = new CustomShortcodes;