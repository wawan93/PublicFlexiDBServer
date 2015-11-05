<?php

function fx_show_menu($menu)
{
	if (is_fx_menu($menu)) {
		foreach($menu -> get_menu() as $id => $value) {
            $link = '';
            if ($value['link'] != '#' && $value['link'] != '') {
                $link = "data-link=\"".$value['link']."\"";
            }

            $inner = "\t<span class=\"\" >".$value['title']."</span>\n";
            echo "\t<span class=\"category menu-icons\" " .$link ." id=\"$id\">$inner</span>\n";


			if ($submenu = $menu -> get_submenu($id)) {
				echo "\t<ul class=\"submenu\">\n";
				echo "\t\t<li class=\"menuname\"><h1>".$value['title']."</h1></li>\n";
				foreach($submenu as $sub_id => $v) echo "\t\t<li class=\"menu_page\" id=\"$sub_id\"><a href=\"".$v['link']."\">".$v['title']."</a></li>\n";
				echo "\t</ul>\n";
			}
		}
	}
}

function fx_show_content()
{
	$content_file = (FIRST_PARAM ? FIRST_PARAM : PAGE).'.php';
	$content_dir = CONF_FX_DIR.'/pages/';
	
	if (file_exists($content_dir.$content_file)) {
		include $content_dir.$content_file;
	}
	else {
		$content_error = new FX_Error(__FUNCTION__, _('Page not found'));

		$custom_content = do_actions('fx_show_custom_content_page', $content_error);

		if (is_fx_error($custom_content)) {
			fx_show_error_metabox($custom_content->get_error_message());
		}
	}
}

function fx_show_page_header()
{
	global $fx_main_menu, $fx_server_menu;

    if ($menu_item = $fx_main_menu -> get_menu_item(PAGE, FIRST_PARAM)) {
		$page_header = $menu_item['header'];
	}
	else {
		$page_header = PAGE.'/'.FIRST_PARAM;
	}

	echo "
    <div class=\"header\">
    	<div class=\"icons ".PAGE."\"></div>
        <h1>".do_actions('fx_print_page_header', $page_header)."</h1>
    </div>\n";
}

function fx_show_footer()
{
	$cur_version = get_fx_option('flexidb_version', 0);
	
	if (defined('CONF_ENABLE_UPDATES') && CONF_ENABLE_UPDATES) {
		$update_options = get_fx_option('update_options', array('new_flexidb_version'=>0));
	}
	
	if (version_compare($cur_version, $update_options['new_flexidb_version'], '<')) {
		echo '<a href="'.URL.'settings/settings_update">'._('Get version').' '.$update_options['new_flexidb_version'].'</a>';
	}
	else {
		echo 'DFX Server v'.$cur_version;
	}
}

function fx_show_company_logo()
{
	if($img_src = get_fx_option('server_logo')) {
		echo '<img src="'.$img_src.'">';
	}
	else {
		echo '<a href="'.URL.'settings/settings_personal" title="Upload custom logo"><img src="'.CONF_IMAGES_URL.'upload_image.jpg"></a>';
	}
}

add_action('fx_show_main_menu', 'fx_show_menu');
add_action('fx_show_content', 'fx_show_content');
add_action('fx_show_page_header', 'fx_show_page_header');
add_action('fx_show_footer', 'fx_show_footer');
add_action('fx_show_company_logo', 'fx_show_company_logo');

/*******************************************************************************
 * Metabox functions
 * Print Flexiweb metabox 
 * @args - array Metabox data
 * @args[header][hidden] - bool Do not show body in metabox
 * @args[footer][content] - string Custom metabox header. Standard header will be replaced
 * @args[header][prefix] - bool Some text which will be prepended to metabox header
 * @args[header][suffix] - bool Some text which will be appended to metabox header
 * @args[header][id] - bool Header HTML ID
 * @args[body][hidden] - bool Hide body in metabox
 * @args[body][function] - string User function which will be called to get(print) body content. [body][content] will be ignored
 * @args[body][args] - array Array of argument for body content function 
 * @args[body][content] - string Body content
 * @args[body][id] - bool Body HTML ID
 * @args[footer][hidden] - bool Do not show footer in metabox
 * @args[footer][function] - string User function which will be called to get(print) footer content.  [footer][content] will be ignored
 * @args[footer][content] - string Body content
 * @args[footer][args] - array Array of argument for cfooter ontent function 
 * @args[footer][id] - bool Body HTML ID
 * @id - string Metabox ID
 ******************************************************************************/

