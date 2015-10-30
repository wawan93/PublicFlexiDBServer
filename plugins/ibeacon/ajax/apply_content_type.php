<?php

	require_once dirname(dirname(dirname(dirname(__FILE__))))."/fx_load.php";
	fx_start_session();
	
	$content_type = $_GET['ct'];
	$field_name = $_GET['fn'];
	
	switch($_GET['ct']) {
		case 'empty':
			$res = '<input type="hidden" name="'.$field_name.'[data]" value="">Empty';
		break;
		case 'static':
			$res = '<textarea name="'.$field_name.'[data]"></textarea>';
		break;
		case 'field':
            $types = get_schema_types($_SESSION['current_schema']);
            $field_name = $field_name.'[data]';
            $res = '
			<select name="'.$field_name.'[type]" onchange="var $el=$(this); $.post(\''.IB_PLUGIN_URL.'ajax/get_additional_fields.php\', {action: \'select_type\', type: $(this).val(), field_name: \''.$field_name.'\'}, function(response) {
			    $el.next().html(response);
			});">
			<option value="">Select type</option>
			'.show_select_options($types, 'object_type_id', 'display_name', '', false).'
			</select><span></span>';
		break;		
		case 'app':
		    $apps = get_objects_by_type('application', $_SESSION['current_schema'], $_SESSION['current_set']);
            $field_name = $field_name.'[data]';
            $res = '
            <select name="'.$field_name.'[app_id]" onchange="var $el=$(this); $.post(\''.IB_PLUGIN_URL.'ajax/get_additional_fields.php\', {action: \'select_app\', app: $(this).val(), field_name: \''.$field_name.'\'}, function(response) {
                $el.next().html(response);
            })">
                <option value="">Select app</option>
                '.show_select_options($apps, 'object_id', 'display_name', '', false).'
            </select>
            <span></span>';
		break;
		default:
			$res = '<font color="#FF0000">ERROR: Invalid content type</font>';
	}
	
	echo $res;