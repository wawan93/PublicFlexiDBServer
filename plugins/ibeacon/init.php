<?php
/*
Plugin Name: iBeacon plugin
Plugin URL: http://flexilogin.com
Description: iBeacon
Version: beta
Author: Flexiweb
Author URL: http://flexiweb.com
License: GPLv2 or later
*/

define('IB_PLUGIN_DIR', dirname(__FILE__));
define('IB_PLUGIN_URL', URL.'plugins/ibeacon/');

function ib_plugin_init($error)
{
	require IB_PLUGIN_DIR.'/lib_ibeacon.php';
}

add_action('fx_init', 'ib_plugin_init');

function ib_add_api($error)
{
	include IB_PLUGIN_DIR.'/api_ibeacon.php';

 	_ibeacon_insert_types();
}

add_action('fx_init_api_methods', 'ib_add_api');

function ib_add_menu_item($menu)
{
	$menu->add('ibeacon', 'iBeacon', 'iBeacon', '#', 1000, IB_PLUGIN_URL.'images/menu_icon.png', IB_PLUGIN_URL.'images/page_icon.png');

	$menu->add_submenu('ibeacon', 'uuid', _('UUID'), _('UUID'), URL . 'ibeacon/uuid', 1);
	$menu->add_submenu('ibeacon', 'beacons', _('Beacons'), _('Beacons'), URL . 'ibeacon/beacons', 2);
	
	return $menu;
}

add_action('fx_add_main_menu_items', 'ib_add_menu_item', 10, 1);

function ib_include_page($error)
{
	if (PAGE == 'ibeacon') {
		switch (FIRST_PARAM) {
			case 'uuid' : require IB_PLUGIN_DIR.'/page_uuid.php'; break;
			case 'beacons' : require IB_PLUGIN_DIR.'/page_beacons.php'; break;
			default: return $error;
		}
	}
}

add_action('fx_show_custom_content_page', 'ib_include_page', 100, 1);

