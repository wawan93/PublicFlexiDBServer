<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();

	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, FX_SERVER."/api/fx_api.php?action=login_user&dfx_key=".DFX_KEY);
	curl_setopt($ch, CURLOPT_POSTFIELDS,serialize(array('login'=>$_POST['login'],'password'=>md5($_POST['password'])))); 
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	$result = unserialize(curl_exec($ch));
	
	curl_close($ch);

	if(is_array($result)) {
		if(array_key_exists('error',$result)) {
			echo '<center><p><font color="#FF0000">'.$result['error'].'</font></p></center>';
		}
	}