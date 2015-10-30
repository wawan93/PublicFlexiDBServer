<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	ob_start();

	download_query_csv(json_decode(
		$_REQUEST['query'],true),
		$_REQUEST['main_type'],
		json_decode($_REQUEST['joined_types'],true),
		$_REQUEST['hide_empty'],
		$_REQUEST['display_name'] ? $_REQUEST['display_name'].'.csv' : 'query.csv'
	);
	
	ob_end_flush();