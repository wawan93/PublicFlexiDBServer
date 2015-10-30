<?php

class RightsException extends Exception {}

class FX_Query_Object
{
    public $types_tree = array();
    public $has_joins = false;
    public $code = array();
    public $main_type_id = 0;
    public $set_id = 0;
    public $schema_id = 0;
    public $hide_empty = 0;
    public $object_fields = array();
    public $filter_by_set = false;

    /**
     * @param array $joined_types
     * @return array
     */
    protected function _get_types_tree($joined_types = array())
    {
        $types = array();
        if (!empty($joined_types)) {
            foreach ($joined_types as $k => $parent) {
                foreach ($parent as $child) {
                    if (!empty($child)) {
                        $type_fields = get_type_fields($child);
                        if($type_fields === false || is_fx_error($type_fields)) {
                            throw new Exception(_("Type doesn't exists") . " " . $child);
                        }
                        $types[$k][$child] = array_keys($type_fields);
                    }
                }
            }
        } else {
            foreach ($this->code as $field) {
                $field = (array)$field;
                if (in_array('parent_type', $field)) {
                    $parent_type = intval($field['parent_type']);
                    if (!$types[$parent_type][$field['object_type_id']]) {
                        $type_fields = get_type_fields($field['object_type_id']);
                        if($type_fields === false || is_fx_error($type_fields)) {
                            throw new Exception(_("Type doesn't exists") . " [" . $field['object_type_id']."]");
                        }
                        $types[$parent_type][$field['object_type_id']] = array_keys($type_fields);
                    }
                }
            }
        }
        $types[0] = array(
            $this->main_type_id => array_keys(
                get_type_fields($this->main_type_id)
            )
        );
        if (isset($types[$this->main_type_id]) && !empty($types[$this->main_type_id])) {
            $this->has_joins = true;
        }

        return $types;
    }

    protected function _get_main_type($main_type)
    {
        if (!$main_type) {
            if ($this->has_joins) {
                throw new Exception('You must set main type ID');
            }
            $main_type = current($this->code);
            $this->main_type_id = $main_type['object_type_id'];
        } else {
            $this->main_type_id = $main_type;
        }

        $main_type = get_type($this->main_type_id);
        if($main_type === false || is_fx_error($main_type)) {
            throw new Exception(_("Main type doesn't exists"));
        }
    }

    /**
     * @param $array - query code
     * @param int|array $main_type_id
     * @param array $joined_types
     */
    public function __construct(
        $array,
        $main_type_id = 0,
        $joined_types = array()
    ) {
        $this->code = $array;
        $this->_get_main_type($main_type_id);
        $this->types_tree = $this->_get_types_tree($joined_types);
    }

    /**
     * @param $array - query code
     * @param int $main_type_id
     * @param array $joined_types
     * @param int $hide_empty
     * @return FX_Query_Object
     */
    public static function load_from_code(
        $array,
        $main_type_id = 0,
        $joined_types = array(),
        $hide_empty = 0
    ) {
        $obj = new FX_Query_Object($array, $main_type_id, $joined_types);
        $obj->hide_empty = $hide_empty;
        $obj->filter_by_set = true;
        return $obj;
    }


    /**
     * @param int $id - query object ID
     * @return FX_Query_Object
     * @throws Exception
     */
    public static function load_from_id($id = 0)
    {
        $query = get_object(get_type_id_by_name(0, 'query'), intval($id));

        $obj = new FX_Query_Object(
            json_decode(stripslashes($query['code']), true),
            intval($query['main_type']),
            json_decode(stripslashes($query['joined_types']), true)
        );
        $obj->hide_empty = $query['hide_empty'];
        $obj->object_fields = $query;
        $obj->filter_by_set = ($query['filter_by_set']?true:false);
        $obj->schema_id = $query['schema_id'];
        return $obj;
    }

    /**
     * @param int $schema_id
     * @param int $set_id
     * @return FX_Query_Object
     */
    public function set_schema_and_set_id($schema_id = 0, $set_id = 0)
    {
        if(!$this->schema_id)
            $this->schema_id = intval($schema_id);
        if($this->filter_by_set)
            $this->set_id = intval($set_id);
        else
            $this->set_id = 0;
        return $this;
    }

}

class FX_Query
{
    public $query_object = null;

