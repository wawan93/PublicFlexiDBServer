<?php

const MAX_REVISIONS_NUM = 10;

global $DEFAULT_TYPE_OPTIONS;
$DEFAULT_TYPE_OPTIONS = array(
    "system" => false,
    "revisions_number" => 1,
    "fields" => array()
);

global $OBJECT_BASE_FIELDS;
$OBJECT_BASE_FIELDS = array(
    "schema_id" => array(
        "name" => "schema_id",
        "caption" => "Schema ID",
        "description" => "Schema ID",
        "mandatory" => true,
        "type" => "INT",
        "default_value" => 0,
        "length" => 11,
        "sort_order" => -10,
    ),
    "set_id" => array(
        "name" => "set_id",
        "caption" => "Set ID",
        "description" => "Set ID",
        "mandatory" => true,
        "type" => "INT",
        "default_value" => 0,
        "length" => 11,
        "sort_order" => -12,
    ),	
    "object_id" => array(
        "name" => "object_id",
        "caption" => "Object ID",
        "description" => "Object ID",
        "mandatory" => true,
        "type" => "INT",
        "default_value" => "",
        "length" => 11,
        "sort_order" => -13,
    ),
    "system" => array(
        "name" => "system",
        "caption" => "System",
        "description" => "System",
        "mandatory" => true,
        "type" => "INT",
        "default_value" => 0,
        "length" => 1,
        "sort_order" => -11,
    ),
    "created" => array(
        "name" => "created",
        "caption" => "Created",
        "description" => "Created",
        "mandatory" => false,
        "type" => "INT",
        "default_value" => null,
        "length" => 4,
        "sort_order" => -10,
    ),
    "modified" => array(
        "name" => "modified",
        "caption" => "Modified",
        "description" => "Modified",
        "mandatory" => false,
        "type" => "INT",
        "default_value" => null,
        "length" => 4,
        "sort_order" => -9,
    ),
    "name" => array(
        "name" => "name",
        "caption" => "Name",
        "description" => "Object Name",
        "mandatory" => true,
        "type" => "VARCHAR",
        "default_value" => null,
        "length" => 64,
        "sort_order" => -8,
    ),
    "display_name" => array(
        "name" => "display_name",
        "caption" => "Display Name",
        "description" => "Object Display Name",
        "mandatory" => true,
        "type" => "VARCHAR",
        "default_value" => null,
        "length" => 64,
        "sort_order" => -7,
    )
);

global $OBJECT_TYPE_FIELDS;
$OBJECT_TYPE_FIELDS = array(
    "schema_id" => array(
        "type" => "INT",
        "length" => 4,
        "mandatory" => true,
    ),
    "system" => array(
        "type" => "INT",
        "length" => 1,
        "mandatory" => true,
    ),
    "prefix" => array(
        "type" => "VARCHAR",
        "length" => 32,
        "mandatory" => false
    ),
    "name_format" => array(
        "type" => "VARCHAR",
        "length" => 128,
        "mandatory" => false,
    ),
    "name" => array(
        "type" => "VARCHAR",
        "length" => 64,
        "mandatory" => true
    ),
    "display_name" => array(
        "type" => "VARCHAR",
        "length" => 64,
        "mandatory" => true
    ),
    "description" => array(
        "type" => "VARCHAR",
        "length" => 256,
        "mandatory" => false
    ),
    "revisions_number" => array(
        "type" => "INT",
        "length" => 2,
        "mandatory" => true
    )
);

global $CUSTOM_FIELD_OPTIONS;
$CUSTOM_FIELD_OPTIONS = array(
    "name" => array(
        "type" => "VARCHAR",
        "length" => 64,
        "mandatory" => true
    ),
    "caption" => array(
        "type" => "VARCHAR",
        "length" => 64,
        "mandatory" => false
    ),
    "description" => array(
        "type" => "VARCHAR",
        "length" => 255,
        "mandatory" => false
    ),
    "mandatory" => array(
        "type" => "INT",
        "length" => 1,
        "mandatory" => true,
    ),
    "type" => array(
        "type" => "VARCHAR",
        "length" => 32,
        "mandatory" => true
    ),
    "default_value" => array(
        "type" => "VARCHAR",
        "length" => 255,
        "mandatory" => false
    ),
    "length" => array(
        "type" => "INT",
        "length" => 11,
        "mandatory" => false,
    ),
    "sort_order" => array(
        "type" => "INT",
        "length" => 11,
        "mandatory" => true,
    )
);

/*******************************************************************************
 * Check is custom field valid
 *
 * @param array $field - field to check
 * @return bool|FX_Error `true` if field is valid and error else
 * ****************************************************************************/
function validate_type_custom_field($field)
{
    global $CUSTOM_FIELD_OPTIONS;
    global $fx_field_types;

    $field_name = $field['name'];
    $errors = new FX_Error();

	global $OBJECT_BASE_FIELDS;
	
	if (isset($OBJECT_BASE_FIELDS[$new_name])) {
		return new FX_Error(__FUNCTION__, _('Field name is reserved'));
	}
	
	global $db_reserved_words;
	
	if (in_array(strtoupper($new_name), $db_reserved_words)) {
		return new FX_Error(__FUNCTION__, _('Field name is reserver as DB keyword'));
	}


    if (!is_numeric($field['length']) || ((int)$field['length'] < 0)) {
        $errors->add(__FUNCTION__, _('Invalid length value for')." [$field_name]");
    }

    $name_errors = validate_normal_string($field_name);

    if (is_fx_error($name_errors)) {
        foreach ($name_errors->get_error_messages() as $message) {
            $errors->add(__FUNCTION__, _('Invalid field name for')." [$field_name].]. $message");
        }
    }

    $field_type = strtolower($field['type']);

    if (is_numeric($field_type)) {
        if (!enum_type_exists($field_type)) {
			$errors->add(__FUNCTION__, _('Invalid enum type for')." [$field_name]");
        }
    }
	elseif (!array_key_exists($field_type, $fx_field_types)) {
		$errors->add(__FUNCTION__, _('Invalid field type')." [$field_type] "._('for')." [$field_name]");
    }

    foreach ($CUSTOM_FIELD_OPTIONS as $option_name => $option) {
        $filed_errors = validate_field_simple($field[$option_name], $option_name, $option);
        if (is_fx_error($filed_errors)) {
            foreach ($filed_errors->get_error_messages() as $message) {
                $errors->add(__FUNCTION__, "Field ".$field_name.": ".$option_name.": ".$message );
            }
        }
    }

    return $errors->is_empty() ? true : $errors;
}

function validate_field_simple($value, $field_name, $constraints)
{
    if($value === null) {
        $value = "";
    }
    else if($value === false) {
        $value = "0";
    }

    $value_len = strlen($value);
	
    if($value_len == 0) {
        if($constraints['mandatory'] && !array_key_exists('default_value', $constraints)) {
            return new FX_Error(__FUNCTION__, $field_name." is mandatory");
        }
        return True;
    }
	
    $errors = new FX_Error();
	
    if($constraints['length'] && ($value_len > $constraints['length'])) {
        $errors->add(__FUNCTION__, $field_name." maximum length is ".$constraints['length']);
    }

    switch(strtolower($constraints['type']))
    {
        case "int":

            if(!is_numeric($value) && !is_bool($value) || (int)$value != $value) {
                $errors->add(__FUNCTION__, $field_name." must be integer");
            }
			
            if($value < 0) {
                $errors->add(__FUNCTION__, $field_name." must be > 0");
            }
			
            break;

        case "float":
            if(!is_numeric($value)) {
                $errors->add(__FUNCTION__, $field_name." must be numeric");
            }
            break;
    }
	
	return $errors->is_empty() ? true : $errors;
}


/*******************************************************************************
 * Check if the specified object type is valid
 * @param array $type - type to check
 * @return bool|FX_Error - `true` if valid and error else
 * ****************************************************************************/
function validate_type($type)
{
    global $OBJECT_TYPE_FIELDS;
    global $system_types;

    $errors = new FX_Error();

    foreach ($OBJECT_TYPE_FIELDS as $field_name => $field) {

        if (!array_key_exists($field_name, $type)) {
            $type[$field_name] = null;
        }

        $filed_errors = validate_field_simple($type[$field_name], $field_name, $field);

        if (is_fx_error($filed_errors)) {
            foreach ($filed_errors->get_error_messages() as $message) {
                $errors->add(__FUNCTION__, $field_name.' incorrect: '.$message);
            }
        }
    }

    if (!$type['system']) {
        if (!(int)$type['schema_id']) {
            $errors->add(__FUNCTION__, _('Set Schema ID for non-system type'));
        }
		elseif (!object_exists(TYPE_DATA_SCHEMA, $type['schema_id'])) {
            $errors->add(__FUNCTION__, _('Specified Schema ID does not exists'));
        }
    }

    $object_type_id = get_type_id_by_name($type['schema_id'], $type['name']);

    if ($object_type_id) {
        $errors->add(__FUNCTION__, _('Type name is already in use in the specified data schema')." [{$type['name']}]");
    }

    if ($type['revisions_number'] < 1 || $type['revisions_number'] > MAX_REVISIONS_NUM) {
        $errors->add(__FUNCTION__, _('Revisions number must be within')." 1..{MAX_REVISIONS_NUM}");
    }

    return $errors->is_empty() ? true : $errors;
}

