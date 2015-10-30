<?php

class FX_Temp_Resource {

	var $object_type_id = 0;
	var $object_id = 0;
	var $last_error = false;
	var $resources = array();

	function __construct($object_type_id = 0, $object_id = 0)
	{
		global $fx_db;

		$object = object_exists($object_type_id, $object_id);

		if (object_exists($object_type_id, $object_id)) {
			$this -> object_type_id = $object_type_id;
			$this -> object_id = $object_id;

			$pdo = $fx_db -> prepare("SELECT resource_id, add_time, field_name, field_value FROM ".DB_TABLE_PREFIX."temp_tbl WHERE object_type_id=:object_type_id AND object_id=:object_id");
			$pdo -> bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
			$pdo -> bindValue(":object_id", $object_id, PDO::PARAM_INT);
			$pdo -> execute();
			
			if ($pdo -> execute()) {
				foreach ($pdo -> fetchAll() as $row) {
					$row['file_path'] = CONF_UPLOADS_DIR.'/temp/'.$row['field_value'];
					$row['file_url'] = CONF_UPLOADS_URL.'temp/'.$row['field_value'];
					$this -> resources[$row['field_name']] = $row;
				}
			}
			else {
				$sql_error = $pdo -> errorInfo();
				$this -> last_error = 'Unable to init FX Resource. '.$sql_error[0].' '.$sql_error[2];
				return new FX_Error('init_fx_tmp_resourse', $this -> last_error);
			}
		}
		else {
			$this -> last_error = 'Unable to init FX Resource. Object not found.';
			return new FX_Error('init_fx_tmp_resourse', $this -> last_error);
		}
	}

	//*********************************************************************************
	// ADD v1.0
	// Add temporary resource for specified object field
	//*********************************************************************************
	function add($field_name, $field_value)
	{
		global $fx_db;
		
		$field_name = normalize_string($field_name);
		
		if(!$field_name) {
			$this -> last_error = 'Unable to add FX Resource. Please set valid field name';
			return new FX_Error('add_fx_tmp_resourse', $this -> last_error);
		}
		
		$time = time();
		
		if (array_key_exists($field_name, $this -> resources)) {
			$resourse = $this -> resources[$field_name];
			
			if (file_exists($resourse['file_path'])) unlink($resourse['file_path']);
			
			$pdo = $fx_db -> prepare("UPDATE ".DB_TABLE_PREFIX."temp_tbl SET add_time=:add_time, field_value=:field_value WHERE resource_id=:resource_id");
			$pdo -> bindValue(":add_time", $time, PDO::PARAM_INT);
			$pdo -> bindValue(":field_value", $field_value, PDO::PARAM_STR);
			$pdo -> bindValue(":resource_id", $resourse['resource_id'], PDO::PARAM_INT);

			if ($pdo -> execute()) {
				$this -> resources[$field_name]['add_time'] = $time;
				$this -> resources[$field_name]['field_name']['add_time'] = $time;				
				$this -> resources[$field_name]['field_name']['field_value'] = $field_value;
				$this -> resources[$field_name]['file_path'] = CONF_UPLOADS_DIR.'/temp/'.$field_value;
				$this -> resources[$field_name]['file_url'] = CONF_UPLOADS_URL.'temp/'.$field_value;
			}
			else {
				$sql_error = $pdo -> errorInfo();
				$this -> last_error = 'Unable to add FX Resource. '.$sql_error[0].' '.$sql_error[2];
				return new FX_Error('add_fx_tmp_resourse', $this -> last_error);
			}
		}
		else {
			$pdo = $fx_db -> prepare("INSERT INTO ".DB_TABLE_PREFIX."temp_tbl (add_time, object_type_id, object_id, field_name, field_value) VALUES (:add_time, :object_type_id, :object_id, :field_name, :field_value)");
			$pdo -> bindValue(":add_time", $time, PDO::PARAM_INT);
			$pdo -> bindValue(":object_type_id", $this -> object_type_id, PDO::PARAM_INT);
			$pdo -> bindValue(":object_id", $this -> object_id, PDO::PARAM_INT);
			$pdo -> bindValue(":field_name", $field_name, PDO::PARAM_STR);
			$pdo -> bindValue(":field_value", $field_value, PDO::PARAM_STR);
			
			if ($pdo -> execute()) {
				$this -> resources[$field_name]['resource_id'] = $fx_db -> lastInsertId();
				$this -> resources[$field_name]['add_time'] = $time;
				$this -> resources[$field_name]['field_name'] = $field_name;				
				$this -> resources[$field_name]['field_value'] = $field_value;
				$this -> resources[$field_name]['file_path'] = CONF_UPLOADS_DIR.'/temp/'.$field_value;
				$this -> resources[$field_name]['file_url'] = CONF_UPLOADS_URL.'temp/'.$field_value;
			}
			else {
				$sql_error = $pdo -> errorInfo();
				$this -> last_error = _('Unable to add FX Resource').'. '.$sql_error[0].' '.$sql_error[2];
				return new FX_Error('add_fx_tmp_resourse', $this -> last_error);
			}
		}
		
		return true;
	}
	
