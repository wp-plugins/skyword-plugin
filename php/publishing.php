<?php


/**
 * Code for post creation
 */
class SkywordPublish {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_filter( 'xmlrpc_methods', array( $this, 'skyword_xmlrpc_methods' ) );
	}

	public function skyword_xmlrpc_methods($methods){		
		$methods['skyword_post'] = array( $this, 'skyword_post');
		$methods['skyword_newMediaObject'] = array( $this, 'skyword_newMediaObject');
		$methods['skyword_author'] = array( $this, 'skyword_author');
		$methods['skyword_version'] =  array( $this,  'skyword_version');
		$methods['skyword_version_number'] = array( $this,'skyword_version_number');
		$methods['skyword_getAuthors'] = array( $this,'skyword_getAuthors');
		return $methods;
	}
	public function skyword_version($args){
		$username	= $args[1];
		$password	= $args[2];
		global $wp_xmlrpc_server;
		//Authenticate that posting user is valid
		if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
			return strval('Invalid UN/PW Combination: UN = '.$username.' PW = '.$password);
		}
		if (!user_can($user->ID, 'edit_posts')){
			return strval('You do not have sufficient privileges to login.');
		}
		return strval("Wordpress Version: ".get_bloginfo('version')." Plugin Version: ".SKYWORD_VERSION);
	}
	public function skyword_version_number($args){
		$username	= $args[1];
		$password	= $args[2];
		global $wp_xmlrpc_server;
		//Authenticate that posting user is valid
		if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
			return strval('Invalid UN/PW Combination: UN = '.$username.' PW = '.$password);
		}
		if (!user_can($user->ID, 'edit_posts')){
			return strval('You do not have sufficient privileges to login.');
		}
		return strval(SKYWORD_VN);
	}
	public function skyword_author($args){
		$username	= $args[1];
		$password	= $args[2];
		$data = $args[3];
		global $wp_xmlrpc_server;
		//Authenticate that posting user is valid
		if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
			return new IXR_Error(403, __('Invalid UN/PW Combination: UN = '.$username.' PW = '.$password));
		}
		if (!user_can($user->ID, 'edit_posts')){
			return new IXR_Error(403, __('You do not have sufficient privileges to login.'));
		}
		$user_id = $this->check_username_exists($data['user-name'], $data['display-name'], $data['first-name'],$data['last-name'], $data['email'],$data['bio']);	
		return strval($user_id);
	}
	
	public function skyword_getAuthors($args){
	
		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		global $wp_xmlrpc_server;
		if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
			return new IXR_Error(403, __('Invalid UN/PW Combination: UN = '.$username.' PW = '.$password));
		}
	
		if (!user_can($user->ID, 'edit_posts')){
			return new IXR_Error(403, __('You do not have sufficient privileges to login.'));
		}
	
		$authors = array();
		foreach ( get_users( array( 'fields' => 'all', 'role' => 'author' ) ) as $user ) {
			$authors[] = array(
				'user_id'       => $user->ID,
				'role'       => $user->role,
				'user_login'    => $user->user_login,
				'display_name'  => $user->display_name
			);
		}
	
		return $authors;
	}
	
	//Code which generates all post information
	public function skyword_post($args){
		$username	= $args[1];
		$password	= $args[2]; 
		$data = $args[3];
		global $wp_xmlrpc_server;
		error_reporting(E_ERROR);
		//Authenticate that posting user is valid
		if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
			return new IXR_Error(403, __('Invalid UN/PW Combination: UN = '.$username.' PW = '.$password));
		}
		
		if (!user_can($user->ID, 'edit_posts')){
			return new IXR_Error(403, __('You do not have sufficient privileges to login.'));
		}
	
		if (null != $data['publication-date']){
			$dateCreated = $data['publication-date']->getIso();
			$post_date = get_date_from_gmt(iso8601_to_datetime($dateCreated));
		} else {
			$post_date = current_time('mysql');
		}
		if (null != $data['publication-state']){
			$state = $data['publication-state'];
		} else {
			$state = "draft";
		}
		
		//get category ids from category names
		$categories = explode(",",$data['categories']);
		$post_category = array();
		foreach ($categories as $category){
			//check if category exists in system and create if it does not
				$categoryName = trim($category);
				$this->check_category_exists($categoryName);
				if (get_cat_ID($categoryName)==0){
					$this->check_category_exists(htmlspecialchars($categoryName));
					$post_category[] = get_cat_ID(htmlspecialchars($categoryName));
				} else {
					$post_category[] = get_cat_ID($categoryName);
				}
		}
		//check if content exists already
		$data['post-id'] = $this->check_content_exists($data['skyword_content_id']);
		if (null != $data['post-id']){
			//update existing post
			$new_post = array(
				'ID' => $data['post-id'],
				'post_title' => $data['title'],
				'post_content' => $data['description'],
				'post_status' => $state,
				'post_date' => 	$post_date,
				'post_excerpt' => $data['excerpt'],
				'post_author' => $data['user-id'],
				'post_type' => 'post',
				'comment_status' => 'open',
				'post_category' => $post_category
			);
		} else {
			//create new post
			$new_post = array(
				'post_title' => $data['title'],
				'post_content' => $data['description'],
				'post_status' => $state,
				'post_date' => 	$post_date,
				'post_excerpt' => $data['excerpt'],
				'post_author' => $data['user-id'],
				'post_type' => 'post',
				'comment_status' => 'open',
				'post_category' => $post_category
			);
		}
		$post_id = wp_insert_post($new_post);	
		wp_set_post_tags($post_id, $data['tags-input'] , false); 
		//attach attachments to new post;
		$this->attach_attachments($post_id, $data);
		//add content template/attachment information as meta 
		$this->create_custom_fields($post_id, $data);
		$this->update_custom_field($post_id, 'skyword_tracking_tag', $data['tracking']);
		$this->update_custom_field($post_id, 'skyword_seo_title', $data['metatitle']);
		$this->update_custom_field($post_id, 'skyword_metadescription', $data['metadescription']);
		$this->update_custom_field($post_id, 'skyword_keyword', $data['metakeyword']);
		$this->update_custom_field($post_id, '_yoast_wpseo_title', $data['metatitle']);
		$this->update_custom_field($post_id, '_yoast_wpseo_metadesc', $data['metadescription']);
		$this->update_custom_field($post_id, '_yoast_wpseo_focuskw', $data['keyword']);
		$this->update_custom_field($post_id, 'skyword_content_id', $data['skyword_content_id']);
		
		//Create sitemap information
		if ('news' == $data['publication-type']){
			$this->update_custom_field($post_id, 'skyword_publication_type','news');
			if (null != $data['publication-access']){
				$this->update_custom_field($post_id, 'skyword_publication_access',$data['publication-access']);
			}
			if (null != $data['publication-name']){
				$this->update_custom_field($post_id, 'skyword_publication_name',$data['publication-name']);
			}
			if (null != $data['publication-geolocation']){
				$this->update_custom_field($post_id, 'skyword_geolocation',$data['publication-geolocation']);
			}
			if (null != $data['publication-keywords']){
				$this->update_custom_field($post_id, 'skyword_tags',$data['publication-keywords']);
			}
			if (null != $data['publication-stocktickers']){
				$this->update_custom_field($post_id, 'skyword_stocktickers',$data['publication-stocktickers']);
			}
		} else {
			$this->update_custom_field($post_id, 'skyword_publication_type','evergreen');
		}
		$skyword_sitemaps_inst = new SkywordSitemaps;
		$skyword_sitemaps_inst->write_sitemaps();
		return strval($post_id);
	
	}
	
	public function skyword_newMediaObject($args) {
			global $wpdb;
			global $wp_xmlrpc_server;
			error_reporting(E_ERROR);
			$blog_ID     = (int) $args[0];
			$username  = $wpdb->escape($args[1]);
			$password   = $wpdb->escape($args[2]);
			$data        = $args[3];
	
			$name = sanitize_file_name( $data['name'] );
			$type = $data['type'];
			$bits = $data['bits'];
			$title = $data['title'];
			$caption = $data['caption'];
			$alttext = $data['alttext'];
			$description = $data['description'];
			if (!isSet($title)){
				$title = $name;
			}
	
			logIO('O', '(MW) Received '.strlen($bits).' bytes');
			if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
				return new IXR_Error(403, __('Invalid UN/PW Combination: UN = '.$username.' PW = '.$password));
			}
			do_action('xmlrpc_call', 'metaWeblog.newMediaObject');
	
			if ( !current_user_can('upload_files') ) {
				logIO('O', '(MW) User does not have upload_files capability');
				return new IXR_Error(401, __('You are not allowed to upload files to this site.'));
			}
	
			if ( $upload_err = apply_filters( 'pre_upload_error', false ) )
				return new IXR_Error(500, $upload_err);
	
			if ( !empty($data['overwrite']) && ($data['overwrite'] == true) ) {
				// Get postmeta info on the object.
				$old_file = $wpdb->get_row("
					SELECT ID
					FROM {$wpdb->posts}
					WHERE post_title = '{$name}'
						AND post_type = 'attachment'
				");
	
				// Delete previous file.
				wp_delete_attachment($old_file->ID);
	
				// Make sure the new name is different by pre-pending the
				// previous post id.
				$filename = preg_replace('/^wpid\d+-/', '', $name);
				$name = "wpid{$old_file->ID}-{$filename}";
			}
	
			$upload = wp_upload_bits($name, NULL, $bits);
			if ( ! empty($upload['error']) ) {
				$errorString = sprintf(__('Could not write file %1$s (%2$s)'), $name, $upload['error']);
				logIO('O', '(MW) ' . $errorString);
				return new IXR_Error(500, $errorString);
			}
			// Construct the attachment array
			// attach to post_id 0
			$post_id = 0;
			$attachment = array(
				'post_title' => $title,
				'post_content' => '',
				'post_type' => 'attachment',
				'post_parent' => $post_id,
				'post_mime_type' => $type,
				'post_excerpt' => $caption,
				'post_content' => $description,
				'guid' => $upload[ 'url' ]
			);
	
			// Save the data
			$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $post_id );
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
			//adds alt text as meta
			add_post_meta($id,"_wp_attachment_image_alt",$alttext, false);
			return apply_filters( 'wp_handle_upload', array( 'file' => $name, 'url' => $upload[ 'url' ], 'type' => $type ), 'upload' );
		}
	
