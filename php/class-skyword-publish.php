<?php


/**
 * Code for post creation
 */
class Skyword_Publish
{

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_filter( 'xmlrpc_methods', array( $this, 'skyword_xmlrpc_methods' ) );
	}
	/**
	* Extend XMLRPC calls
	*/
	public function skyword_xmlrpc_methods( $methods ) {
		$methods['skyword_post'] = array( $this, 'skyword_post');
		$methods['skyword_newMediaObject'] = array( $this, 'skyword_newMediaObject' );
		$methods['skyword_author'] = array( $this, 'skyword_author' );
		$methods['skyword_version'] =  array( $this,  'skyword_version' );
		$methods['skyword_version_number'] = array( $this, 'skyword_version_number' );
		$methods['skyword_getAuthors'] = array( $this, 'skyword_get_authors' );
		$methods['skyword_getCategories'] = array( $this, 'skyword_get_categories' );
		$methods['skyword_getTags'] = array( $this, 'skyword_get_tags' );
		$methods['skyword_getPost'] = array( $this, 'skyword_get_post' );
		$methods['skyword_deletePost'] = array( $this, 'skyword_delete_post' );
		$methods['skyword_getTaxonomies'] = array( $this, 'skyword_get_taxonomies' );
		return $methods;
	}
	/**
	* Returns current version of plugin to write.skyword.com.  
	*/
	public function skyword_version( $args ) {
		$login = $this->login( $args );
		if ( 'success' == $login['status'] ) {
			return strval( 'Wordpress Version: '.get_bloginfo('version').' Plugin Version: '.SKYWORD_VERSION );
		} else {
			return $login['message'];
		}
	}
	/**
	* Returns version number of plugin
	*/
	public function skyword_version_number( $args ) {
		$login = $this->login( $args );
		if ( 'success' == $login['status'] ) {
			return strval( SKYWORD_VN );
		} else {
			return $login;
		}
	}
	/**
	* Gets author id if they exist, otherwise creates guest author with co-author-plus plugin
	*/
	public function skyword_author( $args ) {
		$login = $this->login( $args );
		if ( 'success' == $login['status'] ) {
			$data = $args[3];
			$user_id = $this->check_username_exists( $data['user-name'], $data['display-name'], $data['first-name'], $data['last-name'], $data['email'], $data['bio'] );
			return strval( $user_id );
		} else {
			return $login['message'];
		}
	}
	/**
	* Returns list of authors associated with site for ghost writing
	*/
	public function skyword_get_authors( $args ) {
		$login = $this->login( $args );
		if ( 'success' == $login['status'] ) {
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
		} else {
			return $login['message'];
		}
	}
	/** 
	* Returns list of categories for write.skyword.com publishing
	*/
	public function skyword_get_categories( $args = '' ) {
		$login = $this->login( $args );
		if ( 'success' == $login['status'] ) {

			do_action('xmlrpc_call', 'metaWeblog.getCategories');

			$categories_struct = array();

			if ( $cats = get_categories(array('get' => 'all')) ) {
				foreach ( $cats as $cat ) {
					$struct['categoryId'] = $cat->term_id;
					$struct['parentId'] = $cat->parent;
					$struct['categoryName'] = $cat->name;
					$categories_struct[] = $struct;
				}
			}

			return $categories_struct;
		} else {
			return $login['message'];
		}
	}
	/**
	* Returns list of tags for write.skyword.com publishing
	*/
	public function skyword_get_tags( $args = '' ) {
		$login = $this->login($args);
		if ( 'success' == $login['status'] ) {
			do_action( 'xmlrpc_call', 'wp.getKeywords' );

			$tags = array();

			if ( $all_tags = get_tags() ) {
				foreach ( (array) $all_tags as $tag ) {
					$struct['tag_id']   = $tag->term_id;
					$struct['name']    = $tag->name;
					$struct['count']   = $tag->count;
					$struct['slug']    = $tag->slug;
					$tags[] = $struct;
				}
			}

			return $tags;
		} else {
			return $login['message'];
		}
	}
	public function skyword_get_taxonomies( $args = '' ) {
		$login = $this->login($args);
		if ( 'success' == $login['status'] ) {
			$taxonomiesStruct = array();
			$taxonomies = get_taxonomies(null, "objects"); 

			if ( $taxonomies ) {
				foreach ( $taxonomies as $taxonomy ) {
					$struct['name']    = $taxonomy->name;
					$hierarchical = $taxonomy->hierarchical;
					$terms = get_terms( $struct['name'], array(
 						'hide_empty' => 0 ) 
					);
					foreach ( $terms as $term ) {
						$termStruct['name'] = $term->name;
						if ($hierarchical){
							$termStruct['id'] = $term->term_id;
						} else {
							$termStruct['id'] = $term->name;
						}
						$termsArr[] = $termStruct;
 					}
 					if ($terms){
	 					$struct['terms'] = $termsArr;
	 					$struct['termString'] = $termString;
						$taxonomiesStruct[] = $struct;
					}
					unset($termsArr);
					$termsArr = array();
				}
			}

			return $taxonomiesStruct;
		} else {
			return $login['message'];
		}
	}

	
	/**
	* Returns permalink for post to write.skyword.com
	*/
	public function skyword_get_post( $args = '' )	{
		$login = $this->login( $args );
		if ( 'success' == $login['status'] ) {
			$post_id = (int) $args[3];
			$response['link']= get_permalink( $post_id );
			return $response;
		} else {
			return $login['message'];
		}
	}
	/**
	* Deletes post by id 
	*/
	public function skyword_delete_post( $args = '' ) {
		
		$login = $this->login($args);
		if ( 'success' == $login['status'] ) {
			do_action( 'xmlrpc_call', 'wp.deletePost' );
			$post_id = $args[3];
			$post = get_post( $post_id, ARRAY_A );
			if ( empty( $post['ID'] ) )
				return new IXR_Error( 404, __( 'Invalid post ID.' ) );
	
	
			$result = wp_delete_post( $post_id );
	
			if ( ! $result )
				return new IXR_Error( 500, __( 'The post cannot be deleted.' ) );
	
			return true;
		} else {
			return $login['message'];
		}		
		
	}
	/**
	* Creates posts from write.skyword.com
	*/
	public function skyword_post( $args ) {
		global $coauthors_plus;
		$login = $this->login( $args );
		if ( 'success' == $login['status'] ) {
			$data = $args[3];
			if ( null != $data['publication-date'] ) {
				$dateCreated = $data['publication-date']->getIso();
				$post_date = get_date_from_gmt( iso8601_to_datetime( $dateCreated ) );
			} else {
				$post_date = current_time('mysql');
			}
			if ( null != $data['publication-state'] ) {
				$state = $data['publication-state'];
			} else {
				$state = "draft";
			}

			$categories = $data['categories'];
			$post_category = array();
			foreach ( $categories as $category ) {
				$categoryId = (int) $category['id'];
				if ( $categoryId != null && $categoryId != 0 ){
					$post_category[] = $category['id'];
				}
				
				
			}
			$data['post-id'] = $this->check_content_exists( $data['skyword_content_id'] , $data['post-type'] );
		    $new_post = array(
				'post_status' => $state,
				'post_date' =>  $post_date,
				'post_excerpt' => $data['excerpt'],
				'post_type' => $data['post-type'],
				'comment_status' => 'open',
				'post_category' => $post_category
			);

			if (null != $data['title']) {
				$new_post['post_title'] = $data['title'];
			}
			if (null != $data['description']) {
				$new_post['post_content'] = addslashes( $data['description'] );
			}
			if (null != $data['slug']) {
				$new_post['post_name'] = $data['slug'];
			}
			if (null != $data['post-id']) {
				$new_post['ID'] = $data['post-id'];
			}
			if (null != $data['user-id'] &&  is_numeric( trim( $data['user-id'] ) ) ) {
				$new_post['post_author'] = $data['user-id'];
			}
			
			$post_id = wp_insert_post($new_post);
		
			$utf8string =  html_entity_decode( $data['tags-input'] );
			wp_set_post_tags( $post_id, $utf8string, false );

			//attach attachments to new post;
			$this->attach_attachments( $post_id, $data );
			//add content template/attachment information as meta
			$this->create_custom_fields( $post_id, $data );
			$this->update_custom_field( $post_id, 'skyword_tracking_tag', $data['tracking'] );
			$this->update_custom_field( $post_id, 'skyword_seo_title', $data['metatitle'] );
			$this->update_custom_field( $post_id, 'skyword_metadescription', $data['metadescription'] );
			$this->update_custom_field( $post_id, 'skyword_keyword', $data['metakeyword'] );
			$this->update_custom_field( $post_id, '_yoast_wpseo_title', $data['metatitle'] );
			$this->update_custom_field( $post_id, '_yoast_wpseo_metadesc', $data['metadescription'] );
			$this->update_custom_field( $post_id, '_yoast_wpseo_focuskw', $data['keyword'] );
			$this->update_custom_field( $post_id, 'skyword_content_id', $data['skyword_content_id'] );
			
			//add custom taxonomy values
			foreach ( $data["taxonomies"] as $taxonomy ) { 
			    wp_set_post_terms( $post_id, $taxonomy['values'], $taxonomy['name'], true );
			}
			
			if (null !=  $data[ 'gmwlocation_wppl_street' ]){
				global $wpdb;
				$wpdb->replace( $wpdb->prefix . 'places_locator', array(
					'post_id' => $post_id,
					'feature' => 0,
					'post_type' => $data['post-type'],
					'post_title' => $data['title'],
					'post_status' => $state,
					'street' => $data[ 'gmwlocation_wppl_street' ],
					'city' => $data[ 'gmwlocation_wppl_city' ],
					'state' => $data[ 'gmwlocation_wppl_state' ],
					'zipcode' => $data[ 'gmwlocation_wppl_zipcode' ],
					'lat' => $data[ 'gmwlocation_wppl_lat' ],
					'long' => $data[ 'gmwlocation_wppl_long' ]
				));
				$this->update_custom_field($post_id, '_wppl_street', $data[ 'gmwlocation_wppl_street'] );
				$this->update_custom_field($post_id, '_wppl_city', $data[ 'gmwlocation_wppl_city' ] );
				$this->update_custom_field($post_id, '_wppl_state', $data[ 'gmwlocation_wppl_state' ] );
				$this->update_custom_field($post_id, '_wppl_zipcode', $data[ 'gmwlocation_wppl_zipcode' ] );
				$this->update_custom_field($post_id, '_wppl_lat', $data[ 'gmwlocation_wppl_lat' ] );
				$this->update_custom_field($post_id, '_wppl_long', $data[ 'gmwlocation_wppl_long' ] );
				$this->update_custom_field($post_id, '_wppl_phone', $data[ 'gmwlocation_wppl_phone' ] );
				
			}
			//Create sitemap information
			if ( 'news' == $data['publication-type'] ) {
				$this->update_custom_field($post_id, 'skyword_publication_type', 'news');
				if ( null != $data['publication-access'] ) {
					$this->update_custom_field($post_id, 'skyword_publication_access', $data['publication-access']);
				}
				if ( null != $data['publication-name'] ) {
					$this->update_custom_field($post_id, 'skyword_publication_name', $data['publication-name']);
				}
				if ( null != $data['publication-geolocation'] ) {
					$this->update_custom_field($post_id, 'skyword_geolocation', $data['publication-geolocation']);
				}
				if ( null != $data['publication-keywords'] ) {
					$this->update_custom_field($post_id, 'skyword_tags', $data['publication-keywords']);
				}
				if ( null != $data['publication-stocktickers'] ) {
					$this->update_custom_field($post_id, 'skyword_stocktickers', $data['publication-stocktickers']);
				}
			} else {
				$this->update_custom_field($post_id, 'skyword_publication_type', 'evergreen');
			}
			if ( null != $coauthors_plus) {
				if ( !is_numeric( $data['user-id'] ) )  {
					$data['user-id'] = str_replace( 'guest-', '', $data['user-id'] );
					$author = $coauthors_plus->guest_authors->get_guest_author_by( 'ID', $data['user-id'] );
					$author_term = $coauthors_plus->update_author_term( $author );
					wp_set_post_terms( $post_id, $author_term->slug, $coauthors_plus->coauthor_taxonomy, true );
				}
			}
			return strval($post_id);
		} else {
			return $login['message'];
		}
	}
	/**
	* Modified image upload based off of xmlrpc newMediaObject function.
	* Adds ability to include alt title, caption, and description to attachment
	*/
	public function skyword_newMediaObject( $args ) {
		$login = $this->login($args);
		if ( 'success' == $login['status'] ) {
			global $wpdb;

			$data = $args[3];
			$name = sanitize_file_name( $data['name'] );
			$type = $data['type'];
			$bits = $data['bits'];
			$title = $data['title'];
			$caption = $data['caption'];
			$alttext = $data['alttext'];
			$description = $data['description'];
			if (!isset($title)) {
				$title = $name;
			}

			logIO( 'O', '(MW) Received '.strlen($bits).' bytes' );

			do_action( 'xmlrpc_call', 'metaWeblog.newMediaObject' );


			if ( $upload_err = apply_filters( 'pre_upload_error', false ) )
				return new IXR_Error(500, $upload_err);

			
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
			add_post_meta( $id, "_wp_attachment_image_alt", $alttext, false) ;
			return apply_filters( 'wp_handle_upload', array( 'file' => $name, 'url' => $upload[ 'url' ], 'type' => $type ), 'upload' );
		} else {
			return $login['message'];
		}

	}
	/**
	* Checks if post exists identified by skyword content id, used to avoid duplicates if publishing error occcurs
	*/
	private function check_content_exists( $skywordId, $postType ) {
		$query = array(
		    'ignore_sticky_posts' => true,
			'meta_key' => 'skywordid',
			'meta_value' => $skywordId,
		    'post_type' => $postType,
			'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
		);
		query_posts( $query );
		if ( have_posts() ) :
			while ( have_posts() ) : the_post();
				$str = get_the_ID() ;
			return $str;
			endwhile;
		else :
			$query = array(
			    'ignore_sticky_posts' => true,
				'meta_key' => 'skyword_content_id',
				'meta_value' => $skywordId,
			    'post_type' => $postType,
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
	/**
	* Uses nonce or un/pw to authenticate whether user is able to interact with plugin
	*/
	private function login( $args ) {
		$username = $args[1];
		$password = $args[2];
		global $wp_xmlrpc_server;
		//Authenticate that posting user is valid
		if ( 'skywordapikey' != $username ) {
			if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
				$response['message'] = new IXR_Error(403, __( 'Invalid UN/PW Combination: UN = '.$username.' PW = '.$password ));
				$response['status'] = 'error';
			} else if (!user_can($user->ID, 'edit_posts')) {
				$response['message'] = new IXR_Error(403, __( 'You do not have sufficient privileges to login.' ));
				$response['status'] = 'error';
			} else {
				$response['status'] = 'success';
			}

			return $response;
		} else {
			$values = explode('-', $args[2]);
			$hash = $values[0];
			$timestamp = $values[1];
			$response = $this->validate_secret($hash, $timestamp);
		}
		return $response;
	}
	/**
	* Validates that nonce is valid
	*/
	private function validate_secret( $hash, $timestamp ) {
		$temp_time = time();
		$options = get_option('skyword_plugin_options');
		$api_key = $options['skyword_api_key'];
		if ( $temp_time - $timestamp <= 20000 && $temp_time - $timestamp >= -20000 ) {
			if ($api_key != '') {
				$temp_hash = md5($api_key . $timestamp);
				if ($temp_hash == $hash) {
					$response['status'] = 'success';
				}
				else {
					$response['message'] =  new IXR_Error( 403, __('Could not match hash.') );
					$response['status'] = 'error';
				}
			} else {
				$response['message'] =  new IXR_Error( 403, __('Skyword API key not set.') );
				$response['status'] = 'error';
			}
		} else {
			$response['message'] = new IXR_Error( 403, __('Bad timestamp used. '.$hash.' Timestamp sent: ' . $timestamp) );
			$response['status'] = 'error';
		}
		return $response;
	}
	/**
	* Checks whether username exists. 
	* Creates Guest Author if not
	* Depends on Co Author Plus Plugin
	*/
	private function check_username_exists( $user_name, $display_name, $first_name, $last_name, $email, $bio ) {
	    global $coauthors_plus;
		$user_id = username_exists( $user_name );
		if (!$user_id) {
			$olduser_name = str_replace( 'sw-', 'skywriter-', $user_name );
			$user_id = username_exists($olduser_name);
			if (!$user_id) {
			  if ( null != $coauthors_plus) {
			    $guest_author = array();
			    $guest_author['ID'] = '';
			    $guest_author['display_name'] = $data['display-name'];
			    $guest_author['first_name'] = $data['first-name'];
			    $guest_author['last_name'] = $data['last-name'];
			    $guest_author['user_login'] = $data['user-name'];
			    $guest_author['user_email'] = $data['email'];
			    $guest_author['description'] = $data['bio'];
			    $guest_author['jabber'] = '';
			    $guest_author['yahooim'] = '';
			    $guest_author['aim'] = '';
			    $guest_author['website'] = $data['website'];
			    $guest_author['linked_account'] = '';
			    $guest_author['website'] = $data['website'];
			    $guest_author['company'] = $data['company'];
			    $guest_author['title'] = $data['title'];
			    $guest_author['google'] = $data['google'];
			    $guest_author['twitter'] = $data['twitter'];
			  
			    $retval = $coauthors_plus->guest_authors->create( $guest_author );
			    if( is_wp_error( $retval ) ) {
			      $author = $coauthors_plus->guest_authors->get_guest_author_by( 'user_login', $data['user-name'] );
			      if (null != $author){
			        $user_id = 'guest-'.$author->ID;
			      }
			    } else {
			      $user_id = 'guest-'.$retval;
			    }
			  
			  } else {
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
			
		}
		return $user_id;
	}
	/**
	* Attaches attachments provided to a specific post
	*/
	private function attach_attachments( $post_id, $data ) {
		global $wpdb;
		$args = array(
			'post_type' => 'attachment',
			'post_parent' => 0,
			'suppress_filters' => false
		);

		$attachments = get_posts( $args );

		if ( is_array( $attachments ) ) {
			foreach ( $attachments as $file ) {
				if ( is_array( $data['attachments'] ) ) {
					foreach ( $data['attachments'] as $attachmentExt ) {
						if ( $attachmentExt == $file->guid ) {
							$wpdb->update($wpdb->posts, array('post_parent' => $post_id), array('ID' => $file->ID) );
						}
						if ( false !== strpos( $attachmentExt, $file->guid.'featured' ) ) {
							delete_post_meta($post_id, '_thumbnail_id');
							add_post_meta($post_id, '_thumbnail_id', $file->ID, false);
							$wpdb->update($wpdb->posts, array('post_parent' => $post_id), array('ID' => $file->ID) );

						}
					}
				}
			}
		}
	}
	/** 
	* Updates all custom fields provided by write.skyword.com
	*/
	private function create_custom_fields( $post_id, $data ) {
		$custom_fields = explode( ':', $data['custom_fields']);
		foreach ( $custom_fields as $custom_field ) {
			$fields = explode( '-', $custom_field );
            delete_post_meta( $post_id, str_replace( '%2d', '-', $fields[0] ) );
			add_post_meta($post_id, str_replace('%2d', '-', $fields[0]), str_replace('%3A', ':', str_replace('%2d', '-', $fields[1])), false);
		}
	}
	/** 
	* Updates specified custom field
	*/
	private function update_custom_field( $post_id, $key, $data ) {
		delete_post_meta($post_id, $key);
		add_post_meta($post_id, $key, $data, false);
	}
}

global $skyword_publish;
$skyword_publish = new Skyword_Publish();