<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();
	
	$app = get_application($_GET['app_id']);
	
	if (is_fx_error($app)) {
		die('ERROR: '.($app->get_error_message()));
	}
	
	$theme = json_encode($app['style']);
	
	if ($theme) {
	
		$data = bzcompress($theme);

		$file_name = $app['display_name'].'.'.$app['version'].'.theme';
	
		ob_end_clean();
	
		header('Content-Type: application/file');
		header('Content-disposition: attachment; filename='.$file_name);
		header('Content-Length: '.strlen($data));
	
		echo $data;
	}
	else {
		die('ERROR: Application style is empty');
	}
