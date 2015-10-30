<?php


class FXIntegrityReport {

    var $lost_data_sets;
    var $lost_types;
    var $lost_type_fields;
    var $lost_enums;
    var $lost_enum_fields;
    var $lost_objects;
    var $lost_link_types;
    var $lost_links;
    var $types_without_tables;
    var $type_fields_without_columns;
    var $columns_without_type_fields;


    protected function FXIntegrityReport()
    {
        $this->lost_types = self::find_lost_types();
        $lost_types_ids = array_keys($this->lost_types);
        $this->lost_type_fields = self::find_lost_type_fields($lost_types_ids);
        $this->lost_enums = self::find_lost_enums();
        $this->lost_enum_fields = self::find_lost_enum_fields();
        $this->lost_objects = self::find_lost_objects();
        $this->lost_link_types = self::find_lost_link_types($lost_types_ids);
        $this->lost_links = self::find_lost_links();
        $this->types_without_tables = self::find_types_without_tables();
        $this->type_fields_without_columns = self::find_type_fields_without_columns();
        $this->columns_without_type_fields = self::find_columns_without_type_fields();
    }

	public function fetchAll()
	{
		$data = array();

        $data['types'] = $this->lost_types;
		$data['type_fields'] = $this->lost_type_fields;
        $data['enums'] = $this->lost_enums;
        $data['enum_fields'] = $this->lost_enum_fields;
        $data['objects'] = $this->lost_objects;
        $data['link_types'] = $this->lost_link_types;
        $data['links'] = $this->lost_links;
        $data['types_without_tables'] = $this->types_without_tables;
        $data['type_fields_without_columns'] = $this->type_fields_without_columns;
        $data['columns_without_type_fields'] = $this->columns_without_type_fields;
		
		return $data;
	}

    public static function Create()
    {
        return new FXIntegrityReport();
    }


    /**
     * @return array
     */
    public function get_lost_enum_fields()
    {
        return $this->lost_enum_fields;
    }

    /**
     * @return array
     */
    public function get_lost_enums()
    {
        return $this->lost_enums;
    }

    /**
     * @return array
     */
    public function get_lost_objects()
    {
        return $this->lost_objects;
    }

    /**
     * @return array
     */
    public function get_lost_type_fields()
    {
        return $this->lost_type_fields;
    }

    /**
     * @return array
     */
    public function get_lost_types()
    {
        return $this->lost_types;
    }

    /**
     * @return array
     */
    public function get_lost_link_types()
    {
        return $this->lost_link_types;
    }

    /**
     * @return array
     */
    public function get_lost_links()
    {
        return $this->lost_links;
    }

    public function get_types_without_tables()
    {
        return $this->types_without_tables;
    }

    public function get_type_fields_without_columns()
    {
        return $this->type_fields_without_columns;
    }

    public function get_columns_without_type_fields()
    {
        return $this->columns_without_type_fields;
    }

    protected static function find_lost_types()
    {
        global $fx_db;
        $stmt = $fx_db->prepare("SELECT object_type_id, display_name FROM ".DB_TABLE_PREFIX."object_type_tbl WHERE schema_id NOT IN (SELECT object_id FROM ".DB_TABLE_PREFIX."object_type_".TYPE_DATA_SCHEMA.") AND schema_id > 0");
        $stmt->execute();
		
		$result = array();

		foreach ($stmt->fetchAll() as $row) {
			$result['types.'.$row['object_type_id']] = $row;
		}
		
        return $result;
    }

    protected static function find_lost_type_fields($lost_types_ids)
    {
        $lost_types_str = implode(" ,", $lost_types_ids);

        global $fx_db;

        $stmt = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."field_type_tbl WHERE object_type_id NOT IN (SELECT object_type_id FROM ".DB_TABLE_PREFIX."object_type_tbl) ".($lost_types_str ? "OR object_type_id IN ($lost_types_str)" : ""));
        $stmt->execute();
        $result = array();

        foreach($stmt->fetchAll() as $row) {
            $result['type_fields.'.$row['object_type_id'].'.'.$row['name']] = $row;
        }

