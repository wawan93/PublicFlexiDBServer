<?php
/*
Name: Generate Report
Version: 0.1
Description: Generate Report
Author: Flexiweb
*/

class Task_Generate_Report extends FX_Task
{
	function action($args, $result)
	{
		extract($args);

		$obj = new FX_Report($object_id);
		switch($report_type) {
			case 'pdf':
				$report = $obj->fx_get_report_pdf('render');
			break;
			case 'html':
			default:
				$report = $obj->fx_get_report_html();
		}

		return $report;
	}

	function params()
	{
		return array('result'=>'Report HTML');
	}

	function form($reaction_args)
	{
		extract($args);
		
		$twilio_settings = get_fx_option('twilio_settings');
	  	?>

        <label for="object_id">Report Object ID:</label>
        <select class="task-param" name="object_id" id="object_id">
            <option value="">Please Select</option>
            <?php show_select_options(get_objects_by_type(get_type_id_by_name(0, 'report'), $_SESSION['current_schema']), 'object_id', 'display_name'); ?>
        </select>
        <label for="report_type">Report Object ID:</label>
        <select class="task-param" name="report_type" id="report_type">
            <option value="html">HTML</option>
            <option value="pdf">PDF attachment</option>
        </select>

    	<?php	
	}
}

?>