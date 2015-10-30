<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	require_once CONF_EXT_DIR.'/phpseclib/Net/SSH2.php';
	require_once CONF_EXT_DIR.'/phpseclib/Net/SFTP.php';
		
	switch ($_GET['site_type']) {
		case 'wp':
			$object_type_id = TYPE_DFX_WP_SITE;
			$is_wp_website = true;
			$sh_dir = dirname(dirname(__FILE__))."/wp_setup/";
		break;
		case 'fx':
			$object_type_id = TYPE_DFX_GENERIC_WEBSITE;
			$is_wp_website = false;
			$sh_dir = dirname(dirname(__FILE__))."/generic_website/setup/";
		break;
		default:
			die('Unknown website type (wordpress or generic)');
	}

	$object_id = (int)$_GET['object'];
	$site_object = get_object($object_type_id, $object_id);

	if(is_fx_error($site_object)) {
		echo _('Invalid object identifier or object has wrong type');
	}
	elseif(!$site_object['installed']) {
		echo _('Website not installed! You can install it or delete website object');
	}
	else
	{
		if(!$site_object['linux_username']) {
			$errors[] = 'ERROR: Username is empty!';
		}
		
		if(!$site_object['domain_name']) {
			$errors[] = 'ERROR: Domain name is empty!';
		}

		$out = array();
		
		if($errors) {
			$out = $errors;
		}
		else {
			$commands = array();

			if ($is_wp_website) {	
				$commands[] = $sh_dir.'fx-create-backup.sh "'.$site_object['linux_username'].'" "'.$sh_dir.'dp_backups/" "'.$sh_dir.'wp_backups/"';
				$commands[] = $sh_dir.'fx-mysql-uninstall.sh "'.$site_object['linux_username'].'"';
				$commands[] = 'userdel -r "'.$site_object['linux_username'].'"';
				$commands[] = 'groupdel "'.$site_object['linux_username'].'"';				
				$commands[] = 'rm -f /etc/httpd/conf/vhosts/'.$site_object['domain_name'].'.conf';					
			}
			else {
				$commands[] = 'userdel -r "'.$site_object['linux_username'].'"';
				$commands[] = 'groupdel "'.$site_object['linux_username'].'"';				
				$commands[] = 'rm -f /etc/httpd/conf/vhosts/'.$site_object['domain_name'].'.conf';				
			}

			$out[] = 'host: '.$site_object['domain_name'];
			$out[] = 'port: 22';
			$out[] = 'login as: '.$_GET['user'];
			$out[] = 'password: '.preg_replace('/(.)/', '*', $_GET['password']);

			if (!$ssh = new Net_SSH2($site_object['domain_name'])) {
				$out[] = 'Invalid host';
			}

			if (!$ssh -> login($_GET['user'], $_GET['password'])) {
				$out[] = 'Login failed';
			}
			else {
				$user = '['.$_GET['user'].'@'.$site_object['domain_name'].']';
				
				for ($i=0; $i<count($commands); $i++) {
					$res = $ssh -> exec($commands[$i]);
					$out[] = $user.'# '.$commands[$i].($res ? ' - ['.$res.']' : '');
				}

				$crontab = new FX_Cron($site_object['domain_name'], $_GET['user'], $_GET['password']);

				$crontab -> remove_cronjob('/etc/rc.d/init.d/httpd restart');
				$crontab -> append_cronjob(date("i H j n *",time()+60).' /etc/rc.d/init.d/httpd restart >/dev/null 2>&1');	

				$out[] = '<p><b>The server will be restarted within a minute</b></p>';
				
				update_object_field($object_type_id, $object_id, 'installed', 0);
				//delete_object($object_type_id, $object_id);
			}
		}
		
		echo '<div class="shell">'.implode('</br>',$out).'</div>';
	}