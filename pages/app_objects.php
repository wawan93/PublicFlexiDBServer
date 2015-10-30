<?php

		$object_type_id = TYPE_APPLICATION;
		$current_object = get_current_object();		
	
		$options = array('object_type_id' => $object_type_id,
						 'fields' => array('object_id','created','modified','name','display_name'),
						 'buttons' => array('update','reset','cancel','delete'));

		$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
						 'body' => array('function' => 'object_form', 'args' => array($options)),
					 	 'footer' => array('hidden' => true));
		
		fx_show_metabox($mb_data);
		
			
	if (!is_fx_error($current_object)) {
		$mb_data = array('header' => array('content' => 'Links Explorer'),
						 'body' => array('content' => links_explorer(TYPE_APPLICATION, $current_object['object_id'], $_SESSION['current_schema'], 'system')),
						 'footer' => array('hidden' => true));
								
		fx_show_metabox($mb_data);	
	}
	else {
		$options = array('set_id' => 0,
						 'schema_id' => $_SESSION['current_schema'],
						 'object_type_id' => TYPE_APPLICATION,
						 'fields' => array('object_id','display_name','name'),
						 'actions' => array('view','edit'));
	
		$mb_data = array('header' => array('hidden' => true),
						 'body' => array('content' => object_explorer($options)),
						 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);				
	}		
