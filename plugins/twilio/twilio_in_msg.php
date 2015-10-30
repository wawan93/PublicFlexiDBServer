<?php
	function _check_incoming_messages()
	{
        $total_num = $error_num = 0;
		if (isset($_POST['check_incoming_messages'])) {
            $object_type_id = get_type_id_by_name(0, 'twilio_msg_in');


            $twilio_settings = get_fx_option('twilio_settings', array());

            $client = new Services_Twilio($twilio_settings['twilio_sid'], $twilio_settings['twilio_token']);

            $result = $client->account->incoming_phone_numbers;
            foreach ($result as $number) {
                $tw_number = $client->account->incoming_phone_numbers->get($number->sid)->phone_number;
                $last_date = $twilio_settings['last_incoming_date_sent'][$tw_number]['date'] ? $twilio_settings['last_incoming_date_sent'][$tw_number]['date'] : 0;

                $messages = $client->account->messages->getIterator(0, 50, array('To' => $tw_number, 'DateSent>' => $last_date));
                $first = true;

                foreach ($messages as $message) {
                    if ($message->sid == $twilio_settings['last_incoming_date_sent'][$tw_number]['sid']) {
                        break;
                    }

                    $words = explode(' ', $message->body);
                    if ($message->direction == 'inbound') {
                        $total_num++;
                        $result = add_object(array(
                            'schema_id' => 0,
                            'object_type_id' => $object_type_id,
                            'name' => $message->sid,
                            'first_word' => $words[0],
                            'message' => $message->body,
                            'phone_from' => $message->from,
                            'phone_to' => $message->to
                        ));
                        if (!is_fx_error($result)) {
                            if ($first) {
                                $twilio_settings_temp_date = $message->date_created;
                                $twilio_settings_temp_sid = $message->sid;
                                $first = false;
                            }
                        } else {
                            $error_num++;
                        }
                    }
                }
                if ($twilio_settings_temp_date) $twilio_settings['last_incoming_date_sent'][$tw_number]['date'] = $twilio_settings_temp_date;
                if ($twilio_settings_temp_sid)  $twilio_settings['last_incoming_date_sent'][$tw_number]['sid'] = $twilio_settings_temp_sid;
            }

            update_fx_option('twilio_settings', $twilio_settings);
		}
		$out = '';
        if ($error_num>0) {
            $out .= '<div class="error">'.$error_num.' from total '.$total_num.' messages has not been processed</div>';
        }
		$out .= '<form method="post"><input type="hidden" name="check_incoming_messages"><input type="submit" class="button blue" value="'._('Check for new messages').'"></form>';
		
		return $out;
	}


	{
		$object_type_id = get_type_id_by_name(0, 'twilio_msg_in');
		$current_object = get_current_object();		
					 
		$options = array('fields' => array('object_id','created','modified','display_name'),
						 'buttons' => array('reset','cancel','delete'));
		
		$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
						 'body' => array('function' => 'object_form_new', 'args' => array($options)),
					 	 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);

		$mb_data = array('header' => array('hidden' => true),
						 'body' => array('content' => _check_incoming_messages()),
						 'footer' => array('hidden' => true));
	
		fx_show_metabox($mb_data);
	
        $options = array('set_id' => 0,
						 'filter_system' => false,
						 'read_only' => true,
						 'object_type_id' => $object_type_id,
                         'fields' => array('object_id', 'name', 'phone_from', 'phone_to', 'message'),
                         'actions' => array('view', 'remove'));	
	
		$mb_data = array('header' => array('hidden' => true),
						 'body' => array('content' => object_explorer($options)),
						 'footer' => array('hidden' => true));


		fx_show_metabox($mb_data);
	}
?>