/**
 * Create revision table for specified object type
 * @param int $object_type_id id of object type
 */
function _add_revisions_table($object_type_id)
{
    if (!(int)$object_type_id) {
        return new FX_Error(__FUNCTION__, _('Invalid type identifier'));
    }

    if (!type_exists($object_type_id)) {
        return new FX_Error(__FUNCTION__, _('Specified type does not exists'));
    }

    global $fx_db;

    $query = "CREATE TABLE ".DB_TABLE_PREFIX."object_type_".(int)$object_type_id."_rev (object_id INT, modified INT, field VARCHAR(64), value TEXT, PRIMARY KEY (object_id, field, modified))";

    if ($fx_db->exec($query) === false) {
        return new FX_Error(__FUNCTION__, _('Unable to create revisions table for type')." [$object_type_id]");
    }
	
	return true;
}

function _drop_revisions_table($object_type_id)
{
    if (!(int)$object_type_id) {
        return new FX_Error(__FUNCTION__, _('Invalid type identifier'));
    }

    if (!type_exists($object_type_id)) {
        return new FX_Error(__FUNCTION__, _('Specified type does not exists'));
    }

    global $fx_db;

    $query = "DROP TABLE ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev";

    if ($fx_db->exec($query) === false) {
        return new FX_Error(__FUNCTION__, _('Unable to drop revisions table for type')." [$object_type_id]");
    }
	
	return true;
}

/*******************************************************************************
 * Add type do DB
 * @param array $type type to add
 * @return int|FX_Error - type with defined object_type_id or error if type is invalid
 ******************************************************************************/
function add_type($type)
{
    $options = get_fx_option('fx_options');

    $type['display_name'] = substr($type['display_name'], 0, 32);
    $type['name'] = substr(normalize_string($type['name'] ? $type['name'] : $type['display_name']), 0, 32);

    if (!strlen($type['name'])) {
        return new FX_Error(__FUNCTION__, _('Invalid type name'));
    }

    $type['system'] = array_key_exists('system', $type) && (int)$type['system'] ? 1 : 0;

    if ($type['system']) {
        $type['schema_id'] = 0;
    }

    $type['name_format'] = substr($type['name_format'], 0, 255);
    $type['description'] = substr($type['description'], 0, 255);


    $type_prefix = substr(normalize_string($type['prefix']), 0, 8);

    if (strlen($type_prefix)) {
        $type_prefix = normalize_string($type_prefix.'_');
    }

    if (!array_key_exists('fields', $type) || !is_array($type['fields'])) {
        $type['fields'] = array();
    }

    if (array_key_exists('revisions_number', $type)) {

        $type['revisions_number'] = (int)$type['revisions_number'];

        if ($type['revisions_number'] < 1) {
            $type['revisions_number'] = 1;
        }
		elseif ($type['revisions_number'] > MAX_REVISIONS_NUM) {
            $type['revisions_number'] = MAX_REVISIONS_NUM;
        }
    }
	else {
        $type['revisions_number'] = $options['default_revision_number'] ? $options['default_revision_number'] : 1;
    }

    global $DEFAULT_TYPE_OPTIONS;

    $type = array_merge($DEFAULT_TYPE_OPTIONS, $type);

	$errors = validate_type($type);

    if (is_fx_error($errors)) {
        return $errors;
    }
	else {
        $errors = new FX_Error();
    }

    global $fx_db;
	
    $pdo = $fx_db->prepare("INSERT INTO ".DB_TABLE_PREFIX."object_type_tbl (schema_id, system, revisions_number, prefix, name_format, name, display_name, description) VALUES (:schema_id, :system, :revisions_number, :prefix, :name_format, :name, :display_name, :description)");

    $pdo->bindValue(':schema_id', $type['schema_id'], PDO::PARAM_INT);
    $pdo->bindValue(':system', $type['system'], PDO::PARAM_INT);
    $pdo->bindValue(':prefix', $type['prefix'], PDO::PARAM_STR);
    $pdo->bindValue(':name_format', $type['name_format'], PDO::PARAM_STR);
    $pdo->bindValue(':name', $type['name'], PDO::PARAM_STR);
    $pdo->bindValue(':display_name', $type['display_name'], PDO::PARAM_STR);
    $pdo->bindValue(':description', $type['description'], PDO::PARAM_STR);
    $pdo->bindValue(':revisions_number', $type['revisions_number'], PDO::PARAM_STR);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
            add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
        }
        return new FX_Error(__FUNCTION__, _('SQL Error'), 'Insert type into object_type_tbl');
    }

    $object_type_id = $fx_db->lastInsertId();

    $query = "CREATE TABLE ".DB_TABLE_PREFIX."object_type_".$object_type_id." (
				  object_id INT PRIMARY KEY AUTO_INCREMENT,
				  schema_id INT NOT NULL DEFAULT '".($type['system'] ? "0" : $type['schema_id'])."',
				  set_id INT NOT NULL DEFAULT '0',
				  created INT,
				  modified INT,
				  name VARCHAR(64),
				  display_name VARCHAR(64),
				  UNIQUE INDEX unique_name_idx(schema_id, set_id, name))";
				  /*
				  FOREIGN KEY fx_schema_id(schema_id) REFERENCES ".DB_TABLE_PREFIX."object_type_".TYPE_DATA_SCHEMA." (object_id) ON DELETE CASCADE,
				  FOREIGN KEY fx_set_id(set_id) REFERENCES ".DB_TABLE_PREFIX."object_type_".TYPE_DATA_SET." (object_id) ON DELETE CASCADE)";*/
				  
    if (!$fx_db->query($query)) {
        delete_type($object_type_id);
        return new FX_Error(__FUNCTION__, _('SQL Error occurred while creating type table'));
    }
/*	
	global $system_types;

    $query = "ALTER TABLE ".DB_TABLE_PREFIX."object_type_".$object_type_id." 
		      ADD UNIQUE INDEX(schema_id, set_id, name), 
		      ADD FOREIGN KEY (schema_id) REFERENCES  ".DB_TABLE_PREFIX."object_type_".$system_types["data_schema"]." (object_id), 
		      ADD FOREIGN KEY (set_id) REFERENCES ".DB_TABLE_PREFIX."object_type_".$system_types["data_set"]." (object_id);";
	
    if (!$fx_db->query($query)) {
        return new FX_Error(__FUNCTION__, _('SQL Error occurred while adding foreign keys and indices'));
    }*/

    if ($type['revisions_number'] > 1) {
        _add_revisions_table($type["object_type_id"]);
    }

	global $OBJECT_BASE_FIELDS;

    foreach (array_keys($type['fields']) as $field_name) {
        if (array_key_exists($field_name, $OBJECT_BASE_FIELDS)) {
            unset($type['fields'][$field_name]);
        }
    }
	
	if ($type['fields']) {

		$fields_errors = update_type_custom_fields($object_type_id, $type['fields'], false);
	
		if (is_fx_error($fields_errors)) {
			foreach ($fields_errors->get_error_messages() as $message) {
				$errors->add(__FUNCTION__, $message);
			}
		}
	
		if (!$errors->is_empty()) {
			delete_type($type['object_type_id']);
			return $errors;
		}
		
		if ($type['system']) {
			delete_fx_option('system_types_cache');
		}
	}

	$ss = get_schema_settings($type['schema_id']);
	
	if (!$ss['default_auto_disabled']) {
		$query_id = create_default_query($object_type_id);
		
		if (is_fx_error($query_id)) {
			add_log_message('create_default_query', $query_id->get_error_message());
		}
		
		$form_id = create_default_form($object_type_id);
		
		if (is_fx_error($form_id)) {
			add_log_message('create_default_form', $form_id->get_error_message());
		}
	}

    return $object_type_id;
}

function _insert_external_type($schema_id, $global_type_id, $local_type_id)
{
	global $fx_db;

	$data = array(
		'schema_id' => $schema_id,
		'global_type_id' => $global_type_id,
		'local_type_id' => $local_type_id
	);

	$result = $system_db->insert(DB_TABLE_PREFIX.'schema_type_tbl', $data);

	return is_fx_error($result) ? $result : true;
}

function _delete_external_type($schema_id, $local_type_id)
{
	global $fx_db;

	$data = array(
		'schema_id' => $schema_id,
		'global_type_id' => $global_type_id,
		'local_type_id' => $local_type_id
	);

	$result = $system_db->delete(DB_TABLE_PREFIX.'schema_type_tbl', array('schema_id'=>$schema_id, 'local_type_id'=>$local_type_id));

	return is_fx_error($result) ? $result : true;
}

