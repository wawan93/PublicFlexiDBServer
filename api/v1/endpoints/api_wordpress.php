<?php
/*
Version: 0.1
API Method Name: Wordpress
Description: Wordpress
Author: Flexiweb
Arguments:
*/

class FX_API_Wordpress extends FX_API
{
	protected function _get()
	{	
		$args = $this -> args;

		if (!$args['schema_id']) {
			return new FX_Error(__METHOD__, _('Unknown schema identifier'));
		}
		
		if (!$this -> is_admin && !in_array($args['schema_id'], $this -> user_instance['schemas'])) {
			return new FX_Error('access_forbidden', _('Subscribe to this channel to be able to create blogs'));
		}
		
		switch ($this -> verb)
		{
			case 'signup_form':
			
				$result = array();

				if ($templates = get_objects_by_type(TYPE_WP_TMPL_SIGNUP, $args['schema_id']))
				{
					foreach($templates as $tmpl) {
						if ($tmpl['enabled'] && $tmpl['associated_type']) {
							$fields = array();
							
							foreach(get_type_fields($tmpl['associated_type'], "custom") as $field_name => $field_options) {
								if($field_options['mandatory']) {
									$fields[$field_name] = $field_options;
								}
							}		

							if ($fields) {
								$tmpl_id = $tmpl['object_id'];
								$header = $tmpl['header'];
								$hint = $tmpl['hint'];

								if(!is_fx_error($fields)) {
									$result[] = array('tmpl_id' => $tmpl['object_id'], 'header' => $tmpl['header'], 'hint' => $tmpl['hint'], 'field' => $fields);
								}
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

	protected function _post()
	{
		$args = $this->args;

		if (!$args['schema_id']) {
			return new FX_Error(__METHOD__, _('Unknown schema identifier'));
		}
		
		if (!$this->is_admin && !in_array($args['schema_id'], $this -> user_instance['schemas'])) {
			return new FX_Error('access_forbidden', _('Subscribe to this channel to be able to create blogs'));
		}

		switch ($this -> verb)
		{
			case 'blog':

				/* Create Data Set for new WP Blog */
				/*---------------------------------------------------------------------------------------------*/
				$set_type_id = TYPE_DATA_SET;

				$object = array();
				$object['object_type_id'] = TYPE_DATA_SET;
				$object['schema_id'] = $args['schema_id'];
				$object['set_id'] = 0;
				$object['name'] = $args['blog_title'];
				$object['display_name'] = $args['blog_title'];
				$object['wp_url'] = $args['blog_url'] ? $args['blog_url'] : $_SERVER['HTTP_REFERER'];
				$object['description'] = $args['description'] ? $args['description'] : '';//WP blog instance for '.$args['blog_title'];

				$set_id = add_object($object, true);
		
				if (is_fx_error($set_id)) {
					add_log_message(__METHOD__.'_data_set', print_r($set_id -> get_error_messages(),true));
					add_log_message(__METHOD__.'_data_set', print_r($object, true));
					return new FX_Error(__METHOD__.'_data_set', _('Unable to create Data Set'));
				}
		
				/* Create link between User Subscription and new blog (Data Set) */
				/*---------------------------------------------------------------------------------------------*/
				// If new WP website registered with DFX Key we have no specific user subscription
				
				if ($this->user_instance['subscription_id'])
				{
					add_link_type(TYPE_DATA_SET, $this -> user_instance['object_type_id'], RELATION_N_N, 0, true);
		
					$link_res = add_link(TYPE_DATA_SET, $set_id, TYPE_SUBSCRIPTION, $this -> user_instance['subscription_id']);
					
					if (is_fx_error($link_res)) {
						delete_object($set_type_id, $set_id);
						add_log_message(__METHOD__.'_sfx_link', print_r($link_res -> get_error_messages(),true));
						add_log_message(__METHOD__.'_sfx_link', TYPE_DATA_SET.'-'.$set_id.'-'.TYPE_SUBSCRIPTION.'-'.$this -> user_instance['subscription_id']);
						return new FX_Error(__METHOD__.'_sfx_link', _('Unable to link User with Blog'));
					}
				}

				/* Apply signup templates */
				/*---------------------------------------------------------------------------------------------*/

				if ($templates = get_objects_by_type(TYPE_WP_TMPL_SIGNUP, $args['schema_id']))
				{
					foreach ($templates as $tmpl)
					{
						if ($tmpl['enabled'] && type_exists($tmpl['associated_type']))
						{
							$object = array();
							$object['object_type_id'] = $tmpl['associated_type'];
							$object['display_name'] = $tmpl['object_name'];
							$object['schema_id'] = $args['schema_id'];
							$object['set_id'] = $set_id;

							foreach ($args['fields'][$tmpl['object_id']] as $key => $value) {
								$object[$key] = $value;
							}

							$object_id = add_object($object, true);
		
							if (is_fx_error($object_id)) {
								add_log_message(__METHOD__.'_apply_signup_tmpl', print_r($object_id -> get_error_messages(), true));
								add_log_message(__METHOD__.'_apply_signup_tmpl', print_r($object, true));
							}
						}
					}
				}
				
				/*---------------------------------------------------------------------------------------------*/

				return $set_id;
				
			break;
			default:
				return new FX_Error(__METHOD__, _('Invalid verb value'));
		}	
	}
}