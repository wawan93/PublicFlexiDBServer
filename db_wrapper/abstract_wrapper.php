<?php

abstract class DB_Wrapper extends PDO
{
    abstract public function create_table($table);
    abstract public function get_primary_keys($table_name);
    abstract public function is_table_field_exists($table_name, $field);
    abstract public function add_column($table_name, $column);
    abstract public function update_column($table_name, $column);
    abstract public function rename_column($table, $old_name, $new_name);

    abstract public function update($table, $values, $where);

    protected $_fx_select = array(
        'table' => '',
        'fields' => array(),
        'where' => '',
        'order_by' => '',
        'limit' => '',
        'join' => array()
    );
	
	protected $result = array();
	protected $result_count = 0;	
	protected $current_row = 0;
	protected $last_error = NULL;
	
	protected $uid = 0;

	public function set_uid($uid) {
		$this->uid = $uid;
	}

	public function get_uid() {
		return $this->uid;
	}

	public function reset()
	{
		$this->current_row = 0;
		$this->result_count = 0;
		$this->result = array();
		$this->last_error = NULL;
		
        $this->_fx_select = array(
            'table' => '',
            'fields' => array(),
            'where' => '',
            'order_by' => '',
            'limit' => '',
            'join' => array()
        );
		
        return $this;
	}
	
	public function get_last_error()
	{
		return $this->last_error;
	}
	
	public function get_row_count()
	{
		return $this->result_count;
	}

    public function drop_table($table_name)
    {
        if (!$this->is_table_exists($table_name)) {
			$this->last_error = new FX_Error(__METHOD__, _('Table does not exists'));
            return $this->last_error;
		}

        if ($this->query("DROP TABLE $table_name")) {
            return true;
		}
        else {
            $this->last_error = new FX_Error(__METHOD__, _('Cannot drop table'));
			return $this->last_error;
		}
    }

    public function drop_column($table_name, $column_name)
    {
        if ($this->query("ALTER TABLE ".$table_name." DROP COLUMN $column_name;")) {
            return true;
		}
        else {
            $this->last_error = new FX_Error(__METHOD__, _('Unable to drop table column'));
			return $this->last_error;
		}
    }

    public function is_table_exists($table_name)
    {
		$pdo = $this->prepare("SELECT NULL FROM ".$table_name." LIMIT 1");
		return $pdo->execute() ? true : false;
    }
	
	public function get_table_count($table_name)
	{
		if (!$this->is_table_exists($table_name)) {
			return false;
		}

		$pdo = $this -> prepare("SELECT NULL FROM ".$table_name);
		$pdo -> execute();
	
		return intval($pdo->rowCount());
	}

    public function get_column_type($table_name, $column_name)
    {
        if (!$this->is_table_field_exists($table_name, $column_name)) {
            return new FX_Error(__METHOD__, _('Field does not exists')." [$table_name.$column_name]");
		}

        $prep = $this->prepare("SELECT data_type FROM information_schema.columns where table_name = :table_name AND  column_name = :column_name LIMIT 1;");
        $prep->bindValue('table_name', $table_name, PDO::PARAM_STR);
        $prep->bindValue('column_name', $column_name, PDO::PARAM_STR);
        
		if ($prep->execute()) {
            $res = strtolower($prep->fetchColumn());
			
			if ($res==='character varying') {
				$res = 'varchar';
			}
			elseif (in_array($res, array('int', 'tinyint'))) {
				$res = 'integer';
			}

            return $res;
        }
		else {
            $this->last_error = new FX_Error(__METHOD__, _('Cannot get column type')." [$table_name.$column_name]");
			return $this->last_error;
        }
    }

    public function set_type($table, $column_name, $column_value)
    {
        $field_type = $this->get_column_type($table, $column_name);
        
		if (is_fx_error($field_type)) {
			$this->last_error = $field_type;
			return $field_type;
		}

        if (in_array($field_type, array('integer', 'float'))) {
            if (!empty($column_value) && !(is_numeric($column_value) || is_float($column_value))) {
				$this->last_error = new FX_Error(__METHOD__, _('Invalid value type for')." [$table.$column_name]");
                return $this->last_error;
			}
            if (!settype($column_value,$field_type)) {
                $this->last_error = new FX_Error(__METHOD__, _('Invalid value type for')." [$table.$column_name]");
				return $this->last_error;
			}
            if (empty($column_value)) {
				$column_value = 0;
			}
        }

        return $column_value;
    }

