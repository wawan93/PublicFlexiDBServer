<?php

	$object_type_id = TYPE_DFX_USER;
	$selected_user = get_current_object();

	function _echo_pre_form() {
		echo '<div class="msg-info">WARNING: differentiation of access rights is not available in FlexiDB BETA</div>';
	}
	
	add_action('object_form_before', '_echo_pre_form');

	if (!is_fx_error($selected_user)) {
		$options = array('object_type_id' => $object_type_id,
						 'fields' => array('object_id','created','modified','name','display_name'),
						 'buttons' => array('update','reset','cancel', 'delete'));		
	}

	$mb_data = array('header' => array('suffix' => !is_fx_error($selected_user) ? ' - '.$selected_user['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);

	$options = array('set_id' => 0,
					 'object_type_id' => $object_type_id,
					 'fields' => array('object_id','display_name','name'),
					 'actions' => array('view','edit'));

	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);