function fx_show_metabox($args = array(), $id ='', $class = '')
{
/*	Array example:

	array('header' => array('hidden' => false, 'prefix' => '', 'suffix' => '', 'id' => ''),
	   	  'body' => array('hidden' => false, 'content' => '', 'function' => '', 'args' => '', 'id' => ''),
		  'footer' => array('hidden' => false,'content' => '', 'function' => '', 'args' => '', 'id' => ''));
*/
		   
	switch ($width)
	{
		case 2: $width = " half-right"; break;
		case 3: $width = " half-left"; break;
		case 1:
		default: $width = "";
	}
	
	$class = $class ? ' '.$class : '';
								   
	echo "<div".($id ? " id=\"$id\"" : "")." class=\"metabox$class\">\n";

	if (!$args['header']['hidden']) {
		if ($args['header']['function']) {
			do_actions('fx_show_metabox_header_func', $args['header']['function'], $args['header']['args'], $args['header']['id'], $args['header']['class']);
		}
		else {
			do_actions('fx_show_metabox_header', $args['header']['prefix'], $args['header']['suffix'], $args['header']['id'], $args['header']['content'], $args['header']['class']);
		}
	}

	if (!$args['body']['hidden'])
	{
		if ($args['body']['function']) {
			do_actions('fx_show_metabox_body_func', $args['body']['function'], $args['body']['args'], $args['body']['id'], $args['body']['class']);
		}
		else {
			do_actions('fx_show_metabox_body', $args['body']['content'], $args['body']['id'], $args['body']['class']);
		}
	}

	if (!$args['footer']['hidden'])
	{
		if($args['footer']['function']) {
			do_actions('fx_show_metabox_footer_func', $args['footer']['function'], $args['footer']['args'], $args['footer']['id'], $args['footer']['class']);
		}
		else {
			do_actions('fx_show_metabox_footer', $args['footer']['content'], $args['footer']['id'], $args['footer']['class']);		
		}
	}

	echo "</div>\n";
}

function fx_show_metabox_header_func($function, $args = array(), $id = '', $class = '')
{
	$class = $class ? ' '.$class : '';
	
	echo "\t\t\t\t<div".($id ? " id=\"$id\"" : "")." class=\"header$class\">\n";

	if ($function && function_exists($function)) {
		$content = call_user_func_array($function, (array)$args);
		if(is_fx_error($content)) $content = '<div class="error">'.$content -> get_error_message().'</div>';
	}
	else {
		return false;
	}
	
	echo "\n\t\t\t\t</div>\n";	
}

function fx_show_metabox_header($prefix, $suffix, $id ='', $content = '', $class = '')
{
	global $fx_main_menu;//, $fx_server_menu;

	if ($content) {
		$page_header = $content;
	}
    elseif ($menu_item = $fx_main_menu -> get_menu_item(PAGE, FIRST_PARAM)) {
		$page_header = $menu_item['header'];
	}
	else {
		$page_header = 'Page not found';//PAGE.'/'.FIRST_PARAM;
	}
	
	if ($icon_url = $fx_main_menu->menu[PAGE]['page_icon']) {
		$icon_style = ' style="background: url('.$icon_url.')"';
	}
	else {
		$icon_style = ' style="background: url('.URL.'images/page_icons/icon_page_question.png)"';
	}
	
	$class = $class ? ' '.$class : '';
	
	echo "\t\t\t\t<div".($id ? " id=\"$id\"" : "")." class=\"header$class\">\n";
	echo "\t\t\t\t\t<div class=\"icons\"$icon_style></div>\n";
    echo "\t\t\t\t\t<h1>$prefix$page_header<i>$suffix</i></i></h1>\n";
    echo "\t\t\t\t</div>\n";
}

