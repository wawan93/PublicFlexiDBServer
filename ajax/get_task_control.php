<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();
	
	$args = $_GET ? $_GET : $_POST;
	
	switch($args['function'])
	{
		case 'get':
			$result = get_simple_object($args['object_id']);
		break;
		case 'text':
		default: $result = '<input type="text" name="" value="">';
		
	}

	switch($name)
	{
		case 'schema_id':
			echo '<input type="hidden" name="schema_id" value="'.$_SESSION['current_schema'].'"></li>';
		break;
		case 'set_id':
			echo '
			<select name="set_id" style="width:115px;">
			<option value="0">Select Set</option>';
			
			$res = get_objects_by_type('data_set',$_SESSION['current_schema']);
			
			for($i=0; $i<count($res); $i++)
				echo '<option value="'.$res[$i]['object_id'].'">'.$res[$i]['display_name'].'</option>';	
					
			echo '</select>';
			
		break;
		default:	
			echo '<input type="text" name="'.$name.'"></li>';					
	}