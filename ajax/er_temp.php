<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$source = json_decode($_POST['positions'],true);
	$connections = json_decode($_POST['connections'],true);
	
	$current_schema = (int)$_SESSION['current_schema'];
	$positions = $tmp_diargam = array();	

	foreach ($source as $pos) {
		if ($object_type_id = (int)str_replace('er_','',$pos[0])) {
			$positions[$object_type_id] = array('x' => (int)str_replace('px','',$pos[1]),'y' => (int)str_replace('px','',$pos[2]));
		}
	}

	foreach ($connections as $connector)
	{
		list($object_type_1_id, $object_type_2_id, $relation, $strength) = explode('-', $connector);

		$object_type_1_id = (int)str_replace('er_', '', $object_type_1_id);
		$object_type_2_id = (int)str_replace('er_', '', $object_type_2_id);

		if ($object_type_1_id && $object_type_2_id) {
			$link_type_id = $object_type_1_id.'-'.$object_type_2_id;
			$position = $positions[$object_type_1_id]['x'].','.$positions[$object_type_1_id]['y'].','.$positions[$object_type_2_id]['x'].','.$positions[$object_type_2_id]['y'];			
			$tmp_diargam[] = array(
				'object_type_1_id' => $object_type_1_id,
				'object_type_2_id' => $object_type_2_id,
				'relation' => $relation,
				'position' => $position,
				'strength' => $strength);
		}
	}

	update_fx_option('er_tmp_'.$current_schema, $tmp_diargam);

	echo 'Double click to view/edit';
?>