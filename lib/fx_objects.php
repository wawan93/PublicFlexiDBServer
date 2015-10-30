<?php

function add_object($object_array, $forced = false, $user_instance = false, $units = array())
{	
	global $fx_db;
	
	$errors = new FX_Error();
	$obj_fields = $obj_values = array();

    $type_data = get_type($object_array['object_type_id']);
	if (!$type_data || is_fx_error($type_data)) {
		return new FX_Error(__FUNCTION__, _('Invalid object type'));
	}

	//TODO: TEMPORY SOLUTION - SYSTEM MUST BE REMOVED
	if (array_key_exists('system', $type_data['fields'])) {
		unset($type_data['fields']['system']);
	}

	//TODO: do we need object_id as base field ?
	if (array_key_exists('object_id', $type_data['fields'])) {
		unset($type_data['fields']['object_id']);
	}

	$object_array['set_id'] = intval($object_array['set_id']);

	if ($type_data['system']) {
		$object_array['set_id'] = 0;
	}
	else {
		if (!object_exists(TYPE_DATA_SET, $object_array['set_id']) && $object_array['set_id'] !== 0) {
			return new FX_Error(__FUNCTION__, _('Invalid data set identifier')); 
		}
	}
	
	if ($type_data['name_format']) {
		$display_name = $type_data['name_format'];
		$codes = parse_string($display_name);
	
		for ($i=0; $i<count($codes); $i++) {
			$code = trim(str_replace('%','',$codes[$i]));
			$display_name = str_replace($codes[$i], $object_array[$code], $display_name);
		}
		$object_array['display_name'] = $display_name;
	}
	
	if ((!array_key_exists('name',$object_array) || empty($object_array['name'])) && $object_array['display_name']) {
		$object_array['name'] = $object_array['display_name'];
	}
	
	$object_array['name'] = normalize_string($object_array['name'], $type_data['prefix']);
	
	if (!$object_array['name']) {
		if($forced) {
			$object_array['name'] = 'no_name';
		}
		else {
			$errors->add(__FUNCTION__, _('Please enter object name')); 
		}
	}
	
	if (is_numeric($object_array['name'])) {
		if($forced) {
			$object_array['name'] = '_'.$object_array['name'];
		}
		else {
			$errors->add(__FUNCTION__, _('Object name cannot consist only of numbers'));
		}
	}

	if (!$object_array['display_name']) {
		$object_array['display_name'] = $object_array['name'];
	}

	if (object_exists($object_array['object_type_id'], $object_array['name'], $object_array['set_id'])) {
		$similar_name_count = _get_name_index($object_array['object_type_id'], $object_array['set_id'], $object_array['schema_id'], $object_array['name']);
		$object_array['name'] = $object_array['name'].($similar_name_count ? '_'.$similar_name_count : '');
		$object_array['display_name'] = ($object_array['display_name'] ? $object_array['display_name'] : $object_array['name']).' '.$similar_name_count;
	}

	$object_array['created'] = $object_array['modified'] = time();
	
	$qr_codes = array();

	foreach ($type_data['fields'] as $field_name => $field_options)
	{
		$field_value = array_key_exists($field_name, $object_array) ? $object_array[$field_name] : '';
		
		$field_value = validate_field_value($field_value, $field_options, $forced);
		
		if (strtoupper($field_options['type']) == 'FLOAT') {

			if (array_key_exists($units[$field_name]) && $units[$field_name] != $field_options['unit']) {
				//need to convert to the base units
				$metric = get_metric($field_options['unit'], true);

				$old_factor = $metric['units'][$units[$field_name]]['factor'];
				$new_factor = $metric['units'][$field_options['unit']]['factor'];
				
				$field_value = $field_value * $old_factor / $new_factor;
			}
			
			$unit_decimals = isset($metric['units'][$field_options['unit']]['decimals']) ? $metric['units'][$field_options['unit']]['decimals'] : 2;
			number_format($field_value, $unit_decimals);
		}
		
		// FSM Event Initial State
		//============================================================================
		if (is_numeric($field_options['type']) && $user_instance !== false) {
			if ($fsm_events = get_fsm_initial_events($object_array['object_type_id'], $field_name)) {
				$event_success = false;
				foreach ($fsm_events as $event_id => $event_value) {
					if ($event_value != $field_value) {
						$event_value = get_enum_label($field_options['type'], $event_value);
						return new FX_Error(__FUNCTION__, _('Initial ')."$field_name "._('must be')." \"$event_value\"");
					}
				}
			}
		}
		//============================================================================	

		if (is_fx_error($field_value)) {
			$errors -> add(__FUNCTION__, $field_value -> get_error_message());
		}
		else {
			$obj_fields[] = $field_name;
			$obj_values[] = $field_value;

			if ($field_options['type'] == 'qr') {
				$qr_codes[$field_name] = $field_value;
			}
		}
	}

	if (!$errors -> is_empty()) {
		return $errors;
	}

    $object_id = $fx_db->insert(DB_TABLE_PREFIX."object_type_".$object_array['object_type_id'], array_combine($obj_fields,$obj_values));
	
	if (!is_fx_error($object_id))
	{		
		foreach ($qr_codes as $field_name => $field_value)
		{
			$field_value = $field_value ? $field_value : get_object_permalink($type_data['object_type_id'], $object_id);
			$obj_dir = CONF_UPLOADS_DIR.'/'.$type_data['object_type_id'].'/'.$object_id;
			
			if (!is_dir($obj_dir)) {
				mkdir($obj_dir, 0777, true);
			}
			
			$file = $obj_dir.'/'.$field_name.'.png';
			QRcode::png($field_value, $file);
		}
		
		clear_query_cache_by_type($object_array['object_type_id']);
		
		if ($object_array['object_type_id'] == TYPE_DATA_SET) {
			$channel_id = get_object_field(TYPE_DATA_SCHEMA, (int)$object_array['schema_id'], 'channel');
			if ((int)$channel_id) {
				_clear_remote_channel_cache($channel_id);
			}
		}

 		return $object_id;
	}
	else {
		return new FX_Error(__FUNCTION__, $object_id->get_error_message());
	}	
}

