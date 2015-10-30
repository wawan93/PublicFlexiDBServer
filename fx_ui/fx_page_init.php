<?php
/**
 * Add menu items
 */
global $fx_main_menu, $fx_server_menu, $data_schema;

$fx_main_menu = new FX_Menu();
$fx_server_menu = new FX_Menu();

$fx_main_menu->add('home', _('Home'), _('Welcome to DFX Admin tool'), URL, 0, URL.'images/menu_icons/menu_home.png', URL.'images/page_icons/icon_page_home.png' );
$fx_main_menu->add('network_admin', _('DB Admin'), _('Database Admin'), '#', 10, URL.'images/menu_icons/menu_db_admin.png', URL.'images/page_icons/icon_page_db_admin.png' );

$fx_main_menu->add_submenu('network_admin', 'network_data_schemas', _('Data Schemas'), _('Data Schema Editor'), URL.'network_admin/network_data_schemas', 10 );
$fx_main_menu->add_submenu('network_admin', 'network_subscriptions', _('Subscriptions'), _('Subscriptions'), URL.'network_admin/network_subscriptions', 40 );
$fx_main_menu->add_submenu('network_admin', 'backup', _('Database Backup'), _('Backup Manager'), URL.'network_admin/backup', 80 );
$fx_main_menu->add('plugins', _('Plugins'), _('Plugins'), '#', 90, URL.'images/menu_icons/menu_plugins.png', URL.'images/page_icons/icon_page_plugins.png' );
$fx_main_menu->add('settings', _('Settings'), _('Settings'), '#', 10000, URL.'images/menu_icons/menu_settings.png', URL.'images/page_icons/icon_page_settings.png' );

