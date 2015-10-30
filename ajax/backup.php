<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

if (isset($_POST['link']) && isset($_POST['name'])) {
    $file = FX_Backup::get_instance()->load_from_dropbox(
        $_POST['link'],
        $_POST['name']
    );
    echo json_encode($file);
} else {
    echo 'error';
}