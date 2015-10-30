<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$source = json_decode($_POST['positions'],true);
	$connections = json_decode($_POST['connections'],true);

	$current_schema = (int)$_SESSION['current_schema'];
	$positions = $old_links = $links_to_leave = $links_to_delete = array();	

	foreach($source as $pos)  {
		if($object_type_id = (int)str_replace('er_','',$pos[0])) {
			$positions[$object_type_id] = array('x' => (int)str_replace('px','',$pos[1]),'y' => (int)str_replace('px','',$pos[2]));
		}
	}

    global $fx_db;

    $result = $fx_db->select(DB_TABLE_PREFIX."link_type_tbl")->where(array('schema_id'=>$current_schema, 'system <>'=>1))->select_exec();
	
	if (is_fx_error($result)) {
        add_log_message(__FUNCTION__, print_r($result->get_error_data(), true));
    }

	$link_types = $fx_db->get_all();

	foreach($link_types as $link_type) {
		$old_links[ $link_type['object_type_1_id'].'-'.$link_type['object_type_2_id']] = $link_type;
	}

	foreach($connections as $connector)
	{
		list ($object_type_1_id, $object_type_2_id, $relation, $strength) = explode('-', $connector);

		$object_type_1_id = (int)str_replace('er_', '', $object_type_1_id);
		$object_type_2_id = (int)str_replace('er_', '', $object_type_2_id);

		$type_1 = get_type($object_type_1_id, 'none');
		$type_2 = get_type($object_type_2_id, 'none');

		if ($type_1 !== false && !is_fx_error($type_1) && $type_2 !== false && !is_fx_error($type_2))
		{
			$link_type_id = $object_type_1_id.'-'.$object_type_2_id;
			$position = $positions[$object_type_1_id]['x'].','.$positions[$object_type_1_id]['y'].','.$positions[$object_type_2_id]['x'].','.$positions[$object_type_2_id]['y'];

			if (array_key_exists($link_type_id, $old_links)) {
				if ($old_links[$link_type_id]['position'] != $position || $old_links[$link_type_id]['relation'] != $relation) {					
					$res = update_link_type($object_type_1_id, $object_type_2_id, $relation, $old_links[$link_type_id]['system'], $position);
				}

				$links_to_leave[] = $link_type_id;
			}
			else {
				$system = $type_1['system'] && $type_2['system'] ? true : false;
				$res = add_link_type($object_type_1_id, $object_type_2_id, $relation, $current_schema, $system, $position);		
			}

			if (!is_fx_error($res)) {
				$res = update_link_strength($object_type_1_id, $object_type_2_id, $strength);
			}
			else {
				break;
			}
		}
	}

	if (!is_fx_error($res)) {
		foreach ($old_links as $link_type_id => $options) {
			if (!in_array($link_type_id, $links_to_leave)) {
				$links_to_delete[] = $old_links[$link_type_id];
				list($object_type_1_id, $object_type_2_id) = explode('-', $link_type_id);
				delete_link_type($object_type_1_id, $object_type_2_id);
			}
		}
	
		delete_fx_option('er_tmp_'.$current_schema);
	
		echo '<strong>'._('Diagram successfully updated').'</strong>';
	}
	else {
		echo '<font color="#FF0000">'._('Error').': '.$res->get_error_message().'</font>';
	}