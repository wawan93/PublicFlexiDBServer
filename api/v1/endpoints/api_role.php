<?php
/*
Version: 0.1
API Method Name: Roles
Description: Data Set admin	 can manage data set roles
Author: Flexiweb
Arguments:
*/

class FX_API_Role extends FX_API
{
	
	/**
	* @api {GET} /role
	* @apiVersion 0.1.0
	* @apiName Roles
	* @apiDescription Get Users and theirs roles for the specified Data Set. Only Data Set admin can perform this action for his own Data Sets.
	*
	* @apiParam {Number*} set_id Data Set ID
	*
	* @apiSuccess {Array} Array with all data set roles existing in the data schema and all data set users with theirs roles from the schema roles list.
	* @apiSuccessExample
	* 	{
	*		"users": { "sfx_id" => {	"display_name" => [User Display Name],
	*									"user_id" => [Global User ID],
	*									"sfx_id" => [Subscription ID],
	*									"roles" => { role_id, .... }
	*								},
	*		....
	*		"roles" => { role_id, .... }
	*	}
	*
	* @apiError {error} Error Message
	*
	* @apiEnd
	*/
	protected function _get()
	{		
	//add_log_message(__METHOD__, print_r($this -> args, true));
		$set_id = (int)$this -> args['set_id'];
		$user_id = (int)$this -> args['user_id'];

		if (!$set_id) {
			return new FX_Error('access_forbidden', _('Specify Data Set ID (set_id)'));
		}

		if (!$this -> is_admin && !(in_array($set_id, $this -> user_instance['sets']))) {			
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}
		
		$roles = get_set_roles($set_id);
		
		switch ($this -> verb)
		{
			case 'by_set':			
				return $roles;
			break;
			case 'by_user':
			case 'by_users':
				if (is_fx_error($roles)) {
					return new FX_Error(__METHOD__, $roles->get_error_message());
				}

				$result = array('users' => array(), 'roles' => array());
		
				foreach ($roles as $role_id => $role) {
					$result['roles'][$role_id] = $role['display_name'];
		
					$linked_users = get_actual_links(TYPE_ROLE, $role_id, TYPE_SUBSCRIPTION);
		
					if (is_fx_error($linked_users)) {
						return new FX_Error(__METHOD__, _('Unable to get users with the specified role').' ['.$role_id.']');
					}
		
					if ($user_id) {
						foreach ($linked_users[TYPE_SUBSCRIPTION] as $sfx_id => $link_data) {
							if (in_array($set_id, (array)$link_data['meta'])) {
								if ($user_id == (int)get_object_field(TYPE_SUBSCRIPTION, $sfx_id, 'user_id')) {
									$result['users'][$user_id]['display_name'] = $link_data['display_name'];
									$result['users'][$user_id]['sfx_id'] = $sfx_id;
									$result['users'][$user_id]['roles'][] = $role_id;
								}
							}
						}
					}
					else {
						foreach ($linked_users[TYPE_SUBSCRIPTION] as $sfx_id => $link_data) {
							if (in_array($set_id, (array)$link_data['meta'])) {
								$result['users'][$sfx_id]['display_name'] = $link_data['display_name'];
								$result['users'][$sfx_id]['user_id'] = (int)get_object_field(TYPE_SUBSCRIPTION, $sfx_id, 'user_id');
								$result['users'][$sfx_id]['sfx_id'] = $sfx_id;
								$result['users'][$sfx_id]['roles'][] = $role_id;					
							}
						}					
					}
				}
				
				return $result;			
			break;
			default:
				return new FX_Error(__METHOD__, _('Invalid verb value'));		
		}
	}

	/**
	* @api {PUT} /role
	* @apiVersion 0.1.0
	* @apiName Roles
	* @apiDescription Append role to the user within the specified data set.
	*
	* @apiParam {Number*} set_id Data Set ID
	* @apiParam {Number*} role_id Role ID
	* @apiParam {Number} sfx_id Subscription ID if User ID is empty
	* @apiParam {Number} user_id User ID if Subscription ID is empty
	*
	* @apiSuccess {True} Data Set role successfully appended to the user subscription.
	*
	* @apiError {error} Error Message
	*
	* @apiEnd
	*/	
	