/*******************************************************************************
 * Update type in DB
 * @param array $type with new type options
 * @return int|FX_Error - new type id
 ******************************************************************************/
function update_type($type_array)
{
    $object_type_id = $type_array['object_type_id'];

    $old_type = get_type($type_array['object_type_id'], 'custom');

    if (is_fx_error($old_type) || $old_type === false) {
    	return new FX_Error(__FUNCTION__, _('Invalid type identifier'));
    }

    $is_system_type = $old_type['system'] ? true : false;

    $type_display_name = substr($type_array['display_name'], 0, 32);
    $type_name = substr(normalize_string($type_array['name'] ? $type_array['name'] : $type_display_name), 0, 32);

    if ($type_name != $old_type['name'] && $is_system_type) {
        return new FX_Error(__FUNCTION__, _('Impossible to change name of the system type'));
    }

    if (!strlen($type_name)) {
        return new FX_Error(__FUNCTION__, _('Empty type name'));
    }

    $type_name_format = $type_array['name_format'];
    $type_prefix = $type_array['prefix'] ? substr(normalize_string($type_array['prefix'].'_'), 0, 8) : '';

    $type_description = substr($type_array['description'], 0, 255);

    if (array_key_exists('revisions_number', $type_array)) {
        $revisions_number = (int)$type_array['revisions_number'];
        if ($revisions_number < 1) {
            $revisions_number = 1;
        }
		elseif ($revisions_number > MAX_REVISIONS_NUM) {
            $revisions_number = MAX_REVISIONS_NUM;
        }
    }
	else {
        $revisions_number = 1;
    }

    global $fx_db;

    $pdo = $fx_db->prepare("UPDATE ".DB_TABLE_PREFIX."object_type_tbl SET name=:name, display_name=:display_name, name_format=:name_format, prefix=:prefix, description=:description, revisions_number=:revisions_number WHERE object_type_id=:object_type_id");

    $pdo->bindValue(':object_type_id', $object_type_id, PDO::PARAM_INT);
    $pdo->bindValue(':name', $type_name, PDO::PARAM_STR);
    $pdo->bindValue(':display_name', $type_display_name, PDO::PARAM_STR);
    $pdo->bindValue(':name_format', $type_name_format, PDO::PARAM_STR);
    $pdo->bindValue(':prefix', $type_prefix, PDO::PARAM_STR);
    $pdo->bindValue(':description', $type_description, PDO::PARAM_STR);
    $pdo->bindValue(':revisions_number', $revisions_number, PDO::PARAM_STR);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
            add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
        }
        return new FX_Error(__FUNCTION__, _('SQL Error occurred while updating object type')." [$object_type_id]");
    }

    $errors = new FX_Error();

    // Update revisions
    // ***************************************************************************************************

    if ($revisions_number != $old_type['revisions_number']) {
        if ($old_type['revisions_number'] == 1 && $revisions_number > 1) {
            $rvsn_tbl = _add_revisions_table($object_type_id);
        }
		elseif ($old_type['revisions_number'] > 1 && $revisions_number == 1) {
            $rvsn_tbl = _drop_revisions_table($object_type_id);
        }
		elseif ($old_type['revisions_number'] > $revisions_number) {
            $rvsn_tbl = _truncate_old_revisions($object_type_id, $revisions_number);
        }

        if (is_fx_error($rvsn_tbl)) {
            $errors->add(__FUNCTION__, $rvsn_tbl->get_error_message());
        }
    }
    // ***************************************************************************************************

    $new_fields = array_key_exists('fields', $type_array) ? $type_array['fields'] : false;
    $old_fields = $old_type['fields'];

	$update_default_fields = array();

    if ($new_fields !== false)
	{		
        $errors = new FX_Error();

        $order = 0;
        $prev_field = 'first';

        foreach ($new_fields as $field) {
            $field = _prepare_type_field($field);

            if (!array_key_exists($field['name'], $old_fields)) {
                $add_field = _add_type_field($object_type_id, $field, $prev_field);
                if (is_fx_error($add_field)) {
                    $errors->add(__FUNCTION__, $add_field->get_error_message());
                }
				else {
                    $prev_field = $add_field;
					$update_default_fields[] = $field['name'];
                }
            }
			else {
                if ($old_fields[$field['name']] != $field) {
                    $update_field = _update_type_field($object_type_id, $field, $prev_field);

                    if (is_fx_error($update_field)) {
                        $errors->add(__FUNCTION__, $update_field->get_error_message());
                    }

                    $prev_field = $field['name'];
                }
                unset($old_fields[$field['name']]);
            }
            $order++;
        }

        if ($old_fields) {
            foreach ($old_fields as $field) {
                $res = _delete_type_field($object_type_id, $field['name']);
                if (is_fx_error($res)) {
                    $errors->add(__FUNCTION__, $res->get_error_message());
                }
            }
        }
    }

	if ($is_system_type) {
		delete_fx_option('system_types_cache');
	}

	clear_query_cache_by_type($object_type_id);

	$ss = get_schema_settings($old_type['schema_id']);

	if (!$ss['default_auto_disabled']) {
		if (is_fx_error($res = update_default_query($object_type_id, $update_default_fields))) {
			add_log_message('update_default_query', $res->get_error_message());
		}
		if (is_fx_error($res = update_default_form($object_type_id, $update_default_fields))) {
			add_log_message('update_default_form', $res->get_error_message());
		}
	}

    return $errors->is_empty() ? true : $errors;
}

/**
 * Return mysql field type for fx field type
 * @param string $fx_type name of fx field type
 * @return string name of mysql field type
 */
function _fx_field_type_to_mysql($fx_type, $fx_length = 0)
{
    $fx_type = strtolower($fx_type);

    switch ($fx_type) {
        case 'int':
        case 'float':
        case 'text':
            $mysql_type = $fx_type;
            break;
        case 'datetime':
        case 'time':
        case 'date':
            $mysql_type = 'int(4)';
            break;
        case 'html':
            $mysql_type = 'text';
            break;
        case 'varchar':
            $fx_length = (int)$fx_length;
            if ($fx_length > 255 || $fx_length <= 0) {
                $fx_length = '255';
            }
            $mysql_type = 'varchar('.$fx_length.')';
            break;
        case 'url':
            $mysql_type = 'text';
            break;
        default:
            $mysql_type = 'varchar(255)';
    }

    return $mysql_type;
}

function _prepare_type_field($f_array)
{
    $f_array['name'] = substr(normalize_string($f_array['name'] ? $f_array['name'] : $f_array['caption']), 0, 32);

    if (is_numeric($f_array['name'][0])) {
        $f_array['name'] = '_'.$f_array['name'];
    }

    $f_array['caption'] = substr($f_array['caption'], 0, 32);
    $f_array['description'] = substr($f_array['description'], 0, 255);
    $f_array['type'] = strtolower($f_array['type']);
	$f_array['metric'] = strtolower($f_array['metric']);
	$f_array['unit'] = strtolower($f_array['unit']);
    $f_array['length'] = (int)$f_array['length'];
    $f_array['mandatory'] = isset($f_array['mandatory']) && $f_array['mandatory'] ? 1 : 0;
    $f_array['sort_order'] = (int)$f_array['sort_order'];

    if (!$f_array['type']) {
        $f_array['type'] = 'varchar';
    }

    switch ($f_array['type']) {
        case 'varchar':
            if ($f_array['length'] > 255 || $f_array['length'] <= 0) {
                $f_array['length'] = 255;
            }
            break;
        case 'int':
            if ($f_array['length'] > 11 || $f_array['length'] <= 0) {
                $f_array['length'] = 11;
            }
            $f_array['default_value'] = (int)$f_array['default_value'];
            break;
        case 'datetime':
        case 'time':
        case 'date':
            $f_array['length'] = 4;
            break;
        case 'text':
        case 'html':
            if ($f_array['length'] > 65536 || $f_array['length'] <= 0) {
                $f_array['length'] = 65536;
            }
            $f_array['default_value'] = '';
            break;
        case 'float':
            $f_array['length'] = 0;
            $f_array['default_value'] = (float)$f_array['default_value'];
            break;
        case 'image':
        case 'file':
        case 'url':
        case 'qr':
        case is_numeric($f_array['type']):
            $f_array['length'] = 0;
            break;
        case 'email':
        case 'password':
            $f_array['length'] = 128;
            break;
        case 'ip':
            $f_array['length'] = 15;
            break;
    }

    return $f_array;
}