    public function get_type_table_name($type_id)
	{
        return DB_TABLE_PREFIX.'object_type_'.intval($type_id);
    }

    public function select($table, $fields = array('*'), $alias='')
    {
        if (empty($fields)) {
			$fields = array('*');
		}

        $this->_fx_select['fields'][$alias ? $alias : $table] = (array)$fields;

        if ($alias) {
			$table .= " AS $alias";
		}

        if (!$this->_fx_select['table']) {
            $this->_fx_select['table'] = $table;
		}

        return $this;
    }

    public function where($conditions, $table = '', $return = false)
    {
        $where = array();
	
		$comparisons = array('=','<>','>','>=','<','<=', 'LIKE', 'IN');
		$operators = array('AND', 'OR', 'NOT', 'XOR');
		$ob = $cb = '';

		$conditions = (array)$conditions;

		if (!$conditions) {
			return $this->_fx_select['where'] = '';
		}

		$operator = 'AND';

		if (array_key_exists('operator', $conditions)) {
			$tmp = strtoupper($conditions['operator']);
			if (in_array($tmp, $operators)) {
				$operator = $tmp;
			}
			unset($conditions['operator']);
		}

		foreach ($conditions as $f=>$v)
		{
			//list ($field, $opr, $cmp) = explode(' ', $f);
			
			$ff = explode(' ', $f);

			$field = $ff[0];
			$opr = isset($ff[1]) ? $ff[1] : '';
			$cmp = isset($ff[2]) ? $ff[2] : '';

			$opr = strtoupper($opr);
			
			if (!in_array($opr, $comparisons)) {
				if (!$opr || !in_array($opr, $operators)) {
					$opr = 'AND';
				}
				$cmp = strtoupper($cmp);
			}
			else {
				$cmp = $opr;
				$opr = 'AND';
			}

			if (!$cmp || !in_array($cmp, $comparisons)) {
				$cmp = '=';
			}

			if (is_array($v)) {
				
				foreach ($v as &$vv) {
					if (!is_int($vv) && !is_float($vv)) {
						$vv = "'".$vv."'";
					}
				}
				
				$ob = "(";
				$cb = ")";
			}
			else {

				if (!is_int($v) && !is_float($v)) {
					$v = "'".$v."'";
				}
				
				$v = (array)$v;
			}

			if (strlen($table)) {
				$table = $table.'.';
			}

			switch($cmp) {
				case 'IN':
					$where[] = " $table$field IN (".implode(", ", $v).")";
				break;
				default:
					$where[] = " $ob$table$field $cmp ".implode(" $opr $table$field $cmp ", $v)."$cb";
			}
		}
		
		if ($where) {
			$where = ' WHERE '.implode(' '.$operator.' ', $where);
		}

		if ($return === false) {
			if ($where) {
				$this->_fx_select['where'] = $where;
			}
			return $this;
		}
		else {
			return $where;
		}
    }

    public function limit($limit=0, $offset=0)
    {
		$limit=intval($limit);
		$offset=intval($offset);

		if ($limit) {
			$this->_fx_select['limit'] = " LIMIT ".intval($limit);
		}
		
		if ($offset) {
			$this->_fx_select['limit'] = " OFFSET ".intval($offset);
		}

        return $this;
    }

    public function order($field, $order = 'ASC')
    {
        if(!$this->_fx_select['order_by']) {
            $this->_fx_select['order_by'] = ' ORDER BY ';
		}

        $this->_fx_select['order_by'] .= implode(',', (array)$field).' '.$order.' ';

        return $this;
    }

    public function join($fields, $table, $on, $type = '', $alias = '')
    {
        $this->select($table, $fields, $alias);
		
        $this->_fx_select['join'][$table] = array(
            'alias' => $alias,
            'on' => $on,
            'type' => $type,
        );

        return $this;
    }

	private function build_query()
	{
		$sql_fields = array();
	
		foreach ($this->_fx_select['fields'] as $table=>$fields) {
			foreach ($fields as $field) {
				$sql_fields[] = $table.'.'.$field;
			}
		}
	
		$sql = 'SELECT '.implode(', ', $sql_fields).' FROM '.$this->_fx_select['table'];
	
		if ($this->_fx_select['join']) {
			foreach ($this->_fx_select['join'] as $table => $join) {
				$sql .= " JOIN $table {$join['alias']} ON {$join['on']} ";
			}
		}
	
		$sql .= $this->_fx_select['where'];
		$sql .= $this->_fx_select['order_by'];
		$sql .= $this->_fx_select['limit'];
		
		return $sql;		
	}
	
