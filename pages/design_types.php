<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}

	$object_type_id = $_REQUEST['object_type_id'] ? $_REQUEST['object_type_id'] : 0;		

	$type = get_type($object_type_id, 'base');
	$header_suffix = '';
	
	if ($type && !is_fx_error($type)) {
		$header_suffix  = ' - '.$type['display_name'].($type['system'] ? ' <font color="#FF0000">(system type)</font>' : '');
	}

	fx_show_metabox(array('header' => array('suffix' => $header_suffix), 'body' => array('function' => 'type_form'), 'footer' => array('hidden' => true)));		
	
	$options = array('table' => DB_TABLE_PREFIX.'object_type_tbl',
					 'schema_id' => $_SESSION['current_schema'],
					 'filter_system' => true,
					 'fields' => array('name', 'display_name', 'description'),
					 'actions' => array('view','edit'));	
	
	$explorer = table_explorer($options);

	if (is_fx_error($explorer)) {
		fx_show_metabox(array('header' => array('hidden' => true),
							  'body' => array('content' => new FX_Error('table_explorer', $explorer -> get_error_message())), 
							  'footer' => array('hidden' => true)));	
	}
	else {
		$add_type_btn = "\n\t\t\t<div class=\"button green\" onclick=\"add_type(".$_SESSION['current_schema'].")\">Add New Type</div>\n";			 
		fx_show_metabox(array('header' => array('hidden' => true),'body' => array('content' => $add_type_btn.$explorer), 'footer' => array('hidden' => true)));						 		
	}