function _add_type_field($object_type_id, $field_data, $after = '')
{
    global $OBJECT_BASE_FIELDS;
	global $db_reserved_words;
    global $fx_db;

	$base_fields_instance = $OBJECT_BASE_FIELDS;

    extract($field_data, EXTR_PREFIX_ALL, 'f');
	
    $after = normalize_string($after);
	$f_name = normalize_string($f_name);
	
    if (!$f_name) {
        return new FX_Error(__FUNCTION__, _('Empty field name'));
    }

    if (_table_field_exists(DB_TABLE_PREFIX.'object_type_'.$object_type_id, $f_name)) {
        return new FX_Error(__FUNCTION__, _('Duplicate field name')." [$f_name]");
    }
	
	if (isset($base_fields_instance[$f_name])) {
		return new FX_Error(__FUNCTION__, _('Field name is reserved'));
	}
	
	if (in_array(strtoupper($f_name), $db_reserved_words)) {
		return new FX_Error(__FUNCTION__, _('Field name is reserver as DB keyword'));
	}
	
	if ($f_mandatory && !strlen((string)$f_default_value)) {
		return new FX_Error(__FUNCTION__, _('Default value cannot be empty for mandatory field')." [$f_name]");
	}

    if (strtolower($after) == 'first') {
        $last_base_field = array_pop($base_fields_instance);
        $after = " AFTER {$last_base_field['name']}";
    }
	elseif ($after) {
        $after = " AFTER $after";
    }
	else {
        $after = "";
    }

    $sql_type = _fx_field_type_to_mysql($f_type, $f_length);

	if ($sql_type != 'text') {
    	$sql_default = $f_default_value ? "DEFAULT '$f_default_value'" : "";	
	}

    $query = "ALTER TABLE ".DB_TABLE_PREFIX."object_type_$object_type_id ADD $f_name $sql_type $sql_default $after";

    if ($fx_db->exec($query) === false) {
        if (is_debug_mode()) {
            add_log_message(__FUNCTION__, "SQL Error: $query");
        }
        return new FX_Error(__FUNCTION__, _('SQL Error occured while inserting new field column into the object table')." [$f_name]", $query);
    }

    $pdo = $fx_db->prepare("INSERT INTO ".DB_TABLE_PREFIX."field_type_tbl (object_type_id, name, caption, description, mandatory, type, metric, unit, default_value, length, sort_order) VALUES (:object_type_id, :name, :caption, :description, :mandatory, :type, :metric, :unit, :default_value, :length, :sort_order)");

    $pdo->bindValue(':object_type_id', $object_type_id, PDO::PARAM_INT);
    $pdo->bindValue(':name', $f_name, PDO::PARAM_STR);
    $pdo->bindValue(':caption', $f_caption, PDO::PARAM_STR);
    $pdo->bindValue(':description', $f_description, PDO::PARAM_STR);
    $pdo->bindValue(':mandatory', $f_mandatory, PDO::PARAM_INT);
    $pdo->bindValue(':type', $f_type, PDO::PARAM_STR);
	$pdo->bindValue(':metric', intval($f_metric), PDO::PARAM_INT);
	$pdo->bindValue(':unit', intval($f_unit), PDO::PARAM_INT);
    $pdo->bindValue(':default_value', $f_default_value, PDO::PARAM_STR);
    $pdo->bindValue(':length', $f_length, PDO::PARAM_INT);
    $pdo->bindValue(':sort_order', $f_sort_order, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
            add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
        }
        return new FX_Error(__FUNCTION__, _('SQL Error occured while inserting new field into the types table')." [$f_name]");
    }

    return $f_name;
}

function _update_type_field($object_type_id, $field_data, $after)
{
    global $OBJECT_BASE_FIELDS;
	global $db_reserved_words;
    global $fx_db;

	$base_fields_instance = $OBJECT_BASE_FIELDS;

    extract($field_data, EXTR_PREFIX_ALL, 'f');
	
	$f_name = normalize_string($f_name);
	$after = normalize_string($after);

    if (!$f_name) {
        return new FX_Error(__FUNCTION__, _('Empty field name'));
    }

    if (!_table_field_exists(DB_TABLE_PREFIX.'object_type_'.$object_type_id, $f_name)) {
        return new FX_Error(__FUNCTION__, _('Field does not exists in object table')." [$f_name]");
    }
	
	if (isset($base_fields_instance[$f_name])) {
		return new FX_Error(__FUNCTION__, _('Field name is reserved'));
	}
	
	if (in_array(strtoupper($f_name), $db_reserved_words)) {
		return new FX_Error(__FUNCTION__, _('Field name is reserver as DB keyword'));
	}

	if ($f_mandatory && !strlen((string)$f_default_value)) {
		return new FX_Error(__FUNCTION__, _('Default value cannot be empty for mandatory field')." [$f_name]");
	}

    if (strtolower($after) == 'first') {
        $last_base_field = array_pop($base_fields_instance);
        $after = " AFTER {$last_base_field['name']}";
    }
	elseif ($after) {
        $after = " AFTER $after";
    }
	else {
        $after = "";
    }

    $sql_type = _fx_field_type_to_mysql($f_type, $f_length);

	if ($sql_type != 'text') {
    	$sql_default = $f_default_value ? "DEFAULT '$f_default_value'" : "";	
	}

    $query = "ALTER TABLE ".DB_TABLE_PREFIX."object_type_$object_type_id MODIFY $f_name $sql_type $sql_default $after";

    if ($fx_db->exec($query) === false) {
        if (is_debug_mode()) {
            add_log_message(__FUNCTION__, "SQL Error: $query");
        }
        return new FX_Error(__FUNCTION__, _('SQL Error occured while updating field column in the object table')." [$f_name]", $query);
    }

    $pdo = $fx_db->prepare("UPDATE ".DB_TABLE_PREFIX."field_type_tbl SET caption=:caption, description=:description, mandatory=:mandatory, type=:type, metric=:metric, unit=:unit, default_value=:default_value, length=:length, sort_order=:sort_order WHERE object_type_id=:object_type_id AND name=:name");

    $pdo->bindValue(':object_type_id', $object_type_id, PDO::PARAM_INT);
    $pdo->bindValue(':name', $f_name, PDO::PARAM_STR);
    $pdo->bindValue(':caption', $f_caption, PDO::PARAM_STR);
    $pdo->bindValue(':description', $f_description, PDO::PARAM_STR);
    $pdo->bindValue(':mandatory', $f_mandatory, PDO::PARAM_INT);
    $pdo->bindValue(':type', $f_type, PDO::PARAM_STR);
	$pdo->bindValue(':metric', intval($f_metric), PDO::PARAM_INT);
	$pdo->bindValue(':unit', intval($f_unit), PDO::PARAM_INT);
    $pdo->bindValue(':default_value', $f_default_value, PDO::PARAM_STR);
    $pdo->bindValue(':length', $f_length, PDO::PARAM_INT);
    $pdo->bindValue(':sort_order', $f_sort_order, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
            add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
        }
        return new FX_Error(__FUNCTION__, _('SQL Error occured while updating field into the types table')." [$f_name]");
    }

    _update_field_name_in_components($object_type_id, $old_name, $new_name);

    return true;
}

function _delete_type_field($object_type_id, $field_name)
{
    global $fx_db;

    if (_table_field_exists(DB_TABLE_PREFIX.'object_type_'.$object_type_id, $field_name)) {

        $query = "ALTER TABLE ".DB_TABLE_PREFIX."object_type_$object_type_id DROP $field_name";

        if ($fx_db->exec($query) === false) {
			if (is_debug_mode()) {
				add_log_message(__FUNCTION__, "SQL Error: $query");
			}
			return new FX_Error(__FUNCTION__, _('SQL Error occured while deleting field column from the object table')." [$field_name]", $query);
        }
    }

    $pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."field_type_tbl WHERE object_type_id=:object_type_id AND name=:name");

    $pdo->bindValue(':object_type_id', $object_type_id, PDO::PARAM_INT);
    $pdo->bindValue(':name', $field_name, PDO::PARAM_STR);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
            add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
        }
        return new FX_Error(__FUNCTION__, _('SQL Error occured while deleting field from the types table')." [$field_name]");
    }

    _delete_field_in_components($object_type_id, $field_name);

    return true;
}

/*******************************************************************************
 * Returns information about custom type field
 * @param int $object_type_id id of type
 * @param string $field_name field name
 * @return FX_Error|mixed - information about field or error if not found
 ******************************************************************************/
function get_custom_type_field($object_type_id, $field_name)
{
    global $fx_db;

	$result = $fx_db->select(DB_TABLE_PREFIX.'field_type_tbl')->where(array('object_type_id'=>$object_type_id, 'name'=>$field_name))->limit(1)->select_exec();

	if (is_fx_error($result)) {
		return $result;
	}
	
    if ($result = $fx_db->get()) {
        return $result;
    }

    return new FX_Error(__FUNCTION__, _('Field not found')." [$field_name]");	
/*	

    $pdo = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."field_type_tbl WHERE object_type_id=:object_type_id AND name=:name LIMIT 1");

    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
    $pdo->bindValue(":name", $field_name, PDO::PARAM_STR);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
            add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
        }
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    if ($result = $pdo->fetch()) {
        return $result;
    }

    return new FX_Error(__FUNCTION__, _('Field not found')." [$field_name]");*/
}