        return $result;
    }

    public function clear_lost_type_fields($ids)
    {
        $clauses = array();
        foreach($ids as $object_type_id => $names) {
            foreach($names as $name => $value) {
                if($this->lost_type_fields[$object_type_id.$name]) {
                    $clauses[] = "('$object_type_id', '$name')";
                }
            }
        }

        if($clauses) {
            global $fx_db;
            $fx_db->exec("DELETE FROM ".DB_TABLE_PREFIX."field_type_tbl WHERE (object_type_id, name) IN (".join(",", $clauses).")");
        }
    }

    protected static function find_lost_enums()
    {
        global $fx_db;
        $stmt = $fx_db->prepare("
        SELECT * FROM ".DB_TABLE_PREFIX."enum_type_tbl
        WHERE schema_id > 0 AND schema_id NOT IN
            (SELECT object_id FROM ".DB_TABLE_PREFIX."object_type_".get_type_id_by_name(0, "data_schema").")
        ");
        $stmt->execute();
        return reform_db_result_by_key_field($stmt->fetchAll(), "enum_type_id");
    }

    public function clear_lost_enums($ids)
    {
        $ids = array_intersect(array_keys($this->lost_enums), array_keys($ids));
        if($ids)
        {
            global $fx_db;
            $fx_db->exec("
                DELETE FROM ".DB_TABLE_PREFIX."enum_type_tbl
                WHERE (enum_type_id) IN (".join(",", $ids).")
            ");
        }
    }

    protected static function find_lost_enum_fields()
    {
        global $fx_db;
        $stmt = $fx_db->prepare("
        SELECT * FROM ".DB_TABLE_PREFIX."enum_field_tbl
        WHERE enum_type_id NOT IN (SELECT enum_type_id FROM ".DB_TABLE_PREFIX."enum_type_tbl)
        ");
        $stmt->execute();
        $result = array();
        foreach($stmt->fetchAll() as $row)
        {
            $result[$row["enum_type_id"]."-".$row["enum_field_id"]] = $row;
        }
        return $result;
    }

    public function clear_lost_enum_fields($ids)
    {
        $clauses = array();
        foreach($ids as $enum_id => $enum_fields_ids)
        {
            foreach($enum_fields_ids as $enum_field_id =>  $value)
            {
                if($this->lost_enum_fields[$enum_id."-".$enum_field_id])
                {
                    $clauses[] = "('$enum_id', '$enum_field_id')";
                }
            }
        }
        if($clauses)
        {
            global $fx_db;
            $fx_db->exec("
                DELETE FROM ".DB_TABLE_PREFIX."enum_field_tbl
                WHERE (enum_type_id, enum_field_id) IN (".join(",", $clauses).")
            ");
        }
    }

    protected static function find_lost_objects()
    {
        global $fx_db;
		
        $sets_stmt = $fx_db->prepare("SELECT object_id FROM ".DB_TABLE_PREFIX."object_type_".TYPE_DATA_SET);
        $sets_stmt->execute();
		$sets = $sets_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
		$sets[] = 0;

        $schemas_stmt = $fx_db->prepare("SELECT object_id FROM ".DB_TABLE_PREFIX."object_type_".TYPE_DATA_SCHEMA);
        $schemas_stmt->execute();
		$schemas = $schemas_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
		$schemas[] = 0;

        $typ_stmt = $fx_db->prepare("SELECT object_type_id, schema_id, display_name, system FROM ".DB_TABLE_PREFIX."object_type_tbl");
        $typ_stmt->execute();

		$result = array();

        foreach($typ_stmt->fetchAll() as $type)
        {
            if($type["object_type_id"] == get_type_id_by_name(0,"data_schema") || $type["object_type_id"] == TYPE_SUBSCRIPTION) {
                continue;
            }
			
            if((int)$type['system']) {
                $object_stmt = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."object_type_".$type["object_type_id"]." WHERE schema_id NOT IN (".implode(" ,", $schemas).")");
            }
            else {
                $object_stmt = $fx_db->prepare("SELECT * FROM ".DB_TABLE_PREFIX."object_type_".$type["object_type_id"]." WHERE set_id NOT IN (".implode(" ,", $sets).")");
            }

            $object_stmt->execute();
            $object_result = $object_stmt->fetchAll();

            if($object_result) {
                $result[$type["object_type_id"]] = array("display_name" => $type["display_name"], "objects" =>  reform_db_result_by_key_field($object_result, "object_id"));
            }
        }

        return $result;
    }

    public function clear_lost_objects($ids)
    {
        global $fx_db;
        foreach($ids as $object_type_id => $objects)
        {
            if(!$this->lost_objects[$object_type_id])
            {
                continue;
            }
            $ids_to_delete = array();
            foreach(array_keys($objects) as $object_id)
            {
                if($this->lost_objects[$object_type_id]["objects"][$object_id])
                {
                    $ids_to_delete[] = $object_id;
                }
            }
            $fx_db->exec(
                "DELETE FROM ".DB_TABLE_PREFIX."object_type_".$object_type_id."
                WHERE object_id IN (".join(", ",$ids_to_delete).")
            ");
        }
    }

    protected static function find_lost_link_types($lost_types_ids)
    {
        $lost_types_str = join(" ,", $lost_types_ids);
        global $fx_db;
        $stmt = $fx_db->prepare("
        SELECT * FROM ".DB_TABLE_PREFIX."link_type_tbl
        WHERE object_type_1_id NOT IN (SELECT object_type_id FROM ".DB_TABLE_PREFIX."object_type_tbl)
        ".($lost_types_str ? "OR object_type_1_id IN ($lost_types_str)" : "")."
        OR object_type_2_id NOT IN (SELECT object_type_id FROM ".DB_TABLE_PREFIX."object_type_tbl)
        ".($lost_types_str ? "OR object_type_2_id IN ($lost_types_str)" : "")."
        OR
            schema_id  NOT IN (SELECT object_id FROM ".DB_TABLE_PREFIX."object_type_".get_type_id_by_name(0, "data_schema").")
            AND schema_id > 0
        ");
        $stmt->execute();
        $result = array();
        foreach($stmt->fetchAll() as $row)
        {
            $result[$row["object_type_1_id"]."-".$row["object_type_2_id"]] = $row;
        }
        return $result;
    }

    public function clear_lost_link_types($ids)
    {
        $clauses = array();
        foreach($ids as $object_type_1_id => $object_type_2_ids)
        {
            foreach($object_type_2_ids as  $object_type_2_id => $nothing)
            {
                if($this->lost_link_types[$object_type_1_id."-".$object_type_2_id])
                {
                    $clauses[] = "($object_type_1_id, $object_type_2_id)";
                }
            }
        }
        global $fx_db;
        $fx_db->exec(
            "DELETE FROM ".DB_TABLE_PREFIX."link_type_tbl
            WHERE (object_type_1_id, object_type_2_id) IN (".join(" ,",$clauses).")"
        );
        $fx_db->exec(
            "DELETE FROM ".DB_TABLE_PREFIX." link_tbl
            WHERE (object_type_1_id, object_type_2_id) IN (".join(" ,",$clauses).")"
        );
    }

    protected static function find_lost_links()
    {
        global $fx_db;
        $stmt = $fx_db->prepare("
            SELECT * FROM ".DB_TABLE_PREFIX."link_tbl lt
            WHERE (lt.object_type_1_id, lt.object_type_2_id) NOT IN (
                SELECT object_type_1_id, object_type_2_id FROM ".DB_TABLE_PREFIX."link_type_tbl ltt
                JOIN ".DB_TABLE_PREFIX."object_type_tbl ott1 ON  ltt.object_type_1_id = ott1.object_type_id
                JOIN ".DB_TABLE_PREFIX."object_type_tbl ott2 ON  ltt.object_type_2_id = ott2.object_type_id
            )
        ");
        $result = array();
        $stmt->execute();
        foreach($stmt->fetchAll() as $row)
        {
            $key = $row["object_type_1_id"]."-".$row["object_1_id"]."-".$row["object_type_2_id"]."-".$row["object_2_id"];
            $result[$key] = $row;
        }
        return $result;
    }

    public function clear_lost_links($ids)
    {
        $clauses = array();
        foreach($ids as $object_type_1_id => $object_1_ids)
        {
            foreach($object_1_ids as $object_1_id => $object_type_2_ids)
            {
                foreach($object_type_2_ids as  $object_type_2_id => $object_2_ids)
                {
                    foreach($object_2_ids as $object_2_id => $nothing)
                    {
                        if($this->lost_links[$object_type_1_id."-".$object_1_id."-".$object_type_2_id."-".$object_2_id])
                        {
                            $clauses[] = "($object_type_1_id, $object_1_id, $object_type_2_id, $object_2_id)";
                        }
                    }
                }
            }
        }
        global $fx_db;
        $fx_db->exec(
            "DELETE FROM ".DB_TABLE_PREFIX."link_tbl
            WHERE (object_type_1_id, object_1_id, object_type_2_id, object_2_id) IN (".join(" ,",$clauses).")"
        );
    }

    protected static function find_types_without_tables()
    {
        global $fx_db;
        $stmt = $fx_db->prepare("
            SELECT ott.* FROM ".DB_NAME.".".DB_TABLE_PREFIX."object_type_tbl ott
            WHERE  0 = (
                SELECT COUNT(*) FROM  information_schema.tables
                WHERE table_schema = '".DB_NAME."'
                AND table_name = CONCAT('".DB_TABLE_PREFIX."object_type_',CAST(ott.object_type_id AS CHAR))
            )
        ");
        $stmt->execute();
        return reform_db_result_by_key_field($stmt->fetchAll(), "object_type_id");
    }

    public function clear_types_without_tables($ids)
    {
        $ids_to_remove = array();
        foreach($ids as $object_type_id => $nothing)
        {
            if($this->types_without_tables[$object_type_id])
            {
                $ids_to_remove[] = $object_type_id;
            }
        }
        if($ids_to_remove)
        {
            global $fx_db;
            $fx_db->exec("
                DELETE FROM ".DB_TABLE_PREFIX."object_type_tbl
                WHERE object_type_id IN (".join(", ", $ids_to_remove).")
            ");
        }

    }

    protected static function find_type_fields_without_columns()
    {
        global $fx_db;
        $stmt = $fx_db->prepare("
            SELECT ftt.* FROM ".DB_TABLE_PREFIX."field_type_tbl ftt
            WHERE ftt.name NOT IN (
                SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA =  '".DB_NAME."'
                AND TABLE_NAME = CONCAT('".DB_TABLE_PREFIX."object_type_',CAST(ftt.object_type_id AS CHAR))
            )
        ");
        $stmt->execute();
        $result = array();
        foreach($stmt->fetchAll() as $row)
        {
            $result[$row["object_type_id"].$row["name"]] = $row;
        }
        return $result;
    }

    public function clear_type_fields_without_columns($ids)
    {
        $clauses = array();
        foreach($ids as $object_type_id => $names)
        {
            foreach($names as $name => $value)
            {
                if($this->type_fields_without_columns[$object_type_id.$name])
                {
                    $clauses[] = "('$object_type_id', '$name')";
                }
            }
        }
        if($clauses)
        {
            global $fx_db;
            $fx_db->exec("
                DELETE FROM ".DB_TABLE_PREFIX."field_type_tbl
                WHERE (object_type_id, name) IN (".join(",", $clauses).")
            ");
        }
    }

    protected static function find_columns_without_type_fields()
    {
        global $fx_db;
        global $OBJECT_BASE_FIELDS;
		
        $exclude_columns_str = join(", ", array_map(function($s){return "'$s'";}, array_keys($OBJECT_BASE_FIELDS)));

        $stmt = $fx_db->prepare("
            SELECT ISC.TABLE_NAME as 'table_name', ISC.COLUMN_NAME as 'column_name'
            FROM INFORMATION_SCHEMA.COLUMNS ISC
            WHERE ISC.TABLE_SCHEMA =  '".DB_NAME."'
            AND ISC.COLUMN_NAME NOT IN (".$exclude_columns_str.")
            AND ISC.TABLE_NAME IN (
                SELECT CONCAT('".DB_TABLE_PREFIX."object_type_',CAST(ott.object_type_id AS CHAR))
                FROM ".DB_NAME.".".DB_TABLE_PREFIX."object_type_tbl ott
            )
            AND (ISC.TABLE_NAME, ISC.COLUMN_NAME) NOT IN (
                SELECT CONCAT('".DB_TABLE_PREFIX."object_type_',CAST(ftt.object_type_id AS CHAR)), ftt.name
                FROM ".DB_NAME.".".DB_TABLE_PREFIX."field_type_tbl ftt
            )
        ");
        $stmt->execute();
        $result = array();
        foreach($stmt->fetchAll() as $row)
        {
            $result[$row['table_name']."-".$row["column_name"]] = $row;
        }
        return $result;
    }

    public function clear_columns_without_type_fields($ids)
    {
        global $fx_db;
        foreach($ids as $table_name => $fields)
        {
            $columns = array();
            foreach($fields as $field_name => $nothing)
            {
                if($this->columns_without_type_fields[$table_name."-".$field_name])
                {
                    $columns[] = $field_name;
                }
            }
            if($columns)
            {
                $fx_db->exec("ALTER TABLE $table_name DROP COLUMN ".join(", ",$columns));
            }
        }
    }
}


function reform_db_result_by_key_field($data, $key_field, $remove_key_field = false)
{
    $result = array();
    if($remove_key_field)
    {
        foreach($data as $row)
        {
            $result[$row[$key_field]] = $row;
            unset($result[$row[$key_field]][$key_field]);
        }
    }
    else
    {
        foreach($data as $row)
        {
            $result[$row[$key_field]] = $row;
        }
    }
    return $result;
}