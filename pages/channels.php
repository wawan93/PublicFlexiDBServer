<?php
	
	if (isset($_POST['channel_action']))
	{
		global $fx_error;

		$fx_api = new FX_API_Client();
		$channel_id = 0;

		switch ($_POST['channel_action'])
		{
			case 'create':
				$result = $fx_api -> execRequest('channel/object', 'POST', $_POST);
				if (!is_fx_error($result)) {
					$channel_id = $result;
					$result = update_object_field(1, $_POST['schema_id'], 'channel', $channel_id);
				}
			break;
			case 'update':
				$result = $fx_api -> execRequest('channel', 'PUT', $_POST);
				$channel_id = $_POST['channel_id'];
			break;
			case 'delete':
				$result = $fx_api -> execRequest('channel', 'DELETE', 'channel_id='.$_POST['channel_id']);
				if (!is_fx_error($result)) {
					update_object_field(1, $_POST['schema_id'], 'channel', 0);
					$channel_id = '';
				}
			break;
			default:
				$result = new FX_Error('channel_action', 'Unknown Channel action.');
		}

		if ($channel_id && !empty($_FILES['image'])) {
			$result = $fx_api -> execRequest('channel/image', 'post', array('channel_id' => $channel_id ,'image'=> '@'.$_FILES['image']['tmp_name']));
			if (is_fx_error($result)) $result -> add('upload_channel_image', $result -> get_error_message());
		}
		
		if (is_fx_error($result)) {
			$fx_error = $result;
		}
		else {
			header('Location:'.replace_url_param('edit_channel', $channel_id));
		}
	}
	
	//*****************************************************************************************************************************

	function _channel_metabox($schema_id)
	{
		global $fx_error;

		$schema_object = get_object(TYPE_DATA_SCHEMA, $schema_id);
		
		$channel_id = get_schema_channel($schema_id);

		if (is_fx_error($schema_object)) {
			return 'Invalid Data Schema ID.';
		}

		$fx_api = new FX_API_Client();

		$channel_object = $fx_api -> execRequest('channel/object', 'GET', 'channel_id='.$channel_id);
		$channel_fields = $fx_api -> execRequest('channel/fields', 'GET');
		$user_fields = $fx_api -> execRequest('user/base_fields', 'GET');

		if (is_fx_error($channel_fields)) {
			return $channel_fields;
		}
		else
		{
			if (!$fx_error->is_empty) {
				$errors = $fx_error->get_error_messages();
				for ($i=0; $i<count($errors); $i++) {
					$out .= '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
				}
			}

			if (is_fx_error($channel_object)) {
				$channel_object = array();
			}
			
			if (is_fx_error($user_fields)) {
				$user_fields = array();
			}

			$out .= '
			<form method="post" action="" enctype="multipart/form-data">
				<input type="hidden" id="channel_action" name="channel_action" value="">
				<input type="hidden" name="channel_id" value="'.$channel_id.'">
				<input type="hidden" name="schema_id" value="'.$schema_id.'">
				<table class="profileTable">
				<tr style="font-size:14px">
					<th>Channel ID:</th>
					<td><b>'.(isset($channel_object['channel_id']) ? $channel_object['channel_id'] : 'New Channel').'</b></td>
				</tr>';

			if (isset($channel_object['channel_id']))
			{
				$out .= '
					<tr>
						<th>Created:</th>
						<td>'.date('F j, Y \a\t g:i a', $channel_object['created']).'</td>
					</tr>
					<tr>
						<th>Modified:</th>
						<td>'.date('F j, Y \a\t g:i a', $channel_object['modified']).'</td>
					</tr>';
			}

			$hide_fields = array('sfx_fields', 'base_fields');

			foreach ($channel_fields as $field)
			{
				if (!in_array($field['name'],$hide_fields)) {
					$field['value'] = isset($_POST[$field['name']]) ? $_POST[$field['name']] : $channel_object[$field['name']];
					$ctrl = get_field_control($field);
					$out .= '
					<tr>
						<th>'.$ctrl['label'].'</th>
						<td>'.$ctrl['control'].'</td>
					</tr>';
				}
			}
	
			//$out .= '<pre>'.print_r($channel, true).'</pre>';
			
			if ($channel_id) {
				$out .= '
					<tr>
						<td colspan="2"><hr></td>
					</tr>						
					<tr>
						<th>Channel Icon</th>
						<td>
							<img style="border:1px #ccc solid" src="'.($channel_object['icon_small'] ? $channel_object['icon_small'].'?'.time() : CONF_IMAGES_URL.'mime_image.png').'">
							<br><input type="file" name="image" accept="image/jpeg,image/png,image/gif">				
						</td>
					</tr>';
			}

			if ($schema_object['user_fields'] || $channel_object['sfx_fields'])
			{
				$out .= '<tr><td colspan="2"><hr></td></tr>';

				$sfx_type_fields = get_type_fields($schema_object['user_fields'], "custom");
				
				$sfx_fields = array();
				$fields_info = '';

				if (!is_fx_error($type_fields)) {
					foreach ($sfx_type_fields as $field) {
						unset($field['object_type_id'], $field['length'], $field['sort_order']);
						$sfx_fields[$field['name']] = $field;
						$fields_info .= '-&nbsp;<b>'.($field['caption'] ? $field['caption'] : $field['name']).'</b> ('.strtoupper($field['type']).') <i>'.$field['description'].'</i><br>';
					}
					$sfx_fields = json_encode($sfx_fields);
				}
				else {
					$sfx_fields = '';
				}

				$out .= "
				<tr>
					<th>Extra User Fields:</th>
					<td>
						<input type=\"hidden\" name=\"sfx_fields\" value='".addslashes($sfx_fields)."'>
						".($channel_object['sfx_fields'] != $sfx_fields ? "<font color=\"#FF0000\">Update channel to submit subscription fields.</font></br>" : "")."
						$fields_info
					</td>
				</tr>";
			}

			$out .= '
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			<tr>
				<th>Base User Fields:</th>
				<td>
					Check the basic user fields that you want to request from user:';

			foreach ($user_fields as $field_name) {
				$ch = in_array($field_name, $channel_object['base_fields']) ? ' checked="checked"' : '';
				$out .= '<br><input type="checkbox" name="base_fields[]" value="'.$field_name.'"'.$ch.'>'.$field_name;
			}

			$out .= '					
				</td>
			</tr>';

			$out .= '</table><hr>';

			if ($channel_id) {
				$out .= '<input class="button blue" type="button" value="Update Channel" onclick="$(\'#channel_action\').attr(\'value\',\'update\'); submit();"/>';
				$out .= '<input class="button red" type="button" value="Delete Channel" onclick="$(\'#channel_action\').attr(\'value\',\'delete\'); submit();"/>';				
			}
			else {
				$out .= '<input class="button green" type="button" value="Create Channel" onclick="$(\'#channel_action\').attr(\'value\',\'create\'); submit();"/>';
			}

			$out .= '
				&nbsp;<span style="font-size:20px; color:#CCC">|</span>&nbsp;
				<a class="button blue" href="'.replace_url_param('edit_channel', '').'">Back To Data Schema</a>
			</form>';
		}

		return $out;
	}
	
	if (isset($_GET['edit_channel']))
	{
		$current_schema = get_object($_GET['object_type_id'], $_GET['object_id']);
		$server_settings = get_fx_option('server_settings');
		
		if ($server_settings['api_validated'] === 1 && $server_settings['dfx_key'])
		{
			$mb_data = array('header' => array('suffix' => !is_fx_error($current_schema) ? ' - '.$current_schema['display_name'].' - Channel' : ''),
							 'body' => array('content' => _channel_metabox($current_schema['object_id'], $current_schema['channel'])),
							 'footer' => array('hidden' => true));

			fx_show_metabox($mb_data);			
		}
		else
		{
			fx_show_metabox(array('body' => array('content' => new FX_Error('show_data_sets', 'Please validate your DFX Server.')), 'footer' => array('hidden' => true)));
		}
	}
	else
	{
		fx_show_metabox(array('body' => array('content' => new FX_Error('show_data_sets', 'Please select Data Schema.')), 'footer' => array('hidden' => true)));		
	}

?>