<?php 
class SkywordSitemaps {
	
	/**
	 * Class constructor
	 */
	public function __construct() {
		register_activation_hook(__FILE__, 'activate_rebuild_sitemap');
		add_action('publish_post', array($this,'write_sitemaps'));
		add_action('save_post', array($this,'write_sitemaps'));
		add_action('delete_post', array($this,'write_sitemaps'));
		add_action('transition_post_status', array($this,'write_sitemaps')); 
		add_action('rebuild_sitemap', array($this,'write_sitemaps'));
		add_action('update_option_googlenewssitemap_excludeCat', array($this,'write_sitemaps'));
		add_action('init', array($this,'auto_robotstxt'));

	}
	public function activate_rebuild_sitemap() {
		wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'rebuild_sitemap');
	}
	
	
	public function auto_robotstxt(){
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
	
	public function write_sitemaps(){
		error_reporting(E_ERROR);
		$this->write_google_news_sitemap();
		$this->write_evergreen_sitemap();
	}
	private function write_google_news_sitemap(){
	
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
				WHERE post_status='publish' and ID = post_id and (meta_key = 'publication-type' or meta_key = 'skyword_publication_type')  and meta_value='news'
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
			if (null!= get_metadata("post",$row->ID,"publication-name",true)){
				$xmlOutput.= htmlspecialchars(get_metadata("post",$row->ID,"publication-name",true));
		} else {
			if (null!= get_metadata("post",$row->ID,"skyword_publication_name",true)){
				$xmlOutput.= htmlspecialchars(get_metadata("post",$row->ID,"skyword_publication_name",true));
			}else {
				$xmlOutput.= htmlspecialchars(get_option('blogname'));
			}
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
		} else {
			if (null!= get_metadata("post",$row->ID,"skyword_publication_access",true)){
				$xmlOutput.= "\t\t\t<news:access>";
				$xmlOutput.= get_metadata("post",$row->ID,"skyword_publication_access",true);
				$xmlOutput.= "</news:access>\n";
			}
		}
		if (null!= get_metadata("post",$row->ID,"publication-geolocation",true)){
			$xmlOutput.= "\t\t\t<news:geo_locations>";
			$xmlOutput.= get_metadata("post",$row->ID,"publication-geolocation",true);
			$xmlOutput.= "</news:geo_locations>\n";
		} else {
			if (null!= get_metadata("post",$row->ID,"skyword_publication_geolocation",true)){
				$xmlOutput.= "\t\t\t<news:geo_locations>";
				$xmlOutput.= get_metadata("post",$row->ID,"skyword_publication_geolocation",true);
				$xmlOutput.= "</news:geo_locations>\n";
			}
		}
		if (null!= get_metadata("post",$row->ID,"publication-stocktickers",true)){
			$xmlOutput.= "\t\t\t<news:stock_tickers>";
			$xmlOutput.= get_metadata("post",$row->ID,"publication-stocktickers",true);
			$xmlOutput.= "</news:stock_tickers>\n";
		} else {
			if (null!= get_metadata("post",$row->ID,"skyword_publication_stocktickers",true)){
				$xmlOutput.= "\t\t\t<news:stock_tickers>";
				$xmlOutput.= get_metadata("post",$row->ID,"skyword_publication_stocktickers",true);
				$xmlOutput.= "</news:stock_tickers>\n";
			}
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
		} else {
			if (null!= get_metadata("post",$row->ID,"skyword_publication_keywords",true)){
				$xmlOutput.= "\t\t\t<news:keywords>";
				$xmlOutput.= get_metadata("post",$row->ID,"skyword_publication_keywords",true);
				$xmlOutput.= "</news:keywords>\n";
			}
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
	
	private function write_evergreen_sitemap(){
	
		global $wpdb;
		// Fetch options from database
		$permalink_structure = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name='permalink_structure'");
		$siteurl = $wpdb->get_var("SELECT option_value FROM $wpdb->options	WHERE option_name='siteurl'");
		// Begin urlset
	
		$xmlOutput.= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
	
		$includeMe = 'AND post_type="post"';
		$rows = $wpdb->get_results("SELECT ID, post_date_gmt, post_title
				FROM $wpdb->posts, $wpdb->postmeta
				WHERE post_status='publish' and ID = post_id and  (meta_key = 'publication-type' or meta_key = 'skyword_publication_type')  and meta_value='evergreen'
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
}
global $skyword_sitemaps;
$skyword_sitemaps = new SkywordSitemaps;