<?php

function is_normal_string($str)
{
    preg_match("/^[a-z_][a-z0-9_]*$/", $str, $matches);
    return count($matches) == 1;
}

function validate_normal_string($str)
{
    if (!is_normal_string($str)) {
        return new FX_Error(__FUNCTION__, _('Only lowercase letters, digits and underscores are allowed'));
    }	
	
	if (is_numeric($str[0])) {
		return new FX_Error(__FUNCTION__, _('First symbol cannot be numeric'));
	}
	
	return true;
}

/* From: http://www.php.net/manual/en/function.str-getcsv.php#88773 and http://www.php.net/manual/en/function.str-getcsv.php#91170 */
if(!function_exists('str_putcsv'))
{
    function str_putcsv($input, $delimiter = ',', $enclosure = '"')
    {
        // Open a memory "file" for read/write...
        $fp = fopen('php://temp', 'r+');
        // ... write the $input array to the "file" using fputcsv()...
        fputcsv($fp, $input, $delimiter, $enclosure);
        // ... rewind the "file" so we can read what we just wrote...
        rewind($fp);
        // ... read the entire line into a variable...
        $data = fread($fp, 1048576);
        // ... close the "file"...
        fclose($fp);
        // ... and return the $data to the caller, with the trailing newline from fgets() deleted.
        return rtrim($data, "\n");
    }
}

function csv_to_array($input, $key_field = null, $delimiter = ';')
{
    $lines = explode("\n", $input);
    $header_line = array_shift($lines);
    $header = explode($delimiter, $header_line);
    $result = array();
   
    if ($key_field) {
        $key_index = array_search($key_field, $header);
		
        foreach($lines as $line) {
			$line_data = explode($delimiter, $line);
			for($i=0; $i<count($header); $i++) {
				$res[$header[$i]] = $line_data[$i];
			}
           	$result[$line_data[$key_index]] = $res;
        }
    }
    else {
        foreach($lines as $line) {
			$line_data = explode($delimiter, $line);
			for($i=0; $i<count($header); $i++) {
				$res[$header[$i]] = $line_data[$i];
			}
            $result[] = $res;
        }
    }

    return $result;
}

function arrays_to_csv($input, $key_field, $delimiter = ',', $enclosure = "\r\n")
{
    if(count($input) < 1) {
        return '';
    }
	
    $rows_keys = array_keys($input);
    $keys  = array_keys($input[$rows_keys[0]]);
	
    if($key_field) {
        $result = str_putcsv(array_merge(array($key_field), $keys), $delimiter, $enclosure);
    }
    else {
        $result = str_putcsv($keys, $delimiter, $enclosure);
    }
	
    foreach($input as $row_key => $row)
    {
        //reassurance
        $tmp = array();
		
        if($key_field) {
            $tmp[] = $row_key;
        }
		
        foreach($keys as $key) {
            $tmp[] = $row[$key];
        }
		
        $result .= "\n".str_putcsv($tmp, $delimiter, $enclosure);
    }
    return $result;
}

function download_query_csv($query, $main_type, $joined_types, $hide_empty, $filename='query.csv')
{
    $array = exec_fx_query($query,$_SESSION['current_set'],0,0,null,null,null,null,$main_type,$joined_types,$hide_empty);
    if(is_fx_error($array)){ return new FX_Error(__FUNCTION__, "Invalid query!"); }

	$first = array_shift($array);
 	$csv = "sep=;\n".implode(';', array_keys($first))."\n".implode(';', array_values($first));
    foreach ($array as $row) {
        $csv .= "\n".implode(';', array_values($row));
    }

    if($csv == ''){ return new FX_Error(__FUNCTION__, "Can't convert to CSV!"); }

    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
    echo $csv;
    return true;
}

function fx_redirect($url = false)
{
	if ($url === false) {
		$url = current_page_url();
	}
	header("Location: ".$url);
	die();
}

function fx_mail_attachment($to, $subject, $message, $from, $file)
{
	// $file should include path and filename
	$filename = basename($file);
	$file_size = filesize($file);
	$content = chunk_split(base64_encode(file_get_contents($file))); 
	$uid = md5(uniqid(time()));
	$from = str_replace(array("\r", "\n"), '', $from); // to prevent email injection
	
	$header = "From: ".$from."\r\n"
	."MIME-Version: 1.0\r\n"
	."Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n"
	."This is a multi-part message in MIME format.\r\n" 
	."--".$uid."\r\n"
	."Content-type:text/plain; charset=iso-8859-1\r\n"
	."Content-Transfer-Encoding: 7bit\r\n\r\n"
	.$message."\r\n\r\n"
	."--".$uid."\r\n"
	."Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"
	."Content-Transfer-Encoding: base64\r\n"
	."Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n"
	.$content."\r\n\r\n"
	."--".$uid."--"; 

	return mail($to, $subject, "", $header);
}

