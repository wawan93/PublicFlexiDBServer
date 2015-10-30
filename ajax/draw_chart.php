<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$arr['id'] = (int)$_REQUEST['id'];
	$arr['obj'] = $_REQUEST['obj'];
	$arr['fields'] = $_REQUEST['fields'];
	$ret = draw_chart($arr);
	
	ob_start();
	
	if(is_fx_error($ret)) {
		header("HTTP/1.1 400");
		echo json_encode($ret);
	}
	
	ob_end_flush();

