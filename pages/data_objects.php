<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_metabox(array('body' => array('content' => new FX_Error('empty_data_schema', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));
		return;
	}

	if (!is_numeric($_SESSION['current_set'])) {
		fx_show_metabox(array('body' => array('content' => new FX_Error('empty_data_set', _('Please select Data Set'))), 'footer' => array('hidden' => true)));
		return;
	}

	$current_object = get_current_object();		

	$options = array('fields' => array('object_id','created','modified','name','display_name','type','description'),
					 'buttons' => array('update','replicate','reset','cancel','delete','permalink'));
	
	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);
	
	if (!is_fx_error($current_object)) {
		$mb_data = array('header' => array('content' => 'Links Explorer'),
						 'body' => array('content' => links_explorer($current_object['object_type_id'], $current_object['object_id'], $_SESSION['current_schema'], 'nonsystem')),
						 'footer' => array('hidden' => true));
								
		fx_show_metabox($mb_data);			
	}
	
	$options = array('schema_id' => $_SESSION['current_schema'],
					 'set_id' => $_SESSION['current_set'],
					 'filter_system' => true,
					 'fields' => array('object_id','display_name','name'),
					 'actions' => array('view','edit','delete'),
					 'bulk_actions' => true);		

	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);		
