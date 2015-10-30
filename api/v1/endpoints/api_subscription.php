<?php
/*
Version: 0.1
API Method Name: Subscriptions
Description: Allows to manage user subscription by main server
Author: Flexiweb
Arguments:
*/

class FX_API_Subscription extends FX_API
{	
	protected function _get()
	{
		$this -> permission = intval($this -> user_instance['schema_permissions'][$this->schema_id][TYPE_SUBSCRIPTION]);

		if (!$this -> is_admin && !($this -> permission & U_GET)) {
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}
		
		switch ($this -> verb)
		{
			/**
			* @api {get} /subscription/id
			* @apiVersion 0.1.0
			* @apiName Get Subscription by User ID
			* @apiGroup Subscription
			* @apiDescription Get user subscription by its global User ID
			*
			* @apiParam {Number} user_id* Global user ID
			*
			* @apiSuccess {Array} Array with subscription fields
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'id':
				$sfx_id = get_sfx_by_user_id($this -> args['user_id']);
								
				if (is_fx_error($sfx_id)) {
					return $sfx_id;
				}
				
				return array('object_type_id' => TYPE_SUBSCRIPTION, 'object_id' => (int)$sfx_id);
			break;
			/**
			* @api {get} /subscription/api_key
			* @apiVersion 0.1.0
			* @apiName Get Subscription by API Key
			* @apiGroup Subscription
			* @apiDescription Get user subscription by its API Key
			*
			* @apiParam {Number} api_key* User API Key
			*
			* @apiSuccess {Array} Array with subscription fields
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/

            case 'api_key':
                $sfx_id = get_sfx_base_by_api_key($this -> args['user_api_key']);

				if (is_fx_error($sfx_id)) {
					return $sfx_id;
				}

				return array('object_type_id' => TYPE_SUBSCRIPTION, 'object_id' => (int)$sfx_id['subscription_id'], 'user_id' => (int)$sfx_id['user_id']);
            break;
			default:
				return new FX_Error(__CLASS__, 'Invalid verb value.');
		}
	}	

	/**
	* @api {put} /subscription 
	* @apiVersion 0.1.0
	* @apiName Update Subscription
	* @apiGroup Subscription
	* @apiDescription Update API keys in the existing user subscription 
	*
	* @apiParam {Number} user_id* Global user ID
	* @apiParam {String} api_key* API Key generated on FlexiLogin
	* @apiParam {String} secret_key* Secret Key generated on FlexiLogin
	*
	* @apiSuccess {String} API Key of user subscription. It will be equal to passed keys in case of success
	*
	* @apiError {error} Error Message
	*
	* @apiEnd
	*/

    protected function _put()
    {
        $args = $this -> args;

        if (!$args['user_id']) {
			return new FX_Error(__METHOD__, _('Unknown user identifier'));
		}
		
		if (!$args['user_api_key']) {
			return new FX_Error(__METHOD__, _('Please set API Key for user subscription'));
		}

		if (!$args['user_secret_key']) {
			return new FX_Error(__METHOD__, _('Please set Secret Key for user subscription'));
		}
		
		$sfx_id = get_sfx_by_user_id($args['user_id']);
		
		if (!is_numeric($sfx_id)) {
			return new FX_Error(__METHOD__, _('Unable ot get user subscription by specified User ID'));
		}

		$sfx_object = get_object(TYPE_SUBSCRIPTION, $sfx_id);

		$sfx_object['api_key'] = $args['user_api_key'];
		$sfx_object['secret_key'] = $args['user_secret_key'];

		if (is_fx_error(update_object($sfx_object))) {				
			return new FX_Error(__METHOD__, _('Unable to update existing user subscription'));
		}

		clear_user_cache();
		
		$keys = array(
			'api_key' => $sfx_object['api_key'], 
			'secret_key' => $sfx_object['secret_key']);

        return $keys;	
	}

	/**
	* @api {post} /subscription 
	* @apiVersion 0.1.0
	* @apiName Add Subscription
	* @apiGroup Subscription
	* @apiDescription Create a new user subscription with passed api_key or get api_key of existing subscription
	*
	* @apiParam {Number} user_id* Global user ID
	* @apiParam {Number} schema_id* Schema ID on current server which is associated with Channel ID on Fleilogin server 
	* @apiParam {String} api_key* API Key generated on FlexiLogin
	* @apiParam {String} secret_key* Secret Key generated on FlexiLogin
	*
	* @apiSuccess {String} API Key of user subscription. It will be equal to passed keys in case of success
	*
	* @apiError {error} Error Message
	*
	* @apiEnd
	*/

    protected function _post()
    {
        $args = $this -> args;

        if (!$args['user_id']) {
			return new FX_Error(__METHOD__, _('Unknown user identifier'));
		}

        if (!$args['schema_id']) {
			return new FX_Error(__METHOD__, _('Unknown Data Schema identifier'));
		}
		
		if (!$args['user_api_key']) {
			return new FX_Error(__METHOD__, _('Please set API Key for user subscription'));
		}

		if (!$args['user_secret_key']) {
			return new FX_Error(__METHOD__, _('Please set Secret Key for user subscription'));
		}

        $schema_object = get_object(TYPE_DATA_SCHEMA, $args['schema_id']);

        if (is_fx_error($schema_object)) {
			return new FX_Error(__METHOD__, _('Data Schema not found'));
		}

        if ($sfx_fields_type = $schema_object['user_fields']) {
			
            $sfx_fields = get_type_fields($sfx_fields_type, "custom");

			if (is_fx_error($sfx_fields)) {
				return $sfx_fields;
			}

            if ($sfx_fields && !$args['sfx_fields']) {
				return new FX_Error(__METHOD__, _('Specify additional subscription fields'));
			}
        }

		$sfx_id = get_sfx_by_user_id($args['user_id']);

	   	if (is_fx_error($sfx_id)) {

            $sfx_object = array();

            $sfx_object['name'] = 'sfx_'.$args['user_id'];
            $sfx_object['display_name'] = $args['display_name'] ? $args['display_name'] : 'sfx_'.$args['user_id'];
            $sfx_object['schema_id'] = 0;
            $sfx_object['set_id'] = 0;
            $sfx_object['object_type_id'] = TYPE_SUBSCRIPTION;
            $sfx_object['user_id'] = $args['user_id'];
            $sfx_object['api_key'] = $args['user_api_key'];
			$sfx_object['secret_key'] = $args['user_secret_key'];
            $sfx_object['roles'] = $default_roles !== null ? array($args['schema_id'] => $default_schema_roles) : '';

            $sfx_id = add_object($sfx_object);

            if (is_fx_error($sfx_id)) {
				return $sfx_id;
			}
        }
        else {
			
			$sfx_object = get_object(TYPE_SUBSCRIPTION, $sfx_id);
			
			if (is_fx_error($sfx_object)) {
				return $sfx_object;
			}
			
			$update = false;
			
			if ($sfx_object['api_key'] != $args['user_api_key']) {
				$sfx_object['api_key'] = $args['user_api_key'];
				$update = true;
			}
			
			if ($sfx_object['secret_key'] != $args['user_secret_key']) {
				$sfx_object['secret_key'] = $args['user_secret_key'];
				$update = true;
			}
			
			$display_name = $args['display_name'] ? $args['display_name'] : 'sfx_'.$args['user_id'];
			
			if ($sfx_object['display_name'] != $display_name) {
				$sfx_object['display_name'] = $display_name;
				$update = true;
			}

			if ($update) {
				$res = update_object($sfx_object);
				if (is_fx_error($res)) {				
					return new FX_Error(__METHOD__, _('Unable to update existing user subscription'));
				}
			}

            $sfx_id = $sfx_object['object_id'];
        }

        //Add additional fields object
        if ($sfx_fields_type && $sfx_fields) {

            $sfx_fields_object = (array)json_decode($args['sfx_fields']);
            $sfx_fields_object['object_type_id'] = $sfx_fields_type;
            $name = 'sfx_fields_'.$sfx_id;

            if (!$sfx_fields_object_id = object_exists($sfx_fields_type, $name, 0)) {
                $sfx_fields_object['schema_id'] = $args['schema_id'];
                $sfx_fields_object['set_id'] = 0;
                $sfx_fields_object['name'] = $sfx_fields_object['display_name'] = $name;
                $sfx_fields_object = array_merge($sfx_fields_object, $args['sfx_fields']);

                $sfx_fields_object_id = add_object($sfx_fields_object, true);

                if (is_fx_error($sfx_fields_object_id)) {
					return $sfx_fields_object_id;
				}
            }
            else {
                $sfx_fields_object['object_id'] = $sfx_fields_object_id;
                $update_res = update_object($sfx_fields_object, true);
                if (is_fx_error($update_res)) {
					return $update_res;
				}
            }

            add_link_type(TYPE_SUBSCRIPTION, $sfx_fields_type, RELATION_1_N, $args['schema_id'], false);
            add_link(TYPE_SUBSCRIPTION, $sfx_id, $sfx_fields_type, $sfx_fields_object_id, $sfx_object['api_key']);
        }
		
		if ($schema_object['roles']) {
			$default_schema_roles = (array)json_decode($schema_object['roles'], true);
			
			for ($i=0; $i<count($default_schema_roles); $i++) {
				$result = add_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $default_schema_roles[$i]);
				if (is_fx_error($result)) {
					return new FX_Error(__METHOD__, _('Unable to update user roles'));
				}
			}
		}
		
		$result = add_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_DATA_SCHEMA, $args['schema_id']);

		if (is_fx_error($result)) {
			return new FX_Error(__METHOD__, _('Unable to link with Data Schema'));
		}

		clear_user_cache();

		$keys = array(
			'api_key' => $sfx_object['api_key'], 
			'secret_key' => $sfx_object['secret_key']);

        return $keys;
    }

