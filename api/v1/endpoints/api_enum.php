<?php

class FX_API_Enum extends FX_API
{
	protected function _get()
	{
		switch ($this -> verb)
		{
			/**
			* @api {get} /enum/fields
			* @apiVersion 0.1.0
			* @apiName Get Enum Fields
			* @apiGroup Enumerations
			* @apiDescription Get the enum type fields by the specified Enum Type ID.     
			*
			* @apiParam {Number} enum_type_id* Unique enum type identifier
			*
			* @apiSuccess {Array} Associative array where keys are enum values and values are enum labels  
			* @apiSuccessExample
			* 	{
			*		"enum_value": [enum_label],
			*		....
			*	}
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/	
			case 'fields':
				return get_enum_fields($this -> args['enum_type_id'], $this -> args['details']);
			break;

			/**
			* @api {get} /enum/value_exists
			* @apiVersion 0.1.0
			* @apiName Value Exists
			* @apiGroup Enumerations
			* @apiDescription Checks if the specified value exists in specified Enum type     
			*
			* @apiParam {Number} enum_type_id* Unique enum type identifier
			* @apiParam {String} value* The Value to check 
			*
			* @apiSuccess {Boolean} "true" if the value is found in the enumeratione  
			* @apiSuccess {Boolean} "false" if the value is not found in the enumeration
			*
			* @apiEnd
			*/	
			case 'value_exists':
				$res = enum_value_exists($this -> args['enum_type_id'], $this -> args['value']);
				return $res ? true : false;
			break;

			/**
			* @api {get} /enum/label
			* @apiVersion 0.1.0
			* @apiName Get Enum Label
			* @apiGroup Enumerations
			* @apiDescription Getting enum label by passed enum value
			*
			* @apiParam {Number} enum_type_id* Unique enum type identifier
			* @apiParam {String} value* Enum Value whick is corresponding to Enum Label
			*
			* @apiSuccess {String} Enum label which is corresponding to passed value  
			* @apiSuccess {Boolean} "false" if the value is not found in the enumeration
			*
			* @apiEnd
			*/	
			case 'label':
				return get_enum_label($this -> args['enum_type_id'], $this -> args['value']);
			break;
			
			/**
			* @api {get} /enum/values
			* @apiVersion 0.1.0
			* @apiName Get 
			* @apiGroup Enumerations
			* @apiDescription Get all values for the specified Enum type
			*
			* @apiParam {Number} enum_type_id* Unique enum type identifier
			*
			* @apiSuccess {Array} Array with all enum values
			* @apiSuccessExample
			* 	{
			*		0: [enum_value],
			*		1: [enum_value],
			*		....
			*	}
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/				
			case 'values':
				return get_enum_values($this -> args['enum_type_id']);
			break;
			
			/**
			* @api {get} /enum
			* @apiVersion 0.1.0
			* @apiName Get Enum
			* @apiDescription Get all Enum options and fields (optionally)
			*
			* @apiParam {Number} enum_type_id* Unique enum type identifier
			* @apiParam {Boolean} with_fields=true Include (or not) values and fields into the result
			*
			* @apiSuccess {Array} Enum options only in case of with_fields=false
			* @apiSuccessExample
			* 	{
			*		"enum_type_id": [enum_type_id]
			*		"system": [system]
			*		"schema_id": [schema_id]
			*		"name": [name]
			*		0: [enum_value],
			*		1: [enum_value],
			*		....
			*	}
			*
			* @apiSuccess {Array} Enum options and Enum fields in case of with_fields=true
			* @apiSuccessExample
			* 	{
			*		"enum_type_id": [enum_type_id],
			*		"system": [system],
			*		"schema_id": [schema_id],
			*		"name": [name],
			*		"fields": {0: [enum_value],1: [enum_value], .... }
			*
			*	}
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/					
			default:
				return get_enum_type($this -> args['enum_type_id'], $this -> args['with_fields']);
		}	
	}
}