function _get_name_index($object_type_id, $set_id, $schema_id, $str)
{
    global $fx_db;

	if(!$str) return '';

	$index = $count = 0;

	$fx_db->select(DB_TABLE_PREFIX."object_type_".(int)$object_type_id, 'name')->where(array('name like' => $str.'%', 'set_id'=>$set_id/*, 'schema_id'=>$schema_id*/));

	if (is_fx_error($fx_db->select_exec())) {
		return $fx_db->get_last_error();
	}

    $result = $fx_db->get_all();
	$count = count($result);
	$indexes = $matches = array();
	
	foreach ($result as $object) {
		preg_match_all('/\d+/', $object['name'], $matches);
		$indexes[] = array_pop($matches[0]);
	}
	
	sort($indexes, SORT_NUMERIC);

	$index = array_pop($indexes);
	$index = !$index && $count ? $count : $index;

	return $index ? (string)($index + 1) : '';
}

function replicate_object($object_type_id, $object_id)
{
    global $fx_db;
	
	$object = get_object($object_type_id, $object_id);

	if(is_fx_error($object)) {
		return new FX_Error(__FUNCTION__, $object->get_error_message());
	}
	
	unset($object['object_id']);
	unset($object['object_type_id']);

	$object['name'] .= '_'.time();
	$object['display_name'] = 'Copy of '.$object['display_name'];
	$object['created'] = $object['modified'] = time();

	$insert = $fx_db->insert(DB_TABLE_PREFIX."object_type_".$object_type_id, $object);
	
	if (is_fx_error($insert)) {
		return $insert;
	}

	$new_object_id = $fx_db -> lastInsertId();

	$src_dir = CONF_UPLOADS_DIR.'/'.$object_type_id.'/'.$object_id;
	$new_dir = CONF_UPLOADS_DIR.'/'.$object_type_id.'/'.$new_object_id;

	if (is_dir($src_dir))
	{
		mkdir($new_dir, 0777, true);

		if ($obj_dir = opendir($src_dir)) {
		   while (($file = readdir($obj_dir)) !== false) {
			   if ($file != '.' && $file != '..') {
					if (!copy($src_dir.'/'.$file, $new_dir.'/'.$file)) {
						add_log_message(__FUNCTION__, 'Unable to copy file ('.$src_dir.'/'.$file.', '.$new_dir.'/'.$file.')');
					}
			   }
		   }
		   closedir($obj_dir);
		}
	}

	return $new_object_id;
}


function get_object($object_type_id, $object_id, $details = false)
{
    global $fx_db;

	$fx_db->select(DB_TABLE_PREFIX."object_type_".(int)$object_type_id)->where(array('object_id' => $object_id))->limit(1);

	if (!is_fx_error($fx_db->select_exec())) {
		if(!$object = $fx_db->get()) {
			return new FX_Error(__FUNCTION__, _('Unable to get object with the specified ID'));
		}
	}
	else {
		return new FX_Error(__FUNCTION__, _('Specified object type does not exist'));			
	}

    foreach ($object as &$field) {
        $field = stripslashes($field);
    }

    if ($details) {
		$type_fields = get_type_fields($object_type_id);

		foreach($type_fields as $field => $details) {
			$value = isset($object[$field]) ? $object[$field] : '';
			$type_fields[$field]['value'] = $value;
		}
		$object = $type_fields; 
	}	

	return array_merge(array('object_type_id'=>$object_type_id), $object);
}

function get_object_public($object_type_id, $object_id, $details = false)
{
	$object = get_object($object_type_id, $object_id, $details);
	
	if (!is_fx_error($object)) {
		filter_private($object);
	}
	
	return $object;
}

function filter_private(&$object)
{
	if (is_array($object)) {
		unset($object['schema_id'], $object['set_id'], $object['system'], $object['user_ip'], $object['created'], $object['modified']);		
	}
	
	return $object;
}

