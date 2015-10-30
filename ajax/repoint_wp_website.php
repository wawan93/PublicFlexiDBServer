<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	require_once CONF_EXT_DIR . '/phpseclib/Net/SSH2.php';
	require_once CONF_EXT_DIR . '/phpseclib/Net/SFTP.php';

	$object_type_id = get_type_id_by_name(0, 'dfx_wp_site');
	$object_id = (int)$_GET['object'];
	$site_object = get_object($object_type_id, $object_id);
	$referer = isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER'];
	$IOResult = false;

	$sh_dir = dirname(dirname(__FILE__))."/wp_setup/";

	if(is_fx_error($site_object)) 
	{
		$IOResult = new FX_Error('copy_wp_website','Invalid object identifier or object has wrong type.');
	}
	else
	{
		$errors = new FX_Error();

		if(!$site_object['installed']) $errors -> add('copy_wp_website', 'Website is not installed yet. There is nothing to move.');
		if(!$site_object['linux_username']) $errors -> add('copy_wp_website', 'Unknown Linux username of source website!');
		if(!$site_object['linux_password']) $errors -> add('copy_wp_website', 'Unknown Linux password of source website!');

		if(isset($_POST['repoint_site']) && $errors -> is_empty())
		{
			$new_domain = $_POST['domain_name'];
			$root_login = $_POST['root_username'];
			$root_password = $_POST['root_password'];
	
			if(!$new_domain) $errors -> add('copy_wp_website', 'Please enter new domain name!');
			elseif(!is_url($new_domain)) $errors -> add('copy_wp_website', 'Invalid new domain name!');		
			
			if (!$ssh = new Net_SSH2($site_object['domain_name']))
			{
				$errors -> add('copy_wp_website', 'Invalid host.');
			}
			else
			{
				if (!$ssh->login($root_login, $root_password))
				{
					$errors -> add('copy_wp_website', 'Login failed.');
				}
				else
				{
					$dir_exists = (bool)(int)$ssh -> exec('[ -d /home/'.$site_object['linux_username'].'/ ] && echo "1" || echo "0"');
					$conf_exists = (bool)(int)$ssh -> exec('[ -f /etc/httpd/conf/vhosts/'.$new_domain.'.conf ] && echo "1" || echo "0"');
	
					if(!$dir_exists) $errors -> add('copy_wp_website','Directory not found.');
					if($conf_exists) $errors -> add('copy_wp_website','The domain name you entered is already in use on specified host.');
	
					if($errors -> is_empty())
					{
						$commands = array(
							$sh_dir.'fx-create-backup.sh "'.$site_object['linux_username'].'" "'.$sh_dir.'dp_backups/" "'.$sh_dir.'wp_backups/"',
							$sh_dir.'fx-apache-install.sh "'.$site_object['linux_username'].'" "'.$new_domain.'"'
						);
						
						if($site_object['multisite'])
						{
							$commands[] = $sh_dir.'fx-change-wp-ms-domain.sh "wp_'.$site_object['linux_username'].'" "'.$site_object['domain_name'].'" "'.$new_domain.'" "'.$site_object['linux_username'].'" "'.$sh_dir.'"';
						}
						else
						{
							$commands[] = $sh_dir.'fx-change-wp-domain.sh "wp_'.$site_object['linux_username'].'" "'.$site_object['domain_name'].'" "'.$new_domain.'"';					
						}
						
						$commands[] = $sh_dir.'fx-change-wp-conf.sh "'.$site_object['linux_username'].'" "'.$site_object['domain_name'].'" "'.$new_domain.'"';	

						for ($i=0; $i<count($commands); $i++)
						{
							$res = $ssh->exec($commands[$i]);
							//echo '<p>==================================================</p>';
							//echo '<p>'.$commands[$i].'</p>';
							//echo '<p>--------------------------------------------------</p>';
							//echo '<p>'.$res.'</p>';
						}						
						
						$conf_exists = (bool)(int)$ssh -> exec('[ -f /etc/httpd/conf/vhosts/'.$new_domain.'.conf ] && echo "1" || echo "0"');	
						
						if($conf_exists)
						{
							$ssh -> exec('rm -f /etc/httpd/conf/vhosts/'.$site_object['domain_name'].'.conf');

							update_object_field($object_type_id, $object_id, 'domain_name', $new_domain, true);

							$crontab = new FX_Cron($new_domain, $root_login, $root_password);
							$crontab -> remove_cronjob('/etc/rc.d/init.d/httpd restart');
							$crontab -> append_cronjob(date("i H j n *",time()+60).' /etc/rc.d/init.d/httpd restart >/dev/null 2>&1');
							
							$IOResult = 'Website successfully repointed. Changes will take effect within a minute.';
						}
						else
						{
							$errors -> add('copy_wp_website','Unable to repoint website.');
						}
					}
				}
			}
			
			if(!$errors -> is_empty()) $IOResult = $errors;
		}		
	}

?>

<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">

	<script language="javascript">
        <?php if($redirect_to) echo 'window.parent.location = "'.$redirect_to.'";'; ?>
	</script>
    <style type="text/css">

	</style>
</head>
<body class="popup">

	<?php
        if($IOResult !== false)
        {
            if (is_fx_error($IOResult))
            {
                $errors = $IOResult->get_error_messages();
                for ($i=0; $i<count($errors); $i++) echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
            }
            else
            {
                echo '<div class="msg-info">'.$IOResult.'</div>';
            }
        }
    ?>
    <form method="post" action="" name="objectsForm">
        <input type="hidden" name="repoint_site">
        <input type="hidden" name="referer" value="<?php echo $referer ?>">
        <input type="hidden" name="object_id" value="<?php echo $object_id ?>"/>
        <table class="profileTable">
        <tr>
        	<td colspan="2" align="center">
            	<i>Please set new domain name and root user credentials to repoint WP website.</i>
            </td>
        </tr>
        <tr>
            <th><div class="star"></div><label for="domain_name">New Domain Name:</label></th>
            <td><input type="text" name="domain_name" id="domain_name" value="<?php echo $_POST['domain_name']?>" autocomplete="off"/></td>
        </tr>
        <tr>
            <th><div class="star"></div><label for="root_username">Root Username:</label></th>
            <td>
            	<input type="text" name="root_username" id="root_username" value="<?php echo $_POST['root_username']?>" autocomplete="off"/>
            </td>
        </tr>
        <tr>
            <th><div class="star"></div><label for="root_password">Root Password:</label></th>
            <td>
            	<input type="password" name="root_password" id="root_password" value="<?php echo $_POST['root_password']?>" autocomplete="off"/>
            </td>
        </tr>
        <tr>
            <th></th>
            <td><div class="star"></div> - mandatory field</td>
        </tr>
        </table>
        <hr>
        <input class="button green" type="submit" value="Repoint"/>
        <input class="button red" type="button" id="close-dialog-window" value="Close"/>
    </form>
</body>
</html>