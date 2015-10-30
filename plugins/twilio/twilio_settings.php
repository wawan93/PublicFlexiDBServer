<?php


function _show_twilio_settings_metabox()
{
	$type_msg_out = get_type_id_by_name(0, 'twilio_msg_out');
	$enum_type = (get_type($type_msg_out));
	$enum_type_id = $enum_type['fields']['phone_number']['type'];

	$IOResult = false;

	$twilio_settings = get_fx_option('twilio_settings', array());

    if(isset($_POST['set_twilio_settings'])) {
		$twilio_settings['twilio_sid'] = $_POST['twilio_sid'];
		$twilio_settings['twilio_token'] = $_POST['twilio_token'];
		$twilio_settings['twilio_numbers'] = array();
        $tw_numbers = array();

		if($twilio_settings['twilio_sid'] && $twilio_settings['twilio_token'])
		{
			$client = new Services_Twilio($twilio_settings['twilio_sid'], $twilio_settings['twilio_token']);

			try
			{
				$result = $client->account->incoming_phone_numbers;
				foreach ($result as $number) {
                    $tw_number = $client->account->incoming_phone_numbers->get($number->sid)->phone_number;
					$twilio_settings['twilio_numbers'][] = $tw_number;
                    $tw_numbers[$tw_number] = $tw_number;
                }

				if(!$twilio_settings['twilio_numbers']) {
                    $IOResult = new FX_Error('twilio_validation','ERROR: Unable to get Twilio numbers');
                } else {
                    $enum_array = array('enum_type_id' => $enum_type_id, 'fields' => $tw_numbers);
                    update_enum_type($enum_array);
                }
			}
			catch (Exception $e)
			{
                echo $e->getMessage();
				$IOResult = new FX_Error('twilio_validation','ERROR: Unable to validate your Twilio account');
			}
		}
	
		update_fx_option('twilio_settings', $twilio_settings);
	
		if (!is_fx_error($IOResult)) $IOResult = 'Twilio settings successfully updated';
	}

	if (isset($_POST['set_reply_settings'])) {
		$current_number = $_POST['twilio_number'];
		$twilio_settings['reply'][$current_number]['enable'] = $_POST['reply_enable'];
		$twilio_settings['reply'][$current_number]['text'] = $_POST['reply_text'];
	
		update_fx_option('twilio_settings', $twilio_settings);
	}
	
		if($IOResult)
		{
			if (is_fx_error($IOResult))
			{
				$errors = $IOResult->get_error_messages();
				for ($i=0; $i<count($errors); $i++) echo '<div class="msg-error">'.$errors[$i].'</div>';
			}
			else echo '<center><div class="msg-info">'.$IOResult.'</div></center>';
		}
		?>
		<script type="text/javascript">
			var twilioReplySettings = JSON.parse('<?php echo json_encode($twilio_settings['reply']);?>')
			$(function() {
				$('#twilio_number').on('change', function() {
					var val = $(this).val()
					if (typeof twilioReplySettings[val] !== 'undefined') {
						$('#reply_text').val(twilioReplySettings[val].text)
						if (twilioReplySettings[val].enable) {
							$('#reply_enable').prop('checked', true)
						} else {
							$('#reply_enable').prop('checked', false)
						}
					} else {
						$('#reply_enable').prop('checked', false)
						$('#reply_text').val('')
					}
				})
			})
		</script>
    
        <h1>Twilio Cridentials</h1>
        
        <form action="" method="post">
            <input type="hidden" name="set_twilio_settings"/>
            <table class="profileTable">
            <tr>
                <th></th>
                <td class="prompt">Enter your Twilio cridentials to get an access to sms and call services</td>
            </tr>
            <tr>
                <th><label for="twilio_sid">Account SID</label></th>
                <td><input type="text" name="twilio_sid" id="twilio_sid" size="40" value="<?php echo $twilio_settings['twilio_sid']; ?>" /></td>
            </tr>
            <tr>
                <th><label for="twilio_token">Auth Token</label></th>
                <td><input type="text" name="twilio_token" id="twilio_token" size="40" value="<?php echo $twilio_settings['twilio_token']; ?>" /></td>
            </tr>
            <?php if(($twilio_settings['twilio_sid'] || $twilio_settings['twilio_token']) && !$twilio_settings['twilio_numbers']): ?>
            <tr>
                <th></th>
                <td><font color="#FF0000">Invalid Twilio cridentials or there are no available numbers</font></td>
            </tr>    
            <?php endif;?>
            <?php if(!$twilio_settings['twilio_numbers']): ?>
            <tr>
                <th></th>
                <td><a href="https://www.twilio.com/try-twilio" target="_blank">Have no Twilio account?</a></td>
            </tr> 
            <?php endif;?>
            <tr>
                <th></th>
                <td><input type="submit" class="button green" value="Save"/></td>
            </tr>
            </table>   
        </form>

        <hr/>
        
        <?php if ($twilio_settings['twilio_numbers']): ?>
        
        <h1>Twilio Reply Settings</h1>
        <form action="" method="post">
            <input type="hidden" name="set_reply_settings">
            <table class="profileTable">
                <tr>
                    <th></th>
                    <td class="prompt">
                        To make reply possible, need to set <br>
                        <b>http://flexidev.co.uk/flexiweb/plugins/twilio/twilio_reply.php</b><br>
                        as Messaging Request URL for required phone numbers
                        <b><a href="https://www.twilio.com/user/account/phone-numbers/incoming">here</a></b>
                    </td>
                </tr>
                <tr>
                    <th><label for="twilio_number">Number</label></th>
                    <td>
                        <select name="twilio_number" id="twilio_number">
                            <?php
                            $current_number = $current_number ? $current_number : $twilio_settings['twilio_numbers'][0];
                            foreach ($twilio_settings['twilio_numbers'] as $tw_num) {
                                $selected = $_POST['twilio_number'] == $tw_num ? 'selected' : '';
                                echo '<option value="'.$tw_num.'" '.$selected.'>'.$tw_num.'</option>';
                            }?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="reply_enable">Enabled</label></th>
                    <?php $checked = $twilio_settings['reply'][$current_number]['enable'] ? ' checked' : ''; ?>
                    <td><input type="checkbox" name="reply_enable" id="reply_enable" <?php echo $checked?>></td>
                </tr>
                <tr>
                    <th><label for="reply_text">Reply Message</label></th>
                    <td><textarea rows="6" cols="30" name="reply_text" id="reply_text"><?php echo $twilio_settings['reply'][$current_number]['text']?></textarea></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" class="button green" value="Save"/></td>
                </tr>
            </table>
        </form>
        
        <?php 
		endif;
	}
	
	$mb_data = array('body' => array('function' => '_show_twilio_settings_metabox'),
					 'footer' => array('hidden' => true));
					 
	fx_show_metabox($mb_data);
?>