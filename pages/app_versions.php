<?php
	if($_SESSION['current_schema'])
	{
		$current_object = get_current_object();		
	
		$options = array('object_type_id' => TYPE_APP_DATA,
						 'fields' => array('object_id','created','modified','name','display_name'),
						 'buttons' => array('update','reset','cancel','delete'));
		
		$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
						 'body' => array('function' => 'object_form', 'args' => array($options)),
					 	 'footer' => array('hidden' => true));
		
		fx_show_metabox($mb_data);
		
			
		$options = array('set_id' => 0,
						 'schema_id' => $_SESSION['current_schema'],
						 'object_type_id' => TYPE_APP_DATA,
						 'fields' => array('object_id','created','modified','display_name'),
						 'actions' => array('view','edit'));
	
		$mb_data = array('header' => array('hidden' => true),
						 'body' => array('content' => object_explorer($options)),
						 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);
	}
	else {
		fx_show_metabox(array('body' => array('content' => new FX_Error('show_version_objects', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));		
	}