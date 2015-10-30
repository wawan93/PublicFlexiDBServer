<?php
 
class FX_API_Widget extends FX_API
{
	protected function _get()
	{
		switch ($this -> verb)
		{
			case 'form':
			
				$form = get_object_public(TYPE_DATA_FORM, $this -> args['form_id'], false);
				
				if (!is_fx_error($form)) {
					$fields = json_decode($form['code'], true);
					$form['fields'] = $fields !== NULL ? $fields : array();
					
					foreach ($form['fields'] as $key=>$field) {
						if ($field['metric'] && $field['unit']) {
							$metric = get_metric($field['metric']);
							if (!is_fx_error($metric)) {
								$form['fields'][$key]['units'] = $metric['units'];
							}
							if ($metric['is_currency']) {
								$form['fields'][$key]['is_currency'] = 1;
							}
						}
						else {
							unset($form['fields'][$key]['metric'], $form['fields'][$key]['unit']);
						}
						
						if (is_numeric($field['type'])) {
							$enum_values = get_enum_fields($field['type']);
							if (!is_fx_error($enum_values)) {
								$form['fields'][$key]['enum'] = $enum_values;
							}
						}
					}
					
					$form['code'] = json_encode($form['fields']); //
				}

				return $form;
				
			break;
			case 'schema_forms':

				if (!$this -> args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown schema identifier'));
				}

				$objects = get_objects_by_type(TYPE_DATA_FORM, $this -> args['schema_id']);

				if (!is_fx_error($objects)) {
					foreach ($objects as &$object) {
						filter_private($object);
					}				
				}

				return $objects;

			break;

			case 'query':
			
				$query = get_object_public(TYPE_QUERY, $this -> args['query_id'], false);
				if (!is_fx_error($query)) {
					$fields = json_decode($query['code'], true);
					$query['fields'] = $fields !== NULL ? $fields : array();
					foreach ($query['fields'] as &$field) {
						if (is_numeric($field['type'])) {
							$field['enum_fields'] = get_enum_fields($field['type'], true);
						}
					}
				}
				
				return $query;
			break;
			case 'schema_queries':
			
				if (!$this -> args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown schema identifier'));
				}
				
				$objects = get_objects_by_type(TYPE_QUERY, $this -> args['schema_id']);
				
				if (!is_fx_error($objects)) {
					foreach ($objects as &$object) {
						filter_private($object);
					}				
				}

				return $objects;
				
			break;

			case 'schema_sets':
			
				if (!$this -> args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown schema identifier'));
				}

				$user_sets = (array)get_user_sets($this -> user_instance, $this -> args['schema_id']);

				$data_sets = array();
				
				foreach (get_objects_by_type(TYPE_DATA_SET, $this -> args['schema_id']) as $object)
				{
					$object_id = $object['object_id'];
					
					$set_admins = get_data_set_admins($object_id);
					
					if (!$this -> is_admin) {
						if (($object['disable_subscriptions']) || !count($set_admins)) {
							continue;
						}
					}

					$user_set = array_key_exists($object_id, $user_sets) || $this -> is_admin ? 1 : 0;

					$is_private = $object['public'] || $object['is_public'] ? 0 : 1;

					$roles = get_set_roles($object_id);
					
					if (is_fx_error($roles)) {
						$roles = array();
					}

					if ($is_public || $user_set) {
						$data_sets[$object_id]['object_id'] = $object_id;
						$data_sets[$object_id]['name'] = $object['name'];
						$data_sets[$object_id]['display_name'] = $object['display_name'];
						$data_sets[$object_id]['description'] = $object['description'];
						$data_sets[$object_id]['private'] = $is_private;
						$data_sets[$object_id]['admin'] = $set_admins;
						$data_sets[$object_id]['roles'] = $roles;
					}
				}

				return $data_sets;
			break;

			case 'exchange_rate':
			case 'currency_convert':

				$amount = urlencode($this->args['amount']);
				$from= urlencode($this->args['from']);
				$to = urlencode($this->args['to']);
			
				$url = "https://www.google.com/finance/converter?a=$amount&from=$from&to=$to";
			
				$get = file_get_contents($url);
			
				$get = explode("<span class=bld>", $get);
				$get = explode("</span>", $get[1]);
				
				$converted_amount = preg_replace("/[^0-9\.]/", null, $get[0]);
			
				return $converted_amount;
			
			break;

			default:
				return new FX_Error(__METHOD__, _('Invalid verb value'));
		}	
	}
}