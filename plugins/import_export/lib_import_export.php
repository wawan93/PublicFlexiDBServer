<?php

function import_export_get_links_in_data_sets($object_types, $data_sets)
{
    global $fx_db;

    $data_sets = implode(',', (array)$data_sets);
    $possible_object_ids = array();
    $query = "SELECT l.* FROM ".DB_TABLE_PREFIX."link_tbl l WHERE ";
    foreach($object_types as $type_id){
        $q = "select object_id from ".DB_TABLE_PREFIX."object_type_$type_id where set_id in ($data_sets)";

        $pdo = $fx_db->prepare($q);

        if(!$pdo->execute()) {
            return new FX_Error(__FUNCTION__, _('SQL Error'));
        }
        $tmp = $pdo->fetchAll(PDO::FETCH_COLUMN);
        if($tmp){
            $possible_object_ids[] = "(object_type_1_id = $type_id AND object_1_id IN (".implode(',',$tmp).") " .
                "OR object_type_2_id = $type_id AND object_2_id IN (".implode(',',$tmp)."))";
        }
    }
    $query .= implode(' OR ',$possible_object_ids);

    $pdo = $fx_db->prepare($query);

    if(!$pdo->execute()) {
        return new FX_Error(__FUNCTION__, _('SQL Error'));
    }

    $result = array();
    
    return $pdo->fetchAll();
}

function get_archive_schema_and_sets($filename)
{
    $archive = new ZipArchive();
    $archive->open($filename);
    $schemas = csv_to_array($archive->getFromName("schema.csv"), null);
    $result = array(
        "schema" => $schemas[0],
        "sets" => $data_sets = csv_to_array($archive->getFromName("sets.csv"), "object_id")
    );
    $archive->close();
    return $result;
}

function update_query_ids_in_charts($query_ids)
{
    global $fx_db;
    $chart_type_id = get_type_id_by_name(0, "chart");
    $sql = "UPDATE ".DB_TABLE_PREFIX."object_type_".$chart_type_id." SET query_id = CASE query_id";
    foreach($query_ids as $old_id => $new_id)
    {
        $sql .= "\n WHEN $old_id THEN $new_id";
    }
    $sql .= "ELSE 0 END";
    return $fx_db->exec($sql);
}