	/**
	* @api {delete} /subscription 
	* @apiVersion 0.1.0
	* @apiName Delete Subscription
	* @apiGroup Subscription
	* @apiDescription Delete link between user subscription and data schema associated with some channel and delete subscription objects of there is no linked data sets anymore
	*
	* @apiParam {Number} api_key* User API key to check permissions and find subscription object to delete
	* @apiParam {Number} schema_id* Schema ID on current server which is associated with Channel ID on Fleilogin server 
	*
	* @apiSuccess {true} User Subscription was successfully deleted.
	*
	* @apiError {error} Error Message
	*
	* @apiEnd
	*/
    protected function _delete()
    {
        $args = $this -> args;

		if (!$args['user_id']) {
			return new FX_Error(__METHOD__, _('Unknown user identifier'));
		}

		if ($this -> verb == 'sub_channel')
		{			
			if (!object_exists(TYPE_DATA_SET, $args['set_id'])) {
				return new FX_Error(__METHOD__, _('Invalid Data Set identifier'));
			}

			$return_default_sfx = false;
					
			$sfx_id = get_sfx_by_user_id(intval($this -> args['user_id']), $return_default_sfx);

			if (is_fx_error($sfx_id)) {
				if ($sfx_id->get_error_code() == 'sfx_not_found') {
					return new FX_Error(__METHOD__, _('User has no subscription on the current FlexiDB server'));
				}
				else {
					return new FX_Error(__METHOD__, _('Unable to get subscription by User ID'));			
				}
			}

			$links = get_actual_links(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $_SESSION['current_schema']);		
			
			if (is_fx_error($links)) {
				return new FX_Error(__METHOD__, $links->get_error_messages());
			}

			foreach ($links[TYPE_ROLE] as $role_id => $link) {	
			
				$is_data_set_role = get_object_field(TYPE_ROLE, $role_id, 'data_set_role');

				if ($is_data_set_role == 1) {

					$meta = unserialize($link['meta']);
					$sfx_sets = array_flip(is_array($meta) ? $meta : (array)$link['meta']);
					
					unset($sfx_sets[$args['set_id']]);
					
					$result = update_link_meta(TYPE_SUBSCRIPTION, $sfx_id, TYPE_ROLE, $role_id, array_flip($sfx_sets));
					
					if (is_fx_error($result)) {
						return $result;
					}
				}
			}

			return true;
		}
		else
		{
			if (!$args['schema_id']) {
				return new FX_Error(__METHOD__, _('Unknown Data Schema identifier'));
			}			

			$schema_object = get_object(TYPE_DATA_SCHEMA, $args['schema_id']);

			if (is_fx_error($schema_object)) {
				return new FX_Error(__METHOD__, _('Data Schema not found'));
			}

			if (!object_exists(TYPE_DATA_SCHEMA, $args['schema_id'])) {
				return new FX_Error(__CLASS__, _('Data Schema not found'));
			}

			$sfx_id = get_sfx_by_user_id($args['user_id']);

			if (link_exists(TYPE_SUBSCRIPTION, $sfx_id, TYPE_DATA_SCHEMA, $args['schema_id'])) {
				$result = delete_link(TYPE_SUBSCRIPTION, $sfx_id, TYPE_DATA_SCHEMA, $args['schema_id']);
				if (is_fx_error($result)) {
					return $result;
				}
			}
			else {
				return new FX_Error(__METHOD__, _('You are not subscribed to this channel'));
			}
	
			if ($sfx_fields_type = $schema_object['user_fields']) {
				if ($sfx_fields_object_id = object_exists($sfx_fields_type, 'sfx_fields_'.$sfx_id, 0)) {
					delete_link(TYPE_SUBSCRIPTION, $sfx_id, $sfx_fields_type, $sfx_fields_object_id);
					delete_object($sfx_fields_type, $sfx_fields_object_id);
				}
			}			
		}
		
		clear_user_cache();
        return true;
    }
}