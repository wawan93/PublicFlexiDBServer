<?php

class PgSQL_DB_Wrapper extends DB_Wrapper
{
    public function convert_type($fx_type, $fx_length=0)
    {
        $fx_type = strtolower($fx_type);

        switch ($fx_type) {
            case 'int':
            case 'integer':
                $mysql_type = 'integer';
                break;
            case 'float':
            case 'text':
                $mysql_type = $fx_type;
                break;
            case 'datetime':
            case 'time':
            case 'date':
                $mysql_type = 'integer';
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

    public function lastInsertId() {
        $last = $this->query('select lastval()');
        if($last) {
            $last = $last->fetch();
            $last = $last['lastval'];
        }
        return $last;
    }

    public function rename_column($table, $old_name, $new_name)
    {
        $pdo = $this->prepare("ALTER TABLE ".$table." RENAME COLUMN $old_name TO $new_name");

        if (!$pdo->execute()) {
            return new FX_Error(__METHOD__, print_r($this->errorInfo(), true));
        }
        return true;
    }

    private function _prepare_columns($column) {
        global $OBJECT_BASE_FIELDS;

        $column['length'] = isset($column['length']) ? $column['length'] : 0;
        $column['default'] = isset($column['default']) ? $column['default'] : '';
        $column['after'] = "";

        $column['type'] = $this->convert_type($column['type'], $column['length'] );

        if($column['default'] !== '') {
            if(!in_array($column['type'],array('integer','float')))
                $column['default'] = "'".$column['default']."'";
            else
                settype($column['default'],$column['type']);

            $column['default'] = ' DEFAULT '.$column['default'];
        }

        return $column;
    }

    public function add_column($table_name, $column)
    {
        $column = $this->_prepare_columns($column);

        $query = 'ALTER TABLE "'.$table_name."\" ADD COLUMN \"{$column['name']}\" {$column['type']} {$column['default']} ";

        $prep = $this->prepare($query);
        if(!$res = $prep->execute()) {
            $error_info = $this->errorInfo();
            return new FX_Error(__METHOD__, _('Cannon add column to table'),$error_info[2]);
        } else {
            return true;
        }

    }

    public function update_column($table_name, $column)
    {
        $this->exec("ALTER TABLE \"{$table_name}\" ALTER COLUMN \"{$column['name']}\" DROP DEFAULT;");

        $column = $this->_prepare_columns($column);

        if($column['type'] === 'integer' || $column['type'] === 'float')
            $column['type'] .= " USING 0 ";
        if($column['type'] === 'varchar')
            $column['type'] .= " USING left(".$column['name'].", ".$column['length'].") ";

        $query = 'ALTER TABLE "'.$table_name."\" ALTER COLUMN \"{$column['name']}\" TYPE {$column['type']}";

        if($column['default'])
            $query .= ",  ALTER COLUMN \"{$column['name']}\" SET ". $column['default'].";";

        $prep = $this->prepare($query);
        if(!$prep->execute()) {
            $error_info = $this->errorInfo();
            return new FX_Error(__METHOD__, _('Cannon add column to table'),$error_info[2]);
        } else {
            return true;
        }
    }

    protected function _create_indexes($keys,$table_name)
    {
        if(array_key_exists('index',$keys)) {
            foreach ((array)$keys['index'] as $index_name => $index) {
                $index_name = $index_name ? $index_name : '';
                if(!$this->query("CREATE INDEX $index_name ON $table_name ($index)")) {
                    $this->last_error = new FX_Error(__METHOD__, _('SQL Error occurred while adding foreign keys and indexes'),$this->errorInfo());
                    return $this->last_error;
                }
            }
        }

        if(array_key_exists('unique',$keys)) {
            foreach ((array)$keys['unique'] as $index_name=>$unique) {
                if (is_array($unique))
                    $unique = implode(', ', $unique);

                if(!$this->query("CREATE UNIQUE INDEX $index_name ON $table_name ($unique)")) {
                    $this->last_error = new FX_Error(__METHOD__, _('SQL Error occurred while adding foreign keys and indexes'),$this->errorInfo());
                    return $this->last_error;
                }
            }
        }
        return true;
    }

    protected function _create_primary_key(&$column, $table)
    {
        $return = ' PRIMARY KEY ';

        if($column['type'] === 'int' || $column['type'] === 'integer') {
            $query = "CREATE SEQUENCE i_".$table['name']."; ";
            if (!$this->query($query)) {
                $error_info = $this->errorInfo();
                $this->last_error = new FX_Error(__METHOD__, _('SQL Error occurred while creating SEQUENCE'), $error_info);
                return $this->last_error;
            }

            $return .= 'DEFAULT NEXTVAL(\'i_'.$table['name']."')";
            unset($column['default']);
        }

        $column['not_null'] = true;

        return $return;
    }

    public function create_table($table)
    {
        $columns = array();
        foreach ($table['columns'] as $column_name => $column) {
            if (array_key_exists('name', $column))
                $column_name = $column['name'];

            $column['length'] = array_key_exists('length', $column) ? (int)$column['length'] : 0;
            $columns[$column_name] = "\"" . $column_name . "\" " . $this->convert_type($column['type'], $column['length']);

            if (array_key_exists('keys', $table) && array_key_exists('primary', (array)$table['keys'])) {
                if (in_array($column_name, (array)$table['keys']['primary'])) {
                    $primary_key = $this->_create_primary_key($column, $table);

                    if (!is_fx_error($primary_key))
                        $columns[$column_name] .= $primary_key;
                }
            }

            if (array_key_exists('not_null', $column) && $column['not_null'] === true)
                $columns[$column_name] .= ' NOT NULL ';
            else
                $columns[$column_name] .= ' NULL ';

            if (array_key_exists('default', $column) && $column['default'] !== false) {
                if (!in_array($column['type'], array('integer', 'float')))
                    $column['default'] = "'" . $column['default'] . "'";
                elseif (!is_numeric($column['default']))
                    $column['default'] = 0;

                $columns[$column_name] .= ' DEFAULT ' . $column['default'];
            }

//            if (array_key_exists('keys', $table) && array_key_exists('foreign', (array)$table['keys'])) {
//                foreach ((array)$table['keys']['foreign'] as $foreign) {
//                    if ($foreign['key'] === $column_name)
//                        $columns[$column_name] = " REFERENCES " . $foreign['ref'];
//                }
//            }
        }

        $query = "CREATE TABLE " . $table['name'] . " (" . implode(', ', $columns) . ");";

        if (!$this->query($query)) {
            $error_info = $this->errorInfo();
            $this->last_error = new FX_Error(__METHOD__, _('SQL Error occurred while creating type table'), $error_info);
            $tmp = $this->last_error;
            $this->drop_table($table['name']);
            $this->reset();
            return $tmp;
        }

        if (array_key_exists('keys', $table) && !empty($table['keys'])) {
            $this->_create_indexes((array)$table['keys'], $table['name']);
        }

        return true;
    }

    public function drop_table($table_name)
    {
        if(!$this->is_table_exists($table_name)) {
            $this->last_error = new FX_Error(__METHOD__, _('Table does not exists'));
            return $this->last_error;
        }

        $drop_tbl = $this->exec("DROP TABLE $table_name;");
        $drop_seq = $this->exec("DROP SEQUENCE i_$table_name;");

        if($drop_seq!==false && $drop_tbl!==false) {
            return true;
        } else {
            $error_info = $this->errorInfo();
            return new FX_Error(__METHOD__, _('Cannot drop table'),$error_info[2]);
        }
    }

    public function get_primary_keys($table_name){
        $res = $this->prepare("SELECT
                                          pg_attribute.attname
                                        FROM pg_index, pg_class, pg_attribute
                                        WHERE
                                          pg_class.oid = '$table_name'::regclass AND
                                          indrelid = pg_class.oid AND
                                          pg_attribute.attrelid = pg_class.oid AND
                                          pg_attribute.attnum = any(pg_index.indkey)
                                          AND indisprimary
                                        ");

        if($res->execute()){
            $results = array();

            foreach($res->fetchAll() as $row) {
                $results[] = $row['attname'];
            }

            if($results) return count($results) == 1 ? $results[0] : $results;
            else return false;
        } else {
            $error_info = $res -> errorInfo();
            return new FX_Error(__METHOD__, _('DB error occured. '),$error_info[2]);
        }
    }

    public function is_table_field_exists($table_name, $field){
        $res = $this->prepare("SELECT column_name FROM information_schema.columns WHERE table_name=:table_name and column_name=:field;");
        $res -> bindValue(":table_name", $table_name, PDO::PARAM_STR);
        $res -> bindValue(":field", $field, PDO::PARAM_STR);
        $res -> execute();
        return (bool)$res -> rowCount();
    }

    /*
    * QUERY
    */

    public function delete($table, $where)
    {
        $where = $this->where($where, $table, true);

        $query = "DELETE FROM $table WHERE ".$where;

        $pdo = $this->prepare($query);

        if (!$pdo->execute()) {
            $error_info = $pdo->errorInfo();
            if($error_info[0] == '22P02') { // if we give not int value for int field
                return 0;
            }

            $this->last_error = new FX_Error(__METHOD__, _('DB error'), $pdo->errorInfo());
            return $this->last_error;
        }

        return $pdo->rowCount();
    }

    public function update($table, $values, $where)
    {
        $query_fields = array();
        foreach ($values as $field_name => $val) {
            if($val === null) continue;
            $val = $this->set_type($table,$field_name,$val);
            if(is_fx_error($val)) return $val;
            $query_fields[$field_name] = $val;
        }

        $query = "UPDATE $table  SET (\"".implode('", "',array_keys($query_fields)).'") = (:'.implode(', :',array_keys($query_fields)).')';

        if($where) {
            $where = $this->where($where,$table,true);
            if(!is_string($where))
                return $where;
            else
                $query .= " WHERE $where";
        }

        $prep = $this->prepare($query);

        $res = $prep->execute($query_fields);
        if(!$res) {
            $error_info = $this -> errorInfo();
            return new FX_Error(__METHOD__, _('DB error occured. ').$error_info[2]);
        } else {
            return true;
        }
    }

}
