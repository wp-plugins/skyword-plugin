<?php

add_action('admin_menu', 'skyword_plugin_menu');

function skyword_plugin_menu()
{
	add_options_page('Skyword', 'Skyword', 'manage_options', __FILE__, 'skyword_plugin_options');
	add_action('admin_init', 'skyword_register_settings');
}

function skyword_plugin_options()
{
?>
	<style type="text/css">
		h3{margin-bottom: -35px;}
		.skyword-settings form {margin-top:40px;}
		.skyword-settings .form-table {background:#fff; padding:20px;margin-bottom:-5px;}
		.skyword-settings h3{margin-left:20px;}
		.skyword-settings h2{background:url(http://www.skyword.com/wp-content/themes/skyword/images/s.png) no-repeat; height:30px;min-width:300px;}
		.skyword-settings h2 span{display:none;}  
		.form-table td p {font-size:12px; color: #999999;} 
	</style>
	<div class="wrap skyword-settings">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2><span>Skyword</span></h2>
		<p>The Skyword Plugin allows content created with the Skyword platform to be published in Wordpress.</p><p>To learn more about how Skyword helps you reach and engage your audience, please contact us at <a href="mailto:learnmore@skyword.com">learnmore@skyword.com</a> or visit <a target="_blank" href="www.skyword.com">www.skyword.com</a>.</p>
		<p>Please contact Skyword Support (<a href="mailto:support@skyword.com">support@skyword.com</a>) if you have any questions.</p>

		<form action="options.php" method="post">
		<?php settings_fields('skyword_plugin_options'); ?>
		<?php do_settings_sections(__FILE__); ?>
		<p class="submit">
			<input name="Submit" type="submit" class="button-primary"
            value="<?php esc_attr_e('Save Changes'); ?>" />
		</p>
		</form>
	</div>
<?php
}

function skyword_register_settings()
{
	register_setting('skyword_plugin_options', 'skyword_plugin_options', 'skyword_plugin_options_validate');
	add_settings_section('skyword_apikey_section', 'Skyword API Key', 'skyword_plugin_section_text', __FILE__);
	add_settings_field('skyword_api_key', '', 'skyword_api_key_input', __FILE__, 'skyword_apikey_section');
	add_settings_section('skyword_ogtags_section', 'Facebook OpenGraph', 'skyword_plugin_section_text', __FILE__);
	add_settings_field('skyword_enable_ogtags', '', 'skyword_enable_ogtags_input', __FILE__, 'skyword_ogtags_section');
	add_settings_section('skyword_metatags_section', 'Meta Description', 'skyword_plugin_section_text', __FILE__);
	add_settings_field('skyword_enable_metatags', '', 'skyword_enable_metatags_input', __FILE__, 'skyword_metatags_section');
	add_settings_section('skyword_googlenewstags_section', 'Google News Keywords', 'skyword_plugin_section_text', __FILE__);
	add_settings_field('skyword_enable_googlenewstag', '', 'skyword_enable_googlenewstag_input', __FILE__, 'skyword_googlenewstags_section');
	add_settings_section('skyword_titletag_section', 'SEO Page Title', 'skyword_plugin_section_text', __FILE__);
	add_settings_field('skyword_enable_pagetitle', ' ', 'skyword_enable_pagetitle_input', __FILE__, 'skyword_titletag_section');
	add_settings_section('skyword_sitemap_section', 'XML Sitemaps', 'skyword_plugin_section_text', __FILE__);
	add_settings_field('skyword_enable_sitemaps', ' ', 'skyword_enable_sitemaps_input', __FILE__, 'skyword_sitemap_section');
	add_settings_field('skyword_generate_all_sitemaps', ' ', 'skyword_generate_all_sitemaps_input', __FILE__, 'skyword_sitemap_section');
	add_settings_field('skyword_generate_news_sitemaps', ' ', 'skyword_generate_news_sitemaps_input', __FILE__, 'skyword_sitemap_section');
	add_settings_field('skyword_generate_pages_sitemaps', ' ', 'skyword_generate_pages_sitemaps_input', __FILE__, 'skyword_sitemap_section');
	add_settings_field('skyword_generate_categories_sitemaps', ' ', 'skyword_generate_categories_sitemaps_input', __FILE__, 'skyword_sitemap_section');
	add_settings_field('skyword_generate_tags_sitemaps', ' ', 'skyword_generate_tags_sitemaps_input', __FILE__, 'skyword_sitemap_section');


}

function skyword_api_key_input()
{
	$options = get_option('skyword_plugin_options');
	echo "<input id='skyword_api_key' name='skyword_plugin_options[skyword_api_key]' size='60' type='text' value='{$options['skyword_api_key']}' /><p>The Skyword API Key is used to verify your program and allow the Skyword platform to publish to this site. </p>";
}


function skyword_enable_ogtags_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_ogtags]" value="1" ' . checked(1, $options['skyword_enable_ogtags'], false) . '/> Include the Facebook OpenGraph tags on the post. <p>The OpenGraph tags are used to properly send information to Facebook when a page is recommended, liked, or shared by Facebook users.</p>';
}

function skyword_enable_metatags_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_metatags]" value="1" ' . checked(1, $options['skyword_enable_metatags'], false) . '/> Include the meta description tag on the post. <p>The meta description tag provides additional information for search engines to properly index the web page. </p>';
}

