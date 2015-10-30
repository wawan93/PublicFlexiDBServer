<?php
/*
Version: 0.1
API Method Name: Query
Description: Getting list of objects by some condition
Author: Flexiweb
Arguments:
*/

class FX_API_Query extends FX_API
{
	protected function _get()
	{
		$query = $this -> args['query'] ? $this -> args['query'] : $this -> args['query_id'];

		switch ($this -> verb)
		{
			/**
			* @api {get} /query/count
			* @apiVersion 0.1.0
			* @apiName Get Query Count
			* @apiGroup Queries
			* @apiDescription Perform query and get number of rows    
			*
			* @apiParam {Mixed} query* Numeric Query object identifier or array with query options.
			* @apiParam {Number} set_id=0 Data Set ID if required getting objects from specified Data Set only.
			* @apiParam {Number} linked_object_type_id=0 Used in conjunction with linked_object_id to show only objects linked with specified  linked_object_type_id.linked_object_id
			* @apiParam {Number} linked_object_id=0 Used in conjunction with linked_object_type_id to show only objects linked with specified  linked_object_type_id.linked_object_id
			* @apiParam {Number} search_string=null Filter rows by some field value 
			*
			* @apiSuccess {Number} Number of rows in query result
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/			
			case 'count':

				$args = $this -> args;
				
				

				
				if (caching_enabled() && is_numeric($query)) {					
					$result = get_cached_query_result($query, 
												   $this -> user_instance,
												   $args['set_id'], 
												   0, //offset
												   0, //limit
												   $args['linked_object_type_id'],
												   $args['linked_object_id'],
												   NULL,
												   NULL,
												   $args['filter_by'],
												   $args['group']);							

					if (is_fx_error($result)) {
						return $result;
					}
													 
					return count($result);									 
				}
				else {
					return exec_fx_query_count($query,
												 $args['set_id'],
												 $user_instance,
												 $args['linked_object_type_id'],
												 $args['linked_object_id'],
												 $args['group']);
				}

			break;
			
			/**
			* @api {get} /query/multiple_count
			* @apiVersion 0.1.0
			* @apiName Get Multiple Query Count
			* @apiGroup Queries
			* @apiDescription Execute multiple FlexiDB queries and get an array with number of rows for each query    
			*
			* @apiParam {Array} query* Array with all query options. Each element of the array contains the set of parameters are the same with GET Query method (query [, set_id] [, limit] [, offset] [, linked_object_type_id] [, linked_object_id] [, search_string])
			*
			* @apiSuccess {Array} Array of number of elements in each of the queries
			* @apiSuccessExample
			* 	{ "count_1", "count_2", .... }
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/			
			case 'multiple_count':

				$result = array();
				$query_args = (array)$query;
	
				for ($i=0; $i < count($query_args); $i++)
				{
					$args = $query_args[$i];
												  
					if (caching_enabled() && is_numeric($query)) {
						$cur_res = count(get_cached_query_result($args['query'], 
																 $this -> user_instance,
																 $args['set_id'], 
																 0, //offset
																 0, //limit
																 $args['linked_object_type_id'],
																 $args['linked_object_id']));
					}
					else {
						$cur_res = exec_fx_query_count($args['query'], 
													   $args['set_id'],
													   $this -> user_instance,
													   $args['linked_object_type_id'],
													   $args['linked_object_id'],
													   $args['search_string']);
					}
	
					if (is_fx_error($cur_res)) {
						return new FX_Error(__METHOD__, 'query['.$i.']:'.$cur_res);
					}
	
					$result[] = $cur_res;			
				}
	
				return $result;

			break;

			/**
			* @api {get} /query/object
			* @apiVersion 0.1.0
			* @apiName Get Query Object
			* @apiGroup Queries
			* @apiDescription Get Query object itself    
			*
			* @apiParam {Number} query* Numeric Query object identifier.
			*
			* @apiSuccess {Query Object} Query Object
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/					
			case  'object':
				return get_object_public(TYPE_QUERY, $query, false);
			break;
			
			/**
			* @api {get} /query/multiple_object
			* @apiVersion 0.1.0
			* @apiName Get Multiple Query Objects
			* @apiGroup Queries
			* @apiDescription Get multiple Query objects using one request
			*
			* @apiParam {Array} query* Array containing all the identifiers of queries objects that need to get
			*
			* @apiSuccess {Array} Array with FlexiDB Query objects
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/					
			case 'multiple_object':
				
				$result = array();
				$query_args = (array)$query;

				for ($i=0; $i < count($query_args); $i++) {
					$cur_res = get_object_public(TYPE_QUERY, $query_args[$i], false);
					if (is_fx_error($cur_res)) {
						return new FX_Error(__METHOD__, 'query['.$query_args[$i].']:'.$cur_res);
					}
					$result[] = $cur_res;	
				}

				return $result;				
				
			break;

			/**
			* @api {get} /query/html
			* @apiVersion 0.1.0
			* @apiName Get Query HTML
			* @apiGroup Queries
			* @apiDescription Execute query and get its results as HTML table    
			*
			* @apiParam {Mixed} query* Numeric Query object identifier or array with query options.
			* @apiParam {Number} set_id=0 Data Set ID if required getting objects from specified Data Set only.
			* @apiParam {Number} limit=0 Using to limit your query results to those that fall within a specified range. 
			* @apiParam {Number} offset=0 Says to skip that many rows before beginning to return rows.
			* @apiParam {Number} linked_object_type_id=0 Used in conjunction with linked_object_id to show only objects linked with specified  linked_object_type_id.linked_object_id
			* @apiParam {Number} linked_object_id=0 Used in conjunction with linked_object_type_id to show only objects linked with specified  linked_object_type_id.linked_object_id
			* @apiParam {Number} search_string=null Filter rows by some field value 
			*
			* @apiSuccess {HTML Table} HTML table with query results
			* @apiSuccess {Array} Query result
			* @apiSuccessExample
			* 	{
			*		"object_id": "<table>...</table>",
			*		"object_id": "<table>...</table>",
			*		....
			*	}
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/			
			case 'html':

				return exec_fx_query_html($query,
										  $this -> args['set_id'],
										  $this -> args['limit'],
										  $this -> args['offset'],
										  $this -> user_instance,
										  $this -> args['linked_object_type_id'],
										  $this -> args['linked_object_id'],
										  $this -> args['search_string']);

			break;
			
			/**
			* @api {get} /query/multiple_html
			* @apiVersion 0.1.0
			* @apiGroup Queries
			* @apiName Get Multiple Query HTML
			* @apiDescription Execute multiple queries in a single request and get an array with HTML tables
			*
			* @apiParam {Array} query* Array with all query options. Each element of the array contains the set of parameters are the same with GET Query method (query [, set_id] [, limit] [, offset] [, linked_object_type_id] [, linked_object_id] [, search_string])
			*
			* @apiSuccess {Array} Multiple Query results
			* @apiSuccessExample
			* {
			* 	{
			*		"object_id": "<table>...</table>",
			*		"object_id": "<table>...</table>",
			*		....
			*	},
			* 	{
			*		"object_id": "<table>...</table>",
			*		"object_id": "<table>...</table>",
			*		....
			*	},
			*	....
			* }
			*
			* @apiError {error} Error message with reference to query where error occured
			*
			* @apiEnd
			*/	
			case 'multiple_html':

				$result = array();
	
				$query_args = (array)$query;
	
				for ($i=0; $i < count($query_args); $i++) {
					$args = $query_args[$i];
	
					$cur_res = exec_fx_query_html($args['query'],
												  $args['set_id'], 
												  $args['limit'], 
												  $args['offset'], 
												  $this -> user_instance, 
												  $args['linked_object_type_id'], 
												  $args['linked_object_id'], 
												  $args['search_string']);

					if (is_fx_error($cur_res)) {
						return new FX_Error(__METHOD__, 'query['.$i.']:'.$cur_res);
					}

					$result[] = $cur_res;	
				}

				return $result;

			break;
			
			/**
			* @api {get} /query/multiple
			* @apiVersion 0.1.0
			* @apiName Get Multiple Query Result
			* @apiDescription Execute multiple queries in a single request and get an array with the results
			*
			* @apiParam {Array} query* Array with all query options. Each element of the array contains the set of parameters are the same with GET Query method (query [, set_id] [, limit] [, offset] [, linked_object_type_id] [, linked_object_id] [, search_string])
			*
			* @apiSuccess {Array} Multiple Query results
			* @apiSuccessExample
			* {
			* 	{
			*		"object_id": {"field_name" => [field_value], ...},
			*		"object_id": {"field_name" => [field_value], ...},
			*		....
			*	},
			* 	{
			*		"object_id": {"field_name" => [field_value], ...},
			*		"object_id": {"field_name" => [field_value], ...},
			*		....
			*	},
			*	....
			* }
			*
			* @apiError {error} Error message with reference to query where error occured
			*
			* @apiEnd
			*/
			case 'multiple':

				$result = array();

				$query_args = (array)$query;

				for ($i=0; $i < count($query_args); $i++) {
					$args = $query_args[$i];

					if (caching_enabled() && is_numeric($args['query'])) {
						$cur_res = get_cached_query_result($args['query'], 
														   $this -> user_instance,
														   $args['set_id'], 
														   $args['offset'],												   
														   $args['limit'],
														   $args['linked_object_type_id'],
														   $args['linked_object_id']);
					}
					else {
						$cur_res = exec_fx_query($args['query'],
												 $args['set_id'],
												 $args['limit'],
												 $args['offset'],
												 $user_instance,
												 $args['linked_object_type_id'],
												 $args['linked_object_id'],
												 $args['search_string']);
					}

					if (is_fx_error($cur_res)) {
						return new FX_Error(__METHOD__, 'query['.$i.']:'.$cur_res);
					}
					
					$result[] = $cur_res;	
				}

				return $result;

			break;	
		
			/**
			* @api {get} /query
			* @apiVersion 0.1.0
			* @apiName Get Query Result
			* @apiDescription Execute query and get its results    
			*
			* @apiParam {Mixed} query* Numeric Query identifier or array with query options.
			* @apiParam {Number} set_id=0 Data Set ID if required getting objects from specified Data Set only.
			* @apiParam {Number} limit=0 Using to limit your query results to those that fall within a specified range. 
			* @apiParam {Number} offset=0 Says to skip that many rows before beginning to return rows.
			* @apiParam {Number} linked_object_type_id=0 Used in conjunction with linked_object_id to show only objects linked with specified  linked_object_type_id.linked_object_id
			* @apiParam {Number} linked_object_id=0 Used in conjunction with linked_object_type_id to show only objects linked with specified  linked_object_type_id.linked_object_id
			* @apiParam {Number} search_string=null Filter rows by some field value 
			*
			* @apiSuccess {Array} Query result
			* @apiSuccessExample
			* 	{
			*		"object_id": {"field_name" => [field_value], ...},
			*		"object_id": {"field_name" => [field_value], ...},
			*		....
			*	}
			*
			* @apiError {error} Error message
			*
			* @apiEnd
			*/
			default:
		
				$args = $this -> args;
				
				if (caching_enabled() && is_numeric($query)) {
					return get_cached_query_result($query, 
												   $this -> user_instance,
												   $args['set_id'], 
												   $args['offset'],												   
												   $args['limit'],
												   $args['linked_object_type_id'],
												   $args['linked_object_id'],
												   $args['sort_key'],
												   $args['order'],
												   $args['filter_by'],
												   $args['group']);
				}
				else {
					return exec_fx_query($query,
										 $args['set_id'],
										 $args['limit'],
										 $args['offset'],
										 $user_instance,
										 $args['linked_object_type_id'],
										 $args['linked_object_id'],
										 $args['filter_by']);
				}
		}	
	}
}