<?php

class SQL_DB_Wrapper extends DB_Wrapper
{
    protected $sql_offset = 0;
    protected $sql_limit = 0;
    protected $last_insert_table;

    public function reset()
    {
        parent::reset();
        $this->sql_limit = 0;
        $this->sql_offset = 0;
        return $this;
    }

    public function lastInsertId($table='')
    {
        if(!$table)
            $table = $this->last_insert_table;

        $prep = $this->prepare("SELECT IDENT_CURRENT('$table') as id");
        if($prep->execute()) {
            $res = (array)$prep->fetch();

            if(array_key_exists('id',$res))
                return (int)$res['id'];
        }

        return false;
    }

    private function _prepare_columns($column) {
        global $OBJECT_BASE_FIELDS;

        $column['length'] = isset($column['length']) ? $column['length'] : 0;
        $column['default'] = isset($column['default']) ? $column['default'] : '';
        $column['after'] = "";

        if($column['default'] !== '') {
            if(!in_array(strtolower($column['type']),array('int','integer','float', 'datetime', 'date', 'time')))
                $column['default'] = "'".$column['default']."'";

            $column['default'] = ' DEFAULT '.$column['default'];
        }

        $column['type'] = $this->convert_type($column['type'], $column['length'] );

        return $column;
    }

    public function convert_type($fx_type, $fx_length=0)
    {
        $fx_type = strtolower($fx_type);

        switch ($fx_type) {
            case 'int':
            case 'integer':
                $sql_type = 'INT';
                break;
            case 'float':
                $sql_type = 'FLOAT';
                break;
            case 'text':
                $sql_type = 'VARCHAR(MAX)';
                break;
            case 'datetime':
            case 'time':
            case 'date':
                $sql_type = 'INT';
                break;
            case 'html':
                $sql_type = 'VARCHAR(MAX)';
                break;
            case 'varchar':
                $fx_length = (int)$fx_length;
                if ($fx_length > 255 || $fx_length <= 0) {
                    $fx_length = '255';
                }
                $sql_type = 'VARCHAR('.$fx_length.')';
                break;
            case 'url':
                $sql_type = 'VARCHAR(255)';
                break;
            default:
                $sql_type = 'VARCHAR(255)';
        }

        return $sql_type;
    }

