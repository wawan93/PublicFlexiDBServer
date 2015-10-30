<?php

abstract class FX_Task
{
	
}

//******************************************************************************************************

function enable_task($args)
{
	extract($args);

	if (!$class) {
		return; 
	}

	$active_tasks = get_fx_option('active_tasks', array());
	
	if (!$method) {
		$method = 'EMPTY';
	}

	if (!$endpoint) {
		$endpoint = 'EMPTY';
	}

	$active_tasks[$method][$endpoint][$class] = $args;
	add_log_message(__FUNCTION__, _('Task enabled').': '.$class);

	return update_fx_option('active_tasks', $active_tasks);
}

function disable_task($args)
{
	extract($args);
	$active_tasks = get_fx_option('active_tasks', array());
	unset($active_tasks[$method][$endpoint][$class]);
	add_log_message(__FUNCTION__, _('Task disabled').': '.$class);

	return update_fx_option('active_tasks', $active_tasks);
}

function scan_task_dir($dir, &$tasks)
{
	if (is_dir($dir))
	{
		if ($cur_dir = @opendir($dir))
		{
			while (($item = readdir($cur_dir)) != false)
			{
				$path = $dir.(substr($dir, -1) != '/' ? '/' : '').$item;
				
				if(!in_array($path,$exclude_path))
				{
					if (is_file($path))
					{						
						$task_data = implode('',file($path));
						$str = '';

						preg_match('|class(.*?)extends|Uis', $task_data, $str);
						$task_class = trim($str[1]);//strtolower(trim($str[1]));

						if ($task_class)
						{
							preg_match('|Name:(.*)$|mi', $task_data, $str);
							$task_name = trim($str[1]);

							preg_match('|Description:(.*)$|mi', $task_data, $str);
							$task_description = trim($str[1]);

							preg_match('|Version:(.*)$|mi', $task_data, $str);
							$task_version = trim($str[1]);

							preg_match('|Author:(.*)$|mi', $task_data, $str);
							$task_author = trim($str[1]);			

							preg_match('|API Method:(.*)$|mi', $task_data, $str);
							$task_method = trim($str[1]);

							preg_match('|API Endpoint:(.*)$|mi', $task_data, $str);
							$task_endpoint = $str[1] ? '/'.trim(str_replace('\\', '/', trim($str[1])), '/') : '';

							$tasks[$task_class] = array( 'class' => $task_class,
														 'path' => $path, 
														 'name' => $task_name,
														 'description' => $task_description,									   
														 'version' => $task_version,
														 'method' => $task_method,
														 'endpoint' => $task_endpoint);
						}
					}
					elseif(is_dir($path) && $item != '.' && $item != '..')
					{
						scan_task_dir($path, $tasks);
					}
				}
			}
		}
	}
}

/**
 * @param $schema_id
 * @param $method
 * @param $endpoint
 * @param $verb
 * @param array $request_args
 * @param array $request_result
 * @return bool|int
 */
function perform_tasks_for_request($schema_id, $method, $endpoint, $verb, $request_args = array(), $request_result = array())
{
	$endpoint = '/'.$endpoint.($verb ? '/' : '').$verb;
	$active_tasks = get_fx_option('active_tasks', array());
	$actual_tasks = isset($active_tasks[$method][$endpoint]) ? $active_tasks[$method][$endpoint] : false;

	if (!$actual_tasks) {
		return false;
	}

	global $fx_db;

    $fx_db->
		select(DB_TABLE_PREFIX."object_type_".TYPE_TASK, array('source_class'=>'source', 'source_args', 'action_class'=>'action', 'action_args', 'error_class'=>'error', 'error_args'))->
		where(array(
            'schema_id'=>(int)$schema_id,
            'enabled'=>1,
            'action <>'=>'',
            'schedule'=>'',
            'source IN'=>array_keys($actual_tasks)
        ))->
		order('priority');


	if (is_fx_error($fx_db->select_exec())) {
		add_log_message(__FUNCTION__, 'SQL ERROR: '.print_r($fx_db->get_last_error(),true));
		return false;		
	}

	$tasks = array();

	foreach ((array)$fx_db->get_all() as $task) {
		$source_args = json_decode($task['source_args'], true);

		if ($source_args !== NULL) {			
			$run_task = true;
			$evals = array();

			foreach ($source_args as $param_name => $param) {
				$param['value'] = preg_replace("{[^a-zA-Z0-9_\s]}",'', $param['value']);
				if (strtolower($param['condition']) != 'ignore') {
					if ($param['value'] && $param['condition'] && in_array($param['condition'], array('>','<','==','!=','<=','>='))) {
						if (!eval('if('.$request_args[$param_name].$param['condition'].$param['value'].') return true; else return false;')) {
							$run_task = false;
							break;
						}
					}
				}
			}

			if ($run_task)
			{
				//Transform result
				//=====================
				if (class_exists($task['source'])) {
					$task_reflection = new ReflectionClass($task['source']);
					$task_object = $task_reflection->newInstanceArgs();
					if (method_exists($task_object, 'transform_result')) {
						$request_result = $task_object -> transform_result($request_args, $request_result);
					}
				}
				//=====================				

				if (!is_fx_error($request_result))
				{
					$class = $task['action'];
					$args = json_decode($task['action_args'], true);
								
					if ($args === NULL) {
						$args = array();
					}
					else {
						foreach ($args as $key => $value) {
							$value = str_replace('%result%', print_r($request_result, true), $value);
							foreach (parse_string($value) as $code) {
								$value = str_replace($code, $request_result[trim(str_replace('%', '', $code))], $value);
							}
							$args[$key] = $value;
						}
					}
				}
				else {
					$class = $task['error'];	
					$args = json_decode($task['error_args'], true);

					if ($args === NULL) {
						$args = array();
					}
					else {
						foreach ($args as $key => $value) {
							$args[$key] = str_replace('%error%', $request_result->get_error_message(), $value);		
						}
					}
				}								

				$tasks[] = array('class'=>$class, 'args'=>$args);
			}
		}
	}

	if (!count($tasks)) {
		return false;
	}

	$action_count = 0;

	foreach ($tasks as $task)
	{
		if ($task['class'])
		{
			if (class_exists($task['class'])) {
				$task_reflection = new ReflectionClass($task['class']);
				$current_task = $task_reflection -> newInstanceArgs();
				
				if (method_exists($current_task, 'action')) {
					$result = $current_task -> action($task['args'], $request_result);
					$action_count++;
				}
			}
			else {
				add_log_message(__FUNCTION__, 'Unknown action class ['.$task['class'].']');
			}
		}
	}

	return $action_count;
}

