<?php

	function _ctrl_default_user_roles($roles = array(), $schema_id = 0)
	{
		$schema_roles = get_objects_by_type(TYPE_ROLE, $schema_id ? $schema_id : $_SESSION['current_schema']);
		
		$out = "<h2><strong>"._('Select default user roles for new subscribers').":</strong></h2>";
		
		if (is_fx_error($schema_roles)) {
			return $schema_roles -> get_error_message();
		}
	
		if ($schema_roles) {
			foreach ((array)$schema_roles as $object) {
				if ($object['data_set_role']) {
					continue;
				}
				$object_id = $object['object_id'];
				$checked = in_array($object_id, (array)$roles) ? " checked=\"checked\"" : "";
				$out .= "
				<label class=\"role-btn\" for=\"field_$object_id\">
					<input id=\"field_$object_id\" name=\"roles[]\" type=\"checkbox\" value=\"$object_id\"$checked>
					".$object['display_name']."
				</label>\n";
			}
		}
		else {
			$out .= "<font color=\"#CCC\">"._('No roles available in current Data Schema')."</font>";
		}
		
		$out .= '<a href="'.URL.'schema_admin/schema_roles">Add Role</a>';

		return $out;
	}
	
	function _ctrl_user_fields($object_type_id, $schema_id = 0)
	{
		$types = get_schema_types($schema_id ? $schema_id : $_SESSION['current_schema'], 'base');
		
		if (!is_fx_error($types)) {
			$out = "
			<h2><strong>"._('Select type with additional user fields for this Data Schema').":</strong></h2>
			<select id=\"field_user_fields\" name=\"user_fields\">
				<option value=\"0\">"._('None')."</option>
				".show_select_options($types, 'object_type_id', 'display_name', $object_type_id, false)."
			</select>";
			
			if ($object_type_id) {
				$out .= "&nbsp;&nbsp;<a href=\"".URL."design_editor/design_types?schema_id=$schema_id&object_type_id=$object_type_id\">Edit type</a><br>";
			}
		}
		else {
			$out = "<font color=\"#CCC\">"._('No types available in current Data Schema')."</font>";
		}

		return $out;
	}
	
	function _ctrl_sfx_alias($current_object)
	{
		//TODO: make it dynamical

		$type_fields = get_type_fields($current_object['user_fields'], 'custom');
		
		if (is_fx_error($type_fields)) {
			return _('Select additional user fields type and update object to select subscription alias');
		}

		if (!$type_fields) {
			return _('Selected additional fields type have no fields');
		}

		return "
		<select id=\"field_sfx_alias\" name=\"sfx_alias\">
			<option value=\"0\">"._('None')."</option>
			".show_select_options($type_fields, 'name', 'name', $current_object['sfx_alias'], false)."
		</select><div class=\"hint\" title=\"Subscription name which will be shown instead on \"></div><br>";
	}

	if (isset($_REQUEST['object_id']) && $_REQUEST['object_id'] != $_SESSION['current_schema']) {
		set_current_fx_dir(intval($_REQUEST['object_id']), 0);
		fx_redirect();
	}
	
	global $data_schema;

	if ($data_schema) {
		$custom_fields = array();

		$roles = json_decode($current_schema['roles'], true);

		$custom_fields['roles']['control'] = _ctrl_default_user_roles($roles !== null ? $roles : array(), $data_schema['object_id']);
		$custom_fields['user_fields']['control'] = _ctrl_user_fields($data_schema['user_fields'], $data_schema['object_id']);
		$custom_fields['sfx_alias']['control'] = _ctrl_sfx_alias($data_schema);
		$custom_fields['channel']['control'] = '<a href="'.URL.'schema_admin/schema_channel">'._('Edit Channel').'</a>';
		$custom_fields['app_group']['control'] = '<a href="'.URL.'app_editor/app_group">'._('Edit App Group').'</a>';	
		$custom_fields['icon']['control'] = '<a href="'.URL.'schema_admin/schema_icon">'._('Edit Icon').'</a>';
		
		$sub_chnl_alias = $data_schema['sub_chnl_alias'];
		$sub_chnl_alias .= $data_schema['sub_chnl_alias_pl'] ? " ({$data_schema[sub_chnl_alias_pl]})" : '';

		$custom_fields['sub_chnl_alias']['control'] = '<input value="'.$sub_chnl_alias.'" disabled="disabled">&nbsp;<a href="'.URL.'schema_admin/schema_channel?edit_sub_chnl_alias">'._('Edit').'</a>';
		$custom_fields['sub_chnl_alias_pl'] = false; //hide
	}

	$options = array('object_type_id' => TYPE_DATA_SCHEMA,
					 'object_id' => $data_schema ? $data_schema['object_id'] : 0,
					 'fields' => array('object_id','created','modified','name','display_name'),
					 'custom_fields' => $custom_fields,
					 'buttons' => array('update','reset','delete'));

	$mb_data = array('header' => array('suffix' => $data_schema ? ' - '.$data_schema['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);

	$options = array('set_id' => 0,
					 'object_type_id' => TYPE_DATA_SCHEMA,
					 'fields' => array('object_id','display_name','name'),
					 'actions' => array('view','edit'));

	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);