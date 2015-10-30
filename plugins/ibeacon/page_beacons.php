<?php
    global $custom_fields, $current_object, $content_type;
	$current_object = get_current_object();
    $content_type = array('empty'=>'Empty', 'static'=>'Static Text', 'field'=>'Object Field Vale', 'app'=>'App Page Link');

	function _update_ibeacon_enum($object_array)
	{
		$ibeacon_options = get_fx_option('ibeacon_enums', array());
		
		$type_ibeacon_uuid = get_type_id_by_name(0, 'ibeacon_uuid');

		$new_ibeacon_options = array();

		if (is_numeric($type_ibeacon_uuid)) {			
			foreach ((array)get_objects_by_type($type_ibeacon_uuid, 0) as $uuid_object)
			{
				$enum_id = isset($ibeacon_options[$uuid_object['uuid']]) ? $ibeacon_options[$uuid_object['uuid']] : 0;
				$beacons = get_beacons_by_uuid($uuid_object['uuid']);	
				
				$enum = array(
					'enum_type_id' => $enum_id,
					'name' => $uuid_object['display_name'] ,
					'schema_id' => 0,
					'system' => 1,
					'fields' => array()
				);

				foreach ((array)$beacons as $beacon) {
					$enum['fields'][$beacon['major'].'.'.$beacon['minor']] = $beacon['display_name'];
				}
				
				if ($enum_id) {
					$result = update_enum_type($enum);
					if (!is_fx_error($result)) {
						$new_ibeacon_options[$uuid_object['uuid']] = $enum_id;
						unset($ibeacon_options[$uuid_object['uuid']]);
					}
				}
				else {
					$enum_id = add_enum_type($enum);
					if (is_numeric($enum_id)) {
						$new_ibeacon_options[$uuid_object['uuid']] = $enum_id;
						unset($ibeacon_options[$uuid_object['uuid']]);
					}
				}
			}
			
			update_fx_option('ibeacon_enums', $new_ibeacon_options);
			
			foreach ($ibeacon_options as $enum_id) {
				delete_enum_type($enum_id);
			}
		}
		
		return $object_array;
	}
	
	add_action('fx_object_form_update', '_update_ibeacon_enum', 1, 1000);

    function custom_proximity_field($custom_field_name) {
        global $custom_fields, $current_object, $content_type;
        $$custom_field_name = (array)json_decode($current_object[$custom_field_name]);
        $cfn = $$custom_field_name;

		$custom_fields[$custom_field_name]['control'] =
		'<select name="'.$custom_field_name.'[content_type]" onchange="$(this).next().load(\''.IB_PLUGIN_URL.'ajax/apply_content_type.php?fn='.$custom_field_name.'&ct=\' + $(this).val());">
		'.show_select_options($content_type, '', '', $cfn['content_type'], false).'
		</select>
		<div>';
            switch ($cfn['content_type']) {
                case 'static':
                    $custom_fields[$custom_field_name]['control'] .= '<textarea name="'.$custom_field_name.'[data]">'.$cfn['data'].'</textarea>';
                break;
                case 'field':
                    $field_name = $custom_field_name.'[data]';
                    $types = get_schema_types($_SESSION['current_schema']);
                    $objects = get_objects_by_type($cfn['data']->type, $_SESSION['current_schema'], $_SESSION['current_set']);
                    $fields = get_type_fields($cfn['data']->type, 'all');
                    $custom_fields[$custom_field_name]['control'] .= '
                        <select name="'.$field_name.'[type]" onchange="var $el=$(this); $.post(\''.IB_PLUGIN_URL.'ajax/get_additional_fields.php\', {action: \'select_type\', type: $(this).val(), field_name: \''.$field_name.'\'}, function(response) {
                            $el.next().html(response);
                        });">
                        <option value="">Select type</option>
                        '.show_select_options($types, 'object_type_id', 'display_name', $cfn['data']->type, false).'
                        </select>
                        <span>
                            <select name="'.$field_name.'[object]">
                                <option value="">Select object</option>
                                '.show_select_options($objects, 'object_id', 'display_name', $cfn['data']->object, false).'
                            </select>
                            <select name="'.$field_name.'[object_field]">
                                <option value="">Select field</option>
                                '.show_select_options($fields, 'name', 'caption', $cfn['data']->object_field, false).'
                            </select>
                        </span>
                    ';
                break;
                case 'app':
                    $field_name = $custom_field_name.'[data]';
                    $apps = get_objects_by_type(TYPE_APPLICATION, $_SESSION['current_schema'], $_SESSION['current_set']);
                    $versions = get_actual_links(TYPE_APPLICATION, $cfn['data']->app_id, TYPE_APP_DATA);
                    $app_version = get_object(TYPE_APP_DATA, $cfn['data']->app_version);
					
					if (!is_fx_error($app_version)) {
                   		$code = (array)json_decode($app_version['code']);
                    	$pages = (array)($code['pages']);
					}
					
                    $custom_fields[$custom_field_name]['control'] .= '
                        <select name="'.$field_name.'[app_id]" onchange="var $el=$(this); $.post(\''.IB_PLUGIN_URL.'ajax/get_additional_fields.php\', {action: \'select_app\', app: $(this).val(), field_name: \''.$field_name.'\'}, function(response) {
                            $el.next().html(response);
                        })">
                            <option value="">Select app</option>
                            '.show_select_options($apps, 'object_id', 'display_name', $cfn['data']->app_id, false).'
                        </select>
                        <span>
                            <select name="'.$field_name.'[app_version]" onchange="var $el=$(this); $.post(\''.IB_PLUGIN_URL.'ajax/get_additional_fields.php\', {action: \'select_version\', version: $(this).val(), field_name: \''.$field_name.'\'}, function(response) {
                                $el.next().html(response);
                            });">>
                                <option value="">Select version</option>
                                '.show_select_options($versions[TYPE_APP_DATA], '', 'display_name', $cfn['data']->app_version, false).'
                            </select>
                            <span>
                                <select name="'.$field_name.'[app_page]">
                                    <option value="">Select page</option>
                                    '.show_select_options($pages, '', 'name', $cfn['data']->app_page, false).'
                                </select>
                            </span>
                        </span>
                    ';
                break;
            }
        $custom_fields[$custom_field_name]['control'].= '</div>';
    }

	if (!is_fx_error($current_object)) {
		$custom_fields['uuid']['control'] = '
			<select name="uuid">
				<option value="">Please select</option>
				'.show_select_options(get_objects_by_type(TYPE_IBEACON_UUID), 'uuid', 'display_name', $current_object['uuid'], false).'
			</select>';


        custom_proximity_field('proximity_immediate');
        custom_proximity_field('proximity_near');
        custom_proximity_field('proximity_far');
        custom_proximity_field('proximity_unknown');

	}

	$options = array('fields' => array('object_id','created','modified','display_name','uuid','minor','proximity_immediate','proximity_near','proximity_far','proximity_unknown'),
					 'custom_fields' => $custom_fields,
					 'buttons' => array('update','reset','cancel','delete'));

	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));
	
	fx_show_metabox($mb_data);
	
	$options = array('set_id' => 0,
					 'filter_system' => false,
					 'object_type_id' => TYPE_IBEACON,
					 'fields' => array('display_name', 'uuid', 'major', 'minor', 'description'),
					 'actions' => array('view','edit','remove'));	
	
	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));
	
	fx_show_metabox($mb_data);