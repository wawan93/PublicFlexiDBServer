<script type="text/javascript">
    sendMessage = function(obj_type_id, obj_id) {
        var fx_dir = window.flexiweb.site_url;
        $.ajax({
            'url': fx_dir + 'ajax/call_fx_func.php',
            'type': 'post',
            'dataType': 'json',
            'data': 'function=twilio_send_message&object_type_id='+obj_type_id+'&object_id='+obj_id,
            success: function(resp) {
                if (resp.status == 'success') {
                    window.location = location.href;
                } else if (resp.status == 'error') {
                    //
                }
            }
        })
    }
</script>

<?php
    function _ctrl_status($status) {
        if (!$status) {
            $object_type_id = $_GET['object_type_id'];
            $object_id = $_GET['object_id'];
            $out = '
                <input type="button" class="button" value="Send message" onclick="sendMessage('.$object_type_id.', '.$object_id.')">
            ';
        } else {
            $out = 'Already sended';
        }
        return $out;
    }

		$object_type_id = get_type_id_by_name(0, 'twilio_msg_out');
		$current_object = get_current_object();		


        $custom_fields = array();
        if (!is_fx_error($current_object)) {
            $custom_fields['status']['control'] = _ctrl_status($current_object['status']);
        }

		$options = array('fields' => array('object_id','created','modified','display_name'),
						 'custom_fields' => $custom_fields,
						 'buttons' => array('update', 'reset','cancel','delete'));

		$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
						 'body' => array('function' => 'object_form', 'args' => array($options)),
					 	 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);
	
        $options = array('set_id' => 0,
						 'filter_system' => false,
						 'read_only' => false,
						 'object_type_id' => $object_type_id,
                         'fields' => array('object_id', 'display_name', 'name'),
                         'actions' => array('edit', 'view', 'remove'));
	
		$mb_data = array('header' => array('hidden' => true),
						 'body' => array('content' => object_explorer($options)),
						 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);

?>