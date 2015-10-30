<?php

function get_sfx_by_user_id($user_id, $return_default = false)
{
	global $fx_db;
	
	if (!(int)$user_id) return new FX_Error(__FUNCTION__, _('Unknown user identifier'));

    $fx_db->
		select(DB_TABLE_PREFIX."object_type_".TYPE_SUBSCRIPTION, array('object_id'))->
		where(array('name'=>'sfx_'.$user_id))->
		limit(1);

	if (!is_fx_error($fx_db->select_exec())) {
		if ($sfx_object = $fx_db->get()) {
			return $sfx_object['object_id'];
		}
		else {
			if ($return_default) {
				$default_sfx = get_default_sfx();
				
				if (is_fx_error($default_sfx)) {
					return $default_sfx;
				}
				else {
					return $default_sfx['subscription_id'];
				}
			}
			else {
				return new FX_Error('sfx_not_found', _('User subscription does not exists'));
			}
		}
	}
	else {
		return $fx_db->get_last_error();
	}
}

function get_sfx_base_by_api_key($api_key = '', $return_default = true)
{
	global $fx_db;

	$sfx_type = TYPE_SUBSCRIPTION; 

    $result = $fx_db->
		select(DB_TABLE_PREFIX."object_type_".TYPE_SUBSCRIPTION, array('object_id', 'api_key', 'secret_key', 'user_id', 'is_admin'))->
		where(array('api_key'=>$api_key))->
		limit(1)->
		select_exec();

	if (!is_fx_error($result))
	{
		if ($sfx = $fx_db->get()) {			
			$sfx = array('object_type_id' => TYPE_SUBSCRIPTION,
						 'subscription_id' => $sfx['object_id'],
						 'api_key' => $sfx['api_key'],
						 'secret_key' => $sfx['secret_key'],
						 'user_id' => $sfx['user_id'],
						 'is_admin' => $sfx['is_admin'] ? true : false);

			return $sfx;
		}
		else {
			if ($return_default) {
				$default_sfx_id = insert_default_sfx();
				if (is_numeric($default_sfx_id)) {
					$sfx = get_object($sfx_type, $default_sfx_id);
					return array('object_type_id' => $sfx['object_type_id'],
								 'subscription_id' => $sfx['object_id'],
								 'api_key' => '',
								 'user_id' => $sfx['user_id'],
								 'is_admin' => $sfx['is_admin'] ? true : false);
				}
			}
			return new FX_Error(__FUNCTION__, _('Unable to get user subscription'));
		}
	}
	else {
		return $result;
	}				
}

function get_user_subscription($api_key = '', $return_default = true)
{
	if (caching_enabled()) {
		$cache_sfx = get_user_cache($api_key);
		if ($cache_sfx ) {
			return $cache_sfx;
		}
	}

	$sfx = get_sfx_base_by_api_key($api_key, $return_default);

	if (is_fx_error($sfx)) {
		return $sfx;
	}
	
	$sfx['schemas'] = $sfx['sets'] = $sfx['links'] = array();

	$user_links = get_actual_links(TYPE_SUBSCRIPTION, $sfx['subscription_id']);

	if (!is_fx_error($user_links)) {
		if ($user_links) {
			if (isset($user_links[TYPE_DATA_SCHEMA])) {
				$sfx['schemas'] = array_keys($user_links[TYPE_DATA_SCHEMA]);
				unset($user_links[TYPE_DATA_SCHEMA]);
			}
	
			if (isset($user_links[TYPE_DATA_SET])) {
				$sfx['sets'] = array_keys($user_links[TYPE_DATA_SET]);
				unset($user_links[TYPE_DATA_SET]);
			}
			
			if (isset($user_links[TYPE_ROLE])) {
				
				foreach ($user_links[TYPE_ROLE] as $role_id => $link_data) {
					if ($link_data['meta']) {
						$sfx['schema_roles'] = $roles['schema'];
					}
					else {
						$sfx['set_roles'] = $roles['set'];
					}
				}
				
				unset($user_links[TYPE_ROLE]);
			}
			
			foreach ($user_links as $linked_type => $object) {
				$sfx['links'][$linked_type] = array_keys($user_links[$linked_type]);
			}
		}
	}

	$roles = get_sfx_roles($sfx['subscription_id']);

	if (is_fx_error($roles)) {
		return $roles;
	}

	$sfx['schema_roles'] = $roles['roles_schema'];
	$sfx['set_roles'] = $roles['roles_set'];
	
	$sfx['schema_permissions'] = $roles['permissions_schema'];
	$sfx['set_permissions'] = $roles['permissions_set'];
	
	$sfx['roles'] = $roles['roles'];

	update_user_cache($sfx);

	return $sfx;
}

