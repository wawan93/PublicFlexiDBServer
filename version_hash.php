<?php
function scan_dir($dir, $exclude_path, &$directories, &$files, $dir_filter = '')
{
	if (is_dir($dir)) {
		if ($cur_dir = @opendir($dir)) {
			while (($item = readdir($cur_dir)) != false) {
				$path = $dir.'/'.$item;
				if(!in_array($path,$exclude_path)) {
					if (is_file($path)) {
						$files[substr($path,strlen($dir_filter))] = hash_file('md5', $path);
					}
					elseif(is_dir($path) && $item != '.' && $item != '..') {
						$directories[] = substr($path,strlen($dir_filter));
						scan_dir($path, $exclude_path, $directories, $files, $dir_filter);
					}
				}
			}
		}
	}				
}

$dir = dirname(__FILE__);
$dirs = $files = array();
$exclude = array(__FILE__,
				 $dir.'/install.php',
				 $dir.'/plugins',
				 $dir.'/fx_config.php');

scan_dir($dir, $exclude, $dirs, $files, $dir);
$result = serialize(array('directories'=>$dirs, 'files'=>$files, 'base_dir'=>$dir));
print($result);