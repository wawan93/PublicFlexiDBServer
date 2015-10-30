<?php
/*
Name: Check Twilio Messages
Version: 0.1
Description: Check Twilio Incoming Messages
Author: Flexiweb
*/

class Task_Check_Twilio_Messages_In extends FX_Task
{
	function action($args, $result)
	{
		$twilio_settings = get_fx_option('twilio_settings');
        $object_type_id = get_type_id_by_name(0, 'twilio_msg_in');
	
		if(!$account_sid = $twilio_settings['twilio_sid']) return new FX_Error(__CLASS__, 'Please set Twilio SID.');
		if(!$auth_token = $twilio_settings['twilio_token']) return new FX_Error(__CLASS__, 'Please set Twilio Token.');
	
		$client = new Services_Twilio($account_sid, $auth_token);

		try
		{
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
//                    echo '<b>'.$last_date.'</b><br>'.$message->sid.'________'.$twilio_settings['last_incoming_date_sent'][$tw_number]['sid'].'<br>';
//                    echo $message->sid.'________'.$twilio_settings['last_incoming_date_sent'][$tw_number]['sid'].'<br>';
                    $words = explode(' ', $message->body);
                    if ($message->direction == 'inbound') {

                        $result = add_object(array(
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
                        }
                    }
                }
                if ($twilio_settings_temp_date) $twilio_settings['last_incoming_date_sent'][$tw_number]['date'] = $twilio_settings_temp_date;
                if ($twilio_settings_temp_sid)  $twilio_settings['last_incoming_date_sent'][$tw_number]['sid'] = $twilio_settings_temp_sid;
            }
            update_fx_option('twilio_settings', $twilio_settings);
		} 
		catch (Exception $e)
		{
			return new FX_Error(__CLASS__, $e->getMessage());
		}
	}

	function form($args)
	{

	}
}

?>