function get_object_field($type, $object, $field, $details = false, $schema_id = 0, $set_id = '')
{
	$field = normalize_string($field);

	if (!$type) {
		return new FX_Error(__FUNCTION__, _('Empty Object Type identifier (name or ID)'));
	}
	if (!$object) {
		return new FX_Error(__FUNCTION__, _('Empty Object identifier (name or ID)'));
	}
	if (!$field) {
		return new FX_Error(__FUNCTION__, _('Empty field name'));
	}

	$object_type_id = $type;

	if (!is_numeric($object_type_id)) {
		$object_type_id = get_type_id_by_name((int)$schema_id, $type);
		if (is_fx_error($object_type_id)) {
			return $object_type_id;
		}
	}
	
    global $fx_db;
	
	if (is_numeric($object)) {
		$fx_db->select(DB_TABLE_PREFIX."object_type_".(int)$object_type_id, array('object_id', $field))->where(array('object_id' => $object))->limit(1);
	}
	else {
		if (!strlen((string)$set_id)) {
			return new FX_Error(__FUNCTION__, _('Specify Data Set ID to get vield value by object name'));
		}
		
		$fx_db->select(DB_TABLE_PREFIX."object_type_".(int)$object_type_id, array('object_id', $field))->where(array('name' => $object, 'set_id' => $set_id))->limit(1);
	}

	if (!is_fx_error($fx_db->select_exec())) {
		if(!$object = $fx_db->get()) {
			return new FX_Error(__FUNCTION__, _('Unable to get object with the specified ID'));
		}
	}
	else {
		return new FX_Error(__FUNCTION__, _('Specified object type does not exist'));			
	}

	if ($details) {
		$type_fields = get_type_fields($object_type_id);
		
		if (is_numeric($type_fields[$field]['type'])) {
			$type_fields[$field]['label'] = get_enum_label($type_fields[$field]['type'], $object[$field]);
		}
			
		return array_merge(array('object_type_id' => $object_type_id, 'object_id' => $object['object_id'], 'value' => $object[$field]), $type_fields[$field]);
	}	

	return $object[$field];
}

function update_object_field($object_type_id, $object_id, $field, $value, $forced = false, $user_instance = false)
{
	if(is_fx_error($object = get_object($object_type_id, $object_id))) {
		return $object;
	}
	
	if(!array_key_exists($field, $object)) {
		return new FX_Error(__FUNCTION__, _('Field does not exists in specified object'));	
	}
	
	$object[$field] = $value;
	
	return update_object($object, $forced, $user_instance);
}

function get_current_object($object_type_id = false)
{
	if ($object_type_id == false) {
		if(isset($_GET['object_type_id'])) {
			$object_type_id = $_GET['object_type_id'];
		}
		elseif (isset($_POST['object_type_id'])) {
			$object_type_id = $_POST['object_type_id'];
		}
		else {
			$object_type_id = 0;
		}
	}

	if (isset($_GET['object_id'])) {
		$object_id = $_GET['object_id'];
	}
	elseif (isset($_POST['object_id'])) {
		$object_id = $_POST['object_id'];
	}
	else {
		$object_id = 0;
	}

	return get_object($object_type_id, $object_id, $details);
}

