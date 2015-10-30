<?php
/*
Version: 0.1
API Method Name: User
Description: Managing user options
Author: Flexiweb
Arguments:
*/

class FX_API_User extends FX_API
{
	protected function _get()
	{
		switch ($this -> verb)
		{
			/**
			* @api {get} /user/check_subscription
			* @apiVersion 0.1.0
			* @apiName Check User Subscription
			* @apiGroup User
			* @apiDescription Check if the user subscribed to the specified data schema
			*
			* @apiParam {Number} schema_id* Data Schema ID
			* @apiParam {Number} user_id* Global User ID
			*
			* @apiSuccess {true} User subscribed to the channel/schema.
			* @apiSuccess {false} User not subscribed to the channel/schema.
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'check_subscription':

				$schema_id = (int)$this -> args['schema_id'];
				$user_id = (int)$this -> args['user_id'];

				if (!$schema_id) {
					 return new FX_Error(__METHOD__, _('Unknown Data Schema ID'));
				}
				
				if (!$user_id) {
					 return new FX_Error(__METHOD__, _('Unknown User ID'));
				}
				
				$sfx_id = get_sfx_by_user_id($this -> args['user_id']);

				if (!is_numeric($sfx_id)) {
					return new FX_Error(__METHOD__, _('Unable to get user subscription'));
				}

				return link_exists(TYPE_DATA_SCHEMA, $schema_id, TYPE_SUBSCRIPTION, $sfx_id);

			break;
			/**
			* @api {get} /user/schemas
			* @apiVersion 0.1.0
			* @apiName Get User Schemas
			* @apiGroup User
			* @apiDescription Get all Data Schemas linked with current user
			*
			* @apiSuccess {Array} Array of Data Schemas (ID, Name, Display Name)
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'schemas':

				return get_user_schemas($this -> user_instance);

			break;
			/**
			* @api {get} /user/sets
			* @apiVersion 0.1.0
			* @apiName Get User Sets
			* @apiGroup User
			* @apiDescription Get all Data Sets linked with current user
			*
			* @apiSuccess {Array} Array of Data Sets (ID, Name, Display Name)
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'sets':

				if (!$this -> args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown Data Schema identifier'));
				}
				
				if (isset($this -> args['user_id'])) {
					
					$user_id = intval($this -> args['user_id']);
					
					$return_default_sfx = true; // Return default subscription if user have no subscription on the current server
					
					$sfx_id = get_sfx_by_user_id($user_id, $return_default_sfx);

					if (is_fx_error($sfx_id)) {
						return $sfx_id;
					}

					$api_key = get_object_field(TYPE_SUBSCRIPTION, $sfx_id, 'api_key');
				
					if (is_fx_error($api_key)) {
						return $api_key;
					}
				
					$user_instance = get_user_subscription($api_key, true);
					
					if (is_fx_error($user_instance)) {
						return $user_instance;
					}
				}
				else {
					$user_instance = $this -> user_instance;
				}

				return get_user_sets($user_instance, $this -> args['schema_id']);

			break;
			case 'sub_channels':

				if (!$this -> args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown Data Schema identifier'));
				}
				
				if (isset($this -> args['user_id'])) {
					
					$user_id = intval($this -> args['user_id']);
					
					$return_default_sfx = true; // Return default subscription if user have no subscription on the current server
					
					$sfx_id = get_sfx_by_user_id($user_id, $return_default_sfx);

					if (is_fx_error($sfx_id)) {
						return $sfx_id;
					}

					$api_key = get_object_field(TYPE_SUBSCRIPTION, $sfx_id, 'api_key');
				
					if (is_fx_error($api_key)) {
						return $api_key;
					}
				
					$user_instance = get_user_subscription($api_key, true);
					
					if (is_fx_error($user_instance)) {
						return $user_instance;
					}
				}
				else {
					$user_instance = $this -> user_instance;
				}

				return array_keys($user_instance['set_roles']);

			break;
			/**
			* @api {get} /user/fields
			* @apiVersion 0.1.0
			* @apiName Get User Additional Fields
			* @apiGroup User
			* @apiDescription Get user additianal fields for the current data schema
			*
			* @apiParam {Number} schema_id* Data Schema ID
			*
			* @apiSuccess {Array} Object Type ID of the type which is determining additional fields and custom fields og this type
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'fields':
				if (!$this -> args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown Data Schema ID'));
				}

				$object_type_id = get_object_field(TYPE_DATA_SCHEMA, $this -> args['schema_id'], 'user_fields');

				if (is_fx_error($object_type_id)) return $object_type_id;
				if (!$object_type_id) return array();
				
				$type_data = get_type_fields($object_type_id, "custom");
				
				if (is_fx_error($type_data)) {
					return $type_data;
				}
				else {
					return array('object_type_id' => $object_type_id, 'fields' => $type_data);
				}
				
			break;
			/**
			* @api {get} /user/validate_fields
			* @apiVersion 0.1.0
			* @apiName Validate Additional Fields
			* @apiGroup User
			* @apiDescription Validate additional user fields before submitting
			*
			* @apiParam {Number} schema_id* Data Schema ID
			* @apiParam {Array} fields* assiciative array with fields to validate (field_name=>field_value)
			*
			* @apiSuccess {true} All fields are valid
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'validate_fields':
			
				if (!$this -> args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown Data Schema ID'));
				}

				$object_type_id = get_object_field(TYPE_DATA_SCHEMA, $this -> args['schema_id'], 'user_fields');

				if (is_fx_error($object_type_id)) {
					return $object_type_id;
				}
				
				if (!$object_type_id) {
					return new FX_Error();
				}

				$errors = new FX_Error();
				
				foreach ((array)$this -> args['fields'][$object_type_id] as $field => $value) {
					$result = validate_field_value_by_type($object_type_id, $field, $value, $this -> args['forced']);
					if (is_fx_error($result)) {
						$errors -> add($field, $result -> get_error_message());
					}
				}

				if(!$errors -> is_empty()) {
					return $errors;
				}
				else {
					return true;
				}

			break;
			/**
			* @api {get} /user
			* @apiVersion 0.1.0
			* @apiName Get User Subscription
			* @apiGroup User
			* @apiDescription Return current user subscription 
			*
			* @apiSuccess {array} User subscription
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			default:
				return $this -> user_instance;
		}
	}

	protected function _post()
	{
		switch ($this -> verb)
		{
			/**
			* @api {post} /user/fields
			* @apiVersion 0.1.0
			* @apiName Submit Additional User Fields
			* @apiGroup User
			* @apiDescription Set values of additinal user fields for new user subscription
			*
			* @apiParam {Number} schema_id* Data Schema ID
			* @apiParam {Array} fields* assiciative array with fields to validate (field_name=>field_value)
			*
			* @apiSuccess {Number} Object identifier of new object whick contain additional user fields
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'fields':
			
				if (!$this -> args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown Data Schema identifier'));
				}

				$schema_type_id = get_type_id_by_name(TYPE_DATA_SCHEMA);

				$object_type_id = get_object_field($schema_type_id, $this -> args['schema_id'], 'user_fields');

				if (is_fx_error($object_type_id)) {
					return $object_type_id;
				}

				if (!$object_type_id) {
					return new FX_Error(__METHOD__, _('Invalid object type for user fields'));
				}
				
				$user_object = (array)$this -> args['fields'];
				$user_object['object_type_id'] = $object_type_id;
				$user_object['schema_id'] = $this -> args['schema_id'];
				$user_object['set_id'] = 0;
				$user_object['name'] = '';
				
				$object_id = add_object($this -> args, $this -> args['forced']);
			
				return $object_id;
			break;
			case 'assign_sub_channel':
			
				if (!$this -> args['set_id']) {
					return new FX_Error(__METHOD__, _('Unknown Data Set identifier'));
				}
				
				if (!$this -> args['user_id']) {
					return new FX_Error(__METHOD__, _('Unknown User identifier'));
				}
				
				$user_id = intval($this -> args['user_id']);
				
				$return_default_sfx = false;
				
				$sfx_id = get_sfx_by_user_id($user_id, $return_default_sfx);
				
				if (is_fx_error($sfx_id)) {
					return $sfx_id;
				}
											
				$res = add_link(TYPE_DATA_SET, $this -> args['set_id'], TYPE_SUBSCRIPTION, $sfx_id);	
			
				if (is_fx_error($sfx_id)) {
					return new FX_Error(__METHOD__, _('Unable to link User with specified Data Set'));
				}
				else {
					return true;
				}
			
			break;
			default:
				return new FX_Error(__METHOD__, _('Invalid verb value'));
		}	
	}

	protected function _put()
	{
		switch ($this -> verb)
		{
			case 'fields':
				return new FX_Error(__METHOD__, _('Not available'));
			break;
			default:
				return new FX_Error(__METHOD__, _('Invalid verb value'));
		}	
	}
}