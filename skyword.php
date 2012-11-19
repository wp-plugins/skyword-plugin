<?php
/*
Plugin Name: Skyword
Plugin URI: http://www.skyword.com
Description: Integration with the Skyword content publication platform.
Version: 1.0.7.4
Author: Skyword, Inc.
Author URI: http://www.skyword.com
License: GPL2
*/

/*  Copyright 2012  Skyword, Inc.     This program is free software; you can redistribute it and/or modify    it under the terms of the GNU General Public License, version 2, as    published by the Free Software Foundation.     This program is distributed in the hope that it will be useful,    but WITHOUT ANY WARRANTY; without even the implied warranty of    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    GNU General Public License for more details.     You should have received a copy of the GNU General Public License    along with this program; if not, write to the Free Software    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA */ 

//Admin option page. Currently just a placeholder if necessary
$versionNumber = "1.0.7.4";

function skyword_admin(){
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	include("skyword_admin.php");
}

add_action('admin_menu', 'skyword_admin_actions');

function skyword_admin_actions() {
	add_options_page('Skyword Integration', 'Skyword Integration', 'manage_options', 'skyword-integration', 'skyword_admin');
}


// Plugin with update info
$packages['skyword'] = array(
	'versions' => array(
		'1.0.6' => array(
			'version' => "1.0.7.4",
			'date' => '2012-11-19',
			'author' => 'Stephen da Conceicao',
			'requires' => '3.0',  // WP version required for plugin
			'tested' => '3.0.1',  // WP version tested with
			'homepage' => 'http://www.skyword.com',  // Your personal website
			'external' => 'http://www.skyword.com',  // Site devoted to your plugin if available
			'package' => 'http://www.skyword.com/plugins/wordpress/skyword.zip',  // The zip file of the plugin update
			'sections' => array(
				'description' => 'Allows integration with the skyword publishing platform',
				'installation' => 'Enable XML_RPC',
				'change log' => 'Change log',
				'faq' => 'FAQ',
				'other notes' => 'Other Notes'
				)
		)
	),
	'info' => array(
		'url' => 'http://www.skyword.com'  // Site devoted to your plugin if available
	)
);


//Creates xmlrpc method listener
register_activation_hook(__FILE__, 'activate_rebuild_sitemap');
add_filter('xmlrpc_methods', 'skyword_xmlrpc_methods');
add_action('publish_post', 'write_sitemaps');
add_action('save_post', 'write_sitemaps');
add_action('delete_post', 'write_sitemaps');
add_action('transition_post_status', 'write_sitemaps',10, 3); 
add_action('rebuild_sitemap', 'write_sitemaps');
add_action('update_option_googlenewssitemap_excludeCat', 'write_sitemaps', 10, 2);
add_action('init', 'auto_robotstxt');
@add_shortcode('cf','customfields_shortcode');

function activate_rebuild_sitemap() {
	wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'rebuild_sitemap');
}

function skyword_xmlrpc_methods($methods){
	$methods['skyword_post'] = 'skyword_post';
	$methods['skyword_newMediaObject'] = 'skyword_newMediaObject';
	$methods['skyword_author'] = 'skyword_author';
	$methods['skyword_version'] = 'skyword_version';
	$methods['skyword_version_number'] = 'skyword_version_number';
	$methods['skyword_getAuthors'] = 'skyword_getAuthors';
	return $methods;
}
function skyword_version($args){
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
	return strval("Wordpress Version: ".get_bloginfo('version')." Plugin Version: 1.0.7.4");
}
function skyword_version_number($args){
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
	return strval("1.074");
}
function skyword_author($args){
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
	$user_id = check_username_exists($data['user-name'], $data['display-name'], $data['first-name'],$data['last-name'], $data['email'],$data['bio']);	
	return strval($user_id);
}

function skyword_getAuthors($args){

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
function skyword_post($args){
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
			$categoryName = htmlspecialchars(trim($category));
			check_category_exists($categoryName);
			if (get_cat_ID($categoryName)==0){
				$post_category[] = 1;
			} else {
				$post_category[] = get_cat_ID($categoryName);
			}
	}
	//check if content exists already
	$data['post-id'] = check_content_exists($data['skyword-id']);
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
	attach_attachments($post_id, $data);
	//add content template/attachment information as meta 
	create_custom_fields($post_id, $data);
	delete_post_meta($post_id, 'tracking');
	add_post_meta($post_id,'tracking',$data['tracking'], false);
	//Create sitemap information
	if ('news' == $data['publication-type']){
		delete_post_meta($post_id,'publication-type');
		add_post_meta($post_id, 'publication-type','news', false);
		if (null != $data['publication-access']){
			delete_post_meta($post_id,'publication-access');
			add_post_meta($post_id, 'publication-access',$data['publication-access'], false);
		}
		if (null != $data['publication-name']){
			delete_post_meta($post_id,'publication-name');
			add_post_meta($post_id, 'publication-name',$data['publication-name'], false);
		}
		if (null != $data['publication-geolocation']){
			delete_post_meta($post_id,'publication-geolocation');
			add_post_meta($post_id, 'publication-geolocation',$data['publication-geolocation'], false);
		}
		if (null != $data['publication-keywords']){
			delete_post_meta($post_id,'publication-keywords');
			add_post_meta($post_id, 'publication-keywords',$data['publication-keywords'], false);
		}
		if (null != $data['publication-stocktickers']){
			delete_post_meta($post_id,'publication-stocktickers');
			add_post_meta($post_id, 'publication-stocktickers',$data['publication-stocktickers'], false);
		}
		write_google_news_sitemap();
	} else {
		delete_post_meta($post_id,'publication-type');
		add_post_meta($post_id, 'publication-type','evergreen', false);
	}
	
	return strval($post_id);

}

