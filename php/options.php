<?php

add_action('admin_menu', 'skyword_plugin_menu');

function skyword_plugin_menu()
{
	add_options_page('Skyword Plugin', 'Skyword Plugin', 'manage_options', __FILE__, 'skyword_plugin_options');
	add_action('admin_init', 'skyword_register_settings');
}

function skyword_plugin_options()
{
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>Skyword Plugin</h2>
		<p>The Skyword Plugin allows content created with the Skyword platform to be published in Wordpress.</p><p>To learn more about how Skyword help you reach and engage your audience, please contact us at <a href="mailto:learnmore@skyword.com">learnmore@skyword.com</a> or visit <a target="_blank" href="www.skyword.com">www.skyword.com</a>.</p><p>Please contact Skyword Support (<a href="mailto:support@skyword.com">support@skyword.com</a>) if you have any questions.</p>

		<form action="options.php" method="post">
		<?php settings_fields('skyword_plugin_options'); ?>
		<?php do_settings_sections(__FILE__); ?>
		<p class="submit">
			<input name="Submit" type="submit" class="button-primary"
            value="<?php esc_attr_e('Save Configuration'); ?>" />
		</p>
		</form>
	</div>
<?php
}

function skyword_register_settings()
{
	register_setting('skyword_plugin_options', 'skyword_plugin_options', 'skyword_plugin_options_validate');
	add_settings_section('skyword_publish_section', '', 'skyword_plugin_section_text', __FILE__);
	add_settings_field('skyword_api_key', 'Skyword API Key:', 'skyword_api_key_input', __FILE__, 'skyword_publish_section');

	add_settings_field('skyword_enable_ogtags', '', 'skyword_enable_ogtags_input', __FILE__, 'skyword_publish_section');
	add_settings_field('skyword_enable_metatags', '', 'skyword_enable_metatags_input', __FILE__, 'skyword_publish_section');
	add_settings_field('skyword_enable_googlenewstag', '', 'skyword_enable_googlenewstag_input', __FILE__, 'skyword_publish_section');
	add_settings_field('skyword_enable_pagetitle', ' ', 'skyword_enable_pagetitle_input', __FILE__, 'skyword_publish_section');
	add_settings_field('skyword_enable_sitemaps', ' ', 'skyword_enable_sitemaps_input', __FILE__, 'skyword_publish_section');


}

function skyword_api_key_input()
{
	$options = get_option('skyword_plugin_options');
	echo "<input id='skyword_api_key' name='skyword_plugin_options[skyword_api_key]' size='60' type='text' value='{$options['skyword_api_key']}' /><p>The Skyword API Key is used to verify your program and allow the Skyword platform to publish to this site. </p>";
}


function skyword_enable_ogtags_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_ogtags]" value="1" ' . checked(1, $options['skyword_enable_ogtags'], false) . '/> Include the Facebook OpenGraph tags on the article node. <p>The OpenGraph tags are used to properly send information to Facebook when a page is recommended, liked, or shared by Facebook users.</p>';
}

function skyword_enable_metatags_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_metatags]" value="1" ' . checked(1, $options['skyword_enable_metatags'], false) . '/> Include the meta description tag on the article node. <p>The meta description tag provides additional information for search engines to properly index the web page. </p>';
}

function skyword_enable_googlenewstag_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_googlenewstag]" value="1" ' . checked(1, $options['skyword_enable_googlenewstag'], false) . '/> Include the Google News Keyword tag on the article post. <p>The Google News Keyword tag provides additional information for Google to properly index the web page for news searches.</p>';
}

function skyword_enable_pagetitle_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_pagetitle]" value="1" ' . checked(1, $options['skyword_enable_pagetitle'], false) . '/> Update the page title tag with an SEO Optimized title.<p>The SEO Optimized title provided by Skyword will replace the title of the article.</p>';
}

function skyword_enable_sitemaps_input()
{
	$options = get_option('skyword_plugin_options');
	echo '<input type="checkbox" id="meta_tag" name="skyword_plugin_options[skyword_enable_sitemaps]" value="1" ' . checked(1, $options['skyword_enable_sitemaps'], false) . '/> Generate the Google Sitemaps.<p>The Google Sitemap and Google News Sitemap are used to tell Google about the pages on your site and allows Google to better index your content.</p>';
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

	return $options;
}
function skyword_plugin_section_text()
{
}
function skyword_plugin_meta_text()
{
}

?>
