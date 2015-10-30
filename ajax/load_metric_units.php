<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();
	
	$metric_id = $_REQUEST['metric_id'];
	
	show_select_options(get_metric_units($metric_id), '', 'name');