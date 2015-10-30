<?php
	
	//-------------------------------------------------------------------------------
	
	if (isset($_POST['integrity_action'])) {
		switch ($_POST['integrity_action']) {
			case 'check':
				$report = FXIntegrityReport::Create();
				$data = $report->fetchAll();
				update_fx_option('data_integrity_plugin', array('last_checked'=>time(), 'data'=>$data));
			break;
			default:
			
		}
		
		fx_redirect();
	}
	
	//-------------------------------------------------------------------------------

	function print_report_table($data, $input_name, $input_ids)
	{
		if(!count($data)) {
			return '';
		}
	
		$ids = array_keys($data);
		$keys = array_keys($data[$ids[0]]);
		
		$out = '';
		
		$out .= '
		<form method="post">
		<input type="hidden" name="integrity_action" value="delete">
		<input type="hidden" name="case" value="'.$input_name.'">
		<p>&nbsp;</p>
		<input type="submit" class="small button red" value="Remove Selected">
		<p>&nbsp;</p>
		<div class="object-explorer">
			<table>
			<tr>
				<th style="width: 20px;"><input type="checkbox" onchange="check_all_objects(this)" title="Select all"></th>';

		foreach($keys as $key) { 
			$out .= '
			<th>'.$key.'</th>';
		}
		
		$out .= '
		</tr>';
		
		foreach($data as $key => $row)
		{
			$index = '';

			foreach($input_ids as $id_field) {
				$index .= '['.$row[$id_field].']';
			}

			$out .= '
			<tr>
			<td>
				<input class="checks" type="checkbox" name="'.$key.'">
			</td>';

			foreach($row as $value) { 
				$out .= '
				<td>'.$value.'</td>';
			}

			$out .= '
			</tr>';
		}

		$out .= '
			</table>
		</div>
		<p>&nbsp;</p>
		<input type="submit" class="small button red" value="Remove Selected">
		</form>';					

		return $out;
	}

	function _integrity_metabox()
	{	
		$out = '';

		$last_check = get_fx_option('data_integrity_plugin', array('last_checked'=>array(), 'data'=>array()));

		$last_checked = $last_check['last_checked'];
		$data = $last_check['data'];

		$cases = array(
			'types' => _('Lost types'),
			'type_fields' => _('Lost type fields'),
			'objects' => _('Lost objects'),
			'enums' => _('Lost enums'),
			'enum_fields' => _('Lost enum fields'),
			'link_types' => _('Lost link types'),
			'links' => _('Lost links'),
			'types_without_tables' => _('Types without DB tables'),
			'type_fields_without_columns' => _('Type fields without columns in object tables'),
			'columns_without_type_fields' => _('Object table columns without type fields')
		);

		$show_case = isset($_GET['show']) ? $_GET['show'] : false;

		if ($show_case === false) {

			$out .= '
			<p>&nbsp;</p>
			<h2>'._('Last checked on').': '.($last_checked ? date(FX_DATE_FORMAT.' '.FX_TIME_FORMAT) : 'never').'</h2>
			<p>&nbsp;</p>
			<form method="post">
				<input type="hidden" name="integrity_action" value="check">
				<input type="submit" class="button green" value="Start checking"> 
			</form>';
			
			if ($data) {
	
				$count = 1;
				
				$out .= '<hr>
				<h2>'._('Following problems were found. Click the item to fix problem or get details').':</h2>';
	
				foreach ($cases as $key => $title) {
					if (count($data[$key])) {
						$out .= '<h2><a href="'.replace_url_param('show', $key).'">'.($count++).') '.$title.'&nbsp;('.count($data[$key]).')</h2>';
					}
				}
			}
		}
		else {
			$out .= '
			<a class="button blue" href="'.replace_url_param('show', '').'">Back</a>
			<hr>
			<h1>'.$cases[$show_case].' ('.count($data[$show_case]).')</h1>';
			
			if (array_key_exists($show_case, $cases)) {
				$out .= print_report_table($data[$show_case], $show_case, $input_ids);
			}
			else {
				$out .= '<div class="error">'._('Invalid case value').'</div>';
			}
		}

		return $out;
	}

	$mb_data = array('body' => array('content' => _integrity_metabox()), 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);

