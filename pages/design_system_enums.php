<?php
	
	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}
	
	$enum_type_id = isset($_REQUEST['enum_type_id']) ? $_REQUEST['enum_type_id'] : 0;

	$enum = get_enum_type($enum_type_id, false);
	
	$header_suffix = !is_fx_error($enum) ? $enum['name'].' <font color="#FF0000">(system enum)</font>' : '';

	fx_show_metabox(array('header' => array('suffix' => $header_suffix), 'body' => array('function' => 'enum_form'), 'footer' => array('hidden' => true)));			

	$options = array('table' => DB_TABLE_PREFIX.'enum_type_tbl',
					 'schema_id' => 0,
					 'fields' => array('name'),
					 'actions' => array('view','edit','delete'));
	
	$explorer = table_explorer($options);

	if (is_fx_error($explorer)) {
		fx_show_metabox(array('header' => array('hidden' => true),
							  'body' => array('content' => new FX_Error('table_explorer', $explorer -> get_error_message())), 
							  'footer' => array('hidden' => true)));	
	}
	else {
		$add_enum_btn = "\n\t\t\t<div class=\"button green\" onclick=\"add_enum(".$_SESSION['current_schema'].", 1)\">"._('Add New Enum')."</div>\n";			 
		fx_show_metabox(array('header' => array('hidden' => true),'body' => array('content' => $add_enum_btn.$explorer), 'footer' => array('hidden' => true)));						 		
	}