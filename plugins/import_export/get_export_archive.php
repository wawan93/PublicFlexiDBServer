<?php

	require_once dirname(dirname(dirname(__FILE__)))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$export_filename = tempnam('export_temp', 'export');
	$zip = new ZipArchive();
	
	if ($zip->open($export_filename, ZIPARCHIVE::CREATE)!==true) {
		echo $export_filename;
		exit(_('Unable to create file'));
	}
	
	$zip->addEmptyDir('files');
	
	// Data Schema
	//-------------------------------------------------------------------------------

	$schema = get_object(TYPE_DATA_SCHEMA, $_GET['schema_id']);

	if (is_fx_error($schema)) {
		exit($schema->get_error_message());
	}

	unset($schema['created'], $schema['modified'], $schema['api_key'], $schema['user_ip']);

	$schema_id = $schema['object_id'];

	//Data Sets
	//-------------------------------------------------------------------------------

    foreach($_GET['sets'] as $set_id=>$nothing){

        $data_sets[$set_id] = get_object(TYPE_DATA_SET, $set_id);

        if (is_fx_error($data_sets[$set_id])) {
            exit($data_sets[$set_id]->get_error_message());
        }
    }

    $first_set = current($data_sets);
	
	unset($first_set['created'], $first_set['modified'], $first_set['api_key'], $first_set['user_ip']);
	
	$data_set_csv = implode(';', array_keys($first_set));
	
	foreach ($data_sets as $data_set) {
		unset($data_set['created'], $data_set['modified'], $data_set['api_key'], $data_set['user_ip']);
		$data_set_csv .= "\n".implode(';', array_values($data_set));
	}

	$zip->addFromString('sets.csv', $data_set_csv);	

	//Types, Type Fields, Objects
	//-------------------------------------------------------------------------------
	$types = get_schema_types($schema_id, 'none');
	$type_ids = array_keys($types);
	
	//add system types
	$system_types = array(
		TYPE_ROLE, 
		TYPE_WP_TMPL_PAGE, 
		TYPE_WP_TMPL_SIGNUP,
		TYPE_APPLICATION,
		TYPE_APP_DATA,
		TYPE_APPLICATION_THEME,
		TYPE_QUERY,
		TYPE_DATA_FORM,
		TYPE_REPORT,
		TYPE_MEDIA_IMAGE,
		TYPE_MEDIA_FILE,
		TYPE_FSM_EVENT);
	
	foreach ($system_types as $object_type_id) {
		if (is_fx_error($types[$object_type_id] = get_type($object_type_id, 'none'))) {
			$types[$object_type_id] = array();
		}
		else {
			$type_ids[] = $object_type_id;
		}
	}

	if (is_fx_error($types)) {
		exit($types->get_error_message());
	}

	$first_type = array_shift($types);
	$types_csv = implode(';', array_keys($first_type))."\n".implode(';', array_values($first_type));
	
	$first_type_fields = get_type_fields($first_type['object_type_id'], 'custom');
	
	if ($first_type_fields) {
		if (is_fx_error($first_type_fields)) {
			exit($first_type_fields->get_error_message());
		}
	
		$first_field = array_shift($first_type_fields);
		$fields_csv = implode(';', array_keys($first_field))."\n".implode(';', array_values($first_field));
		
		foreach ($type_fields as $field) {
			$fields_csv .= "\n".implode(';', array_values($field));
		}
	}

	foreach ($types as $type)
	{		
		$types_csv .= "\n".implode(';', array_values($type));
		
		//Fields
		//-------------------------------------------------------------------------------
		
		if (!$type['system']) {
		
			$type_fields = get_type_fields($type['object_type_id'], 'custom');
	
			if ($type_fields) {
				if (is_fx_error($type_fields)) {
					exit($type_fields->get_error_message());
				}
	
				if (!$fields_csv) {
					$first_field = array_shift($type_fields);
					$fields_csv = implode(';', array_keys($first_field))."\n".implode(';', array_values($first_field));	
				}
				
				foreach ($type_fields as $field) {
					$fields_csv .= "\n".implode(';', array_values($field));
				}
			}
		}
	}
	
	foreach ($type_ids as $object_type_id) {
		// Objects
		//-------------------------------------------------------------------------------

		if ($type_objects = get_objects_by_type($object_type_id, $schema_id))
		{
			if (is_fx_error($type_objects)) {
				exit($type_objects->get_error_message());
			}

			$first_object = array_shift($type_objects);

			unset($first_object['created'], $first_object['modified'], $first_object['api_key'], $first_object['user_ip']);

			$objects_csv = implode(';', array_keys($first_object))."\n".implode(';', array_values($first_object));

			foreach ($type_objects as $object) {
				unset($object['created'], $object['modified'], $object['api_key'], $object['user_ip']);
				$objects_csv .= "\n".implode(';', array_values($object));
			}

			$zip->addFromString('objects'.$object_type_id.'.csv', $objects_csv);
		}	
	}

	$zip->addFromString('types.csv', $types_csv);	
	$zip->addFromString('fields.csv', $fields_csv);	


	//Link Types
	//-------------------------------------------------------------------------------
	
	$link_types = get_schema_link_types($schema_id);
	
	if (is_fx_error($link_types)) {
		exit($link_types->get_error_message());
	}
	
	$first_link_type = array_shift($link_types);
	
	$link_types_csv = implode(';', array_keys($first_link_type))."\n".implode(';', array_values($first_link_type));
	
	foreach ($link_types as $link_type) {
		$link_types_csv .= "\n".implode(';', array_values($link_type));
	}	

	$zip->addFromString("link_types.csv",$link_types_csv);

	//Links
	//-------------------------------------------------------------------------------

	$links = import_export_get_links_in_data_sets(array_column($types,'object_type_id'), array_keys($data_sets));

	if (is_fx_error($links)) {
		exit($links->get_error_message());
	}
	
	$first_link = array_shift($links);
	
	$links_csv = implode(';', array_keys($first_link))."\n".implode(';', array_values($first_link));
	
	foreach ($links as $link) {
		$links_csv .= "\n".implode(';', array_values($link));
	}	

	$zip->addFromString("links.csv", $links_csv);
	
	//Enums
	//-------------------------------------------------------------------------------
	$enums = get_schema_enums($schema_id, false);
	
	$enums_csv = '';

	foreach ($enums as $enum_id => $enum) {
		
		$enums_csv .= "\n$enum_id;{$enum[name]};";
		
		$enum_fields = get_enum_fields($enum_id, true);
		
		if (count($enum_fields) > 0) {
			
			$enum_fields_csv = "value;label;color;opacity;";

			foreach($enum_fields as $value => $data) {
				$enum_fields_csv .= "\n$value;{$data[label]};{$data[color]};{$data[opacity]};";
			}
			
			$zip->addFromString("enum_fields_".$enum_id.".csv", $enum_fields_csv);
		}
	}
	
	if ($enums_csv) {
		$zip->addFromString("enums.csv", "enum_id;name;".$enums_csv);
	}
	//-------------------------------------------------------------------------------

	//Units
	//-------------------------------------------------------------------------------
	$metrics = get_schema_metrics($schema_id, false);
	
	$metrics_csv = '';

	foreach ($metrics as $metric_id => $metric) {
		
		$metrics_csv .= "\n$metric_id;{$metric[name]};{$metric[description]};{$metric[is_currency]};";
		
		$units = get_metric_units($metric_id);
		
		if (count($units) > 0) {

			$units_csv = "unit_id;name;factor;decimals";
			
			foreach($units as $unit) {
				$units_csv .= "\n{$unit[unit_id]};{$unit[name]};{$unit[factor]};{$unit[decimals]};";
			}
			
			$zip->addFromString("units_".$metric_id.".csv", $units_csv);
		}
	}
	
	if ($enums_csv) {
		$zip->addFromString("metrics.csv", "metric_id;name;description;is_currency;".$metrics_csv);
	}
	//-------------------------------------------------------------------------------	
	
	$zip->close();

	ob_end_clean();

	header('Content-Type: application/zip');
	header('Content-disposition: attachment; filename='.$schema['name'].'.zip');
	header('Content-Length: '.filesize($export_filename));
	
	readfile($export_filename);
	unlink($export_filename);