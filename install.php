<?php

	ini_set('display_errors', 1);
	error_reporting(E_ERROR);
	set_time_limit(0);
	
	$fx_dir = dirname(__FILE__);
	
	$action = 'install';
	$flexidb_version = $db_version = '';
	
	if (isset($_REQUEST['reinstall'])) {
		if (!file_exists('fx_config.php') || unlink('fx_config.php') === true) {
			$action = 'reinstall';
		}
		else {
			$action = 'reinstall_failed';
		}
	}

	function _is_url($value) {
		$stmt = "~^(?:(?:https?|ftp|telnet)://(?:[a-z0-9_-]{1,32}(?::[a-z0-9_-]{1,32})?@)?)?(?:(?:[a-z0-9-]{1,128}\.)+(?:com|net|org|mil|edu|arpa|gov|biz|info|aero|inc|name|[a-z]{1,10})|(?!0)(?:(?!0[^.]|255)[0-9]{1,3}\.){3}(?!0|255)[0-9]{1,3})(?:/[a-z0-9.,_@%&?+=\~/-]*)?(?:#[^ '\"&<>]*)?$~i";
		return preg_match($stmt, $value) ? true : false;
	}
	
	function _is_email($value) {
		return preg_match("/^[^0-9]+[A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/", $value) ? true : false;
	}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install FlexiDB Server</title>
    <style type="text/css">
        * {
            text-align: center;
        }

        body {
            background: #323232;
            color: #ffffff;
            font-family: sans-serif;
        }

        form {
            padding: 15px 50px;
            background: #292929;
            width: 500px;
            margin: 30px auto;
        }

        .error-msg {
            background: #993333;
            color: #FFFFFF;
            padding: 5px;
            font-size: 14px;
            margin-bottom: 2px;
            display: block;
        }

        input, select {
            color: #a9a9a9;
            background: #505050;
            border: none;
            outline: none;
            font-size: 14px;
            float: left;
            margin-left: 40%;
            margin-top: -40px;
            width: 60%;
            height: 35px;
        }

        input[type=checkbox] {
            width: 35px;
            color: #fff;
            background-color: #505050;
            -moz-appearance: none;
            -webkit-appearance: none;
            -o-appearance: none;
        }

        input[type=checkbox]:checked {
            background: url("images/install-tick.png");
            background-color: #505050;
            background-position: center;
            background-size: contain;
        }

        label {
            float: right;
            margin: 15px;
            margin-right: 65%;
        }

        label[for=agreement] {
            float: none;
            width: 100%;
            text-align: center;
            position: relative;
            line-height: 40px;
        }

        input[name=agreement] {
            float: none;
            margin: 0;
            padding: 0;
            position: absolute;
            right: -50px;
            top: -8px;
        }

        hr, br, button {
            clear: both;
        }

        hr {
            color: #555;
            background-color: #555;
            border: 0px none;
            height: 1px;
            clear: both;
            margin-top: 10px;
        }

        button {
            background: #99b64c;
            border: none;
            font-size: 1.5em;
            color: #FFFFFF;
            padding: 10px 45px;
            cursor: pointer;
        }

        button:hover {
            background: #748840;
        }

        a {
            color: #aaa;
            text-decoration: underline;
        }

        a:hover {
            text-decoration: none;
        }

    </style>
</head>
<body>