function get_default_sfx()
{
	return get_user_subscription('', true);
}

function insert_default_sfx($user = array())
{
	if (!$user) {
		$user['api_key'] = '';
		$user['secret_key'] = '';
		$user['name'] = 'sfx_guest';
		$user['display_name'] = 'Guest';
		$user['user_id'] = 0;
	}

	global $fx_db;

    $fx_db->
		select(DB_TABLE_PREFIX."object_type_".TYPE_SUBSCRIPTION, array('object_id'))->
		where(array('api_key'=>$user['api_key']))->
		limit(1);
	
	if (!is_fx_error($fx_db->select_exec())) {
		if (!$sfx = $fx_db->get()) {
			$created = time();

            $query = $fx_db->insert(DB_TABLE_PREFIX."object_type_".TYPE_SUBSCRIPTION,array(
                'schema_id' => 0,
                'set_id' => 0,
                'created' => $created,
                'modified' => $created,
                'name' => $user['name'],
                'display_name' => $user['display_name'],
                'user_id' =>$user['user_id'],
                'api_key' => $user['api_key'],
				'secret_key' => $user['secret_key'],
            ));

			if(!is_fx_error($query)) {
				return $fx_db -> lastInsertId();
			}
			else {
				return $query;
			}
		}
		else {
			return $sfx['object_id'];
		}
	}
	else {
		return new FX_Error(__FUNCTION__, _('Specified object type does not exist'));			
	}	
}

//*********************************************************************************
// VALIDATE SCRIPT USER  v1.0
// Checks whether the user can run the current script. If not, execution of the script is terminated.
//*********************************************************************************
function validate_script_user($msg = false)
{	
	$msg = $msg === false ? _('You have no permission to run this script') : $msg;

	$sesion_id = session_id();

	if (!current_user_logged_in()) {
		die($msg);
	}
	else {
		return true;
	}
}
//*********************************************************************************

function get_user_schemas($user_instance)
{
	$user_schemas = array();
		
	if ($user_instance['is_admin']) {
		foreach((array)get_objects_by_type(TYPE_DATA_SCHEMA) as $key => $value) {
			$user_schemas[$key] = array('name' => $value['name'], 'display_name' => $value['display_name']);
		}	
	}
	else {
		
		$subscription_type = TYPE_SUBSCRIPTION;
		$schema_type = TYPE_DATA_SCHEMA; 
		$links = get_actual_links($subscription_type, $user_instance['subscription_id'], $schema_type);
		
		foreach($links[$schema_type] as $key => $value) {
			$user_schemas[$key] = array('name' => $value['name'], 'display_name' => $value['display_name']);
		}
	}

	return $user_schemas;
}

function get_data_set_admins($data_set_id)
{
	$links = get_actual_links_simple(TYPE_DATA_SET, $data_set_id, TYPE_SUBSCRIPTION);
	
	$ids = array();
	
	if (!is_fx_error($links)) {
		
		global $fx_db;
		
		$fx_db->select(DB_TABLE_PREFIX."object_type_".TYPE_SUBSCRIPTION, 'user_id')->where(array('object_id IN'=>$links))->select_exec();
		
		foreach ($fx_db->get_all() as $v) {
			$ids[] = $v['user_id'];
		}
		
		return $ids;
	}
	
	return $ids;
}

function get_user_sets($user_instance, $schema_id)
{	
	$user_sets = array();
	
	if ($user_instance['is_admin']) {

		$schema_sets = get_objects_by_type(TYPE_DATA_SET, $schema_id);

		if (is_fx_error($schema_sets)) {
			return array();
		}
		
		foreach($schema_sets as $key => $value) {
			$user_sets[$key] = array('name' => $value['name'], 'display_name' => $value['display_name']);
		}	
	}
	else {	
		global $fx_db;
	
		$subscription_type = TYPE_SUBSCRIPTION;
		$set_type = TYPE_DATA_SET;
		$links = get_actual_links(TYPE_SUBSCRIPTION, $user_instance['subscription_id'], TYPE_DATA_SET);

        $fx_db->
			select( DB_TABLE_PREFIX."object_type_".TYPE_DATA_SET, array('object_id', 'name', 'display_name'))->
			where(array( 'schema_id'=>$schema_id, 'object_id IN'=>array_keys($links[TYPE_DATA_SET]) ));
	
		if (is_fx_error($fx_db->select_exec())) {
			return array();
		}

		foreach($fx_db->get_all() as $set) {
			$user_sets[$set['object_id']] = array('name' => $set['name'], 'display_name' => $set['display_name']);
		}
	}
		
	return $user_sets;
}