    protected $_search_string = '';
    protected $_linked_object_type_id = 0;
    protected $_linked_object_id = 0;
    protected $_limit = 0;
    protected $_offset = 0;
    protected $_aggregation = false;

    protected $_sql = array(
        'select_str' => array(),
        'from_str' => '',
        'where_str' => array(),
        'order_by_str' => array(),
        'group_str' => array(),
        'limit_str' => array(),
        'link_str' => ''
    );
    protected $_executed = false;
    protected $_result = array();

    /**
     * @param $query
     * @param int|array $main_type
     * @param null $joined_types
     * @param int $hide_empty
     * @throws Exception
     */
    public function __construct(
        $query,
        $set_id = 0,
        $limit = 0,
        $offset = 0,
        $user_instance = null,
        $linked_object_type_id = null,
        $linked_object_id = null,
        $search_string = null,
        $main_type = 0,
        $joined_types = null,
        $hide_empty = 0,
        $schema_id = 0
    ) {
        if (is_numeric($query)) {
            $query_object = FX_Query_Object::load_from_id($query);
            if (is_fx_error($query_object))
                throw new Exception('Invalid query object!');
        } else {
            if (is_string($query)) {
                $query = json_decode($query, true);
            }

            $query_object = FX_Query_Object::load_from_code(
                $query,
                $main_type,
                $joined_types,
                $hide_empty
            );
        }
        $this->query_object = $query_object;

        $this->query_object->set_schema_and_set_id($schema_id, $set_id);

        if ($user_instance) {
            $rights = $this->check_user_rights($user_instance);
            if (!$rights) {
                throw new RightsException('Rights error');
            }
        }
        if ($limit) {
            $this->set_limit($limit, $offset);
        }
        if ($linked_object_type_id && $linked_object_id) {
            $this->filter_by_links(
                $linked_object_type_id,
                $linked_object_id
            );
        }
        if ($search_string != null) {
            $this->search_in_query($search_string);
        }
    }

    protected function _prepare_sql_clauses_from_code()
    {
        $this->_add_select_sql_clause();

        // add object_id fields
        foreach ($this->query_object->types_tree as $parent) {
            foreach ($parent as $child => $v) {
                $name = $this->_get_table_name($child) . '.object_id';
                $this->_sql['select_str'][] = $name . ' as object_id_'.$child;
            }
        }

        // FROM  clause and filter ny set
        $this->_sql['from_str'] = $this->_get_table_name(
            $this->query_object->main_type_id
        );

        $this->_filter_by_set_and_schema($this->query_object->main_type_id);
        if ($this->query_object->has_joins) {
            $this->_sql['from_str'] .= $this->_get_sql_join(
                $this->query_object->main_type_id,
                0,
                $this->query_object->hide_empty
            );
        }
        if ($this->_sql['link_str']) {
//            error_log(print_r($this->_sql['link_str'],true));
            $this->_sql['from_str'] .= $this->_sql['link_str'];
        }

    }

    protected function _add_select_sql_clause()
    {
        $search = array();
        foreach ($this->query_object->code as &$field) {
            $field = (array)$field;
//            fx_print($this->query_object->types_tree);
//            fx_print($field);
            //'object_id' fields will be added below
            if ($field['name'] == 'object_id' && empty($field['alias'])) {
//                continue;
            }
            //check if field doesn't exists in type fields
            if (isset($field['parent_type'])) {
                if (!in_array(
                    $field['name'],
                    $this->query_object->types_tree[intval(
                        $field['parent_type']
                    )][$field['object_type_id']]
                )
                ) {
                    $field['hide'] = true;
                    continue;
                }
            }
            $field_name = $this->_get_table_name(
                    $field['object_type_id']
                ) . '.' . $field['name'];

            $this->_add_aggregation($field, $field_name);

            if ($field['criteria']) {
                $this->_sql['where_str'][] = $field_name . ' ' . $field['criteria'];
            }
            if ($this->_search_string) {
                $search[] = " LOWER($field_name) like '%{$this->_search_string}%' ";
            }

            if ($field['order'] != 'none') {
                $this->_sql['order_by_str'][] = $field_name . ' '
                    . strtoupper($field['order']);
            }
        }
        unset($field);

        $this->_add_search_clause($search);
    }

