<?php

function add_action($hook, $function, $priority = 10, $args_count = 1)
{
	global $fx_hooks;

	if(!function_exists($function)) return false;

	$fx_hooks[$hook][$priority][$function] = array('function' => $function, 'args_count' => $args_count);
	
	return true;
}

function do_actions($hook, $value = '')
{
	global $fx_hooks;

	$args = array();

	if (!array_key_exists($hook, $fx_hooks)) {
		return $value;
	}

	ksort($fx_hooks[$hook]);

	$args = func_get_args();

	foreach((array)$fx_hooks[$hook] as $function) {
		foreach((array)$function as $current) {
			if ($current['function']) {
				$args[1] = $value;
				$value = call_user_func_array($current['function'], array_slice($args, 1, (int)$current['args_count']));
			}			
		}
	}

	return $value;
}

function remove_action($hook, $function_to_remove, $priority)
{
	$res = isset($GLOBALS['wp_filter'][$hook][$priority][$function_to_remove]);

	if ($res === true)
	{
		unset($GLOBALS['wp_filter'][$hook][$priority][$function_to_remove]);

		if (empty($GLOBALS['wp_filter'][$hook][$priority])) {
			unset($GLOBALS['wp_filter'][$hook][$priority]);
		}
	}

	return $res;
}

function remove_all_actions($hook)
{
	$res = isset($GLOBALS[$hook]);
	
	if ($res === true)
	{
		unset($GLOBALS[$hook]);
	}

	return $res;
}

function activate_plugin($plugin_name, $plugin_path)
{
	$active_plugins = get_fx_option('active_plugins');
	
	if (!is_array($active_plugins))
	{
		$active_plugins = array();
	}
	
	$active_plugins[$plugin_name] = $plugin_path;
	
	add_log_message(__FUNCTION__, 'Activate plugin - '.$plugin_name);

	return update_fx_option('active_plugins', $active_plugins);
}

function deactivate_plugin($plugin_name)
{
	$active_plugins = get_fx_option('active_plugins');
	
	if(is_array($active_plugins) && array_key_exists($plugin_name, $active_plugins))
	{
		unset($active_plugins[$plugin_name]);
	}
	
	add_log_message(__FUNCTION__, 'Deactivate plugin - '.$plugin_name);
	
	return update_fx_option('active_plugins', $active_plugins);
}

function scan_plugin_dir($dir, &$plugins)
{
	if (is_dir($dir))
	{
		if ($cur_dir = @opendir($dir))
		{
			while (($item = readdir($cur_dir)) != false)
			{
				$path = $dir.(substr($dir, -1) != '/' ? '/' : '').$item;
				
				if(!in_array($path,$exclude_path))
				{
					if (is_file($path))
					{						
						$plugin_data = implode('',file($path));
						$str = '';
						
						if (preg_match('|Plugin Name:(.*)$|mi', $plugin_data, $str))
						{
							$plugin_name = trim($str[1]);
							
							preg_match('|Plugin URL:(.*)$|mi', $plugin_data, $str);
							$plugin_url = trim($str[1]);

							preg_match('|Description:(.*)$|mi', $plugin_data, $str);
							$plugin_description = trim($str[1]);
		
							preg_match('|Version:(.*)$|mi', $plugin_data, $str);
							$plugin_version = trim($str[1]);
							
							preg_match('|Author:(.*)$|mi', $plugin_data, $str);
							$plugin_author = trim($str[1]);			
		
							preg_match('|Author URL:(.*)$|mi', $plugin_data, $str);
							$plugin_author_url = trim($str[1]);
		
							preg_match('|License:(.*)$|mi', $plugin_data, $str);
							$plugin_license = trim($str[1]);
		
							$plugins[] = array('path' => $path,
											   'name' => $plugin_name,
											   'url' => $plugin_url,
											   'description' => $plugin_description,									   
											   'version' => $plugin_version,
											   'author' => $plugin_author,
											   'author_url' => $plugin_author_url,
											   'license' => $plugin_license);
						}
					}
					elseif(is_dir($path) && $item != '.' && $item != '..')
					{
						scan_plugin_dir($path, $plugins);
					}
				}
			}
		}
	}				
}
