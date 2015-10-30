<?php
	
if (!CONF_ENABLE_UPDATES || !defined('CONF_ENABLE_UPDATES')) :

	fx_show_metabox(array('body' => array('content' => '<div class="info">'._('Updates disabled').'</div>'), 
						  'footer' => array('hidden' => true)));

else:

	require_once CONF_EXT_DIR . '/phpseclib/Net/SSH2.php';
	require_once CONF_EXT_DIR . '/phpseclib/Net/SFTP.php';

	global $exclude_path, $fx_error;

	$exclude_path = array(CONF_FX_DIR.'/fx_config.php',
						  CONF_FX_DIR.'/.htaccess',
						  CONF_FX_DIR.'/install.php',
						  CONF_FX_DIR.'/uploads',
						  CONF_FX_DIR.'/plugins',
						  CONF_FX_DIR.'/version_hash.php');

	//*********************************************************************************	
		
	function flexidb_scan_dir($dir, $exclude_path, &$directories, &$files, $dir_filter = '')
	{
		if (is_dir($dir)) {
			if ($cur_dir = @opendir($dir)) {
				while (($item = readdir($cur_dir)) != false) {
					$path = $dir.'/'.$item;
					if (!in_array($path, $exclude_path)) {
						if (is_file($path)) {
							$files[substr($path,strlen($dir_filter))] = hash_file('md5', $path);
						}
						elseif (is_dir($path) && $item != '.' && $item != '..') {
							$directories[] = substr($path,strlen($dir_filter));
							flexidb_scan_dir($path, $exclude_path, $directories, $files, $dir_filter);
						}
					}
				}
			}
		}				
	}
	
	//*********************************************************************************	

	function flexidb_check_hash()
	{
		$update_options = get_fx_option('update_options', array());
		
		if (!$update_options) {
			return new FX_Error(__FUNCTION__, _('Check last version'));
		}
		
		$update_address = $update_options['ftp_address'];
		$update_dir = $update_options['ftp_dir'];
		$update_username = $update_options['ftp_username'] ? $update_options['ftp_username'] : 'anonymous';
		$update_password = $update_options['ftp_password'];

		// Connect to remote (update) server (FTP)
		if (!$remote_ftp = new Net_SFTP($update_address, 22)) {
			return new FX_Error(__FUNCTION__, _('Unable connect to').' '.$update_address);
		}

		if (!$remote_ftp->login($update_username, $update_password)) {
			return new FX_Error(__FUNCTION__, _('Login failed'));
		}

		$hash_raw = $remote_ftp->exec("php $update_dir/version_hash.php");
		$update_hash = unserialize($hash_raw);

		if (!is_array($update_hash)) {
			return new FX_Error(__FUNCTION__, _('Unable to get update hash').'. '.$hash_raw);
		}

		global $exclude_path;

		$local_dirs = $local_files = array();

		$result = array('missing' => array(), 'corrupted' => array(), 'unknown' => array());

		flexidb_scan_dir(CONF_FX_DIR, $exclude_path, $local_dirs, $local_files, CONF_FX_DIR);

		foreach ($local_files as $path => $hash) {
			if (!array_key_exists($path, $update_hash['files']))  {
				if (is_file(CONF_FX_DIR.$path)) {
					$result['unknown'][] = CONF_FX_DIR.$path;
				}
			}
		}

		foreach ($update_hash['files'] as $path => $hash) {
			if (in_array(CONF_FX_DIR.$path, $exclude_path)) {
				continue;
			}
			if (!array_key_exists($path, $local_files)) {
				$result['missing'][] = CONF_FX_DIR.$path;
			}
			elseif ($hash != $local_files[$path]) {
				$result['corrupted'][] = CONF_FX_DIR.$path;
			}
		}

		return $result;
	}

	function flexidb_update($ftp_login, $ftp_password, $connection_type)
	{
		$update_options = get_fx_option('update_options', array());
		$local_ftp_options = get_fx_option('local_ftp_options', array('ftp_username'=>'', 'ftp_password'=>''));
		
		if (!$update_options) {
			return new FX_Error(__FUNCTION__, _('Check last version'));
		}
		
		$update_address = $update_options['ftp_address'];
		$update_dir = $update_options['ftp_dir'];
		$update_username = $update_options['ftp_username'] ? $update_options['ftp_username'] : 'anonymous';
		$update_password = $update_options['ftp_password'];
		
		$local_ftp_login = $ftp_login; //$local_ftp_options['ftp_username'];
		$local_ftp_password = $ftp_password; //$local_ftp_options['ftp_password'];

		// Check local ftp credentials
		if (!$local_ftp_login) {
			return new FX_Error(__FUNCTION__, _('Enter FTP Username'));
		}
		if (!$local_ftp_password) {
			return new FX_Error(__FUNCTION__, _('Enter FTP Password'));
		}
		if (!in_array($connection_type,array('ftp','sftp'))) {
			return new FX_Error(__FUNCTION__, _('Unknown connection type'));
		}
		
		// Connect to local (S)FTP
		if (!$local_ftp = new Net_SFTP('localhost', 22)) {
			return FX_Error(__FUNCTION__, _('Cannot connect to local FTP'));
		}
		if (!$local_ftp->login($local_ftp_login, $local_ftp_password)) {
			return new FX_Error(__FUNCTION__, _('Local FTP: Login failed'));
		}
		
		// Connect to remote (update) server (S)FTP
		if (!$remote_ftp = new Net_SFTP($update_address, 22)) {
			return new FX_Error(__FUNCTION__, _('Cannot connect to').' '.$update_address);
		}
		if (!$remote_ftp->login($update_username, $update_password)) {
			return new FX_Error(__FUNCTION__, _('Remote FTP: Login failed'));
		}

		$errors = new FX_Error();

		//=========================================================================================
		$local_dirs = $local_files = array();

		global $exclude_path;

		flexidb_scan_dir(CONF_FX_DIR, $exclude_path, $local_dirs, $local_files, CONF_FX_DIR);

		$hash_raw = $remote_ftp->exec("php $update_dir/version_hash.php");
		$update_hash = unserialize($hash_raw);

		if (!is_array($update_hash)) {
			return new FX_Error(__FUNCTION__, _('Unable to get update hash').'. '.$hash_raw);
		}
		
		$commands = array();
		
		//DELETE UNNECESSARY LOCAL DIRECTORIES
		foreach ($local_dirs as $dir) {
			if (!in_array($dir, $update_hash['directories'])) {
				$commands[] = 'rm -rf "'.CONF_FX_DIR.$dir.'"';
			}
		}
		
		//CREATE MISSING LOCAL DIRECTORIES
		foreach ($update_hash['directories'] as $dir) {
			if (!in_array(CONF_FX_DIR.$dir, $exclude_path)) {
				if (!in_array($dir, $local_dirs)) {
					$commands[] = 'mkdir "'.CONF_FX_DIR.$dir.'"';
				}
			}
		}

		//DELETE UNNECESSARY LOCAL FILES
		foreach ($local_files as $path => $hash) {
			if (!array_key_exists($path, $update_hash['files']))  {
				if (is_file(CONF_FX_DIR.$path)) {
					$commands[] = 'rm -f "'.CONF_FX_DIR.$path.'"';
				}
			}
		}

		// EXECUTE SSH COMMANDS
		foreach ($commands as $cmd) {
			$local_ftp->exec($cmd);
		}
		
		// COPY MISSED FILES FROM UPDATE SERVER
		foreach ($update_hash['files'] as $path => $hash) {
			if (!in_array(CONF_FX_DIR.$path, $exclude_path)) {
				if (!array_key_exists($path, $local_files) || $hash != $local_files[$path]) {
					$current_file = $remote_ftp->get($update_hash['base_dir'].$path);
					if ($current_file === false) {
						$errors->add(__FUNCTION__, _('Unable to read remote file').' '.$update_hash['base_dir'].$path);
					}
					else {
						if (!$local_ftp->put(CONF_FX_DIR.$path, $current_file)) {
							$errors->add(__FUNCTION__, _('Unable to put local file').' '.CONF_FX_DIR.$path);
						}
					}
				}
			}
		}

		//=========================================================================================		
		
		if ($errors->get_error_messages()) {
			return $errors;
		}
		else {
			//FINAL HASH CHECK
			$local_dirs = $local_files = $missed_files = array();

			flexidb_scan_dir(CONF_FX_DIR, $exclude_path, $local_dirs, $local_files, CONF_FX_DIR);

			foreach ($update_hash['files'] as $path => $hash) {
				if (!in_array(CONF_FX_DIR.$path, $exclude_path)) {
					if (!array_key_exists($path, $local_files) || $hash != $local_files[$path]) {
						$missed_files[] = CONF_FX_DIR.$path;
					}
				}
			}

			if ($missed_files) {
				add_log_message('flexidb_update_failed', print_r($missed_files, true));
				return new FX_Error(__FUNCTION__, _('Not all files have been updated. Check Message Log for details'));
			}
			else {
				update_fx_option('flexidb_version', $update_options['new_flexidb_version']);
				
				if (array_key_exists('/db_patch.php', $update_hash['files']) && file_exists(CONF_FX_DIR.'/db_patch.php')) {
					return 'db_patch';
				}
				
				return true;
			}
		}
	}

	//*********************************************************************************
	
	if (isset($_POST['run_update']) && SECOND_PARAM == 'dfx_update')
	{
		$update_result = flexidb_update($_POST['ftp_username'], $_POST['ftp_password'], $_POST['connection_type']);
		
		if (is_fx_error($update_result)) {
			$fx_error->add('run_update', $update_result->get_error_message());
		}
		else {
			if ($update_result == 'db_patch') {
				fx_redirect(URL.'db_patch.php?redirect');
			}
			else {
				fx_redirect(current_page_url());
			}
		}
	}

	//*********************************************************************************

	if (isset($_POST['update_check']))
	{
		if (is_fx_error(_update_version_info())) {
			$fx_error->add('update_check', _('Unable to check DFX Server version'));
		}
	}

	//*********************************************************************************

	$update_options = get_fx_option('update_options', array());
	$local_ftp_options = get_fx_option('local_ftp_options', array('ftp_username'=>'', 'ftp_password'=>''));

	if (!$update_options) {
		$fx_error->add('empty_update_options', _('Update options are empty. Check Updates to get updates server details'));
	}

	$last_checked = $update_options['last_checked'];
	$last_checked = $last_checked ? date(FX_DATE_FORMAT.' '.FX_TIME_FORMAT, $last_checked) : 'never';

	$current_version = get_fx_option('flexidb_version', 0);
	$new_version = $update_options['new_flexidb_version'];

	$ftp_username = $_POST['ftp_username'] ? $_POST['ftp_username'] : $local_ftp_options['ftp_username'];
	$ftp_password = $_POST['ftp_password'] ? $_POST['ftp_password'] : $local_ftp_options['ftp_password'];

