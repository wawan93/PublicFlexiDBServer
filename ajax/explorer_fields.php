<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$url = urldecode($_GET['url']);

	$table_fields = get_table_fields(normalize_string($_GET['table']));


	echo $url;
	
	foreach($table_fields as $field => $details) {
		echo $field.'<input class="criteria" type="text" id="filter-'.$field.'" name="'.$field.'" value=""><br>';
	}