function skyword_newMediaObject($args) {
		global $wpdb;
		global $wp_xmlrpc_server;
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
			'post_title' => $name,
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

function check_content_exists($skywordId){
	$query = array(
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
		return null;
	endif;
}

function check_category_exists($category_name){
	$cat_values = array('cat_name' => trim($category_name) );
  	if (!category_exists($category_name)){
  		wp_insert_category( $cat_values);
  	}
}
function check_username_exists($user_name, $display_name, $first_name, $last_name, $email, $bio){
	$user_id = username_exists($user_name);
	
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
			'role' => 1,
			'user_login' => $user_name,
			'user_pass' => $random_password,
			'description' => $bio
		)) ;

	}
	return $user_id;
}
function attach_attachments($post_id, $data){
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
function create_custom_fields($post_id, $data){
	$custom_fields = explode(":", $data['custom_fields']);
	foreach ($custom_fields as $custom_field){
		$fields = explode("-", $custom_field);
		delete_post_meta($post_id, $fields[0]);
		add_post_meta($post_id, $fields[0],$fields[1], false);
	}
}

function customfields_shortcode($atts, $text) {
	global $post;
	return get_post_meta($post->ID, $text, true);
}
function auto_robotstxt(){
	//check all requests for if they are for autogenerated robots.txt
	$request = str_replace( get_bloginfo('url'), '', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] );

	if ( (get_bloginfo('url').'/robots.txt' != 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) && ('/robots.txt' != $_SERVER['REQUEST_URI']) && ('robots.txt' != $_SERVER['REQUEST_URI']) )
		return;		// checking whether they're requesting robots.txt

	$remove = array("http://", "www.");
	$siteUrl = get_site_url();
	$sitename = str_replace($remove, "", $siteUrl);
	$sitenameA = explode(".",$sitename);
	$smallName = $sitenameA[0];
	// Generate evergreen/news sitemaps generated by plugin
	header('Content-type: text/plain');
	print "User-agent: * \n";
	print "Disallow: /wp-admin/ \n";
	print "Disallow: /wp-includes/ \n \n";
	print "Sitemap: ".get_site_url()."/".$smallName."-skyword-sitemap.xml \n";
	print "Sitemap: ".get_site_url()."/".$smallName."-skyword-google-news-sitemap.xml \n";
	die;
}