    protected function _add_aggregation($field, $field_name)
    {
        if ($field['aggregation'] == '') {
            if ($field['alias']) {
                $tmp = $field_name . ' as "' .
                    str_replace('"', '', $field['alias']) . '"';
            } else {
                $tmp = $field_name;
            }
            $this->_sql['select_str'][] = $tmp;
            $this->_sql['group_str'][] = $field_name;
        } else {
            $tmp = $field['aggregation'] . '(' . $field_name . ')';
            if ($field['alias']) {
                $tmp .= ' as "' . str_replace('"', '', $field['alias']) . '"';
            } else {
                $tmp .= ' as "' . $field['name'] . '"';
            }
            $this->_sql['select_str'][] = $tmp;
            $this->_aggregation = true;
        }
    }

    protected function _add_search_clause($search)
    {
        if ($this->_search_string && !empty($search)) {
            $this->_sql['where_str'][] = ' ( ' . implode(
                    ' OR ',
                    $search
                ) . ' ) ';
        }
    }

    protected function _filter_by_set_and_schema($type_id)
    {
        if (!in_array($type_id, array(
            get_type_id_by_name(0, 'subscription')
        ))) {
            if ($this->query_object->set_id != 0) {
                $this->_sql['where_str'][] = "(" .
                    $this->_get_table_name($type_id) .
                    ".set_id = " . $this->query_object->set_id .
                    " OR " . $this->_get_table_name($type_id) .
                    ".set_id IS NULL ) ";
            }
            if ($this->query_object->schema_id != 0) {
                $this->_sql['where_str'][] = '(' .
                    $this->_get_table_name($type_id) .
                    ".schema_id = " . $this->query_object->schema_id .
                    " OR " . $this->_get_table_name($type_id) .
                    ".schema_id IS NULL)";
            }
//        } else {
//            $this->_sql['link_str'] .= $this->_get_sql_link(
//                get_type_id_by_name(0,'data_set'),
//                $this->query_object->set_id,
//                $type_id
//            );
        }
    }

    protected function _get_sql_join($main_type_id = 0, $links_count = 0, $hide = false)
    {
        $j = $hide ? '' : 'LEFT';
        $where_str = '';
        foreach ($this->query_object->types_tree[$main_type_id] as $join_type_id => $nothing) {
            $this->_filter_by_set_and_schema($join_type_id);
            $links_count++;
            $where_str .= " $j JOIN " . DB_TABLE_PREFIX . "link_tbl as l$links_count ";
            $where_str .= " ON (l$links_count.object_type_1_id=$main_type_id AND l$links_count.object_1_id=" . $this->_get_table_name(
                    $main_type_id
                ) . ".object_id) "
                    ." OR (l$links_count.object_type_2_id=$main_type_id AND l$links_count.object_2_id=" . $this->_get_table_name(
                    $main_type_id
                ) . ".object_id) ";
            $where_str .= " $j JOIN " . $this->_get_table_name($join_type_id);
            $where_str .= " ON (l$links_count.object_type_1_id=$join_type_id AND l$links_count.object_1_id=" . $this->_get_table_name(
                    $join_type_id
                ) . ".object_id "
                ." AND l$links_count.object_type_2_id=$main_type_id AND l$links_count.object_2_id=" . $this->_get_table_name(
                    $main_type_id
                ) . ".object_id "
                     ." ) OR (l$links_count.object_type_2_id=$join_type_id AND l$links_count.object_2_id=" . $this->_get_table_name(
                    $join_type_id
                ) . ".object_id "
                        ." AND l$links_count.object_type_1_id=$main_type_id AND l$links_count.object_1_id=" . $this->_get_table_name(
                    $main_type_id
                ) . ".object_id) \n";
        }
        foreach ($this->query_object->types_tree[$main_type_id] as $join_type_id => $nothing) {
            if (isset($this->query_object->types_tree[$join_type_id])) {
                $where_str .= $this->_get_sql_join(
                    $join_type_id,
                    $links_count,
                    $hide
                );
            }
        }

        return $where_str;
    }

