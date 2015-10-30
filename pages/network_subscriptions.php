<?php


	function _form_update_permissions($object_array)
	{
		if (is_fx_error($object_array)) {
			return $object_array;
		}
		
		if (!$_SESSION['current_schema']) {
			return $object_array;
		}
		
		$sfx_id = $object_array['object_id'];

		$sfx_roles = get_sfx_roles($sfx_id);

		if (is_fx_error($old_roles)) {
			return $old_roles;
		}			

		$sfx_links = get_actual_links(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $_SESSION['current_schema']);

		if (is_fx_error($old_roles)) {
			return $sfx_links;
		}
		
		$sfx_links = $sfx_links[TYPE_ROLE];
		
		$old_schema_roles = (array)$sfx_roles['roles_schema'][$_SESSION['current_schema']];
		$sfx_id = $object_array['object_id'];

		//Update data schema roles
		foreach ((array)$object_array['roles']['schema'] as $role_id) {
			if (!isset($sfx_links[$role_id])) {
				add_link_type(TYPE_SUBSCRIPTION, TYPE_ROLE, 4, 0, true);
				add_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id);
			}
			unset($old_schema_roles[$role_id]);
		}

		foreach ($old_schema_roles as $role_id => $role) {
			delete_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id);
		}		

		//Update data set roles
		if ($_SESSION['current_set']) {

			$old_set_roles = (array)$sfx_roles['roles_set'][$_SESSION['current_set']];

			foreach ((array)$object_array['roles']['set'] as $role_id)
			{
				if (isset($sfx_links[$role_id])) {
					$meta = unserialize($sfx_links[$role_id]['meta']);
					$link_meta = is_array($meta) ? $meta : (array)$sfx_links[$role_id]['meta'];

					if (!in_array($_SESSION['current_set'], $link_meta)) {
						$link_meta[] = $_SESSION['current_set'];
						sort($link_meta);
						update_link_meta(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id, $link_meta);
					}
				}
				else {
					$result = add_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id, $_SESSION['current_set']);
				}	
				
				unset($old_set_roles[$role_id]);		
			}

			foreach ($old_set_roles as $role_id => $role) {

				$meta = unserialize($sfx_links[$role_id]['meta']);
				$link_meta = is_array($meta) ? $meta : (array)$sfx_links[$role_id]['meta'];

				$index = array_search($_SESSION['current_set'], $link_meta);
				
				if ($index !== false) {
					unset($link_meta[$index]);
				
					if ($link_meta) {
						update_link_meta(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id, $link_meta);
					}
					else {
						delete_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id);
					}
				}
			}	
		}
		
		$object_array['roles'] = 1;
	
		return $object_array;
	}
	
	add_action('fx_object_form_update', '_form_update_permissions', 1, 1);
	
	function _make_user_admin($object_array)
	{
		$default_sfx = get_default_sfx();
		
		if ($object_array['object_id'] === $default_sfx['subscription_id'] && $object_array['is_admin']) {
			$object_array['is_admin'] = 0;
			return new FX_Error(__FUNCTION__, _('Guest user cannot be the admin'));
		}

		return $object_array;
	}
	
	add_action('fx_object_form_update', '_make_user_admin', 1, 1);

	function _roles_control($subscription_id)
	{
		if (!$_SESSION['current_schema']) {
			return '<font color="#FF0000">'._('Select Data Schema to view/edit user roles').'</font>';
		}

		$sfx_roles = get_actual_links(TYPE_SUBSCRIPTION, $subscription_id, TYPE_ROLE, $_SESSION['current_schema']);
		
		if (is_fx_error($sfx_roles)) {
			$out .= '<font color="#FF0000">'.$sfx_roles->get_error_message().'</font>';
		}

		$roles = array();

		foreach (get_objects_by_type(TYPE_ROLE, $_SESSION['current_schema']) as $role_id => $role)
		{
			$linked = isset($sfx_roles[TYPE_ROLE][$role_id]);
			
			if (!$role['data_set_role']) {
				$roles['schema'][$role_id] = array('display_name' => $role['display_name'], 'checked' => $linked ? ' checked="checked"' : '');
			}
			else {
				$meta = unserialize($sfx_roles[TYPE_ROLE][$role_id]['meta']);
				$sfx_sets = is_array($meta) ? $meta : (array)$sfx_roles[TYPE_ROLE][$role_id]['meta'];
				$roles['set'][$role_id] =  array('display_name' => $role['display_name'], 'checked' => in_array($_SESSION['current_set'], $sfx_sets) ? ' checked="checked"' : '');
			}
		}
		
		$out = '';

		// Data Schema Roles
		//==========================================================================
		$out .= '<p><i>'._('Data Schema Roles').'</i>:&nbsp;';

		if ($_SESSION['current_schema']) {
			if (!count($roles['schema'])) {
				$out .= '<font color="#ccc">n/a</font>';
			}
			else {
				foreach ($roles['schema'] as $id=>$role) {
					$out .= '
					<label for="role-'.$id.'" class="role-btn">
						<input id="role-'.$id.'" name="roles[schema][]" type="checkbox" value="'.$id.'"'.$role['checked'].'>
						'.$role['display_name'].'
					</label>&nbsp;';
				}
			}
		}
		else {
			$out .= '<font color="#FF0000">'._('Select Data Schema to view/edit user roles').'</font>';
		}
		
		// Data Set Roles
		//==========================================================================
		
		$out .= '<p><i>'._('Data Set Roles').'</i>:&nbsp;';
		
		if ($_SESSION['current_set']) {
			if (!count($roles['set'])) {
				$out .= '<font color="#ccc">n/a</font>';
			}
			else {
				foreach ($roles['set'] as $id=>$role) {
					$out .= '
					<label for="role-'.$id.'" class="role-btn">
						<input id="role-'.$id.'" name="roles[set][]" type="checkbox" value="'.$id.'"'.$role['checked'].'>
						'.$role['display_name'].'
					</label>&nbsp;';
				}
			}
		}
		else {
			$out .= '<font color="#FF0000">'._('Select Data Set to view/edit user roles').'</font>';
		}
		
		return $out;
	}

	$current_object = get_current_object();

	if (!is_fx_error($current_object)) {
		$custom_fields = $custom_buttons = array();
		$custom_fields['roles']['control'] = _roles_control($current_object['object_id']);
		$custom_buttons[] = '<a class="button" target="_blank" href="https://flexilogin.com/users/'.$current_object['user_id'].'">'._('User Page').'</a>';
	}

	$options = array('object_type_id' => TYPE_SUBSCRIPTION,
					// 'mode' => 'view',
					 'custom_fields' => $custom_fields,
					 'custom_buttons' => $custom_buttons,
					 'fields' => array('object_id','created','modified'),
					 'buttons' => array('update', 'cancel', 'delete'));
	
	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);

	if (is_fx_error($current_object)) {
		$options = array('set_id' => 0,
						 'schema_id' => 0,
						 'object_type_id' => TYPE_SUBSCRIPTION,
						 'fields' => array('object_id', 'user_id', 'is_admin', 'name', 'display_name', 'api_key'),
						 'actions' => array('view', 'edit'));		
	
		$mb_data = array('header' => array('hidden' => true),
						 'body' => array('content' => object_explorer($options)),
						 'footer' => array('hidden' => true));

		fx_show_metabox($mb_data);
	}
	else {
		$mb_data = array('header' => array('content' => _('Links Explorer')),
						 'body' => array('content' => links_explorer(TYPE_SUBSCRIPTION, $current_object['object_id'], $_SESSION['current_schema'], 'system')),
						 'footer' => array('hidden' => true));
						 		
		fx_show_metabox($mb_data);
	}
?>