if ($data_schema) {
	$fx_main_menu->add('schema_admin', _('Schema Admin'), _('Data Schema Admin'), '#', 20, URL.'images/menu_icons/menu_schema_admin.png', URL.'images/page_icons/icon_page_schema_admin.png' );
	$fx_main_menu->add('design_editor', _('Design Editor'), _('Design Editor'), '#', 30, URL.'images/menu_icons/menu_design_editor.png', URL.'images/page_icons/icon_page_design_editor.png' );	
	$fx_main_menu->add('wp_templates', _('Templates'), _('Wordpress Templates'), URL.'wp_templates', 40, URL.'images/menu_icons/menu_tmpl.png', URL.'images/page_icons/icon_page_tmpl.png' );
	$fx_main_menu->add('data_editor', _('Data Editor'), _('Data Editor'), '#', 50, URL.'images/menu_icons/menu_data_editor.png', URL.'images/page_icons/icon_page_data_editor.png' );
	$fx_main_menu->add('component', _('DB Widgets'), _('Database Widgets'), '#', 60, URL.'images/menu_icons/menu_db_widgets.png', URL.'images/page_icons/icon_page_widgets.png' );
	$fx_main_menu->add('app_editor', _('App Editor'), _('Application Editor'), '#', 70, URL.'images/menu_icons/menu_app_editor.png', URL.'images/page_icons/icon_page_app.png' );
	$fx_main_menu->add('task_editor', _('Task Editor'), _('Task Editor'), '#', 80, URL.'images/menu_icons/menu_tasks.png', URL.'images/page_icons/icon_page_task_editor.png' );
	$fx_main_menu->add('report', _('Report Tool'), _('Report Tool'), '#', 90, URL.'images/menu_icons/menu_reports.png', URL.'images/page_icons/icon_page_reports.png' );
	
	$fx_main_menu->add_submenu('schema_admin', 'schema_data_sets', _('Data Sets'), _('Data Set Editor'), URL.'schema_admin/schema_data_sets', 10 );
	$fx_main_menu->add_submenu('schema_admin', 'schema_channel', _('Channel'), _('Channel'), URL.'schema_admin/schema_channel', 20 );
	$fx_main_menu->add_submenu('schema_admin', 'schema_icon', _('Icon'), _('Data Schema Icon'), URL.'schema_admin/schema_icon', 30 );
	$fx_main_menu->add_submenu('schema_admin', 'schema_media', _('Media'), _('Data Schema Media'), URL.'schema_admin/schema_media', 40 );
	$fx_main_menu->add_submenu('schema_admin', 'schema_roles', _('Roles'), _('Roles Manager'), URL.'schema_admin/schema_roles', 50 );
	$fx_main_menu->add_submenu('schema_admin', 'schema_options', _('Options'), _('Schema Options'), URL.'schema_admin/schema_options', 60 );	

	$fx_main_menu->add_submenu('design_editor', 'design_system_types', _('System Types'), _('System Object Types'), URL.'design_editor/design_system_types', 10 );
	$fx_main_menu->add_submenu('design_editor', 'design_system_enums', _('System Enums'), _('System Enum Types'), URL.'design_editor/design_system_enums', 20 );
	$fx_main_menu->add_submenu('design_editor', 'design_system_metrics', _('System Metrics'), _('System Metrics'), URL.'design_editor/design_system_metrics', 30 );
	$fx_main_menu->add_submenu('design_editor', 'design_types', _('Object Types'), _('Object Type Editor'), URL.'design_editor/design_types', 40 );
	$fx_main_menu->add_submenu('design_editor', 'design_enums', _('Enum Types'), _('Enum Type Editor'), URL.'design_editor/design_enums', 50 );
	$fx_main_menu->add_submenu('design_editor', 'design_metrics', _('Metrics'), _('Metrics Editor'), URL.'design_editor/design_metrics', 60 );
	$fx_main_menu->add_submenu('design_editor', 'design_er', _('ER Diagram'), _('ER Diagram Designer'), URL.'design_editor/design_er', 70 );
	$fx_main_menu -> add_submenu('design_editor', 'design_fsm', _('FSM Editor'), _('FSM Editor'), URL.'design_editor/design_fsm', 80);

	$fx_main_menu->add_submenu('data_editor', 'data_objects', _('Objects'), _('Objects Editor'), URL.'data_editor/data_objects' );	
	
	$fx_main_menu->add_submenu('component', 'component_query_editor', _('Query Editor'), _('Query Editor'), URL.'component/component_query_editor', 10);
	$fx_main_menu->add_submenu('component', 'component_form_editor', _('Form Editor'), _('Form Editor'), URL.'component/component_form_editor', 20);
	
	if (is_debug_mode()) {
		$fx_main_menu->add_submenu('component', 'component_objects', _('Component Objects (d)'), _('Component Objects (debug mode)'), URL.'component/component_objects', 40);	
	}
	
	$fx_main_menu->add_submenu('task_editor', 'task_editor_tool', _('Task Editor'), _('Task Editor'), URL.'task_editor/task_editor_tool', 10);
	$fx_main_menu->add_submenu('task_editor', 'task_actions', _('Task Actions'), _('Task Actions'), URL.'task_editor/task_actions', 20);
	$fx_main_menu->add_submenu('task_editor', 'task_action_editor', _('Task Action Editor'), _('Task Action Editor'),URL.'task_editor/task_action_editor', 30);
	
	$fx_main_menu->add_submenu('report', 'report_tool', _('Report Tool'), _('Report Tool'), URL.'report/report_tool', 10);
	$fx_main_menu->add_submenu('report', 'report_charts', _('Chart Editor'), _('Chart Editor'), URL.'report/report_charts', 20);
	$fx_main_menu->add_submenu('report', 'report_widgets', _('Widgets'), _('Widgets'), URL.'report/report_widgets', 30);
	
	if (is_debug_mode()) {
		$fx_main_menu->add_submenu('app_editor', 'app_objects', _('App Objects (d)'), _('App Objects (debug mode)'), URL.'app_editor/app_objects', 1);
	}
	$fx_main_menu->add_submenu('app_editor', 'app_group', _('App Group'), _('App Group'), URL.'app_editor/app_group', 10);
	$fx_main_menu->add_submenu('app_editor', 'app_release_manager', _('Release Manager'), _('Release Manager'), URL.'app_editor/app_release_manager', 20);
	$fx_main_menu->add_submenu('app_editor', 'app_pages', _('Pages Editor'), _('Pages Editor'), URL.'app_editor/app_pages', 30);	
	$fx_main_menu->add_submenu('app_editor', 'app_themeroller', _('Theme Roller'), _('Theme Roller'), URL.'app_editor/app_themeroller', 40);
	$fx_main_menu->add_submenu('app_editor', 'app_preview', _('Preview Tool'), _('App Preview Tool'), URL.'app_editor/app_preview', 50);			
}
else {
	$fx_main_menu->add('app_editor', _('App Editor'), _('Application Editor'), '#', 70, URL.'images/menu_icons/menu_app_editor.png', URL.'images/page_icons/icon_page_app.png' );
	$fx_main_menu->add_submenu('app_editor', 'app_preview', _('Preview Tool'), _('App Preview Tool'), URL.'app_editor/app_preview', 50);
}

$fx_main_menu->add_submenu('plugins', 'plugins_installed', _('Installed Plugins'), _('Installed Plugins'), URL.'plugins/plugins_installed');
$fx_main_menu->add_submenu('plugins', 'plugins_add', _('Add New'), _('Add New'), URL.'plugins/plugins_add');
$fx_main_menu->add_submenu('plugins', 'plugins_editor', _('Editor'), _('Editor'), URL.'plugins/plugins_editor' );

