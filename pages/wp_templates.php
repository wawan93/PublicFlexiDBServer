<?php
	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}
	
	$current_object = get_current_object();		
				 
	$options = array('fields' => array('object_id','created','modified','name','display_name'),
					 'buttons' => array('update','reset','cancel','delete'));
	
	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);

	$options = array('schema_id' => $_SESSION['current_schema'],
					 'set_id' => 0,
					 'filter_system' => false,
					 'object_type_id' => TYPE_WP_TMPL_SIGNUP,
					 'fields' => array('object_id', 'display_name', 'name'),
					 'actions' => array('view', 'edit', 'delete'));	

	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);