function update_object($object_array, $forced = false, $user_instance = false)
{
    global $fx_db;

	$fields_to_update = array();
	$fields_to_revision = array();
	$qr_codes = array();
	$update_required = false;

	if (!$object_id = (int)$object_array['object_id']) {
		return new FX_Error(__FUNCTION__, _('Specify correct Object ID'));
	}

	if (!$object_type_id = (int)$object_array['object_type_id']) {
		return new FX_Error(__FUNCTION__, _('Specify correct Type ID'));
	}

	$old_object = get_object($object_type_id, $object_id, true);

	if(is_fx_error($old_object)) {
		return new FX_Error(__FUNCTION__, $old_object -> get_error_message());
	}

	$tmp_res = new FX_Temp_Resource($object_type_id, $object_id);

	if(is_fx_error($tmp_res)) {
		return new FX_Error(__FUNCTION__, $tmp_res -> get_error_message());
	}

	$errors = new FX_Error();

	unset($object_array['created'], $object_array['object_id'], $object_array['object_type_id']);

	if(array_key_exists('name',$object_array)) {
		$object_array['name'] = normalize_string($object_array['name']);
	}

	$object_array['modified'] = time();
	//$object_array['user_ip'] = array_key_exists('user_ip', $object_array) ? $object_array['user_ip'] : $_SERVER['REMOTE_ADDR'];

	foreach($object_array as $field => $value) {	
		if(array_key_exists($field, $old_object)) {
			if($old_object[$field]['value'] !== $value) {
				if (!in_array(strtolower($old_object[$field]['type']), array('image', 'file'))) //TODO: need to find universal solution
				{
					// FSM Event 
					//============================================================================
					if (is_numeric($old_object[$field]['type']) && $user_instance !== false && !$user_instance['is_admin'])
					{
						if ($fsm_events = get_fsm_events($object_type_id, $field))
						{
							$event_success = false;

							foreach ($fsm_events as $event) {

								$ec = (array)json_decode($event['event_condition'], true);

								if (array_key_exists($ec['field'], $old_object) && in_array($ec['operator'], array('>','<','==','!=','<=','>='))) {
									$object_value = array_key_exists($ec['field'], $object_array) ? $object_array[$ec['field']] : $old_object[$ec['field']]['value'];
									$ec_value = validate_field_value($ec['value'], $old_object[$ec['field']]);

									if (is_fx_error($ec_value)) {
										add_log_message(__FUNCTION__.'.validate_fsm_value', $ec_value->get_error_message());
										continue;
									}

									if (!eval('if('.$object_value.$ec['operator'].$ec_value.') return true; else return false;')) {
										continue;
									}
								}

								if ($event['start_state'] == $old_object[$field]['value'] && 
									$event['end_state'] == $object_array[$field] && 
									array_intersect($event['roles'], $user_instance['roles'])) {
									$event_success = true;
									break;
								}
							}
	
							if (!$event_success) {
								return new FX_Error(__FUNCTION__, "FSM: "._('You do not have the correct permissions to perform this action'));
							}
						}
					}
					//============================================================================
					
					$field_value = validate_field_value($object_array[$field], $old_object[$field], $forced);
	
					if(is_fx_error($field_value)) {
						$errors -> add(__FUNCTION__, $field_value->get_error_message());
					}
					else {
						//$fields_to_update[] = $field."='".$field_value."'";
						$fields_to_update[$field] = $field_value;
						$fields_to_revision[$field] = $old_object[$field]['value'];
						
						if ($old_object[$field]['type'] == 'qr') {
							$qr_codes[$field] = $field_value;
						}
						
						if(!in_array($field, array(/*'user_ip',*/ 'modified')) && !$update_required) {
							$update_required = true;
						}
					}					
				}
			}
		}
	}
	
	if (!$errors -> is_empty()) {
		return $errors;
	}
	
	if ($update_required)
	{
		$result = $fx_db->update(DB_TABLE_PREFIX."object_type_".$object_type_id, $fields_to_update, array("object_id"=>$object_id));

		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('DB error occured'), $result->get_error_data());
		}
		else {
			if ($object_type_id == TYPE_ROLE) {
				clear_user_cache();
			}
		
			if ($object_type_id == TYPE_SUBSCRIPTION) {
				clear_user_cache($old_object['api_key']['value']);
			}
			
			if ($object_type_id == TYPE_QUERY) {
				clear_query_cache($object_id);
			}
			
			clear_query_cache_by_type($object_type_id);	

			if ($object_type_id == TYPE_DATA_SET) {
				$channel_id = get_object_field(TYPE_DATA_SCHEMA, (int)$old_object['schema_id']['value'], 'channel');
				if ((int)$channel_id) {
					_clear_remote_channel_cache($channel_id);
				}
			}

			foreach ($qr_codes as $field_name => $field_value) {
				$field_value = $field_value ? $field_value : get_object_permalink($object_type_id, $object_id);
				$obj_dir = CONF_UPLOADS_DIR.'/'.$object_type_id.'/'.$object_id;
				if (!is_dir($obj_dir)) mkdir($obj_dir, 0777, true);
				$file = $obj_dir.'/'.$field_name.'.png';
				QRcode::png($field_value, $file);
			}

			$revision = _add_object_revision($object_type_id, $object_id, $fields_to_revision);
			
			if(is_fx_error($revision)) {
				return $revision;
			}
			
			if(!$tmp_res -> resources) {
				return true;
			}
		}
	}

	if ($tmp_res -> resources)
	{
		$result = $tmp_res -> submit();
		if(is_fx_error($result)) {
			return $result;
		}
		
		if ($object_type_id == TYPE_ROLE) {
			clear_user_cache();
		}
		
		return true;
	}
	
	return false;
}

function rollback_object($object_type_id, $object_id, $time)
{
    global $fx_db;

	$fields_to_update = array();
	
	if (!$object_id) {
		return new FX_Error(__FUNCTION__, _('Specify correct Object ID to rollback'));
	}

	if (!$object_type_id) {
		return new FX_Error(__FUNCTION__, _('Specify correct Type ID to rollback'));
	}

	$rollback_data = get_changes_to_rollback($object_type_id, $object_id, $time);

	if(is_fx_error($rollback_data)) {
		return new FX_Error(__FUNCTION__, $rollback_data->get_error_message());
	}

	$old_object = get_object($object_type_id, $object_id, true);

	if(is_fx_error($old_object)) {
		return new FX_Error(__FUNCTION__, $old_object->get_error_message());
	}

	$errors = new FX_Error();

	$rollback_data['modified'] = time();

	foreach($rollback_data as $field => $value) {	
		if(array_key_exists($field, $old_object)) {
			if($old_object[$field]['value'] !== $value) {
				$field_value = validate_field_value($rollback_data[$field], $old_object[$field], $forced);

				if(is_fx_error($field_value)) {
					$errors -> add(__FUNCTION__, $field_value->get_error_message());
				}
				else {
					//$fields_to_update[] = $field."='".$field_value."'";
					$fields_to_update[$field] = $field_value;
				}
			}
		}
	}
	
	if(!$errors -> is_empty()) {
		return $errors;
	}
	
	if($fields_to_update)
	{
		$result = $fx_db -> update(DB_TABLE_PREFIX."object_type_".$object_type_id, $fields_to_update, array("object_id"=>$object_id));
		
		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('DB error occured').' ['.$result->get_error_data().']');
		}
		else {
			$rollback_result = _delete_revisions_after($object_type_id, $object_id, $time);

			if(is_fx_error($rollback_result)) {
				return new FX_Error(__FUNCTION__, $rollback_result->get_error_message());
			}
			
			clear_query_cache_by_type($object_type_id);
			
			return true;
		}
	}
	else {
		return false;
	}
}