$fx_main_menu->add_submenu('settings', 'settings_server', _('Server'), _('Server Settings'), URL.'settings/settings_server', 10);
$fx_main_menu->add_submenu('settings', 'settings_general', _('General'), _('General Settings'), URL.'settings/settings_general', 20);
$fx_main_menu->add_submenu('settings', 'settings_personal', _('Personal'), _('Personal Settings'), URL.'settings/settings_personal', 30);
$fx_main_menu->add_submenu('settings', 'settings_keys', _('API keys'), _('API keys'), URL.'settings/settings_keys', 40);
$fx_main_menu->add_submenu('settings', 'settings_dfx_users', _('DFX Users'), _('DFX Server users'), URL.'settings/settings_dfx_users', 50);
$fx_main_menu->add_submenu('settings', 'settings_update', _('Update'), _('Flexiweb Update'), URL.'settings/settings_update', 60);
$fx_main_menu->add_submenu('settings', 'settings_log', _('Message Log'), _('FlexiDB Message Log'), URL.'settings/settings_log', 70);

$fx_main_menu = do_actions('fx_add_main_menu_items', $fx_main_menu);

/**
 * Enqueue JS scripts
 */

// root (for all pages)

fx_enqueue_scripts(array(
	URL.'js/jquery.js',
	URL.'js/jquery-ui.custom.min.js',
	URL.'extensions/tiny_mce/tiny_mce.js',
	URL.'extensions/colourbox/colourbox.jquery.min.js',
	URL.'js/jquery.session.js',
	URL.'js/jquery.ui.touch-punch.min.js',
	URL.'js/jquery.blockUI.js',
	URL.'js/jquery.cookie.js',
	URL.'js/flexiweb.js',
	URL.'js/general.js',
), '/', 'header');

// /app_editor
fx_enqueue_script('', URL.'js/jquery.minicolors.js', array('/app_editor', '/design_editor','/report/report_charts'), 'custom');
fx_enqueue_script('', URL.'js/ICanHaz.min.js', '/app_editor', 'custom');

// /app_editor/app_pages
fx_enqueue_script('', URL.'mobile_app/js/themeroller.js', '/app_editor/app_themeroller', 'custom');

fx_enqueue_script('', URL.'js/codemirror.js', '/app_editor/app_pages', 'custom');
fx_enqueue_script('', URL.'js/codemirror.javascript.js', '/app_editor/app_pages', 'custom');
fx_enqueue_script('', URL.'js/codemirror.javascript-hint.js', '/app_editor/app_pages', 'custom');
fx_enqueue_script('', URL.'js/codemirror.simple-hint.js', '/app_editor/app_pages', 'custom');

// /design_editor
fx_enqueue_script('', URL.'js/editor.task.js', '/task_editor/task_editor_tool', 'custom');

fx_enqueue_script('', URL.'js/ICanHaz.min.js', '/design_editor/design_er', 'custom');
fx_enqueue_script('', URL.'js/ER.tool.js', '/design_editor/design_er', 'custom');


// /component
fx_enqueue_script('', URL.'js/ICanHaz.min.js', '/component', 'custom');

fx_enqueue_script('', URL.'js/jquery.blockUI.js', '/component', 'custom');

fx_enqueue_script('', URL.'js/codemirror.js', '/component', 'custom');
fx_enqueue_script('', URL.'js/codemirror.javascript.js', '/component', 'custom');
fx_enqueue_script('', URL.'js/codemirror.javascript-hint.js', '/component', 'custom');
fx_enqueue_script('', URL.'js/codemirror.simple-hint.js', '/component', 'custom');
fx_enqueue_script('', URL.'js/jquery.minicolors.js', array('/report/report_charts','/schema_admin/schema_icon'), 'custom');
fx_enqueue_script('', URL.'js/editor.application.js', '/app_editor/app_preview', 'custom');
fx_enqueue_script('', URL.'mobile_app/js/blowfish.js', '/app_editor/app_preview', 'custom');

fx_enqueue_script('', URL.'js/ICanHaz.min.js', '/report', 'custom');
fx_enqueue_script('', URL.'js/ClassesV2.js', '/report', 'custom');

/**
 * Enqueue Flexiweb styles
 */

fx_enqueue_style('', URL.'style/reset.css', '/');
fx_enqueue_style('', URL.'style/jquery-ui.custom.css', '/');
fx_enqueue_style('', URL.'style/jquery.miniColors.css', '/');
fx_enqueue_style('', URL.'style/flexiweb.css', '/');

fx_enqueue_style( '', URL.'style/codemirror.css', array('/app_editor/app_pages', '/component'));
fx_enqueue_style( '',  URL.'style/codemirror.simple-hint.css',  array('/app_editor/app_pages', '/component'));