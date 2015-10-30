<?php

	$data = array('type'=>'app', 'data'=>'http://flexidev.co.uk/flexiweb/uploads/10014/6/10014-6-image.png');

	$object_type_id = TYPE_IBEACON_UUID;
	$current_object = get_current_object();

	$options = array('fields' => array('object_id','created','modified','display_name','uuid','description'),
					 'buttons' => array('reset','cancel','delete'));
	
	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));
	
	fx_show_metabox($mb_data);
	
	$options = array('set_id' => 0,
					 'filter_system' => false,
					 'object_type_id' => $object_type_id,
					 'fields' => array('object_id', 'display_name', 'uuid'),
					 'actions' => array('view', 'remove'));	
	
	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));
	
	fx_show_metabox($mb_data);