	protected function _put()
	{
		$set_id = (int)$this -> args['set_id'];
		$role_id = (int)$this -> args['role_id'];
		$user_id = (int)$this -> args['user_id'];

		if (!$set_id) {
			return new FX_Error('access_forbidden', _('Specify Data Set ID (set_id)'));
		}

		$set_object = get_object(TYPE_DATA_SET, $set_id);
		
		if (is_fx_error($set_object)) {
			return new FX_Error(__METHOD__, _('Specified Data Set does not exists'));
		}		

		if (!$this -> is_admin && !(in_array($set_id, $this -> user_instance['sets']))) {
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}
		
		$possible_roles = get_set_roles($set_id);

		if (is_fx_error($possible_roles)) {
			return new FX_Error(__METHOD__, $possible_roles->get_error_message());
		}
		
		if (!isset($possible_roles[$role_id])) {
			return new FX_Error(__METHOD__, _('Specified role does not belong to specified Data Set'));
		}

		$is_data_set_role = get_object_field(TYPE_ROLE, $role_id, 'data_set_role');

		if (is_fx_error($is_data_set_role)) {
			return new FX_Error(__METHOD__.'.access_forbidden', _('Unable to get specified Role'));
		}
		
		if (!$is_data_set_role) {
			return new FX_Error(__METHOD__.'.access_forbidden', _('You cannot edit specified role. Not Data Set role.'));
		}
		
		if (!$sfx_id && !$user_id) {
			return new FX_Error(__METHOD__, _('Specify User ID (user_id) or Subscription ID (sfx_id)'));
		}
		
		$sfx_id = get_sfx_by_user_id($user_id);	

		if (is_fx_error($sfx_id)) {
			if ($sfx_id->get_error_code() == 'sfx_not_found') {
				return new FX_Error(__METHOD__, _('User has no subscription on the current FlexiDB server'));
			}
			else {
				return new FX_Error(__METHOD__, _('Unable to get subscription by User ID'));			
			}
		}

		if (link_exists(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id)) {
			
			$link_meta = get_link_meta(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id);
			
			if (is_fx_error($link_meta)) {
				return new FX_Error(__METHOD__, $link_meta->get_error_message());
			}

			$link_meta = is_array($link_meta) ? $link_meta : array((int)$link_meta);
			
			if (!in_array($set_id, $link_meta)) {
				$link_meta[] = $set_id;
			}
			else {
				return true;
			}
			
			sort($link_meta);

			$result = update_link_meta(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id, $link_meta);
		}
		else {

			if (!link_exists(TYPE_SUBSCRIPTION, $sfx_id, TYPE_DATA_SCHEMA, $set_object['schema_id'])) {
				return new FX_Error(__METHOD__, _('User not subscribed to the main channel'));
			}
			
			$result = add_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id, $set_id);
		}

		if (is_fx_error($result)) {
			return new FX_Error(__METHOD__, _('Unable to link specified user subscription and role'));
		}

		return true;
	}

	/**
	* @api {DELETE} /role
	* @apiVersion 0.1.0
	* @apiName Roles
	* @apiDescription Romove role from the user .
	*
	* @apiParam {Number*} set_id Data Set ID
	* @apiParam {Number*} role_id Role ID
	* @apiParam {Number} sfx_id Subscription ID if User ID is empty
	* @apiParam {Number} user_id User ID if Subscription ID is empty
	*
	* @apiSuccess {True} Data Set role successfully deleted from the user subscription.
	*
	* @apiError {error} Error Message
	*
	* @apiEnd
	*/	
	protected function _delete()
	{
		//add_log_message(__METHOD__, print_r($this -> args, true));
		
		$set_id = (int)$this -> args['set_id'];
		$role_id = (int)$this -> args['role_id'];
		$sfx_id = (int)$this -> args['sfx_id'];
		$user_id = (int)$this -> args['user_id'];

		if (!$set_id) {
			return new FX_Error('access_forbidden', _('Specify Data Set ID (set_id)'));
		}

		if (!$this -> is_admin && !(in_array($set_id, $this -> user_instance['sets']))) {
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}
		
		$possible_roles = get_set_roles($set_id);

		if (is_fx_error($possible_roles)) {
			return new FX_Error(__METHOD__, $possible_roles->get_error_message());
		}
		
		if (!isset($possible_roles[$role_id])) {
			return new FX_Error(__METHOD__, _('Specidied role Role does not belong to specified Data Set'));
		}

		$is_data_set_role = get_object_field(TYPE_ROLE, $role_id, 'data_set_role');

		if (is_fx_error($is_data_set_role)) {
			return new FX_Error('access_forbidden', _('Unable to get specified Role'));
		}
		
		if (!$is_data_set_role) {
			return new FX_Error('access_forbidden', _('You cannot edit specified role. Not Data Set role.'));
		}
		
		if (!$sfx_id && !$user_id) {
			return new FX_Error(__METHOD__, _('Specify User ID (user_id) or Subscription ID (sfx_id)'));
		}

		if (!$sfx_id && $user_id) {
			$sfx_id = get_sfx_by_user_id($user_id);	

			if ($sfx_id->get_error_code() == 'sfx_not_found') {
				return new FX_Error(__METHOD__, _('User has no subscription on the current FlexiDB server'));
			}
			else {
				return new FX_Error(__METHOD__, _('Unable to get subscription by User ID'));			
			}
		}

		$link_meta = get_link_meta(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id);
		
		if (is_fx_error($link_meta)) {
			return new FX_Error(__METHOD__, $link_meta->get_error_message());
		}

		$link_meta = is_array($link_meta) ? $link_meta : array((int)$link_meta);
		$index = array_search($set_id, $link_meta);
		
		if ($index !== false) {
			unset($link_meta[$index]);
		}
		else {
			return true;
		}
		
		if ($link_meta) {
			$result = update_link_meta(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id, $link_meta);
		}
		else {
			$result = delete_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id);
		}

		if (is_fx_error($result)) {
			return new FX_Error(__METHOD__, _('Unable to link specified user subscription and role'));
		}

		return true;
	}
	
}