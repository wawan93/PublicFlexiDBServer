<?php
/*
Plugin Name: DFX Twilio Plugin
Plugin URL: http://flexilogin.com
Description: Plugin for communication with Twilio service.
Version: 0.0.1
Author: Flexiweb
Author URL: http://flexiweb.com
License: GPLv2 or later
*/

define('TWILIO_PLUGIN_DIR', dirname(__FILE__));
define('TWILIO_PLUGIN_URL', URL.'plugins/twilio/');

function twilio_include_lib($error)
{
	require TWILIO_PLUGIN_DIR.'/Services/Twilio.php';
}

add_action('fx_init', 'twilio_include_lib');

function twilio_add_task_actions($dirs)
{
	$dirs[] = TWILIO_PLUGIN_DIR.'/task_actions';
	return $dirs;
}

add_action('fx_custom_task_actions', 'twilio_add_task_actions');

function twilio_add_menu_item($menu)
{
    $type_msg_in = get_type_id_by_name(0, 'twilio_msg_in');
	$type_msg_out = get_type_id_by_name(0, 'twilio_msg_out');
	
	$menu -> add('twilio', 'Twilio', 'Twilio Plugin', '#', 1000, TWILIO_PLUGIN_URL.'images/menu_icon.png', TWILIO_PLUGIN_URL.'images/page_icon.png' );
	
	if ((int)$type_msg_in && (int)$type_msg_in) {
		$menu -> add_submenu('twilio', 'msg_in', 'Incoming Messages', 'Twilio Incoming Messages', URL.'twilio/msg_in', 1);
		$menu -> add_submenu('twilio', 'msg_out', 'Outgoing Messages', 'Twilio Outgoing Messages', URL.'twilio/msg_out', 2);
		$menu -> add_submenu('twilio', 'twilio_settings', 'Settings', 'Twilio Settings', URL.'twilio/twilio_settings', 3);
	}
	else {
		$menu -> add_submenu('twilio', 'init', 'Twilio Install', 'Twilio Install', URL.'twilio/twilio_init', 1);
		$menu -> add_submenu('twilio', 'twilio_settings', 'Settings', 'Twilio Settings', URL.'twilio/twilio_settings', 2);
	}

	return $menu;
}

add_action('fx_add_main_menu_items', 'twilio_add_menu_item', 10, 1);

function twilio_include_page($error)
{
	if (PAGE == 'twilio') {
		switch (FIRST_PARAM) {
			case 'msg_in' : require TWILIO_PLUGIN_DIR.'/twilio_in_msg.php'; break;
			case 'msg_out' : require TWILIO_PLUGIN_DIR.'/twilio_out_msg.php'; break;
			case 'twilio_settings' : require TWILIO_PLUGIN_DIR.'/twilio_settings.php'; break;
			case 'twilio_reply' : require TWILIO_PLUGIN_DIR.'/twilio_reply.php'; break;
			case 'twilio_init' : require TWILIO_PLUGIN_DIR.'/twilio_init.php'; break;
			default: return $error;
		}
	}
}

add_action('fx_show_custom_content_page', 'twilio_include_page', 100, 1);

function twilio_send_message($object_type_id, $object_id) {
    $message = get_object($object_type_id, $object_id);

    $twilio_settings = get_fx_option('twilio_settings', array());
    $client = new Services_Twilio($twilio_settings['twilio_sid'], $twilio_settings['twilio_token']);

    if ($message['phone_number'] !== $message['phone_number_to']) {
        $client->account->messages->sendMessage($message['phone_number'], $message['phone_number_to'], $message['message']);
        update_object_field($object_type_id, $object_id, 'status', 1, false);
        return array('status'=>'success');
    } else {
        echo json_encode(array('status'=>'error', 'message'=>'Phone numbers must be different'));
    }
}