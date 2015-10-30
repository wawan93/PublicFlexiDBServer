<?php
	
	$IOResult = false;
	
	$server_settings = get_fx_option('server_settings', array());

	if(isset($_POST['update_api_settings']))
	{
		switch ($_POST['update_api_settings'])
		{
			case 'update':
				$base_api_url = str_replace('\\', '/', $_POST['fx_api_base_url']);
				
				if ($base_api_url[strlen($base_api_url)-1] != '/') $base_api_url .= '/';
				
				if ($base_api_url == $server_settings['fx_api_base_url']) break;
				
				if (!is_url($base_api_url)) {
					$IOResult = new FX_Error('update_base_api_url', _('Invalid url format'));
					break;
				}

				if (!is_fx_error($IOResult)) {
					$server_settings['fx_api_base_url'] = $base_api_url;
					$server_settings['api_validated'] = -1;
					update_fx_option('server_settings', $server_settings);
					fx_redirect();
				}
				
			break;
			case 'validate_api':

				$fx_api = new FX_API_Client();

				if ($fx_api -> validate() === true) {
					$server_settings['api_validated'] = 1;
				}
				else {
					$server_settings['api_validated'] = 0;
					$IOResult = new FX_Error('validate_base_api_url', _('Unable to validate base API url'));
				}
				
				$server_settings['api_last_check'] = time();
				update_fx_option('server_settings', $server_settings);
				
				if (!is_fx_error($IOResult)) {
					fx_redirect();
				}

			break;			
		}
	}
	
	if (isset($_POST['set_server_settings']))
	{
		$server_settings['dfx_key'] = $_POST['dfx_key'];
		update_fx_option('server_settings', $server_settings);
		
		$fx_api = new FX_API_Client();
		$server_id = $fx_api -> execRequest('server/validate', 'GET', 'dfx_key='.$server_settings['dfx_key']);

		$server_settings['dfx_key_is_valid'] = is_numeric($server_id) ? 1 : 0;
		update_fx_option('server_settings', $server_settings);

		fx_redirect();
	}

	$api_last_checked = $server_settings['api_last_check'];
	$api_last_checked = $api_last_checked ? date('F j, Y \a\t g:i a', $api_last_checked) : _('never');
	
	switch ($server_settings['api_validated']) {
		case 1:	$api_validated = '<font color="#009933">'._('API url is valid').'</font>'; break;
		case 0:	$api_validated = '<font color="red">'._('API url is not valid').'</font>'; break;
		case -1: $api_validated = '<font color="red">'._('Validate new API url').'</font>'; break;
	}

?>

<div class="rightcolumn">
    <div class="metabox">                
        <div class="header">
            <div class="icons settings"></div>
            <h1>DFX Server Settings</h1>
        </div>
        <div class="content">
        	<?php print_fx_errors($IOResult); ?>
            <h2>FlexiLogin API</h2>
            <form action="" method="post">
                <input type="hidden" name="update_api_settings" id="update_api_settings" value="update"/>
                <table class="profileTable">
                <tr>
                    <th>API Base Url:</th>
                    <td><input type="text" name="fx_api_base_url" id="fx_api_base_url" value="<?php echo $server_settings['fx_api_base_url']; ?>" size="50"/></td>
                </tr>
                <tr>
                    <th>Last Checked:</th>
                    <td><?php echo $api_last_checked ?></td>
                </tr>
                <tr>
                    <th></th>
                    <td><font size="+1"><strong><?php echo $api_validated; ?></strong></font></td>
                </tr>
                <tr>
                    <th></th>
                    <td>
                    	<input type="submit" class="button green" value="Save"/>
                        <input type="button" class="button blue" value="Validate" onclick="$('#update_api_settings').attr('value','validate_api');submit();"/>
                    </td>
                </tr>
                </table>
            </form>
            <hr/>            
            <h2>DFX Key</h2>
            <form action="" method="post">
                <input type="hidden" name="set_server_settings"/>
                <table class="profileTable">
                <tr>
                    <th></th>
                    <td class="prompt">
                    	<p><?php _('Enter your DFX Key in the field bellow if you already have it, to gain access to all Flexiweb functionality.') ?></p>
                        <p><?php _('If you have no DFX key you can <b><a target="_blank" style="color:#0066CC" href="https://flexilogin.com/personal/server">get it right now</a></b>.') ?></p>
                    </td>
                </tr>
                <tr>
                    <th>DFX Key:</th>
                    <td><input type="text" name="dfx_key" id="dfx_key" value="<?php echo $server_settings['dfx_key']; ?>" size="50"/></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" class="button green" value="Save"/></td>
                </tr>
                </table>   
            </form>
		</div>
	</div>
</div>
