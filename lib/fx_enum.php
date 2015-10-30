<?php

function add_enum_type($enum_array)
{
	global $fx_db;
	$errors = new FX_Error();

	if (!strlen((string)$enum_array['name'])) {
		$errors -> add(__FUNCTION__, _('Please enter name for new enum type'));
	}
	
	$enum_fields = array();

	if (array_key_exists('fields', $enum_array) && $enum_array['fields']) {
		foreach ($enum_array['fields'] as $label) {
			if (!strlen((string)(is_array($label) ? $label['label'] : $label))) {
				$errors -> add(__FUNCTION__, _('Please fill all enum labels'));
			}
		}
	}

	if (!$errors -> is_empty()) {
		return $errors;
	}

	$system = array_key_exists('system', $enum_array) ? intval($enum_array['system']) : 0;
	$schema_id = array_key_exists('schema_id', $enum_array) && !$system ? intval($enum_array['schema_id']) : 0;

	$fx_db->select(DB_TABLE_PREFIX.'enum_type_tbl', 'enum_type_id')->where(array('name like'=>$enum_array['name'].'%', 'schema_id'=>$schema_id))->select_exec();
	$count = $fx_db->get_row_count();

	$enum_base_fields = array(
		'name' => $count ? $enum_array['name'].' '.$count : $enum_array['name'], 
		'system' => $system,
		'schema_id' => $schema_id
	);
	
    $enum_type_id = $fx_db->insert(DB_TABLE_PREFIX.'enum_type_tbl', $enum_base_fields);

	if (is_numeric($enum_type_id))
	{
		if (array_key_exists('fields', $enum_array) && $enum_array['fields'])
		{	
			$enum_fields = array();
			$order = 0;
			
			foreach($enum_array['fields'] as $value => $label) {
				$enum_fields[] = array(
					'enum_type_id' => $enum_type_id, 
					'value' => $value,
					'label' => is_array($label) ? $label['label'] : $label,
					'color' => is_array($label) && $label['color'] ? $label['color'] : '#ffffff',
					'opacity' => is_array($label) && $label['opacity'] ? $label['opacity'] : 0,
					'sort_order' => $order++
				);
			}
			
			$result = $fx_db->insert(DB_TABLE_PREFIX.'enum_field_tbl', $enum_fields);
			
			if (is_fx_error($result)) {
				return $result;
			}
		}

		return $enum_type_id;	
	}
	else {
		return new FX_Error(__FUNCTION__, _('Unable to add new enum type'));
	}
}

function update_enum_type($enum_array)
{
	global $fx_db;
	$errors = new FX_Error();

	if (!array_key_exists('enum_type_id', $enum_array)) {
		return new FX_Error(__FUNCTION__, _('Specify the correct Enum Type ID'));
	}

	$enum_type_id = intval($enum_array['enum_type_id']);
	$old_enum = get_enum_type($enum_type_id, true);
	
	$old_new_values = array();

	if (is_fx_error($old_enum)) {
		return $old_enum -> get_error_message();
	}

	$delete_old_fields = $update_fields = false;

	if (array_key_exists('fields', $enum_array) && $enum_array['fields'] !== $old_enum['fields']) {
		if ($old_enum['fields']){
			$delete_old_fields = true;
		}
		foreach ($enum_array['fields'] as $label) {
			if (!strlen((string)(is_array($label) ? $label['label'] : $label))) {
				$errors -> add(__FUNCTION__, _('Please fill all enum labels'));
			}
		}
		$update_fields = true;
	}

	if (array_key_exists('name', $enum_array) && $enum_array['name'] != $old_enum['name']) {
		if(!strlen((string)$enum_array['name'])) {
			return new FX_Error(__FUNCTION__, _('Please enter name for enum type'));
		}

		$fx_db->select(DB_TABLE_PREFIX.'enum_type_tbl', 'enum_type_id')->where(array('name like'=>$enum_array['name'].'%', 'schema_id'=>$old_enum['schema_id']))->select_exec();
		$count = $fx_db->get_row_count();

		$name = $count ? $enum_array['name'].' '.$count : $enum_array['name'];

		$result = $fx_db->update(DB_TABLE_PREFIX.'enum_type_tbl', array('name'=>$name), array('enum_type_id'=>$enum_type_id));
		
		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('Unable to update enum type'));
		}			
	}

	if ($delete_old_fields) {
		$result = $fx_db->delete(DB_TABLE_PREFIX.'enum_field_tbl', array('enum_type_id'=>$enum_type_id));
		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('Unable to delete old enum type values'));	
		}			
	}

	if ($update_fields)
	{	
		$enum_fields = array();
		$order = 0;
		
		foreach($enum_array['fields'] as $value => $label) {
            $result = $fx_db->insert(DB_TABLE_PREFIX.'enum_field_tbl', array(
                'enum_type_id' => $enum_type_id,
                'value' => $value,
                'label' => is_array($label) ? $label['label'] : $label,
                'color' => is_array($label) && $label['color'] ? $label['color'] : '#ffffff',
                'opacity' => is_array($label) && $label['opacity'] ? $label['opacity'] : 0,
                'sort_order' => $order++
            ));

			//_update_enum_value_in_type($enum_type_id, $values, $default);

            if (is_fx_error($result)) {
                return $result;
            }
		}
	}

	clear_api_cache();
	clear_query_cache();

	return true;
}