	//*********************************************************************************
	// GET v1.0
	// Get temporary resource by object ID and field name
	//*********************************************************************************
	function get($field_name = false)
	{
		if ($field_name) {
			return $this -> resources[$field_name];
		}
		else {
			return $this -> resources;
		}

		return array();
	}
	
	//*********************************************************************************
	// SUBMIT v1.0
	// Submit temporary resource(s) with specified object ID and field name (optional)
	//*********************************************************************************
	function submit()
	{
		global $fx_db;
		$errors = new FX_Error();
		
		$object_type_id = $this -> object_type_id;
		$object_id = $this -> object_id;

		foreach ($this -> resources as $resource)
		{
			$submit_empty = $resource['field_value'] == '' ? true : false;

			$obj_dir = CONF_UPLOADS_DIR.'/'.$object_type_id.'/'.$object_id.'/';

			$field_data = get_object_field($object_type_id, $object_id, $resource['field_name'], true);
			
			if (is_fx_error($field_data))
			{
				$errors -> add('submit_fx_tmp_resourse', $field_data -> get_error_message());
			}
			else
			{
				//add_log_message('$submit_empty', $submit_empty);
				
				$field_type = strtolower($field_data['type']);
				$old_value = $field_data['value'];
				$new_value = $resource['field_value'];
				$is_image = $field_type == 'image' ? true : false;
				
				if (!$submit_empty)
				{
					if (is_file($resource['file_path']))
					{
						if(!is_dir($obj_dir)) mkdir($obj_dir, 0777, true);
			
						$path_parts = pathinfo($resource['file_path']);
						$name = $path_parts['filename'];
						$ext = $path_parts['extension'];
						
						$new_value = $object_type_id.'-'.$object_id.'-'.$resource['field_name'].'.'.$ext;//check_file_index($obj_dir, $name, $ext);
			
						//$copy_result = copy($resource['file_path'], $obj_dir.$new_value);
						
						//add_log_message('copy', $resource['file_path'].' '.$obj_dir.$new_value);
			
						if (copy($resource['file_path'], $obj_dir.$new_value))//is_file($obj_dir.$new_value))
						{
							//add_log_message('submit_fx_tmp_resourse', 'file_exists: '.$obj_dir.$new_value);
							
							if ($object_type_id == TYPE_DATA_SCHEMA && $resource['field_name'] == 'icon') {
								add_log_message('change_schema_icon', 'schema_id='.$object_id);
								img_resize($resource['file_path'], $obj_dir.'thumb_'.$new_value, CONF_IMG_THUMB_DEFAULT, CONF_IMG_THUMB_DEFAULT, 0xFFFFFF, 100, 'max');
								img_resize($resource['file_path'], $obj_dir.$new_value, CONF_IMG_SCHEMA_ICON, CONF_IMG_SCHEMA_ICON, 0xFFFFFF, 100, 'max');
							}
							else {
								if ($is_image)
								{
									$image_settings = get_fx_option('image_settings');
									// Default sizes
									if (!$image_settings['img_thumb_width']) $image_settings['img_thumb_width'] = CONF_IMG_THUMB_DEFAULT;
									if (!$image_settings['img_thumb_height']) $image_settings['img_thumb_height'] = CONF_IMG_THUMB_DEFAULT;
									if (!$image_settings['img_small_width']) $image_settings['img_small_width'] = CONF_IMG_SMALL_DEFAULT;
									if (!$image_settings['img_small_height']) $image_settings['img_small_height'] = CONF_IMG_SMALL_DEFAULT;
									if (!$image_settings['img_medium_width']) $image_settings['img_medium_width'] = CONF_IMG_MEDIUM_DEFAULT;
									if (!$image_settings['img_medium_height']) $image_settings['img_medium_height'] = CONF_IMG_MEDIUM_DEFAULT;
									if (!$image_settings['img_large_width']) $image_settings['img_large_width'] = CONF_IMG_LARGE_DEFAULT;
									if (!$image_settings['img_large_height']) $image_settings['img_large_height'] = CONF_IMG_LARGE_DEFAULT;
									// Default quality
									if (!$image_settings['img_thumb_quality']) $image_settings['img_thumb_quality'] = CONF_IMG_QUALITY_DEFAULT;
									if (!$image_settings['img_small_quality']) $image_settings['img_small_quality'] = CONF_IMG_QUALITY_DEFAULT;
									if (!$image_settings['img_medium_quality']) $image_settings['img_medium_quality'] = CONF_IMG_QUALITY_DEFAULT;
									if (!$image_settings['img_large_quality']) $image_settings['img_large_quality'] = CONF_IMG_QUALITY_DEFAULT;
	
									img_resize($resource['file_path'], 
											   $obj_dir.'thumb_'.$new_value, 
											   $image_settings['img_thumb_width'], 
											   $image_settings['img_thumb_height'], 
											   0xFFFFFF, 
											   $image_settings['img_thumb_quality'], 
											   'max');
	
									if ($image_settings['img_small_enabled']) {
										img_resize($resource['file_path'], 
												   $obj_dir.'small_'.$new_value, 
												   $image_settings['img_small_width'], 
												   $image_settings['img_small_height'], 
												   0xFFFFFF, 
												   $image_settings['img_small_quality'], 
												   'min', 
												   false);
									}
									else {
										unlink($obj_dir.'small_'.$new_value);
									}
	
									if ($image_settings['img_medium_enabled']) {
										img_resize($resource['file_path'],
												   $obj_dir.'medium_'.$new_value, 
												   $image_settings['img_medium_width'], 
												   $image_settings['img_medium_height'], 
												   0xFFFFFF, 
												   $image_settings['img_medium_quality'], 
												   'min', 
												   false);
									}
									else {
										unlink($obj_dir.'medium_'.$new_value);
									}
									
									if ($image_settings['img_large_enabled']) {
										img_resize($resource['file_path'], 
												   $obj_dir.'large_'.$new_value, 
												   $image_settings['img_large_width'], 
												   $image_settings['img_large_height'], 
												   0xFFFFFF, 
												   $image_settings['img_large_quality'], 
												   'min', 
												   false);
									}
									else {
										unlink($obj_dir.'large_'.$new_value);
									}
								}							
							}
							

						}
						else {
							//add_log_message('submit_fx_tmp_resourse', _('Unable to submit resource'));
							$errors -> add('submit_fx_tmp_resourse', _('Unable to submit resource').' #'.$resource['resource_id']);
						}
					}
					else {
						$errors -> add('submit_fx_tmp_resourse', _('Temp file not found'));
					}
				}

				if($errors -> is_empty())
				{
					$pdo = $fx_db -> prepare("DELETE FROM ".DB_TABLE_PREFIX."temp_tbl WHERE resource_id=:resource_id");
					$pdo -> bindValue(":resource_id", $resource['resource_id'], PDO::PARAM_INT);
					
					if ($pdo -> execute()) {
						if (!$submit_empty) unlink($resource['file_path']);
					}

					$pdo = $fx_db -> prepare("UPDATE ".DB_TABLE_PREFIX."object_type_".$object_type_id." SET ".normalize_string($resource['field_name'])."=:new_value WHERE object_id=:object_id");
					$pdo -> bindValue(":object_id", $object_id, PDO::PARAM_INT);
					$pdo -> bindValue(":new_value", $new_value, PDO::PARAM_STR);

					if ($pdo -> execute()) {
						if($new_value != $old_value) {
							if($is_image && is_file($obj_dir.'thumb_'.$old_value)) unlink($obj_dir.'thumb_'.$old_value);
							if(is_file($obj_dir.$old_value)) unlink($obj_dir.$old_value);
						}
						
						clear_query_cache_by_type($object_type_id);
						
						return true;
					}
					else {
						$sql_error = $pdo -> errorInfo();
						$errors -> add('submit_fx_tmp_resourse', _('Unable to update Object field').' '.$sql_error[0].' '.$sql_error[2]);
					}

					unset($this -> resources[$resource['field_name']]);
				}
				else {
					//add_log_message('submit_fx_tmp_resourse', print_r($errors -> get_error_message(), true));
				}
			}
		}

		if($errors -> get_error_messages('resource_submit'))
		{
			$this -> last_error = $errors -> get_error_message();
			//return $errors;
		}
		else
		{
			return true;
		}
	}
	