/*******************************************************************************
 * Get fields of type
 *
 * @param  int|array $object_type id of object type or array with type info
 * @param string $fields_mode ("all"|"base"|"custom") - what fields should be
 *                            returned
 * @return array|FX_Error - array of fields for this mode or error
 ******************************************************************************/
function get_type_fields($object_type_id, $fields_mode, $filter_fields = false)
{	
    if (!(int)$object_type_id) {
        return new FX_Error(__FUNCTION__, _('Invalid type identifier'));
    }
	
	if (!$fields_mode) {
		$fields_mode = 'all';
	}
	
    if ($fields_mode && !in_array($fields_mode, array('all', 'base', 'custom'))) {
        return new FX_Error(__FUNCTION__, _('Invalid field mode value'));
    }

    if ($fields_mode == 'all' || $fields_mode == 'base') {
        global $OBJECT_BASE_FIELDS;
        $fields = $OBJECT_BASE_FIELDS;
		
		//TODO: find another solution
		//------------------------------------
		$fields['created']['type'] = $fields['modified']['type'] = 'datetime';
		//------------------------------------
    }
	else {
		$fields = array();
	}

    if ($fields_mode == 'all' || $fields_mode == 'custom') {
		
		global $fx_db;

        $pdo = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."field_type_tbl WHERE object_type_id=:object_type_id ORDER BY sort_order");
        $pdo->bindValue(':object_type_id', $object_type_id, PDO::PARAM_INT);

        if (!$pdo->execute()) {
            if (is_debug_mode()) {
                add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
            }
            return new FX_Error(__FUNCTION__, _('SQL Error'));
        }

        foreach ($pdo->fetchAll() as $field) {
            $fields[$field['name']] = $field;

            if (is_numeric($field['type'])) {
                $enum_fields = get_enum_fields($field['type']);
                $fields[$field['name']]['enum'] = !is_fx_error($enum_fields) ? $enum_fields : array();
            }
        }
    }

    return $filter_fields === false ? $fields : filter_field_list($fields);
}

/*******************************************************************************
 * Update set of type custom fields
 * @param int $object_type_id id of type to update
 * @param array $new_fields new fields
 * @param bool $check_old_fields - false if type is new and there are no old fields
 * @return FX_Error|array new fields or error if something wrong
 ******************************************************************************/
function update_type_custom_fields($object_type_id, $new_fields, $check_old_fields = true)
{
    $errors = new FX_Error();
	
    $old_fields = $check_old_fields ? get_type_fields($object_type_id, 'custom') : array();
	
    $new_fields_tmp = $fields_db_types = array();

    foreach ($new_fields as $field_name => $field)
	{
        $fields_db_types[$field['name']] = _fx_field_type_to_mysql($field['type'], $field['length']);

        if (!isset($field['sort_order']) || !is_numeric($field['sort_order'])) {
            $field['sort_order'] = 0;
        }

        $field['type'] = strtolower($field['type']);
        $field['mandatory'] = (bool)$field['mandatory'];
        $field['length'] = (int)$field['length'];

        $new_fields_tmp[$field['name']] = $field;

        if (is_fx_error($field_errors = validate_type_custom_field($field))) {
            foreach ($field_errors->get_error_messages() as $message) {
                $errors->add(__FUNCTION__, $message);
            }
        }
    }

    $new_fields = $new_fields_tmp;
	
    if (!$errors->is_empty()) {
        return $errors;
    }
	
    global $fx_db;
	
    $old_keys = array_keys($old_fields);
    $new_keys = array_keys($new_fields);
	
    array_multisort($old_keys);
    array_multisort($new_keys);
	
    $only_old_fields = array_diff($old_keys, $new_keys);
    $only_new_fields = array_diff($new_keys, $old_keys);

    $updated_fields = array();
	
    foreach (array_intersect($new_keys, $old_keys) as $field_name) {
		
        unset($new_fields[$field_name]["object_type_id"]);
        unset($old_fields[$field_name]["object_type_id"]);
		
        if ($new_fields[$field_name] != $old_fields[$field_name]) {
            $updated_fields[] = $field_name;
        }
    }
	
    foreach ($only_old_fields as $old_field) {
        _delete_field_in_components($object_type_id, $old_field);
    }
	
    foreach ($only_old_fields + $updated_fields as $field_name)
	{
        $query = "ALTER TABLE ".DB_TABLE_PREFIX."object_type_".$object_type_id." DROP COLUMN ".$field_name;
		
		if (!$fx_db->query($query)) {
			if (is_debug_mode()) {
				add_log_message(__FUNCTION__, $query);
			}
			return new FX_Error(__FUNCTION__, _('SQL Error occurred while removing field column from the object table').$query);
		}		
    }
	
    foreach ($updated_fields + $only_new_fields as $field_name)
	{
        $new_field = $new_fields[$field_name];
        $query = "ALTER TABLE ".DB_TABLE_PREFIX."object_type_".$object_type_id." ADD COLUMN ".$new_field["name"]." ".$fields_db_types[$new_field["name"]];

        if ($new_field["mandatory"]) {
            $query .= " NOT NULL";
        }

        if (array_key_exists("default value", $new_field)) {
            $query .= " DEFAULT '".$new_field["default value"]."'";
        }

		if (!$fx_db->query($query)) {
			if (is_debug_mode()) {
				add_log_message(__FUNCTION__, $query);
			}
			return new FX_Error(__FUNCTION__, _('SQL Error occurred while adding field column to the object table'));
		}	
    }

    $delete_stmt = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."field_type_tbl WHERE object_type_id = :object_type_id AND name = :name");
		
    foreach ($only_old_fields as $field_name) {
        $delete_stmt->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
        $delete_stmt->bindValue(":name", $field_name, PDO::PARAM_INT);
		
        if (!$delete_stmt->execute()) {
            if (is_debug_mode()) {
                add_log_message(__FUNCTION__, print_r($delete_stmt->errorInfo(), true));
            }
            return new FX_Error(__FUNCTION__, _('SQL Error'));
        }
    }

    $update_stmt = $fx_db->prepare("UPDATE ".DB_TABLE_PREFIX."field_type_tbl SET caption = :caption, description = :description, mandatory = :mandatory, type = :type, metric = :metric, unit = :unit, default_value = :default_value, length = :length, sort_order = :sort_order WHERE name = :name AND object_type_id = :object_type_id");
	
    $insert_stmt = $fx_db->prepare("INSERT INTO ".DB_TABLE_PREFIX."field_type_tbl (object_type_id, name, caption, description, mandatory, type, metric, unit, default_value, length, sort_order) VALUES (:object_type_id, :name, :caption, :description, :mandatory, :type, :metric, :unit, :default_value, :length, :sort_order)");
    
	if (empty($updated_fields) && empty($only_new_fields)) {
        return $new_fields;
    }

    foreach ($updated_fields as $field_name)
	{
        $field = $new_fields[$field_name];

		$update_stmt->bindValue(":name", $field["name"], PDO::PARAM_STR);
        $update_stmt->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
        $update_stmt->bindValue(":caption", $field["caption"], PDO::PARAM_STR);
        $update_stmt->bindValue(":description", $field["description"], PDO::PARAM_STR);
		$update_stmt->bindValue(":mandatory", $field["mandatory"], PDO::PARAM_INT);
        $update_stmt->bindValue(":type", strtolower($field["type"]), PDO::PARAM_STR);
		$update_stmt->bindValue(':metric', intval($f_metric), PDO::PARAM_INT);
		$update_stmt->bindValue(':unit', intval($f_unit), PDO::PARAM_INT);
        $update_stmt->bindValue(":default_value", $field["default_value"], PDO::PARAM_STR);
        $update_stmt->bindValue(":length", $field["length"], PDO::PARAM_INT);
        $update_stmt->bindValue(":sort_order", $field["sort_order"], PDO::PARAM_INT);

        if (!$update_stmt->execute()) {
            if (is_debug_mode()) { 
				add_log_message(__FUNCTION__, print_r($update_stmt->errorInfo(), true));
            }
            return new FX_Error(__FUNCTION__, _('SQL Error'), 'update existing fields');
        }
    }

    foreach ($only_new_fields as $field_name)
	{
        $field = $new_fields[$field_name];

        $insert_stmt->bindValue(":name", $field["name"], PDO::PARAM_STR);
        $insert_stmt->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
        $insert_stmt->bindValue(":caption", $field["caption"], PDO::PARAM_STR);
        $insert_stmt->bindValue(":description", $field["description"],PDO::PARAM_STR);
        $insert_stmt->bindValue(":mandatory", $field["mandatory"], PDO::PARAM_INT);
        $insert_stmt->bindValue(":type", strtolower($field["type"]), PDO::PARAM_STR);
		$insert_stmt->bindValue(':metric', intval($f_metric), PDO::PARAM_INT);
		$insert_stmt->bindValue(':unit', intval($f_unit), PDO::PARAM_INT);
        $insert_stmt->bindValue(":default_value", $field["default_value"],PDO::PARAM_STR);
        $insert_stmt->bindValue(":length", $field["length"], PDO::PARAM_INT);
        $insert_stmt->bindValue(":sort_order", $field["sort_order"], PDO::PARAM_INT);

        if (!$insert_stmt->execute()) {
            if (is_debug_mode()) {
                add_log_message(__FUNCTION__, print_r($insert_stmt->errorInfo(), true));
            }
            return new FX_Error(__FUNCTION__, _('SQL Error'), 'insert new fields');
        }
    }

    if ($errors->is_empty()) {
        return $new_fields;
	}

    return $errors;
}