function update_enum_type_NEW($enum)
{
	global $fx_db;
	$errors = new FX_Error();

	if (!array_key_exists('enum_type_id', $enum)) {
		return new FX_Error(__FUNCTION__, _('Specify the correct Enum Type ID'));
	}

	$enum_type_id = intval($enum['enum_type_id']);
	$old_enum = get_enum_type($enum_type_id, true);

	if (is_fx_error($old_enum)) {
		return $old_enum -> get_error_message();
	}
	
	$fields_to_delete = $fields_to_update = $fields_to_add = array();

	if (array_key_exists('fields', $enum)) {
		$new_order = 0;
		foreach ($enum['fields'] as $value => $label)
		{	
			if (!is_array($label)) {
				$field = array('value' => $value, 'label' => $label);
			}
			else {
				$field = $label;
			}
			
			$field['sort_order'] = $new_order++;
			
			if (isset($old_enum['fields'][$field['enum_field_id']])) {
				if ($old_enum['fields'][$field['enum_field_id']] != $field) {
					$fields_to_update[] = $field;
				}
				unset($old_enum['fields'][$field['enum_field_id']]);
			}
			else {
				$fields_to_add[] = $field;
			}
		}
	}

	$fields_to_delete = array_keys((array)$old_enum['fields']);

	$enum_options = array();

	if (array_key_exists('name', $enum) && $enum['name'] != $old_enum['name']) {
		
		if(!strlen((string)$enum['name'])) {
			return new FX_Error(__FUNCTION__, _('Please enter name for Enum type'));
		}
		
		$fx_db->select(DB_TABLE_PREFIX.'enum_type_tbl', 'enum_type_id')->where(array('name like'=>$enum['name'].'%', 'schema_id'=>$old_enum['schema_id']))->select_exec();
		$count = $fx_db->get_row_count();
		$name = $count ? $enum['name'].' '.$count : $enum['name'];
		$enum_options['name'] = $count ? $enum['name'].' '.$count : $enum['name'];
	}

	if ($enum_options) {
		$result = $fx_db->update(DB_TABLE_PREFIX.'enum_type_tbl', $enum_options, array('enum_type_id'=>$enum_type_id));
		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('Unable to update Enum type'));
		}			
	}

	if (count($fields_to_add)) {
		foreach($fields_to_add as $field) {
			$fields[] = array(
				'enum_type_id' => $enum_type_id, 
				'value' => $field['value'],
				'label' => $field['label'],
				'color' => $field['color'] ? $field['color'] : '#ffffff',
				'opacity' => $field['opacity'] ? $field['opacity'] : 0,
				'sort_order' => $field['sort_order']				
			);
		}
		
		$result = $fx_db->insert(DB_TABLE_PREFIX.'enum_field_tbl', $fields);
		
		if (is_fx_error($result)) {
			return $result;
		}
	}
	
	if (count($fields_to_update)) {
		foreach($fields_to_update as $field) {
			$enum_field_id = $field['enum_field_id'];
			$field = array(				
				'enum_type_id' => $enum_type_id, 
				'value' => $field['value'],
				'label' => $field['label'],
				'color' => $field['color'] ? $field['color'] : '#ffffff',
				'opacity' => $field['opacity'] ? $field['opacity'] : 0,
				'sort_order' => $field['sort_order']	
			);
			
			$result = $fx_db->update(DB_TABLE_PREFIX.'enum_field_tbl', $field, array('enum_field_id'=>$field['enum_field_id']));
			
			if (is_fx_error($result)) {
				return $result;
			}
		}
	}

	if (count($fields_to_delete)) {
		$result = $fx_db->delete(DB_TABLE_PREFIX.'enum_field_tbl', array('enum_field_id in'=>$fields_to_delete));
		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('Unable to delete Enum fields'));	
		}	
	}

	clear_api_cache();
	clear_query_cache();

	return true;
}

function _update_enum_value_in_type($enum_id, $values, $default)
{
	$enum_id = intval($enum_id);
	
	if (!$enum_id) {
		return false;
	}

	global $fx_db;
	
	$result = $fx_db->select(DB_TABLE_PREFIX.'field_type_tbl', array('object_type_id', 'name'))->where(array('type'=>"$enum_id"))->select_exec()->get_all();
	$types = $fx_db->get_all();
	
	foreach($types as $type) {
		foreach ($values as $old_value => $new_value) {
			
			if (!$new_value) {
				$new_value = $default;
			}
			
			$result = $fx_db->update(DB_TABLE_PREFIX.'object_type_'.$type['object_type_id'], array('name'=>$new_value), array('enum_type_id'=>$old_value));
	
			if (is_fx_error($result)) {
				return new FX_Error(__FUNCTION__, _('Unable to update enum type'));
			}			
		}
	}

	return true;
}

