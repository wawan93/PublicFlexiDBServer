<?php
/*
Version: 0.1
API Method Name: Objects
Description: Allow to get an access to Flexiweb DFX Object functions
Author: Flexiweb
Arguments:
*/
 
class FX_API_Object extends FX_API
{
	protected function _get()
	{		
		if (!$this->_check_permissions(U_GET)) {			
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}

		switch ($this -> verb)
		{
			/**
			* @api {GET} /object/field
			* @apiVersion 0.1.0
			* @apiGroup Objects
			* @apiName Get Object Field
			* @apiDescription Get FlexiDB Object field value
			*
			* @apiParam {Number*} object_type_id Object Type ID
			* @apiParam {Number*} object_id Object ID
			* @apiParam {String*} field Field Name
			* @apiParam {Boolean} details=false Set to "true" if required full field description
			*
			* @apiSuccess {Field Value} Object field value
			* @apiSuccess {Field Array} Array with all field options, including field value as a part of this array (when details=true)
			*
			* @apiError {error} Unable to get object with the specified ID
			* @apiError {error} Specified objest type or field does not exist
			*
			* @apiEnd
			*/	
			case  'field':

				return get_object_field($this -> args['object_type_id'],
										$this -> args['object_id'],
										$this -> args['field'],
										$this -> args['details'],
										$this -> args['schema_id'],
										$this -> args['set_id'] );
			break;
			
			/**
			* @api {GET} /object/validate_field
			* @apiVersion 0.1.0
			* @apiGroup Objects
			* @apiName Validate Field Value
			* @apiDescription Validate value of the specified field and object type
			*
			* @apiParam {Number*} object_type_id Object Type ID
			* @apiParam {String*} field Field Name
			* @apiParam {Mixed*} value Field Value to validate
			* @apiParam {Boolean} forced=false In case of forced validation, the value will be converted to the type of field
			*
			* @apiSuccess {Field Value} Object field value
			*
			* @apiError {error} Validation error message
			*
			* @apiEnd
			*/ 
			case 'validate_field':
				return validate_field_value_by_type($this -> args['object_type_id'], 
													$this -> args['field'], 
													$this -> args['value'], 
													$this -> args['forced']);
			break;

			/**
			* @api {GET} /object/validate_fields
			* @apiVersion 0.1.0
			* @apiGroup Objects
			* @apiName Validate Multiple Field Values
			* @apiDescription Validate multiple field values of the specified object type
			*
			* @apiParam {Number*} object_type_id Object Type ID
			* @apiParam {Array*} fields Associative array with fields and their values ([field name] => [field value])
			* @apiParam {Boolean} forced=false In case of forced validation, the value will be converted to the type of field
			*
			* @apiSuccess {True} Return True when ALL passed field values are valid
			*
			* @apiError {error} Validation error messages
			*
			* @apiEnd
			*/
			case 'validate_fields':

				$errors = new FX_Error();
				foreach ((array)$this -> args['fields'] as $field => $value) {
					$result = validate_field_value_by_type($this -> args['object_type_id'], $field, $value, $this -> args['forced']);
					if (is_fx_error($result)) {
						$errors -> add($field, $result -> get_error_message());
					}
				}

				return !$errors -> is_empty() ? $errors : true;
				
			break;

			/**
			* @api {GET} /object/by_type
			* @apiVersion 0.1.0
			* @apiGroup Objects
			* @apiName Get Objects By Type
			* @apiDescription Get all objects of some specified type
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} schema_id=null Schema ID for system types
			* @apiParam {Number} set_id=null Get objects from the specified Data Set only
			*
			* @apiSuccess {Array of objects} Array of FlexiDB objects
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'by_type':			
				return get_objects_by_type($this -> args['object_type_id'], $this -> args['schema_id'], $this -> args['set_id']);
			break;
			default:
				return $this->dfx_api_get_object($this -> args['object_type_id'], $this -> args['object_id'], $this -> args['details']);
		}	
	}


			

	protected function _post()
	{		
		if (!$this->_check_permissions(U_POST)) {	
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}

		switch ($this -> verb)
		{
			/**
			* @api {post} /object/replicate 
			* @apiVersion 0.1.0
			* @apiName Replicate Object
			* @apiGroup Object
			* @apiDescription Create a copy of the specified FlexiDB object
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			*
			* @apiSuccess {Object ID} The numeric ID of just created copy of the object
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'replicate':
				//TODO: replicate object links
				return replicate_object($this -> args['object_type_id'], $this -> args['object_id']);			
			break;
			/**
			* @api {post} /object/group
			* @apiVersion 0.1.0
			* @apiName Add Object Group
			* @apiGroup Objects
			* @apiDescription Add multiple FlexiDB objects
			*
			* @apiParam {Number} objects* Array of objects to add
			* @apiParam {Boolean} forced=false In case of forced validation, the value will be converted to the type of field
			*
			* @apiSuccess {Array} Array with the results for each object (error or object ID)
			*
			* @apiEnd
			*/
			case 'group':
			/**
			* @api {post} /object/array
			* @apiVersion 0.1.0
			* @apiName Add Object Aray
			* @apiDescription Alias for Add Object Group
			*
			* @apiEnd
			*/
			case 'array':
			
				$errors = new FX_Error();
				$count = 0;
				$result = array();
				
				foreach ($this -> args['objects'] as $count => $object) {
					$object_id = add_object($object, $this -> args['forced'], $this -> user_instance);
					if (!is_fx_error($object_id)) {
						$result[$count] = $object_id;
					}
					else {
						$result[$count] = $object_id->get_error_messages();
					}
				}

				return $result;
							
			break;
			/**
			* @api {post} /object
			* @apiVersion 0.1.0
			* @apiName Add Object
			* @apiDescription Add new FlexiDB object
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} schema_id* Data Schema ID
			* @apiParam {Number} set_id* Data Set ID
			* @apiParam {String} display_name* Display Name
			* @apiParam {Mixed} [field_name] All custom fields (at least all mandatory fields), where param name is field name and param value is a field value 
			* @apiParam {Boolean} forced=false In case of forced validation, the value will be converted to the type of field
			*
			* @apiSuccess {Object ID} The numeric ID of just created object
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			default: //{post} /object
				if (array_key_exists('_link_with_user', $this -> args) && $this -> args['_link_with_user'] !== false) {
					if (!link_type_exists($this -> args['object_type_id'], TYPE_SUBSCRIPTION)) {
						return new FX_Error('link_with_user', 'Unable to create and link object with current user because link type does not exists');
					}
				}
	
				$object_id = add_object($this -> args, $this -> args['forced'], $this -> user_instance);
				
				if (is_fx_error($object_id)) {
					return $object_id;
				}
				
				if (array_key_exists('_units', $this -> args)) {
					foreach($this -> args['_units'] as $field => $unit_id) {
						convert_object_value_by_unit();
					}
				}
				
				if (array_key_exists('_links', $this -> args)) {
					foreach ($this -> args['_links'] as $target_type_id => $objects) {
						foreach ((array)$objects as $key => $target_object_id) {
							$res = add_link($this -> args['object_type_id'], $object_id, $target_type_id, $target_object_id);
							if(is_fx_error($res)) add_log_message('api.add_object_link', $res -> get_error_message());
						}
					}
				}
	
				if (array_key_exists('_link_with_user', $this -> args) && $this -> args['_link_with_user'] !== false) {				
					$result = add_link($this -> args['object_type_id'], $object_id, TYPE_SUBSCRIPTION, $this -> user_instance['subscription_id']);
					if (is_fx_error($result)) {
						return new FX_Error('link_with_user', 'Unable to link object with User Subscription.');
					}
				}
	
				return $object_id;
		}
	}
	
	protected function _put()
	{
		if (!$this->_check_permissions(U_PUT)) {			
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}

		switch ($this -> verb)
		{
			/**
			* @api {put} /object/field
			* @apiVersion 0.1.0
			* @apiName Update Object Field
			* @apiGroup Objects
			* @apiDescription Update specified FlexiDB object field with new value
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			* @apiParam {String} field* Name of the field to update
			* @apiParam {Mixed} value=empty New field value
			* @apiParam {Boolean} forced=false In case of forced validation, the value will be converted to the type of field
			*
			* @apiSuccess {true} Field updated with new value without errors
			* @apiSuccess {false} Object updated with same value without errors
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/	
			case 'field':
			
				return update_object_field($this -> args['object_type_id'], 
										   $this -> args['object_id'], 
										   $this -> args['field'], 
										   $this -> args['value'], 
										   $this -> args['forced'], 
										   $this -> user_instance);
	
			break;
			
			/**
			* @api {put} /object/group
			* @apiVersion 0.1.0
			* @apiName Update Object Group
			* @apiGroup Objects
			* @apiDescription Update multiple FlexiDB objects with new field values
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			* @apiParam {Mixed} [field_name] Fields and values to update, where param name is field name and param value is a field value
			* @apiParam {Boolean} forced=false In case of forced validation, the value will be converted to the type of field
			*
			* @apiSuccess {Object Count} The number of objects that have been updated
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'group':
			/**
			* @api {put} /object/array
			* @apiVersion 0.1.0
			* @apiName Update Object Aray
			* @apiDescription Alias for Update Object Group
			*
			* @apiEnd
			*/
			case 'array':

				$errors = new FX_Error();
				$count = 0;
				foreach ($this -> args as $object) {
					$update_result = update_object($object, false, $this->user_instance);
					
					if (!is_fx_error($update_result)) {
						$count++;
					}
					else {
						$errors->add(__METHOD__.' ['.$object['object_id'].']', $update_result->get_error_message(), $object['object_id']);
					}
				}

				return $errors -> is_empty() ? $count : $errors;

			break;
			/**
			* @api {put} /object
			* @apiVersion 0.1.0
			* @apiName Update Object
			* @apiDescription Update specified FlexiDB object with new field values
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			* @apiParam {Mixed} [field_name] Fields and values to update, where param name is field name and param value is a field value
			* @apiParam {Boolean} forced=false In case of forced validation, the value will be converted to the type of field
			*
			* @apiSuccess {true} Object updated without errors. At least one field has been changed.
			* @apiSuccess {false} Object updated without errors. No one field has been changed.
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			default:
			
/*				if (!empty($_FILES)) {
					$tmp_res = new FX_Temp_Resource($this -> args['object_type_id'], $this -> args['object_id']);
	
					if ($tmp_res->last_error) {	
						add_log_message('fx_tmp_resource', $tmp_res->last_error);
						return new FX_Error('fx_tmp_resource', $tmp_res->last_error);
					}

					$tmp_dir =  CONF_UPLOADS_DIR.'/temp/';
					$upload_res = array();

					foreach ($_FILES as $field_name => $tmp_file) {
						if (!file_exists($tmp_file["tmp_name"])) {
							return new FX_Error('upload_file', 'Temporary file not found '.$tmp_file["tmp_name"]);
						}
		
						if (move_uploaded_file($tmp_file["tmp_name"], $tmp_dir.$tmp_file['name']) === true) {
							$res = $tmp_res -> add($field_name, $tmp_file['name']);
							$this -> args[$field_name] = $tmp_res -> resources[$field_name]['field_value'];
						}
						else {
							$tmp_res -> remove();
							return new FX_Error('upload_file', 'Unable to upload temporary file for field "'.$field_name.'".');
						}
					}
				}
		*/
				
				$update_result = update_object($this -> args, $this -> args['forced'], $this -> user_instance);
	
				if (is_fx_error($update_result)) {
					return $update_result;
				}
	
				if (array_key_exists('_links', $this -> args))
				{
					$link_errors = new FX_Error();
					$actual_links = get_actual_links($this -> args['object_type_id'], $this -> args['object_id'], $target_type_id);
	
					foreach ($actual_links as $linked_type => $linked_objects) {
						if (array_key_exists($linked_type, $this -> args['_links'])) {
							foreach ($linked_objects as $object_id => $options) {
								if (!in_array($object_id, $this -> args['_links'][$linked_type])) {
									$result = delete_link($this -> args['object_type_id'], $this -> args['object_id'], $linked_type, $object_id);
									if (is_fx_error($result)) {
										$link_errors -> add('api_delete_link', $result -> get_error_message());
									}
									else {
										unset($actual_links[$linked_type][$object_id]);
									}
								}
							}
						}
					}				
	
					foreach ($this -> args['_links'] as $target_type_id => $objects) {
						foreach ($objects as $key => $target_object_id) {
							$target_object_id = (int)$target_object_id;
							if ($target_object_id && !array_key_exists($target_object_id, $actual_links[$target_type_id])) {
								$result = add_link($this -> args['object_type_id'], $this -> args['object_id'], $target_type_id, $target_object_id);	
								if (is_fx_error($result)) {
									$link_errors -> add('api_update_link', $result -> get_error_message());
								}
							}
						}
					}
	
					if (!$link_errors -> is_empty()) {
						return $link_errors;
					}
				}
				
				if (array_key_exists('_link_with_user', $this -> args) && $this -> args['_link_with_user'] !== false) {
					if (!link_type_exists($this -> args['object_type_id'], TYPE_SUBSCRIPTION)) {
						return new FX_Error('link_with_user', 'Unable to link object with User Subscription because link type does not exists');
					}
					
					$result = add_link($this -> args['object_type_id'], 
											   $this -> args['object_id'],
											   $this -> user_instance['object_type_id'], 
											   $this -> user_instance['subscription_id']);

	/*				if (is_fx_error($result))
					{
						return new FX_Error('link_with_user', 'Unable to link object with User Subscription.');
					}*/
				}
	
				return $update_result;			
		}
	}