/**
 * Change name of custom type field
 * @param int $object_type_id id of type
 * @param string $old_name old field name
 * @param string $new_name new field name
 * @return FX_Error|null
 */
function change_custom_field_name($object_type_id, $old_name, $new_name)
{
	if (!(int)$object_type_id) {
		return new FX_Error(__FUNCTION__, _('Invalid Object Type identifier'));
	}

	if (!$old_name) {
		return new FX_Error(__FUNCTION__, _('Empty old name value'));
	}
	
	if (!$new_name) {
		return new FX_Error(__FUNCTION__, _('Empty new field name value'));
	}
	
	$new_name = normalize_string($new_name);
	
	if (!strlen($new_name)) {
		return new FX_Error(__FUNCTION__, _('Empty normalized field name'));
	}
	
	global $OBJECT_BASE_FIELDS;
	
	if (isset($OBJECT_BASE_FIELDS[$new_name])) {
		return new FX_Error(__FUNCTION__, _('Field name is reserved'));
	}
	
	global $db_reserved_words;
	
	if (in_array(strtoupper($new_name), $db_reserved_words)) {
		return new FX_Error(__FUNCTION__, _('Field name is reserver as DB keyword'));
	}

	if (isset($OBJECT_BASE_FIELDS[$new_name])) {
		return new FX_Error(__FUNCTION__, _('Field name is reserved'));
	}

    $object_type = get_type($object_type_id);

    if (is_fx_error($object_type) || $object_type == false) {
        return new FX_Error(__FUNCTION__, _('Invalid type identifier'));
    }

    if ($object_type['system']) {
        return new FX_Error(__FUNCTION__, _('Unable to rename system type field'));
    }

    $old_field = $object_type['fields'][$old_name];
    $old_field_type = _fx_field_type_to_mysql($old_field['type'], $old_field['length']);

    global $fx_db;

    $pdo = $fx_db->prepare("ALTER TABLE ".DB_TABLE_PREFIX."object_type_".$object_type_id." CHANGE $old_name $new_name $old_field_type");

    if (!$pdo->execute()) {
        return new FX_Error(__FUNCTION__, print_r($fx_db->errorInfo(), true));
    }

    $pdo = $fx_db->prepare("UPDATE ".DB_TABLE_PREFIX."field_type_tbl SET name=:new_name WHERE name=:old_name AND object_type_id=:object_type_id");
    $pdo->bindValue(":new_name", $new_name);
    $pdo->bindValue(":old_name", $old_name);
    $pdo->bindValue(":object_type_id", $object_type_id);

    if (!$pdo->execute()) {
        return new FX_Error(__FUNCTION__, print_r($fx_db->errorInfo(), true));
    }

    _update_field_name_in_components($object_type_id, $old_name, $new_name);
}

/**
 * Function to be called when need to update name of filed in components (query,
 * chart and etc.).
 * For internal usage
 * @param int $object_type_id id of type
 * @param string $old_name old field name
 * @param string $new_name new field name
 */
function _update_field_name_in_components($object_type_id, $old_name, $new_name)
{
    foreach (get_objects_by_type(TYPE_DATA_FORM) as $form)
	{
		if ($form['object_type'] == $object_type_id) {
			$code = json_decode($form['code'], true);

			foreach ($code as $key => $field) {
				if ($field['name'] == $old_name) {
					$new_field = $field;
					$new_field['name'] = $new_name;
					$new_field['caption'] = $new_name;
					$code[] = $new_field;
					unset($code[$key]);
				}
			}
			
			$form['code'] = json_encode($code);
			update_object($form);
		}
    }

    foreach (get_objects_by_type(TYPE_QUERY) as $query)
	{
		if ($query['main_type'] == $object_type_id || strpos($query['joined_types'], $object_type_id) !== false) {
			
			$code = json_decode($query['code'], true);

			foreach ($code as $key => $field) {
				if ($field['name'] == $old_name && $field['object_type_id'] == $object_type_id) {
					$new_field = $field;
					$new_field['name'] = $new_name;
					$new_field['caption'] = $new_name;
					$new_field['alias'] = str_replace($old_name, $new_name, $field['alias']);
					$code[] = $new_field;
					unset($code[$key]);
				}
			}
			
			$query['code'] = json_encode($code);
			update_object($query);
		}
    }

    foreach (get_objects_by_type(TYPE_FSM_EVENT) as $fsm)
	{
		if ($fsm['object_type'] == $object_type_id) {
			if ($fsm['object_field'] == $old_name) {
				$fsm['object_field'] = $new_name;
				//update_object($fsm);
			}
	
			$code = json_decode($fsm['event_condition'], true);
	
			if ($code['field'] == $old_name) {
				$code['field'] = $new_name;
				$fsm['event_condition'] = json_encode($code);
				//update_object($fsm);
			}
		}
    }	
}

/**
 * Function to be called when need to delete field from components.
 * For internal usage
 * @param int $object_type_id id of object type
 * @param string $field_name name of field to be deleted
 */
function _delete_field_in_components($object_type_id, $field_name)
{
    foreach (get_objects_by_type(TYPE_DATA_FORM) as $form)
	{
        $code = json_decode($form['code'], true);
        $flag = false;
        $new_code = array();
        foreach ($code as $element) {
            if ($element['field'] == $field_name) {
                $flag = true;
            }
			else {
                $new_code[] = $element;
            }
        }
        if ($flag) {
            $form['code'] = json_encode($new_code);
            update_object($form);
        }
    }

    foreach (get_objects_by_type(TYPE_QUERY) as $query)
	{
		foreach ((array)json_decode($query['code'], true) as $i => $field) {
			if ($field['name'] == $field_name) {
				unset($code[$i]);
				$query['code'] = json_encode($code);
				update_object($query);
			}
		}
    }

    foreach (get_objects_by_type(TYPE_FSM_EVENT) as $fsm)
	{
        if ($fsm['object_field'] == $field_name) {
            delete_object(TYPE_FSM_EVENT, $fsm['object_id']);
        }
    }
}

/*******************************************************************************
 * Get types defined in specified schema
 * @param int $schema_id - schema to search
 * @param string $fields_mode ("all"|"base"|"custom") - what fields should be returned
 * @return array|Fx_error - types in schema or error
 ******************************************************************************/
function get_schema_types($schema_id, $fields_mode = 'all')
{
    global $fx_db;

    $pdo = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE schema_id=:schema_id ORDER BY name");
    $pdo->bindValue(":schema_id", $schema_id, PDO::PARAM_INT);

    $types = array();

    if ($pdo->execute()) {
        foreach ($pdo->fetchAll() as $type) {
            if ($fields_mode != 'none') {
                $fields = get_type_fields($type['object_type_id'], $fields_mode);
                $type['fields'] = !is_fx_error($fields) ? $fields : array();
            }
            $types[$type['object_type_id']] = $type;
        }
    }

    return $types;
}

/*******************************************************************************
 * Get type by id
 * @param int $object_type_id - id of type
 * @param string $fields_mode ("all"|"base"|"custom") - what fields should be returned
 * @return array|bool - type or false if type not found
 ******************************************************************************/
