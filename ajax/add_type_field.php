<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	require_once CONF_FX_DIR.'/fx_ui/fx_type_form.php';	
	
	echo fx_type_form_print_field();
?>