    protected function _get_sql_link($_linked_object_type_id, $_linked_object_id,$main=-1)
    {
        if($main==-1) $main = $_linked_object_type_id;
        if ($_linked_object_type_id && $_linked_object_id) {
            return " JOIN " . DB_TABLE_PREFIX . "link_tbl as link$main ON ((link$main.object_1_id = " . $this->_get_table_name(
                $this->query_object->main_type_id
            ) . ".object_id " .
            " AND link$main.object_type_1_id = {$this->query_object->main_type_id} " .
            " AND link$main.object_2_id = {$_linked_object_id} AND link$main.object_type_2_id = {$_linked_object_type_id}) " .
            " OR (link$main.object_2_id = " . $this->_get_table_name(
                $this->query_object->main_type_id
            ) . ".object_id " .
            " AND link$main.object_type_2_id = {$this->query_object->main_type_id} " .
            " AND link$main.object_1_id = {$_linked_object_id} AND link$main.object_type_1_id = {$_linked_object_type_id})) ";
        } else {
            return "";
        }
    }

    protected function _create_sql_string()
    {
        $this->_prepare_sql_clauses_from_code();
//        var_dump($this->_sql);

        $select_str = implode(', ', $this->_sql['select_str']);

        $sql = "SELECT $select_str FROM " . $this->_sql['from_str'];

        if ($this->_sql['where_str']) {
            $sql .= ' WHERE ' . implode(' AND ', $this->_sql['where_str']);
        }

        if (!empty($this->_sql['group_str']) && $this->_aggregation) {
            $sql .= ' GROUP BY ' . implode(', ', $this->_sql['group_str']);
        }
        if (!empty($this->_sql['order_by_str'])) {
            $sql .= ' ORDER BY ' . implode(', ', $this->_sql['order_by_str']);
        } else {
            $sql .= ' ORDER BY object_id_'.$this->query_object->main_type_id.' ASC ';
        }
        if ($this->_sql['limit_str']) {
            $sql .= $this->_sql['limit_str'];
        }

        return $sql;
    }

    protected function _get_table_name($type_id)
    {
        return DB_TABLE_PREFIX . "object_type_" . $type_id;
    }

    //

    public function execute($format = false, $image_and_file = false)
    {
        $sql = $this->_create_sql_string();
        error_log($sql);

        global $fx_db;
        $stmt = $fx_db->prepare($sql);
        $stmt->execute();

        if ($stmt->errorCode() > 0) {
           // var_dump($stmt->errorInfo());
            throw new Exception('Unable to perform query. SQL Error.');
        }

        $result = array();

        if ($this->query_object->has_joins) {
            $type_ids = array_unique(
                array_column($this->query_object->code, 'object_type_id')
            );
        }

		$units = array(); // for unit decimals
	
        foreach ($stmt->fetchAll() as $row) {
            $id = $row['object_id_'.$this->query_object->main_type_id];
            if ($this->query_object->has_joins) {
                foreach ($type_ids as $type) {
                    if($type == $this->query_object->main_type_id) continue;
                    if ($row['object_id_'.$type]) {
                        $id .= '-' . $row['object_id_'.$type];
                    } else {
                        $id .= '-0';
                    }
                }
            }

            foreach ($this->query_object->code as $field) {
                if (isset($field['hide']) && !empty($field['hide'])) {
                    continue;
                }
                $name = $field['alias'] ? $field['alias'] : $field['name'];
                if ($name == 'object_id') {
                    $name_in_row = 'object_id_'.$field['object_type_id'];
                } else {
                    $name_in_row = $name;
                }
                if ($format) {
                    if (in_array($field['name'],array('created', 'modified'))) {
                        $field['type'] = 'DATETIME';
                    }
					
					if (strtoupper($field['type']) == 'FLOAT') {
						
						if (!isset($units[$field['name']])) {
							$units[$field['name']] = get_field_decimals($field['object_type_id'], $field['name']);
						}
												
						$row[$name_in_row] = number_format($row[$name_in_row], $units[$field['name']]);
					}
					
                    if(!$field['aggregation']) {
                        $row[$name_in_row] = FX_Format::format(
                            array(
                                'value' => $row[$name_in_row],
                                'type' => $field['type'],
                                'object_type_id' => $field['object_type_id'],
                                'object_id' => $row['object_id_'.$field['object_type_id']]
                            ), $image_and_file
                        );
                    }
                }
                $result[$id][$name] = $row[$name_in_row] !== null ? $row[$name_in_row] : '';
            }
        }

        $this->_result = $result;
        $this->_executed = true;
        return $this;
    }