function _update_version_info()
{
	$fx_api = new FX_API_Client();
	
	$update_info = $fx_api -> execRequest('update/version', 'GET', 'product=flexidb');

	if (!$update_info) {
		return new FX_Error(__FUNCTION__, _('Unable to get versions from Flexilogin server'));
	}

	if (!is_fx_error($update_info)) {
		$update_options = array('new_flexidb_version' => $update_info['flexidb_version'],
							    'new_db_version' => $update_info['db_version'],
							    'last_checked' => time(),
							    'ftp_address' => $update_info['ftp_address'],
							    'ftp_dir' => $update_info['ftp_dir'],
							    'ftp_username' => $update_info['ftp_username'],
							    'ftp_password' => $update_info['ftp_password']);

		update_fx_option('update_options', $update_options);
		return true;
	}
	else {
		return $update_info;
	}
}

/**
 * This file is part of the array_column library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2013 Ben Ramsey <http://benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
if (!function_exists('array_column')) {
    /**
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                     a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                        the returned array. This value may be the integer key
     *                        of the column, or it may be the string key name.
     * @return array
     */
    function array_column($input = null, $columnKey = null, $indexKey = null)
    {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_array($params[0])) {
            trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
            return null;
        }

        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }

        $resultArray = array();

        foreach ($paramsInput as $row) {

            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }

        }

        return $resultArray;
    }

}

function _build_query_tree($code)
{
	if (!is_array($code)) {
		$code = json_decode($code, true);
		if ($code === null) return array();
	}
	
	$result = array();
	
	foreach ($code as $field) {
		if (isset($field['parent_type']) && $field['parent_type']) {
			$result[$field['parent_type']][] = $field['object_type_id'];
		}
	}

	return $result;
}

function get_schema_media($schema_id, $media_type)
{
	if (!$schema_id) {
		return new FX_Error(__FUNCTION__, _('Please specify schema ID'));
	}

	switch ($media_type) {
		case 'image':
			$object_type_id = TYPE_MEDIA_IMAGE;
		break;
		case 'file':
			$object_type_id = TYPE_MEDIA_FILE;
		break;
		default:
			return new FX_Error(__FUNCTION__, _('Please specify valid media type'));
	}

	$media = get_objects_by_type($object_type_id, $schema_id);

	return $media;
}

function get_schema_images($schema_id)
{
	$images = get_schema_media($schema_id, 'image');
	if (is_fx_error()) {
		return array();
	}
	return $images;
}

function get_schema_files($schema_id)
{
	$files = get_schema_media($schema_id, 'file');
	if (is_fx_error()) {
		return array();
	}
	return $files;
}

function get_app_data($app_id = 0)
{
	$app_links = get_actual_links(TYPE_APPLICATION, $app_id, TYPE_APP_DATA);

	if (is_fx_error($app_links)) {
		return $app_links;
	}

	$versions = array();

	foreach($app_links[TYPE_APP_DATA] as $object_id => $link_data) {
		$app_data_object = get_object(TYPE_APP_DATA, $object_id);
		if (is_fx_error($app_data_object)) {
			return $app_data_object;
		}
		else {
			$versions[$object_id] = filter_field_list($app_data_object);
		}
	}

	return $versions;
}

function get_application($app_full_id)
{
	list ($app_id, $app_data_id) = explode('.', $app_full_id);
	
	$app = get_object(TYPE_APPLICATION, $app_id);
	
	if (is_fx_error($app)) {
		return new FX_Error(__FUNCTION__.'::get_main_app', $app -> get_error_message());
	}
	
	$app_data = get_object(TYPE_APP_DATA, $app_data_id);

	if (is_fx_error($app_data)) {
		return new FX_Error(__FUNCTION__.'::get_app_data', $app_data -> get_error_message());
	}

	$app['version_id'] = $app_data_id;
	$app['remote_version_id'] = $app_data['remote_data_id'];
	$app['version'] = $app_data['version'];
	$app['code'] = json_decode($app_data['code'], true);
	$app['style'] = json_decode($app_data['style'], true);
	$app['description'] = $app_data['description'];
	
	return $app;
}

