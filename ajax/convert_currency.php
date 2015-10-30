<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$amount = $_REQUEST['amount'];
	$from = $_REQUEST['from'];
	$to = $_REQUEST['to'];
	
 	$amount = urlencode($amount);
	$from= urlencode($from);
	$to = urlencode($to);

	$url = "https://www.google.com/finance/converter?a=$amount&from=$from&to=$to";

	$get = file_get_contents($url);

	$get = explode("<span class=bld>", $get);
	$get = explode("</span>", $get[1]);
	
	$converted_amount = preg_replace("/[^0-9\.]/", null, $get[0]);
		
	echo $converted_amount;