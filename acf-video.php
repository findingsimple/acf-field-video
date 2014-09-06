<?php
/*
Plugin Name: Advanced Custom Fields: Video Field
Plugin URI: http://plugins.findingsimple.com
Description: Adds a 'video' field type for the [Advanced Custom Fields](http://wordpress.org/extend/plugins/advanced-custom-fields/) WordPress plugin.
Version: 1.1
Author: Finding Simple
Author URI: http://findingsimple.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


class acf_field_video_plugin
{
	/*
	*  Construct
	*
	*/
	function __construct()
	{
		// set text domain
		/*
		$domain = 'acf-video';
		$mofile = trailingslashit(dirname(__File__)) . 'lang/' . $domain . '-' . get_locale() . '.mo';
		load_textdomain( $domain, $mofile );
		*/
		
		//Version 4
		add_action('acf/register_fields', array($this, 'register_fields'));	

		//Version 5
		add_action('acf/include_field_types', array($this, 'include_field_types'));	

	}
	
		
	/*
	*  register_fields
	*
	*/
	function register_fields()
	{
		include_once('video.php');
	}
	
	/*
	*  include_field_types
	*
	*/
	function include_field_types()
	{
		include_once('acf-video-v5.php');
	}
	
}

new acf_field_video_plugin();