function enum_type_exists($enum_type_id)
{
	global $fx_db;
	
	$fx_db->select(DB_TABLE_PREFIX.'enum_type_tbl', 'enum_type_id')->where(array('enum_type_id'=>$enum_type_id))->limit(1)->select_exec();
	$row = $fx_db->get();
	return $row ? $row['enum_type_id'] : false;
}

function get_enum_type($enum_type_id, $with_fields = true)
{
	global $fx_db;
	
	$fx_db->select(DB_TABLE_PREFIX.'enum_type_tbl')->where(array('enum_type_id'=>$enum_type_id))->limit(1)->select_exec();

	if ($enum = $fx_db->get()) {
		if ($with_fields) {
			$enum['fields'] = get_enum_fields($enum_type_id, true);
		}
		return $enum;
	}
	else {
		return new FX_Error(__FUNCTION__, _('Specified Enum type does not exist'));
	}
}

function delete_enum_type($enum_type_id)
{
	global $fx_db;

	$result = $fx_db->delete(DB_TABLE_PREFIX.'enum_field_tbl', array('enum_type_id'=>$enum_type_id));

	if (is_fx_error($result)) {
		return new FX_Error(__FUNCTION__, _('Unable to delete enum fields'));
	}

	$result = $fx_db->delete(DB_TABLE_PREFIX.'enum_type_tbl', array('enum_type_id'=>$enum_type_id));

	if (is_fx_error($result)) {
		return new FX_Error(__FUNCTION__, _('Unable to delete enum type'));
	}

	$fx_db->update(DB_TABLE_PREFIX.'field_type_tbl', array('type'=>'varchar', 'length'=>255), array('type'=>$enum_type_id));

	clear_api_cache();
	clear_query_cache();

	return true;
}

function get_enum_fields($enum_type_id, $details = false)
{
	global $fx_db;
	
	$fx_db->
		select(DB_TABLE_PREFIX.'enum_field_tbl', array('enum_field_id', 'label', 'value', 'color', 'opacity', 'sort_order'))->
		where(array('enum_type_id'=>$enum_type_id))->
		order('sort_order');

	if (!is_fx_error($fx_db->select_exec())) {
		if (!$fields = $fx_db->get_all()) {
			return array();
		}
		else {
			$result = array();
			if ($details) {
				foreach($fields as $key => $value) {
					$result[$value['value']] = array(
						'enum_field_id' => $value['enum_field_id'],
						'label' => $value['label'],
						'color' => $value['color'],
						'opacity' => $value['opacity']);
				}
			}
			else {
				foreach($fields as $key => $value) {
					$result[$value['value']] = $value['label'];
				}
			}
			return $result;
		}
	}
	else {
		return new FX_Error(__FUNCTION__, _('Specified Enum type does not exist'));			
	}
}

function enum_value_exists($enum_type_id, $value)
{
	global $fx_db;

	$fx_db->select(DB_TABLE_PREFIX.'enum_field_tbl', 'label')->where(array('enum_type_id'=>$enum_type_id, 'value'=>$value))->select_exec();
	
	if ($row = $fx_db->get()) {
		return $row['label'];
	}
	else {
		return false;
	}
}

function get_enum_label($enum_type_id, $value)
{
	return enum_value_exists($enum_type_id, $value);
}

function get_enum_values($enum_type_id)
{
	global $fx_db;
	
	$fx_db->select(DB_TABLE_PREFIX.'enum_field_tbl', 'value')->where(array('enum_type_id'=>$enum_type_id))->order('sort_order');

	if (!is_fx_error($fx_db->select_exec())) {
		$result = array();
		if($values = $fx_db->get_all()) {
			$result = array();
			foreach($values as $key => $value) {
				$result[] = $value['value'];
			}
		}
		return $result;
	}
	else {
		return new FX_Error(__FUNCTION__, _('Specified Enum type does not exist'));			
	}
}	

function get_schema_enums($schema_id, $show_system = true)
{
	global $fx_db;

    $where = array(
        'schema_id'=>$schema_id
    );
	
	if ($show_system) {
		$where['operator'] = 'OR';
		$where['system'] = 1;
	}
	else {
		$where['system'] = 0;
	}
	
	$fx_db->select(DB_TABLE_PREFIX.'enum_type_tbl', array('enum_type_id', 'name', 'system'))->where($where)->order('name');

	if (!is_fx_error($fx_db->select_exec())) {
		$result = array();
		if($enums = $fx_db->get_all()) {
			foreach($enums as $enum) {
				$result[$enum['enum_type_id']] = array('name' => $enum['name'], 'system' => $enum['system']);
			}
		}
		return $result;
	}
	else {
		return new FX_Error(__FUNCTION__, _('Unable to get schema enum'));			
	}
}