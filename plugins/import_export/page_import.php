<?php

	$IOResult = false;
	
	function _import_archive($archive_name, $schema_name)
	{
		if (!$schema_name) {
			return new FX_Error(__FUNCTION__, _('Please specify new Data Schema name'), 0);
		}
				
		$archive = new ZipArchive();
		$archive->open(IE_PLUGIN_DIR.'/uploads/'.$_POST['import_archive']);	

		$sets = csv_to_array($archive->getFromName("sets.csv"), null, ';');
		$types = csv_to_array($archive->getFromName("types.csv"), null, ';');
		$enums = csv_to_array($archive->getFromName("enums.csv"), null, ';');
		$metrics = csv_to_array($archive->getFromName("metrics.csv"), null, ';');
		$link_types = csv_to_array($archive->getFromName("link_types.csv"), null, ';');
		$links = csv_to_array($archive->getFromName("links.csv"), null, ';');

		$fields = array();
		
		foreach (csv_to_array($archive->getFromName("fields.csv"), null, ';') as $field) {
			$fields[$field['object_type_id']][$field['name']] = $field;
		}

		if (!$sets) {
			return new FX_Error(__FUNCTION__, _('No Data Sets found'), 0);
		}

		$old_new_id = array('sets' => array(),
						    'types' => array(),
							'objects' => array(),
							'enumns' => array(),
							'metrics' => array());

		$new_schema['object_type_id'] = TYPE_DATA_SCHEMA;
		$new_schema['display_name'] = $schema_name;
		$new_schema['schema_id'] = 0;

		$forced_validation = isset($_POST['forced_validation']) ? true : false;
		$check_system_types = isset($_POST['check_system_types']) ? true : false;
		
		$schema_id = add_object($new_schema);

		if (is_fx_error($schema_id)) {
			return new FX_Error(__FUNCTION__, $schema_id->get_error_message(), 0);
		}

		foreach ($enums as $enum) {

			$enum['schema_id'] = $schema_id;
			$enum['fields'] = array();

			$enum_fields = csv_to_array($archive->getFromName('enum_fields_'.$enum['enum_id'].'.csv'), null, ';');

			foreach ($enum_fields as $enum_field) {
				$enum['fields'][$enum_field['value']] = array(
					'label' => $enum_field['label'],
					'color' => $enum_field['color'],
					'opacity' => $enum_field['opacity']);
			}
			
			$enum_id = add_enum_type($enum);
			
			if (!is_fx_error($enum_id)) {
				$old_new_id['enums'][$enum['enum_id']] = $enum_id;
			}
			else {
				return new FX_Error(__FUNCTION__, $enum_id->get_error_message(), $schema_id);
            }
		}
			
		foreach ($metrics as $metric) {
			$metric['schema_id'] = $schema_id;
			$metric['units'] = array();

			$units = csv_to_array($archive->getFromName('units_'.$metric['metric_id'].'.csv'), null, ';');

			if ($units && is_array($units)) {
				$metric['units'] = $units;
			}
			else {
				$metric['units'] = array();
			}

			$metric_id = add_metric($metric);
			
			if (!is_fx_error($metric_id)) {
				$old_new_id['metrics'][$metric['metric_id']] = $metric_id;
			}
			else {
                return new FX_Error(__FUNCTION__, $metric_id->get_error_message(), $schema_id);
            }
		}

		foreach ($sets as $set_object) {
			$set_object['object_type_id'] = TYPE_DATA_SET;
			$set_object['schema_id'] = $schema_id;

			$set_id = add_object($set_object);
			
			if (!is_fx_error($set_id )) {
				$old_new_id['sets'][$set_object['object_id']] = $set_id;
			}
			else {
                return new FX_Error(__FUNCTION__, $set_id->get_error_message(), $schema_id);
            }
		}

		foreach ($types as $type) {

			$type['schema_id'] = $schema_id;
			$type['fields'] = $fields[$type['object_type_id']];

            foreach ($type['fields'] as &$field) {
                if (is_numeric($field['type'])) {
                    $field['type'] = $old_new_id['enums'][$field['type']];
                }
            }
			
			if (!$type['system']) {
				$object_type_id = add_type($type);
			}
			elseif ($check_system_types) {
				if (!$object_type_id = get_type_id_by_name(0, $type['name'])) {
					$object_type_id = new FX_Error('import_data_schema', _('Incompatible system types. Unable to get system type by name'). ' ['.$type['name'].']', $schema_id);
				}
			}

            if (!is_fx_error($object_type_id)) {

                $old_new_id['types'][$type['object_type_id']] = $object_type_id;

                $objects = csv_to_array($archive->getFromName('objects'.$type['object_type_id'].'.csv'), null, ';');
				
				foreach ($objects as $object) {
					
					$object['object_type_id'] = $object_type_id;
					$object['schema_id'] = $schema_id;
					$object['set_id'] = $old_new_id['sets'][$object['set_id']];

					$object_id = add_object($object, $forced_validation); //Forced (ignore validation error)

					if (!is_fx_error($object_id)) {
						$old_new_id['objects'][$type['object_type_id']][$object['object_id']] = $object_id;
					}
					else {
						return new FX_Error(__FUNCTION__, $object_id->get_error_message(), $schema_id);
                    }
				}
			}
			else {
				return new FX_Error(__FUNCTION__, $object_type_id->get_error_message(), $schema_id);
            }
		}

		foreach ($link_types as $link_type) {
            $result = add_link_type(
				$old_new_id['types'][$link_type['object_type_1_id']],
				$old_new_id['types'][$link_type['object_type_2_id']], 
				$link_type['relation'], 
				$schema_id,
				$link_type['system'],
				$link_type['position']);
				
			if (is_fx_error($result)) {
				return new FX_Error(__FUNCTION__, $result->get_error_message(), $schema_id);
			}
		}

		foreach ($links as $link){
             $result = add_link(
				$old_new_id['types'][$link['object_type_1_id']],
				$old_new_id['objects'][$link['object_type_1_id']][$link['object_1_id']],
				$old_new_id['types'][$link['object_type_2_id']], 
				$old_new_id['objects'][$link['object_type_2_id']][$link['object_2_id']],
				$link['meta']);
				
			if (is_fx_error($result)) {
				return new FX_Error(__FUNCTION__, $result->get_error_message(), $schema_id);
			}
		}		

		return $schema_id;
	}

	global $fx_error;

	if (!is_dir(IE_PLUGIN_DIR.'/uploads')) {
		if (!mkdir(IE_PLUGIN_DIR.'/uploads/', 0777)) {
			$fx_error->add('import_export', _('Unable to create uploads directory').' ['.IE_PLUGIN_DIR.'/uploads]');
		}
	}
	elseif (!is_writable(IE_PLUGIN_DIR.'/uploads/')) {
		$fx_error->add('import_export', _('Insufficient permissions to write to the directory').' ['.IE_PLUGIN_DIR.'/uploads]');
	}

	if (isset($_POST['delete_archive'])) {
		$file_path = IE_PLUGIN_DIR.'/uploads/'.$_POST['delete_archive'];
		
		if (!file_exists($file_path)) {
			$fx_error->add('import_export', _('Specified file does not exists'));
		}
		else {
			unlink($file_path);
			if (!file_exists($file_path)) {
				fx_redirect(current_page_url());
			}
			else {
				$fx_error->add('import_export', _('Unable to remove specified archive'));
			}
		}
	}

	if (!empty($_FILES['archive'])) {
		$file_path = IE_PLUGIN_DIR.'/uploads/';
		$filename = $_FILES['archive']['name'];
	
		while(file_exists($file_path.$filename) !== false) {
			$filename = "new_".$filename;
		}
		
		move_uploaded_file($_FILES['archive']['tmp_name'], $file_path.$filename);
	
		if (file_exists($file_path.$filename)) {
			fx_redirect(current_page_url());
		}
		else {
			$fx_error->add('import_export', _('Unable to upload archive'));
		}
    }

	if (isset($_POST['import_archive'])) {
		$backup_name = FX_Backup::get_instance()->backup()->get_filename();

		$import_result = _import_archive($_POST['import_archive'], $_POST['schema_name']);

		if (is_fx_error($import_result)) {
			FX_Backup::get_instance()->delete($backup_name);
			$fx_error->add('import_archive', $import_result->get_error_message());
			
			$schema_id = intval($import_result->get_error_data());
			if ($schema_id) {
				delete_object(TYPE_DATA_SCHEMA, $schema_id);
			}
		}
		else {
            FX_Backup::get_instance()->delete($backup_name);
			set_current_fx_dir($import_result);
			//fx_redirect(URL.'network_admin/network_data_schemas');
        }
	}

	function _mb_import_content()
	{
		global $fx_error;

		$out .= '';

		if (!$fx_error->is_empty) {
			$errors = $fx_error->get_error_messages();
			for ($i=0; $i<count($errors); $i++) $out .= '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
		}
		
		if (isset($_GET['import_archive'])) {
			$out .= '
			<h1>Specify new Data Schema name:</h1>
			<hr>
			<form method="post">
				<input type="hidden" name="import_archive" value="'.$_GET['import_archive'].'">
				<p>
					<input id="schema_name" name="schema_name" value="">
				</p>
				<hr>
				<p>
					<label for="forced_validation">Forced validation (ignore validation errors if possible)</label>
					<input type="checkbox" name="forced_validation" id="forced_validation" checked="checked">
				</p>
				<hr>
				<p>
					<label for="check_system_types">Check system types compatibility</label>
					<input type="checkbox" name="check_system_types" id="check_system_types" checked="checked">
				</p>
				<hr>
				<a href="'.URL.PAGE.'/'.FIRST_PARAM.'" class="button red">'._('Cancel').'</a>
				<input type="submit" class="button green" value="'._('Continue').'">
			</form>';
		}
		else {
			
			$files = array_filter(scandir(IE_PLUGIN_DIR.'/uploads'), 'is_zip');

			$out .= '
			<h1>'._('Select archive to import or upload another one').'</h1>
			<hr>
			<div class="object-explorer">';
	
			if ($files) {
	
				$out .= '
				<table>
					<tr>
						<th style="width: 66%">Archive Name</th>
						<th></th>
						<th></th>
					</tr>';
	
				foreach ($files as $file) {
					$out .= '
					<tr>
						<td>'.$file.'</td>
						<td>
							<a href="'.replace_url_param('import_archive', $file).'" class="button green">'._('Import').'</a>
						</td>						
						<td>
							<form method="post">
								<input type="hidden" name="delete_archive" value="'.$file.'">
								<input type="submit" class="button red" value="'._('Delete').'">
							</form>
						</td>
					</tr>';
				}
	
				$out .= '
				</table>';
			}
			else {
				$out .= '<div class="info">'._('No files to import').'</div>';
			}
			$out .= '
			</div>
			<hr>
			<form enctype="multipart/form-data" method="post">
				<input type="file" name="archive">
				<input type="submit" class="button green" value="Upload">
			</form>';
		}
	
		return $out;
	}

	$mb_data = array('body' => array('content' => _mb_import_content()),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);