function skyword_enable_googlenewstag_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_googlenewstag]" value="1" ' . checked(1, $options['skyword_enable_googlenewstag'], false) . '/> Include the Google News Keyword tag on the article post. <p>The Google News Keyword tag provides additional information for Google to properly index the web page for news searches.</p>';
}

function skyword_enable_pagetitle_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_pagetitle]" value="1" ' . checked(1, $options['skyword_enable_pagetitle'], false) . '/> Include the search engine optimized page title.<p>The page title will use the search engine optimized title provided by Skyword.</p>';
}

function skyword_enable_sitemaps_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_sitemaps]" value="1" ' . checked(1, $options['skyword_enable_sitemaps'], false) . '/> Enable XML Sitemaps.<p>The XML Sitemaps are used to tell search engines about the pages on your site and 
allows the search engines to better index your content. Select the types of site maps 
below.</p>';
}
function skyword_generate_all_sitemaps_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_generate_all_sitemaps]" value="1" ' . checked(1, $options['skyword_generate_all_sitemaps'], false) . '/> Generate XML Sitemap for all content.<p>This site map includes all posts and pages. Requires the XML Sitemaps to be enabled.</p>';
}
function skyword_generate_news_sitemaps_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_generate_news_sitemaps]" value="1" ' . checked(1, $options['skyword_generate_news_sitemaps'], false) . '/> Generate Google News Sitemap.<p>The Google News site maps allow the Google search engine to discover and index news articles. Requires the XML Sitemaps to be enabled.</p>';
}

function skyword_generate_pages_sitemaps_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_generate_pages_sitemaps]" value="1" ' . checked(1, $options['skyword_generate_pages_sitemaps'], false) . '/> Generate Pages Sitemap.<p>This site map includes only the pages in your site. Requires the XML Sitemaps to be 
enabled.</p>';
}

function skyword_generate_categories_sitemaps_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_generate_categories_sitemaps]" value="1" ' . checked(1, $options['skyword_generate_categories_sitemaps'], false) . '/> Generate Categories Sitemap.<p>This site map generates the list of category pages. Requires the XML Sitemaps to be enabled.</p>';
}

function skyword_generate_tags_sitemaps_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_generate_tags_sitemaps]" value="1" ' . checked(1, $options['skyword_generate_tags_sitemaps'], false) . '/> Generate Tags Sitemap.<p>This site map generates the list of tags. Requires the XML Sitemaps to be enabled.</p>';
}


function skyword_plugin_options_validate($input)
{
	$options = get_option('skyword_plugin_options');
	$options['skyword_api_key'] = trim($input['skyword_api_key']);
	$options['skyword_enable_ogtags'] = trim($input['skyword_enable_ogtags']);
	$options['skyword_enable_metatags'] = trim($input['skyword_enable_metatags']);
	$options['skyword_enable_googlenewstag'] = trim($input['skyword_enable_googlenewstag']);
	$options['skyword_enable_pagetitle'] = trim($input['skyword_enable_pagetitle']);
	$options['skyword_enable_sitemaps'] = trim($input['skyword_enable_sitemaps']);
	$options['skyword_generate_all_sitemaps'] = trim($input['skyword_generate_all_sitemaps']);
	$options['skyword_generate_news_sitemaps'] = trim($input['skyword_generate_news_sitemaps']);
	$options['skyword_generate_pages_sitemaps'] = trim($input['skyword_generate_pages_sitemaps']);
	$options['skyword_generate_categories_sitemaps'] = trim($input['skyword_generate_categories_sitemaps']);
	$options['skyword_generate_tags_sitemaps'] = trim($input['skyword_generate_tags_sitemaps']);

	return $options;
}
function skyword_plugin_section_text()
{
}
function skyword_plugin_meta_text()
{
}

?>