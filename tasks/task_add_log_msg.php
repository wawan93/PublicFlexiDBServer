<?php
/*
Name: Add Log Message
Version: 0.1
Description: Add message to Fx log with the specified code and text
Author: Flexiweb
*/

class Task_Add_Log_Message extends FX_Task
{
	function action($args, $result)
	{
		$code = normalize_string($args['code']);
		$code = $code ? $code : __METHOD__;
		
		if (!$args['message']) {
			$args['message'] = is_fx_error($result) ?  'success' : 'failure';
		}
		
		add_log_message($code, $args['message']);
	}

	function form($args)
	{
		extract($args);
		?>

        <label for="code">Code:</label>
        <input class="task-param" id="code" name="code" type="text" value="<?php echo $code; ?>"/>
        <label for="message">Message:</label>
        <textarea class="task-param" id="message" name="message" rows="5"><?php echo $message; ?></textarea>
 
    	<?php
	}
}
?>