function get_type($object_type_id, $fields_mode = 'all')
{
    global $fx_db;

    $pdo = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE object_type_id = :object_type_id LIMIT 1");
    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

    if ($pdo->execute()) {
        if ($type = $pdo->fetch()) {
            $type['fields'] = array();
			
            if ($fields_mode != 'none') {
                $type['fields'] = get_type_fields($object_type_id, $fields_mode);
            }
            return $type;
        }
    }
	else {
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    return false;
}

/*******************************************************************************
 * Get type id by name and schema
 *
 * @param int $schema_id - id of schema to search
 * @param string $type_name - name to search
 * @return int - id or 0 if type not found
 *******************************************************************************
 */
function get_type_id_by_name($schema_id, $type_name = '')
{
    if (!$type_name) {
        return 0;
    }

    global $system_types;

    if ($schema_id == 0 && array_key_exists($type_name, $system_types)) {
        return $system_types[$type_name];
    }

    global $fx_db;

    $pdo = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE name=:type_name AND schema_id=:schema_id LIMIT 1");
    $pdo->bindValue(":type_name", $type_name, PDO::PARAM_STR);
    $pdo->bindValue(":schema_id", $schema_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return 0;
    }

	if ($row = $pdo->fetch()) {
		return $row['object_type_id'];
	}

	return 0;
}

/*******************************************************************************
 * Get type name by the object_type_id
 *
 * @param int $object_type_id - id to search
 * @return false|string - string or false if type not found
 ******************************************************************************/
function get_type_name_by_id($object_type_id)
{
    global $system_types;

    if ($name = array_search($object_type_id, $system_types)) {
        return $name;
    }

    global $fx_db;

    $pdo = $fx_db->prepare("SELECT name FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE object_type_id=:object_type_id LIMIT 1");
    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return false;
    }

	if ($row = $pdo->fetch()) {
		return $row['name'];
	}

	return false;
}

/*******************************************************************************
 * Get type revisions number by id
 * @param int $object_type_id - id to search
 * @return FX_Error|int - revisions number or error if type not found
 ******************************************************************************/
function get_type_revisions_number($object_type_id)
{
    global $fx_db;

    $pdo = $fx_db->prepare("SELECT revisions_number FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE object_type_id = :object_type_id LIMIT 1");
    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

    if ($pdo->execute()) {
        if ($type = $pdo->fetch()) {
            return $type['revisions_number'] ? $type['revisions_number'] : 1;
        }
    }

    return false;
}

/*******************************************************************************
 * Check type existing
 * @param int $object_type_id to check
 * @return bool - true if type exist
 * *****************************************************************************
 */
function type_exists($object_type_id)
{
    global $fx_db;

    $pdo = $fx_db->prepare("SELECT object_type_id FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE object_type_id=:object_type_id LIMIT 1");
    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        return false;
    }

    if ($row = $pdo->fetch()) {
        return $row["object_type_id"];
    }
	else {
        return false;
    }
}

/*******************************************************************************
 * Delete type with specified id from DB
 * @param int $object_type_id id of type to delete
 * @return FX_Error|bool true if deleted and error if not found
 * *****************************************************************************
 */
function delete_type($object_type_id)
{
    if (!(int)$object_type_id) {
        return new FX_Error(__FUNCTION__, _('Please set Object Type ID'));
    }

    $type = get_type($object_type_id, 'none');

    if (is_fx_error($type) || $type == false) {
        return new FX_Error(__FUNCTION__, _('Type not found'));
    }

/*    if ($type['system']) {
        return new FX_Error(__FUNCTION__, _('Unable to delete system type'));
    }*/

	$ss = get_schema_settings($type['schema_id']);

 	$type_links = get_type_links($object_type_id);

	if (!is_fx_error($type_links)) {
		foreach ((array)$type_links as $type_id => $link) {
			if ($link['strength'] == LINK_STRONG && ($link['relation'] == RELATION_1_1 || $link['relation'] == RELATION_1_N)) {
				switch (intval($ss['link_option'])) {
					case LINK_OPTION_DELETE:
						$res = delete_type($type_id);
						if (is_fx_error($res)) {
							return new FX_Error(__FUNCTION__, _('Removing strongly inked type error').'. '.$res->get_error_message());
						}
					break;
					case LINK_OPTION_FORBID:
					default:
						return new FX_Error(__FUNCTION__, _('Schema settings are not allow to delete this type, because it has strongly linked types'));
				}
			}
		}
	}
	else {
		return $type_links; 
	}

    global $fx_db;

    $errors = new FX_Error();

	_delete_type_from_components($object_type_id);

	$pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."field_type_tbl WHERE object_type_id=:object_type_id");
	$pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

	if (!$pdo->execute()) {
		$errors->add(__FUNCTION__, _('SQL Error').': '._('delete type fields'));
	}
	
	$type_links = get_type_links($object_type_id);

	$pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."link_type_tbl WHERE object_type_1_id=:object_type_id OR object_type_2_id=:object_type_id");
	$pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

	if (!$pdo->execute()) {
		$errors->add(__FUNCTION__, _('SQL Error').': '._('delete link types'));
	}

	$pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."link_tbl WHERE object_type_1_id=:object_type_id OR object_type_2_id=:object_type_id");
	$pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

	if (!$pdo->execute()) {
		$errors->add(__FUNCTION__, _('SQL Error').': '._('delete links'));
	}

	$fx_db->exec("DROP TABLE ".DB_TABLE_PREFIX."object_type_".$object_type_id);
	$fx_db->exec("DROP TABLE ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev");

	if (is_dir(CONF_UPLOADS_DIR.'/'.$object_type_id)) {
		full_del_dir(CONF_UPLOADS_DIR.'/'.$object_type_id);
	}

	clear_query_cache_by_type($object_type_id);
	clear_user_cache();

	if (is_system_type($object_type_id)) {
		delete_fx_option('system_types_cache');
	}

	if ($errors->is_empty()) {
		$pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE object_type_id=:object_type_id");
		$pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

		if (!$pdo->execute()) {
			return new FX_Error(__FUNCTION__, _('SQL Error').': '._('delete type'));
		}
		
		return true;
	}
	else {
		return $errors;
	}
}

function _delete_type_from_components($object_type_id)
{
	/*******************************************************************************/

	$objects = get_type_component($object_type_id, 'query');
	if (is_fx_error($objects)) {
		add_log_message('delete_components_queries', $objects->get_error_message());
	}
	else {
		foreach ($objects as $id) {
			delete_object(TYPE_QUERY, $id);
			
			//Delete chart with this query
			
			global $fx_db;
			
			$fx_db->select(DB_TABLE_PREFIX."object_type_".TYPE_CHART, 'object_id')->where(array('query_id'=>$id));
			if (!is_fx_error($fx_db->select_exec())) {
				foreach ($fx_db->get_all() as $row) {
					delete_object(TYPE_CHART,  $row['object_id']);
				}
			}
		}
	}

	/*******************************************************************************/
	
	$objects = get_type_component($object_type_id, 'form');
	
	if (is_fx_error($objects)) {
		add_log_message('delete_components_forms', $objects->get_error_message());
	}
	else {
		foreach ($objects as $id) {
			delete_object(TYPE_DATA_FORM, $id);
		}	
	}
	/*******************************************************************************/
	
	$objects = get_type_component($object_type_id, 'fsm');
	if (is_fx_error($objects)) {
		add_log_message('delete_components_fsm_events', $objects->get_error_message());
	}
	else {
		foreach ($objects as $id) {
			delete_object(TYPE_FSM_EVENT, $id);
		}	
	}
	
	/*******************************************************************************/
}

/*******************************************************************************
 * Delete all types from specified schema in DB
 * @param int $schema_id id of schema
 * @return FX_Error|int number or deleted types or error
 ******************************************************************************/
function delete_schema_types($schema_id)
{
    if (!(int)$schema_id) {
        return new FX_Error(__FUNCTION__, _('Please set Schema ID'));
    }

    if (!object_exists(get_type_id_by_name, $schema_id)) {
        return new FX_Error(__FUNCTION__, _('Specified schema does not exists'));
    }

    $schema_types = array_keys(get_schema_types($schema_id, 'none'));

    if (!count($schema_types)) {
        return 0;
    }

    global $fx_db;
	
    $pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE schema_id=:schema_id");
    $pdo->bindValue(":schema_id", $schema_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        return new FX_Error(__FUNCTION__, _('SQL Error').': '._('delete schema types'));
    }

    if ($pdo->rowCount() > 0) {
        $errors = new FX_Error();

        if ($pdo->exec("DELETE FROM ".DB_TABLE_PREFIX."field_type_tbl WHERE object_type_id IN (".implode(',', $schema_types).")") === false) {
            $errors->add(__FUNCTION__, _('SQL Error').': '._('delete link types'));
        }

        if ($pdo->exec("DELETE FROM ".DB_TABLE_PREFIX."link_type_tbl WHERE object_type_1_id IN (".implode(',', $schema_types).") OR object_type_2_id IN (".implode(',', $schema_types).")") === false) {
            $errors->add(__FUNCTION__, _('SQL Error').': '._('delete link types'));
        }

        if ($pdo->exec("DELETE FROM ".DB_TABLE_PREFIX."link_tbl WHERE object_type_1_id IN (".implode(',', $schema_types).") OR object_type_2_id IN (".implode(',', $schema_types).")") === false) {
            $errors->add(__FUNCTION__, _('SQL Error').': '._('delete links'));
        }

        for ($i = 0; $i < count($schema_types); $i++) {
            $fx_db->exec("DROP TABLE ".DB_TABLE_PREFIX."object_type_".$schema_types[$i]);
            $fx_db->exec("DROP TABLE ".DB_TABLE_PREFIX."object_type_".$schema_types[$i]."_rev");
        }

		clear_query_cache_by_schema($schema_id);
		clear_user_cache();
		delete_fx_option('system_types_cache');

        return $errors->is_empty() ? count($schema_types) : $errors;
    }
	else {
        return new FX_Error(__FUNCTION__, _('Unable to delete types'));
    }
}