    protected function _drop_constraints($table_name, $column_name)
    {
        $res = $this->exec("DECLARE @sql NVARCHAR(MAX)
                    WHILE 1=1
                    BEGIN
                        SELECT TOP 1 @sql = N'alter table $table_name drop constraint ['+dc.NAME+N']'
                        from sys.default_constraints dc
                        JOIN sys.columns c
                            ON c.default_object_id = dc.object_id
                        WHERE
                            dc.parent_object_id = OBJECT_ID('$table_name')
                        AND c.name = N'$column_name'
                        IF @@ROWCOUNT = 0 BREAK
                        EXEC (@sql)
                    END
                    ");
        if(!$res) {
            $error_info = $this->errorInfo();
            return $error_info;
        }
    }

    public function rename_column($table, $old_name, $new_name)
    {
        $this->_drop_constraints($table,$old_name);

        $query = "EXEC sp_rename @objname='$table.$old_name', @newname='$new_name', @objtype='COLUMN';";
        if (false === $this->exec($query)) {
            return new FX_Error(__METHOD__, print_r($this->errorInfo(), true));
        }
        return true;
    }

    public function drop_column($table_name, $column_name)
    {
        $this->_drop_constraints($table_name, $column_name);
        if ($this->query("ALTER TABLE ".$table_name." DROP COLUMN $column_name;")) {
            return true;
        }
        else {
            $this->last_error = new FX_Error(__METHOD__, _('Unable to drop table column'), $this->errorInfo());
            return $this->last_error;
        }
    }

    public function add_column($table_name, $column)
    {
        $column = $this->_prepare_columns($column);

        $query = 'ALTER TABLE '.$table_name." ADD {$column['name']} {$column['type']} {$column['default']} ";

        $prep = $this->prepare($query);
        if(!$res = $prep->execute()) {
            $error_info = $prep->errorInfo();
            return new FX_Error(__METHOD__, _('Cannon add column to table'),$error_info[2]);
        } else {
            return true;
        }

    }

    public function update_column($table_name, $column)
    {
        $this->_drop_constraints($table_name,$column['name']);

        $column = $this->_prepare_columns($column);

        $query = 'ALTER TABLE '.$table_name." ALTER COLUMN {$column['name']} {$column['type']} null";

//        if($column['default'])
//            $query .= " ". $column['default']."";

        $prep = $this->prepare($query);
        if(!$prep->execute()) {
            $error_info = $prep->errorInfo();
            return new FX_Error(__METHOD__, _('Cannon add column to table'),$error_info[2]);
        } else {
            return true;
        }
    }

    protected function _create_primary_key(&$column, $keys)
    {
        $return = " PRIMARY KEY";

        if(in_array($column['name'],(array)$keys['primary'])) {
            if(strtolower($column['type']) === 'int' || strtolower($column['type']) === 'integer') {
                $return .= " IDENTITY(1,1)";
                unset($column['null']);
                unset($column['default']);
            }
        }

        return $return;
    }

    protected function _create_indexes($keys,$table_name)
    {
        if(array_key_exists('index',$keys)) {
            foreach ((array)$keys['index'] as $index_name => $index) {
                if (is_array($index))
                    $index = implode("', '", $index);

                $index_name = $index_name ? $index_name : $table_name.normalize_string($index);

                if(!$this->query("CREATE NONCLUSTERED INDEX $index_name ON $table_name ($index)")) {
                    $this->last_error = new FX_Error(__METHOD__, _('SQL Error occurred while adding foreign keys and indexes'),$this->errorInfo());
                    return $this->last_error;
                }
            }
        }

        if(array_key_exists('unique',$keys)) {
            foreach ((array)$keys['unique'] as $index_name=>$unique) {
                if (is_array($unique))
                    $unique = implode("', '", $unique);

                $index_name = $index_name ? $index_name : $table_name.normalize_string($unique);

                if(!$this->query("CREATE NONCLUSTERED UNIQUE INDEX $index_name ON $table_name ($unique)")) {
                    $this->last_error = new FX_Error(__METHOD__, _('SQL Error occurred while adding foreign keys and indexes'),$this->errorInfo());
                    return $this->last_error;
                }
            }
        }
        return true;
    }

    public function create_table($table)
    {
        $columns = array();
        foreach($table['columns'] as $column_name => $column) {
            if (array_key_exists('name', $column))
                $column_name = $column['name'];

            $column['length'] = array_key_exists('length',$column) ? (int)$column['length'] : 0;
            $columns[$column['name']] = "".$column['name']." ".$this->convert_type($column['type'],$column['length']);

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
                $columns[$column['name']] .= '';

            if(array_key_exists('default', $column)) {
                if(!in_array(strtolower($column['type']), array('int','integer','float')))
                    $column['default'] = "'".$column['default']."'";
                elseif(!is_numeric($column['default']))
                    $column['default'] = 0;
                $columns[$column['name']] .= ' DEFAULT '.$column['default'];
            }

        }

        $query = $this->prepare("CREATE TABLE ".normalize_string($table['name'])." (".implode(",\n", $columns).");");
        $query->execute();
        if ($query->errorCode()>0) {
            $error_info = $this->errorInfo();
            $this->last_error = new FX_Error(__FUNCTION__, _('SQL Error occurred while creating type table'), $error_info);
            return $this->last_error;
        }

        // TODO: fix this
//        if (array_key_exists('keys', $table) && !empty($table['keys'])) {
//            $this->_create_indexes((array)$table['keys'], $table['name']);
//        }

        if($this->last_error !== null) {
            $this->drop_table($table['name']);
            $tmp = $this->last_error;
            $this->reset();
            return $tmp;
        }

        return true;
    }

    public function get_primary_keys($table_name)
    {
        $res = $this -> prepare("SELECT column_name FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE OBJECTPROPERTY(OBJECT_ID(constraint_name), 'IsPrimaryKey') = 1 AND table_name = '$table_name'");

        if($res -> execute())
        {
            $result = array();

            foreach($res -> fetchAll() as $row)
            {
                $result[] = $row["column_name"];
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
        $res = $this->prepare("SELECT column_name FROM information_schema.columns WHERE table_name='$table_name' and column_name='$field'");
        $res -> execute();
        return (bool)$res -> rowCount();
    }


    public function limit($limit=0, $offset=0)
    {
        $this->sql_limit = intval($limit);
        $this->sql_offset = intval($offset);

        return $this;
    }

    public function select_exec()
    {
        $query = $this->build_query();

        try {
            $pdo = $this->prepare($query);

            if ($pdo->execute()) {
                $result = $pdo->fetchAll();

                if($this->sql_limit || $this->sql_offset)
                    $result = array_slice($result,$this->sql_offset, $this->sql_limit);

                $this->reset();

                $this->result = $result;
                $this->result_count = count($result);//count($this->result);
                $this->current_row = 0;

                return $this->result_count;
            }
            else {
                $this->reset();
                $this->last_error = new FX_Error(__METHOD__, _('DB error'), $pdo->errorInfo());
                return $this->last_error;
            }
        }
        catch (PDOException $e) {
            $this->last_error = new FX_Error(__METHOD__, $e->getMessage());
            return $this->last_error;
        }
    }

    public function update($table, $values, $where)
    {
        $query_fields = array();
        foreach ($values as $field_name => $val) {
            $val = $this->set_type($table,$field_name,$val);
            if(is_fx_error($val)) return $val;
            if($val === null || $val === '') $val='NULL';
            $query_fields[$field_name] = $val;
        }

        $query = "UPDATE $table  SET ";
        $values = array();

        foreach ($query_fields as $field_name => $val) {
            if(!is_numeric($val) && !is_float($val) && $val !== 'NULL')
                $val = "'$val'";

            $values[] = "$field_name=$val";
        }
        $query .= implode(', ',$values);

        if($where) {
            $where = $this->where($where,$table,true);
            if(is_fx_error($where))
                return $where;
            else
                $query .= " WHERE $where";
        }

        $prep = $this->prepare($query);

        $res = $prep->execute();

        if(!$res) {
            $error_info = $prep->errorInfo();
            return new FX_Error(__FUNCTION__, 'DB error occured. '.$error_info[2]);
        } else {
            return true;
        }
    }

}