function delete_object($object_type_id, $object_id)
{
    global $fx_db;

    $type_table = DB_TABLE_PREFIX."object_type_".(int)$object_type_id;

	$old_object = get_object($object_type_id, $object_id);

	if(is_fx_error($old_object)) {
		return new FX_Error(__FUNCTION__, _('Specified object does not exist'));
	}

	if ($object_type_id == TYPE_DATA_SCHEMA || $object_type_id == TYPE_DATA_SET) {

		if ($object_type_id == TYPE_DATA_SCHEMA) $res = _delete_data_schema($object_id);		
		if ($object_type_id == TYPE_DATA_SET) $res = _delete_data_set($object_id);
		
		if (is_fx_error($res)) {
			return $res;
		}
	}

	$result = $fx_db->delete(DB_TABLE_PREFIX."object_type_".$object_type_id, array('object_id'=>$object_id));

	if ($result > 0) {
		// delete temp resources (if exist)
		$tmp_res = new FX_Temp_Resource($object_type_id, $object_id);

		if ($tmp_res -> resources) {
			$tmp_res -> remove();
		}

		// delete strongly linked objects
		delete_strongly_linked_objects($object_type_id, $object_id);
		
		// delete object directory (if exist)
		full_del_dir(CONF_UPLOADS_DIR.'/'.$object_type_id.'/'.$object_id); 
		
		// delete object links (if exist)		
		delete_object_links($object_type_id, $object_id);

		// create backup if required
		$res = add_deleted_object($object_type_id, $old_object, time());

		if(is_fx_error($res)) {
			return $res;
		}
	}
	else {
		return new FX_Error(__FUNCTION__, 'No one object was deleted.');
	}

	clear_query_cache_by_type($object_type_id);

	if ($object_type_id == TYPE_QUERY) {
		clear_query_cache($object_id);
	}

	return true;
}

function delete_strongly_linked_objects($object_type_id, $object_id)
{
	$sl = get_object_strong_links($object_type_id, $object_id, true);

	if (is_fx_error($sl)) {
		add_log_message(__FUNCTION__, $sl->get_error_message());
		return false;
	}

	foreach ($sl as $l) {
		$res = delete_object($l['object_type_id'], $l['object_id']);
		if (is_fx_error($res)) {
			add_log_message(__FUNCTION__, $res->get_error_message());
		}
	}
}

function _delete_data_schema($schema_id)
{
	if (!(int)$schema_id) {
		return new FX_Error(__FUNCTION__, _('Empty Data Schema ID'));
	}

	$errors = new FX_Error();

	foreach (get_schema_types($schema_id, 'none') as $object_type_id => $type) {
		if (!$type['system']) {
			$res = delete_type($object_type_id);
			if (is_fx_error($res)) $errors -> add(__FUNCTION__, $res -> get_error_message());
		}
	}

	foreach (get_schema_enums($schema_id, false) as $enum_type_id => $enum) {
		$res = delete_enum_type($enum_type_id);
		if (is_fx_error($res)) $errors -> add(__FUNCTION__, $res -> get_error_message());
	}
	
	foreach (get_schema_metrics($schema_id, false) as $metric_id => $metric) {
		$res = delete_metric($metric_id);
		if (is_fx_error($res)) $errors -> add(__FUNCTION__, $res -> get_error_message());
	}

	foreach (get_schema_types(0, 'none') as $object_type_id => $type) {
		$system_objects = get_objects_by_type($object_type_id, $schema_id);

		if (!is_fx_error($system_objects)) {
			foreach ($system_objects as $object) {
				$res = delete_object($object_type_id, $object['object_id']);
				if (is_fx_error($res)) $errors -> add(__FUNCTION__, $res -> get_error_message());
			}
		}
		else {
			 $errors -> add(__FUNCTION__, $system_objects -> get_error_message());
		}
	}
	
	global $system_types, $fx_db;

	foreach ($system_types as $type_name => $object_type_id) {
		$result = $fx_db->delete(DB_TABLE_PREFIX."object_type_".$object_type_id, array('schema_id'=>$schema_id));
		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('Unable to delete system objects'));
		}
	}

	return $errors -> is_empty() ? true : $errors;
}

function _delete_data_set($set_id)
{
	if (!(int)$set_id) {
		return new FX_Error(__FUNCTION__, _('Empty Data Set ID'));
	}
	
	$set_object = get_object(TYPE_DATA_SET, $set_id);

	if (is_fx_error($set_object)) {
		return new FX_Error(__FUNCTION__, _('Unable to get Data Set object'));
	}

	if (!$set_object['schema_id']) {
		return new FX_Error(__FUNCTION__, _('Data Set does not belong to no one Data Schema'));		
	}
	
	$errors = new FX_Error();
	
	foreach (get_schema_types($set_object['schema_id'], "none") as $object_type_id => $type) {
		$objects = get_objects_by_type($object_type_id, $schema_id, $set_id);
		
		if(!is_fx_error($objects)) {
			foreach($objects as $object) {
				$res = delete_object($object_type_id, $object['object_id']);
				if (is_fx_error($res)) $errors -> add(__FUNCTION__, $res -> get_error_message());
			}
		}
		else {
			 $errors -> add(__FUNCTION__, $objects -> get_error_message());
		}
	}	
	
	if($errors -> is_empty()) {
		return true;
	}
	else {
		return $errors;
	}
}

