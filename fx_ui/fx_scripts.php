<?php

/*******************************************************************************
 * Enqueue JS Script
 *
 * @handle string - Script handle (name)
 * @src string - Full URL. Script location
 * @allowed_path string - Page(s) where allowed to load script
 * @in_footer bool - Put script into the footer
 ******************************************************************************/
function fx_enqueue_script($handle, $src, $allowed_path = '/', $location = 'header')
{
	global $fx_scripts;
	
	list($src, ) = explode('?', $src);
	$src = sanitize_script_url($src);

	if (!$handle) $handle = str_replace('.js', '', basename($src));   

	foreach((array)$allowed_path as $path) {
		$path = trim(str_replace('\\', '/', $path), '/');
		$fx_scripts[$location]['/'.$path][$handle] = $src;		
	}
}

/*******************************************************************************
 * Enqueue JS Scripts
 * Allow to include several scripts per time with common allowed path
 *
 * @sources array - Array of full URLs.
 * @allowed_path string - Page(s) where allowed to load scripts
 * @in_footer bool - Put scripts into the footer
 ******************************************************************************/
function fx_enqueue_scripts($sources, $allowed_path = '/', $location = 'header')
{
	foreach((array)$sources as $src) {
		fx_enqueue_script('', $src, $allowed_path, $location);
	}
}

/*******************************************************************************
 * Enqueue CSS Style
 *
 * @handle string - Script handle (name)
 * @src string - Full URL. Script location
 * @allowed_path string - Page(s) where allowed to load script
 ******************************************************************************/
function fx_enqueue_style($handle, $src, $allowed_path = '/')
{
	global $fx_styles;

	$src = sanitize_script_url($src);
	$path_restr = trim(str_replace('\\', '/', $path_restr), '/');

	if (!$handle) $handle = str_replace('.css', '', basename($src)); 

	foreach((array)$allowed_path as $path)
	{
		$path = trim(str_replace('\\', '/', $path), '/');
		$fx_styles['/'.$path][$handle] = $src;	
	}
}

/*******************************************************************************
 * Enqueue CSS Styles
 * Allow to include several CSS styles per time with common allowed path
 *
 * @sources array - Array of full URLs.
 * @allowed_path string - Page(s) where allowed to load scripts
 ******************************************************************************/
function fx_enqueue_styles($sources, $allowed_path = '/')
{
	foreach((array)$sources as $src) {
		fx_enqueue_style('', $src, $allowed_path);
	}
}

/*******************************************************************************
 * Print Scripts
 * Print all scripts in page header or footer
 *
 * @location header|footer
 ******************************************************************************/
function fx_print_scripts($location = 'header')
{
	global $fx_scripts;
	
	if (!array_key_exists($location, $fx_scripts)) {
		return false;
	}
	
	$allowed_path = array_unique(array('/', '/'.PAGE, '/'.PAGE.'/'.FIRST_PARAM, '/'.PAGE.'/'.FIRST_PARAM.'/'.SECOND_PARAM));
		
	for ($i=0; $i < count($allowed_path); $i++) {
		if (array_key_exists($allowed_path[$i], $fx_scripts[$location])) {
			foreach($fx_scripts[$location][$allowed_path[$i]] as $handle) {
				echo "\t<script type=\"text/javascript\" src=\"$handle\"></script>\n";
			}
		}
	}
}

/*******************************************************************************
 * Print Styles
 * Print all styles in page header
 ******************************************************************************/
function fx_print_styles()
{
	global $fx_styles, $fx_main_menu, $fx_server_menu;
	
	$allowed_path = array_unique(array('/', '/'.PAGE, '/'.PAGE.'/'.FIRST_PARAM, '/'.PAGE.'/'.FIRST_PARAM.'/'.SECOND_PARAM));
		
	for ($i=0; $i < count($allowed_path); $i++) {
		if (array_key_exists($allowed_path[$i], $fx_styles)) {
			foreach($fx_styles[$allowed_path[$i]] as $handle) {
				echo "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"$handle\"/>\n";
			}
		}
	}
	
	if(is_fx_menu($fx_main_menu) && is_fx_menu($fx_server_menu)) {
		echo "\t<style type=\"text/css\">\n";

		foreach(array_merge($fx_main_menu -> get_menu(), $fx_server_menu -> get_menu()) as $id => $value) {
			$src = sanitize_script_url($value['menu_icon'] ? $value['menu_icon'] : URL."images/menu_icons/menu_question.png");
			echo "\t\t.category#$id { background:url($src) 5px 4px no-repeat; }\n";
		}
		
        echo "\t</style>\n";
	}
}
/*
function fx_print_scripts($location = 'header')
{
	fx_print_scripts('header');
}

function fx_print_header_custom_scripts()
{
	fx_print_scripts('header');
}

function fx_print_header_scripts()
{
	fx_print_scripts('header');
}

function fx_print_footer_scripts()
{
	fx_print_scripts('footer');
}*/

add_action('fx_print_scripts', 'fx_print_scripts', 10, 1);

//add_action('fx_print_header_scripts', 'fx_print_header_scripts', 10, 1);
//add_action('fx_print_footer_scripts', 'fx_print_footer_scripts', 10, 1);
add_action('fx_print_styles', 'fx_print_styles', 10, 0);

?>