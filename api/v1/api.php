<?php
	require_once dirname(dirname(dirname(__FILE__))).'/fx_load.php';

	$methods = array();

	$ep_path = 'endpoints/';
	
	include 'endpoints/api_channel.php';
	include 'endpoints/api_chart.php';
	include 'endpoints/api_enum.php';
	include 'endpoints/api_file.php';
	include 'endpoints/api_version.php';
	include 'endpoints/api_link.php';
	include 'endpoints/api_object.php';
	include 'endpoints/api_queries.php';
	include 'endpoints/api_query.php';
	include 'endpoints/api_request.php';
	include 'endpoints/api_role.php';
	include 'endpoints/api_send.php';
	include 'endpoints/api_subscription.php';
	include 'endpoints/api_type.php';
	include 'endpoints/api_user.php';
	include 'endpoints/api_widget.php';
	include 'endpoints/api_wordpress.php';
	include 'endpoints/api_sync.php';
		
	do_actions('fx_init_api_methods');

	if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
		$_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
	}

	$clear_path = trim($_REQUEST['endpoint'], '/');

	list($endpoint) = explode('/', $clear_path);

	ob_start();
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Methods: *");
	header("Content-Type: application/json");

	if (caching_enabled() && ($result = get_api_cache($clear_path.'/', $_REQUEST['data']))) {	
        header("HTTP/1.1 200 OK");
		echo $result;
	}
	else {		
		$class_name = 'FX_API_'.ucwords($endpoint);
	
		if (!class_exists($class_name)) {
			$class_name = 'FX_API_Version';
		}

		try {		
			$custom_API = new ReflectionClass($class_name);
			$API = $custom_API -> newInstanceArgs(array($_REQUEST, $_SERVER['HTTP_ORIGIN']));
			$result = $API -> process_API();

			//header("HTTP/1.1 200 OK");
			echo $result;
		}
		catch (Exception $e) {
			header("HTTP/1.1 400 Bad Request");
			echo json_encode(new FX_Error('api_error', 'ERROR: '.$e -> getMessage()));
		}
	}

	ob_end_flush();