/**
 * The method removes the objects that older then $older_then
 *
 * @author Artem Orlov
 *
 * @param $object_type_id
 * @param $older_then
 * @param bool $use_modified
 * @return bool|FX_Error
 */
function delete_objects_older_then($object_type_id, $older_then, $use_modified = false)
{
	global $fx_db;
	
	$type_table = DB_TABLE_PREFIX."object_type_".(int)$object_type_id; // table of current type
	
	$where = array('created <' => $older_then);
	
	if($use_modified) {
		$where = array('modified <' => $older_then);
	}
	
	if ($object_type_id == TYPE_DATA_SCHEMA || $object_type_id == TYPE_DATA_SET) {
		return new FX_Error(__FUNCTION_, ('Specified object does not exist'));
	}
	
	$fx_db->select($type_table)->where($where);
	$fx_db->select_exec();
	$older_objects = $fx_db->get_all();
	
	if (isset($older_objects) && is_array($older_objects) && count($older_objects)) {
		foreach ($older_objects as $old_object) {
			
	 		if ($old_object != NULL) {
	
	  			$object_id = $old_object->object_id;
	  			$result = $fx_db->delete($type_table, $object_id);
	
				if ($result > 0) {
					// delete temp resources (if exist)
					$tmp_res = new FX_Temp_Resource($object_type_id);
				
					if ($tmp_res->resources) {
						$tmp_res->remove();
					}
	
				   // delete strongly linked objects
				   delete_strongly_linked_objects($object_type_id, $object_id);
				
				   // delete object directory (if exist)
				   full_del_dir(CONF_UPLOADS_DIR . '/' . $object_type_id . '/' . $object_id);
				
				   // delete object links (if exist)
				   delete_object_links($object_type_id, $object_id);
				
				   // create backup if required
				   $res = add_deleted_object($object_type_id, $old_object, time());
		
					if (is_fx_error($res)) {
						return $res;
					}
				}
				else {
					return new FX_Error(__FUNCTION__, _('No one object was deleted'));
				}
	
				clear_query_cache_by_type($object_type_id);
	
				if ($object_type_id == TYPE_QUERY) {
					clear_query_cache($object_id);
				}
	 		}
		} // end foreach()
	}
	return true;
}

function object_exists($object_type_id, $object_key, $set_id = '')
{
    global $fx_db;

    $type_table = DB_TABLE_PREFIX."object_type_".(int)$object_type_id;

    if (is_numeric($object_key)) {
		$fx_db->select($type_table, 'object_id')->where(array('object_id'=>$object_key))->limit(1);
    }
    else {
		if (!strlen((string)$set_id)) {
			return false;
		}
		$fx_db->select($type_table, 'object_id')->where(array('name'=>$object_key, 'set_id'=>$set_id))->limit(1);
    }

	$fx_db->select_exec();
	$row = $fx_db->get();

	return $row ? $row['object_id'] : false;
}

function get_object_number($object_type_id)
{
    global $fx_db;

	$count = $fx_db->select(DB_TABLE_PREFIX."object_type_".(int)$object_type_id, 'object_id')->select_exec();

	return intval($count);
}

function validate_field_value_by_type($object_type_id, $field_name, $field_value, $forced = false)
{
	$options = get_custom_type_field($object_type_id, $field_name);

	if (is_fx_error($options)) {
		return $options;
	}

	return validate_field_value($field_value, $options, $forced = false);
}

