<?php

function _type_custom_field_control($object_type_id, $options)
{
	$result = array();

	$field_name = $options['name'];
	
	switch ($object_type_id) {
		
		case TYPE_DATA_SCHEMA:
			if (in_array($field_name, array('roles','user_fields','sfx_alias','channel','app_group','app_group','icon', 'sub_chnl_alias', 'sub_chnl_alias_pl'))) {
				return false;
			}
		break;
		case TYPE_ROLE:
			if ($field_name == 'permissions') {
				return false;
			}		
		break;
		case TYPE_WP_TMPL_SIGNUP:
		case TYPE_WP_TMPL_PAGE:
			if ($field_name == 'associated_type') {
				
					$result = array(
						'label'=>'<div class="star"></div><label for="field_associated_type">Associated Type:</label>', 
						'control' => '<select id="field_associated_type" name="associated_type" onchange="$(\'._fields\').hide(); $(\'._\'+$(this).val()).show(); $(\'#object_field\').val(0);"><option value="">Please select</option>'.show_select_options(get_schema_types($_SESSION['current_schema'], 'none'), 'object_type_id', 'display_name', $options['value'], false).'</select>');
			}	
		break;
		case TYPE_FSM_EVENT:
		case TYPE_FSM_INITIAL_EVENT:
			switch ($field_name) {
				case 'object_type':
					$result = array(
						'label'=>'<div class="star"></div><label for="object_type">Object Type:</label>', 
						'control' => '<select id="object_type" name="object_type" onchange="$(\'._fields\').hide(); $(\'._\'+$(this).val()).show(); $(\'#object_field\').val(0);"><option value="">Please select</option>'.show_select_options(get_schema_types($_SESSION['current_schema'], 'none'), 'object_type_id', 'display_name', $options['value'], false).'</select>');
				
				break;
				case 'object_field':
					
					$ctrl = '<select id="object_field" name="object_field"><option value="">Please select</option>';
					foreach (get_schema_types($_SESSION['current_schema'], 'custom') as $type) {
						foreach ($type['fields'] as $f_name => $opts) {
							if(!is_numeric($opts['type'])) continue;
							$s = $options['value'] == $opts['type'] ? ' selected="selected"' : '';
							$ctrl .= '<option class="_fields _'.$opts['object_type_id'].'" value="'.$f_name.'" style="display:none"'.$s.'>'.$f_name.'</option>';
						}
					}
					$ctrl .= '</select>';

					$result = array(
						'label'=>'<div class="star"></div><label for="'.$field_name.'">Object Field:</label>', 
						'control' => $ctrl);
				break;
				case 'initial_state':
				case 'start_state':
				case 'end_state':
				case 'event_condition':
				case 'roles':
					return false;
				break;
			}
		break;
		case TYPE_TWILIO_MSG_OUT:
			if ($field_name == 'status') {
				return false;
			}
		break;
		case TYPE_APPLICATION:
			if (in_array($field_name, array('code', 'style', 'status', 'icon', 'category'))) {
				return false;
			}
		break;
		case TYPE_DFX_GENERIC_WEBSITE:
		case TYPE_DFX_WP_SITE:
			if (in_array($field_name, array('installed', 'fx_plugin', 'fx_theme'))) {
				return false;
			}
		case TYPE_SUBSCRIPTION:
			if (in_array($field_name, array('roles'))) {
				return false;
			}
		break;
		break;
	}
	
	return $result;
}

add_action('fx_type_custom_field_control', '_type_custom_field_control', 100, 2);