function add_app_version($app_array = array())
{
	$app_data = array();
	$app_data['object_type_id'] = TYPE_APP_DATA;
	$app_data['schema_id'] = $app_array['schema_id'];
	$app_data['set_id'] = 0;
	$app_data['display_name'] = $app_array['display_name'];
	$app_data['version'] = $app_array['display_name'];
	$app_data['code'] = $app_array['code'];

	$app_data_id = add_object($app_data);
	
	if (!is_fx_error($app_data_id)) {
		add_link(TYPE_APPLICATION, (int)$app_array['parent_app_id'], TYPE_APP_DATA, $app_data_id);
	}
	
	return (int)$app_array['parent_app_id'].'.'.$app_data_id;
}

function get_app_version_list($schema_id)
{
	if (!(int)$schema_id) {
		return new FX_Error(__FUNCTION, _('Empty Data Schema ID'));
	}

	$apps = get_objects_by_type(TYPE_APPLICATION, $schema_id);

	$result = array();
	
	foreach ($apps as $app) {
		
		$result[$app['object_id']] = array('display_name' => $app['display_name']);
		
		$versions = get_actual_links(TYPE_APPLICATION, $app['object_id'], TYPE_APP_DATA);

		if (is_fx_error($versions)) {
			return $versions;
		}

		foreach ($versions[TYPE_APP_DATA] as $version_id => $link_data) {
			$result[$app['object_id']]['versions'][$version_id] = get_object_field(TYPE_APP_DATA, $version_id, 'version');
		}
	}

	return $result;	
}


function caching_enabled()
{
	if (defined('CACHING_ENABLED') && CACHING_ENABLED === true) {
		return true;
	}
	return false;
}

function is_zip($fname)
{
	return substr($fname, -strlen(".zip")) === ".zip";
}

function print_fx_errors($errors, $return = false)
{
	$out = '';
	
	if (is_fx_error($errors)) {
		if (!$errors->is_empty()) {
			foreach ($errors->get_error_messages() as $msg) {
				$out .= '<div class="msg-error">ERROR: '.$msg.'</div>';
			}
		}
	}
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}


/*******************************************************************************
 * IS DEBUG MODE
 * Check if DFX server in debug mode
 *
 * @return bool - Sanitized URL
 ******************************************************************************/
function is_debug_mode() {
	$debug_mode = get_fx_option('debug_mode', 0);
	return $debug_mode ? true : false;
}

//*********************************************************************************

/*******************************************************************************
 * Sanitize Script URL
 * Remove all wrong chars from the url
 *
 * @url string - URL
 * @return string - Sanitized URL
 ******************************************************************************/
function sanitize_script_url($url)
{
	$url = str_replace('\\','/',$url);
	$url = preg_replace('#(?<!:)/{2,}#','/', $url);
	list($url, ) = explode('?', $url);
	return $url;
}

//*********************************************************************************
// CUT STRING v0.1
// 
//*********************************************************************************
function cut_string($string, $length, $end='')
{
	if(strlen($string) > $length)
	{
		$string = substr($string, 0, $length);
		$pos = strrpos($string, ' ');
		$string = substr($string, 0, $pos).$end;
	}

	return $string;
}

//*********************************************************************************
// CHECK FILE INDEX v0.1
// Checks file existence and number index if file exists
//*********************************************************************************
function check_file_index($dir, $file, $ext, $number = 0)
{
	$n = $number ? ' ('.$number.')' : '';
	$e = $ext ? '.'.$ext : '';

	if(file_exists($dir.$file.$n.$e))
	{
		$number ++;
		return check_file_index($dir, $file, $ext, $number);
	}

	return $file.$n.$e;
}
//*********************************************************************************	



//*********************************************************************************
// FX PRINT v1.0
// 
//*********************************************************************************
function fx_print($a, $title = false)
{
	if ($title !== false) {
		echo '<p><strong>'.$title.'</strong></p>';
	}
	echo '<pre>'.print_r($a,true).'</pre>';
}
//*********************************************************************************