function validate_field_value($value, $options, $forced = false)
{
	if (!$options || !is_array($options)) {
		return new FX_Error(__FUNCTION__, _('Empty field options or wrong options format'));
	}
	
	if (is_array($value)) {
		$value = json_encode($value);
	}

	$caption = $options['caption'] ? $options['caption'] : $options['name'];

	if (!strlen((string)$value) && $options['default_value']) {
		$value = $options['default_value']; 
	}

	if (!strlen((string)$value) && $options['mandatory'] && !in_array($type, array('IMAGE', 'FILE'))) {
		return new FX_Error(__FUNCTION__, _('Field must be filled').' ['.$caption.']');
	}

	$type = strtoupper($options['type']);

	if($options['length']) {
		if (in_array($type, array('INT', 'DATETIME', 'TIME', 'DATE'))) {
			$length = (int)(strlen((string)decbin('12'))/8)+1;
		}
		elseif (!is_numeric($options['type'])) {
			$length = strlen($value);
		}
	
		if ($length > $options['length']) {
			return new FX_Error(__FUNCTION__, _('Value is too big').' ['.$caption.']');
		}
	}

	if(strlen((string)$value))
	{
		switch ($type)
		{
			case 'DATETIME':
			case 'TIME':
			case 'DATE':
				if (!is_numeric($value)) {
					$value = $value == '' ? 0 : strtotime($value);
					if ($value === false) {
						if ($forced) {
							$value = 0;
						}
						else {
							return new FX_Error(__FUNCTION__, _('Date(time) value required').' ['.$caption.']');
						}
					}
				}
			break;
			case 'FLOAT':
				if (!$forced && !is_numeric($value)) {
					return new FX_Error(__FUNCTION__, _('Float value required').' ['.$caption.']');
				}
				$value = floatval($value);
			break;
			case 'INT':
				if (!$forced && (intval($value) != $value)) {
					return new FX_Error(__FUNCTION__, _('Integer value required').' ['.$caption.']');
				}
				$value = (int)$value;
			break;
			case 'PASSWORD':
				$value = generate_password_hash($value);//md5($value);
			break;
			case 'URL':
				if (!$forced) {
					if(!is_url($value)) return new FX_Error(__FUNCTION__, _('URL required').' ['.$caption.']');
				}
			break;
			case 'IP':
				if (!$forced) {
					if(!is_ip($value)) return new FX_Error(__FUNCTION__, _('IP address required').' ['.$caption.']');
				}
			break;
			case 'EMAIL':
				if (!$forced) {
					if(!is_email($value)) return new FX_Error(__FUNCTION__, _('E-mail address required').' ['.$caption.']');
				}
			break;
/*			case 'QR':
			break;*/
			case is_numeric($options['type']):
			
				$enum_values = get_enum_values($options['type']);
				
				if(!in_array($value,$enum_values)) {
					if($forced) {
						$value = $enum_values[0];
					}
					else {
						return new FX_Error(__FUNCTION__, _('Invalid Enum value').' ['.$caption.']');
					}
				}
			break;
			default:
				$value = (string)addslashes($value);
		}
	}
	else {
		if (in_array($type, array('INT', 'FLOAT', 'DATETIME', 'TIME', 'DATE'))) {
			$value = 0;
		}
		else {
			$value = '';
		}
	}

	return $value;
}

function get_objects_by_type($type, $schema_id = NULL, $set_id = '')
{
    global $fx_db;

	$object_type_id = $type && !is_numeric($type) ? get_type_id_by_name($schema_id, $type) : (int)$type;

	if (!$object_type_id) {
		return new FX_Error(__FUNCTION__, _('Empty Object Type ID'));
	}

	if (is_system_type($object_type_id) && $schema_id !== NULL) {
		$fx_db->select(DB_TABLE_PREFIX."object_type_".$object_type_id, array('*'))
            ->where(array('schema_id'=>$schema_id))
            ->order('display_name');
	}
	else {
		$fx_db->select(DB_TABLE_PREFIX."object_type_".$object_type_id, array('*'));
		
		if ($set_id && is_numeric($set_id)) {
			$fx_db->where(array('set_id'=>$set_id));
		}

		$fx_db->order('display_name');
	}

	if (!is_fx_error($fx_db->select_exec())) {
		if (!$objects = $fx_db->get_all()) {
			return array();
		}
		else {
			$result = array();
			foreach ($objects as $object) {
				$result[$object['object_id']] = array('object_type_id' => $object_type_id) + $object;
			}
			return $result;
		}
	}
	else  {
		return new FX_Error(__FUNCTION__, _('DB Error'));			
	}
}

