<?php

function add_metric($metric)
{
	global $fx_db;
	$errors = new FX_Error();

	if (!strlen((string)$metric['name'])) {
		$errors -> add(__FUNCTION__, _('Please enter name for new metric'));
	}

	$system = array_key_exists('system', $metric) ? intval($metric['system']) : 0;
	$schema_id = array_key_exists('schema_id', $metric) && !$system ? intval($metric['schema_id']) : 0;

	if (!$system && !$schema_id) {
		return new FX_Error(__FUNCTION__, _('Please set Data Schema ID for non-system metric'));
	}
	
	$units = array();

	if (array_key_exists('units', $metric) && $metric['units']) {
		foreach ($metric['units'] as $unit) {
			if (!strlen($unit['name']) || !$unit['factor']) {
				$errors -> add(__FUNCTION__, _('Please specify options for all units'));
			}
		}
	}

	if (!$errors -> is_empty()) {
		return $errors;
	}

	$fx_db->select(DB_TABLE_PREFIX.'metric_tbl', 'metric_id')->where(array('name like'=>$metric['name'].'%', 'schema_id'=>$schema_id))->select_exec();
	$count = $fx_db->get_row_count();

	$metric_fields = array(
		'name' => $count ? $metric['name'].' '.$count : $metric['name'], 
		'system' => $system,
		'schema_id' => $schema_id,
		'is_currency' => $metric['is_currency'] ? 1 : 0,
		'description' => substr($metric['description'], 0, 255)
	);
	
    $metric_id = $fx_db->insert(DB_TABLE_PREFIX.'metric_tbl', $metric_fields);

	if (is_numeric($metric_id))
	{
		if (array_key_exists('units', $metric) && $metric['units'])
		{	
			$units = array();
			$order = 0;
			
			foreach($metric['units'] as $unit) {
				$units[] = array(
					'metric_id' => $metric_id, 
					'name' => $unit['name'],
					'factor' => $unit['factor'] ? $unit['factor'] : 0,
					'decimals' => intval($unit['decimals']),
					'sort_order' => $order++
				);
			}
			
			$result = $fx_db->insert(DB_TABLE_PREFIX.'unit_tbl', $units);
			
			if (is_fx_error($result)) {
				return $result;
			}
		}

		return $metric_id;	
	}
	else {
		return new FX_Error(__FUNCTION__, _('Unable to add metric'));
	}
}

function update_metric($metric)
{
	global $fx_db;
	$errors = new FX_Error();

	if (!array_key_exists('metric_id', $metric)) {
		return new FX_Error(__FUNCTION__, _('Specify the correct Metric ID'));
	}

	$metric_id = intval($metric['metric_id']);
	$old_metric = get_metric($metric_id, true);

	if (is_fx_error($old_metric)) {
		return $old_metric -> get_error_message();
	}
	
	$units_to_delete = $units_to_update = $units_to_add = array();

	if (array_key_exists('units', $metric) && $metric['units'] !== $old_metric['units']) {
		$new_order = 0;
		foreach ($metric['units'] as $unit) {
			$unit['sort_order'] = $new_order++;
			if (isset($old_metric['units'][$unit['unit_id']])) {
				if ($old_metric['units'][$unit['unit_id']] != $unit) {
					$units_to_update[] = $unit;
				}
				unset($old_metric['units'][$unit['unit_id']]);
			}
			else {
				$units_to_add[] = $unit;
			}
		}
	}
	
	$units_to_delete = array_keys((array)$old_metric['units']);
	
	$metric_fields = array();

	if (array_key_exists('name', $metric) && $metric['name'] != $old_metric['name']) {
		if(!strlen((string)$metric['name'])) {
			return new FX_Error(__FUNCTION__, _('Please enter name for metric'));
		}
		$fx_db->select(DB_TABLE_PREFIX.'metric_tbl', 'metric_id')->where(array('name like'=>$metric['name'].'%', 'schema_id'=>$old_metric['schema_id']))->select_exec();
		$count = $fx_db->get_row_count();
		$name = $count ? $metric['name'].' '.$count : $metric['name'];
		$metric_fields['name'] = $count ? $metric['name'].' '.$count : $metric['name'];
	}
	
	if (array_key_exists('description', $metric) && $metric['description'] != $old_metric['description']) {
		$metric_fields['description'] = substr($metric['description'], 0, 255);
	}

	if ($metric['is_currency'] != $old_metric['is_currency']) {
		$metric_fields['is_currency'] = $metric['is_currency'] ? 1 : 0;
	}

	if ($metric_fields) {
		$result = $fx_db->update(DB_TABLE_PREFIX.'metric_tbl', $metric_fields, array('metric_id'=>$metric_id));
		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('Unable to update metric'));
		}			
	}

	if (count($units_to_add)) {
		foreach($units_to_add as $unit) {
			$units[] = array(
				'metric_id' => $metric_id, 
				'name' => $unit['name'],
				'factor' => $unit['factor'] ? $unit['factor'] : 0,
				'decimals' => intval($unit['decimals']),
				'sort_order' => $unit['sort_order']
			);
		}
		
		$result = $fx_db->insert(DB_TABLE_PREFIX.'unit_tbl', $units);
		
		if (is_fx_error($result)) {
			return $result;
		}
	}
	
	if (count($units_to_update)) {
		foreach($units_to_update as $unit) {
			$unit_id = $unit['unit_id'];
			$unit = array(
				'metric_id' => $metric_id, 
				'name' => $unit['name'],
				'factor' => $unit['factor'] ? $unit['factor'] : 0,
				'decimals' => intval($unit['decimals']),
				'sort_order' => $unit['sort_order']
			);
			
			$result = $fx_db->update(DB_TABLE_PREFIX.'unit_tbl', $unit, array('unit_id'=>$unit_id));
			
			if (is_fx_error($result)) {
				return $result;
			}
		}
	}

	if (count($units_to_delete)) {
		$result = $fx_db->delete(DB_TABLE_PREFIX.'unit_tbl', array('unit_id in'=>$units_to_delete));
		if (is_fx_error($result)) {
			return new FX_Error(__FUNCTION__, _('Unable to delete units'));	
		}	
	}

	return true;
}


