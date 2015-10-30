<?php
    
	require_once dirname(dirname(dirname(dirname(__FILE__))))."/fx_load.php";
	fx_start_session();
    
	$field_name = $_REQUEST['field_name'];

    switch ($_REQUEST['action']) {
        case 'select_type':
            if (!$_SESSION['current_set']) {
                $ret = '<div class="error">Select data set</div>';
            } else {
                $objects = get_objects_by_type($_REQUEST['type'], $_SESSION['current_schema'], $_SESSION['current_set']);
                $fields = get_type_fields($_REQUEST['type'], 'all');
                $ret = '
                <select name="'.$field_name.'[object]">
                    <option value="">Select object</option>
                    '.show_select_options($objects, 'object_id', 'display_name', '', false).'
                </select>
                <select name="'.$field_name.'[object_field]">
                    <option value="">Select field</option>
                    '.show_select_options($fields, 'name', 'caption', '', false).'
                </select>';
            }
        break;
        case 'select_app':
            $app_type = get_type_id_by_name(0, 'application');
            $versions = get_actual_links($app_type, $_REQUEST['app'], TYPE_APP_DATA);
            $ret = '
           <select name="'.$field_name.'[app_version]" onchange="var $el=$(this); $.post(\''.IB_PLUGIN_URL.'ajax/get_additional_fields.php\', {action: \'select_version\', version: $(this).val(), field_name: \''.$field_name.'\'}, function(response) {
			    $el.next().html(response);
			});">>
                <option value="">Select version</option>
                '.show_select_options($versions[TYPE_APP_DATA], '', 'display_name', '', false).'
            </select><span></span>';
        break;
        case 'select_version':
            $app_version = get_object(TYPE_APP_DATA, $_REQUEST['version']);
            $code = json_decode($app_version['code']);
            $pages = (array)($code->pages);
            $ret = '
            <select name="'.$field_name.'[app_page]">
                <option value="">Select page</option>
                '.show_select_options($pages, '', 'name', '', false).'
            </select>
            ';
        break;
    }

    echo $ret;
?>