<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_metabox(array('body' => array('content' => new FX_Error('empty_data_schema', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));
		return;
	}
?>

<script type="text/javascript">
    //Temporary here. Later it'll changed and moved
    function setAsCurrent(){
        localStorage.setItem("app",unescape($("#field-code").val()));
    }
    function loadFromCurrent(){
        $("#field-code").val(escape(localStorage.getItem("app")));
    }
</script>

<?php
	$object_type_id = TYPE_APP_DATA;
	$current_object = get_current_object();		

	$options = array('object_type_id' => $object_type_id,
					 'fields' => array('object_id','created','modified','name','display_name'),
					 'buttons' => array('update','reset','cancel','delete'));
	
	if (!is_fx_error($current_object)) {
		$options['custom_buttons'] = array('<a class="button blue" href="'.URL.'app_editor/app_pages?id='.$current_object['object_id'].'">Edit Application</a>');
	}
	
	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));
	
	fx_show_metabox($mb_data);
	
	if(is_fx_error($current_object)) {
		$options = array('set_id' => 0,
						 'object_type_id' => $object_type_id,
						 'fields' => array('object_id','created','modified','display_name','description'),
						 'actions' => array('view','edit','delete'));		
	
		$mb_data = array('header' => array('hidden' => true),
						 'body' => array('content' => object_explorer($options)),
						 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);		
	}
	else {			
		$mb_data = array('header' => array('content' => 'Links Explorer'),
						 'body' => array('content' => links_explorer(TYPE_APP_DATA, $current_object['object_id'], $_SESSION['current_schema'])),
						 'footer' => array('hidden' => true));
								
		fx_show_metabox($mb_data);	
	}
?>