function metric_exists($metric_id)
{
	global $fx_db;
	
	$fx_db->select(DB_TABLE_PREFIX.'metric_tbl', 'metric_id')->where(array('metric_id'=>$metric_id))->limit(1)->select_exec();
	$row = $fx_db->get();
	return $row ? $row['metric_id'] : false;
}

function get_metric($metric_id, $with_units = true)
{
	global $fx_db;
	
	$fx_db->select(DB_TABLE_PREFIX.'metric_tbl')->where(array('metric_id'=>$metric_id))->limit(1)->select_exec();

	if ($metric = $fx_db->get()) {
		if ($with_units) {
			$metric['units'] = get_metric_units($metric_id);
		}
		
		return $metric;
	}
	else {
		return new FX_Error(__FUNCTION__, _('Specified Metric does not exist'));
	}
}

function delete_metric($metric_id)
{
	global $fx_db;

	$result = $fx_db->delete(DB_TABLE_PREFIX.'unit_tbl', array('metric_id'=>$metricid));

	if (is_fx_error($result)) {
		return new FX_Error(__FUNCTION__, _('Unable to delete metric units'));
	}

	$result = $fx_db->delete(DB_TABLE_PREFIX.'metric_tbl', array('metric_id'=>$metric_id));

	if (is_fx_error($result)) {
		return new FX_Error(__FUNCTION__, _('Unable to delete metric'));
	}

	return true;
}

function get_metric_units($metric_id)
{
	global $fx_db;
	
	$fx_db
        ->select(DB_TABLE_PREFIX.'unit_tbl', array('unit_id', 'name', 'factor', 'decimals', 'sort_order'))
        ->where(array('metric_id'=>$metric_id))
        ->order('sort_order');

	if (!is_fx_error($fx_db->select_exec())) {
		if (!$units = $fx_db->get_all()) {
			return array();
		}
		else {
			$result = array();
			foreach($units as $unit) {
				$result[$unit['unit_id']] = array(
					'unit_id' => $unit['unit_id'], 
					'name' => $unit['name'], 
					'factor' => $unit['factor'], 
					'decimals' => $unit['decimals'],
					'sort_order' => $unit['sort_order']);
			}
			return $result;
		}
	}
	else {
		return new FX_Error(__FUNCTION__, _('Specified Metric does not exist'));
	}
}

function unit_exists($metric_id, $unit_id)
{
	global $fx_db;
	$fx_db->select(DB_TABLE_PREFIX.'unit_tbl', 'unit_id')->where(array('metric_id'=>$metric_id, 'unit_id'=>$unit_id))->limit(1)->select_exec();
	return $fx_db->get() ? true : false;
}

function get_unit($metric_id, $unit_id)
{
	global $fx_db;

	$fx_db->select(DB_TABLE_PREFIX.'unit_tbl', array('name', 'factor', 'decimals'))->where(array('metric_id'=>$metric_id, 'unit_id'=>$unit_id))->limit(1)->select_exec();
	
	if ($row = $fx_db->get()) {
		return $row;
	}
	else {
		return false;
	}
}

function get_schema_metrics($schema_id, $show_system = true)
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

	$fx_db->select(DB_TABLE_PREFIX.'metric_tbl', array('metric_id', 'name', 'system', 'description', 'is_currency'))->where($where)->order('name');

	if (!is_fx_error($fx_db->select_exec())) {
		$result = array();
		foreach($fx_db->get_all() as $metric) {
			$result[$metric['metric_id']] = $metric;
		}
		return $result;
	}
	else {
		return new FX_Error(__FUNCTION__, _('Unable to get schema metrics'));
    }
}

function get_field_decimals($object_type_id, $field_name)
{
	$default_decimals = 2;

	$field = get_custom_type_field($object_type_id, $field_name);
	
	if (is_fx_error($field)) {
		return $default_decimals;
	}

	$metric = get_metric($field['metric']);
	
	if (is_fx_error($metric)) {
		return $default_decimals;
	}
	
	return $metric['units'][$field['unit']]['decimals'];
	
}

function update_field_unit($object_type_id, $field_name, $metric_id, $unit_id)
{
	$field = get_custom_type_field($object_type_id, $field_name);
	
	if (is_fx_error($field)) {
		return $field;
	}
	
	//check field type
	
	$metric = get_metric($metric_id);
	
	if (is_fx_error($metric)) {
		return $metric;
	}

	$old_factor = (float)$metric['units'][$field['unit']]['factor'];
	$new_factor = (float)$metric['units'][$unit_id]['factor'];
	
	$ratio = $new_factor/$old_factor;
	
	global $fx_db;
	
	//$fx_db->update(DB_TABLE_PREFIX."object_type_".$object_type_id, array($field_name=>));
	
	$query = "UPDATE ".DB_TABLE_PREFIX."object_type_".$object_type_id." SET $field_name=$field_name*$ratio";
}

function get_schema_settings($schema_id)
{
	$schema_settings = get_fx_option('schema_settings', array());
	return isset($schema_settings[$schema_id]) && is_array($schema_settings[$schema_id]) ? $schema_settings[$schema_id] : array();
}