function fx_show_metabox_body($content, $id = '', $class = '')
{
	if(is_fx_error($content)) {
		$content = '<div class="error">'.$content -> get_error_message().'</div>';
	}

	$class = $class ? ' '.$class : '';

	echo "\t\t\t\t<div".($id ? " id=\"$id\"" : "")." class=\"content$class\">\n$content\n\t\t\t\t</div>";	
}

function fx_show_metabox_body_func($function, $args = array(), $id = '', $class = '')
{
	$class = $class ? ' '.$class : '';
	
	echo "\t\t\t\t<div".($id ? " id=\"$id\"" : "")." class=\"content$class\">\n";

	if ($function && function_exists($function)) {
		$content = call_user_func_array($function, (array)$args);
		if(is_fx_error($content)) $content = '<div class="error">'.$content -> get_error_message().'</div>';
	}
	else {
		return false;
	}
	
	echo "\n\t\t\t\t</div>\n";	
}

function fx_show_metabox_footer($content, $id = '', $class = '')
{
	if (is_fx_error($content)) {
		$content = '<div class="error">'.$content -> get_error_message().'</div>';
	}

	$class = $class ? ' '.$class : '';

	echo "\t\t\t\t<div".($id ? " id=\"$id\"" : "")." class=\"footer$class\">\n$content\n\t\t\t\t</div>\n";
}

function fx_show_metabox_footer_func($function, $args = array(), $id = '', $class = '')
{
	$class = $class ? ' '.$class : '';
	
	echo "\t\t\t\t<div".($id ? " id=\"$id\"" : "")." class=\"footer$class\">\n";

	if ($function && function_exists($function)) {
		$content = call_user_func_array($function, (array)$args);
		if(is_fx_error($content)) $content = '<div class="error">'.$content -> get_error_message().'</div>';
	}
	else {
		return false;
	}
	
	echo "\n\t\t\t\t</div>\n";	
}

function fx_show_error_metabox($error_msg = '')
{
	if (!$error_msg) {
		$error_msg = 'Error';
	}
	fx_show_metabox(array('body' => array('content' => new FX_Error('generic_error', $error_msg)), 'footer' => array('hidden' => true)));
}

function fx_show_schema_control()
{
	global $data_schema;
	
	$app_group = get_schema_app_group($_SESSION['current_schema']);
	
	$cnl_btn_class = $data_schema['channel'] ? 'active' : 'inactive';
	$app_btn_class = $app_group ? 'active' : 'inactive';
	$www_btn_class = 'inactive';
	
	$cnl_url = URL.'schema_admin/schema_channel';
	$app_url = $app_group ? URL.'app_editor/app_release_manager' : URL.'app_editor/app_group';
	$www_url = URL.'websites/websites_wp';
	$ico_url = URL.'schema_admin/schema_icon';
	$er_url = URL.'design_editor/design_er';
	
	$schema_ico_url = get_object_field_img_url(TYPE_DATA_SCHEMA, $_SESSION['current_schema'], 'icon', 'thumb');
	if (!$schema_ico_url) {
		$schema_ico_url = CONF_IMAGES_URL.'mime_image.png';
	}
	
	echo '
	<a href="'.$cnl_url.'" class="'.$cnl_btn_class.'" title="Schema Channel">C</a>
	<a href="'.$app_url.'" class="'.$app_btn_class.'" title="Schema Application">A</a>
	<a href="'.$ico_url.'" class="btn-ico" title="Channel Icon"><img src="'.$schema_ico_url.'"></a>
	&nbsp;
	<a href="'.$er_url.'" class="active" title="Schema ER Diagram">Er</a>';
}

add_action('fx_show_metabox', 'fx_show_metabox', 10, 2);
add_action('fx_show_metabox_header', 'fx_show_metabox_header', 10, 5);
add_action('fx_show_metabox_header_func', 'fx_show_metabox_header_func', 10, 4);
add_action('fx_show_metabox_body', 'fx_show_metabox_body', 10, 3);
add_action('fx_show_metabox_body_func', 'fx_show_metabox_body_func', 10, 4);
add_action('fx_show_metabox_footer', 'fx_show_metabox_footer', 10, 3);
add_action('fx_show_metabox_footer_func', 'fx_show_metabox_footer_func', 10, 4);
add_action('fx_show_schema_control', 'fx_show_schema_control', 10);