/*
	function print_report_table($data, $header, $input_name, $input_ids)
	{
		if(count($data) < 1) {
			return false;
		}
	
		if($header) {
			echo "<h1>$header</h1>";
		}
	
		$ids = array_keys($data);
		$keys = array_keys($data[$ids[0]]);
	
		?>
		<div class="object-explorer integrity-report-table">
			<table>
				<tr>
				<th style="width: 20px;"><input type="checkbox" class="master-checkbox"></th>
				<?php foreach($keys as $key) { ?>
					<th><?php echo $key; ?></th>
				<?php } ?>
				</tr>
				<?php foreach($data as $row) {
					$index = "";
					foreach($input_ids as $id_field) {
						$index .= "[".$row[$id_field]."]";
					}
					?>
					<tr>
					<td>
						<input type="checkbox" name="<?php echo $input_name.$index ?>">
					</td>
					<?php foreach($row as $value) { ?>
						<td><?php echo $value; ?></td>
					<?php } ?>
					</tr>
				<?php } ?>
			</table>
		</div>
		<?php
		return true;
	}
	
	ob_start();
	
	if ($_SESSION["report"] && $_POST)
	{
		$report = unserialize($_SESSION["report"]);
		$_POST["types"] && $report->clear_lost_types($_POST["type_fields"]);
		$_POST["type_fields"] && $report->clear_lost_type_fields($_POST["type_fields"]);
		$_POST["enums"] && $report->clear_lost_enums($_POST["enums"]);
		$_POST["enum_fields"] && $report->clear_lost_enum_fields($_POST["enum_fields"]);
		$_POST["objects"] && $report->clear_lost_objects($_POST["objects"]);
		$_POST['link_types'] && $report->clear_lost_link_types($_POST['link_types']);
		$_POST["links"] && $report->clear_lost_links($_POST["links"]);
		$_POST["types_without_tables"] && $report->clear_types_without_tables($_POST["types_without_tables"]);
		$_POST["type_fields_without_columns"] && $report->clear_type_fields_without_columns($_POST["type_fields_without_columns"]);
		$_POST["columns_without_type_fields"] && $report->clear_columns_without_type_fields($_POST["columns_without_type_fields"]);
	}
	
	try {
		$report = FXIntegrityReport::Create();

		echo '
		<form method="post">
		<input type="submit" class="button red" value="Remove selected">';

		$objects = $report->get_lost_objects();

		foreach($objects as $object_type_id => $options) {
			print_report_table($options["objects"], "Lost objects of '".$options["display_name"]."'", "objects[$object_type_id]", array("object_id"));
		}
		
		print_report_table($report->get_lost_types(), "Lost types", "types", array("object_type_id"));
		print_report_table($report->get_lost_type_fields(), "Lost type fields", "type_fields", array("object_type_id", "name"));
		print_report_table($report->get_lost_enums(), "Lost enums", "enums", array("enum_type_id"));
		print_report_table($report->get_lost_enum_fields(), "Lost enum_fields", "enum_fields", array("enum_type_id", "enum_field_id"));
		print_report_table($report->get_lost_link_types(), "Lost link types", "link_types", array("object_type_1_id", "object_type_2_id"));
		print_report_table($report->get_lost_links(), "Lost links", "links", array("object_type_1_id", "object_1_id", "object_type_2_id", "object_2_id"));
		print_report_table($report->get_types_without_tables(), "Types without MySQL tables", "types_without_tables", array("object_type_id"));
		print_report_table($report->get_type_fields_without_columns(), "Type fields without MySQL Columns", "type_fields_without_columns", array("object_type_id", "name"));
		print_report_table($report->get_columns_without_type_fields(), "MySQL columns without type fields", "columns_without_type_fields", array("table_name", "column_name"));
		
		echo '
		<input type="submit" class="button red" value="Remove selected">
		</form>';

		$_SESSION['report'] = serialize($report);
	}
	catch (Exception $e){
		var_dump($e);
	}

	$content = ob_get_contents();

	ob_end_clean();

	$mb_data = array('body' => array('content' => $content), 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);
	*/