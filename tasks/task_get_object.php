<?php
/*
Name: Get Object
Version: 0.1
Description: Get object
Author: Flexiweb
API Method: GET
API Endpoint: \object
*/

class Task_Get_Object extends FX_Task
{
	function action($args, $result)
	{
		extract($args);
		return get_object($object_type_id, $object_id);
	}

	function params()
	{
		global $OBJECT_BASE_FIELDS;

		$params = array('result' => 'Result');

		foreach ($OBJECT_BASE_FIELDS as $key => $value) {
			$params[$key] = $value['caption'] ? $value['caption'] : $value['name'];
		}

		return $params;
	}

	function form($args)
	{
		extract($args);
	  	?>
        <label for="object_type_id">Object Type:</label>
        <select class="task-param type-selector" name="object_type_id" id="object_type_id">
            <option value="">Please Select</option>
            <?php show_select_options(get_schema_types($_SESSION['current_schema'], 'none'), 'object_type_id', 'display_name', $object_type_id); ?>
        </select>
        <label for="object_id">Object ID:</label>
        <input class="task-param" id="object_id" name="object_id" type="text" value="<?php echo $object_id; ?>"/>
        <?php		
	}
}

?>