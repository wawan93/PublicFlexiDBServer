<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();
	
	$data = $_GET ? $_GET : $_POST;
	
	if(!isset($data['function']))
	{
		$error = new FX_Error('call_fx_function', 'Set function name.');
		echo json_encode($error);
		exit();
	}

	if(!function_exists($data['function']))
	{
		$error = new FX_Error('call_fx_function', 'Specified function does not exist.');
		echo json_encode($error);
		exit();
	}	
	
	foreach($data as $key => $value)
	{
		$value = json_decode($value, true);
		if($value !== null) $data[$key] = $value;
	}
	
    $f = new ReflectionFunction($data['function']);
    
	$args = array();
	
    foreach ($f->getParameters() as $param)
	{
        $args[$param->name] = $data[$param->name];   
    }

	echo json_encode(call_user_func_array ($data['function'], $args));
?>