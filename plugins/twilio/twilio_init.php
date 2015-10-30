<?php

	function _twilio_insert_types()
	{
		$type_msg_in = get_type_id_by_name(0, 'twilio_msg_in');
		
		if (!$type_msg_in) {
	
			$fields = array(array('name'=>'message', 'caption'=>'Message', 'description'=>'Message text', 'type'=>'TEXT'),
							array('name'=>'first_word', 'caption'=>'First Word', 'description'=>'First Word of text message (useful for sorting texts)', 'type'=>'VARCHAR'),
							array('name'=>'phone_from', 'caption'=>'Phone Number', 'description'=>'Phone Number of person who sent message', 'type'=>'VARCHAR'),
							array('name'=>'phone_to', 'caption'=>'Phone Number', 'description'=>'Phone Number of person who received message', 'type'=>'VARCHAR')
							
							);
	
			$type_array = array('system' => 1, 
								'name' => 'twilio_msg_in',
								'display_name' => 'Twilio Incoming Message',
								'description' => 'This type was inserted by Flexiweb Twilio Plugin',
								'prefix' => 'tw_',
								'fields' => $fields);
	
			$result = add_type($type_array);
	
			if (is_fx_error($result)) {
				return $result;
			}
		}
		
		$type_msg_out = get_type_id_by_name(0, 'twilio_msg_out');
	
		if (!$type_msg_out) {
	
			$enum_numbers = add_enum_type(array('name'=>'Twilio Numbers', 'system'=>1));
	
			$fields = array(array('name'=>'message', 'caption'=>'Message', 'description'=>'Message text', 'type'=>'TEXT'),
			  array('name'=>'phone_number', 'caption'=>'Phone Number From', 'description'=>'Phone Number of person who sent message', 'type'=>$enum_numbers),
			  array('name'=>'phone_number_to', 'caption'=>'Phone Number To', 'description'=>'Phone Number of person who get message', 'type'=>'VARCHAR'),
			  array('name'=>'status', 'caption'=>'Status', 'description'=>'Message status', 'type'=>'VARCHAR'));
	
			$type_array = array('system' => 1,
			   'name' => 'twilio_msg_out',
			   'display_name' => 'Twilio Outgoing Message',
			   'description' => 'This type was inserted by Flexiweb Twilio Plugin',
			   'prefix' => 'tw_',
			   'fields' => $fields);
	
			$result = add_type($type_array);
			
			if (is_fx_error($result)) {
				return $result;
			}
		}
		
		return true;
	}

	function _check_twilio_types($result)
	{
		if (is_fx_error($result)) {
			$out = '<div class="error">'.$result->get_error_message().'</div>';
		}
		
        $type_msg_in = get_type_id_by_name(0, 'twilio_msg_in');
		$type_msg_out = get_type_id_by_name(0, 'twilio_msg_out');
		
		if ((int)$type_msg_in && (int)$type_msg_in) {
			$out .= '<div class="info">Twilio types already exist</div>';
		}
		else {
			$out .= '
			<form method="post" action="">
			<input type="hidden" name="init_types">
			<input type="submit" value="Create Twilio types" class="button green">
			</form>';
		}
		
		return $out;
	}
	
	$IOResult = false;

	if (isset($_POST['init_types'])) {
		$IOResult = _twilio_insert_types();
	}
	
	$object_type_id = get_type_id_by_name(0, 'twilio_msg_in');
	$current_object = get_current_object();
	
	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => _check_twilio_types($IOResult)),
					 'footer' => array('hidden' => true));
	
	fx_show_metabox($mb_data);