    public function check_user_rights($user_instance)
    {
        $main_type = get_type($this->query_object->main_type_id, 'none');
        $main_type_schema = $main_type['schema_id'];
        $ignore_system_types = array(
            get_type_id_by_name(0,'subscription')
        );

        if (!$user_instance || $user_instance['is_admin']) return $this;

        if(isset($user_instance['schema_permissions'][$main_type_schema][$this->query_object->main_type_id])) {
            $main_type_schema_permission = $user_instance['schema_permissions'][$main_type_schema][$this->query_object->main_type_id];

            if ($main_type_schema_permission & U_GET) {
                $continue = false;
                if ($this->query_object->has_joins) {
                    foreach ($this->query_object->types_tree as $parent) {
                        foreach ($parent as $child => $nothing) {
                            if(in_array($child,$ignore_system_types)) continue;
                            if (!isset($user_instance['schema_permissions'][$main_type_schema][$child])) {
                                $continue = true;
                                continue;
                            }
                            if ($user_instance['schema_permissions'][$main_type_schema][$child] & U_GET) {
                                continue;
                            } else {
                                throw new Exception('No schema permissions for type '.$child);
                            }
                        }
                    }
                }
                if (!$continue) return $this;
            }
        }

        if(in_array($main_type_schema,$user_instance['schemas'])) {
            return $this;
        }

        $user_constraints = array();
        $allowed_sets = array();
        $allowed_sets[$this->query_object->main_type_id] = array();
        if ($this->query_object->has_joins) {
            foreach ($this->query_object->types_tree as $parent) {
                foreach ($parent as $child => $nothing) {
                    $allowed_sets[$child] = array();
                }
            }
        }

        if(isset($user_instance['set_permissions'])) {
            $continue = false;
            foreach ($user_instance['set_permissions'] as $set_id => $types) {
                if(isset($types[$this->query_object->main_type_id])) $continue = true;
                if($types[$this->query_object->main_type_id] & U_GET) {
                    $allowed_sets[$this->query_object->main_type_id][] = $set_id;
                }
            }
            if(!$continue && !isset($user_instance['links'][$this->query_object->main_type_id]) && empty($user_instance['sets']))
                throw new RightsException('2');

            if ($this->query_object->has_joins) {
                foreach ($this->query_object->types_tree as $parent) {
                    foreach ($parent as $child => $nothing) {
                        $continue = false;
                        if(in_array($child,$ignore_system_types)) continue;
                        foreach ($user_instance['set_permissions'] as $set_id => $types) {
                            if(isset($types[$child])) $continue=true;
                            if($types[$child] & U_GET) {
                                $allowed_sets[$child][] = $set_id;
                            }
                        }
                        if(!$continue && !isset($user_instance['links'][$child]) && empty($user_instance['sets']))
                            throw new RightsException('3');
                    }
                }
            }
        }

        foreach ($allowed_sets as $type_id=>$sets) {
            $sets = array_merge($sets,$user_instance['sets']);
            if(!empty($sets)) {
                $user_constraints['set_permissions'][] = $this->_get_table_name($type_id)
                    . ".set_id IN (" . implode(', ', $sets) . ') ';
            }
        }
        if(!empty($user_constraints['set_permissions'])) {
            $user_constraints['set_permissions'] = " (".implode(' AND ', $user_constraints['set_permissions']).") ";
        }

        $links = array();
        if ($user_instance['links'] && $user_instance['links'][$this->query_object->main_type_id]) {
            foreach ($user_instance['links'][$this->query_object->main_type_id] as $object_id) {
                $links[] = $this->_get_table_name(
                        $this->query_object->main_type_id
                    ) . ".object_id = $object_id";
            }
        }
        if ($this->query_object->has_joins) {
            foreach ($this->query_object->types_tree as $parent) {
                foreach ($parent as $child=>$nothing) {
                    if ($user_instance['links'] && $user_instance['links'][$child]) {
                        foreach ($user_instance['links'][$child] as $object_id) {
                            $links[] = $this->_get_table_name( $child ) . ".object_id = $object_id";
                        }
                    }
                }
            }
        }

        if($links) {
            if($user_constraints['set_permissions']) {
                $user_constraints[] = $user_constraints['set_permissions']." OR (".implode(' OR ',$links).")";
            } else {
                $user_constraints[] = "(".implode(' OR ',$links).")";
            }
        }


        if ($user_constraints) {
            $this->_sql['where_str'][] = "(" . implode(
                    " AND ",
                    $user_constraints
                ) . ")";
        }

        return $this;
    }

