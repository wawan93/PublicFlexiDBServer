<?php

class FX_API_Sync extends FX_API
{
	protected function _get()
	{		
		$user = $this->user_instance;
		$set_id = $this->args['set_id'];
		
		$set_object = get_object(TYPE_DATA_SET, $set_id);
		
		if (is_fx_error($set_object)) {
			return new FX_Error(__METHOD__, _('Invalid Data Set identifier')); 
		}
		
		$schema_id = $set_object['schema_id'];
		
		$last_sync = get_sync($user['subscription_id'], $set_id);

		if (is_fx_error($last_sync)) {
			return $last_sync;
		}

		$objects = $enums = $links = array();

		$schema_types = get_schema_types($schema_id);
		
		$map = array(
			'objects' => array(),
			'types' => array(),
			'enums' => array(),
			'link_types' => array(),
			'forms' => array());

		if (in_array($set_id, $user['sets']) || $this->is_admin)
		{
			// get all objects if the user is data set admin
			foreach(array_keys($schema_types) as $object_type_id) {
				$objects[$object_type_id] = get_objects_by_type($object_type_id, $schema_id, $set_id);
				
				$map['types'][] = $object_type_id;
			}
		}
		else
		{
			// get objects depending on Data Schema role
			foreach ($user['schema_permissions'][$schema_id] as $object_type_id=>$permission) {
				if ($permission & U_GET) {
					$objects[$object_type_id] = get_objects_by_type($object_type_id, $schema_id, $set_id);
				}
				
				$map['types'][] = $object_type_id;
			}

			// get objects depending on Data Set role
			foreach ($user['set_permissions'][$set_id] as $object_type_id=>$permission) {
				if ($permission & U_GET) {
					$objects[$object_type_id] = get_objects_by_type($object_type_id, $schema_id, $set_id);
				}
				
				$map['types'][] = $object_type_id;
			}
	
			// get objects linked with user subscription		
			foreach ($user['links'] as $object_type_id => $links) {
				if (in_array($object_type_id, $user['links'])) {
					foreach ($links as $object_id) {
						if (!isset($objects[$object_type_id][$object_id])) {
							$object = get_object($object_type_id, $object_id);
							if (!is_fx_error($object)) {
								$objects[$object_type_id][$object_id] = $object;
							}
						}
					}
				}
				
				$map['types'][] = $object_type_id;
			}
			
			$map['types'] = array_unique($map['types']);

		}
		
		ksort($objects);

		$last_update = (int)$last_sync['updated'];

		foreach ($objects as $objects_type_id => $objs)
		{
			$map['objects'][$objects_type_id] = array_keys($objs);

			foreach ($objs as $object_id => $object) {
				if ($object['modified'] < $last_update) {
					unset($objects[$objects_type_id][$object_id]);
				}
			}
		}

		$forms = get_objects_by_type(TYPE_DATA_FORM, $schema_id);
		
		$queries = get_objects_by_type(TYPE_QUERY, $schema_id);

		switch ($this -> verb)
		{
			
			/**
			* @api {get} /sync/map
			* @apiVersion 0.1.0
			* @apiName Get Subscription Map
			* @apiGroup Synchronization
			* @apiDescription Get all data in simple form (IDs) to compare and ensure that all data were successfully received in main sync request.
			*
			* @apiParam {Number} set_id* Data Set ID
			*
			* @apiSuccess {Array} Array with synchronization data
			* @apiSuccessExample
			* 	{
			*		"objects": {
			*			...
			*			},
			*		"types": {
			*			...
			*			},
			*		"enums": {
			*			...
			*			},
			*		"forms": {
			*			...
			*			},
			*		"queries": {
			*			...
			*			},
			*		"links": {
			*			...
			*			}
			*	}
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'map':
				
				sort($map['types']);
		
				foreach ($map['types'] as $object_type_id) {
					
					$type_links = get_type_links($object_type_id);
					
					if(!is_fx_error($type_links)) {
						foreach ($type_links as $id => $link_data) {
							if (!isset($map['link_types'][$object_type_id.'-'.$id]) && !isset($map['link_types'][$id.'-'.$object_type_id])) {
								$map['link_types'][$object_type_id.'-'.$id] = array(
									'object_type_1_id' => $object_type_id,
									'object_type_2_id' => $id,
									'relation' => $link_data['relation'],
									'system' => $link_data['system'],
									'description' => $link_data['description']);
							}
						}
					}					
		
					foreach (get_type_fields($object_type_id, 'custom') as $field) {
						if (is_numeric($field['type'])) {
							if (!isset($enums[$field['type']])) {
								$enum_type = get_enum_type($field['type']);
								if (is_fx_error($enum_type)) {
									add_log_message(__METHOD__, $enum_type->get_error_message());
								}
								else {
									$enums[$field['type']] = $enum_type;

									$enum_fields = get_enum_fields($field['type']);
									
									if (is_fx_error($enum_fields)) {
										add_log_message(__METHOD__, $enum_fields->get_error_mesage());
									}
									else {
										$enums[$field['type']]['fields'] = $enum_fields;
									}
								}
							}
						}
					}
				}
				
				$map['queries'] = array();
				
				foreach ($queries as $id => $query) {
					$map['queries'][$id] = array(
						'code' => $query['code'],
						'main_type' => $query['main_type']);	
				}
				
				$map['enums'] = $enums;
				$map['forms'] = array_keys($forms);

				return $map;
			
			break;
			/**
			* @api {get} /sync
			* @apiVersion 0.1.0
			* @apiName Get Synchronization Data
			* @apiGroup Synchronization
			* @apiDescription Get all data from the specified data set for the current user. If confirmed that the data has been successfully received, the next requests will return only the changed/new data.
			*
			* @apiParam {Number} set_id* Data Set ID
			*
			* @apiSuccess {Array} Array with synchronization data
			* @apiSuccessExample
			* 	{
			*		"objects": {
			*			...
			*			},
			*		"types": {
			*			...
			*			},
			*		"enums": {
			*			...
			*			},
			*		"forms": {
			*			...
			*			},
			*		"queries": {
			*			...
			*			},
			*		"links": {
			*			...
			*			}
			*	}
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			default:
			
				foreach ($map['types'] as $object_type_id) {
					$types[$object_type_id] = get_type($object_type_id);
					array_filter($types[$object_type_id], 'is_numeric');
				}
				
				$form_fields = array_keys(get_type_fields(TYPE_DATA_FORM, 'custom'));
				
				foreach ($forms as $form_id => $form) {
					if ($form['modified'] < $last_update) {
						unset($forms[$form_id]);
					}
					else {
						foreach ($form as $f_name => $f_data) {
							if (!in_array($f_name, $form_fields)) {
								unset($forms[$form_id][$f_name]);
							}
						}
					}
				}

				foreach (array_keys($queries) as $id) {
					$queries[$id] = get_cached_query_result($id, $user, $set_id);				
				}

				$result = array(
					'objects' => $objects,
					'types' => $types,
					'enums' => $enums,
					'forms' => $forms,
					'queries' => $queries,
					'links' => get_links_list($map['types']));

				return $result;
		}
	}

	protected function _put()
	{
		switch ($this -> verb)
		{
			/**
			* @api {put} /sync/confirm
			* @apiVersion 0.1.0
			* @apiName Confirm Synchronization
			* @apiGroup Synchronization
			* @apiDescription Confirm that last sync process was successfully completed and next request have to return only new/changed data.
			*
			* @apiParam {Number} set_id* Data Set ID
			*
			* @apiSuccess {true} Successfully confirmed
			*
			* @apiError {error} Error Message
			*
			* @apiEnd
			*/
			case 'confirm':

				if (!object_exists(TYPE_DATA_SET, $this->args['set_id'])) {
					return new FX_Error(__METHOD__, _('Invalid Data Set identifier')); 
				}

				$res = confirm_sync($this->user_instance['subscription_id'], $this->args['set_id']);

				if (is_fx_error($res)) {
					return $res;
				}

				return true;

			break;
			default:
				return new FX_Error(__METHOD__, _('Invalid verb value'));			 
		}
	}
}