<?php

function insert_initial_db(DB_Wrapper $pdo)
{
	$tables = array();
	
	//#1
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'object_type_tbl',
        'columns'=>array(
            array('name'=>'object_type_id', 'type'=>'integer'),
            array('name'=>'schema_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'system', 'type'=>'integer', 'default'=>0),
            array('name'=>'revisions_number', 'type'=>'integer', 'default'=>1),
            array('name'=>'prefix', 'type'=>'varchar', 'length'=>32),
            array('name'=>'name_format', 'type'=>'varchar', 'length'=>255),
            array('name'=>'name', 'type'=>'varchar', 'length'=>64),
            array('name'=>'default_query_id', 'type'=>'integer', 'not_null'=>true, 'default'=>0),
            array('name'=>'default_form_id', 'type'=>'integer', 'not_null'=>true, 'default'=>0),
            array('name'=>'display_name', 'type'=>'varchar', 'length'=>64),
            array('name'=>'description', 'type'=>'varchar', 'length'=>255)),
        'keys'=> array(
            'primary'=>'object_type_id',
            'auto_increment'=>'object_type_id',
            'unique'=>array('object_type_name_idx'=>'name, schema_id'),
            'index'=>array('object_type_system_idx'=>'system', 'object_type_schema_fk'=>'schema_id'))
    );

	//#2
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'field_type_tbl',
        'columns'=>array(
            array('name'=>'object_type_id', 'type'=>'integer'),
            array('name'=>'name', 'type'=>'varchar', 'length'=>64),
            array('name'=>'caption', 'type'=>'varchar', 'length'=>64, 'default'=>''),
            array('name'=>'description', 'type'=>'varchar', 'length'=>255),
            array('name'=>'mandatory', 'type'=>'integer', 'default'=>0),
            array('name'=>'metric', 'type'=>'integer', 'default'=>0),
            array('name'=>'unit', 'type'=>'integer', 'default'=>0),
            array('name'=>'type', 'type'=>'varchar', 'length'=>32, 'default'=>'varchar'),
            array('name'=>'default_value', 'type'=>'varchar', 'default'=>''),
            array('name'=>'length', 'type'=>'integer'),
            array('name'=>'sort_order', 'type'=>'integer', 'length'=>0)),
        'keys'=> array(
            'unique'=>array('field_type_name_idx'=>'name, object_type_id'),
            'index'=>array('object_type_id'=>'object_type_id'))
    );

	//#3
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'enum_type_tbl',
        'columns'=>array(
            array('name'=>'enum_type_id', 'type'=>'integer'),
            array('name'=>'schema_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'system', 'type'=>'integer', 'default'=>0),
            array('name'=>'name', 'type'=>'varchar', 'length'=>64)),
        'keys'=> array(
            'primary'=>'enum_type_id',
            'auto_increment'=>'enum_type_id',
            'unique'=>array('enum_type_name_idx'=>'name, schema_id'),
            'index'=>array('enum_type_system_idx'=>'system'))
    );

	//#4
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'enum_field_tbl',
        'columns'=>array(
            array('name'=>'enum_field_id', 'type'=>'integer'),
            array('name'=>'enum_type_id', 'type'=>'integer'),
            array('name'=>'label', 'type'=>'varchar', 'length'=>64, 'default'=>''),
            array('name'=>'value', 'type'=>'varchar', 'length'=>64, 'default'=>''),
            array('name'=>'color', 'type'=>'varchar', 'length'=>7, 'default'=>'#ffffff'),
            array('name'=>'opacity', 'type'=>'float', 'default'=>0),
            array('name'=>'sort_order', 'type'=>'integer', 'default'=>0)),
        'keys'=> array(
            'primary'=>'enum_field_id',
            'auto_increment'=>'enum_field_id',
            'index'=>array('enum_type_id'=>'enum_type_id'))
    );

	//#5
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'link_type_tbl',
        'columns'=>array(
            array('name'=>'system', 'type'=>'integer', 'default'=>0),
            array('name'=>'schema_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'object_type_1_id', 'type'=>'integer'),
            array('name'=>'object_type_2_id', 'type'=>'integer'),
            array('name'=>'relation', 'type'=>'integer', 'default'=>0),
            array('name'=>'strength', 'type'=>'integer', 'default'=>0),
            array('name'=>'position', 'type'=>'varchar', 'default'=>'')),
        'keys'=> array(
            'primary'=>'object_type_1_id, object_type_2_id',
            'index'=>array(
                'link_type_type_1_id_idx'=>'object_type_1_id',
                'link_type_type_2_id_idx'=>'object_type_2_id',
                'link_type_system_idx'=>'system',
                'link_type_relation_idx'=>'relation',
                'link_type_strength_idx'=>'strength'))
    );

	//#6
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'link_tbl',
        'columns'=>array(
            array('name'=>'object_type_1_id', 'type'=>'integer'),
            array('name'=>'object_type_2_id', 'type'=>'integer'),
            array('name'=>'object_1_id', 'type'=>'integer'),
            array('name'=>'object_2_id', 'type'=>'integer'),
            array('name'=>'meta', 'type'=>'varchar')),
        'keys'=> array(
            'primary'=>'object_type_1_id,object_1_id,object_type_2_id,object_2_id',
            'index'=>array(
                'link_1_dx'=>'object_type_1_id,object_1_id',
                'link_2_dx'=>'object_type_2_id,object_2_id'))
    );

	//#7	
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'options_tbl',
        'columns'=>array(
            array('name'=>'option_id', 'type'=>'integer'),
            array('name'=>'option_name', 'type'=>'varchar'),
            array('name'=>'option_value', 'type'=>'text')),
        'keys'=> array(
            'primary'=>'option_id',
            'auto_increment'=>'option_id')
    );
	
	//#8
    $tables[] = array(
        'name' => DB_TABLE_PREFIX.'metric_tbl',
        'columns' => array(
            array('name'=>'metric_id', 'type'=>'integer'),
            array('name'=>'system', 'type'=>'integer', 'default'=>0),
            array('name'=>'schema_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'name', 'type'=>'varchar', 'default'=>''),
            array('name'=>'decimals', 'type'=>'integer', 'default'=>0),
            array('name'=>'is_currency', 'type'=>'integer', 'default'=>0),
            array('name'=>'description', 'type'=>'varchar', 'default'=>''),
        ),
        'keys'=>array(
            'primary'=>'metric_id',
            'auto_increment'=>'metric_id',
            'unique'=>array('metric_name_idx' => 'schema_id,name')
        )
    );
	
	//#9
    $tables[] = array(
        'name' => DB_TABLE_PREFIX.'unit_tbl',
        'columns' => array(
            array('name'=>'unit_id', 'type'=>'integer', 'not_null'=>true, 'default'=>0),
            array('name'=>'metric_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'name', 'type'=>'varchar'),
            array('name'=>'factor', 'type'=>'float', 'not_null'=>true, 'default'=>1),
            array('name'=>'decimals', 'type'=>'integer', 'not_null'=>true, 'default'=>0),
            array('name'=>'sort_order', 'type'=>'integer', 'not_null'=>true, 'default'=>0),
        ),
        'keys'=>array(
            'primary'=>'unit_id',
            'auto_increment'=>'unit_id',
            'unique'=>array('field_type_name_idx' => 'unit_id, name')
        )
    );

	//#10
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'sync_tbl',
        'columns'=>array(
            array('name'=>'id', 'type'=>'integer'),
            array('name'=>'sfx_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'set_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'updated', 'type'=>'integer', 'default'=>0),
            array('name'=>'key', 'type'=>'varchar', 'default'=>'')),
        'keys'=> array(
            'primary'=>'id',
            'auto_increment'=>'id')
    );

	//#11
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'temp_tbl',
        'columns'=>array(
            array('name'=>'resource_id', 'type'=>'integer'),
            array('name'=>'add_time', 'type'=>'integer', 'default'=>0),
            array('name'=>'object_type_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'object_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'field_name', 'length'=>64, 'type'=>'varchar', 'default'=>''),
            array('name'=>'field_value', 'type'=>'test', 'default'=>'')),
        'keys'=> array(
            'primary'=>'resource_id',
            'auto_increment'=>'resource_id')
    );
	
	//#12
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'api_cache',
        'columns'=>array(
            array('name'=>'sfx_id', 'type'=>'integer'),
            array('name'=>'request_hash', 'type'=>'varchar', 'length'=>40),
            array('name'=>'time', 'type'=>'integer', 'default'=>0),
            array('name'=>'object_type_id', 'type'=>'integer', 'default'=>0),
            array('name'=>'data', 'type'=>'text')),
        'keys'=> array(
            'unique'=>array('sfx_id' => 'sfx_id,request_hash'))
    );
	
	//#13
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'query_cache',
        'columns'=>array(
            array('name'=>'query_id', 'type'=>'integer'),
            array('name'=>'data', 'type'=>'text')),
        'keys'=> array(
            'unique'=>array('query_id' => 'query_id'))
    );
	
	//#14
    $tables[] = array(
        'name'=>DB_TABLE_PREFIX.'sfx_cache',
        'columns'=>array(
            array('name'=>'sfx_id', 'type'=>'integer'),
			array('name'=>'api_key', 'type'=>'varchar', 'length'=>255,'default'=>'' ),
            array('name'=>'data', 'type'=>'text')),
        'keys'=> array(
			'primary'=>'sfx_id',
            'unique'=>array('api_key' => 'api_key'))
    );

    foreach ($tables as $tbl) {
		
		if ($pdo->is_table_exists($tbl['name'])) {
			$pdo->drop_table($tbl['name']);
		}
		
        $result = $pdo->create_table($tbl);
		
        if (is_fx_error($result)) {
            return new FX_Error(__FUNCTION__, $result->get_error_message());
        }
    }

    $init_data = insert_initial_data($pdo);
	
    if (is_fx_error($init_data)) {
		return $init_data;
	}

    return true;
}

