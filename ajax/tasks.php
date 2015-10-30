<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	require_once CONF_EXT_DIR . '/phpseclib/Net/SSH2.php';
	require_once CONF_EXT_DIR . '/phpseclib/Net/SFTP.php';

	$args = $_GET ? $_GET : $_POST;
	
	$remove_cronjob = $append_cronjob = false;
	$result = true;
	$object_type_id = TYPE_TASK;

	switch($args['function'])
	{
		case 'get':

			$result = get_object($object_type_id, $args['object_id']);

		break;
		case 'add':
	
			$args['schema_id'] = $_SESSION['current_schema'];
			$args['set_id'] = 0;
			$args['object_type_id'] = $object_type_id;

			$object_id = add_object($args);
			
			if (is_fx_error($object_id))
			{
				$result = array('error' => $object_id -> get_error_message());
			}
			else
			{
				$task_object = get_object($object_type_id, $object_id);
				
				if($task_object['source'] && $task_object['schedule'] && $task_object['schedule'] != '* * * * *') {
					$server_settings = get_fx_option('server_settings');
					$append_cronjob = $task_object['schedule'].' wget "'.CONF_AJAX_URL.'cron_task.php?task_id='.$task_object['object_id'].'&dfx_key='.$server_settings['dfx_key'];
					$append_cronjob .= '" >/dev/null 2>&1';
				}
				$result = $object_id;
			}

		break;
		case 'update':

			$task_object = get_object($object_type_id, $args['object_id']);
			
			$args['object_type_id'] = $object_type_id;
			
			if($task_object['enabled'] && $task_object['source'] && $task_object['schedule'] && $task_object['schedule'] != '* * * * *') {
				$remove_cronjob = 'task_id='.$args['object_id'];
			}
			
			$update_result = update_object($args);
			
			if (is_fx_error($update_result)) {
				$result = array('error'=>$update_result->get_error_message());
			}
			else
			{
				$task_object = get_object($object_type_id, $args['object_id']);
			
				if($task_object['enabled'] && $task_object['source'] && $task_object['schedule'] && $task_object['schedule'] != '* * * * *') {
					$server_settings = get_fx_option('server_settings');
					$append_cronjob = $task_object['schedule'].' wget "'.CONF_AJAX_URL.'cron_task.php?task_id='.$task_object['object_id'].'&dfx_key='.$server_settings['dfx_key'];
					$append_cronjob .= '" >/dev/null 2>&1';
				}
			}
				
		break;
		case 'delete':
			
			$task_object = get_object($object_type_id, $args['object_id']);
			
			if($task_object['source'] && $task_object['schedule'] && $task_object['schedule'] != '* * * * *') {
				$remove_cronjob = 'task_id='.$args['object_id'];						
			}
			
			$delete_result = delete_object($object_type_id, $args['object_id']);

			if (is_fx_error($delete_result)) {
				$result = array('error' => $delete_result -> get_error_message());
			}
			
		break;
		default: $result = array('error'=>'ERROR: Unknown action.');
	}
	
	if (($remove_cronjob || $append_cronjob))
	{
		$local_ftp_options = get_fx_option('local_ftp_options', array());

		$crontab = new FX_Cron('localhost', $local_ftp_options['ftp_username'], $local_ftp_options['ftp_password']);

		if (!$crontab->error) {
			if ($remove_cronjob) {
				$crontab->remove_cronjob($remove_cronjob);
				if(CONF_ENABLE_TASK_LOG) {
					add_log_message('remove_cronjob',$remove_cronjob);
				}
			}
			if ($append_cronjob) {
				$crontab->append_cronjob($append_cronjob);
				if(CONF_ENABLE_TASK_LOG) {
					add_log_message('append_cronjob',$append_cronjob);
				}
			}
		}
		else {
			$result = array('error'=>$crontab->error);
		}
	}
		
	echo json_encode($result);
?>