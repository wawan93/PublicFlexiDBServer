<?php
/*
Name: Send Email
Version: 0.1
Description: Just send a message to the specified email address 
Author: Flexiweb
API Method: PUT
API Endpoint: /email
*/

class Task_Send_Email extends FX_Task
{
	function action($args, $result)
	{
		extract($args);

		if ($file)
		{
			if (fx_mail_attachment($to, $subject, $message, $from, $file)) {
				return true;
			}
			else {
				return new FX_Error(__CLASS__, 'Unable to send email');
			}	
		}
		else
		{
			$headers = array();
	
			if (!$subject) $subject = 'no subject';
			
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "Content-type: text/html; charset=utf-8";
			if ($from) $headers[] = "From: ".$from;
			if ($reply_to) $headers[] = "Reply-To: ".$reply_to;
			$headers[] = "Subject: ".$subject;
			$headers[] = "X-Mailer: PHP/".phpversion();
		
			if (mail($to, $subject, $message, implode("\r\n", $headers))) {
				return true;
			}
			else {
				return new FX_Error(__CLASS__, 'Unable to send email');
			}
		}
	}

	function form($reaction_args)
	{
		extract($args);
	  	?>

        <label for="to">To:</label>
        <input class="task-param" id="to" name="to" type="text" value="<?php echo $to; ?>"/>

        <label for="from">From:</label>
        <input class="task-param" id="from" name="from" type="text" value="<?php echo $from; ?>"/>

        <label for="reply_to">Reply To:</label>
        <input class="task-param" id="reply_to" name="reply_to" type="text" value="<?php echo $reply_to; ?>"/>

        <label for="subject">Subject:</label>
        <input class="task-param" id="subject" name="subject" type="subject" value="<?php echo $subject; ?>"/>

        <label for="message">Message:</label>
        <textarea class="task-param" id="message" name="message" rows="5"><?php echo $message; ?></textarea>
        
        <label for="file">File:</label>
        <input class="task-param" id="file" name="file" type="text" value="<?php echo $file; ?>"/>

    	<?php	
	}
}

?>