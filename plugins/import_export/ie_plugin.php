<?php
/*
Plugin Name: DFX Import/Export Plugin
Plugin URL: http://flexilogin.com
Description: Plugin for exporting selected data schema and its data (sets, types, objects, links) to the zip archive and importing archive into the DFX server.
Version: beta
Author: Flexiweb
Author URL: http://flexiweb.com
License: GPLv2 or later
*/

define('IE_PLUGIN_DIR', dirname(__FILE__));
define('IE_PLUGIN_URL', URL.'plugins/import_export/');

fx_enqueue_script('', IE_PLUGIN_URL.'ie.js', 'import_export/export', 'custom');

function ie_include_lib($error)
{
	require IE_PLUGIN_DIR.'/lib_import_export.php';
}

add_action('fx_init', 'ie_include_lib');

function ie_add_menu_item($menu)
{
	$menu -> add('import_export', 'Import/Export', 'Import/Export Plugin', '#', 1000, IE_PLUGIN_URL.'images/menu_icon.png', IE_PLUGIN_URL.'images/page_icon.png');

	$menu->add_submenu('import_export', 'export', _('Export Tool'), _('Export Tool'), URL . 'import_export/export', 1);
	$menu->add_submenu('import_export', 'import', _('Import Tool'), _('Import Tool'), URL . 'import_export/import', 2);

	return $menu;
}

add_action('fx_add_main_menu_items', 'ie_add_menu_item', 10, 1);

function ie_include_page($error)
{
	if (PAGE == 'import_export') {
		switch (FIRST_PARAM) {
			case 'export' : require IE_PLUGIN_DIR.'/page_export.php'; break;
			case 'import' : require IE_PLUGIN_DIR.'/page_import.php'; break;
			default: return $error;
		}
	}
}

add_action('fx_show_custom_content_page', 'ie_include_page', 100, 1);