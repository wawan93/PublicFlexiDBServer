<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$api_key = validate_script_user();

	$class = (int)$_POST['class'];
	$form = (int)$_POST['form'];

	if (class_exists($class)) {
		$action_reflection = new ReflectionClass($class);
		$action = $action_reflection -> newInstanceArgs();

		if (method_exists($action, $form.'_form')) {
			if ($form = 'action') {
				$action-> action_form();	
			}
			elseif ($form = 'reaction') {
				$action-> reaction_form();
			}
		}
		else {
			echo '<font color="#FF0000">Invalid form method</font>';
		}				
	}
	else {
		echo '<font color="#FF0000">Action class does not exists</font>';				
	}