function get_field_control($options, $read_only = false, $prefix = 'field_')
{
	if(!is_array($options)) return false;

	if ($options['caption']) $f_caption = $options['caption'];
	elseif ($options['name']) $f_caption = $options['name'];
	else $f_caption = '<font color="#aaaaaa">Unnamed</font>';

	if (!$read_only)
	{
		$f_length = $options['length'];
		$f_mandatory = $options['mandatory'] ? '<div class="star"></div>' : '';	
		$f_description = $options['description'] != '' ? '&nbsp;<div class="hint" title="'.$options['description'].'"></div>' : '';
		$ctrl_name = $options['name'];
		$ctrl_id = normalize_string($prefix).$options['name'];
        $ctrl_class = $options['class'];
	}
	
	$f_type = strtoupper($options['type']);
	$f_value = strlen((string)$options['value']) ? $options['value'] : $options['default_value'];

	switch ($f_type)
	{
		case 'DATETIME':
			$f_value = $f_value ? date(FX_DATE_FORMAT.' '.FX_TIME_FORMAT, $f_value) : '';
			$control = $read_only ? $f_value : '<input class="_date '.$ctrl_class.'" type="text" id="'.$ctrl_id.'" name="'.$ctrl_name.'" value="'.$f_value.'">';
		break;
		case 'DATE':
			$f_value = $f_value ? date(FX_DATE_FORMAT, $f_value) : '';
			$control = $read_only ? $f_value : '<input class="_date '.$ctrl_class.'" type="text" id="'.$ctrl_id.'" name="'.$ctrl_name.'" value="'.$f_value.'">';
		break;
		case 'TIME':
			$f_value = $f_value ? date(FX_TIME_FORMAT, $f_value) : '';
			$control = $read_only ? $f_value : '<input class="'.$ctrl_class.'" type="text" id="'.$ctrl_id.'" name="'.$ctrl_name.'" value="'.$f_value.'">';
		break;			
		case 'TEXT':
			$control = $read_only ? $f_value : '<textarea class="'.$ctrl_class.'" id="'.$ctrl_id.'" name="'.$ctrl_name.'" cols="50" rows="3">'.$f_value.'</textarea>';
		break;
		case 'HTML':
			$control = $read_only ? $f_value : '<textarea class="HTMLEditor '.$ctrl_class.'" id="'.$ctrl_id.'" name="'.$ctrl_name.'" cols="30" rows="3">'.$f_value.'</textarea>';
		break;
		case 'PASSWORD':
			$control = $read_only ? '******' : '<input type="password" class="'.$ctrl_class.'" id="'.$ctrl_id.'" name="'.$ctrl_name.'" value="">';
			//$control = $read_only ? preg_replace('/(.)/', '*', $f_value) : '<input type="password" id="'.$ctrl_id.'" name="'.$ctrl_name.'" value="'.$f_value.'">';
		break;
		case 'IMAGE':
		case 'FILE':
			if($read_only) {
				$control = '<a href="'.CONF_UPLOADS_DIR.'/'.$options['object_type_id'].'/'.$options['object_id'].'/'.$f_value.'">'.$f_value.'</a>';
			} else {
				$control = '<iframe class="upload-conteiner" style="margin:0; padding:0;" frameborder="0" vspace="0" hspace="0" scrolling="no" src="'.CONF_AJAX_URL.'upload_file.php?type='.$options['object_type_id'].'&object='.$options['object_id'].'&field='.$options['name'].'"></iframe>';	
			}									
		break;
		case is_numeric($f_type):
			$enum = isset($options['enum']) ? $options['enum'] : get_enum_fields($f_type);

			if(is_fx_error($enum)) {
				$control = '<font color="#FF0000">'.$enum->get_error_message().'</font>';
			}
			else {
				$control = $read_only ? $enum[$f_value] : '
				<select class="'.$ctrl_class.'" id="'.$ctrl_id.'" name="'.$ctrl_name.'" style="width:150px">
					<option value="">Please select</option>
					'.show_select_options($enum, '', '', $f_value, false).'
				</select>';
			}
		break;
		case 'FLOAT':
		case 'INT':		
		case 'VARCHAR':
		case 'URL':
		case 'IP':
		case 'EMAIL':
			$control = $read_only ? $f_value : '<input type="text" class="'.$ctrl_class.'" id="'.$ctrl_id.'" name="'.$ctrl_name.'" value="'.$f_value.'">';
		break;
		case 'QR':
			$control = $options['object_id'] ? '<img src="'.CONF_UPLOADS_URL.$options['object_type_id'].'/'.$options['object_id'].'/'.$options['name'].'.png"/></br>' : '';
			$control .= $read_only ? $f_value : '<input type="text" class="'.$ctrl_class.'" id="'.$ctrl_id.'" name="'.$ctrl_name.'" value="'.$f_value.'" size="50"></br><font size="-1"><i><sup>*</sup> leave blank to generate object permalink</i></font>';
		break;
		default:
			$control = '<font color="#FF0000">Unknown field type <b>'.$f_type.'</b></font>';
	}
	
	if ($f_type == 'FLOAT') {
		$f_metric = get_metric($options['metric'], true);
		
		if (!is_fx_error($f_metric)) {
			//$factor = $f_metric['units'][$options['unit']]['factor'];
			$name = $f_metric['units'][$options['unit']]['name'];
			$options = '';
			
			foreach ($f_metric['units'] as $unit) {
				//$s = $factor == $unit['factor'] ? ' selected="selected"' : '';
				$s = $name == $unit['name'] ? ' selected="selected"' : '';
				$options .= '<option value="'.$unit['unit_id'].'" data-factor="'.$unit['factor'].'" data-decimals="'.$unit['decimals'].'"'.$s.'>'.$unit['name'].'</option>';
			}
			$is_currency = $f_metric['is_currency'] ? ' data-currency="1" ' : ' data-currency="0" '; 
			$control .= '<select'.$is_currency.'onfocus="calculate_field_value(this, \''.$ctrl_id.'\')">'.$options.'</select>';
		}
	}

	$label = $read_only ? $f_caption.':' : $f_mandatory.'<label for="'.$ctrl_id.'">'.$f_caption.':</label>';
	$control = $read_only ? $control : $control.$f_description;

	return array('label' => $label, 'control' => $control);
}


function get_object_permalink($object_type_id, $object_id)
{
	//return object_exists($object_type_id, $object_id) ? URL."object/$object_type_id/$object_id" : false;
	return object_exists($object_type_id, $object_id) ? "object_type_id=$object_type_id&object_id=$object_id" : false;
}

function get_object_field_img_url($object_type_id, $object_id, $field_name, $size = '')
{
	$field_value = get_object_field($object_type_id, $object_id, $field_name);
	
	if (is_fx_error($field_value) || !$field_value) {
		return false;
	}
	
	if ($size && in_array($size, array('thumb', 'medium', 'small', 'large'))) {
		$size = $size.'_';
	}
	else {
		$size = '';
	}
	
	if (file_exists(CONF_UPLOADS_DIR.'/'.$object_type_id.'/'.$object_id.'/'.$size.$field_value)) {
		return CONF_UPLOADS_URL.$object_type_id.'/'.$object_id.'/'.$size.$field_value;
	}
	else {
		return false;
	}
}