//*********************************************************************************
// PARSE SHORT CODES v1.1
// Parse shortcodes like a: $$object_type_id.object_id[field]$$
// For example: $$type_name.object_name[some_field]$$ 
//*********************************************************************************
function parse_short_codes($text, $codes_only = false)
{
	$result = array();
	
	preg_match_all('/\$\$\s*(.*)\s*\$\$/Uis', $text, $matches, PREG_PATTERN_ORDER);
	$codes = array_unique($matches[0]);

	if($codes_only) return $codes;

	for($i=0; $i<count($codes); $i++)
	{
		$code = trim(str_replace('$$','',$codes[$i]));
		$code = str_replace(' ','',$code);
		list($object,$field) = explode('[',$code);
		list($type,$object) = explode('.',$object);
		$field = str_replace(']','',$field);
		$result[] = array('code' => $codes[$i], 'type' => $type, 'object' => $object, 'field' => $field);
	}

	return $result;
}
//*********************************************************************************

//*********************************************************************************
// REMOVE SHORT CODES v1.0
// 
//*********************************************************************************
function remove_short_codes($text)
{
	preg_match_all('/\$\$\s*(.*)\s*\$\$/Uis', $text, $matches, PREG_PATTERN_ORDER);
	$codes = array_unique($matches[0]);

	for($i=0; $i<count($codes); $i++)
	{
		$text = str_replace($codes[$i],'',$text);
	}
	
	return trim($text);
}
//*********************************************************************************

//*********************************************************************************
// PARSE STRING v0.1
// to find all short codes %...% in content
//*********************************************************************************
function parse_string($content,$code = '%')
{
	preg_match_all('/\%\s*(.*)\s*\%/Uis', $content, $matches, PREG_PATTERN_ORDER);
	
	$codes = array_unique($matches[0]);
	return $codes;
}
//*********************************************************************************


//*********************************************************************************
// SET SELECTED v0.2
// Print "selected" if v1=v2 or if v2 not set and v1 is true
//*********************************************************************************
function set_selected($v1, $v2)
{
	if(isset($v1) && isset($v2) && $v1 == $v2) {
		echo 'selected="selected"';
	}
	
	if(!isset($v2) && $v1) {
		echo 'checked="checked"';
	}
}

//*********************************************************************************

//*********************************************************************************
// IS SYSTEM FIELD v1.2
// 
//*********************************************************************************

function is_system_field($field)
{
	$system_fields = array('object_id','created','modified','schema_id','set_id','object_type_id','system','name','display_name','description','parent_object_id','parent_object','type','image','image_small','image_medium','image_large','update_links','link_child','link_parent');
	
	if (in_array($field,$system_fields)) {
		return true;
	}
	else {
		return false;
	}
}
			
function get_current_fx_dir()
{
	$dir = 'root';
	
	if ($schema = get_object(TYPE_DATA_SCHEMA, $_SESSION['current_schema'])) {
		$dir .= '/'.$schema['name'];
	}
	
	if ($set = get_object(get_type_id_by_name($_SESSION['current_schema'],'data_schema'), $_SESSION['current_set'])) {
		$dir .= '/'.$set['name'];
	}
	
	return $dir;
}

function set_current_fx_dir($schema='', $set='')
{
	if (!$schema) {
		$_SESSION['current_schema'] = 0;
		$_SESSION['current_set'] = 0;
		fx_redirect(CONF_SITE_URL);
	}

	$_SESSION['current_schema'] = $schema;
	$_SESSION['current_set'] = $set;	

	unset($_SESSION['c_et'], $_SESSION['clt']);
	
	$clear_get = array('id'=>'', 'object_id'=>'', 'object_type_id'=>'', 'p'=>'', 'ipp'=>'');
	
	fx_redirect(replace_url_params($clear_get));
}

//*********************************************************************************
// NORMILIZE STRING v1.0
// Converts string to lowercase, replaces all special chars by '_'
// and removes repeating '_' which were appeared after special chars replacing
// from v0.2 adds prefix
//*********************************************************************************
function normalize_string($str,$prefix = false)
{
	$str = trim($str);
	if($prefix)	$str = strpos($str, $prefix) !== 0 ? $prefix.'_'.$str : $str;
	
	$str = preg_replace("{[^a-z0-9_]}",'_',strtolower($str));
	$str = preg_replace("/_+/i", "_", $str);
		
	return $str; 
}
//*********************************************************************************	

function _get_primary_keys($table_name)
{
	global $fx_db;
	return $fx_db->get_primary_keys($table_name);
}

function _get_table_count($table_name)
{
	global $fx_db;
	return intval($fx_db->get_table_count($table_name));
}

function _table_field_exists($table_name, $field)
{
	global $fx_db;
	return $fx_db->is_table_field_exists($table_name, $field);
}

function _table_exists($table_name, $field)
{
	global $fx_db;
	return $fx_db->is_table_exists($table_name);
}


//*********************************************************************************