?>

<div class="rightcolumn">
    <div class="metabox">                
        <div class="header">
            <div class="icons settings"></div>
            <h1><?php echo _('DFX Server Settings') ?></h1>
        </div>
        <div class="content">
		
		<?php print_fx_errors($fx_error) ?>
        
		<?php if (SECOND_PARAM == 'dfx_update'): ?>

		<div class="update-msg"><?php echo _('Connection Information') ?></div>
        <p>&nbsp;</p>
		<p><?php echo _('DFX Server needs to access your web server. Please enter your FTP credentials to proceed. If you do not remember your credentials, you should contact your web host.') ?></p>
		<p>&nbsp;</p>
        
        <form method="post" action="">
            <input type="hidden" name="run_update"/>
    
            <table class="profileTable">
            <tr>
                <th><label for="ftp_username"><?php echo _('FTP Username') ?></label></th>
                <td><input type="text" id="ftp_username" name="ftp_username" value="<?php echo $ftp_username; ?>"/></td>
            </tr>
            <tr>
                <th><label for="ftp_password"><?php echo _('FTP Password') ?></label></th>
                <td><input type="password" id="ftp_password" name="ftp_password" value="<?php echo $ftp_password; ?>"/></td>
            </tr>
            <tr>
                <th><label><?php echo _('Connection Type') ?></label></th>
                <td>
                    <label for="ftp">
                        <input type="radio" name="connection_type" id="ftp" checked="checked" value="ftp" <?php echo $_POST['connection_type'] == 'ftp' ? ' checked="checked"' : ''; ?> disabled="disabled"/>&nbsp;FTP
                    </label>
                    <label for="sftp">
                        <input type="radio" name="connection_type" id="sftp" value="sftp" <?php echo $_POST['connection_type'] == 'sftp' ? ' checked="checked"' : ''; ?> checked="checked"/>&nbsp;SFTP
                    </label>
                <td>
            </tr>
            <tr>
            	<td colspan="2"><hr></td>
            </tr>
            <tr>
                <th></th>
                <td>
                    <input type="submit" class="button green" value="<?php echo _('Proceed') ?>"/>                
                    <a class="button blue" href="<?php echo URL ?>settings/settings_update"><?php echo _('Back') ?></a>
                <td>
            </tr>  
            </table>
        </form>    

    	<?php  elseif (SECOND_PARAM == 'check_hash'): ?>
    
    	<?php
			$hash_check = flexidb_check_hash();
			print_fx_errors($hash_check);
			
			echo '<p>&nbsp;</p>';
			echo '<a class="button blue" href="'.URL.'settings/settings_update">'._('Back').'</a>'; 

			if (!is_fx_error($hash_check))
			{
				if(!$hash_check['missing'] && !$hash_check['corrupted']) {
					echo '<div class="update-msg">'._('All local files match with files of latest FlexiDB Server version').'</div>';
				}
				else {
					echo '<hr/>';
					echo '<div class="update-msg">'._('You have missed or corrupted files on your FlexiDB Server').'</div>';
					echo _('You can update your local files to bring them into conformity with latest DFX Server version').':';
					echo '&nbsp;<a class="button small green" href="'.URL.'settings/settings_update/dfx_update">'._('Update').'</a>';
					
					if ($hash_check['missing']) {
						$cm = count($hash_check['missing']);
						echo '<p>&nbsp;</p><h1>'._('Files to download').' ('.$cm.'):</h1><p>&nbsp;</p>';
						echo '<table>';
						for($i=0; $i<$cm; $i++) {
							echo '
							<tr>
								<td align="right">&nbsp;'.($i+1).':&nbsp;</td>
								<td>&nbsp;'.str_replace(CONF_FX_DIR, '', $hash_check['missing'][$i]).'&nbsp;</td>
							</tr>';
						}
						echo '</table>';
					}
	
					if ($hash_check['corrupted']) {
						$cr = count($hash_check['corrupted']);
						echo '<p>&nbsp;</p><h1>'._('Files to update').' ('.$cr.'):</h1><p>&nbsp;</p>';
						echo '<table>';
						for($i=0; $i<$cr; $i++) {
							echo '
							<tr>
								<td align="right">&nbsp;'.($i+1).':&nbsp;</td>
								<td>&nbsp;'.str_replace(CONF_FX_DIR, '', $hash_check['corrupted'][$i]).'&nbsp;</td>
							</tr>';
						}
						echo '</table>';
					}
	
					if ($hash_check['unknown']) {
						$cu = count($hash_check['unknown']);
						echo '<p>&nbsp;</p><h1>'._('Files to delete').' ('.$cu.'):</h1><p>&nbsp;</p>';
						echo '<table>';
						for($i=0; $i<$cu; $i++) {
							echo '
							<tr>
								<td align="right">&nbsp;'.($i+1).':&nbsp;</td>
								<td>&nbsp;'.str_replace(CONF_FX_DIR, '', $hash_check['unknown'][$i]).'&nbsp;</td>
							</tr>';
						}
						echo '</table>';
					}
					
					echo '<hr>';
					echo '<a class="button blue" href="'.URL.'settings/settings_update">'._('Back').'</a>';
					
				}
			}
		?>
    
    	<?php  else: ?>
    
        <p>&nbsp;</p>
        <form method="post" action="">
            <input type="hidden" name="update_check"/>
            <?php echo _('Last checked on') ?> <strong><?php echo $last_checked; ?></strong>&nbsp;&nbsp;&nbsp;
            <input type="submit" class="button green" value="Check Again" />
        </form>
        <p>&nbsp;</p>
        <hr/>

        <?php if (version_compare($current_version, $new_version, '<')): ?>
        
        <div class="update-msg"><?php echo _('A new version of DFX Server is available') ?>.</div>
        <p>&nbsp;</p>
        <p><?php echo _('You can update to DFX Server').' '.$new_version.' '._('automatically or download the package and install it manually') ?>:</p>
        <p>&nbsp;</p>
        <a class="button green" href="<?php echo URL ?>settings/settings_update/dfx_update"><?php echo _('Update Now') ?></a>
        <a class="button blue" target="_blank" href="https://flexilogin.com/last_version.tar.gz"><?php echo _('Download') ?> <?php echo $new_version ?></a>
        <a class="button blue" href="<?php echo URL ?>settings/settings_update/check_hash"><?php echo _('Check Hash') ?></a>
        <p>&nbsp;</p>
        <p style="color:#E20000"><strong><?php echo _('Important') ?>:</strong> <?php echo _('we are strongly recommend you backup your database and files before updating') ?>.</p>
        <p>&nbsp;</p>

        <?php else: ?>

        <div class="update-msg"><?php echo _('You are using the latest version of DFX Server. You can still refresh your current files if you wish') ?>.</div>
        <p>&nbsp;</p>
        <p><?php echo _('You can check hash to find missing or corrufted files on you local server') ?></p>
        <p>&nbsp;</p>
        <a class="button green" href="<?php echo URL ?>settings/settings_update/dfx_update"><?php echo _('Refresh') ?></a>
        <a class="button blue" href="<?php echo URL ?>settings/settings_update/check_hash"><?php echo _('Check Hash') ?></a>
        
        <?php endif; ?>    	
 
		<?php endif; ?>
		
        </div>
	</div>
</div>

<?php endif; ?>