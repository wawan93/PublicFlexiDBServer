<?php
/*
Name: Update Object Field
Version: 0.1
Description: Allows to change the value of one of the object fields.
Author: Flexiweb
API Method: PUT
API Endpoint: \object\field  
*/

class Task_Update_Object_Field extends FX_Task
{
	function action($args, $result)
	{
		extract($args);
		return update_object_field($object_type_id, $object_id, $field, $value);
	}

	function params()
	{
		return array('result'=>'Result');
	}

	function form($args)
	{
		extract($args);
	  	?>
        <label for="object_type_id">Object Type ID:</label>
        <input class="task-param" id="object_type_id" name="object_type_id" type="text" value="<?php echo $object_type_id; ?>"/>
        
        <label for="object_id">Object ID:</label>
        <input class="task-param" id="object_id" name="object_id" type="text" value="<?php echo $object_id; ?>"/>

        <label for="field">Field Name:</label>
        <input class="task-param" id="field" name="field" type="text" value="<?php echo $field; ?>"/>

        <label for="value">Field Name:</label>
        <input class="task-param" id="value" name="value" type="text" value="<?php echo $value; ?>"/>
        <?php
	}
}

?>