function full_del_dir($directory, $delete_top_dir = true)
{
	if (is_dir($directory)) {
		if ($dir = opendir($directory))  {
			while(($file = readdir($dir))) {
				if ( is_file ($directory."/".$file)) {
					unlink ($directory."/".$file);
				}
				elseif ( is_dir ($directory."/".$file) && ($file != ".") && ($file != "..")) {
					full_del_dir ($directory."/".$file);
				}
			}
			closedir ($dir);
			
			if ($delete_top_dir) {
				rmdir ($directory);
			}
			
			return true;
		}
		else {
			return false;
		}
	} else {
		return false;
	}
}

function explode_trim($delimiter,$str) 
{ 
	if ( is_string($delimiter) ) { 
		$str = trim(preg_replace('|\\s*(?:' . preg_quote($delimiter) . ')\\s*|', $delimiter, $str)); 
		return explode($delimiter, $str); 
	} 
	return $str; 
}

function img_resize($src, $dest, $width, $height, $rgb=0xFFFFFF, $quality=100, $ratio='min', $centered=true)
{
  if (!file_exists($src)) { echo '1'; return false; }

  $size = getimagesize($src);

  if ($size === false) { echo '2';  return false; }

  $format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
  $icfunc = "imagecreatefrom".$format;
  if (!function_exists($icfunc)) { echo '3';  return false; }

  $x_ratio = $width / $size[0];
  $y_ratio = $height / $size[1];

  //$ratio = min($x_ratio, $y_ratio);
  
  $ratio = $ratio == 'min' ? min($x_ratio, $y_ratio) : max($x_ratio, $y_ratio);
  
  $use_x_ratio = ($x_ratio == $ratio);

  $new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio);
  $new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio);
  $new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2);
  $new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);

  if (!$centered) {
	  $new_left = $new_top = 0;
  }

  $isrc = $icfunc($src);
  $idest = imagecreatetruecolor($width, $height);

  imagefill($idest, 0, 0, $rgb);
  imagecopyresampled($idest, $isrc, $new_left, $new_top, 0, 0, 
	$new_width, $new_height, $size[0], $size[1]);

  imagejpeg($idest, $dest, $quality);

  imagedestroy($isrc);
  imagedestroy($idest);

  return true;

}	

function image_crop($file_input, $file_output, $crop = 'square',$percent = false)
{
	list($w_i, $h_i, $type) = getimagesize($file_input);
	
	if (!$w_i || !$h_i) {
		return new FX_Error(__FUNCTION__, _('Unable to get image width or image height'));
	}
		
	$types = array('','gif','jpeg','png');
	$ext = $types[$type];
	
	if ($ext) {
		$func = 'imagecreatefrom'.$ext;
		$img = $func($file_input);
	}
	else {
		return new FX_Error(__FUNCTION__, _('Invalid (not image) file format'));
	}
		
	if ($crop == 'square') {
		$min = $w_i;
		if ($w_i > $h_i) {
			$min = $h_i;
		}
		$w_o = $h_o = $min;
	}
	else {
		list($x_o, $y_o, $w_o, $h_o) = $crop;
		
		if ($percent) {
			$w_o *= $w_i / 100;
			$h_o *= $h_i / 100;
			$x_o *= $w_i / 100;
			$y_o *= $h_i / 100;
		}
		
		if ($w_o < 0) {
			$w_o += $w_i;
		}
		
		$w_o -= $x_o;
		
		if ($h_o < 0) {
			$h_o += $h_i;
		}
		$h_o -= $y_o;
	}
	
	$img_o = imagecreatetruecolor($w_o, $h_o);
	imagecopy($img_o, $img, 0, 0, $x_o, $y_o, $w_o, $h_o);
	
	if ($type == 2) {
		return imagejpeg($img_o,$file_output, 100);
	}
	else {
		$func = 'image'.$ext;
		return $func($img_o,$file_output);
	}
}

//*********************************************************************************
// IS URL v0.1
// Checks if the value is URL
//*********************************************************************************
function is_url($value)
{
	$stmt = "~^(?:(?:https?|ftp|telnet)://(?:[a-z0-9_-]{1,32}(?::[a-z0-9_-]{1,32})?@)?)?(?:(?:[a-z0-9-]{1,128}\.)+(?:com|net|org|mil|edu|arpa|gov|biz|info|aero|inc|name|[a-z]{1,10})|(?!0)(?:(?!0[^.]|255)[0-9]{1,3}\.){3}(?!0|255)[0-9]{1,3})(?:/[a-z0-9.,_@%&?+=\~/-]*)?(?:#[^ '\"&<>]*)?$~i";		

	return preg_match($stmt, $value) ? true : false;
}
//*********************************************************************************

