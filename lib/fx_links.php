<?php

/*******************************************************************************
 * Link two objects. Objects types must be linked before call
 * @param $object_type_1_id int id of first object
 * @param $object_1_id int type id of first object
 * @param $object_type_2_id int id of second object
 * @param $object_2_id int type id of second object
 * @return bool|FX_Error `true` or error if link is invalid
 *******************************************************************************
 */
function add_link($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id, $meta = '')
{
	if (link_exists($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id)) {
		return true;
	}
		
    $validation_result = validate_link($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id);

    if(is_fx_error($validation_result)) {
        return $validation_result;
    }
	
	$meta = is_array($meta) ? serialize($meta) : $meta;
	
    global $fx_db;
	
    if(_is_link_real_orintation($object_type_1_id, $object_type_2_id)) {
        $pdo = $fx_db->prepare("INSERT INTO ".DB_TABLE_PREFIX."link_tbl
        (object_type_1_id, object_1_id, object_type_2_id, object_2_id, meta)
        VALUES
        (:object_type_1_id, :object_1_id, :object_type_2_id, :object_2_id, :meta)");
    }
	else {
        $pdo = $fx_db->prepare("INSERT INTO ".DB_TABLE_PREFIX."link_tbl
        (object_type_1_id, object_1_id, object_type_2_id, object_2_id, meta)
        VALUES
        (:object_type_2_id, :object_2_id, :object_type_1_id, :object_1_id, :meta)");
    }
	
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_1_id", $object_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_2_id", $object_2_id, PDO::PARAM_INT);
	$pdo->bindValue(":meta", $meta, PDO::PARAM_STR);

    if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

	// Clear cache
	//================================================================ 
	clear_user_cache();
	clear_query_cache_by_type($object_type_1_id);
	clear_query_cache_by_type($object_type_2_id);
	//================================================================ 

    return true;
}

/**
 * Get possible links for obejct only by type id (e.g. new object of this type)
 * @param int $object_type_id if of type
 * @param int|null $linked_object_type_id id of type if only links to specified
 *                                        type objects needed
 * @return array|FX_Error result or error
 */
function get_possible_links_by_type($object_type_id, $linked_object_type_id = null, $schema_id = false)
{
    if($linked_object_type_id) {
        $types = array($linked_object_type_id => array("relation" => get_link_relation($object_type_id, $linked_object_type_id)));
    }
	else {
        $types = get_type_links($object_type_id);
    }
	
    $sub_queries = array();
	
    global $fx_db;

    foreach($types as $type_id => $options)
    {
        $relation = $options["relation"];
		$schema_condition = $schema_id ? "(schema_id=".(int)$schema_id." OR schema_id=0) AND" : "";

        if ($relation == RELATION_1_1)
        {
            $sub_queries[] =
                "SELECT object_id, ".$type_id." AS object_type_id, ".$relation." as relation,name, display_name, set_id, schema_id
                    FROM ".DB_TABLE_PREFIX."object_type_".$type_id."
                    WHERE $schema_condition object_id NOT in (
                        SELECT object_1_id FROM ".DB_TABLE_PREFIX."link_tbl
                        WHERE object_type_1_id = ".$type_id."
                        AND object_type_2_id = :object_type_id
                    UNION
                        SELECT object_2_id FROM ".DB_TABLE_PREFIX."link_tbl
                        WHERE object_type_2_id = ".$type_id."
                        AND object_type_1_id = :object_type_id)";
        }
        elseif ($relation == RELATION_N_1 || $relation == RELATION_N_N)
        {
            $sub_queries[] = "SELECT object_id, ".$type_id." AS object_type_id, ".$relation." as relation,name, display_name, set_id, schema_id FROM ".DB_TABLE_PREFIX."object_type_".$type_id;
        }
        elseif ($relation == RELATION_1_N)
        {
            $sub_queries[] =
                "SELECT object_id, ".$type_id." AS object_type_id, ".$relation." as relation,name, display_name, set_id, schema_id
                FROM ".DB_TABLE_PREFIX."object_type_".$type_id."
                WHERE $schema_condition object_id NOT in (
                    SELECT object_1_id FROM ".DB_TABLE_PREFIX."link_tbl
                    WHERE object_type_1_id = ".$type_id."
                    AND object_type_2_id = :object_type_id
                    UNION
                    SELECT object_2_id FROM ".DB_TABLE_PREFIX."link_tbl
                    WHERE object_type_2_id = ".$type_id."
                    AND object_type_1_id = :object_type_id)";
        }
    }

    if (!count($sub_queries)) {
        return array();
    }
	
    $pdo = $fx_db->prepare(join("\n UNION \n", $sub_queries));
    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
	
    if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
    $result = array();

    foreach($pdo->fetchAll() as $row) {
        $cur_type_id = $row["object_type_id"];

        if(!isset($result[$cur_type_id])) {
            $result[$cur_type_id] = array();
        }
		
        $result[$cur_type_id][$row["object_id"]] = array(
			"relation" => $row["relation"],
			'strength' => $types[$cur_type_id]['strength'],
			"schema_id" => $row["schema_id"],
			"set_id" => $row["set_id"],
			"name" => $row["name"],
			"display_name" => $row["display_name"],
			"actuality" => "possible");

    }

    return $result;
}

/**
 * Get possible links for specified object
 * @param $object_type_id type id of obejct
 * @param $object_id id of object
 * @param  int|null $linked_object_type_id linked type id if only links for some
 * single type needed. `null` to get links to all types
 * @return array|FX_Error result links or error
 */
function get_possible_links($object_type_id, $object_id, $linked_object_type_id = null, $schema_id = false)
{
    if ($linked_object_type_id) {
        $types = array($linked_object_type_id => array("relation" => get_link_relation($object_type_id, $linked_object_type_id)));
    }
    else {
        $types = get_type_links($object_type_id);
    }

    $sub_queries = array();

    global $fx_db;

    foreach ($types as $type_id => $options)
    {
        $relation = $options["relation"];
		$schema_condition = $schema_id ? "(schema_id=".(int)$schema_id." OR schema_id=0) AND" : "";
		
		switch ($relation)
		{
			case RELATION_1_1:

				$pdo = $fx_db->prepare("SELECT NULL FROM ".DB_TABLE_PREFIX."link_tbl
										 WHERE ((object_type_1_id=:object_type_id AND object_1_id=:object_id AND object_type_2_id=:linked_type_id)
										 		OR 
												(object_type_2_id=:object_type_id AND object_2_id=:object_id AND object_type_1_id=:linked_type_id))");
				
				$pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
				$pdo->bindValue(":linked_type_id", $type_id, PDO::PARAM_INT);
				$pdo->bindValue(":object_id", $object_id, PDO::PARAM_INT);
	
				if (!$pdo->execute()) {
					if (is_debug_mode()) {
						add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
					}
					return new FX_Error(__FUNCTION__, _('SQL Error occured').'. '._('Relation 1-1'));
				}
	
				if (!$pdo->rowCount())
				{
					$sub_queries[] =
					"SELECT object_id, ".$type_id." as object_type_id, ".$relation." as relation,name, display_name, set_id, schema_id
					FROM ".DB_TABLE_PREFIX."object_type_".$type_id."
					WHERE $schema_condition object_id NOT in (
						SELECT object_1_id FROM ".DB_TABLE_PREFIX."link_tbl
						WHERE object_type_1_id = ".$type_id."
						AND object_type_2_id = :object_type_id
						UNION
						SELECT object_2_id FROM ".DB_TABLE_PREFIX."link_tbl
						WHERE object_type_2_id = ".$type_id."
						AND object_type_1_id = :object_type_id)";
				}

			break;
			case RELATION_1_N:

				$sub_queries[] = "SELECT object_id, ".$type_id." as object_type_id, ".$relation." as relation,name, display_name, set_id, schema_id
								  FROM ".DB_TABLE_PREFIX."object_type_".$type_id."
								  WHERE $schema_condition object_id NOT in (
									  SELECT object_1_id FROM ".DB_TABLE_PREFIX."link_tbl
									  WHERE object_type_1_id = ".$type_id."
									  AND object_type_2_id = :object_type_id
									  UNION
									  SELECT object_2_id FROM ".DB_TABLE_PREFIX."link_tbl
									  WHERE object_type_2_id = ".$type_id."
									  AND object_type_1_id = :object_type_id)";
	
			break;
			case RELATION_N_1:
				$pdo = $fx_db->prepare("SELECT NULL FROM ".DB_TABLE_PREFIX."link_tbl
										 WHERE ((object_type_1_id=:object_type_id AND object_1_id=:object_id AND object_type_2_id=:linked_type_id)
										 OR (object_type_2_id=:object_type_id AND object_2_id=:object_id AND object_type_1_id=:linked_type_id))");
	
				$pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
				$pdo->bindValue(":linked_type_id", $type_id, PDO::PARAM_INT);
				$pdo->bindValue(":object_id", $object_id, PDO::PARAM_INT);
	
				if(!$pdo->execute()) {
					if (is_debug_mode()) {
						add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
					}
					return new FX_Error(__FUNCTION__, _('SQL Error occured').'. '._('Relation N-1'));
				}

				if (!$pdo->rowCount()) {
					$sub_queries[] = "SELECT object_id, ".$type_id." as object_type_id, ".$relation." as relation,name, display_name, set_id, schema_id
									  FROM ".DB_TABLE_PREFIX."object_type_".$type_id."
									  WHERE $schema_condition object_id NOT in (
										  SELECT object_1_id FROM ".DB_TABLE_PREFIX."link_tbl
										  WHERE object_type_1_id = ".$type_id."
										  AND object_type_2_id = :object_type_id
										  AND object_2_id = :object_id
										  UNION
										  SELECT object_2_id FROM ".DB_TABLE_PREFIX."link_tbl
										  WHERE object_type_2_id = ".$type_id."
										  AND object_type_1_id = :object_type_id
										  AND object_1_id = :object_id)";
				}
			break;
			case RELATION_N_N:
				$sub_queries[] = "SELECT object_id, ".$type_id." as object_type_id, ".$relation." as relation,name, display_name, set_id, schema_id
								  FROM ".DB_TABLE_PREFIX."object_type_".$type_id."
								  WHERE $schema_condition object_id NOT in (
									  SELECT object_1_id FROM ".DB_TABLE_PREFIX."link_tbl
									  WHERE object_type_1_id = ".$type_id."
									  AND object_type_2_id = :object_type_id
									  AND object_2_id = :object_id
									  UNION
									  SELECT object_2_id FROM ".DB_TABLE_PREFIX."link_tbl
									  WHERE object_type_2_id = ".$type_id."
									  AND object_type_1_id = :object_type_id
									  AND object_1_id = :object_id)";			
			break;
			default:
				return new FX_Error(__FUNCTION__, _('Invalid link relation'));
		}
    }
	
    if (!count($sub_queries)) {
        return array();
    }
	
    $pdo = $fx_db->prepare(join("\n UNION \n", $sub_queries));
    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_id", $object_id, PDO::PARAM_INT);

    if(!$pdo->execute())
    {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error occured').'. '._('Get links'));
    }

    $result = array();

    foreach($pdo->fetchAll() as $row) {
        $id = $row['object_type_id'];

        if(!isset($result[$id])) {
            $result[$id] = array();
        }
		
        $result[$id][$row['object_id']] = array(
			'relation' => $row['relation'],
			'strength' => $types[$id]['strength'],
			'schema_id' => $row['schema_id'],
			'set_id' => $row['set_id'],
			'name' => $row['name'],
			'display_name' => $row['display_name'],
			'actuality' => 'possible');
    }

    return $result;
}

/**
 * Internal function for possible link validation
 * @param int $object_type_1_id id of 1st type
 * @param int $object_1_id id of 1st object
 * @param int $object_type_2_id id of 2nd type
 * @param int $object_2_id id of 2nd object
 * @return FX_Error list or errors (can be empty)
 */
function _get_types_and_objects_errors($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id)
{
    $errors = new FX_Error();

    if (type_exists($object_type_1_id)) {
        if (!object_exists($object_type_1_id, $object_1_id)) {
            $errors->add(__FUNCTION__, "There is no id = $object_1_id in $object_type_1_id type");
        }
    }
    else {
        $errors->add(__FUNCTION__, "Wrong type id = $object_type_1_id");
    }

    if (type_exists($object_type_2_id)) {
        if (!object_exists($object_type_2_id, $object_2_id)) {
            $errors->add(__FUNCTION__, "There is no id = $object_2_id in $object_type_2_id type");
        }
    }
    else {
        $errors->add(__FUNCTION__, "Wrong type id = $object_type_2_id");
    }

    return $errors;
}

/********************************************************************************
 * Check is linking of types is ok
 * @param int $object_type_1_id id of first type
 * @param int $object_type_2_id id of second type
 * @return bool|FX_Error `true` or error if link is invalid
 *******************************************************************************/
function validate_link_type($object_type_1_id, $object_type_2_id, $check_exist = true)
{
    if($check_exist && link_type_exists($object_type_1_id, $object_type_2_id)) {
        return new FX_Error(__FUNCTION__, _('Link already exists'));
    }
    return true;
}

/********************************************************************************
 * Check is linking of objects is ok
 * @param int $object_type_1_id id of first object
 * @param int $object_1_id type id of first object
 * @param int $object_type_2_id int id of second object
 * @param int $object_2_id type id of second object
 * @return bool|FX_Error `true` or error if link is invalid
 *******************************************************************************/
function validate_link($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id)
{
    $errors = _get_types_and_objects_errors($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id);
    
	if(!$errors->is_empty()) {
        return $errors;
    }

    $type_relation =  get_link_relation($object_type_1_id, $object_type_2_id);

    if ($type_relation) {
        if($type_relation == RELATION_N_N) {
            if(link_exists($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id)) {
                return new FX_Error(__FUNCTION__, _('Objects already linked'));
            }
            return true;
        }
		
        global $fx_db;
		
        if ($type_relation == RELATION_1_1)
        {
            $pdo = $fx_db->prepare("SELECT NULL FROM ".DB_TABLE_PREFIX."link_tbl
            WHERE (object_type_1_id = :object_type_1_id AND object_1_id = :object_1_id AND object_type_2_id = :object_type_2_id)
            OR (object_type_1_id = :object_type_2_id AND object_1_id = :object_2_id AND object_type_2_id = :object_type_1_id)
            OR (object_type_2_id = :object_type_2_id AND object_2_id = :object_2_id AND object_type_1_id = :object_type_1_id)
            OR (object_type_2_id = :object_type_1_id AND object_2_id = :object_1_id AND object_type_1_id = :object_type_2_id)");
            
			$pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
            $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
            $pdo->bindValue(":object_1_id", $object_1_id, PDO::PARAM_INT);
            $pdo->bindValue(":object_2_id", $object_2_id, PDO::PARAM_INT);
            $pdo->execute();
			
            if ($pdo->rowCount()) {
                return new FX_Error(__FUNCTION__, _('Link type does not allow to link these objects'));
            }
            return true;
        }
		
        $pdo = $fx_db->prepare("SELECT NULL FROM ".DB_TABLE_PREFIX."link_tbl
        WHERE (object_type_1_id = :object_type AND object_1_id = :object_id AND object_type_2_id = :other_type_id)
        OR (object_type_2_id = :object_type AND object_2_id = :object_id AND object_type_1_id = :other_type_id)");
		
        if ($type_relation == RELATION_N_1) {
            $pdo->bindValue(":object_type", $object_type_1_id, PDO::PARAM_INT);
            $pdo->bindValue(":object_id", $object_1_id, PDO::PARAM_INT);
            $pdo->bindValue(":other_type_id", $object_type_2_id, PDO::PARAM_INT);
        }
        else {
            $pdo->bindValue(":object_type", $object_type_2_id, PDO::PARAM_INT);
            $pdo->bindValue(":object_id", $object_2_id, PDO::PARAM_INT);
            $pdo->bindValue(":other_type_id", $object_type_1_id, PDO::PARAM_INT);
        }
		
        if (!$pdo->execute()) {
			if (is_debug_mode()) {
				add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
			}
            return new FX_Error(__FUNCTION__, _('SQL Error'));
        }
		
        if($pdo->rowCount()) {
            return new FX_Error( __FUNCTION__, _('Link type does not allow to link these objects'));
        }
		
        return true;
    }

    return new FX_Error(__FUNCTION__, _('Link type does not exists'), array($object_type_1_id,$object_type_2_id));
}

/********************************************************************************
 * Add link between types
 * @param int $object_type_1_id id of first type
 * @param int $object_type_2_id id of second type
 * @param int $relation relation(RELATION_1_1|RELATION_1_N|RELATION_N_1|RELATION_N_N)
 * @param int $schema_id int id of schema of link
 * @param bool $system is link system
 * @param string $position position in ER tool
 * @return bool|FX_Error `true` or error if link is invalid
 *******************************************************************************/
function add_link_type($object_type_1_id, $object_type_2_id, $relation, $schema_id = 0, $system = false, $position = '')
{
	if (link_type_exists($object_type_1_id, $object_type_2_id)) {
		return true;
	}
	
    global $fx_db;
	
    if ($relation) {
		if(!in_array($relation, array(RELATION_1_1, RELATION_1_N, RELATION_N_1, RELATION_N_N))) {
			return new FX_Error(__FUNCTION__, "Wrong relation type: ".$relation);
		}
	}
	
    $validation_result = validate_link_type($object_type_1_id, $object_type_2_id);
	
    if (is_fx_error($validation_result)) {
        return $validation_result;
    }
	
    $pdo = $fx_db->prepare("INSERT INTO ".DB_TABLE_PREFIX."link_type_tbl (system, schema_id, object_type_1_id, object_type_2_id, relation, position) VALUES (:system, :schema_id, :object_type_1_id, :object_type_2_id, :relation, :position)");
    $pdo->bindValue(":system", $system, PDO::PARAM_INT);
    $pdo->bindValue(":schema_id", $schema_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
    $pdo->bindValue(":relation", $relation, PDO::PARAM_INT);
    $pdo->bindValue(":position", $position, PDO::PARAM_STR);
    
	if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
    return true;
}

/********************************************************************************
 * Delete link between types
 * @param int $object_type_1_id id of first type
 * @param int $object_type_2_id id of second type
 * @return bool were types linked or not
 *******************************************************************************/
function delete_link_type($object_type_1_id, $object_type_2_id)
{
    global $fx_db;
	
    $pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."link_type_tbl WHERE (object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id) OR (object_type_2_id = :object_type_1_id AND object_type_1_id = :object_type_2_id)");
	
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
  
    if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    $pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."link_tbl WHERE (object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id) OR (object_type_2_id = :object_type_1_id AND object_type_1_id = :object_type_2_id)");
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

	// Clear cache
	//================================================================ 
	clear_user_cache();
	clear_query_cache_by_type($object_type_1_id);
	clear_query_cache_by_type($object_type_2_id);
	//================================================================ 

    return $pdo->rowCount() > 0;
}

/**
 * Get number of actual links beetween objects of specifed types
 * @param int $object_type_1_id id of object type 1
 * @param int $object_type_2_id id of object type 2
 * @return int|FX_Error number or error
 */
function get_link_type_count($object_type_1_id, $object_type_2_id)
{
    global $fx_db;
	
    $pdo = $fx_db->prepare("SELECT COUNT(*) AS C FROM ".DB_TABLE_PREFIX."link_tbl WHERE (object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id) OR (object_type_2_id = :object_type_1_id AND object_type_1_id = :object_type_2_id)");
	
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
	
    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    $row = $pdo->fetch();
	
    return $row["C"];
}

/********************************************************************************
 * Checks are types linked
 * @param int $object_type_1_id id of first type
 * @param int $object_type_2_id id of second type
 * @return bool are types linked or not
 *******************************************************************************/

function link_type_exists($object_type_1_id, $object_type_2_id)
{
    global $fx_db;
	
    $pdo = $fx_db->prepare("SELECT NULL FROM ".DB_TABLE_PREFIX."link_type_tbl WHERE (object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id) OR (object_type_2_id = :object_type_1_id AND object_type_1_id = :object_type_2_id) LIMIT 1");
	
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
	
    if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return false;
    }
	
	return $pdo->rowCount() ? true : false;
}

/********************************************************************************
 * Gets information about types link
 * @param int $object_type_1_id id of first type
 * @param int $object_type_2_id id of second type
 * @return bool|array array with information or `false` if there no link
 *******************************************************************************/
function get_link_type($object_type_1_id, $object_type_2_id)
{
    global $fx_db;
	
    $pdo = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."link_type_tbl WHERE (object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id) OR (object_type_2_id = :object_type_1_id AND object_type_1_id = :object_type_2_id)");
   
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);

	if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    if ($row = $pdo->fetch())
	{
        $result_row = array();
		
        $result_row['system'] = $row['system'];
        $result_row['schema_id'] = $row['schema_id'];
        $result_row['position'] = $row['position'];

        if ($object_type_2_id == $row['object_type_1_id']){
            $result_row['object_type_1_id'] = $row['object_type_2_id'];
            $result_row['object_type_2_id'] = $row['object_type_1_id'];
            $result_row['relation'] = _invert_relation($row['relation']);
        }
        else {
            $result_row['object_type_1_id'] = $row['object_type_1_id'];
            $result_row['object_type_2_id'] = $row['object_type_2_id'];
            $result_row['relation'] = $row['relation'];
        }

        return $result_row;
    }

    return false;
}

/**
 * Fetch all links between types of specified schema
 * @param int $schema_id id of schema
 * @param bool $include_system include links to system types
 * @return array|FX_Error array of links
 */
function get_schema_link_types($schema_id, $include_system = false)
{
    global $fx_db;
	
    if(!$include_system)
    {
        $query = "
        SELECT l.*
        FROM ".DB_TABLE_PREFIX."link_type_tbl l
        JOIN ".DB_TABLE_PREFIX."object_type_tbl t1
        ON l.object_type_1_id = t1.object_type_id AND t1.schema_id = :schema_id
        JOIN ".DB_TABLE_PREFIX."object_type_tbl t2
        ON l.object_type_2_id = t2.object_type_id AND t2.schema_id = :schema_id";
    }
	else {
        $query = "
        SELECT l.*
        FROM ".DB_TABLE_PREFIX."link_type_tbl l
        JOIN ".DB_TABLE_PREFIX."object_type_tbl t1
        ON l.object_type_1_id = t1.object_type_id AND (t1.schema_id = :schema_id OR t1.schema_id = 0)
        JOIN ".DB_TABLE_PREFIX."object_type_tbl t2
        ON l.object_type_2_id = t2.object_type_id AND (t2.schema_id = :schema_id OR t2.schema_id = 0)
        WHERE NOT (t1.schema_id = 0 AND t2.schema_id = 0)";
    }

    $pdo = $fx_db->prepare($query);
    $pdo->bindValue(":schema_id", $schema_id);
   
    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
    return $pdo->fetchAll();
}

/*******************************************************************************
 * Get types linked to specified type
 * @param mixed $types specified type_id
 * @return array array with results array(
 *                     linked_type_id => array(,
 *                     relation => ...,
 *                     position => ...,
 *                     system => ...,
 *                     )
 ******************************************************************************/
function get_type_links($object_type_id)
{
	if (is_array($object_type_id)) {
		$result = array();
		foreach ($object_type_id as $id) {
			$result[$id] = _get_type_links($id);
		}
		return $result;
	}
	else {
		return _get_type_links($object_type_id);
	}
}

function _get_type_links($object_type_id)
{
    global $fx_db;

    $pdo = $fx_db->prepare("SELECT l.*, t.name, t.display_name, l.relation, l.strength FROM ".DB_TABLE_PREFIX."link_type_tbl l 
							JOIN ".DB_TABLE_PREFIX."object_type_tbl t 
							ON l.object_type_1_id = :object_type_id AND t.object_type_id = l.object_type_2_id 
							OR l.object_type_2_id = :object_type_id AND t.object_type_id = l.object_type_1_id");

    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    $result = array();

    foreach ($pdo->fetchAll() as $row) {
        $result_row = $row;
        if ($object_type_id == $row['object_type_2_id']) {
            $id = $row['object_type_1_id'];
            $result_row['relation'] = _invert_relation($row['relation']);
        }
        else {
            $id = $row['object_type_2_id'];
            $result_row['relation'] = $row['relation'];
        }
        unset($result_row['object_type_1_id'], $result_row['object_type_2_id']);
        $result[$id] = $result_row;
    }

    return $result;
}

/********************************************************************************
 * Gets information about types relation
 * @param int $object_type_1_id id of first type
 * @param int $object_type_2_id id of second type
 * @return bool|int id or relation type or `false` if there no link
 *******************************************************************************/
function get_link_relation($object_type_1_id, $object_type_2_id)
{
    global $fx_db;

    $pdo = $fx_db->prepare("SELECT relation, object_type_1_id FROM ".DB_TABLE_PREFIX."link_type_tbl 
							 WHERE (object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id) 
							 	OR (object_type_2_id = :object_type_1_id AND object_type_1_id = :object_type_2_id)");
								
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
    if ($row = $pdo->fetch()) {
        $relation = $row['relation'];
		
        if ($object_type_2_id == $row['object_type_1_id']) {
            return _invert_relation($relation);
        }
        return $relation;
    }

    return false;
}

/**
 * Get existing links for obejct
 * @param int $object_type_id type
 * @param int $object_id
 * @param bool $linked_object_type_id
 * @return array|FX_Error
 */
function get_actual_links($object_type_id, $object_id, $linked_object_type_id = false, $schema_id = false)
{
    if ($linked_object_type_id) {
        $types = array($linked_object_type_id => array('relation' => get_link_relation($object_type_id, $linked_object_type_id)));
    }
	else {
        $types = get_type_links($object_type_id);
    }

	if (!$types) {
		return array();
	}

    $sub_queries = array();

    global $fx_db;

    foreach ($types as $type_id => $options) {
		
		$relation = $options["relation"] ? $options["relation"] : -1;

		$schema_condition = $schema_id ? "(o.schema_id=".(int)$schema_id." OR o.schema_id=0) AND" : "";
		
		$sub_queries[] = "SELECT o.object_id, ".$type_id." AS object_type_id, ".$relation." AS relation, o.name, o.display_name, o.set_id, o.schema_id, l.meta
						  FROM ".DB_TABLE_PREFIX."link_tbl l 
						  JOIN ".DB_TABLE_PREFIX."object_type_".$type_id." o 
						  ON (o.object_id=l.object_1_id AND l.object_type_1_id=".$type_id.") 
						  	OR (o.object_id=l.object_2_id AND l.object_type_2_id=".$type_id.")
						  WHERE".$schema_condition." ((l.object_type_1_id = ".$type_id." AND l.object_type_2_id = :object_type_id AND l.object_2_id = :object_id) 
						 	OR (l.object_type_2_id = ".$type_id." AND l.object_type_1_id = :object_type_id AND l.object_1_id = :object_id))";		
    }

	if (!count($sub_queries)) {
		return array();
	}

    $pdo = $fx_db -> prepare(implode("\n UNION \n", $sub_queries));
    $pdo -> bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
    $pdo -> bindValue(":object_id", $object_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    $result = array();

    foreach ($pdo->fetchAll() as $row)
    {
        $cur_type_id = $row['object_type_id'];

        if (!isset($result[$cur_type_id])) {
            $result[$cur_type_id] = array();
        }

		$meta = unserialize($row['meta']);

		$result[$cur_type_id][$row['object_id']] = array(
			'relation' => $row['relation'],
			'strength' => $types[$cur_type_id]['strength'],
			'name' => $row['name'],
			'display_name' => $row['display_name'],
			'set_id' => $row['set_id'],
			'schema_id' => $row['schema_id'],
			'actuality' => 'actual',
			'meta' => is_array($meta) ? $meta : $row['meta']);
    }

    return $result;
}

function get_object_strong_links($object_type_id, $object_id, $sub_links = false)
{
    $types = get_type_links($object_type_id);

	if (!$types) {
		return array();
	}

    $sub_queries = array();

    global $fx_db;

    foreach ($types as $linked_type_id => $options) {
		if ($options['strength']) {
			$relation = $options["relation"] ? $options["relation"] : -1;
			
			$sub_queries[] = "
				SELECT ".$linked_type_id." AS object_type_id, o.object_id, ".$relation." AS relation, o.name, o.display_name 
				FROM ".DB_TABLE_PREFIX."link_tbl l 
				JOIN ".DB_TABLE_PREFIX."object_type_".$linked_type_id." o 
				ON (o.object_id=l.object_1_id AND l.object_type_1_id=".$linked_type_id.") OR (o.object_id=l.object_2_id AND l.object_type_2_id=".$linked_type_id.")
				WHERE ((l.object_type_1_id = ".$linked_type_id." AND l.object_type_2_id = :object_type_id AND l.object_2_id = :object_id) 
				OR (l.object_type_2_id = ".$linked_type_id." AND l.object_type_1_id = :object_type_id AND l.object_1_id = :object_id))";
		}
    }

	if (!count($sub_queries)) {
		return array();
	}

    $pdo = $fx_db -> prepare(implode("\n UNION \n", $sub_queries));
    $pdo -> bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
    $pdo -> bindValue(":object_id", $object_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

	if (!$sub_links) {
		return $pdo->fetchAll();
	}
	else {
		$result = array();
		foreach ($pdo->fetchAll() as $link) {
			if ($link['relation'] == RELATION_1_1 || $link['relation'] == RELATION_1_N) {
				$result[] = $link;
			}
		}
		return $result;
	}
}

function get_actual_links_simple($object_type_id, $object_id, $linked_object_type_id, $schema_id = false)
{
    global $fx_db;

	$query = "(SELECT object_2_id AS object_id FROM ".DB_TABLE_PREFIX."link_tbl 
			  WHERE object_type_1_id = :object_type_id AND object_1_id = :object_id AND object_type_2_id = :linked_object_type_id)
			  UNION
			 (SELECT object_1_id AS object_id FROM ".DB_TABLE_PREFIX."link_tbl 
			  WHERE object_type_2_id = :object_type_id AND object_2_id = :object_id AND object_type_1_id = :linked_object_type_id)";	

    $pdo = $fx_db -> prepare($query);
    $pdo -> bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
    $pdo -> bindValue(":object_id", $object_id, PDO::PARAM_INT);
	$pdo -> bindValue(":linked_object_type_id", $linked_object_type_id, PDO::PARAM_INT);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    return $result = array_map(function($n){return $n['object_id'];}, $pdo -> fetchAll());
}

function get_actual_links_by_type($type_1, $type_2 = false)
{
    global $fx_db;

	if ($type_2) {
		$t2c1 = " AND object_type_2_id = :type_2";
		$t2c2 = " AND object_type_2_id = :type_2";
	}

	$query = "(SELECT object_2_id AS object_id, object_type_2_id AS object_type_id, meta FROM ".DB_TABLE_PREFIX."link_tbl 
			  WHERE object_type_1_id = :type_1".$t2c1.")
			  UNION
			 (SELECT object_1_id AS object_id, object_type_1_id AS object_type_id, meta FROM ".DB_TABLE_PREFIX."link_tbl 
			  WHERE object_type_2_id = :type_1".$t2c2.")";	

    $pdo = $fx_db -> prepare($query);
    $pdo -> bindValue(":type_1", (int)$type_1, PDO::PARAM_INT);
	
	if ($type_2) {
		$pdo -> bindValue(":type_2", (int)$type_2, PDO::PARAM_INT);
	}

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

	$result = array();

	foreach ($pdo -> fetchAll() as $link) {
		$result[$link['object_type_id']][] = array('object_id' => $link['object_id'], 'meta' => $link['meta']);
	}

	ksort($result);

	return $result;
}

function get_links_list($type)
{
	global $fx_db;

	$type = implode(',',(array)$type);

	$query = "(SELECT * FROM ".DB_TABLE_PREFIX."link_tbl WHERE object_type_1_id IN ($type))
			  UNION
			 (SELECT * FROM ".DB_TABLE_PREFIX."link_tbl WHERE object_type_2_id IN ($type))";

	$pdo = $fx_db -> prepare($query);

    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

	return $pdo -> fetchAll();
}


function get_link_meta($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id)
{
    global $fx_db;

    $pdo = $fx_db->prepare("SELECT meta FROM ".DB_TABLE_PREFIX."link_tbl 
							 WHERE (object_type_1_id = :object_type_1_id AND object_1_id = :object_1_id AND object_type_2_id = :object_type_2_id AND object_2_id = :object_2_id)
    						 OR (object_type_1_id = :object_type_2_id AND object_1_id = :object_2_id AND object_type_2_id = :object_type_1_id AND object_2_id = :object_1_id)");

    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_1_id", $object_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_2_id", $object_2_id, PDO::PARAM_INT);

    if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
	$row = $pdo->fetch();
	$data = unserialize($row['meta']);

	return is_array($data) ? $data : $row['meta'];
}

function update_link_meta($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id, $meta = '') 
{
	$meta = is_array($meta) ? serialize($meta) : $meta;

    global $fx_db;
	
    $pdo = $fx_db->prepare("UPDATE ".DB_TABLE_PREFIX."link_tbl SET meta=:meta 
							WHERE (object_type_1_id=:object_type_1_id AND 
								   object_1_id=:object_1_id AND 
								   object_type_2_id=:object_type_2_id AND 
								   object_2_id=:object_2_id) 
								OR (object_type_2_id=:object_type_1_id AND 
									object_2_id=:object_1_id AND 
									object_type_1_id=:object_type_2_id AND 
									object_1_id=:object_2_id)");
	
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_1_id", $object_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_2_id", $object_2_id, PDO::PARAM_INT);
	$pdo->bindValue(":meta", $meta, PDO::PARAM_STR);

    if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
	// Clear cache
	//================================================================ 
	clear_user_cache();
	//================================================================ 

    return true;
}

/**
 * Is object_type_id_1 and object_type_2_id in same order in reald db columns
 * @param int $object_type_1_id id of 1st type
 * @param int $object_type_2_id id of 2nd type
 * @return bool|FX_Error true if 1st in object_type_1_id column, false else
 */
function _is_link_real_orintation($object_type_1_id, $object_type_2_id)
{
    global $fx_db;
	
    $pdo = $fx_db->prepare("SELECT COUNT(*) AS C FROM ".DB_TABLE_PREFIX."link_type_tbl WHERE object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id");
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
    
	if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
    $row = $pdo->fetch();
    return ((int)$row["C"]) > 0;
}

/**
 * Get inverted relation for specified (e.g. 1_N => N_1)
 * @param int $relation relation
 * @return int inverted relation
 */
function _invert_relation($relation)
{
	switch ($relation) {
		case RELATION_1_1:
		case RELATION_N_N:
			return $relation;
		break;
		case RELATION_1_N:
			return RELATION_N_1;
		break;
		case RELATION_N_1:
			return RELATION_1_N;
		break;
		default:
			if (is_debug_mode()) {
				add_log_message(__FUNCTION__, _('Invalid relation')." [$relation]");
			}
			return false;
	}
}

/********************************************************************************
 * Gets information about objects linked to this object
 * @param int $object_type_id id of object type
 * @param int $object_id id of object
 * @return array|Fx_Error array with linked objects or error
 *******************************************************************************/
function get_object_links($object_type_id, $object_id, $linked_object_type_id = false)
{
	if (!object_exists($object_type_id, $object_id)) {
		return new FX_Error(__FUNCTION__, _('Object does not exists'));
	}

    global $fx_db;

    if($linked_object_type_id){
        $linked_type_constraint_a = "AND link.object_type_2_id = :linked_object_type_id";
        $linked_type_constraint_b = "AND link.object_type_1_id = :linked_object_type_id";
    }
	else {
        $linked_type_constraint_a = $linked_type_constraint_b = "";
    }

    $query = "
		SELECT link.object_2_id AS object_id, link.object_type_2_id AS object_type_id, link_type.relation AS relation, link_type.strength AS strength, link.meta
        FROM ".DB_TABLE_PREFIX."link_tbl link JOIN ".DB_TABLE_PREFIX."link_type_tbl link_type
            ON link.object_type_1_id = link_type.object_type_1_id
            AND link.object_type_2_id = link_type.object_type_2_id
            $linked_type_constraint_a
        WHERE link.object_type_1_id = :object_type_id
        AND link.object_1_id = :object_id

        UNION

        SELECT link.object_1_id AS object_id, link.object_type_1_id AS object_type_id, link_type.strength AS strength, CASE WHEN link_type.relation = ".RELATION_N_1." THEN ".RELATION_1_N."
        WHEN link_type.relation = ".RELATION_1_N." THEN ".RELATION_N_1."
        ELSE link_type.relation
        END AS relation,
		link.meta
        FROM ".DB_TABLE_PREFIX."link_tbl link
        JOIN ".DB_TABLE_PREFIX."link_type_tbl link_type
            ON link.object_type_1_id = link_type.object_type_1_id
            AND link.object_type_2_id = link_type.object_type_2_id
            $linked_type_constraint_b
        WHERE link.object_type_2_id = :object_type_id
        AND link.object_2_id = :object_id";

	//echo $query;

	$pdo = $fx_db->prepare($query);
    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_id", $object_id, PDO::PARAM_INT);
	
    if($linked_object_type_id) {
        $pdo->bindValue(":linked_object_type_id", $linked_object_type_id, PDO::PARAM_INT);
    }
	
    if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

	$result = array();

	foreach ($pdo->fetchAll() as $row) {
		$meta = unserialize($row['meta']);
		$row['meta'] = is_array($meta) ? $meta : $row['meta'];
		$result[$row['object_id']] = $row;
	}

    return $result;
}

/********************************************************************************
 * Check is there link between objects
 * @param int $object_type_1_id id of first object type
 * @param int $object_1_id id of first object
 * @param int $object_type_2_id id of second object type
 * @param int $object_2_id id of second object
 * @return bool is there link between objects
 *******************************************************************************/
function link_exists($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id)
{
    global $fx_db;
   
    $pdo = $fx_db->prepare("SELECT NULL FROM ".DB_TABLE_PREFIX."link_tbl 
							WHERE 
								(object_type_1_id = :object_type_1_id AND 
								 object_1_id = :object_1_id AND 
								 object_type_2_id = :object_type_2_id AND 
								 object_2_id = :object_2_id)
								OR 
								(object_type_1_id = :object_type_2_id AND 
								 object_1_id = :object_2_id AND 
								 object_type_2_id = :object_type_1_id AND 
								 object_2_id = :object_1_id)");
   
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_1_id", $object_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_2_id", $object_2_id, PDO::PARAM_INT);

	if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return false;
    }

    return $pdo->rowCount() ? true : false;
}

/*******************************************************************************
 * Delete link between objects
 * @param int $object_type_1_id id of first object type
 * @param int $object_1_id id of first object
 * @param int $object_type_2_id id of second object type
 * @param int $object_2_id id of second object
 * @return bool was there link between objects
 *******************************************************************************/
function delete_link($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id)
{
    global $fx_db;

    $pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."link_tbl
    WHERE (object_type_1_id = :object_type_1_id AND object_1_id = :object_1_id AND object_type_2_id = :object_type_2_id AND object_2_id = :object_2_id)
    OR (object_type_1_id = :object_type_2_id AND object_1_id = :object_2_id AND object_type_2_id = :object_type_1_id AND object_2_id = :object_1_id)");
	
    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_1_id", $object_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_2_id", $object_2_id, PDO::PARAM_INT);
    
	if(!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
	// Clear cache
	//================================================================ 
	clear_user_cache();
	clear_query_cache_by_type($object_type_1_id);
	clear_query_cache_by_type($object_type_2_id);
	//================================================================ 
	
    return $pdo->rowCount() > 0;
}

/**
 * Delete all links for specified object
 * @param int $object_type_id object type id
 * @param int $object_id object id
 * @return FX_Error|int number of deleted links or error
 */
function delete_object_links($object_type_id, $object_id)
{
    global $fx_db;
	
    $pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."link_tbl WHERE (object_type_1_id = :object_type_id AND object_1_id = :object_id) OR (object_type_2_id = :object_type_id AND object_2_id = :object_id)");

    $pdo->bindValue(":object_type_id", $object_type_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_id", $object_id, PDO::PARAM_INT);
	
    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
	// Clear cache
	//================================================================ 
	clear_user_cache();
	clear_query_cache_by_type($object_type_id);
	//================================================================ 	
	
    return $pdo->rowCount();
}

/**
 * Update link for specified types
 * @param int $object_type_1_id 1st type id
 * @param int $object_type_2_id 2nd type id
 * @param int $relation new relation
 * @param bool $system new system option
 * @param string $position new position
 * @return bool|FX_Error true or error
 */
function update_link_type($object_type_1_id, $object_type_2_id, $relation, $system = false, $position = '')
{
    global $fx_db;
	
    if ($relation) {
        if(!in_array($relation, array(RELATION_1_1, RELATION_1_N, RELATION_N_1, RELATION_N_N))) {
            return new FX_Error(__FUNCTION__, _('Invalid relation type')." [$relation]");
        }
	}
	
    $validation_result = validate_link_type($object_type_1_id, $object_type_2_id, false);

    if(is_fx_error($validation_result)) {
        return $validation_result;
    }

    $pdo = $fx_db->prepare("SELECT 1 AS T, relation FROM ".DB_TABLE_PREFIX."link_type_tbl
             WHERE object_type_1_id = :object_type_1_id
             AND object_type_2_id = :object_type_2_id
             LIMIT 1
             UNION
             SELECT 2 AS T, relation FROM ".DB_TABLE_PREFIX."link_type_tbl
             WHERE object_type_1_id = :object_type_2_id
             AND object_type_2_id = :object_type_1_id
             LIMIT 1");

    $pdo->bindValue(":object_type_1_id", $object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $object_type_2_id, PDO::PARAM_INT);
	
    if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    $row = $pdo->fetch();
	
    if (!$row) {
        return new FX_Error(__FUNCTION__, _('Object link not found'));
    }
	
    $real_old_relation = $row["relation"];
	
    if($row["T"] == "1"){
        $real_object_type_1_id = $object_type_1_id;
        $real_object_type_2_id = $object_type_2_id;
        $real_relation = $relation;
    }
	else {
        $real_object_type_2_id = $object_type_1_id;
        $real_object_type_1_id = $object_type_2_id;
        $real_relation = _invert_relation($relation);
    }
	
    if ($real_old_relation == $real_relation){
        $delete_object_links = false;
    }
	elseif($real_old_relation == RELATION_1_N && ($real_relation == RELATION_1_1 || $real_relation == RELATION_N_1)){
        $delete_object_links = true;
    }
	elseif($real_old_relation == RELATION_N_1 && ($real_relation == RELATION_1_1 || $real_relation == RELATION_1_N)){
        $delete_object_links = true;
    }
	elseif($real_old_relation == RELATION_N_N){
        $delete_object_links = true;
    }
	else {
        $delete_object_links = false;
    }

    if ($delete_object_links) {
        $pdo = $fx_db->prepare("DELETE FROM ".DB_TABLE_PREFIX."link_tbl WHERE object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id");

        $pdo->bindValue(":object_type_1_id", $real_object_type_1_id, PDO::PARAM_INT);
        $pdo->bindValue(":object_type_2_id", $real_object_type_2_id, PDO::PARAM_INT);
       
	    if(!$pdo->execute()) {
			if (is_debug_mode()) {
				add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
			}
            return new FX_Error(__FUNCTION__, _('SQL Error'));
        }
    }

    $pdo = $fx_db->prepare("UPDATE ".DB_TABLE_PREFIX."link_type_tbl SET system = :system, relation = :relation, position = :position WHERE object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id");

    $pdo->bindValue(":system", $system, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_1_id", $real_object_type_1_id, PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", $real_object_type_2_id, PDO::PARAM_INT);
    $pdo->bindValue(":relation", $real_relation, PDO::PARAM_INT);
    $pdo->bindValue(":position", $position, PDO::PARAM_STR);
    
	if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
	// Clear cache
	//================================================================ 
	clear_user_cache();
	clear_query_cache_by_type($object_type_1_id);
	clear_query_cache_by_type($object_type_2_id);
	//================================================================ 	
	
    return true;
}


function update_link_strength($object_type_1_id, $object_type_2_id, $strength)
{
	if (!link_type_exists($object_type_1_id, $object_type_2_id)) {
		return new FX_Error(__FUNCTION__, _('Link type does not exists '.$object_type_1_id.' '.$object_type_2_id));
	}	
	
	if (!$strength && ($strength != LINK_WEAK || $strength != LINK_STRONG)) {
		$strength = LINK_WEAK;
	}

	global $fx_db;

    $pdo = $fx_db->prepare("
		UPDATE ".DB_TABLE_PREFIX."link_type_tbl 
		SET strength = :strength 
		WHERE 
			(object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id)
			OR
			(object_type_1_id = :object_type_2_id AND object_type_2_id = :object_type_1_id)");

    $pdo->bindValue(":object_type_1_id", intval($object_type_1_id), PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", intval($object_type_2_id), PDO::PARAM_INT);
	$pdo->bindValue(":strength", $strength, PDO::PARAM_INT);
   
	if (!$pdo->execute()) {
        if (is_debug_mode()) {
			add_log_message(__FUNCTION__, print_r($pdo->errorInfo(), true));
		}
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
	return true;
}

function get_link_strength($object_type_1_id, $object_type_2_id)
{
	global $fx_db;
	
    $pdo = $fx_db->prepare("SELECT strength FROM ".DB_TABLE_PREFIX."link_type_tbl WHERE	(object_type_1_id = :object_type_1_id AND object_type_2_id = :object_type_2_id) OR (object_type_1_id = :object_type_2_id AND object_type_2_id = :object_type_1_id) LIMIT 1");

    $pdo->bindValue(":object_type_1_id", intval($object_type_1_id), PDO::PARAM_INT);
    $pdo->bindValue(":object_type_2_id", intval($object_type_2_id), PDO::PARAM_INT);
   
	if (!$pdo->execute()) {
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }
	
	if ($row = $pdo->fetch()) {
		return new FX_Error(__FUNCTION__, _('Link type does not exists'));
	}
	
	return $row['strength'];
}