	public function get_query()
	{
		return $this->build_query();
	}

	public function select_exec()
	{
		$query = $this->build_query();
	
		try {
			$pdo = $this->prepare($query);
			
			$this->reset();

			if ($pdo->execute()) {
				$this->result = $pdo->fetchAll();
				$this->result_count = $pdo->rowCount();//count($this->result);
				$this->current_row = 0;
				
				return $this->result_count;
			}
			else {
				$this->last_error = new FX_Error(__METHOD__, _('DB error'), $pdo->errorInfo());
				return $this->last_error;
			}
		}
		catch (PDOException $e) {
			$this->last_error = new FX_Error(__METHOD__, $e->getMessage());
			return $this->last_error;
		}
	}

	public function get()
	{		
		if ($this->current_row+1 <= $this->result_count) {
			return $this->result[$this->current_row++];
		}
		else {
			return false;
		}		
	}
	
	public function get_all()
	{
		return $this->result;
	}

    // Static

    public function insert($table, $values)
    {
		if (empty($values)) {
			$this->last_error = new FX_Error(__METHOD__, _('Empty field list'));
			return $this->last_error;
		}

		if ($this->_is_multi($values)) {

			$fields = array_keys(current($values));
			$insert_values = array();

			foreach ($values as $vv) {
				foreach ($vv as $k=>$v) {
/*					if (!strlen((string)$v)) {
						unset($vv[$k]);
					}
					elseif (!is_int($v) && !is_float($v) || (is_string($v) && !$v)) {
						$vv[$k] = "'".$v."'";
					}*/
					if (!is_int($v) && !is_float($v) || (is_string($v) && !$v)) {
						$vv[$k] = "'".$v."'";
					}
				}
				$insert_values[] = "(".implode(", ", $vv).")";
			}
			$query = "INSERT INTO $table (".implode(", ", $fields).') VALUES '.implode(', ', $insert_values);		
		}
		else {
			foreach ($values as $k=>$v) {
/*				if (!strlen((string)$v)) {
					unset($values[$k]);
				}
				elseif (!is_int($v) && !is_float($v) || (is_string($v) && !$v)) {
					$values[$k] = "'".$v."'";
				}*/
				if (!is_int($v) && !is_float($v) || (is_string($v) && !$v)) {
					$values[$k] = "'".$v."'";
				}
			}
			$query = "INSERT INTO $table (".implode(", ", array_keys($values)).") VALUES (".implode(", ", array_values($values)).")";
		}

		$pdo = $this->prepare($query);

		if ($pdo->execute()) {
			$last_id = $this->lastInsertId();
			return $last_id === 0 ? true : $this->lastInsertId();
		}

		$this->last_error = new FX_Error(__METHOD__, _('DB error uccured'), $pdo->errorInfo());

		return $this->last_error;		
    }

    public function delete($table, $where)
    {
        $where = $this->where($where, $table, true);

        $query = "DELETE FROM $table".$where;

        $pdo = $this->prepare($query);

        if (!$pdo->execute()) {
			$this->last_error = new FX_Error(__METHOD__, _('DB error'), $pdo->errorInfo());
			return $this->last_error;
        }

        return $pdo->rowCount();
    }

    public static function connect($db_host, $db_user, $db_pass, $db_name = false)
    {
		if (!defined('DB_DRIVER')) {
			define('DB_DRIVER', 'mysql');
		}

        require_once DB_DRIVER.'.php';

        if (!in_array(DB_DRIVER, PDO::getAvailableDrivers())) {
			throw new PDOException(_('Invalid DB driver'));
		}
		
		if ($db_name) {
			$db_name = ';dbname='.$db_name;
		}
		
		switch (DB_DRIVER) {
			case 'mysql':
				return new MySQL_DB_Wrapper('mysql:host='.$db_host.$db_name, $db_user, $db_pass);
			break;
			case 'pgsql':
				return new PgSQL_DB_Wrapper('pgsql:host='.$db_host.';port=5432'.$db_name.';user='.$db_user.';password='.$db_pass);
			break;
			default:
                throw new PDOException(_('Unsupported DB driver'));
		}
    }
	
	private function _is_multi($a)
	{
		$rv = array_filter($a,'is_array');
		return count($rv)>0 ? true : false;
	}
}

function is_fx_pdo($object)
{
	if (is_object($object) && is_a($object,'DB_Wrapper')) return true;
	return false;
}