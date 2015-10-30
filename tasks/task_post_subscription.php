<?php
/*
Name: Add Subscription
Version: 0.1
Description: Can initiate action on subscription creation event
Author: Flexiweb
API Method: POST
API Endpoint: subscription
*/

class Task_Add_Subscription extends FX_Task
{
	function params()
	{
		return array('result'=>'Subscription ID');
	}

	function form($args)
	{
		extract($args);
	  	?>

        <label for="schema_id">Schema ID:</label>
        <select class="task-param" name="schema_id" id="schema_id">
            <option value="">Please Select</option>
            <?php show_select_options(get_objects_by_type(get_type_id_by_name(0,'data_schema'),0), 'object_id', 'display_name', $_SESSION['current_schema']); ?>
        </select>

        <label for="user_id">Display Name:</label>
        <input class="task-param" id="user_id" name="user_id" type="text" value="<?php echo $user_id; ?>"/>

        <label for="user_api_key">Display Name:</label>
        <input class="task-param" id="user_api_key" name="user_api_key" type="text" value="<?php echo $user_api_key; ?>"/>

        <?php		
	}
}

?>