    public function set_limit($limit, $offset, $insert_to_sql = true)
    {
        $this->_limit = intval($limit);
        $this->_offset = intval($offset);
        if ($insert_to_sql && $this->_limit > 0) {
            $this->_sql['limit_str'] = " LIMIT {$this->_limit} OFFSET {$this->_offset}";
            $this->_executed = false;
        }
        return $this;
    }

    public function filter_by_links($linked_object_type_id, $linked_object_id)
    {
        error_log('filter by links');
        if (is_array($linked_object_type_id)) {
            $this->_linked_object_type_id = $linked_object_type_id['object_type_id'];
        } else {
            $this->_linked_object_type_id = $linked_object_type_id;
        }
        $this->_linked_object_id = $linked_object_id;

        if ($this->_linked_object_type_id && $this->_linked_object_id) {
            $this->_sql['link_str'] .= $this->_get_sql_link(
                $this->_linked_object_type_id,
                $this->_linked_object_id
            );
        }
        $this->_executed = false;
        return $this;
    }

    public function search_in_query($search_string)
    {
        $this->_search_string = strtolower($search_string);
        $this->_executed = false;
        return $this;
    }

    public function get_count()
    {
        if (!$this->_executed) {
            $this->execute(false);
        }
        return count($this->_result);
    }

    public function get_result($format = false)
    {
        if (!$this->_executed) {
            $this->execute($format);
        }
        if ($this->_limit && !$this->_sql['limit_str']) {
            return array_slice(
                $this->_result,
                $this->_offset,
                $this->_limit,
                true
            );
        } else {
            return $this->_result;
        }
    }

    public function get_html_table($format = false)
    {
        if (!$this->_executed) {
            $this->execute($format, true);
        }
        $keys = array_column($this->query_object->code, 'caption');
//        $keys = array_keys($this->_result);
//        $keys = array_keys($this->_result[$keys[0]]);
        $table = '<table><thead><tr>';
        foreach ($keys as $v) {
            $table .= '<th>' . $v . '</th>';
        }

        $table .= '</tr></thead> <tbody>';
        foreach ($this->_result as $id => $row) {
            $table .= '<tr>';
            foreach ($row as $key => $field) {
                $table .= "<td>$field</td>";
            }
            $table .= '</tr>';
        }
        $table .= "</tbody></table>";
        return $table;
    }

    public function get_query_object($field = '')
    {
        if (!$field) {
            return $this->query_object->object_fields;
        } else {
            $field = normalize_string($field);
            return isset($this->query_object->object_fields[$field]) ? $this->query_object->object_fields[$field] : null;
        }
    }

}

function exec_fx_query_count(
    $query,
    $set_id,
    $user_instance = null,
    $linked_object_type_id = null,
    $linked_object_id = null,
    $search_string = null,
    $main_type = 0,
    $joined_types = null,
    $hide_empty = 0,
    $schema_id = 0
) {
    //TODO: optimize
    return count(
        exec_fx_query(
            $query,
            $set_id,
            0,
            0,
            $user_instance,
            $linked_object_type_id,
            $linked_object_id,
            $search_string,
            $main_type,
            $joined_types,
            0,
            $schema_id
        )
    );
}

function exec_fx_query(
    $query,
    $set_id = 0,
    $limit = 0,
    $offset = 0,
    $user_instance = null,
    $linked_object_type_id = null,
    $linked_object_id = null,
    $search_string = null,
    $main_type = 0,
    $joined_types = null,
    $hide_empty = 0,
    $schema_id = 0
) {
    try {
        $object = new FX_Query(
            $query,
            $set_id,
            $limit,
            $offset,
            $user_instance,
            $linked_object_type_id ,
            $linked_object_id ,
            $search_string ,
            $main_type ,
            $joined_types ,
            $hide_empty ,
            $schema_id
        );
    } catch (RightsException $e1) {
        error_log('RIGHTS ERROR! '.$e1->getMessage());
        return array();
    } catch (Exception $e) {
        return new FX_Error(__FUNCTION__, _($e->getMessage()));
    }
    return $object->get_result(true);
}

