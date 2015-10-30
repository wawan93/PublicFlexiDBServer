<?php
/*
Name: Get Object Field
Version: 0.1
Description: Get object field value
Author: Flexiweb
API Method: GET
API Endpoint: \object\field  
*/

class Task_Get_Object_Field extends FX_Task
{
	function action($args, $result)
	{
		return get_object_field($args['object_type_id'], $args['object_id'], $args['field'], false);
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

        <?php
	}
}

?>