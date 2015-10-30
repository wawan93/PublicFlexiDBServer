<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$object_type_id = intval($_GET['type']);
	
	if ($object_type_id) {
		$type_fields = get_type_fields($object_type_id, 'custom');
		
		if (is_fx_error($type_fields)) {
			echo '<font color="#FF0000">'.$type_fields->get_error_message().'</font>';
		}
		else {
			$exclude_fields = array('file', 'image');
			$conditions = array("ignore"=>"ignore", "=="=>"==", "!="=>"!=", ">"=>">", ">="=>">=", "<"=>"<", "<="=>"<=");
		
		
			foreach($type_fields as $field => $field_options) {
				if (!in_array($field_options['type'], $exclude_fields)) {
					$field_options['class'] = 'task-param';
					$fc = get_field_control($field_options);
					$name = $field_options['name'];
					$conditions_select = '<select class="condition-select" style="display:none" name="'.$name.'-condition">';
		
						foreach ($conditions as $key => $value) {
							$selected = $key == $selected ? ' selected="selected"' : '';
							$conditions_select .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
						}
		
					$conditions_select .= '</select>';
					echo $fc['label'].$conditions_select.$fc['control'];
				}
			}
		}
	}