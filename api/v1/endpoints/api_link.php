<?php

class FX_API_Link extends FX_API
{
	protected function _get()
	{
		switch ($this -> verb)
		{
			/**
			* @api {GET} /link/linked_types
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Get Linked Types
			* @apiDescription Get all types which can be linked with requested 

			* @apiParam {Number} object_type_id* Object Type ID
			*
			* @apiSuccess {Array} Array with all types which can be linkd with requested
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/				
			case 'linked_types':

				if (array_key_exists('query_id', $this -> args)) {
					$object_type_id = get_object_field(TYPE_QUERY, $this -> args['query_id'], 'main_type');
				}
				else {
					$object_type_id = $this -> args['object_type_id'];
				}
				
				$linked_types = get_type_links($object_type_id);
				
				if (is_fx_error($linked_types) ) {
					return $linked_types;
				}
				
				$result = array();

				foreach ($linked_types as $object_type_id=>$data) {
					$result[$object_type_id] = array ('name' => $data['name'],
													  'display_name' => $data['display_name'],
													  'relation' => $data['relation']);
				}
				
				return $result;
			
			break;

			/**
			* @api {GET} /link/query_linked_types
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Get Query Linked Types
			* @apiDescription Get all types which can be linked with main type of requested query 
			*
			* @apiParam {Number} query_id* Query ID
			*
			* @apiSuccess {Array} Array with all types which can be linkd with requested main type of the specified query
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'query_linked_types':

				$object_type_id = get_object_field(TYPE_QUERY, $this -> args['query_id'], 'main_type');

				if (is_fx_error($object_type_id)) {
					return $object_type_id;
				}

				$linked_types = get_type_links($object_type_id);
				
				if (is_fx_error($linked_types) ) {
					return $linked_types;
				}
				
				$result = array();

				foreach ($linked_types as $object_type_id=>$data) {
					$result[$object_type_id] = array ('name' => $data['name'],
													  'display_name' => $data['display_name'],
													  'relation' => $data['relation']);
				}
				
				return $result;
			
			break;
			
			/**
			* @api {GET} /link/validate
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Validate Link
			* @apiDescription Validate link between two objects
			*
			* @apiParam {Number} object_type_1_id* Object Type ID of the first object
			* @apiParam {Number} object_1_id* Object ID of the first object
			* @apiParam {Number} object_type_2_id* Object Type ID of the second object
			* @apiParam {Number} object_2_id* Object ID of the second object
			*
			* @apiSuccess {true} Two objects can be linked
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/	
			case 'validate':
				return validate_link($this -> args['object_type_1_id'], $this -> args['object_1_id'], $this -> args['object_type_2_id'], $this -> args['object_2_id']);
			break;

			/**
			* @api {GET} /link/validate_group
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Validate Link Group
			* @apiDescription Multiple link validation. This method is equal to Validate Link, but input arguments locate in the array.
			*
			* @apiParam {Number} object_type_1_id* Object Type ID of the first object
			* @apiParam {Number} object_1_id* Object ID of the first object
			* @apiParam {Number} object_type_2_id* Object Type ID of the second object
			* @apiParam {Number} object_2_id* Object ID of the second object
			*
			* @apiSuccess {array} Array of the results for the each array key
			*
			* @apiEnd
			*/ 				
			case 'validate_group':
			
			/**
			* @api {put} /object/validate_array
			* @apiVersion 0.1.0
			* @apiName Validate Link Array
			* @apiDescription Alias for Validate Link Group
			*
			* @apiEnd
			*/
			case 'validate_array':

				$errors = new FX_Error();
				$result = array();
				
				$count = 0;

				foreach ($this -> args as $params) {
					if (!link_exists($params['object_type_1_id'], $params['object_1_id'], $params['object_type_2_id'], $params['object_2_id'])) {
						$result[$count] = validate_link($params['object_type_1_id'], $params['object_1_id'], $params['object_type_2_id'], $params['object_2_id']);
					}
					else {
						$result[$count] = true;
					}
					$count++;
				}
				return $result;
				
			break;

			/**
			* @api {GET} /link/exists
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Link Exists
			* @apiDescription Check if the link between two objects exists
			*
			* @apiParam {Number} object_type_1_id* Object Type ID of the first object
			* @apiParam {Number} object_1_id* Object ID of the first object
			* @apiParam {Number} object_type_2_id* Object Type ID of the second object
			* @apiParam {Number} object_2_id* Object ID of the second object
			*
			* @apiSuccess {true} Link exists
			* @apiSuccess {false} Link dies not exists
			*
			* @apiEnd
			*/
			case 'exists':
				return link_exists($this -> args['object_type_1_id'], $this -> args['object_1_id'], $this -> args['object_type_2_id'], $this -> args['object_2_id']);
			break;
			
			/**
			* @api {GET} /link/relation
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Get Link Relation
			* @apiDescription Get link relation based on link type
			*
			* @apiParam {Number} object_type_1_id* Object Type ID of the first object
			* @apiParam {Number} object_type_2_id* Object Type ID of the second object
			*
			* @apiSuccess {1} one-to-one
			* @apiSuccess {2} one-to-many
			* @apiSuccess {3} many-to-one
			* @apiSuccess {4} many-to-many
			* @apiSuccess {false} Link dies not exists
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'relation':
				$res =  get_link_relation($this -> args['object_type_1_id'], $this -> args['object_type_2_id']);
				return $res;
			break;
			
			/**
			* @api {GET} /link/possible
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Get Possible Links
			* @apiDescription Get all possible links for the some object (all or of the specified object type) 
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			* @apiParam {Number} linked_object_type Object Type ID of the linked objects. Leave it blank to get objects of all types
			* @apiParam {Number} limit Limit number of results
			* @apiParam {Number} offset Get results starting this value
			*
			* @apiSuccess {Array} Array with all possible links
			* @apiSuccessExample
			* 	{
			*		"object_type_id": {
			*			"object_id": {
			*				"relation" => 1,
			*				"strength" => "strong",
			*				"schema_id" => 1,
			*				"set_id" => 1,
			*				"name" => "object_name",
			*				"display_name" => "Display Name",
			*				"actuality" => "possible"
			*			},
			*		....
			*	}
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'possible':
			
			/**
			* @api {GET} /link/possible_count
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Get Possible Links Count
			* @apiDescription Get number of possible links for the some object (all or of the specified object type) 
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			* @apiParam {Number} linked_object_type Object Type ID of the linked objects. Leave it blank to get objects of all types
			*
			* @apiSuccess {Number} Links number 
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/				
			case 'possible_count':

				if ($this -> args['object_id']) {
					$res = get_possible_links($this -> args['object_type_id'], $this -> args['object_id'], $this -> args['linked_object_type'], $this -> schema_id);
				}
				else {
					$res = get_possible_links_by_type($this -> args['object_type_id'], $this -> args['linked_object_type'], $this -> schema_id);
				}

				if (array_key_exists('set_id', $this -> args) && !is_fx_error($res)) {
					$this -> _filter_sets($res, $this -> args['set_id']);
				}
				
				if ($this -> args['filter']) {
					foreach ($res as $object_type_id => $links) {
						foreach ($links as $object_id => $data) {
							if (strpos(strtolower($data['display_name']), strtolower($this -> args['filter'])) === false) {
								unset($res[$object_type_id][$object_id]);
							}
						}
					}
				}

				if ($this -> verb == 'possible') {
					if ($this -> args['limit'] || $this -> args['offset']) {
						if ($limit < 1) {
							$limit = -1;
						}
						foreach($res as $object_type_id => $links) {
							$res[$object_type_id] = array_slice($links, $this -> args['offset'], $this -> args['limit'], true);
						}	
					}
					
					return $res;
				}

				foreach($res as $object_type_id => $links) {
					$res[$object_type_id] = count($links);
				}
				
				return $res;
	
			break;
			
			/**
			* @api {GET} /link/actual
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Get Actual Links
			* @apiDescription Get all actual links for the some object (all or of the specified object type) 
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			* @apiParam {Number} linked_object_type Object Type ID of the linked objects. Leave it blank to get objects of all types
			* @apiParam {Number} limit Limit number of results
			* @apiParam {Number} offset Get results starting this value
			*
			* @apiSuccess {Array} Array with all actual links
			* @apiSuccessExample
			* 	{
			*		"object_type_id": {
			*			"object_id": {
			*				"relation" => 1,
			*				"strength" => "strong",
			*				"schema_id" => 1,
			*				"set_id" => 1,
			*				"name" => "object_name",
			*				"display_name" => "Display Name",
			*				"actuality" => "actual"
			*			},
			*		....
			*	}
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'actual':
			
			/**
			* @api {GET} /link/actual_count
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Get Actual Links Count
			* @apiDescription Get number of actual links for the some object (all or of the specified object type) 
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			* @apiParam {Number} linked_object_type Object Type ID of the linked objects. Leave it blank to get objects of all types
			*
			* @apiSuccess {Number} Links number 
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'actual_count':

				$res = get_actual_links($this -> args['object_type_id'], $this -> args['object_id'], $this -> args['linked_object_type'], $this -> schema_id);
				if (array_key_exists('set_id', $this -> args) && !is_fx_error($res)) {
					$this -> _filter_sets($res, $this -> args['set_id']);
				}

				if ($this -> args['filter']) {
					foreach ($res as $object_type_id => $links) {
						foreach ($links as $object_id => $data) {
							if (strpos(strtolower($data['display_name']), strtolower($this -> args['filter'])) === false) {
								unset($res[$object_type_id][$object_id]);
							}
						}
					}
				}
		
				if ($this -> verb == 'actual') {	
					if ($this -> args['limit'] || $this -> args['offset']) {
						if ($limit < 1) {
							$limit = -1;
						}
						foreach($res as $object_type_id => $links) {
							$res[$object_type_id] = array_slice($links, $this -> args['offset'], $this -> args['limit'], true);
						}	
					}
					
					return $res;
				}

				foreach($res as $object_type_id => $links) {
					$res[$object_type_id] = count($links);
				}

				return $res;
			
			/**
			* @api {GET} /link/all
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Get All Links
			* @apiDescription Get all actual and possible links for the some object (all or of the specified object type) 
			*
			* @apiParam {Number} object_type_id* Object Type ID
			* @apiParam {Number} object_id* Object ID
			* @apiParam {Number} linked_object_type Object Type ID of the linked objects. Leave it blank to get objects of all types
			* @apiParam {Number} limit Limit number of results
			* @apiParam {Number} offset Get results starting this value
			*
			* @apiSuccess {Array} Array with all actual links
			* @apiSuccessExample
			* 	{
			*		"object_type_id": {
			*			"object_id": {
			*				"relation" => 1,
			*				"strength" => "strong",
			*				"schema_id" => 1,
			*				"set_id" => 1,
			*				"name" => "object_name",
			*				"display_name" => "Display Name",
			*				"actuality" => "actual"
			*			},
			*			"object_id": {
			*				"relation" => 1,
			*				"strength" => "strong",
			*				"schema_id" => 1,
			*				"set_id" => 1,
			*				"name" => "object_name",
			*				"display_name" => "Display Name",
			*				"actuality" => "posible"
			*			},
			*		....
			*	}
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/		
			case 'all':
			default:
			default:

				$object_type_id = $this -> args['object_type_id'];
				$object_id = $this -> args['object_id'];
				$linked_object_type_id = $this -> args['linked_object_type'];
				$set_id = $this -> args['set_id'];

				$actual_links = get_actual_links($object_type_id, $object_id, $linked_object_type_id, $this -> schema_id);
				
				//fx_print($actual_links);
				
				if (is_fx_error($actual_links)) {
					return $actual_links;
				}
				else {
					if ($set_id !== false) {
						$this->_filter_sets($actual_links, $set_id);
					}
				}
			
				$possible_links = get_possible_links_by_type($object_type_id, $linked_object_type_id, $this -> schema_id);
			
				if ($set_id !== false && !is_fx_error($possible_links)) {
					$this->_filter_sets($possible_links, $set_id);
				}
				
				if (is_fx_error($possible_links) || !$actual_links) {
					return $possible_links;
				}
			
				foreach ($possible_links as $object_type_id => $objects) {
					foreach ($objects as $object_id => $object) {
						if (isset($actual_links[$object_type_id][$object_id])) {
							$possible_links[$object_type_id][$object_id]['actuality'] = 'actual';
							unset($actual_links[$object_type_id][$object_id]);
						}
					}
				}
			
				foreach ($actual_links as $object_type_id => $objects) {
					if (isset($possible_links[$object_type_id])) {
						$possible_links[$object_type_id] = $possible_links[$object_type_id] + $actual_links[$object_type_id];
					}
					else {
						$possible_links[$object_type_id] = $actual_links[$object_type_id];
					}
			
					ksort($possible_links[$object_type_id]);
				}
			
				ksort($possible_links);

				return $possible_links;	
		}
	}
	
	protected function _post()
	{
		switch ($this -> verb)
		{
			/**
			* @api {post} /link/group
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Add Link Group
			* @apiDescription Multiple link creation 
			*
			* @apiParam {Number} object_type_1_id* Object Type ID of the first object
			* @apiParam {Number} object_1_id* Object ID of the first object
			* @apiParam {Number} object_type_2_id* Object Type ID of the second object
			* @apiParam {Number} object_2_id* Object ID of the second object
			*
			* @apiSuccess {Number} Number of link which were successfully added
			*
			* @apiError {Array} Aarray of errors which were occured
			*
			* @apiEnd
			*/
			case 'group':
			
			/**
			* @api {post} /link/array
			* @apiVersion 0.1.0
			* @apiName Add Link Group
			* @apiDescription Alias for Add Link Group
			*
			* @apiEnd
			*/
			case 'array':
				$errors = new FX_Error();
				$count = 0;
				foreach ($this -> args as $params) {
					if (!link_exists($params['object_type_1_id'], $params['object_1_id'], $params['object_type_2_id'], $params['object_2_id'])) {
						$result = add_link($params['object_type_1_id'], $params['object_1_id'], $params['object_type_2_id'], $params['object_2_id']);
						if (!is_fx_error($result)) {
							$count++;
						}
						else {
							$errors->add(__METHOD__.' ['.$count.']',$result->get_error_message());
						}
					}
				}
				return $errors -> is_empty() ? $count : $errors;
			break;
			
			/**
			* @api {post} /link
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Add Link
			* @apiDescription Create link between two objects
			*
			* @apiParam {Number} object_type_1_id* Object Type ID of the first object
			* @apiParam {Number} object_1_id* Object ID of the first object
			* @apiParam {Number} object_type_2_id* Object Type ID of the second object
			* @apiParam {Number} object_2_id* Object ID of the second object
			*
			* @apiSuccess {true} Link successfully added
			*
			* @apiError {Error} Error message
			*
			* @apiEnd
			*/
			default:
				return add_link($this -> args['object_type_1_id'], $this -> args['object_1_id'], $this -> args['object_type_2_id'], $this -> args['object_2_id']);	
		}
	}
	
