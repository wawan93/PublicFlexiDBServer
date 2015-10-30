<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$object_type_id = intval($_GET['type']);
	
	if ($object_type_id) {
		$type_fields = get_type_fields($object_type_id, 'all');
		
		if (is_fx_error($type_fields)) {
			echo '<font color="#FF0000">'.$type_fields->get_error_message().$object_type_id.'</font>';
		}
		else {
			foreach($type_fields as $code => $field_data) {
				$title = $field_data['caption'] ? $field_data['caption'] : $code;		
				echo "\n\t<span class=\"param event button small green\" data=\"%$code%\">$title</span>";
			}
		}
	}