function write_sitemaps(){
	write_google_news_sitemap();
 	write_evergreen_sitemap();
}
function write_google_news_sitemap(){

	global $wpdb;
	// Fetch options from database
	$permalink_structure = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name='permalink_structure'");
	$siteurl = $wpdb->get_var("SELECT option_value FROM $wpdb->options	WHERE option_name='siteurl'");
	// Begin urlset
	$xmlOutput.= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\">\n";

	$includeMe = 'AND post_type="post"';
	//Exclude categories
	if (get_option('googlenewssitemap_excludeCat')<>NULL)	{
		$exPosts = get_objects_in_term(get_option('googlenewssitemap_excludeCat'),"category");
		$includeMe.= ' AND ID NOT IN ('.implode(",",$exPosts).')';
	}

	//Limit to last 2 days, 1000 items as google requires
	$rows = $wpdb->get_results("SELECT ID, post_date_gmt, post_title
	FROM $wpdb->posts, $wpdb->postmeta
	WHERE post_status='publish' and ID = post_id and meta_key = 'publication-type' and meta_value='news'		 
	AND (DATEDIFF(CURDATE(), post_date_gmt)<=2) $includeMe
	ORDER BY post_date_gmt DESC
	LIMIT 0, 1000");

	// Output sitemap data
	foreach($rows as $row){
		$xmlOutput.= "\t<url>\n";
		$xmlOutput.= "\t\t<loc>";
		$xmlOutput.= get_permalink($row->ID);
		$xmlOutput.= "</loc>\n";
		$xmlOutput.= "\t\t<news:news>\n";
	
		$xmlOutput.= "\t\t\t<news:publication>\n";
		$xmlOutput.= "\t\t\t\t<news:name>";
		if (null!= get_metadata("post",$row->ID,"publication-access",true)){
			$xmlOutput.= htmlspecialchars(get_metadata("post",$row->ID,"publication-name",true));
		} else {
			$xmlOutput.= htmlspecialchars(get_option('blogname'));
		}
		$xmlOutput.= "</news:name>\n";
		$xmlOutput.= "\t\t\t\t<news:language>";
		$xmlOutput.= substr(get_bloginfo('language'), 0, 2);
		$xmlOutput.= "</news:language>\n";
		$xmlOutput.= "\t\t\t</news:publication>\n";
		if (null!= get_metadata("post",$row->ID,"publication-access",true)){
			$xmlOutput.= "\t\t\t<news:access>";
			$xmlOutput.= get_metadata("post",$row->ID,"publication-access",true);
			$xmlOutput.= "</news:access>\n";
		}
		if (null!= get_metadata("post",$row->ID,"publication-geolocation",true)){
			$xmlOutput.= "\t\t\t<news:geo_locations>";
			$xmlOutput.= get_metadata("post",$row->ID,"publication-geolocation",true);
			$xmlOutput.= "</news:geo_locations>\n";
		}
		if (null!= get_metadata("post",$row->ID,"publication-stocktickers",true)){
			$xmlOutput.= "\t\t\t<news:stock_tickers>";
			$xmlOutput.= get_metadata("post",$row->ID,"publication-stocktickers",true);
			$xmlOutput.= "</news:stock_tickers>\n";
		}
		$xmlOutput.= "\t\t\t<news:publication_date>";
		$thedate = substr($row->post_date_gmt, 0, 10);
		$xmlOutput.= $thedate;
		$xmlOutput.= "</news:publication_date>\n";
		$xmlOutput.= "\t\t\t<news:title>";
		$xmlOutput.= htmlspecialchars($row->post_title);
		$xmlOutput.= "</news:title>\n";
		if (null!= get_metadata("post",$row->ID,"publication-keywords",true)){
			$xmlOutput.= "\t\t\t<news:keywords>";
			$xmlOutput.= get_metadata("post",$row->ID,"publication-keywords",true);
			$xmlOutput.= "</news:keywords>\n";
		}
		$xmlOutput.= "\t\t</news:news>\n";
		$xmlOutput.= "\t</url>\n";
		

	}

	// End urlset
	$xmlOutput.= "</urlset>\n";
	$remove = array("http://", "www.");
	$siteUrl = get_site_url();
	$sitename = str_replace($remove, "", $siteUrl);
	$sitenameA = explode(".",$sitename);
	$smallName = $sitenameA[0];
	$xmlFile = ABSPATH."/".$smallName."-skyword-google-news-sitemap.xml";
	$fp = fopen($xmlFile, "w+"); // open the cache file "skyword-google-news-sitemap.xml" for writing
	fwrite($fp, $xmlOutput); // save the contents of output buffer to the file
	fclose($fp); // close the file

}

function write_evergreen_sitemap(){

	global $wpdb;
	// Fetch options from database
	$permalink_structure = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name='permalink_structure'");
	$siteurl = $wpdb->get_var("SELECT option_value FROM $wpdb->options	WHERE option_name='siteurl'");
	// Begin urlset

	$xmlOutput.= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

	$includeMe = 'AND post_type="post"';
	$rows = $wpdb->get_results("SELECT ID, post_date_gmt, post_title
	FROM $wpdb->posts, $wpdb->postmeta
	WHERE post_status='publish' and ID = post_id and meta_key = 'publication-type' and meta_value='evergreen'		 
	ORDER BY post_date_gmt DESC
	LIMIT 0, 1000");

	// Output sitemap data
	foreach($rows as $row){
		$xmlOutput.= "\t<url>\n";
		$xmlOutput.= "\t\t<loc>";
		$xmlOutput.= get_permalink($row->ID);
		$xmlOutput.= "</loc>\n";
		
		$xmlOutput.= "\t\t<priority>";
		$xmlOutput.= "0.9";
		$xmlOutput.= "</priority>\n";
		$xmlOutput.= "\t\t<changefreq>";
		$xmlOutput.= "yearly";
		$xmlOutput.= "</changefreq>\n";
		$xmlOutput.= "\t</url>\n";
		

	}

	// End urlset
	$xmlOutput.= "</urlset>\n";
	$remove = array("http://", "www.");
	$siteUrl = get_site_url();
	$sitename = str_replace($remove, "", $siteUrl);
	$sitenameA = explode(".",$sitename);
	$smallName = $sitenameA[0];
	$xmlFile = ABSPATH."/".$smallName."-skyword-sitemap.xml";
	$fp = fopen($xmlFile, "w+"); // open the cache file "skyword-sitemap.xml" for writing
	fwrite($fp, $xmlOutput); // save the contents of output buffer to the file
	fclose($fp); // close the file

}

	
?>