	protected function _delete()
	{
		switch ($this -> verb)
		{
			/**
			* @api {delete} /link/group
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Delete Link Group
			* @apiDescription Multiple link deleting 
			*
			* @apiParam {Number} object_type_1_id* Object Type ID of the first object
			* @apiParam {Number} object_1_id* Object ID of the first object
			* @apiParam {Number} object_type_2_id* Object Type ID of the second object
			* @apiParam {Number} object_2_id* Object ID of the second object
			*
			* @apiSuccess {Number} Number of link which were successfully deleted
			*
			* @apiError {Array} Aarray of errors which were occured
			*
			* @apiEnd
			*/
			case 'group':
			
			/**
			* @api {delete} /link/array
			* @apiVersion 0.1.0
			* @apiName Delete Link Group
			* @apiDescription Alias for Delete Link Group
			*
			* @apiEnd
			*/
				$errors = new FX_Error();
				$count = 0;
				foreach ($this -> args as $params) {
					$result = delete_link($params['object_type_1_id'], $params['object_1_id'], $params['object_type_2_id'], $params['object_2_id']);
					if (!is_fx_error($result)) $count++;
					else $errors->add(__METHOD__.' ['.$count.']',$result->get_error_message());
				}
				return $errors -> is_empty() ? $count : $errors;
			break;
			/**
			* @api {delete} /link
			* @apiVersion 0.1.0
			* @apiGroup Links
			* @apiName Delete Link
			* @apiDescription Delete link between two objects
			*
			* @apiParam {Number} object_type_1_id* Object Type ID of the first object
			* @apiParam {Number} object_1_id* Object ID of the first object
			* @apiParam {Number} object_type_2_id* Object Type ID of the second object
			* @apiParam {Number} object_2_id* Object ID of the second object
			*
			* @apiSuccess {true} Link successfully deleted
			*
			* @apiError {Error} Error message
			*
			* @apiEnd
			*/
			default:
				return delete_link($this -> args['object_type_1_id'], $this -> args['object_1_id'], $this -> args['object_type_2_id'], $this -> args['object_2_id']);
		}
	}
	
	protected function _filter_sets(&$array, $set_id)
	{
		if (!strlen((string)$set_id)) return;

		foreach ($array as $object_type_id => $objects) {
			foreach ($objects as $object_id => $object) {
				if ($object['set_id'] == $set_id || ($this->is_admin && is_system_type($object_type_id))) {
					continue;
				}
				unset($array[$object_type_id][$object_id]);
			}
			
			if (!$array[$object_type_id]) {
				unset($array[$object_type_id]);
			}
		}		
	}
}