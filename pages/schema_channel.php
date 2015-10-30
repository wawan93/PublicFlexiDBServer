<?php
	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}	
	
	if (isset($_POST['channel_action']))
	{
		global $fx_error;

		$fx_api = new FX_API_Client();
		$channel_id = 0;
		$schema_id = intval($_POST['schema_id']);
		
		if (!$_POST['sub_chnl_alias']) {
			$sca = $_POST['sub_chnl_alias'] = $_POST['sub_chnl_alias_pl'];
			unset($_POST['sub_chnl_alias_pl']);
		}
		else {
			$sca = $_POST['sub_chnl_alias'];
		}

		$sca_pl = $_POST['sub_chnl_alias_pl'];
		
		switch ($_POST['channel_action'])
		{
			case 'create':
			
				$result = $fx_api -> execRequest('channel/object', 'POST', $_POST);
				
				if (!is_fx_error($result)) {
					$channel_id = $result;
					
					$schema_fields = array(
						'object_type_id' => TYPE_DATA_SCHEMA,
						'object_id' => $schema_id,
						'channel' => $channel_id,
						'sub_chnl_alias' => $sca,
						'sub_chnl_alias_pl' => $sca_pl
					);
	
					$result = update_object($schema_fields);
				}
			break;
			case 'update':

				$result = $fx_api -> execRequest('channel', 'PUT', $_POST);
				$channel_id = $_POST['channel_id'];
				
				if (!is_fx_error($result)) {
					$schema_fields = array(
						'object_type_id' => TYPE_DATA_SCHEMA,
						'object_id' => $schema_id,
						'sub_chnl_alias' => $sca,
						'sub_chnl_alias_pl' => $sca_pl
					);
					$result = update_object($schema_fields);
				}
				
			break;
			case 'delete':
			
				$result = $fx_api -> execRequest('channel', 'DELETE', 'channel_id='.$_POST['channel_id']);
				
				if (!is_fx_error($result)) {
					update_object_field(1, $schema_id, 'channel', 0);
					$channel_id = '';
				}
				
			break;
			default:
				$result = new FX_Error('channel_action', _('Unknown Channel action'));
		}
		
		if (is_fx_error($result)) {
			$fx_error = $result;
		}
		else {
			fx_redirect(URL.'schema_admin/schema_channel');
		}
	}
	
	//*****************************************************************************************************************************

	function _channel_metabox($schema_id)
	{
		global $fx_error;

		$schema_object = get_object(TYPE_DATA_SCHEMA, $schema_id);
		
		$channel_id = get_schema_channel($schema_id);

		if (is_fx_error($schema_object)) {
			return _('Invalid Data Schema ID');
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
				<tr style="font-size:16px">
					<th>'._('Channel ID').':</th>
					<td><b>'.(isset($channel_object['channel_id']) ? $channel_object['channel_id'] : _('New Channel')).'</b></td>
				</tr>';

			if (isset($channel_object['channel_id']))
			{
				$out .= '
				  <tr>
					  <th>'._('Channel Link').':</th>
					  <td><a href="'.FX_SERVER.'channels/'.$channel_object['channel_id'].'" target="_blank">'.FX_SERVER.'channels/'.$channel_object['channel_id'].'</a></td>
				  </tr>
				  <tr>
					  <th>'._('Created').':</th>
					  <td>'.date('F j, Y \a\t g:i a', $channel_object['created']).'</td>
				  </tr>
				  <tr>
					  <th>'._('Modified').':</th>
					  <td>'.date('F j, Y \a\t g:i a', $channel_object['modified']).'</td>
				  </tr>';
			}

			$hide_fields = array('sfx_fields', 'base_fields', 'sub_chnl_alias', 'sub_chnl_alias_pl');

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

			$bgcolor = isset($_GET['edit_sub_chnl_alias']) ? ' style="background-color: #FFEBE8;"' : '';
			
			$out .= '
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			<tr'.$bgcolor.'>
				<th>Sub-channel alias:</th>
				<td><input type="text" name="sub_chnl_alias" value="'.$schema_object['sub_chnl_alias'].'"></td>
			</tr>
			<tr'.$bgcolor.'>
				<th>Plural form:</th>
				<td><input type="text" name="sub_chnl_alias_pl" value="'.$schema_object['sub_chnl_alias_pl'].'"></td>
			</tr>
			<tr>
				<th></th>
				<td><p><i>'._('Alias will be used instead of the "sub-channel" term').'</i></p></td>
			</tr>			
			';
				
			if ($schema_object['user_fields'] || $channel_object['sfx_fields'])
			{
				$out .= '
				<tr>
					<td colspan="2"><hr></td>
				</tr>';

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
						".(json_encode($channel_object['sfx_fields']) != $sfx_fields ? "<font color=\"#FF0000\">"._('Update channel to submit subscription fields')."</font></br>" : "")."
						$fields_info
					</td>
				</tr>";
			}

			$out .= '
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			<tr>
				<th>Requested Fields:</th>
				<td>
					<p><i>'._('Check the basic user fields that you want to request from user').':</i></p>';

			foreach ($user_fields as $field_name) {
				$ctrl_id = 'chk_'.$field_name;
				$ch = in_array($field_name, $channel_object['base_fields']) ? ' checked="checked"' : '';
				$out .= '
				<input type="checkbox" name="base_fields[]" id="'.$ctrl_id.'" value="'.$field_name.'"'.$ch.'>
				<label for="'.$ctrl_id.'">'.$field_name.'</label>
				</br>';
			}

			$out .= '</td></tr></table><hr>';

			if ($channel_id) {
				$out .= '<input class="button blue" type="button" value="'._('Update Channel').'" onclick="$(\'#channel_action\').attr(\'value\',\'update\'); submit();"/>';
				$out .= '<input class="button red" type="button" value="'._('Delete Channel').'" onclick="$(\'#channel_action\').attr(\'value\',\'delete\'); submit();"/>';				
			}
			else {
				$out .= '<input class="button green" type="button" value="'._('Create Channel').'" onclick="$(\'#channel_action\').attr(\'value\',\'create\'); submit();"/>';
			}

			$out .= '
			</form>';
		}

		return $out;
	}
	
	global $data_schema;
	$server_settings = get_fx_option('server_settings');
	
	if ($server_settings['api_validated'] === 1 && $server_settings['dfx_key']) {
		$mb_data = array('header' => array('suffix' => $data_schema ? ' - '.$data_schema['display_name'] : ''),
						 'body' => array('content' => _channel_metabox($data_schema['object_id'], $data_schema['channel'])),
						 'footer' => array('hidden' => true));

		fx_show_metabox($mb_data);
	}
	else {
		fx_show_metabox(array('body' => array('content' => new FX_Error('show_data_sets', _('Please validate your DFX Server'))), 'footer' => array('hidden' => true)));
	}