<?php

/*******************************************************************************
 * Update FX Option
 * @option_name string - name of the option to update
 * @option_value string - value of the option to get
 * @return bool - update result
 ******************************************************************************/
function update_fx_option($option_name, $option_value = '')
{
	global $fx_db;
	
	$option_name = normalize_string($option_name);
	
	if (is_array($option_value)) {
		$option_value = serialize($option_value);
	}
	
	if (get_fx_option($option_name) === false) {
        $res = $fx_db->insert(DB_TABLE_PREFIX."options_tbl",array('option_name'=>$option_name, 'option_value'=>$option_value));

		if (is_fx_error($res)) {
			add_log_message(__FUNCTION__, print_r($res, true));
		}
		else {
			return true;
		}
	}
	else {
        $res = $fx_db->update( DB_TABLE_PREFIX."options_tbl", array('option_value' => $option_value), array('option_name'=>$option_name) );

		if (is_fx_error($res)) {
			add_log_message(__FUNCTION__, print_r($res, true));
		}
		else {
			return true;
		}		
	}
	
	return false;		
}

/*******************************************************************************
 * Add FX Option (update_fx_option alias)
 * @option_name string - name of the option to add
 * @option_value string - value of the option to add
 * @return bool - add result
 ******************************************************************************/
function add_fx_option($option_name, $option_value = '')
{
	return update_fx_option($option_name, $option_value);
}

/*******************************************************************************
 * Get FX Option
 * @option_name string - name of the option to get
 * @return mixed - value of the specified option
 ******************************************************************************/
function get_fx_option($option_name, $default = false)
{
	global $fx_db;

    $fx_db->select(DB_TABLE_PREFIX."options_tbl",array('option_value'))->where(array('option_name'=>$option_name))->limit(1);

    $res = $fx_db->select_exec();

	if($res && $row = $fx_db->get()) {
		$option_value = $row['option_value'];
		$data = @unserialize($option_value);
		return $data !== false ? unserialize($option_value) : $option_value;
	}
	else {
		return $default;
	}
}

/*******************************************************************************
 * Get FX Options
 * @option_name array - array with options to get
 * @return array - array with values
 ******************************************************************************/
function get_fx_options($options)
{
	global $fx_db;

	$options = (array)$options;

    $fx_db->select(DB_TABLE_PREFIX."options_tbl",array('option_name','option_value'))->where(array('option_name in'=>array_keys($options)));

    $res = $fx_db->select_exec();

	if (is_fx_error($res)) {
		return $options;
	}

	if($res && $rows = $fx_db->get_all()) {
		foreach ($rows as $row) {
			if (isset($options[$row['option_name']])) {
				$option_value = $row['option_value'];
				$data = @unserialize($option_value);
				$options[$row['option_name']] = $data !== false ? unserialize($option_value) : $option_value;
			}
		}
	}
	
	return $options;
}

/*******************************************************************************
 * Remove FX Option
 * @option_name string - name of the option to remove
 * @return bool - remove result
 ******************************************************************************/
function delete_fx_option($option_name)
{
	global $fx_db;

	$option_name = normalize_string($option_name);
	
	if (get_fx_option($option_name)) {

        $pdo = $fx_db->delete(DB_TABLE_PREFIX."options_tbl",array('option_name'=>$option_name));

		if (is_fx_error($pdo)) {
			add_log_message(__FUNCTION__, print_r($pdo->get_error_data(), true));
		}
		else {
			return true;
		}
	}
	
	return false;		
}