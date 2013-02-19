<?php 
class SkywordOpengraph {

	function __construct() {
		//Only add tags if site does not have yoast plugin enabled
		if(!in_array( 'wordpress-seo/wp-seo.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			add_action( 'wp_head', array( $this, 'head' ), 1, 1 );
			add_filter( 'wp_title', array($this, 'title' ));
		}
		
	}
	public function head(){
		global $wp_query;
		$current_post = $wp_query->post;
		//Only display meta tags if on single post page
		if (is_singular()){
		 	$description = get_metadata("post",$current_post->ID,"skyword_metadescription",true);
		 	$title = !(get_metadata("post",$current_post->ID,"skyword_metatitle",true)) ? $current_post->post_title : get_metadata("post",$current_post->ID,"skyword_metatitle",true);
		 	$image = $this->getImage($current_post);		 	
		 	echo "<meta property='og:title' content='".esc_attr($title)."'/>";
			echo "<meta property='og:description' content='" .esc_attr($description)."'/>\n";
			echo "<meta property='og:url' content='" .get_permalink($current_post->ID)."'/>\n";
			echo "<meta property='og:site_name' content='" .get_option('blogname'). "'/>\n";
			echo "<meta property='og:type' content='article'/>";
			if (isset($image)){
				echo "<meta property='og:image' content='".esc_attr($image)."'/>\n";
			}
		}
		return;
	
	}
	public function title(){
		global $wp_query;
		$current_post = $wp_query->post;
		//Only display meta tags if on single post page
		if (is_singular()){
			return !(get_metadata("post",$current_post->ID,"skyword_metatitle",true)) ? $current_post->post_title : get_metadata("post",$current_post->ID,"skyword_metatitle",true)."  ";
		}
	}
	private function getImage($current_post){
		//First try for featured image
		if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $current_post->ID ) ) {
			$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $current_post->ID ), 'thumbnail' );
		 	$image = $thumb[0];
		}
		//if not found, use an attached image
		if (!isset($image)){
			$args = array('post_type' => 'attachment','post_mime_type' => 'image','post_parent' => $current_post->ID);
			$images = get_posts( $args );
			foreach($images as $image){
				$thumb = wp_get_attachment_image_src($image->ID, 'thumbnail'); 
				return $thumb[0];
			}
		}

		return $image;
	}

}
global $skyword_opengraph;
$skyword_opengraph = new SkywordOpengraph;
