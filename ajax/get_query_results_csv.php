<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$query_id = isset($_REQUEST['query_id']) ? $_REQUEST['query_id'] : 0;
	$set_id = isset($_REQUEST['set_id']) ? $_REQUEST['set_id'] : 0;

	if (!$query_id) {
		die(_('Invalid Query ID'));
	}
	
	$query_object = get_object(TYPE_QUERY, $query_id);
	
	if (is_fx_error($query_object)) {
		die($query_object->get_error_message());
	}
	
	$query_result = exec_fx_query($query_id, $set_id);
	
	if (is_fx_error($query_result)) {
		die($query_result->get_error_message());
	}
	
	$filename = $query_object['display_name'] ? $query_object['display_name'] : $query_object['name'];

	$first_row = array_shift($query_result);
	$csv = implode(';', array_keys($first_row))."\n".implode(';', array_values($first_row));
	
	foreach ($query_result as $row) {
		$csv .= "\n".implode(';', array_values($row));
	}

	ob_end_clean();

	header('Content-Type: text/csv');
	header('Content-disposition: attachment; filename='.$filename.'.csv');
	header('Content-Length: '.strlen($csv));
	
	echo $csv;