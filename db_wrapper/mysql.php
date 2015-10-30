<?php

global $db_reserved_words;
//mysql 57 reserved words
$db_reserved_words = array('ADD','ALL','ALTER','ANALYZE','AND','AS','ASC','BEFORE','BETWEEN','BIGINT','BINARY','BLOB','BOTH','BY','CASCADE','CASE','CHANGE','CHAR','CHARACTER','CHECK','COLLATE','COLUMN','COLUMNS','CONSTRAINT','CONVERT','CREATE','CROSS','CURRENT_DATE','CURRENT_TIME','CURRENT_TIMESTAMP','CURRENT_USER','DATABASE','DATABASES','DAY_HOUR','DAY_MICROSECOND','DAY_MINUTE
','DAY_SECOND','DEC','DECIMAL','DEFAULT','DELAYED','DELETE','DESC','DESCRIBE','DISTINCT','DISTINCTROW','DIV','DOUBLE','DROP','DUAL','ELSE','ENCLOSED','ESCAPED','EXISTS
','EXPLAIN','FALSE','FIELDS','FLOAT','FLOAT4','FLOAT8','FOR','FORCE','FOREIGN','FROM','FULLTEXT','GRANT','GROUP','HAVING','HIGH_PRIORITY','HOUR_MICROSECOND','HOUR_MINUTE','HOUR_SECOND','IF','IGNORE','IN','INDEX','INFILE','INNER','INSERT','INT','INT1','INT2','INT3','INT4','INT8','INTEGER','INTERVAL','INTO','IS','JOIN','KEY','KEYS','KILL','LEADING','LEFT','LIKE','LIMIT','LINES','LOAD','LOCALTIME','LOCALTIMESTAMP','LOCK','LONG','LONGBLOB','LONGTEXT','LOW_PRIORITY','MATCH','MEDIUMBLOB','MEDIUMINT','MEDIUMTEXT','MIDDLEINT','MINUTE_MICROSECOND','MINUTE_SECOND','MOD','NATURAL','NOT','NO_WRITE_TO_BINLOG','NULL','NUMERIC','ON','OPTIMIZE','OPTION','OPTIONALLY','OR','ORDER','OUTER','OUTFILE','PRECISION','PRIMARY','PRIVILEGES','PROCEDURE','PURGE','READ','REAL','REFERENCES','REGEXP','RENAME','REPLACE','REQUIRE','RESTRICT','REVOKE','RIGHT','RLIKE','SECOND_MICROSECOND','SELECT','SEPARATOR','SET','SHOW','SMALLINT','SONAME','SPATIAL','SQL_BIG_RESULT','SQL_CALC_FOUND_ROWS','SQL_SMALL_RESULT','SSL','STARTING','STRAIGHT_JOIN','TABLE','TABLES','TERMINATED','THEN','TINYBLOB','TINYINT','TINYTEXT','TO','TRAILING','TRUE','UNION','UNIQUE','UNLOCK','UNSIGNED','UPDATE','USAGE','USE','USING','UTC_DATE','UTC_TIME','UTC_TIMESTAMP','VALUES','VARBINARY','VARCHAR','VARCHARACTER','VARYING','WHEN','WHERE','WITH','WRITE','XOR','YEAR_MONTH','ZEROFILL','CHECK','FORCE','LOCALTIME','LOCALTIMESTAMP','REQUIRE','SQL_CALC_FOUND_ROWS','SSL','XOR','BEFORE','COLLATE','CONVERT','CURRENT_USER','DAY_MICROSECOND','DIV','DUAL','FALSE','HOUR_MICROSECOND','MINUTE_MICROSECOND','MOD','NO_WRITE_TO_BINLOG','SECOND_MICROSECOND','SEPARATOR','SPATIAL','TRUE','UTC_DATE','UTC_TIME','UTC_TIMESTAMP','VARCHARACTER','ASENSITIVE','CALL','CONDITION','CONNECTION','CONTINUE','CURSOR','DECLARE','DETERMINISTIC','EACH','ELSEIF','EXIT','FETCH','GOTO','INOUT','INSENSITIVE','ITERATE','LABEL','LEAVE','LOOP','MODIFIES','OUT','READS','RELEASE','REPEAT','RETURN','SCHEMA','SCHEMAS','SENSITIVE','SPECIFIC','SQL','SQLEXCEPTION','SQLSTATE','SQLWARNING','TRIGGER','UNDO','UPGRADE','WHILE','ACCESSIBLE','LINEAR','MASTER_SSL_VERIFY_SERVER_CERT','RANGE','READ_ONLY','READ_WRITE','GENERAL','IGNORE_SERVER_IDS','MASTER_HEARTBEAT_PERIOD','MAXVALUE','RESIGNAL','SIGNAL','SLOW','GET','IO_AFTER_GTIDS','IO_BEFORE_GTIDS','MASTER_BIND','ONE_SHOT','PARTITION','SQL_AFTER_GTIDS','SQL_BEFORE_GTIDS','NONBLOCKING');

class MySQL_DB_Wrapper extends DB_Wrapper
{
    public function __construct($dsn, $username, $passwd)
    {
        parent::__construct($dsn, $username, $passwd);
        $this->exec("SET NAMES 'utf8'");
        $this->exec("SET sql_mode = 'STRICT_ALL_TABLES';");
    }

    private function _prepare_columns( $column,$table='') {
        global $OBJECT_BASE_FIELDS;

		$base_fields_instance = $OBJECT_BASE_FIELDS;

        $column['length'] = isset($column['length']) ? $column['length'] : 0;
        $column['after'] = isset($column['after']) ? normalize_string($column['after']) : '';
        $column['default'] = isset($column['default']) ? $column['default'] : '';

        if (strtolower($column['after']) == 'first') {
            $last_base_field = array_pop($base_fields_instance);
            $column['after'] = " AFTER {$last_base_field['name']}";
        } elseif ($column['after'] && $this->is_table_field_exists($table,$column['after'])) {
            $column['after'] = " AFTER {$column['after']}";
        } else {
            $column['after'] = "";
        }

        $column['type'] = $this->convert_type($column['type'], $column['length'] );

        if($column['default'] !== '' && $column['type'] !== 'text' ){
            if(!in_array($column['type'],array('int','float')))
                $column['default'] = "'".$column['default']."'";
            else
                settype($column['default'],$column['type']);
            $column['default'] = ' DEFAULT '.$column['default'];
        } elseif($column['type'] == 'text') {
            $column['default'] = '';
        }


        return $column;
    }

    public function convert_type($fx_type, $fx_length=0)
    {
        $fx_type = strtolower($fx_type);

        switch ($fx_type) {
            case 'int':
			case 'integer':
                $mysql_type = 'int'.($fx_length > 0 ? '('.$fx_length.')' : '');
                break;
            case 'float':
            case 'text':
                $mysql_type = $fx_type;
                break;
            case 'datetime':
            case 'time':
            case 'date':
                $mysql_type = 'int';
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
                $mysql_type = 'varchar(255)';
                break;
            default:
                $mysql_type = 'varchar(255)';
        }

        return $mysql_type;
    }

    public function rename_column($table, $old_name, $new_name)
    {
        $res = $this->query("SHOW COLUMNS FROM $table WHERE field='$old_name'");
        $res = $res->fetch();
        if(!$res) return new FX_Error('Column does not exists');
        $type = $res['Type'];
        $pdo = $this->prepare("ALTER TABLE ".$table." CHANGE $old_name $new_name $type");

        if (!$pdo->execute()) {
            $error_info = $this->errorInfo();
            return new FX_Error(__FUNCTION__, print_r($this->errorInfo(), true));
        }
        return true;
    }

    public function add_column($table_name,  $column)
    {
        $column = $this->_prepare_columns($column,$table_name);

        $query = 'ALTER TABLE '.$table_name." ADD {$column['name']} {$column['type']} {$column['default']} {$column['after']}";

		//echo $query;

        if(!$this->query($query)){
            $error_info = $this->errorInfo();
            return new FX_Error(__METHOD__, _('Unable to add column into the table')." $table_name({$column['name']})");
        }

        return true;
    }

    public function update_column($table_name,  $column)
    {
        $column = $this->_prepare_columns($column,$table_name);

        $query = 'ALTER TABLE '.$table_name." MODIFY {$column['name']} {$column['type']} {$column['default']} {$column['after']}";

        if(!$this->query($query)){
            $error_info = $this->errorInfo();
			return new FX_Error(__METHOD__, _('Unable to update column of the table')." $table_name({$column['name']})");
        }

        return true;
    }

    protected function _create_primary_key(&$column, $keys)
    {
        $return = " PRIMARY KEY";

        if(in_array($column['name'],(array)$keys['primary'])) {
            if(strtolower($column['type']) === 'int' || strtolower($column['type']) === 'integer') {
                $return .= " AUTO_INCREMENT";
                $column['not_null']=true;
                unset($column['default']);
            }
        }

        return $return;
    }

    protected function _create_indexes($table_name, $table_keys)
    {
        if(!$table_keys) return true;

        $query = "ALTER TABLE `".$table_name.'` ';
        $keys = array();
        if(array_key_exists('indexes',$table_keys)) {
            foreach ($table_keys['indexes'] as $index_name => $index) {
                $keys[] = "ADD INDEX(`$index`)";
            }
        }
        if(array_key_exists('unique',$table_keys)) {
            foreach ((array)$table_keys['unique'] as $unique) {
                if (is_array($unique)) {
                    $unique = implode('`, `', $unique);
                }
                if($unique) {
                    $keys[] = "ADD UNIQUE INDEX(`$unique`)";
                }
            }
        }
        if(array_key_exists('foreign',$table_keys)) {
            foreach ((array)$table_keys['foreign'] as $foreign) {
                if($foreign['key'] && $foreign['ref']) {
                    $keys[] = "ADD FOREIGN KEY (`{$foreign['key']}`) REFERENCES " . $foreign['ref'];
                }
            }
        }

        if($keys){
            $query .= implode(', ', $keys);
            $res=$this->query($query);
            if (!$res) {
                $this->last_error = new FX_Error(__FUNCTION__, _('SQL Error occurred while adding foreign keys and indexes'), $this->errorInfo());
                return $this->last_error;
            }
        }

        return true;
    }

    public function create_table( $table)
    {
        $columns = array();
        foreach($table['columns'] as $column_name => $column) {
            if (array_key_exists('name', $column))
                $column_name = $column['name'];

            $column['length'] = array_key_exists('length',$column) ? (int)$column['length'] : 0;
            $columns[$column['name']] = "`".$column['name']."` ".$this->convert_type($column['type'],$column['length']);

            if (array_key_exists('keys', $table) && array_key_exists('primary', (array)$table['keys'])) {
                if (in_array($column_name, (array)$table['keys']['primary'])) {
                    $primary_key = $this->_create_primary_key($column, $table['keys']);

                    if (!is_fx_error($primary_key))
                        $columns[$column_name] .= $primary_key;
                }
            }

            if(array_key_exists('not_null', $column) && $column['not_null']===true)
                $columns[$column['name']] .= ' NOT NULL ';
            else
                $columns[$column['name']] .= ' NULL ';

            if(array_key_exists('default', $column)) {
                if(!in_array($column['type'], array('int','integer','float')))
                    $column['default'] = "'".$column['default']."'";
                elseif(!is_numeric($column['default']))
                    $column['default'] = 0;
                $columns[$column['name']] .= ' DEFAULT '.$column['default'];
            }

        }
        $query = "CREATE TABLE `".normalize_string($table['name'])."` (".implode(",\n", $columns).");";

        if (!$this->query($query)) {
            $error_info = $this->errorInfo();
            return new FX_Error(__FUNCTION__, _('SQL Error occurred while creating new table').' ['.$table['name'].']');
        }

        $this->_create_indexes($table['name'],(array)$table['keys']);

        return true;
    }

    public function get_primary_keys($table_name)
    {
        $res = $this -> prepare("SHOW INDEX FROM ".normalize_string($table_name)." WHERE Key_name = 'PRIMARY'");

        if($res -> execute())
        {
            $result = array();

            foreach($res -> fetchAll() as $row)
            {
                $result[] = $row["Column_name"];
            }

            if($result) return count($result) == 1 ? $result[0] : $result;
            else return false;
        }
        else
        {
            $error_info = $res -> errorInfo();
            return new FX_Error(__FUNCTION__, 'DB error occured. '.$error_info[2]);
        }
    }

    public function is_table_field_exists($table_name, $field)
    {
        $res = $this->prepare("SHOW COLUMNS FROM ".normalize_string($table_name)." WHERE Field=:field");
        $res -> bindValue(":field", $field, PDO::PARAM_STR);
        $res -> execute();
        return (bool)$res -> rowCount();
    }


    public function update($table, $values, $where)
    {
/*        $query_fields = array();
        foreach ($values as $field_name => $val) {
            if($val === null) continue;
            $val = $this->set_type($table,$field_name,$val);
            if(is_fx_error($val)) return $val;
            $query_fields[$field_name] = $val;
        }

        $query = "UPDATE $table  SET ";
        $values = array();
        foreach ($query_fields as $field_name => $val) {
            $values[] = "$field_name = :$field_name";
        }
        $query .= implode(', ',$values);

        if($where) {
            $where = $this->where($where,$table,true);
            if(!is_string($where))
                return $where->get_last_error();
            else
                $query .= $where;
        }

        $prep = $this->prepare($query);

        $res = $prep->execute($query_fields);

        if(!$res) {
            $error_info = $prep->errorInfo();
            return new FX_Error(__FUNCTION__, 'DB error occured. '.$error_info[2]);
        } else {
            return true;
        }
		
		
		
		*/
		
		
        if (empty($values)) {
			$this->last_error = new FX_Error(__METHOD__, _('Empty field list'));
			return $this->last_error;
		}

		$where = $this->where($where, $table, true);
        if($where) {
            $where = " $where";
        }

		$query_fields = array();
		
		foreach ($values as $f=>$v) {
			if (!is_int($v) && !is_float($v)) {
				$v = "'".$v."'";
			}
			
			$query_fields[] = "`$f` = $v";
		}
		
		$query = "UPDATE $table SET ".implode(", ", $query_fields)." ".$where;

		$pdo = $this->prepare($query);

		if ($pdo->execute()) {
			return true;
		}

		$this->last_error = new FX_Error(__METHOD__, _('DB error'), $pdo->errorInfo());
		return $this->last_error;	
    }

}
