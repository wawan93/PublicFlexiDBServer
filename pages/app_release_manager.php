<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}
	
	function escapeJsonString($value)
	{
		$escapers = array("\\", "/", "\n", "\r", "\t", "\x08", "\x0c");
		$replacements = array("\\\\", "\\/", "\\n", "\\r", "\\t", "\\f", "\\b");
		$result = str_replace($escapers, $replacements, $value);

		return $result;
	}

	function _app_metabox($schema_id, $channel_id = 0)
	{
		global $fx_error;

		if (!$app_group = get_schema_app_group($schema_id)) {
			fx_redirect(URL.'app_editor/app_group');
		}
		
		$app_id = $app_group['object_id'];
		$local_app = $app_group;

		if (is_fx_error($local_app)) {
			return '<div class="info">'._('Please select valid application').'</div>';
		}
		
		$schema_object = get_object(TYPE_DATA_SCHEMA, $schema_id);

		if (is_fx_error($schema_object)) {
			return '<div class="info">'._('Invalid Data Schema ID').'</div>';
		}

		$fx_api = new FX_API_Client();
		$args = array('remote_app_id'=>$app_id, 'remote_schema_id'=>$schema_id);

		$app_object = $fx_api -> execRequest('app/dev_object', 'GET', $args);

		$versions = get_app_data($app_id);

		if (is_fx_error($versions)) {
			return '<div class="info">'._('Get application versions').': '.$versions->get_error_message().'</div>';
		}

		$app_fields = $fx_api -> execRequest('app/fields', 'GET');

		if (is_fx_error($app_fields)) {
			return $app_fields;
		}
		else
		{
			if (!$fx_error->is_empty) {
				$errors = $fx_error->get_error_messages();
			
				foreach ((array)$fx_error->errors as $code => $msgs) {
					foreach ((array)$msgs as $msg) {
						$out .= '<div class="msg-error" title="'.$code.'">ERROR: '.$msg.'</div>';
					}
				}
			}

			if (is_fx_error($app_object)) {
				$app_object = array(
					'title' => $local_app['display_name'] ? $local_app['display_name'] : $local_app['name'],
					'description' => $local_app['description']);
				$new_application = true;
			}
			else {
				$new_application = false;
			}

			$out .= '
			<form method="post" action="" enctype="multipart/form-data">
				<input type="hidden" id="app_action" name="app_action" value="">
				<input type="hidden" name="remote_app_id" value="'.$app_id.'">
				<input type="hidden" name="schema_id" value="'.$schema_id.'">';


			$out .= '<table class="profileTable">';

			if (!$new_application)
			{
				if ($update_required) {
					$out .= '
					<tr style="font-size:14px">
						<th></th>
						<td><b><font color="#FF0000">'._('The local applications has been changed').'. '._('Update remote application.').'</font></b></td>
					</tr>';				
				}

				$out .= '
				<tr style="font-size:14px">
					<th>'._('Flexilogin Application ID').':</th>
					<td><b>'.$app_object['app_id'].'</b></td>
				</tr>
				<tr>
					<th>'._('Created').':</th>
					<td>'.date('F j, Y \a\t g:i a', $app_object['created']).'</td>
				</tr>
				<tr>
					<th>'._('Modified').':</th>
					<td>'.date('F j, Y \a\t g:i a', $app_object['modified']).'</td>
				</tr>
				<tr>
					<td colspan="2"><hr></td>
				</tr>';
			}
			else {
				$out .= '
				<tr style="font-size:14px">
					<th></th>
					<td><b><font color="#FF0000">'._('The application has not released yet').'</font></b></td>
				</tr>';
			}

			$hide_fields = array('meta', 'code', 'style', 'live_version');

			foreach ($app_fields as $field) {
				if (!in_array($field['name'], $hide_fields)) {
					$field['value'] = isset($_POST[$field['name']]) ? $_POST[$field['name']] : $app_object[$field['name']];				
					$ctrl = get_field_control($field);
					$out .= '
					<tr>
						<th>'.$ctrl['label'].'</th>
						<td>'.$ctrl['control'].'</td>
					</tr>';
				}
			}

			$out .= '
			<tr><hr><tr>
			</tr>
				<th>'._('Live version').':</th>
				<td>
					<select name="live_version">
						<option value="">'._('no live version').'</option>';

			foreach ((array)$app_object['versions'] as $version_id => $data) {
				$s = $version_id == $app_object['live_version'] ? ' selected="selected"' : '';
				$out .= '<option value="'.$version_id.'"'.$s.'>'.$data['version'].'</option>';
			}

			$out .= '
					</select>
					<p>'._('You can select live version from one of the released versions').'</p>
				</td>
			</tr>';
			
			$meta = isset($_POST['meta']) ? $_POST['meta'] : $app_object['meta'];
			$out .= '
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			<tr>
				<th>'._('META Current Version').'</th>
				<td><input type="text" name="meta[version]" value="'.$meta['version'].'"></td>
			</tr>
			<tr>
				<th>'._('META System Requirements').'</th>
				<td><input type="text" name="meta[requirements]" value="'.$meta['requirements'].'"></td>
			</tr>
			<tr>
				<th>'._('META Content Rating').'</th>
				<td><input type="text" name="meta[content_rating]" value="'.$meta['content_rating'].'"></td>
			</tr>
			<tr>
				<th>'._('META Developer').'</th>
				<td><input type="text" name="meta[developer]" value="'.$meta['developer'].'"></td>
			</tr>
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			';

			if ($new_application) {
				$out .= '
				<tr>
					<th></th>
					<td>
						<input class="button green" type="button" value="'._('Add New Application group to the Flexilogin server').'" onclick="$(\'#app_action\').attr(\'value\',\'create\'); submit();"/>
					</td>
				</tr>';
			}
			else {
				$out .= '
				<tr>
					<th></th>
					<td>
						<input class="button blue" type="button" value="'._('Update Application Group').'" onclick="$(\'#app_action\').attr(\'value\',\'update\'); submit();"/>
						<input class="button red" type="button" value="'._('Delete Application Group').'" onclick="$(\'#app_action\').attr(\'value\',\'delete\'); submit();"/>
						<input type="button" class="button green toggle_advanced" data-toggle="app_channels" value="'._('Show / Hide versions').'">
					</td>
				</tr>';
			}

			$out .= '
			</table>
			</form>';

			if (!$new_application)
			{
				$out .= '
				<div id="app_channels" class="advanced_options">
				<h1>&nbsp;'._('Versions').'</h1>';

				//-----------------------------------------------------------------------------------------------------
				if (!$versions) {
					$out .= '
					<hr>
					<div class="info">'._('No versions for this application').'</div>';
				}
				else {
					foreach ($versions as $version_id => $version) {
						$local_app_data = $version;
						$remote_data_id = (int)$local_app_data['remote_data_id'];
						$new_version = $remote_data_id ? false : true;
						$remote_data = $app_object['versions'][$remote_data_id];

						$out .= '
						<hr>
						<form method="post" action="">
							<input type="hidden" id="version_action_'.$version_id.'" name="version_action" value="">
							<input type="hidden" name="version_id" value="'.$version_id.'">
							<input type="hidden" name="dfx_app_id" value="'.$app_id.'">
							<input type="hidden" name="fx_app_data_id" value="'.$remote_data_id.'">
							<input type="hidden" name="schema_id" value="'.$schema_id.'">
							<table class="profileTable">
							<tr style="font-size:14px">
								<th></th>
								<td><b>';

						if (!$new_version)
						{
							$local_code = (array)json_decode($local_app_data['code'], true);
							$local_style = (array)json_decode($local_app_data['style'], true);

							$remote_code = is_array($remote_data['code']) ? $remote_data['code'] : json_decode($remote_data['code'], true);
							$remote_style = is_array($remote_data['style']) ? $remote_data['style'] : json_decode($remote_data['style'], true);

							if ($local_code != $remote_code || $local_style != $remote_style) {
								$out .= '<font color="#f7ae00">'._('The local version has been changed').'. '._('Update remote version').'</font>';
							}
							else {
								$out .= '<font color="#1c962a">'._('Released version').'</font>';
							}
						}
						else {
							$out .= '<font color="#FF0000">'._('This version has not released yet').'</font>';
						}
						
						$out .= '
								</b>
								</td>
							</tr>
							<tr>
								<th>'._('Remote Version ID').':</th>
								<td>'.($remote_data_id ? $remote_data_id : 'n/a').'</td>
							</tr>
							<tr>
								<th>'._('Version name').':</th>
								<td>'.$local_app_data['display_name'].'</td>
							</tr>
							<tr>
								<th>'._('Created').':</th>
								<td>'.date('F j, Y \a\t g:i a', $local_app_data['created']).'</td>
							</tr>
							<tr>
								<th>'._('Modified').':</th>
								<td>'.date('F j, Y \a\t g:i a', $local_app_data['modified']).'</td>
							</tr>
							<tr>
								<th>'._('Trusted users').':</th>
								<td>
									<textarea name="dev_keys" cols="50" rows="3">'.$remote_data['dev_keys'].'</textarea>
									<p class="prompt">'._('A list of subscription API Keys through the gaps, who can use the app').'</p>
									<p class="prompt">'._('You can find keys here').': <a href="'.URL.'network_admin/network_subscriptions">FlexiDB subscriptions</a></p>
								</td>
							</tr>
							<tr style="font-size:14px">
								<th>Local FlexiDB:</th>
								<td>
									<a class="button blue small" href="'.URL.'app_editor/app_pages?object_id='.$app_id.'.'.$version_id.'"/>'._('Edit').'</a>
									<input class="button green small" type="button" value="'._('Replicate').'" onclick="$(\'#version_action_'.$version_id.'\').attr(\'value\',\'replicate\'); submit();"/>
									<input class="button red small" type="button" value="'._('Delete').'" onclick="$(\'#version_action_'.$version_id.'\').attr(\'value\',\'delete_both\'); submit();"/>
								</td>
							</tr>';
	
						if ($new_version) {				
							$out .= '						
							<tr style="font-size:14px">
								<th>Remote FlexiLogin:</th>
								<td>
									<input class="button green small" type="button" value="'._('Release').'" onclick="$(\'#version_action_'.$version_id.'\').attr(\'value\',\'create\'); submit();"/>
									<input class="button red small" type="button" value="'._('Delete').'" onclick="$(\'#version_action_'.$version_id.'\').attr(\'value\',\'delete_remote\'); submit();"/>
								</td>
							</tr>';
						}
						else {
							$out .= '						
							<tr style="font-size:14px">
								<th>Remote FlexiLogin:</th>
								<td>
									<input class="button blue small" type="button" value="'._('Update').'" onclick="$(\'#version_action_'.$version_id.'\').attr(\'value\',\'update\'); submit();"/>
									<input class="button red small" type="button" value="'._('Delete').'" onclick="$(\'#version_action_'.$version_id.'\').attr(\'value\',\'delete_remote\'); submit();"/>
								</td>
							</tr>';
						}
	
						$out .= '
						</table>
						</form>';
					}
				}
				$out .= '</div>';
			}
		}

		return $out;
	}

	//*****************************************************************************************************************************		
		
	if (isset($_POST['app_action']))
	{
		global $fx_error;

		$fx_api = new FX_API_Client();
		$app_id = 0;
		
		switch ($_POST['app_action'])
		{
			case 'create':
				$result = $fx_api -> execRequest('app/object', 'POST', $_POST);
				if (!is_fx_error($result)) {
					$app_id = $result;
				}
			break;
			case 'update':
				$result = $fx_api -> execRequest('app', 'PUT', $_POST);
				$app_id = $_POST['remote_app_id'];
			break;
			case 'delete':
				$result = $fx_api -> execRequest('app', 'DELETE', 'app_id='.$_POST['app_id']);
				if (!is_fx_error($result)) {
					$app_id = '';
				}
			break;
			default:
				$result = new FX_Error('app_action', _('Unknown App action'));
		}
		
		if (is_fx_error($result)) {
			$fx_error = $result;
		}
		else {
			fx_redirect(replace_url_param('edit_channel', $channel_id));
		}
	}

	if (isset($_POST['version_action']))
	{
		global $fx_error;

		$fx_api = new FX_API_Client();
		$app_id = 0;

		switch ($_POST['version_action'])
		{
			case 'create':
				$app_data = get_object(TYPE_APP_DATA, $_POST['version_id']);			
				
				$app_data['dfx_app_id'] = $_POST['dfx_app_id'];
				$app_data['fx_app_data_id'] = $_POST['fx_app_data_id'];
				$app_data['schema_id'] = $_POST['schema_id'];
				$app_data['code'] = escapeJsonString($app_data['code']);
				$app_data['version_name'] = $app_data['display_name'];
				
				$result = $fx_api -> execRequest('app/version', 'POST', $app_data);

				if (!is_fx_error($result)) {
					$remote_data_id = $result;
					$result = update_object_field(TYPE_APP_DATA, $_POST['version_id'], 'remote_data_id', $remote_data_id);
				}
			break;
			case 'update':
				
				$app_data = get_object(TYPE_APP_DATA, $_POST['version_id']);			
				
				$app_data['dfx_app_id'] = $_POST['dfx_app_id'];
				$app_data['fx_app_data_id'] = $_POST['fx_app_data_id'];
				$app_data['dev_keys'] = $_POST['dev_keys'];
				$app_data['code'] = escapeJsonString($app_data['code']);
				$app_data['version_name'] = $app_data['display_name'];
				
				$result = $fx_api -> execRequest('app/version', 'PUT', $app_data);
			break;
			case 'replicate':
			
				$result = replicate_object(TYPE_APP_DATA, $_POST['version_id']);
				
				if (!is_fx_error($result)) {
					
					$app_data = get_object(TYPE_APP_DATA, $_POST['version_id']);	

					update_object_field(TYPE_APP_DATA, $_POST['version_id'], 'remote_data_id', 0);
					update_object_field(TYPE_APP_DATA, $_POST['version_id'], 'version', 'version '.time());

					$link_result = add_link(TYPE_APPLICATION, $_POST['dfx_app_id'], TYPE_APP_DATA, $result);

					if (is_fx_error($link_result)) {
						$result = $link_result;
					}
				}
			break;
			case 'delete_remote':
			
				$args['app_id'] = $_POST['dfx_app_id'];
				$args['app_data_id'] = $_POST['fx_app_data_id'];
				$args['schema_id'] = $_POST['schema_id'];
				
				$result = $fx_api -> execRequest('app/version', 'DELETE', $args);
				
				if (!is_fx_error($result)) {
					update_object_field(TYPE_APP_DATA, $_POST['version_id'], 'remote_data_id', 0);
				}
			break;
			case 'delete_both':
			
				$args['app_id'] = $_POST['dfx_app_id'];
				$args['app_data_id'] = $_POST['fx_app_data_id'];
				$args['schema_id'] = $_POST['schema_id'];			
			
				$result = $fx_api -> execRequest('app/version', 'DELETE', $args);
				if (!is_fx_error($result)) {
					$result = delete_object(TYPE_APP_DATA, $_POST['version_id']);
				}
			break;
			default:
				$result = new FX_Error('app_action', _('Unknown App action'));
		}

		if (is_fx_error($result)) {
			$fx_error = $result;
		}
		else {
			fx_redirect(replace_url_param('app_id', $_POST['dfx_app_id']));
		}
	}
	
	//*****************************************************************************************************************************	

	$current_schema = get_object(TYPE_DATA_SCHEMA, $_SESSION['current_schema']);
	
	if (!$app_group = get_schema_app_group($_SESSION['current_schema'])) {
		fx_redirect(URL.'app_editor/app_group');
	}
	
	$app_id = $app_group['object_id'];
	$app_object = get_object(TYPE_APPLICATION, $app_id);

	if (is_fx_error($app_object))
	{
		fx_show_metabox(array('body' => array('content' => new FX_Error('app_release_manager', _('Please select Application'))), 'footer' => array('hidden' => true)));
		
			$options = array('set_id' => 0,
							 'schema_id' => $current_schema['object_id'],
							 'object_type_id' => TYPE_APPLICATION,
							 'fields' => array('object_id','created','modified','description', 'display_name'),
							 'actions' => array('edit'));
		
			$mb_data = array('header' => array('hidden' => true),
							 'body' => array('content' => object_explorer($options)),
							 'footer' => array('hidden' => true));

			fx_show_metabox($mb_data);					
	}	
	else
	{
		$server_settings = get_fx_option('server_settings');
		
		if ($server_settings['api_validated'] === 1 && $server_settings['dfx_key']) {
			$mb_data = array('header' => array('suffix' => !is_fx_error($current_schema) ? ' - '.$current_schema['display_name'] : ''),
							 'body' => array('content' => _app_metabox($current_schema['object_id'], $current_schema['channel'])),
							 'footer' => array('hidden' => true));

			fx_show_metabox($mb_data);
		}
		else {
			fx_show_metabox(array('body' => array('content' => new FX_Error('app_release_manager', _('Please validate your DFX Server'))), 'footer' => array('hidden' => true)));
		}
	}