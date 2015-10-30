<?php
/*
Name: Add Object
Version: 0.1
Description: Creates an object based on the passed parameters
Author: Flexiweb
API Method: POST
API Endpoint: object
*/

class Task_Add_Object extends FX_Task
{
	function action($args, $result)
	{
		return add_object($args);
	}
	
	function transform_result($args, $result)
	{		
		if (is_fx_error($result)) {
			return $result;
		}	
		return get_object($args['object_type_id'], $result);
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

        <label for="set_id">Set ID:</label>
        <select class="task-param" name="set_id" id="set_id">
            <option value="">Please Select</option>
            <?php show_select_options(get_objects_by_type(get_type_id_by_name(0,'data_set'),$_SESSION['current_schema']),'object_id','display_name', $object_type_id); ?>
        </select>

        <label for="object_type_id">Object Type:</label>
        <select class="task-param type-selector" name="object_type_id" id="object_type_id">
            <option value="">Please Select</option>
            <?php show_select_options(get_schema_types($_SESSION['current_schema'], 'none'), 'object_type_id', 'display_name', $object_type_id); ?>
        </select>

        <label for="name">Name:</label>
        <input class="task-param" id="name" name="name" type="text" value="<?php echo $name; ?>"/>

        <label for="display_name">Display Name:</label>
        <input class="task-param" id="display_name" name="display_name" type="text" value="<?php echo $name; ?>"/>

        <?php		
	}
}

?>