/*******************************************************************************
 * Is type system
 * @param int $object_type_id id of type check
 * @return bool|FX_Error is type system or error
 ******************************************************************************/
function is_system_type($object_type_id)
{
	global $fx_db;	
    global $system_types;

    if (array_search($object_type_id, $system_types) !== false) {
        return true;
    }

    $pdo = $fx_db->prepare("SELECT system FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE object_type_id=:object_type_id LIMIT 1");
    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        return false;
    }

    if ($type = $pdo->fetch()) {
        return $type['system'] > 0 ? true : false;
    }

    return false;
}

/**
 * Return array of types marked as system
 * @return array|FX_Error
 */
function get_system_types()
{
	global $fx_db;

    $pdo = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE system > 0");

    $result = array();

    if ($pdo->execute()) {
        foreach ($pdo->fetchAll() as $row) {
            $result[$row['object_type_id']] = $row;
        }
    }

    return $result;
}

/**
 * Create default query for type
 * @param mixed $object_type numeric Object Type ID or type array
 * @return FX_Error|string result of query object creation
 */
function create_default_query($object_type)
{
	if (is_numeric($object_type)) {
		$object_type = get_type($object_type);
	}

	if (is_fx_error($object_type) && !is_array($object_type)) {
		return new FX_Error(__FUNCTION__, 'Invalid object type format');
	}
	
	$object_type_id = $object_type['object_type_id'];

	if ($object_type['default_query_id'] && object_exists(TYPE_QUERY, $object_type['default_query_id'])) {
		return $object_type['default_query_id'];
	}

    $query_object = _build_default_query_object($object_type);
	
    $query_id = add_object($query_object);

	if (!is_fx_error($query_id)) {
		global $fx_db;
		$result = $fx_db->update(DB_TABLE_PREFIX.'object_type_tbl', array('default_query_id'=>$query_id), array('object_type_id'=>$object_type_id));
		if (is_fx_error($result)) {
			delete_object(TYPE_QUERY, $query_id);
			return $result;
		}
	}

	return $query_id;
}

function update_default_query($object_type, $new_fields)
{
	if (is_numeric($object_type)) {
		$object_type = get_type($object_type);
	}
	
	if (is_fx_error($object_type) && !is_array($object_type)) {
		return new FX_Error(__FUNCTION__, 'Invalid object type format');
	}

	if ($object_type['default_query_id'] && object_exists(TYPE_QUERY, $object_type['default_query_id'])) {
		$query_object = _build_default_query_object($object_type, $new_fields);
		$query_object['object_id'] = $object_type['default_query_id'];
		return update_object($query_object);	
	}
	else {
		return create_default_query($object_type);
	}
	
	
/*	
	if (!$object_type['default_query_id'] || !object_exists(TYPE_QUERY, $object_type['default_query_id'])) {
		return create_default_query($object_type);
	}*/
}

function _build_default_query_object($object_type, $new_fields = array())
{
    global $OBJECT_BASE_FIELDS;

    $code = $new_fields ? _get_default_query_code($object_type['object_type_id']) : array();

    foreach ($object_type['fields'] as $name => $options)
	{
		$default_fields = array(
			'display_name',
			'object_id',
			//'created',
			//'modified'
		);
		
        if ($OBJECT_BASE_FIELDS[$name] && !in_array($name, $default_fields)) {
            continue;
        }
		
		if ($new_fields && !in_array($name, $new_fields)) {
			continue;
		}

        $code[] = array(
            'order' => 'none',
            'criteria' => '',
            'aggregation' => '',
            'alias' => normalize_string($object_type['name'].'_'.$name),
            'name' => $name,
            'type' => strtolower($options['type']),
            'caption' => $options['caption'],// $object_type['display_name'].' '.$options['caption'],
            'object_type_id' => $object_type['object_type_id'],
            'parent_type' => 0);
    }

    $query_object = array(
        'name' => $object_type['name'].'_default_query',
        'object_type_id' => TYPE_QUERY,
        'display_name' => $object_type['display_name'].' default query',
        'schema_id' => $object_type['schema_id'],
        'main_type' => $object_type['object_type_id'],
        'joined_type' => 0,
        'code' => json_encode($code));	
		
	return $query_object;
}

function _get_default_query_code($object_type_id)
{
	$type = get_type($object_type_id);
	
	if (is_fx_error($type)) {
		return false;
	}
	
	$default_query = get_object(TYPE_QUERY, $type['default_query_id']);
	
	$code = json_decode($default_query['code'], true);
	
	return is_array($code) ? $code : array();
}

function _get_default_form_code($object_type_id)
{ 
	$type = get_type($object_type_id);
	
	//echo 1;

	if (is_fx_error($type)) {
		return false;
	}
	//echo 1;
	if (!$type['default_form_id']) {
		return false;
	}
//echo 1;
	$default_form = get_object(TYPE_DATA_FORM, $type['default_form_id']);
//echo 1;
	$code = json_decode($default_form['code'], true);
//echo 1;
	return is_array($code) ? $code : array();
}

/**
 * Create default form object for object type
 * @param array $object_type object type array
 * @return FX_Error|string result of form object creation
 */
function create_default_form($object_type)
{
	if (is_numeric($object_type)) {
		$object_type = get_type($object_type);
	}
	
	if (is_fx_error($object_type) && !is_array($object_type)) {
		return new FX_Error(__FUNCTION__, _('Invalid object type format'));
	}	

	if ($object_type['default_form_id'] && object_exists(TYPE_DATA_FORM, $object_type['default_form_id'])) {
		return $object_type['default_form_id'];
	}

	$form_object = _build_default_form_object($object_type);

    $form_id = add_object($form_object);

	if (!is_fx_error($form_id)) {
		global $fx_db;
		$result = $fx_db->update(DB_TABLE_PREFIX.'object_type_tbl', array('default_form_id'=>$form_id), array('object_type_id'=>$object_type['object_type_id']));
		if (is_fx_error($result)) {
			delete_object(TYPE_DATA_FORM, $form_id);
			return $result;
		}
	}

	return $form_id;
}

function update_default_form($object_type, $new_fields)
{
	if (is_numeric($object_type)) {
		$object_type = get_type($object_type);
	}

	if (is_fx_error($object_type) && !is_array($object_type)) {
		return new FX_Error(__FUNCTION__, _('Invalid object type format'));
	}
	
	$form_id = $object_type['default_form_id'];

	if ($form_id && object_exists(TYPE_DATA_FORM, $form_id)) {	
		$form_object = _build_default_form_object($object_type, $new_fields);
		$form_object['object_id'] = $form_id;
		
		
		
		return update_object($form_object);		
	}
	else {
		return create_default_form($object_type);
	}
}

function _build_default_form_object($object_type, $new_fields = array())
{
    global $OBJECT_BASE_FIELDS;

    $control_types = array(
        "boolean" => "checkbox",
        "datetime" => "datepicker",
        "date" => "datepicker",
        "image" => "imageSelect",
        "file" => "fileSelect",
    );

    $form_subcode = $new_fields ? _get_default_form_code($object_type['object_type_id']) : array();

    foreach ($object_type["fields"] as $name => $options) {
		
        if ($OBJECT_BASE_FIELDS[$name] && $name != "display_name") {
            continue;
        }
		
		if ($new_fields && !in_array($name, $new_fields)) {
			continue;
		}		
		
        if (is_numeric($options["type"])) {
            $control_type = "dropdown";
        }
		else {
            if ($control_types[$options["type"]]) {
                $control_type = $control_types[$options["type"]];
            }
			else {
                $control_type = "textbox";
            }
        }

		$form_subcode[] = array_merge(array("palette" => "fields", "readonly" => false, "control-type" => $control_type), $options);
    }

	$form_object = array(
		'name' => $object_type['name'].'_default_form',
		'object_type_id' => TYPE_DATA_FORM,
		'display_name' => $object_type['display_name'].' default form',
		'schema_id' => $object_type['schema_id'],
		'object_type' => $object_type['object_type_id'],
		'filter_by_set' => 1,
		'code' => json_encode($form_subcode));
	
	return $form_object;
}

function filter_field_list($fields = array())
{
	if (!is_array($fields)) {
		return $fields;
	}

	//TODO: add option into the settings where user can change fields filter 
	$exclude = array('set_id', 'schema_id', 'system');
	
	foreach($exclude as $field) {
		if (array_key_exists($field, $fields)) {
			unset($fields[$field]);
		}
	}
	
	return $fields;
}