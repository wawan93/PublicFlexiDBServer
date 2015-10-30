<?php
/*
Version: 0.1
API Method Name: Types
Description: Allow to get an access to Flexiweb DFX Type functions
Author: Flexiweb
Arguments:
*/

class FX_API_Type extends FX_API
{
	protected function _get()
	{
		if (!$this -> is_admin && !($this -> permission & U_GET))
		{
			return new FX_Error('access_forbidden', 'You have no proper access rights to perform this action.');
		}

		switch ($this -> verb)
		{
			/**
			* @api {get} /type/fields
			* @apiVersion 0.1.0
			* @apiName Get Type Fields
			* @apiGroup Types
			* @apiDescription Get all fields with options of the specified Object type
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {String} fields_mode="all" What type of fields need to return ("all", "base", "custom").
			*
			* @apiSuccess {Array of fields} Array with requested fields depending on fields_mode value
			* @apiSuccessExample
			* 	{
			*		"field_name": {"name" => [name],
			*					   "caption" => [caption],
			*					   "description" => [description],
			*					   "mandatory" => [mandatory],
			*					   "type" => [type],
			*					   "default_value" => [default_value]
			*					  },
			*		....
			*	}
			*
			* @apiError {error} Error Message
			*
			* @apiEnd		
			*/
			case 'fields':
				$fields = get_type_fields($this -> args['object_type_id'], $this -> args['fields_mode']);
				if (!is_fx_error($fields)) {
					unset($fields['schema_id'], $fields['set_id'], $fields['user_ip'], $fields['user_api_key'], $fields['system']);					
				}
				return $fields;				
			break;
			/**
			* @api {get} /type/schema
			* @apiVersion 0.1.0
			* @apiName Get Schema Types
			* @apiGroup Types
			* @apiDescription Get all object types which are belong to specified Data Schema
			*
			* @apiParam {Number} schema_id* Data Schema ID
			* @apiParam {String} fields_mode="all" What type of fields need to return ("none", "all", "base", "custom")
			*
			* @apiSuccess {Array of types} Array with requested object types with fields depending on fields_mode value. The array can be empty if Data Scheme does not contain types
			*
			* @apiEnd
			*/
			case 'schema':
				return get_schema_types($this -> args['schema_id'], $this -> args['fields_mode']);
			break;
			/**
			* @api {get} /type/exists
			* @apiVersion 0.1.0
			* @apiName Type Exists
			* @apiGroup Types
			* @apiDescription Check the existence of the object type with the specified ID
			*
			* @apiParam {Number} object_type_id* Object Type ID
			*
			* @apiSuccess {Object Type ID} Type identifier comes back in case of success
			* @apiSuccess {false} This method returns false if object type with the specified ID does not exists
			*/
			case 'exists':
				return type_exists($this -> args['object_type_id']);
			break;
			/**
			* @api {get} /type/links
			* @apiVersion 0.1.0
			* @apiName Type Links
			* @apiGroup Types
			* @apiDescription Get 
			*
			* @apiParam {Mixed} object_type_id* Numeric Object Type ID or array of identifiers
			*
			* @apiSuccess {Array} Associative array with 
			* @apiSuccess {false} This method returns false if object type with the specified ID does not exists
			*/
			case 'links':
				return get_type_links($this -> args['object_type_id']);
			break;
			/**
			* @api {get} /type
			* @apiVersion 0.1.0
			* @apiName Get Object Type
			* @apiDescription Get FlexiDB object type with the specified type identifier
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {String} fields_mode="all" What type of fields need to return ("none", "all", "base", "custom")
			*
			* @apiSuccess {Type} Array with requested object types with fields depending on fields_mode value
			* @apiSuccessExample
			* 	{
			*		....
			*	}
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			default:
				return get_type($this -> args['object_type_id'], $this -> args['fields_mode']);
		}	
	}
}