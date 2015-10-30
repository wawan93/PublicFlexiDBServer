<?php

/*******************************************************************************
 * @param $object_type_id
 * @param int $revisions_to_keep number of revision to keep.
 * @param int $revisions_to_keep number of revision to keep.
 * @param null $object_id
 * @return FX_Error|int
 ******************************************************************************/
 function _truncate_old_revisions($object_type_id, $revisions_to_keep = 1,
                                 $object_id = null)
{
    //TODO rewrite for object_id = null
    if($revisions_to_keep < 1)
    {
        return new FX_Error(__FUNCTION__, "Can't truncate current revision");
    }
    if(!is_numeric($object_type_id))
    {
        return new FX_Error(__FUNCTION__, "Wrong type id");
    }
    global $fx_db;
    if($revisions_to_keep > 1)
    {
        if($object_id !== null)
        {
            $stmt = $fx_db->prepare("
            DELETE rev.*
            FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev rev
            JOIN (
                SELECT DISTINCT modified as modified
                FROM  ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
                WHERE object_id = :object_id
                ORDER BY modified DESC
                LIMIT :limit, 9999) J ON J.modified = rev.modified
            WHERE object_id = :object_id");
            $stmt->bindValue(":object_id", $object_id, PDO::PARAM_INT);

        }
        else
        {
            $stmt = $fx_db->prepare("
            DELETE FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev rev
            JOIN (
                SELECT DISTINCT modified as modified
                FROM  ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
                ORDER BY modified DESC
            LIMIT :limit, 9999) J
            ON J.modified = rev.modified AND J.object_id = rev.object_id");
        }
        $stmt->bindValue(":limit", $revisions_to_keep - 1, PDO::PARAM_INT);
    }
    else
    {
        $stmt = $fx_db->prepare(
            "DELETE FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev"
        );
    }

    if(!$stmt->execute()){
        $type = get_type($object_type_id, "none");
        if(is_fx_error($type))
        {
            return $type;
        }
        if($type["revisions_number"] == 1)
        {
            new FX_Error(__FUNCTION__, "Type don't store old revisions");
        }

        return new FX_Error(__FUNCTION__, "Something go wrong");
    }
    return $stmt->rowCount();
}

/*******************************************************************************
 * Remove old deleted objects data from DB
 * @param int $days number of days from to day objects must be older than to
 *                  be cleaned
 * @param null|array $object_type_ids array of type ids for objects to be
 *                  cleaned all types if not specified
 * @return bool|FX_Error true or FX_Error
 ******************************************************************************/
function clean_removed_older_than($days = 30, $object_type_ids = null)
{
    global $fx_db;
    $border_time = time() - $days * 24 * 60 * 60;
    if($object_type_ids == null)
    {
        $stmt = $fx_db->prepare("
        SELECT object_type_id FROM ".DB_TABLE_PREFIX."object_type_tbl
        WHERE revisions_number > 1"
        );
        if($stmt->execute())
        {
            $object_type_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        else
        {
            return new FX_Error(__FUNCTION__, "DB Error");
        }
    }
    foreach($object_type_ids as $object_type_id)
    {
        $stmt = $fx_db->prepare("
        DELETE FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
        WHERE modified < :border_time"
        );
        $stmt->bindValue(":border_time", $border_time, PDO::PARAM_INT);
        if(!$stmt->execute())
        {
            return new FX_Error(__FUNCTION__, "DB Error");
        }
    }
    return true;
}

/*******************************************************************************
 * Get removed objects off this type which is store in DB
 * @param int $object_type_id id of object type
 * @param $set_id
 * @param null|array $fields list of field names to return. All if not specified
 * @return array|FX_Error
 ****************************************************************************
 */
function get_removed_objects_by_type($object_type_id, $set_id, $fields = null)
{
    global $fx_db;
    if($fields ===  null)
    {
        $fields = array_keys(get_type_fields($object_type_id, "all"));
        if(!array_search("set_id", $fields))
        {
            $fields[] = "set_id";
        }
    }
    foreach(array("object_id", "modified") as $field_to_exclude)
    {
        if(($key = array_search($field_to_exclude, $fields)) !== false) {
            unset($fields[$key]);
        }
    }

    $select_fields = array_map(
        function ($f) {return $f."_tbl.value as $f";},
        $fields
    );
    $sql = "SELECT main.object_id, main.modified"
            .(count($select_fields) > 0 ? ",".join(", ", $select_fields): "")
            ." FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev main ";
    if(!array_search("set_id", $fields))
    {
        $fields[] = "set_id";
    }
    foreach($fields as $field)
    {
        $sql .= "
        LEFT JOIN ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev ".$field."_tbl
        ON ".$field."_tbl.object_id = main.object_id
        AND ".$field."_tbl.field = '$field' ";
    }
    $sql .= "WHERE main.object_id NOT IN (
                SELECT object_id
                FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."
             ) AND main.field = 'name'
             AND set_id_tbl.value = :set_id
             ORDER BY main.modified DESC";
    $stmt = $fx_db->prepare($sql);
    $stmt->bindValue(":set_id", $set_id);
    if($stmt->execute())
    {
        $result = array();
        foreach($stmt->fetchAll() as $row)
        {
            $result[$row["object_id"]] = $row;
        }
        return $result;
    }
    add_log_message(__FUNCTION__, var_export($stmt->errorInfo()));
    return new FX_Error(__FUNCTION__, "DB Error");
}

/*******************************************************************************
 * Returns removed object data
 * @param int $object_type_id id of object_type
 * @param int $object_id id of object
 * @return array|FX_Error object data or error
 ******************************************************************************/
function get_removed_object($object_type_id, $object_id)
{
    global $fx_db;
    $stmt = $fx_db->prepare("
        SELECT modified, field, value
        FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
        WHERE object_id = :object_id
    ");
    $stmt->bindValue(":object_id", $object_id, PDO::PARAM_INT);
    if($stmt->execute())
    {
        if($stmt->rowCount() == 0)
        {
            return new FX_Error(__FUNCTION__, "No information for this id");
        }
        $rows = $stmt->fetchAll();
        $object = array(
            "object_id" => $object_id,
            "modified" => $rows[0]["modified"]
        );
        foreach($rows as $row)
        {
            if(isset($object[$row["field"]]))
            {
                return new FX_Error(__FUNCTION__, "Looks like object not removed");
            }
            $object[$row["field"]] = $row["value"];
        }
        return $object;
    }
    add_log_message(__FUNCTION__, var_export($stmt->errorInfo()));
    return new FX_Error(__FUNCTION__, "DB Error");
}

/*******************************************************************************
 * Returns list of old object revisions
 * array($modification_time_1, $modification_time_2..,)
 * @param int $object_type_id id of type
 * @param int $object_id id of object
 * @return array|FX_Error list of modification timestamps or error
 ******************************************************************************/
function get_revisions_list($object_type_id, $object_id)
{
    global $fx_db;
    $stmt = $fx_db->prepare("SELECT DISTINCT modified AS modified FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev WHERE object_id = :object_id ORDER BY modified DESC");
    $stmt->bindValue(":object_id", $object_id, PDO::PARAM_INT);
    
	if($stmt->execute()) {
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    elseif (!type_exists($object_type_id))  {
        return new FX_Error(__FUNCTION__, _('Invalid type'));
    }

    return array();
}

function get_changes_to_rollback($object_type_id, $object_id, $time)
{
    if(get_type_revisions_number($object_type_id) == 1)
    {
        return new FX_Error(__FUNCTION__, "Incorrect time");
    }
    global $fx_db;
    $stmt = $fx_db->prepare(
        "SELECT main.field, main.value
        FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev main
        JOIN (
            SELECT MIN(modified) as modified, field
            FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
            WHERE modified >= :time
            AND :time >= (
                SELECT MIN(modified)
                FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
                WHERE object_id = :object_id
            )
            AND object_id = :object_id
            GROUP BY field
        ) t
        ON main.field = t.field AND main.modified = t.modified
        WHERE main.object_id = :object_id"
    );
    $stmt->bindValue(":time", $time, PDO::PARAM_INT);
    $stmt->bindValue(":object_id", $object_id, PDO::PARAM_INT);
    if($stmt->execute())
    {
        if($stmt->rowCount() == 0)
        {
            return new FX_Error(__FUNCTION__, "No history for this time");
        }
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}


function _delete_revisions_after($object_type_id, $object_id, $time)
{
    global $fx_db;
    $stmt = $fx_db->prepare(
        "DELETE FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
            WHERE object_id = :object_id
            AND modified >= :time"
    );
    $stmt->bindValue(":time", $time, PDO::PARAM_INT);
    $stmt->bindValue(":object_id", $object_id, PDO::PARAM_INT);
    if(!$stmt->execute())
    {
        add_log_message(__FUNCTION__, var_export($stmt->errorInfo()));
        return new FX_Error(__FUNCTION__, "DB Error");
    }
}

/*******************************************************************************
 * Get object state at revision
 * @param int $object_type_id id of type
 * @param int $object_id id of object
 * @param int $time timestamp of revision
 * @return array|FX_Error array with object data or error
 ******************************************************************************/
function get_revision($object_type_id, $object_id, $time)
{
    $object = get_object($object_type_id, $object_id);
    if(is_fx_error($object))
    {
        return new FX_Error(__FUNCTION__, "Can't find current state of object");
    }
    if(get_type_revisions_number($object_type_id) == 1)
    {
        if($object["modified"] <= $time)
        {
            return $object;
        }
        return new FX_Error(__FUNCTION__, "Incorrect time");
    }
    global $fx_db;
    $stmt = $fx_db->prepare(
        "SELECT main.field, main.value
        FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev main
        JOIN (
            SELECT MIN(modified) as modified, field
            FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
            WHERE modified >= :time
            AND :time >= (
                SELECT MIN(modified)
                FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
                WHERE object_id = :object_id
            )
            AND object_id = :object_id
            GROUP BY field
        ) t
        ON main.field = t.field AND main.modified = t.modified
        WHERE main.object_id = :object_id"
    );
    $stmt->bindValue(":time", $time, PDO::PARAM_INT);
    $stmt->bindValue(":object_id", $object_id, PDO::PARAM_INT);
    if($stmt->execute())
    {
        if($stmt->rowCount() == 0)
        {
            return new FX_Error(__FUNCTION__, "No history for this time");
        }
        $old_values = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $old_values["modified"] = $time;
        return array_merge($object, $old_values);
    }
    add_log_message(__FUNCTION__, var_export($stmt->errorInfo()));
    return new FX_Error(__FUNCTION__, "DB Error");
}

/*******************************************************************************
 * Register object as removed in revisions table
 * @param int $object_type_id id of type
 * @param array $object object data
 * @param int $time timestamp of removing operation
 * @return bool|FX_Error true or error
 ******************************************************************************/
function add_deleted_object($object_type_id, $object, $time)
{
    global $fx_db;
    $stmt = $fx_db->prepare("
    DELETE FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
    WHERE object_id = :object_id
    ");
    $stmt->bindValue(":object_id", $object["object_id"], PDO::PARAM_INT);
    $revisions_number = get_type_revisions_number($object_type_id);
    
	if($revisions_number == false)
    {
         return new FX_Error(__FUNCTION__, 'Unable to get revisions number.');
    }
	
    if($revisions_number == 1)
    {
        return true;
    }
	
    if($stmt->execute())
    {
        $object_id = $object["object_id"];
        unset($object["object_id"]);
        $object["modified"] = $time;
        $res = _add_object_revision(
            $object_type_id,
            $object_id,
            $object
        );
        if(is_fx_error($res))
        {
            return $res;
        }
        return true;
    }
    add_log_message(__FUNCTION__, var_export($stmt->errorInfo()));
    return new FX_Error(__FUNCTION__, "DB Error");
}

function _add_object_revision($object_type_id, $object_id, $overwritten_data)
{

//    echo "\n--------------------\n";
//    fx_print($overwritten_data);
//    echo "\n=====================\n";
    if(!isset($overwritten_data["modified"]))
    {
        return new FX_Error(__FUNCTION__, "'Modified' field required");
    }
    $modified = $overwritten_data["modified"];
    unset($overwritten_data["modified"]);
    unset($overwritten_data["object_type_id"]);
    $revisions_number = get_type_revisions_number($object_type_id);
	
	if($revisions_number == false) {
         return new FX_Error(__FUNCTION__, 'Unable to get revisions number.');
    }
	
    if($revisions_number == 1)
    {
        return true;
    }
    $values = array_map(function($f){
        return "(:object_id, '$f', :modified, :$f)";
    }, array_keys($overwritten_data));
    global $fx_db;
    $stmt = $fx_db->prepare("
            INSERT INTO ".DB_TABLE_PREFIX."object_type_".$object_type_id."_rev
            (object_id, field, modified, value) VALUES "
            .join(",\n", $values)."
            ON DUPLICATE KEY UPDATE modified = modified + 1"
    );
    $stmt->bindValue(":object_id", $object_id, PDO::PARAM_INT);
    $stmt->bindValue(":modified", $modified, PDO::PARAM_INT);
    foreach($overwritten_data as $field => $value)
    {
        $stmt->bindValue(":".$field, $value);
    }
    if($stmt->execute())
    {
        $result = _truncate_old_revisions(
            $object_type_id,
            $revisions_number,
            $object_id
        );
        if(is_fx_error($result))
        {
            return $result;
        }
        return true;
    }
    add_log_message(__FUNCTION__, var_export($stmt->errorInfo()));
    return new FX_Error(__FUNCTION__, "DB Error");
}