function insert_initial_data(DB_Wrapper $pdo)
{
    $initial_data = array(
	
        'enum_type_tbl' => array(
            array('system', 'schema_id', 'name'),
            array(1, 0, 'Binary'),
            array(1, 0, 'WP Post Type'),
            array(1, 0, 'WP Post Status'),
            array(1, 0, 'WP Comment Status'),
            array(1, 0, 'Month'),
            array(1, 0, 'Day of Week'),
            array(1, 0, 'Sex'),
            array(1, 0, 'Request Status'),
            array(1, 0, 'Chart Type'),
            array(1, 0, 'Chart Legend Orientation'),
            array(1, 0, 'Chart Legend Position'),
            array(1, 0, 'Report Page Orientation'),
            array(1, 0, 'Report Page Format')
        ),
		
        'enum_field_tbl' => array(
            array('enum_type_id', 'label', 'value', 'sort_order'),
            array(1, 'Yes', '1', 0),
            array(1, 'No', '0', 1),
            array(2, 'Post', 'post', 0),
            array(2, 'Page', 'page', 1),
            array(3, 'Draft', 'draft', 0),
            array(3, 'Publish', 'publish', 1),
            array(3, 'Pending', 'pending', 2),
            array(3, 'Future', 'future', 3),
            array(3, 'Private', 'private', 4),
            array(4, 'Disable', 'closed', 0),
            array(4, 'Enable', 'open', 1),
            array(5, 'Jan', '1', 0),
            array(5, 'Feb', '2', 1),
            array(5, 'Mar', '3', 2),
            array(5, 'Apr', '4', 3),
            array(5, 'May', '5', 4),
            array(5, 'Jun', '6', 5),
            array(5, 'Jul', '7', 6),
            array(5, 'Aug', '8', 7),
            array(5, 'Sep', '9', 8),
            array(5, 'Oct', '10', 9),
            array(5, 'Nov', '11', 10),
            array(5, 'Dec', '12', 11),
            array(6, 'Sat', '6', 5),
            array(6, 'Fri', '5', 4),
            array(6, 'Thu', '4', 3),
            array(6, 'Wed', '3', 2),
            array(6, 'Tue', '2', 1),
            array(6, 'Mon', '1', 0),
            array(7, 'Male', '1', 0),
            array(7, 'Female', '0', 1),
            array(8, 'Pending', '0', 0),
            array(8, 'Approved', '1', 1),
            array(8, 'Deny', '2', 2),
            array(9, 'Pie', '5', 5),
            array(9, 'Spline', '4', 4),
            array(9, 'Plot', '3', 3),
            array(6, 'Sun', '7', 6),
            array(9, 'Bar', '2', 2),
            array(9, 'Area', '1', 1),
            array(9, 'Line', '0', 0),
            array(10, 'LEGEND_VERTICAL', '690901', 1),
            array(10, 'LEGEND_HORIZONTAL', '690902', 0),
            array(9, 'Filled spline', '6', 6),
            array(13, 'A8', 'a8', 8),
            array(11, 'CORNER_TOP_RIGHT', '4', 3),
            array(11, 'CORNER_BOTTOM_LEFT', '2', 1),
            array(11, 'CORNER_BOTTOM_RIGHT', '3', 2),
            array(11, 'CORNER_TOP_LEFT', '1', 0),
            array(13, 'A7', 'a7', 7),
            array(13, 'A6', 'a6', 6),
            array(13, 'A5', 'a5', 5),
            array(13, 'A4', 'a4', 4),
            array(13, 'A3', 'a3', 3),
            array(13, 'A2', 'a2', 2),
            array(13, 'A1', 'a1', 1),
            array(13, 'A0', 'a0', 0),
            array(13, 'A9', 'a9', 9),
            array(13, 'A10', 'a10', 10),
            array(13, 'B0', 'b0', 11),
            array(13, 'B1', 'b1', 12),
            array(13, 'B2', 'b2', 13),
            array(13, 'B3', 'b3', 14),
            array(13, 'B4', 'b4', 15),
            array(13, 'B5', 'b5', 16),
            array(13, 'B6', 'b6', 17),
            array(13, 'B7', 'b7', 18),
            array(13, 'B8', 'b8', 19),
            array(13, 'B9', 'b9', 20),
            array(13, 'B10', 'b10', 21),
            array(13, 'C0', 'c0', 22),
            array(13, 'C1', 'c1', 23),
            array(13, 'C2', 'c2', 24),
            array(13, 'C3', 'c3', 25),
            array(13, 'C4', 'c4', 26),
            array(13, 'C5', 'c5', 27),
            array(13, 'C6', 'c6', 28),
            array(13, 'C7', 'c7', 29),
            array(13, 'C8', 'c8', 30),
            array(13, 'C9', 'c9', 31),
            array(13, 'C10', 'c10', 32),
            array(13, 'ra0', 'ra0', 33),
            array(13, 'ra1', 'ra1', 34),
            array(13, 'ra2', 'ra2', 35),
            array(13, 'ra3', 'ra3', 36),
            array(13, 'ra4', 'ra4', 37),
            array(13, 'sra0', 'sra0', 38),
            array(13, 'sra1', 'sra1', 39),
            array(13, 'sra2', 'sra2', 40),
            array(13, 'sra3', 'sra3', 41),
            array(13, 'sra4', 'sra4', 42),
            array(13, 'letter', 'letter', 43),
            array(13, 'legal', 'legal', 44),
            array(13, 'ledger', 'ledger', 45),
            array(13, 'tabloid', 'tabloid', 46),
            array(13, 'executive', 'executive', 47),
            array(13, 'folio', 'folio', 48),
            array(13, 'commercial #10 envelope', 'commercial_10_envelope', 49),
            array(13, 'catalog #10 1/2 envelope', 'catalog_10_1_2_envelope', 50),
            array(13, '8.5x11', '8_5x11', 51),
            array(13, '8.5x14', '8_5x14', 52),
            array(13, '11x17', '11x17', 53),
            array(12, 'portrait', 'portrait', 0),
            array(12, 'landscape', 'landscape', 1)
        ),
		
        'link_type_tbl' => array(
            array('system', 'schema_id', 'object_type_1_id', 'object_type_2_id', 'relation', 'strength', 'position'),
            array(1, 0, 3, 1, 4, 0, '28,199,28,400'),
            array(1, 0, 3, 2, 4, 0, '28,199,168,107'),
            array(1, 0, 3, 15, 4, 0, '28,199,28,10'),
            array(1, 0, 8, 9, 2, 1, '330,10,168,10'),
            array(1, 0, 2, 15, 4, 0, '168,107,28,10')
        ),
		
        'metric_tbl' => array(
            array('metric_id', 'system', 'schema_id', 'name', 'decimals', 'is_currency', 'description'),
            array(1, 1, 0, 'Currency', 0, 1, 'System metric for currency converting')
        ),
		
        'unit_tbl' => array(
            array('metric_id', 'name', 'factor', 'decimals', 'sort_order'),
            array(1, 'EUR', 0, 3, 1),
            array(1, 'USD', 0, 3, 0),
            array(1, 'GBP', 0, 3, 2),
            array(1, 'RUB', 0, 3, 3),
            array(1, 'INR', 0, 3, 4),
            array(1, 'CNY', 0, 3, 5),
        ),
    );

    foreach ($initial_data as $table=>$data)
	{
        $field_names = array_shift($data);
        $insert_data = array();
		
        foreach ($data as $row) {
            $insert_data[] = array_combine($field_names, $row);
        }

        $insert_result = $pdo->insert(DB_TABLE_PREFIX.$table, $insert_data);

        if(is_fx_error($insert_result)) {
            return $insert_result;
        }
    }

    return true;
}