<?php

	$MET = ini_get('max_execution_time');
	
	if (ini_get('safe_mode') || (0 < $MET && $MET < 300)) {
		echo "
		<h1>Warning!</h1>
		<p>Installation process may take a lot of time</p>
		<p class='error-msg'>Please, turn off <code>safe_mode</code> option in php.ini or set <code>max_execution_time</code> greater than 300</p>";
	}
	
	if (!is_writable(dirname(__FILE__))) {
		echo "
		<h1>FlexiDB Error</h1>
		<p>Unable to write into the current directory. Please, ensure that you have sufficient permissions.</p>";
		die();
	}
	
	if (!function_exists('curl_init')) {
		echo "
		<h1>FlexiDB Error</h1>
		<p>CURL PHP extension required for using FlexiDB</p>";
		die();
	}
	
	if (!function_exists('json_decode') || !function_exists('json_encode')) {
		echo "
		<h1>FlexiDB Error</h1>
		<p>JSON PHP extension required for using FlexiDB</p>";
		die();
	}
	
	if (!in_array('gd', get_loaded_extensions())) {
		echo "
		<h1>FlexiDB Error</h1>
		<p>GD module (PHP extension) required for using FlexiDB</p>";
		die();
	}
	
	if (file_exists("fx_config.php")) {
		echo '
		<h1>FlexiDB Error</h1>
		<p>fx_config.php already exists!</p>
		<p>Are you sure that you want to reinstall FlexiDB Server?</p>
		<p>1) <a href="?reinstall">Yes, I want to reinstall FlexiDB Server</a></p>
		<p>2) <a href="'.$base_url.'">Back to the FlexiDB Server</a></p>';
		die();
	}

	$errors = array();

	if (isset($_POST['flexidb_install'])) {
	
		$base_url = $_POST['base_url'];
		
		if (!_is_url($base_url)) {
			$errors[] = 'Invalid base FlexiDB Server url';
		}
	
		$db_driver = 'mysql';//$_POST['db_driver']; Mysql only for Beta version
		
		if (!$db_host = $_POST['db_host']) {
			$errors[] = 'Please set database host';
		}
		
		if (!$db_user = $_POST['db_user']) {
			$errors[] = 'Please set database user';
		}
		
		if (!$db_pass = $_POST['db_pass']) {
			$errors[] = 'Please set database password';
		}
		
		if (!$db_name = $_POST['db_name']) {
			$errors[] = 'Please set database name';
		}

		$db_table_prefix = $_POST['db_table_prefix'];
		$db_sample_data = isset($_POST['db_sample_data']) ? true : false;
	
		if (!$conf_admin_login = $_POST['conf_admin_login']) {
			$errors[] = 'Please set login';
		}
	
		$conf_admin_email = $_POST['conf_admin_email'];
	
		if (!_is_email($conf_admin_email)) {
			$errors[] = 'Please set valid e-mail address';
		}
	
		$password = $_POST['password'];
		$password_confirm = $_POST['password_confirm'];
	
		if (!$password) {
			$errors[] = 'Please set password';
		}
		else {
			if (!$password_confirm) {
				$errors[] = 'Please confirm, entered password';
			}
			else {
				if ($password != $password_confirm) {
					$errors[] = 'Entered passwords are not the same';
				}
			}
		}
	
		if (!isset($_POST['agreement'])) {
			$errors[] = 'Please confirm that you are argee with our terms of use';
		}
	
		if (!$errors) {
	
			$config_content = "<?php\n";
			$config_content .= "\t/* Main Flexilogin Server url*/\n";
			$config_content .= "\tdefine('FX_SERVER', 'https://flexilogin.com/');\n\n";
			$config_content .= "\t/* Current Flexidb Server base url*/\n";
			$config_content .= "\tdefine('DFX_BASE_URL', '$base_url');\n\n";
			$config_content .= "\t /* Database credentials */\n";
	
			$config_content .= "\tdefine('DB_DRIVER', '$db_driver');\n";
			$config_content .= "\tdefine('DB_HOST', '$db_host');\n";
			$config_content .= "\tdefine('DB_USER', '$db_user');\n";
			$config_content .= "\tdefine('DB_PASS', '$db_pass');\n";
			$config_content .= "\tdefine('DB_TABLE_PREFIX', '$db_table_prefix');\n";
			$config_content .= "\tdefine('DB_NAME', '$db_name');\n\n";
			$config_content .= "\t/* Base Flexidb Server settings */\n";
			$config_content .= "\tdefine('CONF_ENABLE_UPDATES', true);\n";
			$config_content .= "\tdefine('CONF_ENABLE_AUTH_LOG', true);\n";
			$config_content .= "\tdefine('DEBUG', false);\n";
			$config_content .= "\tdefine('CACHING_ENABLED', true);\n";

			file_put_contents('fx_config.php', $config_content);

			if (!file_exists('fx_config.php')) {
				echo "
				<h1>FlexiDB Error</h1>
				<p>Unable to create fx_config.php</p>";
				die();				
			}
			else {
				require_once $fx_dir.'/db_wrapper/abstract_wrapper.php';

				global $fx_db;

				try {
					$fx_db = DB_Wrapper::connect($db_host, $db_user, $db_pass, $db_name);
					$fx_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
				}
				catch (PDOException $e) {
					echo '
					<h1>FlexiDB Error</h1>
					<div class="error-msg">
						Unable to connect to database <strong>'.$db_name.'</strong> using specified credentials<br>
						Please ensure that database exists and you are using correct login details
					</div>
					<p>Error occured during installation process</p>
					<p><a href="?reinstall">Try to reinstall FlexiDB Server</a></p>';
					die();
				}				
				
				$initial = true; //Initial mode for fx_load.php

				require_once $fx_dir.'/fx_load.php';
				require_once $fx_dir.'/db_wrapper/initial_db.php';
				
				$install_db = insert_initial_db($fx_db);

				$insert_system_types = install_system_types($fx_db);

				if (is_fx_error($insert_system_types)) {
					echo '<div class="error-msg">'.$insert_system_types->get_error_message().'</div>';
				}

				$flexidb_user = array(
					'object_type_id' => TYPE_DFX_USER,
					'schema_id' => 0,
					'set_id' => 0,
					'display_name' => $conf_admin_login,
					'email' => $conf_admin_email,
					'password' => $password);

				add_object($flexidb_user);

				insert_default_sfx();

				$version_path = $fx_dir.'/version_info.txt';

				if ($version_info = file_get_contents($version_path)) {
					list ($flexidb_version, $db_version) = explode(';', $version_info);
					update_fx_option('flexidb_version', $flexidb_version);
					update_fx_option('db_version', $db_version);
				}
				else {
					add_log_message('flexidb_install', 'Unable to read '.$version_path);
				}

				$rss1 = array(
					'rss_title' => 'FlexiDB Blog',
					'rss_url' => 'http://flexidb.com/feed/',
					'rss_items' => '7',
					'rss_show_content' => 1,
					'rss_show_date' => 1,
					'rss_show_author' => 1);

				update_fx_option('rss_options_1', $rss1);

				$rss2 = array(
					'rss_title' => 'WordPress RSS',
					'rss_url' => 'http://wordpress.org/news/feed/',
					'rss_items' => '10',
					'rss_show_content' => 1,
					'rss_show_date' => 1,
					'rss_show_author' => 1);

				update_fx_option('rss_options_2', $rss2);

				$server_settings = array();

				$server_settings['fx_api_base_url'] = 'https://flexilogin.com/flexiweb/api/v1/';
				$server_settings['api_validated'] = 1;
				$server_settings['api_last_check'] = time();

				update_fx_option('server_settings', $server_settings);

				//add demo data
				//-------------------------------------------------------------------------

				$demo_schema = array(
					'object_type_id' => TYPE_DATA_SCHEMA,
					'schema_id' => 0,
					'set_id' => 0,
					'name' => 'demo_schema',
					'display_name' => 'Demo Schema');
					
				$schema_id = add_object($demo_schema);
				
				if (!is_fx_error($schema_id)) {
					$demo_set = array(
						'object_type_id' => TYPE_DATA_SET,
						'schema_id' => $schema_id,
						'set_id' => 0,
						'name' => 'demo_set',
						'display_name' => 'Demo Set');
						
					$set_id = add_object($demo_set);
					
					if (is_fx_error($set_id)) {
						add_log_message('add_demo_set', 'Unable to add demo Data Set. '.$set_id->get_error_message());
					}
				}
				else {
					add_log_message('add_demo_schema', 'Unable to add demo Data Schema. '.$schema_id->get_error_message());
				}

				//-------------------------------------------------------------------------
				//end of demo data
				
				$res = dfx_login($conf_admin_login, $password, true);

				$action = 'completed';
			}
		}
	}
	else {
		$base_url = 'http';
	
		if ($_SERVER["HTTPS"] == "on") {
			$base_url .= "s";
		}
	
		$base_url .= "://";
	
		if ($_SERVER["SERVER_PORT"] != "80") {
			$base_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER['PHP_SELF'];;
		}
		else {
			$base_url .= $_SERVER["SERVER_NAME"] . $_SERVER['PHP_SELF'];
		}
	
		$url = explode('/', $base_url);
		array_pop($url);
	
		$base_url = implode('/', $url) . '/';
		$user_id = '';
		$api_key = '';
		$flexidb_key = '';
	
		$db_host = 'localhost';
		$db_user = '';
		$db_pass = '';
		$db_name = 'flexidb';
		$db_table_prefix = 'fx_';
		$db_sample_data = false;
	
		$conf_admin_login = 'admin';
		$conf_admin_email = '';
		$password = '';
		$password_confirm = '';
	}

	switch ($action) {
		case 'reinstall_failed':
		?>

			<h1>FlexiDB Error</h1>
			<p>Unable to remove <code>fx_config.php</code></p>
            <p>Remove it manually and run intallation process again</p>

        <?php
		break;
		case 'completed':
		?>
        
            <h1>Congratulations!</h1>
            <p>FlexiDB Server <?php echo $flexidb_version ?>was successfully installed</p>
            <p style="font-size:24px"><a href="<?php echo $base_url?>">Go to FlexiDB Server</a><p>

		<?php
		break;
		case 'install':
		case 'reinstall':
		default:
		?>

            <form action="" method="post" id="mainForm">
                <img src="images/logo.png" alt="FlexiDB"/>
                <h1>Install FlexiDB server</h1>
                <hr>
                <?php
					if ($errors) {
						foreach ($errors as $error) {
							echo '<div class="error-msg" style="background:#993333; color:#FFFFFF; padding:5px; font-size: 12px; margin:1px;">' . $error . '</div>';
						}
						echo '<hr>';
					}
                ?>
                <input type="hidden" name="flexidb_install">
                <label for="base_url">FlexiDB base url</label>
                <input type="text" name="base_url" id="base_url" value="<?php echo $base_url; ?>"><br>
                <hr>
                <label for="db_driver">DB driver</label>
                <label for="db_host">DB host</label>
                <input type="text" name="db_host" id="db_host" value="<?php echo $db_host; ?>"><br>
                <label for="db_user">DB user</label>
                <input type="text" name="db_user" id="db_user" value="<?php echo $db_user; ?>"><br>
                <label for="db_pass">DB pass </label>
                <input type="text" name="db_pass" id="db_pass" value="<?php echo $db_pass; ?>"><br>
                <label for="db_name">DB name</label>
                <input type="text" name="db_name" id="db_name" value="<?php echo $db_name; ?>"><br>
                <label for="db_table_prefix">Table prefix <i>(optional)</i></label>
                <input type="text" name="db_table_prefix" id="db_table_prefix" value="<?php echo $db_table_prefix; ?>"><br>
                <hr>
                <label for="conf_admin_login">Admin login</label>
                <input type="text" name="conf_admin_login" id="conf_admin_login" value="<?php echo $conf_admin_login; ?>"><br>
                <label for="conf_admin_email">Admin email</label>
                <input type="text" name="conf_admin_email" id="conf_admin_email" value="<?php echo $conf_admin_email; ?>"><br>
                <label for="password">Admin password</label>
                <input type="password" name="password" id="password" value="<?php echo $password; ?>"><br>
                <label for="password_confirm">Confirm password</label>
                <input type="password" name="password_confirm" id="password_confirm" value="<?php echo $password_confirm; ?>"><br>
                <hr>
                <label for="agreement">
                    I agree to the terms of the <a href="http://flexidb.com/licence/" target="_blank">software licence</a>
                    <input class="agreement" type="checkbox" name="agreement"
                           id="agreement" <?php isset($_POST['agreement']) ? 'checked="checked"' : ''; ?>>
                </label>
                <hr>
                <button type="submit">Install</button>
            </form>

        <?php
	}
?>
</body>
</html>