	protected function _delete()
	{
		if (!$this->_check_permissions(U_DELETE)) {			
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}
		/**
		* @api {delete} /object
		* @apiVersion 0.1.0
		* @apiName Delete Object
		* @apiDescription Delete specified FlexiDB object
		*
		* @apiParam {Number} object_type_id* Object Type ID
		* @apiParam {Number} object_id* Object ID
		*
		* @apiSuccess {true} The object was successfully deleted
		*
		* @apiError {error} Error Message
		*
		* @apiEnd
		*/
			
		return delete_object($this -> args['object_type_id'], $this -> args['object_id']);
	}

	/**
	* @api {get} /object
	* @apiVersion 0.1.0
	* @apiName  Get Object
	* @apiDescription Get FlexiDB object with specified Object Type ID and Object ID
	*
	* @apiParam {Number} object_type_id* Object Type ID
	* @apiParam {Number} object_id* Object ID
	* @apiParam {Boolean} details=false Set to "true" if required full field description
	*
	* @apiSuccess {Array} Associative array with simple object representation if details=false
	* @apiSuccessExample
	* 	{
   	*		"field_name": [field_value],
    *		....
	*	}
	*
	* @apiSuccess {Array} Associative array with detailed object representation if details=true
	* @apiSuccessExample
	* 	{
   	*		"field_name": {"name" => [name],
	*					   "caption" => [caption],
	*					   "description" => [description],
	*					   "mandatory" => [mandatory],
	*					   "type" => [type],
	*					   "default_value" => [default_value]
	*					   "field_value" => [field_value]
	*					  },
    *		....
	*	}
	*
	*
	* @apiError {error} Unable to get object with the specified ID
	* @apiError {error} Specified object type does not exist
	*
	* @apiEnd
	*/
	private function dfx_api_get_object($object_type_id, $object_id, $details)
	{
		return get_object($object_type_id, $object_id, $details);
	}
	
	private function _check_permissions ($required_permission)
	{
		$set_id = get_object_field($this -> args['object_type_id'], $this -> args['object_id'], 'set_id');

		if (!is_numeric($set_id)) {
			$set_id = $this -> args['set_id'] ? $this -> args['set_id'] : 0;
		}

		if ($this -> is_admin || in_array($set_id, $this->user_instance['sets'])) {
			return true;
		}

		$csp = $this->user_instance['set_permissions'][$set_id][$this -> args['object_type_id']];
		
		if (!($this -> permission & $required_permission) && !($csp & $required_permission)) {
			return false;
		}
		else {
			return true;
		}
	}
	
}