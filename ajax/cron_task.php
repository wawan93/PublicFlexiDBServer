<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	//fx_start_session();
	//validate_script_user();

	require_once CONF_EXT_DIR . '/phpseclib/Net/SSH2.php';
	require_once CONF_EXT_DIR . '/phpseclib/Net/SFTP.php';

	$task_id = $_GET['task_id'];
	$dfx_key = $_GET['dfx_key'];
	
	$server_settings = get_fx_option('server_settings');
	
	if($server_settings['dfx_key'] == '') {
		add_log_message('cron_task', 'Empty DFX Key.');
	}
	elseif($dfx_key != $server_settings['dfx_key']) {
		add_log_message('cron_task', 'Invalid DFX Key.');
	}
	else {
		$result = run_task($task_id);
	
		if (is_fx_error($result)) {
			add_log_message('cron_task', $result->get_error_message());
		}
	}