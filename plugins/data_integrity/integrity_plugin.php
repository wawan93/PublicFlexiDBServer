<?php
/*
Plugin Name: DFX Data Integrity Plugin
Plugin URL: http://flexilogin.com
Description: Plugin for checking data inegrity of DFX server database.
Version: 0.0.1
Author: Flexiweb
Author URL: http://flexiweb.com
License: GPLv2 or later
*/

define('INTEGRITY_PLUGIN_DIR', dirname(__FILE__));

require_once INTEGRITY_PLUGIN_DIR.'/integrity_class.php';

function integrity_add_menu_item($menu)
{
	$menu -> add('integrity', 'Data Integrity', 'Data Integrity Plugin', URL.'integrity', 1000);
	return $menu;
}

add_action('fx_add_server_menu_items', 'integrity_add_menu_item', 10, 1);

function integrity_include_page($error)
{
	if (PAGE == 'integrity') {
		require INTEGRITY_PLUGIN_DIR.'/integrity_summary.php';
	}
	else {
		return $error;
	}
}

add_action('fx_show_custom_content_page', 'integrity_include_page', 100, 1);