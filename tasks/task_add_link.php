<?php
/*
Name: Add Link
Version: 0.1
Description: 
Author: Flexiweb
API Method: POST
API Endpoint: link
*/

class Task_Add_Link extends FX_Task
{
	function action($args, $result)
	{
		return add_link($args['object_type_1_id'], $args['object_1_id'], $args['object_type_2_id'], $args['object_2_id']);
	}
	
	function params()
	{
		global $OBJECT_BASE_FIELDS;

		$params = array('result' => 'Result');

		return $params;
	}

	function form($args)
	{
		extract($args);
	  	?>

        <label for="object_type_1_id">Object Type 1:</label>
        <select class="task-param" name="object_type_1_id" id="object_type_1_id">
            <option value="">Please Select</option>
            <?php show_select_options(get_schema_types($_SESSION['current_schema'], 'none'), 'object_type_id', 'display_name', $object_type_1_id); ?>
        </select>

        <label for="object_type_2_id">Object Type 2:</label>
        <select class="task-param" name="object_type_2_id" id="object_type_2_id">
            <option value="">Please Select</option>
            <?php show_select_options(get_schema_types($_SESSION['current_schema'], 'none'), 'object_type_id', 'display_name', $object_type_1_id); ?>
        </select>

        <?php		
	}
}

?>