<?php

	function _clear_log_ctrl()
	{
		$out = '';
		
		if (isset($_POST['clear_log'])) {
			global $fx_db;
			
			$pdo = $fx_db->prepare("TRUNCATE ".DB_TABLE_PREFIX."object_type_".TYPE_LOG_MSG);
	
			if (!$pdo->execute()) {
				$out.= '<div class="error">'._('Unable to clear message log').'</div>';
			}
			else {
				fx_redirect(replace_url_params(array('p'=>'', 'ipp'=>'')));
			}
		}
		
		$out.='
		<form method="post">
			<input type="hidden" name="clear_log">
			<input type="button" class="button red" value="Clear Log" onclick="if(confirm(\''._('Are you sure you want to delete all messages?').'\'))submit();else return;">
		</form>';
		
		return $out;
	}

	$current_object = get_current_object();		
				 
	$options = array('fields' => array('object_id','created'),
					 'mode' => 'view',
					 'buttons' => array('cancel','delete'));
	
	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - Message #'.$current_object['object_id'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));
	
	fx_show_metabox($mb_data);
	
	$options = array('set_id' => 0,
					 'filter_system' => false,
					 'object_type_id' => TYPE_LOG_MSG,
					 'fields' => array('object_id', 'created', 'code', 'msg'),
					 'read_only' => true,
					 'order_by' => 'object_id',
					 'order' => 'DESC',
					 'bulk_actiona' => false,
					 'actions' => array('edit', 'delete'));	
	
	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);
	
	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => _clear_log_ctrl()),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);