function exec_fx_query_html(
    $query,
    $set_id,
    $limit = 0,
    $offset = 0,
    $user_instance = null,
    $linked_object_type_id = null,
    $linked_object_id = null,
    $search_string = null,
    $main_type = 0,
    $joined_types = null,
    $hide_empty = 0,
    $schema_id = 0
) {
    try {
        $object = new FX_Query(
            $query,
            $set_id,
            $limit,
            $offset,
            $user_instance,
            $linked_object_type_id ,
            $linked_object_id ,
            $search_string ,
            $main_type ,
            $joined_types ,
            $hide_empty ,
            $schema_id
        );
    } catch (RightsException $e1) {
        error_log('RIGHTS ERROR! '.$e1->getMessage());
        return array();
    } catch (Exception $e) {
        return new FX_Error(__FUNCTION__, _($e->getMessage()));
    }
    return $object->get_html_table(true);
}

function query_data_set_map($query_result, $code)
{
	$set_map = $tmp = $objects = array();
	
	foreach ($query_result as $group_id => $group) {	
		foreach (explode('-', $group_id) as $i => $object_id) {
			$objects[$code[$i]['object_type_id']][] = $object_id;
		}
	}
	
	if (!$objects) return array();
	
	global $fx_db;

	$query = array();

	foreach ($objects as $object_type_id => $obj) {
		$query[] = "SELECT $object_type_id AS object_type_id, object_id, set_id 
					FROM ".DB_TABLE_PREFIX."object_type_$object_type_id 
					WHERE object_id IN (".implode(',', $obj).")";
	}

	$pdo = $fx_db -> prepare(implode(' UNION ', $query));
	
	if ($pdo -> execute()) {
		// TODO: optimize the following code
		foreach($pdo->fetchAll() as $row) {
			$set_map[$row['object_type_id']][$row['object_id']] = $row['set_id'];
		}

		foreach ($query_result as $group_id => $group) {	
			$cur = array();
			foreach (explode('-', $group_id) as $i => $object_id) {
				$cur[] = (int)$set_map[$code[$i]['object_type_id']][$object_id];
			}
			$tmp[] = $cur;
		}	

		return $tmp;
	}

	return false;
}
	
