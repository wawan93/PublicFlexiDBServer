<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}

	$app_group = get_schema_app_group($_SESSION['current_schema']);
	
	if ($app_group) :
	
		global $data_schema;
	
		$options = array('object_type_id' => TYPE_APPLICATION,
						 'object_id' => $app_group['object_id'],
						 //'mode' => 'view',
						 'fields' => array('object_id','created','name'),
						 'buttons' => array('update', 'delete'));

		$options['custom_buttons'] = array('<a class="button blue" href="'.URL.'app_editor/app_pages">Edit Application</a>',
										   '<a class="button blue" href="'.URL.'app_editor/app_release_manager">Release Manager</a>');
		
		$mb_data = array('header' => array('suffix' => ' - '.$data_schema['display_name']),
						 'body' => array('function' => 'object_form', 'args' => array($options)),
					 	 'footer' => array('hidden' => true));
		
		fx_show_metabox($mb_data);	
	
	else:
		function create_app_group($schema_id)
		{
			$schema_id = intval($_POST['schema_id']);
			
			if (!object_exists(TYPE_DATA_SCHEMA, $schema_id)) {
				return new FX_Error(__FUNCTION__, _('Data Schema does not exists'));
			}
			
			if (!$app_group = get_schema_app_group($_POST['schema_id'])) {
				$app_group_object = array(
					'object_type_id' => TYPE_APPLICATION,
					'schema_id' => $schema_id,
					'set_id' => 0,
					'display_name' => 'app_group_'.$schema_id
				);
				
				$group_id = add_object($app_group_object);
	
				if (!is_fx_error($group_id)) {
					update_object_field(TYPE_DATA_SCHEMA, $schema_id, 'app_group', $group_id);
				}
				
				return $group_id;
			}
			else {
				return new FX_Error(__FUNCTION__, _('App group already created for this Data Schema'));
			}
		}
		
		if (isset($_POST['create_app_group'])) {
			$app_group_id = create_app_group($_POST['schema_id']);
			if (!is_fx_error($app_group_id)) {
				fx_redirect(URL.'app_editor/app_pages');
			}
		}
		
		function _app_group_form()
		{
			global $fx_error;
			
			
			$app_group = get_schema_app_group($_SESSION['current_schema']);
			
			if (!$app_group) {
				
				if (!$fx_error->is_empty) {
					$errors = $fx_error->get_error_messages();
					for ($i=0; $i<count($errors); $i++) {
						$out .= '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
					}
				}			
				
				echo '
				<p>&nbsp;</p>
				<p>'._('You need to add the application group to be able to add and edit applications').'.</p>
				<p>&nbsp;</p>
				<form action="" method="post">
				<input type="hidden" name="create_app_group">
				<input type="hidden" name="schema_id" value="'.$_SESSION['current_schema'].'">
				<input type="submit" class="button green" value="'._('Create Application Group').'">
				</form>
				<p>&nbsp;</p>';
			}
			else {
				fx_redirect(URL.'app_editor/app_pages');
			}
		}
	
		$mb_data = array('body' => array('function' => '_app_group_form'),
						 'footer' => array('hidden' => true));
		
		fx_show_metabox($mb_data);

	endif;
?>