function run_task($object_id)
{
	$task_object = get_object(TYPE_TASK, $object_id);
	
	if (is_fx_error($task_object)) {
		return $task_object;
	}

	if (!$task_object['enabled']) {
		return false;
	}
	
	if (!$task_object['schedule'] || $task_object['schedule'] == '* * * * *') {
		return new FX_Error(__FUNCTION__, 'Invalid cron task.');
	}
	
	$source_class = $task_object['source'];
	$source_args = $task_object['source_args'];

	if (!class_exists($source_class)) {
		return new FX_Error(__FUNCTION__, 'Unknown source class ['.$source_class.']');
	}
	
	$task_reflection = new ReflectionClass($source_class);
	$current_task = $task_reflection -> newInstanceArgs();
	
	if (method_exists($current_task, 'action')) {
		
		$source_args = json_decode($source_args, true);			
		if ($source_args === NULL) $source_args = array();
		
		foreach ($source_args as $key=>$value) {
			$source_args[$key] = $value['value'] ;
		}

		if (is_array($source_args)) {
			$source_args['schema_id'] = $task_object['schema_id'];
		}

		$source_result = $current_task -> action($source_args);
	}
	else {
		return new FX_Error(__FUNCTION__, 'Action method does not exists in class ['.$source_class.'].');		
	}
	
	//******************************************************************************************************

	if (!is_fx_error($source_result))
	{
		$reaction_class = $task_object['action'];		
		
		$reaction_args = json_decode($task_object['action_args'], true);			
		if ($reaction_args === NULL) $reaction_args = array();		
		
		foreach ($reaction_args as $key => $value) {
			$value = str_replace('%result%', print_r($source_result, true), $value);
			foreach (parse_string($value) as $code) {			
				$value = str_replace($code, $request_result[trim(str_replace('%', '', $code))], $value);
			}
			$reaction_args[$key] = $value;						
		}
	}
	else
	{
		$reaction_class = $task_object['error'];	
			
		$reaction_args = json_decode($task_object['error_args'], true);			
		if ($reaction_args === NULL) $reaction_args = array();		
	
		foreach ($reaction_args as $key => $value) {
			$reaction_args[$key] = str_replace('%error%', $request_result->get_error_message(), $value);		
		}
	}	
	//******************************************************************************************************

/*	if (!is_fx_error($source_result)) {
		$reaction_class = $task_object['action'];				
		$reaction_args = str_replace('%result%', print_r($source_result, true), $task_object['action_args']);
		
		foreach (parse_string($reaction_args) as $code) {			
			$reaction_args = str_replace($code, $source_result[trim(str_replace('%', '', $code))], $reaction_args);
		}			
	}
	else {
		$reaction_class = $task_object['error'];
		$reaction_args = str_replace('%error%', $source_result->get_error_message(), $task_object['error_args']);	
	}*/

	if ($reaction_class)
	{
		if (!class_exists($reaction_class)) {
			return new FX_Error(__FUNCTION__, 'Unknown reaction class ['.$reaction_class.'].');
		}

		$reaction_class = new ReflectionClass($reaction_class);
		$task = $reaction_class -> newInstanceArgs();
		
		if (method_exists($task, 'action'))  {					
			return $task -> action($reaction_args, $source_result);
		}
		else {
			return new FX_Error(__FUNCTION__, 'Action method does not exists in class ['.$reaction_class.']');
		}
	}

	return true;	
}