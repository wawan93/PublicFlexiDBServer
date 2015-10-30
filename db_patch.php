<?php

	require_once dirname(__FILE__)."/fx_load.php";
	
	function update_db_from_1_to_2() {
		//not supported
		return true;
	}
	
	function update_db_from_2_to_3() {	
		//not supported
		return true;		
	}	
	
	function update_db_from_3_to_4() {	
		//not supported
		return true;		
	}
	
	function update_db_from_4_to_5() {
		
		//add sub_chnl_alias and sub_chnl_alias_pl fileds into the data schema

		$type = get_type(TYPE_DATA_SCHEMA, 'custom');
		
		if (is_fx_error($type)) {
			return $type;
		}		

		$type['fields']['sub_chnl_alias'] = array(
			'name' => 'sub_chnl_alias',
			'caption' => 'Sub-channel Alias',
			'description' => 'Alias for sub-channels at all',
			'mandatory' => 0,
			'type' => 'varchar',
			'default' => 'sub-channel',
			'sort_order' => 6
		);
		
		$type['fields']['sub_chnl_alias_pl'] = array(
			'name' => 'sub_chnl_alias_pl',
			'caption' => 'Sub-channel Alias Plural',
			'description' => 'Plural form for Sub-channel Alias',
			'mandatory' => 0,
			'type' => 'varchar',
			'sort_order' => 7
		);
		
		$result = update_type($type);
		
		if (is_fx_error($result)) {
			return $result;
		}
		
		return true;
	}
	
	$update_options = get_fx_option('update_options', array());
	$new_db_version = $update_options['new_db_version'];
	
	$current_db_version = get_fx_option('db_version', 1);
	$update_result = array();
	
	echo '<p>Current DB Version: '.$current_db_version.'</p>';
	echo '<p>Latest DB Version: '.$new_db_version.'</p>';

	if ($new_db_version > $current_db_version) {
		$update_error = false;
		
		for ($i=$current_db_version; $i<$new_db_version; $i++) {
			$func_name = 'update_db_from_'.$i.'_to_'.($i+1);

			if (function_exists($func_name)) {
				$result = call_user_func($func_name);
				if (is_fx_error($result)) {
					$update_result[] = 'ERROR: Unable to update from '.$i.' to '.($i+1);
					$update_result[] = $result->get_error_message();
					$update_error = true;
					break;
				}
				else {
					$update_result[] = 'FlexiDB database updated from version '.$i.' to '.($i+1);
					update_fx_option('db_version', $i+1);
				}
			}
			else {
				$update_result[] = 'ERROR: Unknow function '.$current_db_version.' to '.$i;
				$update_error = true;
				break;
			}
		}

		if (!$update_error) {
			if (isset($_GET['redirect'])) {
				fx_redirect(URL.'settings/settings_update');
			}	

			$update_result[] = 'Database update complete from version '.$current_db_version.' to '.$new_db_version;
			
			fx_print($update_result);
			echo '<p><a href="'.URL.'settings/settings_update">Go back to the Settings/Update</a></p>';
			die();
		}
		else {
			fx_print($update_result);
			echo '<p><a href="'.URL.'settings/settings_update">Go back to the Settings/Update</a></p>';
			die();		
		}
	}
	else {
		
		if (isset($_GET['redirect'])) {
			fx_redirect(URL.'settings/settings_update');
		}		
	
		echo '
		<p>You are using the latest version of the database</p>
		<p><a href="'.URL.'settings/settings_update">Go back to the Settings/Update</a></p>';
	}