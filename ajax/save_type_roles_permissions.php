<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();


	$role_type = get_type_id_by_name(0, 'role');
	$roles = get_objects_by_type($role_type, $_SESSION['current_schema']);
	
	foreach ($roles as $role) {
		$permissions = json_decode($role['permissions']);
		
		if (isset($_POST['flags'][$role['object_id']])) {
			$flag = $_POST['flags'][$role['object_id']];
			$perms = bindec((isset($flag['delete'])?'1':'0').(isset($flag['put'])?'1':'0').(isset($flag['post'])?'1':'0').(isset($flag['get'])?'1':'0'));
			$permissions->$_POST['object_type_id'] = $perms;
		}
		else {
			unset($permissions->$_POST['object_type_id']);
		}
		
		$role['permissions'] = json_encode($permissions);
		$result = update_object($role);
		
		if (is_fx_error($result)) {
			break;
		}
	}
	
	if (is_fx_error($result)) {
		echo json_encode(array('status'=>'error', 'message' => $result->get_error_message()));
	}
	else {
		echo json_encode(array('status'=>'success'));
	}