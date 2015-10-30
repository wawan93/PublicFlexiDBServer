<?php

	function _mb_export_content()
	{
		$out = '
		<h1>'.get_object_field(TYPE_DATA_SCHEMA, $_SESSION['current_schema'], 'display_name').'</h1>
		<form action="'.IE_PLUGIN_URL.'get_export_archive.php" method="get" target="_blank">
			<input type="hidden" name="schema_id" value="'.$_SESSION['current_schema'].'"/>
			<div style="max-height:350px;overflow:auto;">
			<table class="profileTable">
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			<tr>
			    <th><input type="checkbox" id="export_select_all"></th>
				<td align="center"><b>'.('Warning').':</b> '.('you have chosen to not export all data sets. If you have links between data sets then they will be invalid if the data set they link to is not exported').'</div></td>
			</tr>
			<tr>
				<td colspan="2"><hr></td>
			</tr>';
			
		foreach (get_objects_by_type(TYPE_DATA_SET, $_SESSION['current_schema']) as $set_id => $data) {
			$checked = isset($_POST['sets'][$set_id]) ? ' checked="checked"' : '';
			$out .= '
				<tr>
					<th><input type="checkbox" name="sets['.$set_id.']" id="data_set_'.$set_id.'"'.$checked.'></th>
					<td><label for="data_set_'.$set_id.'">'.$data['display_name'].'</label></td>
				</tr>';	
		}
            
		$out .= '
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			</table>
			</div>
			<input type="submit" class="button green" value="'._('Export').'"/>
		</form>';
	
		return $out;
	}

	if ($_SESSION['current_schema']) {
		$mb_data = array('body' => array('content' => _mb_export_content()),
					 	 'footer' => array('hidden' => true));

		fx_show_metabox($mb_data);
	}
	else {
		fx_show_metabox(array('body' => array('content' => new FX_Error('import_export', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));		
	}
?>