function get_cached_query_result(
	$query_id,
	$user_instance,
	$filter_by_set = false,
	$offset = 0,
	$limit = 0,
	$linked_type = 0,
	$linked_object = 0,
	$sort_key = NULL,
	$order = NULL,
	$filter_by = NULL,
	$group_by_enum = NULL)
{
	$query_cache = get_query_cache($query_id);

	if (!$query_cache) {
		$query_object = get_object(TYPE_QUERY, $query_id);
		$query_code = (array)json_decode($query_object['code'], true);
		$query_result = exec_fx_query($query_id);

		if (is_fx_error($query_result)) {
			return new FX_Error(__FUNCTION__, $query_result->get_error_message()); 
		}		
		
		$query_map = query_data_set_map($query_result, $query_code);	//Query data set map		

		if ($query_map === false) {
			return new FX_Error(__FUNCTION__, _('Unable to build query data set map'));
		}	

		update_query_cache($query_id, $query_object, $query_result, $query_map);
	}
	else {		
		$query_object = $query_cache['object'];
		$query_code = (array)json_decode($query_object['code'], true);			
		$query_result = $query_cache['result'];
		$query_map = $query_cache['map'];
	}
	
	if (!$query_result) {
		return array();
	}

	if (!$query_code || !$query_result || !$query_map) {
		return new FX_Error(__FUNCTION__, _('Data format error'));
	}

	global $system_types;
	$sys_types = array_values($system_types);

	$result = array();

	$query_types = array_unique((array)array_map(function($f){return $f['object_type_id'];}, $query_code));

	// 0. Check user roles	
	if ($user_instance['is_admin']) {
		$result = $query_result;
	}
	elseif ($schema_types = (array)$user_instance['schema_permissions'][$query_object['schema_id']]) {
		$valid_type = true;
		foreach ($query_types as $object_type_id) {
			if (!($schema_types[$object_type_id] & U_GET)) {

				$valid_type = false;
				break;
			}
		}
		if ($valid_type) {
			$result = $query_result;
		}
	}

	//--------------------------------

	if ($query_object['filter_by_set']) {
		if (!$filter_by_set) {
			return new FX_Error(__FUNCTION__, _('This query required Data Set ID'));
		}

		if (is_numeric($query_object['filter_by_set'])) {
			$set_filter = (array)$filter_by_set;
		}
		else {
			$set_filter = array();
			foreach (explode('-', $query_object['filter_by_set']) as $id) {
				$set_filter[] = $id * $filter_by_set;
			}
		}
	}

	//--------------------------------

	$links_filter = false;

	if ($linked_type && $linked_object) {
		$links_filter = get_actual_links_simple($linked_type, $linked_object, $query_object['main_type']);
		if (is_fx_error($links_filter) || !$links_filter) {
			return array();
		}			
	}

	//--------------------------------
	
	$user_sets = (array)$user_instance['sets'];
	$user_sets[] = 0;		
	
	//--------------------------------
	
	$role_sets = array_keys((array)$user_instance['set_permissions']);
	$role_sets[] = 0;

	//--------------------------------
	
	$linked_objects = (array)$user_instance['links'][$query_object['main_type']];

	//--------------------------------
	
	reset($query_map);
	
	foreach ($query_result as $group_id => $group)
	{
		list ($object_id, ) = explode('-', $group_id);
		
		// 1. Filter result by linked object (if required)	
	
		if ($links_filter !== false) {
			if(!in_array($object_id, $links_filter)) {
				next($query_map);
				unset($result[$group_id]);
				continue;
			}
		}
		
		// 2. Filter result by data set (if required)
		
		if ($set_filter !== false) {
			if(array_sum(array_diff($set_filter, current($query_map)))) {					
				next($query_map);
				unset($result[$group_id]);
				continue;
			}
		}

		// 3. Check linked objects
		if (in_array($object_id, $linked_objects)) {
			if (!isset($result[$group_id])) {
				$result[$group_id] = $group;
			}
		}

		// 4. Check data sets were user is the set admin

		if ($user_instance['sets']) {
			if(!array_diff(current($query_map), $user_sets)) {
				if (!isset($result[$group_id])) {
					$result[$group_id] = $group;
				}
			}
		}

		// 5. Check user data set roles

		if ($user_instance['set_permissions']) {
			if(!array_diff(current($query_map), $role_sets)) {
				$add_row = true;
				foreach (current($query_map) as $i => $cur_set) {
					$cur_type_id = $query_code[$i]['object_type_id'];				
					if (!($user_instance['set_permissions'][$cur_set][$query_code[$i]['object_type_id']] & U_GET)) {
						$add_row = false;
						break;
					}
				}
				if ($add_row) {
					if (!isset($result[$group_id])) {
						$result[$group_id] = $group;
					}
				}
			}
		}
		
		// end

		next($query_map);
	}		

	if ($group_by_enum == NULL)
	{
		//Filter by field value
		if ($filter_by !== NULL && strlen((string)$filter_by)) {
			filter_array_by_value($result, $filter_by);
		}

		// Sort result if required
		if ($sort_key !== NULL) {
			$desc = strtolower($order) == 'desc' ? true : false;
			usort_by_row_value($result, $sort_key, $desc);
		}
	
		// Apply limit & offset
		if ($offset || $limit) {
			if ($limit < 1) {
				$limit = -1;
			}
			$result = array_slice($result, $offset, $limit, true);		
		}
	}
	else
	{
		$group_by = normalize_string($group_by_enum['by']);
		$group_for = normalize_string($group_by_enum['for']);

		$enum_fields = array();

		foreach ($query_code as $field) {
			if ($field['alias'] == $group_by && is_numeric($field['type'])) {
				$enum_fields = get_enum_fields($field['type']);
				if (is_fx_error($enum_fields)) {
					return $enum_fields;
				}				
				break;
			}
		}

		$grouped_result = array();

		$columns = array_keys($enum_fields);
		array_unshift($columns, $group_for);
		$enum_fields = array_flip($enum_fields);

		foreach ($result as $group) {
			
			$cf = $group[$group_for];
			$cb = $enum_fields[$group[$group_by]];

			if (!isset($grouped_result[$cf])) {
				$grouped_result[$cf] = array_combine($columns, (array)$cf + array_fill(0, count($columns), 0));
			}

			if (isset($grouped_result[$cf][$cb])) {
				$grouped_result[$cf][$cb]++;
			}		
		}

		return array_values($grouped_result);
		//return $grouped_result;
	}

	return $result;
}