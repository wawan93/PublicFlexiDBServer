<?php
	ob_start();

	ini_set("max_execution_time", "120");
	ini_set('display_errors','off');
	error_reporting(0);

	if (!function_exists('curl_init')) {
		die('FlexiDB Server: CURL PHP extension required!');
	}
	
	if (!function_exists('json_decode') || !function_exists('json_encode')) {
		die('FlexiDB Server: JSON extension required!');
	}
	
	if (!in_array('gd',get_loaded_extensions())) {
		die('FlexiDB Server: GD module (PHP extension) not found!');
	}

    if (!file_exists('fx_config.php')) {
        header('Location: install.php');
    }
	
	/*******************************************************************************
	 * Sustem functions
	 ******************************************************************************/

	require_once dirname(__FILE__).'/fx_load.php';

	/******************************************************************************/

	fx_start_session();
	
	if (!current_user_logged_in())
	{
		$redirect_to = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : URL;
		
		if (isset($_POST['login'])) {

			$login_result = do_actions('fx_login', $_POST['username'], $_POST['password'], $_POST['remember_me']);
	
			if (!is_fx_error($login_result)) {
				fx_redirect($redirect_to);
			}
		}

		if (!isset($_POST['login']) && isset($_COOKIE['fx_username']) && isset($_COOKIE['fx_password'])) {
			$login_result = do_actions('fx_login', $_COOKIE['fx_username'], $_COOKIE['fx_password']);

			if (is_fx_error($login_result)) {
				setcookie('fx_username', '');
				setcookie('fx_password', '');
			}
			else {
				fx_redirect($redirect_to);
			}
		}

		if (isset($_POST['lostpassword'])) {
			do_actions('fx_remind_password', $_POST['email']);
		}		
		
		include CONF_FX_DIR."/pages/login.php";
	}
	else
	{
		/*******************************************************************************
		 * Get current Data Schema object
		 ******************************************************************************/
		 
		global $data_schema;
		$data_schema = get_object(TYPE_DATA_SCHEMA, $_SESSION['current_schema']);
		if (is_fx_error($data_schema)) {
			$data_schema = array();
		}
		
		/*******************************************************************************
		 * User Interface
		******************************************************************************/
		
		do_actions('fx_ui_before');
		
		require_once CONF_FX_DIR.'/fx_ui/fx_menu.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_type_form.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_object_form.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_enum_form.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_metric_form.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_object_explorer.php';	
		require_once CONF_FX_DIR.'/fx_ui/fx_link_explorer.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_table_explorer.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_page_content.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_page_init.php';
		require_once CONF_FX_DIR.'/fx_ui/fx_explorer.php';
		
		do_actions('fx_ui_after');
		
		/*******************************************************************************
		 * Unclean URL -> Clean URL
		 ******************************************************************************/
	
		list($page, $first_param, $second_param) = explode('/', $_GET['path']);
	
		define('PAGE', $page ? $page : 'home');
		define('FIRST_PARAM', $first_param ? $first_param : '');
		define('SECOND_PARAM', $second_param ? $second_param : '');
	
		if (PAGE == 'logout') {
			do_actions('fx_logout');			
		}		

		if(isset($_GET['schema_id']) && isset($_GET['set_id'])) {
			set_current_fx_dir($_GET['schema_id'], $_GET['set_id']);
		}

		if (isset($_POST['set_fx_dir'])) {
			set_current_fx_dir($_POST['current_schema'], $_POST['current_set']);
		}		

		if (isset($_REQUEST['set_cet'])) { // Current Explorer Type (Object Explorer)
			$_SESSION['c_et'] = (int)$_REQUEST['object_type_id'];
			//fx_redirect();
		}
		
		if (isset($_POST['set_clt'])) { // Current Link Type (Link Explorer)
			$_SESSION['clt'] = (int)$_POST['clt'];
			fx_redirect();
		}

		if (isset($_POST['set_show_system'])) {
			$_SESSION['show_system'] = $_SESSION['show_system'] ? 0 : 1;
		}

		$initial_errors = new FX_Error();
		
		global $fx_error;
		
		if (is_fx_error($fx_error) && !$fx_error->is_empty()) {
			$initial_errors = $fx_error;
		}
		
		global $schema_db;
		
		if (is_fx_error($schema_db)) {
			$initial_errors -> add('initial_errors', $schema_db->get_error_message());
		}
		
		$server_settings = get_fx_option('server_settings', array());
		
		if (!$server_settings['dfx_key']) {
			$initial_errors -> add('initial_errors', _('Please set DFX Key to be able to use all server tools').' <a href="'.CONF_SITE_URL.'settings/settings_server">Server Settings</a>');
		}

		if (!$server_settings['dfx_key_is_valid'] || !$server_settings['fx_api_base_url']) {
			$initial_errors -> add('initial_errors', _('Please set valid Flexilogin API URL').' <a href="'.CONF_SITE_URL.'settings/settings_server">Server Settings</a>');
		}

		if (!is_writable(CONF_UPLOADS_DIR)) {
			$initial_errors -> add('initial_errors', _('[Uploads] directory is not writable. Please change directory permissions'));
		}
		
		if (file_exists('install.php')) {
			$initial_errors -> add('initial_errors', _('Remove install.php from the root dir'));
		}

		require_once CONF_FX_DIR.'/fx_ui/fx_page.php';
	}
	
	do_actions('fx_shutdown');
	
	ob_end_flush();