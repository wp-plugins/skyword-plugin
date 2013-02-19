<?php 
/*
Plugin Name: Skyword
Plugin URI: http://www.skyword.com
Description: Integration with the Skyword content publication platform.
Version: 2.0
Author: Skyword, Inc.
Author URI: http://www.skyword.com
License: GPL2
*/

/*  Copyright 2013  Skyword, Inc.     This program is free software; you can redistribute it and/or modify    it under the terms of the GNU General Public License, version 2, as    published by the Free Software Foundation.     This program is distributed in the hope that it will be useful,    but WITHOUT ANY WARRANTY; without even the implied warranty of    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    GNU General Public License for more details.     You should have received a copy of the GNU General Public License    along with this program; if not, write to the Free Software    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA */ 

if ( !defined('SKYWORD_PATH') )
	define( 'SKYWORD_PATH', plugin_dir_path( __FILE__ ) );
if ( !defined('SKYWORD_VERSION') )
	define( 'SKYWORD_VERSION', "2.0" );
if ( !defined('SKYWORD_VN') )
	define( 'SKYWORD_VN', "2.0" );

require SKYWORD_PATH.'php/publishing.php';
require SKYWORD_PATH.'php/sitemap.php';
require SKYWORD_PATH.'php/shortcode.php';
require SKYWORD_PATH.'php/opengraph.php';