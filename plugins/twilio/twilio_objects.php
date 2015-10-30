<?php
	if($_SESSION['current_schema'])
	{
		$object_type_id = get_type_id_by_name(0, 'data_set');
		$current_object = get_current_object();		
					 
		$options = array('fields' => array('object_id','created','modified','display_name'),
						 'buttons' => array('reset','cancel','delete'));
		
		$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
						 'body' => array('function' => 'object_form_new', 'args' => array($options)),
					 	 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);
	
        $options = array('set_id' => 0,
						 'filter_system' => false,
						 'read_only' => true,
						 'schema_id' => $_SESSION['current_schema'],
						 'object_type_id' => array(get_type_id_by_name(0, 'data_form')),
                         'fields' => array('object_id', 'display_name', 'name'),
                         'actions' => array('view', 'remove'));	
	
		$mb_data = array('header' => array('hidden' => true),
						 'body' => array('content' => object_explorer($options)),
						 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);
	}
	else
	{
		fx_show_metabox(array('body' => array('content' => new FX_Error('show_templates', 'Please select Data Schema.')), 'footer' => array('hidden' => true)));		
	}
?>