function install_system_types()
{
	//Get all system types
	if (is_fx_error($types = get_schema_types(0))) {
		return $types;
	}

    $system_types = array(
        array('schema_', '', 'data_schema', 'Data Schema', 'System type. Describes a Data Schema, default user roles and user fields for subscription.'),
        array('set_', '', 'data_set', 'Data Set', 'System type. Describes a set of data.'),
        array('sfx_', '', 'subscription', 'Subscription', 'System type. Allows the user to gain access to the Schemes and Sets.'),
        array('www_', '%domain_name%', 'dfx_generic_website', 'DFX Generic Website', 'System type. DFX Generic Website.'),
        array('www_', '%domain_name%', 'dfx_wp_site', 'DFX WP Site', 'System type. DFX Wordpress website.'),
        array('tmpl_', '', 'wp_tmpl_signup', 'WP Signup Template', 'System type. Additional WP signup page.'),
        array('app_', '', 'application', 'Mobile Application', 'System type. Mobile application is parent object for its versions (App Data)'),
        array('app_', '', 'app_data', 'App Data', 'System type. Child of Mobile Application object which cantain version data (code, style, etc.)'),
        array('app_theme_', '', 'application_theme', 'Application Theme', 'System type. Defines interface colors and styles for an application.'),
        array('query_', '', 'query', 'Query', 'System type. Allows the user get object by some condition.'),
        array('form_', '', 'data_form', 'Data form', 'System type. Customizing editing and displaying objects.'),
        array('chart_', '', 'chart', 'Chart', 'System type. Charting based on object data.'),
        array('tsk_', '', 'task', 'Task', 'System type. Execution of actions according to a schedule or some condition.'),
        array('role_', '', 'role', 'Role', 'System type. Specifies access permissions within the Data Schema and Data Set.'),
        array('', '', 'dfx_user', 'DFX User', 'System type. Current FlexiDB administators.'),
        array('fsm_', '', 'fsm_event', 'FSM Event', 'System type. Finite State Machine events.'),
        array('', '', 'report', 'Report', 'System type. Data reports.'),
        array('', 'msg', 'log_msg', 'Log Message', 'System type. FlexiDB system log messages.'),
        array('', 'file', 'media_file', 'Media File', 'FlexiDB media library file.'),
        array('', 'image', 'media_image', 'Media Image', 'FlexiDB media library image.')
    );

    $type_fields = array(
	
        'data_schema'=> array(
            array('roles', 'Roles', 'Default user role which will be assigned on subscribe event', 0, 'text', '', 65536, 0),
            array('user_fields', 'User Fields', 'Object type with additional user fields within for current Data Schema', 0, 'text', '', 65536, 1),
            array('sfx_alias', 'Subscription Alias', 'Select field from additional user fields which will be alias for subscription', 0, 'varchar', '', 255, 2),
            array('channel', 'Channel', '', 0, 'int', '0', 11, 3),
            array('app_group', 'App Group', '', 0, 'int', '0', 11, 4),
            array('icon', 'Icon', 'This image will be used for app and channel', 0, 'image', '0', 0, 5),
			array('sub_chnl_alias', 'Sub-channel Alias', 'Alias for sub-channels at all', 0, 'varchar', 'sub-channel', 0, 6),
			array('sub_chnl_alias_pl', 'Sub-channel Alias Plural', 'Plural form for Sub-channel Alias', 0, 'varchar', '0', 0, 7),
        ),
		
        'data_set' => array(
            array('wp_url', 'WP URL', 'Link to blog if data set is a wordpress blog instance', 0, 'url', '', 0, 0),
            array('description', 'Description', 'Data Set description', 0, 'text', '', 65536, 1),
            array('is_public', 'Public', 'Data set publicly viewable', 0, 1, 1, 0, 2),
        ),
		
        'subscription' => array(
            array('user_id', 'User ID', 'Global User ID in Flexiweb Network', 1, 'int', '', 0, 0),
            array('api_key', 'API Key', 'Subscription API Key', 1, 'varchar', '', 30, 1),
            array('secret_key', 'Secret Key', 'Subscription Secret Key', 1, 'varchar', '', 255, 2),
            array('roles', 'User Roles', '', 0, 'text', '', 0, 3),
            array('is_admin', 'is_admin', '', 0, '1', '0', 0, 4),
        ),
		
        'dfx_generic_website' => array(
            array('linux_username', 'Linux Username', 'FTP username', 1, 'varchar', '', 64, 0),
            array('linux_password', 'Linux Password', 'FTP password', 1, 'varchar', '', 64, 1),
            array('domain_name', 'Domain Name', 'Domain name', 1, 'url', '', 0, 2),
            array('installed', 'Installed', 'Click to install new WordPress instance to the server', 0, '1', '0', 0, 3),
        ),
		
        'dfx_wp_site' => array(
            array('linux_username', 'Linux Username', 'FTP, MySQL username', 1, 'varchar', '', 64, 0),
            array('linux_password', 'Linux Password', 'FTP, MySQL password', 1, 'varchar', '', 64, 1),
            array('domain_name', 'Domain Name', 'Domain name', 1, 'url', '', 0, 2),
            array('wp_sitename', 'WP Sitename', 'Wordpress sitename', 1, 'varchar', '', 255, 3),
            array('wp_admin_username', 'WP Username', 'Wordpress admin username', 1, 'varchar', '', 64, 4),
            array('wp_admin_password', 'WP Password', 'Wordpress admin password', 1, 'varchar', '', 64, 5),
            array('wp_admin_email', 'WP Email', 'Wordpress admin email', 1, 'email', '', 0, 6),
            array('multisite', 'Multisite', 'Do you want to use WP Multisite?', 1, '1', '1', 0, 7),
            array('installed', 'Installed', 'Click to install new WordPress instance to the server', 0, '1', '0', 255, 8),
            array('fx_plugin', 'FX Plugin', 'Click to install/reinstall Flexiweb plugin for this WP site', 0, '1', '0', 0, 9),
            array('fx_theme', 'FX Theme', 'Click to install/reinstall Flexiweb theme for this WP site', 0, '1', '0', 0, 10),
            array('ssh_key', 'SSH Key', '', 0, 'varchar', '', 255, 11),
            array('install_dir', 'Install Dir', '', 0, 'varchar', '', 255, 12),
        ),
		
        'wp_tmpl_signup' => array(
            array('enabled', 'Enabled', 'Enable or disable page template', 0, '1', '1', 0, 0),
            array('mandatory', 'Mandatory', 'Defines necessity of filliing during registration', 0, '1', '1', 0, 1),
            array('associated_type', 'Associated Type', 'The object type which is associated with template', 1, 'int', '', 0, 2),
            array('object_name', 'Object Name', 'Object will be added in DB with name', 1, 'varchar', '', 64, 3),
            array('header', 'Header', 'Header of the section on WP signup page', 0, 'varchar', '', 255, 4),
            array('hint', 'Hint', 'Some information about the value', 0, 'varchar', '', 255, 5),
        ),
		
        'app_data' => array(
            array('version', 'Version', 'version', 0, 'varchar', '', 255, 0),
            array('code', 'Code', 'code', 0, 'text', '', 65536, 1),
            array('style', 'Style', 'style', 0, 'text', '', 65536, 2),
            array('is_development', 'Is Development', 'is_development', 0, '1', '0', 0, 3),
            array('remote_data_id', 'remote_data_id', 'remote_data_id', 0, 'int', '0', 11, 4),
            array('dev_keys', 'dev_keys', 'dev_keys', 0, 'text', '', 65536, 5),
        ),
		
        'application_theme' => array(
            array('css', '', '', 0, 'text', '', 0, 0),
            array('swatches_num', '', '', 0, 'int', '', 0, 1),
        ),
		
        'query' => array(
            array('main_type', 'Main Type', 'Main query type', 0, 'int', '0', 11, 0),
            array('joined_types', 'Joined Types', 'JSON encoded joined query types (optional)', 0, 'text', '', 65535, 1),
            array('code', 'Code', 'code', 0, 'text', '', 65535, 2),
            array('hide_empty', 'Hide Empty', 'Hide empty joined rows', 0, 'int', '0', 11, 3),
            array('filter_by_set', 'Filter by Set', 'filter_by_set', 0, 'int', '0', 11, 4),
        ),
		
        'data_form' => array(
            array('code', '', '', 0, 'text', '', 0, 0),
            array('object_type', '', '', 0, 'int', '', 0, 1),
            array('link_with_user', '', '', 0, '1', '0', 32, 3),
            array('filter_by_set', '', '', 0, '1', '0', 0, 4),
        ),
		
        'chart' => array(
            array('code', '', '', 0, 'text', '', 0, 0),
            array('chart_type', 'Chart Type', 'Chart Type', 1, '9', '4', 0, 1),
            array('query_id', 'Query', 'Query ID', 1, 'int', '', 0, 2),
            array('x', 'x-axis', 'x-axis', 1, 'varchar', '', 255, 3),
            array('g_width', 'Chart width', 'Chart width', 1, 'int', '700', 0, 4),
            array('g_height', 'Chart height', 'Chart height', 1, 'int', '250', 0, 5),
            array('g_border', 'Border', 'Image Border', 0, '1', '1', 0, 6),
            array('g_aa', 'Antialiasing', 'Antialiasing', 0, '1', '1', 0, 7),
            array('g_shadow', 'Shadow', 'Shadow', 0, '1', '1', 0, 8),
            array('g_transparent', 'Transparent background', 'Transparent background', 0, '1', '1', 0, 9),
            array('g_gradient_enabled', 'Gradient', 'Gradient', 0, '1', '1', 0, 10),
            array('g_solid_dashed', 'Dashed Background', 'Solid Dashed Background', 0, '1', '1', 0, 11),
            array('l_enabled', 'Legend enabled', 'Legend enabled', 0, '1', '1', 0, 12),
            array('l_orientation', 'Legend orientation', 'Legend orientation', 0, '10', '690902', 0, 13),
            array('l_position', 'Legend position', 'Legend position', 0, '11', '1', 0, 14),
            array('g_title_enabled', 'Title enabled', 'Title enabled', 0, '1', '1', 0, 15),
            array('g_title', 'Title', 'Title', 0, 'varchar', '', 255, 16),
            array('g_solid_color', 'Solid background color', 'Solid background color', 0, 'varchar', '#AAB757', 255, 17),
            array('g_gradient_start', 'Start gradient color', 'Start gradient color', 0, 'varchar', '#DBE78B', 255, 18),
            array('g_gradient_end', 'End gradient color', 'End gradient color', 0, 'varchar', '#018A44', 255, 19),
        ),
		
        'task' => array(
            array('enabled', '', '', 0, '1', '1', 0, 0),
            array('source', '', '', 0, 'varchar', '', 128, 1),
            array('source_args', '', '', 0, 'text', '', 0, 2),
            array('action', '', '', 0, 'varchar', '', 128, 3),
            array('action_args', '', '', 0, 'text', '', 0, 4),
            array('error', '', '', 0, 'varchar', '', 128, 5),
            array('error_args', '', '', 0, 'text', '', 0, 6),
            array('priority', '', '', 0, 'int', '0', 0, 7),
            array('schedule', '', '', 0, 'varchar', '', 255, 8),
        ),
		
        'role' => array(
            array('data_set_role', 'Data Set Role', 'Allow the data set admin to manage this role', 0, '1', '0', 0, 0),
            array('description', 'Description', 'description', 0, 'text', '', 65536, 1),
            array('permissions', 'Permissions', 'permissions', 0, 'text', '', 65536, 2),
        ),
		
        'dfx_user' => array(
            array('email', 'E-mail', '', 1, 'email', '', 128, 0),
            array('password', 'Password', '', 1, 'password', '', 128, 1),
        ),
		
        'fsm_event' => array(
            array('object_type', 'Object Type', '', 1, 'int', '0', 11, 0),
            array('object_field', 'Object Field', '', 1, 'varchar', '', 255, 1),
            array('start_state', 'Start State', '', 0, 'text', '', 65536, 2),
            array('end_state', 'End State', '', 0, 'text', '', 65536, 3),
            array('event_condition', 'Event Condition', '', 0, 'text', '', 65536, 4),
            array('roles', 'Roles', '', 0, 'text', '', 65536, 5),
            array('enabled', 'Enabled', '', 0, '1', '1', 0, 6),
            array('code', 'Code', '', 0, 'text', '', 65536, 7),
            array('initial_state', 'Initial State', '', 0, 'text', '', 65536, 8),
        ),

        'report' => array(
            array('code', '', '', 0, 'text', '', 65536, 0),
            array('orientation', '', '', 0, '12', 'portrait', 0, 1),
            array('format', '', '', 0, '13', 'a4', 0, 2),
            array('page_numbers', '', '', 0, '1', '0', 0, 3),
        ),

        'log_msg' => array(
            array('code', 'Code', 'code', 1, 'varchar', '', 255, 0),
            array('msg', 'Message', 'msg', 0, 'text', '', 65536, 1),
            array('data', 'Data', 'data', 0, 'text', '', 65536, 2),
        ),

        'media_file' => array(
            array('file', 'File', '', 0, 'file', '', 0, 0),
            array('caption', 'Caption', '', 0, 'varchar', '', 255, 1),
            array('description', 'Description', '', 0, 'varchar', '', 255, 2),
        ),

        'media_image' => array(
            array('image', 'Image', '', 0, 'image', '', 0, 0),
            array('caption', 'Caption', '', 0, 'varchar', '', 255, 1),
            array('alt', 'Alternative Text', '', 0, 'varchar', '', 255, 2),
            array('description', 'Description', '', 0, 'text', '', 255, 3)
        )
    );

    foreach ($system_types as $type) {
        $tmp_type = array_combine(array('prefix', 'name_format', 'name', 'display_name', 'description'), $type);
        if($type_fields[$tmp_type['name']]) {
            foreach ($type_fields[$tmp_type['name']] as $field) {
                $tmp_type['fields'][] = array_combine(array('name', 'caption', 'description', 'mandatory', 'type', 'default_value', 'length', 'sort_order'), $field);
            }
        }

        $tmp[] = $tmp_type;
    }

    $system_types = $tmp;

    foreach ($system_types as $type) {
        $type['schema_id'] = 0;
        $type['system'] = 1;
        $type['revisions_number'] = 1;
		
		if (is_fx_error($object_type_id = add_type($type))) {
			return $res;
		}
		
		define('TYPE_'.strtoupper($type['name']), $object_type_id);
	}

	add_link_type(TYPE_SUBSCRIPTION, TYPE_DATA_SCHEMA, 4, 0, true);
	add_link_type(TYPE_SUBSCRIPTION, TYPE_DATA_SET, 4, 0, true);
	add_link_type(TYPE_SUBSCRIPTION, TYPE_ROLE, 4, 0, true);
	add_link_type(TYPE_APPLICATION, TYPE_APP_DATA, 2, 0, true);
	
	//update_fx_option('sys_links_created', 1);

	return true;
}