public function check_content_exists($skywordId){
		$query = array(
		        'ignore_sticky_posts' => true,
				'meta_key' => 'skywordid',
				'meta_value' => $skywordId,
				'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
		);
		query_posts($query);
		if (have_posts()) :
			while (have_posts()) : the_post();
				$str = get_the_ID() ;
				return $str;
			endwhile;
		else :
			$query = array(
			        'ignore_sticky_posts' => true,
					'meta_key' => 'skyword_content_id',
					'meta_value' => $skywordId,
					'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
			);
			query_posts($query);
			if (have_posts()) :
				while (have_posts()) : the_post();
					$str = get_the_ID() ;
					return $str;
				endwhile;
				return null;
			else :
				return null;
			endif;
		endif;
	}
	
	private function check_category_exists($category_name){
		$cat_values = array('cat_name' => trim($category_name) );
		if (!category_exists($category_name)){
			wp_insert_category( $cat_values);
		}
	}
	private function check_username_exists($user_name, $display_name, $first_name, $last_name, $email, $bio){
		$user_id = username_exists($user_name);
		
		if (!$user_id) {
			$olduser_name = str_replace("sw-","skywriter-",$user_name);
			$user_id = username_exists($olduser_name);
			if (!$user_id) {
				
				//Generate a random password
				$random_password = wp_generate_password(20, false);
				//Create the account
				$user_id = wp_insert_user( array (
					'first_name' => $first_name,
					'last_name' => $last_name,
					'user_nicename' => $user_name,
					'display_name' => $display_name,
					'user_email' => $email,
					'role' => "author",
					'user_login' => $user_name,
					'user_pass' => $random_password,
					'description' => $bio
				)) ;
			}
		}
		return $user_id;
	}
	private function attach_attachments($post_id, $data){
		global $wpdb;
		$attachments = $wpdb->get_results( "SELECT ID, guid FROM {$wpdb->posts} WHERE post_parent = '0' AND post_type = 'attachment'" );
		if ( is_array( $attachments ) ) {
			foreach ( $attachments as $file ) {
				if (is_array($data['attachments'])){
					foreach ($data['attachments'] as $attachmentExt){
						if ( $attachmentExt == $file->guid){
							$wpdb->update($wpdb->posts, array('post_parent' => $post_id), array('ID' => $file->ID) );
						}
						if ( strpos( $attachmentExt, $file->guid."featured" ) !== false ){
							delete_post_meta($post_id, '_thumbnail_id');
							add_post_meta($post_id, '_thumbnail_id', $file->ID, false);
							
						}
					}
				}
			}
		}
	}
	private function create_custom_fields($post_id, $data){
		$custom_fields = explode(":", $data['custom_fields']);
		foreach ($custom_fields as $custom_field){
			$fields = explode("-", $custom_field);
			delete_post_meta($post_id, $fields[0]);
			add_post_meta($post_id, $fields[0],str_replace("%3A",":",str_replace("%2d","-",$fields[1])), false);
		}
	}
	private function update_custom_field($post_id, $key, $data){
		delete_post_meta($post_id, $key);
		add_post_meta($post_id,$key,$data, false);
	}
}

global $skyword_publish;
$skyword_publish = new SkywordPublish();