function get_user_sub_channels($sfx_id, $schema_id)
{	
	$user_sets = array();

	$links = get_actual_links(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $schema_id);

	if (is_fx_error($links)) {
		return array();
	}

	$data_sets = array();
	
	foreach ($links as $link) {
		$data_sets[] = $link['meta'];
	}
		
	return $data_sets;
}

function validate_api_key($api_key = '')
{
	if(!$api_key) return false;
	$api_key = preg_replace("{[^A-Z0-9]}",'',strtoupper($api_key));
	if(strlen($api_key) != 30) return false;
	return true;
}

function generate_password_hash($password, $salt = '')
{
	return sha1(md5($salt.sha1($password)));
}

function generate_session_id()
{
	if (defined('DFX_BASE_URL') && defined('DB_NAME')) {
		return false;//sha1(md5(DFX_BASE_URL.sha1(DB_NAME)));
	}
	else {
		return false;
	}
}

function fx_start_session()
{
	//if ($session_id = generate_session_id()) {
		$base_url = parse_url(DFX_BASE_URL);
		session_set_cookie_params (COOKIE_EXPIRE, $base_url['path'], $base_url['host']);	
		//session_id($session_id);
	//}

	session_start();
}

function current_user_logged_in()
{
	$session_id = session_id();

	$user_object = get_object(TYPE_DFX_USER, $_SESSION['user_id']);

	if (is_fx_error($user_object)) {
		return false;
	}
	
	if ($user_object['name'] == $_SESSION['username'] && $user_object['password'] == $_SESSION['password']) {
		return true;
	}

	return false;
}

function dfx_login($username, $password, $remember_me = false)
{
	if (!$username) {
		return new FX_Error(__FUNCTION__, _('Please enter username'));
	}
	
	if (!$password) {
		return new FX_Error(__FUNCTION__, _('Please enter password'));
	}
	
	global $fx_db;
	
	$object_type_id = TYPE_DFX_USER;

	if (is_fx_error($object_type_id)) {
		return new FX_Error(__FUNCTION__, $object_type_id->get_error_message());
	}

    $res = $fx_db->
		select($fx_db->get_type_table_name($object_type_id))->
		where(array('name'=>$username, 'password' => generate_password_hash($password)))->
		limit(1);

	if (!is_fx_error($fx_db->select_exec())) {
		if(!$user = $fx_db->get()) {
			return new FX_Error(__FUNCTION__, _('Wrong username or password'));
		}
	}
	else {
		return $fx_db->get_last_error();
	}
	
	$_SESSION['user_id'] = $user['object_id'];
	$_SESSION['username'] = $user['name'];
	$_SESSION['password'] = $user['password'];
	$_SESSION['display_name'] = $user['display_name'] ? $user['display_name'] : $user['name'];

	if ($remember_me) {					
		setcookie('fx_username', $username, COOKIE_EXPIRE);
		setcookie('fx_password', $password, COOKIE_EXPIRE);
	}		
	
	if (CONF_ENABLE_AUTH_LOG) {
		add_log_message('user_login', _('User').' ['.$_SESSION['username'].'] '._('successfully logged in').'. IP='.$_SERVER['REMOTE_ADDR']);
	}

	$dfx_version = _update_version_info();

	if (is_fx_error($dfx_version)) {
		add_log_message(__FUNCTION__, $dfx_version->get_error_message());
	}
}

add_action('fx_login', 'dfx_login', 100, 3);

function fx_logout()
{
	session_destroy();

	setcookie('fx_login', '');
	setcookie('fx_password', '');

	if (CONF_ENABLE_AUTH_LOG) {
		add_log_message('user_login', _('User').' ['.$_SESSION['username'].'] '._('logged out'));
	}
	
	do_actions('fx_after_logout');

	fx_redirect(URL);	
}

add_action('fx_logout', 'fx_logout', 100, 0);