//*********************************************************************************
// IS IP v0.1
// Checks if the value is IP address
//*********************************************************************************
function is_ip($value)
{
	$stmt = "/^(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}$/";
	return preg_match($stmt, $value) ? true : false;
}
//*********************************************************************************

//*********************************************************************************
// IS EMAIL v1.0
// Checks if the value is e-mail address
//*********************************************************************************
function is_email($value)
{
	$stmt = "/^[^0-9]+[A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/";
	return preg_match($stmt, $value) ? true : false;
}
//*********************************************************************************		

function generate_random_string($length, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
{
	$numChars = strlen($chars);
	$out = '';
	
	for ($i = 0; $i < $length; $i++) {
		$out .= $chars[rand(1, $numChars)-1];
	}
	
	return $out;
}

//*********************************************************************************
// SHOW SELECT OPTIONS v1.2
// 
//*********************************************************************************
function show_select_options($options, $value, $label, $selected=0, $echo=true)
{
	$out = '';

	if (count($options)) {
		foreach ($options as $k=>$v) {
            if (is_object($v)) {
				$v = (array)$v;
			}
			if ($value == '_label') {
				$opt_value = $v;
			}
			else {
				$opt_value = $value == '' ? $k : $v[$value];
			}
			
			if ($label == '_value') {
				$opt_label = $k;
			}
			$opt_label = $label == '' ? $v : $v[$label];
			$s = (string)$selected === (string)$opt_value ? ' selected="selected"' : $s = '';
			$out .= '<option value="'.$opt_value.'"'.$s.'>'.$opt_label.'</option>';
		}			
		
	}
	else {
		$out .= '<option disabled="disabled">No items</option>';
	}
	
	if ($echo) {
		echo $out;
	}
	else {
		return $out;
	}
}

//*********************************************************************************

function current_page_url($clear_get = false)
{
	$URL = 'http'.($_SERVER['HTTPS'] == 'on' ? 's' : '').'://';
	if ($_SERVER["SERVER_PORT"] != "80") {
		$URL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
	}
	else {
		$URL .= $_SERVER["SERVER_NAME"];
	}

	$URL .= $_SERVER['REQUEST_URI'];
		
	if ($clear_get === true) {
		list($URL, ) = explode('?', $URL);
	}
	
	return $URL;
}		

//*********************************************************************************
// REPLACE URL PARAM v1.0
// @name - name of parameter to replace
// @new_value - parameter value to replace
// @url - source url in which need to replace parameters (if parameter exists)
//*********************************************************************************
function replace_url_param($name, $new_value, $url = '')
{	
	if (!$url) {
		$url = $_SERVER["REQUEST_URI"];
	}

	$parsed_url = parse_url($url, PHP_URL_QUERY);

	$params = array();
	parse_str($parsed_url, $params);

	if (strlen((string)$new_value)){
		$params[$name] = $new_value;
	}
	else {
		unset($params[$name]);
	}

	if ($pos = strpos($url,'?')) {
		$url = substr($url,0,$pos);
	}

	if($url[strlen($url) - 1] == '/') {
		$url = substr($url, 0, strlen($url)-1);
	}

	return $params ? $url.'?'.http_build_query($params) : $url;
}
//*********************************************************************************

//*********************************************************************************
// REPLACE URL PARAMS v1.0
// @new_params - associative array with parameters to replace
// @url - source url in which need to replace parameters (if parameter exists)
//*********************************************************************************
function replace_url_params($new_params = array(), $url = '')
{	
	if(!$url) {
		$url = $_SERVER["REQUEST_URI"];
	}

	$parsed_url = parse_url($url, PHP_URL_QUERY);

	$params = array();
	parse_str($parsed_url, $params);

	foreach($new_params as $name => $new_value) {
		if(strlen((string)$new_value)) {
			$params[$name] = $new_value;
		}
		else {
			unset($params[$name]);			
		}
	}

	if($pos = strpos($url,'?')) {
		$url = substr($url,0,$pos);
	}

	if($url[strlen($url) - 1] == '/') {
		$url = substr($url, 0, strlen($url)-1);
	}

	return $params ? $url.'?'.http_build_query($params) : $url;
}
//*********************************************************************************

//*********************************************************************************
// GET SCHEMA_CHANNELS v1.0
// @schema_id - local FlexiDB schema ID
//*********************************************************************************
function get_schema_channel($schema_id)
{
	$channel_id = get_object_field(TYPE_DATA_SCHEMA, $schema_id, 'channel');
	
	if (!is_numeric($channel_id)) {
		return false;
	}
	
	$fx_api = new FX_API_Client();
	$result = $fx_api -> execRequest('channel', 'GET', 'channel_id='.$channel_id.'&return=1');

	return !is_fx_error($result) && $channel_id == $result ? $channel_id : false;
}

function get_queries_by_type($object_type_id)
{
	$res = get_type_component($object_type_id, 'query');
	return is_fx_error($res) ? array() : $res;
}

function get_forms_by_type($object_type_id)
{
	$res = get_type_component($object_type_id, 'form');
	return is_fx_error($res) ? array() : $res;
}

function get_fsm_events_by_type($object_type_id)
{
	$res = get_type_component($object_type_id, 'fsm');
	return is_fx_error($res) ? array() : $res;
}

function get_type_component($object_type_id, $component_type)
{
	global $fx_db;
	
	switch ($component_type) {
		case 'query':
		
			$where = array(
				'operator' => 'or',
				'main_type' => $object_type_id,
				'joined_types' => '%"'.$object_type_id.'"%'
			);
			$fx_db->select(DB_TABLE_PREFIX."object_type_".TYPE_QUERY, 'object_id')->where($where);
		break;
		case 'form':
			$fx_db->select(DB_TABLE_PREFIX."object_type_".TYPE_DATA_FORM, 'object_id')->where(array('object_type'=>$object_type_id));
		break;
		case 'fsm':
			$fx_db->select(DB_TABLE_PREFIX."object_type_".TYPE_FSM_EVENT, 'object_id')->where(array('object_type'=>$object_type_id));
		break;
		default:
			return new FX_Error(__FUNCTION__, _('Invalid component type'));
	}
	
	$result = array();

	if (!is_fx_error($fx_db->select_exec())) {
		foreach ($fx_db->get_all() as $row) {
			$result[] = $row['object_id'];
		}
	}
	else {
		return new FX_Error(__FUNCTION__, _('DB  Error'));
	}

	return $result;
}

function usort_by_row_value(&$array, $key, $desc = false) 
{ 
	if(!is_array($props)) 
		$props = array($props => true); 
		
	uasort($array, function($a, $b) use ($key, $desc) {
		$desc = $desc === true ? -1 : 1;
        if (is_string($a[$key]) && is_string($b[$key])) {
            if (strtotime($a[$key]) && strtotime($b[$key])) {
                return (strtotime($a[$key])-strtotime($b[$key]))*$desc;
            }
            return (strcmp($a[$key], $b[$key]))*$desc;
        }
		return($a[$key]-$b[$key])*$desc;
	});    
}

function get_schema_app_group($schema_id)
{
	$app_groups = get_objects_by_type(TYPE_APPLICATION, $schema_id);
	
	$app_group = array_shift($app_groups);
	
	return $app_group ? $app_group : false;
}

function filter_array_by_value(&$array, $value)
{
	if (is_array($value)) {
		foreach ($array as $k=>$v) {
			foreach ($value as $fn=>$fv) {
				if (strpos(strtolower($v[$fn]), strtolower($fv)) === false) {
					unset($array[$k]);
				}	
			}	
		}		
	}
	else {
		$n = strtolower($value);
		foreach ($array as $k=>$v) {
			$h = strtolower(implode(' ', $v));
			if (strpos($h, $n) === false) {
				unset($array[$k]);
			}
		}
	}
}

if (!function_exists(_)) {
	function _($str) {
		return $str;
	}
}

if (!function_exists(gettext)) {
	function gettext($str) {
		return $str;
	}
}

function _get_schema_er($schema_id)
{
	global $fx_db;
	
	$types_in_use = $types_free = $connections = array();
	
	$user_subscription = TYPE_SUBSCRIPTION;

	$types_free = get_schema_types(0, 'none') + get_schema_types($_SESSION['current_schema'], 'none');
	
	foreach ($types_free as $key=>$value) {
		if ($value['system'] && $value['object_type_id'] == $user_subscription) {
			$color = 'green';
		}
		elseif ($value['system']) {
			$color = 'red';
		}
		else {
			$color = 'blue';
		}
		
		$types_free[$key] = array(
			//'object_type_id'=>$key,
			'name'=>$value['name'],
			'display_name'=>$value['display_name'],
			'system'=>$value['system'] ? 1 : 0,
			'color'=>$color, 
			);
		
	}

	if (!$link_types = get_fx_option('er_tmp_'.$schema_id)) {
		$sth = $fx_db -> prepare("SELECT object_type_1_id, object_type_2_id, relation, position, strength FROM ".DB_TABLE_PREFIX."link_type_tbl WHERE schema_id=:schema_id AND system<>1");
		$sth -> bindValue(":schema_id", $schema_id, PDO::PARAM_INT);
		
		if (!$sth -> execute()) {
			add_log_message(__FUNCTION__, print_r($stmt->errorInfo(), true));
		}

		$link_types = $sth -> fetchAll();
		$er_status = '';
	}

	foreach ((array)$link_types as $link_type)
	{
		$t1 = $link_type['object_type_1_id'];
		$t2 = $link_type['object_type_2_id'];
		
		list ($x1, $y1, $x2, $y2) = explode(',', $link_type['position']);
		
		if (array_key_exists($t1, $types_free)) {
			$types_in_use[$t1] = array(
				'name'=>$types_free[$t1]['name'], 
				'display_name'=>$types_free[$t1]['display_name'], 
				'system'=>$types_free[$t1]['system'], 
				'color'=>$types_free[$t1]['color'], 
				'x'=>$x1, 
				'y'=>$y1);

			unset($types_free[$t1]);
		}

		if (array_key_exists($t2, $types_free)) {
			$types_in_use[$t2] = array(
				'name'=>$types_free[$t2]['name'], 
				'display_name'=>$types_free[$t2]['display_name'], 
				'system'=>$types_free[$t2]['system'], 
				'color'=>$types_free[$t2]['color'], 
				'x'=>$x2, 
				'y'=>$y2);

			unset($types_free[$t2]);
		}

		$connections['er_'.$t1.'-er_'.$t2] = array(
			'relation' => $link_type['relation'],
			'strength' => $link_type['strength']
		);
	}
	
	$result = array(
		'connections' => $connections,
		'types_free' => $types_free,
		'types_in_use' => $types_in_use
	);
	
	return $result;
}

function currency_convert($amount, $from, $to)
{
 	$amount = urlencode($amount);
	$from= urlencode($from);
	$to = urlencode($to);

	$url = "https://www.google.com/finance/converter?a=$amount&from=$from&to=$to";

	$get = file_get_contents($url);

	$get = explode("<span class=bld>", $get);
	$get = explode("</span>", $get[1]);
	$converted_amount = preg_replace("/[^0-9\.]/", null, $get[0]);
	
	return $converted_amount;
}

function _clear_remote_channel_cache($channel_id)
{	
	$fx_api = new FX_API_Client();
	$result = $fx_api -> execRequest('channel/channel_cache', 'DELETE', 'channel_id='.$channel_id);
	
	if (is_fx_error($result)) {
		return new FX_Error(__FUNCTION__, 'Flexilogin: '.$result->get_error_message());
	}
}

function check_system_links()
{
	$system_links = array(
		array(TYPE_SUBSCRIPTION, TYPE_DATA_SCHEMA, 4),
		array(TYPE_SUBSCRIPTION, TYPE_DATA_SET, 4),
		array(TYPE_SUBSCRIPTION, TYPE_ROLE, 4),
		array(TYPE_APPLICATION, TYPE_APP_DATA, 2),
	
	);

	$error_occured = false;
	
	foreach ($system_links as $link_type) {
		if (link_type_exists($link_type[0], $link_type[1]) === false) {
			$res = add_link_type($link_type[0], $link_type[1], $link_type[2], 0, true);
			if (is_fx_error($res)) {
				$error_occured = true;
				$msg = _('Unable to add system type').' ['.$link_type[0].'-'.$link_type[1].'('.$link_type[2].')';
			}
			else {
				$msg = _('Added missed system type').' ['.$link_type[0].'-'.$link_type[1].'('.$link_type[2].')';
			}
			
			add_log_message(__FUNCTION__, $msg);
		}
	}
	
	if (!$error_occured) {
		update_fx_option('system_links_checked', 1);
		add_log_message(__FUNCTION__, _('System links were successfully checked'));
	}
}

function include_to_var($file, $args)
{
	extract($args);
    ob_start();
    include($file);
    return ob_get_clean();
}

function require_to_var($file, $args)
{
	extract($args);
    ob_start();
    require($file);
    return ob_get_clean();
}