<?php
/*
Name: Delete Object
Version: 0.1
Description: Deletes the object and all its fields associated with it from the database.
Author: Flexiweb
API Method: DELETE
API Endpoint: object
*/

class Task_Delete_Object extends FX_Task
{
	function action($args, $result)
	{
		extract($args);
		return delete_object($object_type_id, $object_id);
	}
	
	function params()
	{
		return array('result'=>'Result');
	}

	function form($args)
	{
		extract($args);
	  	?>

        <label for="object_type_id">Object Type:</label>
        <select class="task-param" name="object_type_id" id="object_type_id">
            <option value="">Please Select</option>
            <?php show_select_options(get_schema_types($_SESSION['current_schema'], 'none'), 'object_type_id', 'display_name', $object_type_id); ?>
        </select>

        <label for="object_id">Object ID:</label>
        <input class="task-param" id="object_id" name="object_id" type="text" value="<?php echo $object_id; ?>"/>

        <?php		
	}
}

?>