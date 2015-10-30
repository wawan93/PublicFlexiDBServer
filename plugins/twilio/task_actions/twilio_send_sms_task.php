<?php
/*
Name: Send SMS
Version: 0.1
Description: Send SMS using Twilio service
Author: Flexiweb
API Method: PUT
API Endpoint: /sms
*/

class Task_Send_Sms extends FX_Task
{
	function action($args, $result)
	{
		extract($args);

		if(!$from) return new FX_Error(__CLASS__, 'Unknown sender number.');
		if(!$to) return new FX_Error(__CLASS__, 'Unknown sender number.');
		if(!$message) return new FX_Error(__CLASS__, 'Empty message.');

		$twilio_settings = get_fx_option('twilio_settings');
	
		if(!$account_sid = $twilio_settings['twilio_sid']) return new FX_Error(__CLASS__, 'Please set Twilio SID.');
		if(!$auth_token = $twilio_settings['twilio_token']) return new FX_Error(__CLASS__, 'Please set Twilio Token.');
	
		$client = new Services_Twilio($account_sid, $auth_token);

		try
		{
			$client->account->messages->sendMessage($from, $to, $message);

			return true;
		} 
		catch (Exception $e)
		{
			return new FX_Error(__CLASS__, $e->getMessage());
		}
	}

	function form($reaction_args)
	{
		extract($args);
		
		$twilio_settings = get_fx_option('twilio_settings');
	  	?>

        <label for="from">From (tel. num.):</label>
        <?php
			if($numbers = $twilio_settings['twilio_numbers']) {
				echo '<select class="task-param" id="from" name="from" >';
				for($i=0; $i<count($numbers); $i++) {
					echo '<option value="'.$numbers[$i].'">'.$numbers[$i].'</option>';
				}
				echo '</select>';
			}
			else {
				echo '<font color="#FF0000">Please Set Up Twilio</font>';		
			}
		?>

        <label for="to">To (tel. num.):</label>
        <input class="task-param" id="to" name="to" type="text" value="<?php echo $to; ?>"/>

        <label for="message">Message:</label><font>1600 chars max</font>
        <textarea class="task-param" id="message" name="message" rows="5"><?php echo $message; ?></textarea>

    	<?php
	}
}

?>