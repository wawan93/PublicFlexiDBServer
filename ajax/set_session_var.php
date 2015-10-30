<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$_SESSION[$_POST['var']] = $_POST['value'];