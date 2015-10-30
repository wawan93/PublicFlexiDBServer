<?php
	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}
	
	$object_type_id = get_type_id_by_name(0, 'data_set');
	$current_object = get_current_object();		
				 
	$options = array('fields' => array('object_id','created','modified'),
					 'buttons' => array('update','reset','cancel','delete'));
	
	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);

	$options = array('set_id' => 0,
					 'filter_system' => false,
					 'schema_id' => $_SESSION['current_schema'],
					 'object_type_id' => array(get_type_id_by_name(0, 'media_file'),
											   get_type_id_by_name(0, 'media_image')),
					 'fields' => array('object_id', 'modified', 'description'),
					 'actions' => array('edit', 'delete'));	

	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);