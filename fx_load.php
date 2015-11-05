<?php
	
	require_once dirname(__FILE__) . "/fx_config.php";

	$initial = isset($initial) && $initial ? true : false;
	
	define('CONF_FX_DIR', dirname(__FILE__));
	
	define('CONF_LIB_DIR', CONF_FX_DIR . "/lib");
	define('CONF_EXT_DIR', CONF_FX_DIR . "/extensions");
	define('CONF_AJAX_DIR', CONF_FX_DIR . "/ajax");
	define('CONF_API_DIR', CONF_FX_DIR . "/api");
	define('CONF_UPLOADS_DIR', CONF_FX_DIR . "/uploads");
	define('CONF_LOCALE_DIR', CONF_FX_DIR . "/locale");
	define('CONF_PLUGINS_DIR', CONF_FX_DIR . "/plugins");
	define('CONF_IMPORT_UPLOADS_DIR', CONF_UPLOADS_DIR . "/db_import");
    define('CONF_REP_WIDGETS_DIR', CONF_FX_DIR . "/report_widgets");
	
	define('CONF_SITE_URL', DFX_BASE_URL);
	define('URL', DFX_BASE_URL);
	define('CONF_AJAX_URL', DFX_BASE_URL . "ajax/");
	define('CONF_API_URL', DFX_BASE_URL . "api/v1/");
	define('CONF_UPLOADS_URL', DFX_BASE_URL . "uploads/");
	define('CONF_IMAGES_URL', DFX_BASE_URL . "images/");
	define('CONF_PLUGINS_URL', DFX_BASE_URL . "plugins/");
    define('CONF_EXT_URL', DFX_BASE_URL . "extensions/");
	
	define('CONF_IMG_THUMB_WIDTH', 64);
	define('CONF_IMG_THUMB_HEIGHT', 64);
	define('CONF_MAX_FILE_SIZE', 1024 * 1024 * 3);
	
	define('CONF_IMG_THUMB_DEFAULT', 64);
	define('CONF_IMG_SMALL_DEFAULT', 128);
	define('CONF_IMG_MEDIUM_DEFAULT', 512);
	define('CONF_IMG_LARGE_DEFAULT', 768);
	define('CONF_IMG_ORIGINAL_DEFAULT', 1024);
	define('CONF_IMG_QUALITY_DEFAULT', 90);
	
	define('CONF_IMG_SCHEMA_ICON', 160);
	
	define('CONF_OBJ_IMG_TABLE', 22);
	define('CONF_OBJ_IMG_SMALL', 32);
	define('CONF_OBJ_IMG_MEDIUM', 64);
	define('CONF_OBJ_IMG_LARGE', 128);
	
	define('COOKIE_EXPIRE', time() + 2592000); // expire = 30 days
	
	define('RELATION_1_1', 1);
	define('RELATION_1_N', 2);
	define('RELATION_N_1', 3);
	define('RELATION_N_N', 4);
	
	define('LINK_WEAK', 0);
	define('LINK_STRONG', 1);
	
	define('LINK_OPTION_FORBID', 0);
	define('LINK_OPTION_DELETE', 1);
	
	/**
	 * Metabox width options
	 */
	define('FULL_WIDTH', 1);
	define('HALF_RIGHT', 2);
	define('HALF_LEFT', 3);

	/**
	 * Global array: db_reserved_words
	 * reserver keywords for the current databese 
	 * must re-defined in the specified wrapper, otherwise it is blank array
	 */	
	global $db_reserved_words;
	$db_reserved_words = array();

	/**
	 * Global array: fx_hooks
	 */
	global $fx_main_menu, $fx_server_menu;
	//$fx_main_menu = new FX_Menu();
	//$fx_server_menu = new FX_Menu();
	
	/**
	 * Field types: fx_field_types
	 * all field types available on DFX server (except Enum field type)
	 */
	global $fx_field_types;
	$fx_field_types = array();
	
	$fx_field_types['varchar'] = 'Varchar';
	$fx_field_types['text'] = 'Text';
	$fx_field_types['html'] = 'HTML';
	$fx_field_types['int'] = 'Integer';
	$fx_field_types['float'] = 'Float';
	$fx_field_types['datetime'] = 'Date+Time';
	$fx_field_types['date'] = 'Date';
	$fx_field_types['time'] = 'Time';
	$fx_field_types['password'] = 'Password';
	$fx_field_types['url'] = 'URL';
	$fx_field_types['ip'] = 'IP';
	$fx_field_types['email'] = 'E-Mail';
	$fx_field_types['image'] = 'Image';
	$fx_field_types['file'] = 'File';
	$fx_field_types['qr'] = 'QR-Code';
	
	/**
	 * Global array: fx_hooks
	 */
	global $fx_hooks;
	$fx_hooks = array();
	
	/**
	 * Global array: fx_scripts
	 * All JS scripts which need to be loaded
	 */
	global $fx_scripts;
	$fx_scripts = array();
	
	/**
	 * Global array: fx_styles
	 * All CSS styles which need to be loaded
	 */
	global $fx_styles;
	$fx_styles = array();
	
	/**
	 * Global array: user_states
	 */
	
	/*******************************************************************************
	 * User permissions
	 ******************************************************************************/
	define('U_GET', 1 << 0); // 0001
	define('U_POST', 1 << 1); // 0010
	define('U_PUT', 1 << 2); // 0100
	define('U_DELETE', 1 << 3); // 1000
	//define('U_ALL', U_READ | U_CREATE | U_EDIT | U_DELETE); // 1111

	/*******************************************************************************
	 * Database connection
	 ******************************************************************************/
	
	if (!$initial) {
		require_once CONF_FX_DIR . "/fx_db_connect.php";
	}
	
	/*******************************************************************************
	 * FlexiDB library
	 ******************************************************************************/

	require_once CONF_LIB_DIR . '/fx_plugins.php';
	require_once CONF_LIB_DIR . '/fx_error.php';
	require_once CONF_LIB_DIR . '/fx_utils.php';
	require_once CONF_LIB_DIR . '/fx_cache.php';
	require_once CONF_LIB_DIR . '/fx_type.php';
	require_once CONF_LIB_DIR . '/fx_objects.php';
	require_once CONF_LIB_DIR . '/fx_links.php';
	require_once CONF_LIB_DIR . '/fx_enum.php';
	require_once CONF_LIB_DIR . '/fx_units.php';
	require_once CONF_LIB_DIR . '/fx_api.php';
	require_once CONF_LIB_DIR . '/fx_client.php';
	require_once CONF_LIB_DIR . '/fx_revisions.php';
	require_once CONF_LIB_DIR . '/fx_resource.php';
	require_once CONF_LIB_DIR . '/fx_user.php';
	require_once CONF_LIB_DIR . '/fx_cron.php';
	require_once CONF_LIB_DIR . '/fx_options.php';
	require_once CONF_LIB_DIR . '/fx_log.php';
	require_once CONF_LIB_DIR . '/fx_roles.php';
	require_once CONF_LIB_DIR . '/fx_query.php';
	require_once CONF_LIB_DIR . '/fx_backup.php';
	require_once CONF_LIB_DIR . '/fx_sync.php';
	require_once CONF_LIB_DIR . '/fx_format_fields.php';

	if (!$initial)
	{	
		require_once CONF_FX_DIR . '/fx_ui/fx_scripts.php';

		require_once CONF_LIB_DIR . '/fx_charts.php';
		require_once CONF_LIB_DIR . '/fx_tasks.php';
		require_once CONF_LIB_DIR . '/fx_reports.php';
		require_once CONF_LIB_DIR . '/fx_custom_fields.php';

		require_once CONF_EXT_DIR . '/phpqrcode/qrlib.php';
		require_once CONF_EXT_DIR . '/blowfish/blowfish.php';
	}

	/*******************************************************************************/

	global $fx_error;
	$fx_error = new FX_Error();

	/*******************************************************************************
	 * Get all initial options in one query
	 ******************************************************************************/
	 
	$initial_options = array(
		'system_types_cache' => false,
		'fx_datetime_format' => false,
		'active_tasks' => array(),
		'active_plugins' => array(),
		'locale_settings' => array(),
		'system_links_checked' => false,
		'flexidb_version' => false,
		'db_version' => false);

	$initial_options = get_fx_options($initial_options);

	/*******************************************************************************
	 * System types caching
	 ******************************************************************************/
	 
	global $system_types;
	
	if (!$initial)
	{
		$system_types = $initial_options['system_types_cache'];
		
		if ($system_types === false) {
			foreach ((array)get_schema_types(0, 'none') as $type) {
				$system_types[$type['name']] = $type['object_type_id'];
			}
			asort($system_types);
			update_fx_option('system_types_cache', $system_types);
		}
		
		foreach ($system_types as $type_name=>$object_type_id) {
			define('TYPE_'.strtoupper($type_name), $object_type_id);
		}
	}
	else {
		$system_types = array();
	}

	if ($initial) {
		return;
	}
	
	/*******************************************************************************
	 * Check system links
	 ******************************************************************************/
	
	if (!$initial_options['system_links_checked']) {
		check_system_links();
	}
	
	/*******************************************************************************
	 * Check interanl version number
	 ******************************************************************************/	
	
	if (!$initial_options['flexidb_version'] || !$initial_options['db_version']) {		
		if ($version_info = file_get_contents('version_info.txt')) {
			list($flexidb_version, $db_version) = explode(';', $version_info);
			update_fx_option('flexidb_version', $flexidb_version);
			update_fx_option('db_version', $db_version);
		}
		else {
			add_log_message('fxload_update_internal_version', 'Unable to read version_info.txt');
		}
	}

	/*******************************************************************************
	 * Date time format cache
	 ******************************************************************************/
	 
	$dt_default = array('date'=> array('format'=>'F j, Y'), 
						'time'=> array('format'=>'g:i a'));
	
	if (!$dt_format = $initial_options['fx_datetime_format']) {
		$dt_format = $dt_default;
	};
	
	define('FX_DATE_FORMAT', $dt_format['date']['format']);
	define('FX_TIME_FORMAT', $dt_format['time']['format']);

	/*******************************************************************************
	 * Include active task actions
	 ******************************************************************************/

	foreach ((array)$initial_options['active_tasks'] as $methods) {
		foreach ($methods as $endpoints) {
			foreach ($endpoints as $class => $data) {
				if (isset($data['path']) && file_exists($data['path'])) {
					include_once $data['path'];
				}
			}
		}
	}
	
	/*******************************************************************************
	 * Include active plugins
	 ******************************************************************************/
	
	foreach ((array)$initial_options['active_plugins'] as $plugin_name => $plugin_path) {
		include_once $plugin_path;
	}

	/*******************************************************************************
	 * Fx init actions
	 ******************************************************************************/
	
	do_actions('fx_init');
	
	/*******************************************************************************
	 * Set locale settings
	 ******************************************************************************/
	
	$locale_settings = (array)$initial_options['locale_settings'];
	
	if (in_array($locale_settings['timezone'], timezone_identifiers_list())) {
		date_default_timezone_set($locale_settings['timezone']);
	}
	
	if ($locale_settings['locale']) {
		$current_locale = $locale_settings['locale'];
		$domain = 'messages';
	
		putenv("LC_ALL=$current_locale");
		setlocale(LC_ALL, $current_locale);
		bindtextdomain($domain, CONF_LOCALE_DIR);
		bind_textdomain_codeset($domain, 'UTF-8');
		textdomain($domain);
	}

	/*******************************************************************************/
	
	global $data_schema;
	$data_schema = array();