	//*********************************************************************************
	// REMOVE v1.0
	// Remove temporary resource(s) by object ID and field name (optional)
	//*********************************************************************************	
	function remove($field_name = false)
	{
		global $fx_db;

		$resources = $field_name ? array($field_name => $this -> resources[$field_name]) : $this -> resources;

		$count = 0;

		foreach ($resources as $resource) {
			$pdo = $fx_db -> prepare("DELETE FROM ".DB_TABLE_PREFIX."temp_tbl WHERE resource_id=:resource_id");
			$pdo -> bindValue(":resource_id", $resource['resource_id'], PDO::PARAM_INT);
			if($pdo -> execute()) {
				if (file_exists($resource['file_path'])) unlink($resource['file_path']);
				unset($this -> resources[$resource['field_name']]);
				$count++;
			}
		}

		return $count;
	}
	
	//*********************************************************************************
	// REMOVE v1.0
	// Remove temporary resource(s) which have cration time less then required
	//*********************************************************************************	
/*	function remove_old($older_then)
	{
		global $fx_db;

		$pdo = $fx_db -> prepare("SELECT resource_id AS id, field_value AS filename FROM ".DB_TABLE_PREFIX."temp_tbl WHERE add_time<:add_time");
		$pdo -> bindValue(":add_time", $older_then, PDO::PARAM_INT);

		$count = 0;

		if ($pdo -> execute())
		{
			foreach ($pdo -> fetchAll() as $row)
			{
				$pdo = $fx_db -> prepare("DELETE FROM ".DB_TABLE_PREFIX."temp_tbl WHERE resource_id=:resource_id");
				$pdo -> bindValue(":resource_id", $row['id'], PDO::PARAM_INT);
				if($pdo -> execute())
				{
					if (file_exists(CONF_UPLOADS_DIR.'/temp/'.$row['filename'])) unlink(CONF_UPLOADS_DIR.'/temp/'.$row['filename']);
				}
			}
		}

		return $count;	
	}*/
}

function is_fx_temp_resource($object)
{
	return is_object($object) && is_a($object,'FX_Temp_Resource') ? true : false;
}
?>