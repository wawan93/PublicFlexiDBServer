<?php

fx_enqueue_style('', URL.'style/fx_explorer.css', '/', 'custom');
fx_enqueue_script('', URL.'js/fx_explorer.js', '/', 'custom');
fx_enqueue_script('', URL.'js/fixed_table_rc.js', '/', 'custom');

class FX_Explorer
{
	/**
	 * Property: method
	 * The HTTP method this request was made in, either GET, POST, PUT or DELETE
	 */
	
	var $bulk_actions = true;
	var $ipp = 10;
	var $ipps = array(10,20,50,100);
	var $tmpl = '';
	var $filter = true;
	var $count = 0;
	var $data = array();

	function __construct($data = array())
	{
		$this->data = $data;
		$this->count = count($data);
	}
	
	function print_table($return = false)
	{
		if ($return === true) {
			return $this -> generate_content();
		}
		else {
			echo $this -> generate_content();
		}
	}
		
	function refresh()
	{
		$this->generate_content();
	}
		
	private function generate_content()
	{
		$tmpl = '
		<div class="explorer">
			<div class="explorer-header">
				%%HEADER%%
			</div>
			<div class="explorer-content">
				%%CONTENT%%
			</div>
			<div class="explorer-footer">
				%%FOOTER%%
			</div>
		</div>';


		$item_actions = array('View', 'Edit', 'Delete');
        $key_fields_array = array('object_id','name');

		$content = "<table id='explorer-table'>";

		// Table header
		$content .= "\n<thead>";

		$content .= "\n\t<tr>";
		if ($this->bulk_actions) {
			$content .= "\n\t\t<th class='fixed-key-field'><div>
                <input type=\"checkbox\" onchange=\"check_all_objects(this)\" title=\"Select all\">
                </div></th>";
		}
		$content .= "\n\t\t<th class='fixed-key-field'></th>";

		reset($this->data);
		$data_keys = array_keys(current($this->data));

        foreach ($key_fields_array as $key) {
            $content .= "\n\t\t<th class='fixed-key-field'><div>".ucwords(str_replace('_', ' ', $key))."</div></th>";
		}
        foreach ($data_keys as $key) {
			if(in_array($key,$key_fields_array)) continue;
			$content .= "\n\t\t<th>".ucwords(str_replace('_', ' ', $key))."</th>";
		}
		$content .= "\n\t</tr>";

		// data filter
/*		if ($this -> filter) {
			$content .= "\n\t<tr class=\"filter\">";
			if ($this->bulk_actions) {
				$content .= "\n\t\t<th></th>";
			}
			$content .= "\n\t\t<th></th>";
			foreach ($data_keys as $key) {
				$content .= "\n\t\t<th><input class=\"criteria\" type=\"text\" id=\"filter-$value\" name=\"filter-$value\" value=\"".$_GET['filter-'.$value]."\"></th>";
			}
			$content .= "\n\t</tr>";
		}*/

		$content .= "\n</thead>";

		// Table content
		$i = 0;
		$content .= "\n<tbody>";
		foreach ($this->data as $key => $row) {
            $i++;
            $view_object = "onclick='view_object({$row['object_type_id']},{$row['object_id']})'";
            $content .= "\n\t<tr".($i % 2 ? ' class="odd"' : '')." >";
			if ($this->bulk_actions) {
				$content .= "\n\t\t<td class='fixed-key-field'><div>
                    <input type=\"checkbox\" class=\"checks\" value=\"$key\">
                    </div></td>";
			}
            $content .= "\n\t\t<td class='fixed-key-field'><div>$i</div>
                    <div class='actions'>
						<a href=\"#\">View</a>&nbsp;|&nbsp;<a href=\"#\">Edit</a>&nbsp;|&nbsp;<a href=\"#\">Delete</a>
					</div></td>";

            $edit_link = URL."/data_editor/data_objects?object_type_id={$row['object_type_id']}&object_id={$row['object_id']}";
            $delete_link = URL."/data_editor/data_objects?object_type_id={$row['object_type_id']}&object_id={$row['object_id']}";
//            $content .= "\n\t\t<td class='fixed-key-field'><div>
//                <a href='javascript:view_object({$row['object_type_id']},{$row['object_id']});'>View</a>
//                <a href='$edit_link'>Edit</a>
//                <a href='$delete_link'>Delele</a>
//            </div></td>";
            foreach($key_fields_array as $key){
                $content .= "\n\t\t<td class='fixed-key-field'><div>{$row[$key]}</div></td>";
            }
			foreach ($row as $key => $value) {
                if (in_array($key, $key_fields_array)) {
					continue;
				}
				
				if (empty($value)) {
					$value = "<span class=\"null\">null</span>";
				}
				
				$content .= "\n\t\t<td>$value</td>";
			}
			$content .= "\n\t</tr>";
		}
		$content .= "\n</tbody>";
		$content .= "\n</table>";

		//footer
		$footer = "<span class=\"count\">{$this->count} items(s)</span>".($this->navigator());

		$tmpl = str_replace('%%CONTENT%%', $content, $tmpl);
		$tmpl = str_replace('%%FOOTER%%', $footer, $tmpl);

		return $tmpl;
	}

	private function navigator($a=null, $q=null, $cp=null)
	{
		$cp = (int)$_GET['p'];
		$pages = $this->count % $this->ipp ? (int)($this->count / $this->ipp)+1 : $this->count / $this->ipp;
		$out = "\n<div class=\"nav-bar\">\n\t";

		if ($pages > 1) {
			$out .= $cp > 1 ? "<a href=\"".replace_url_param('p', '')."\"><<</a>" : "<span class=\"disabled\"><<</span>";	
			$out .= $cp > 1 ? "<a href=\"".replace_url_param('p', $cp-1)."\"><</a>" : "<span class=\"disabled\"><</span>";	
			$out .= "\n\t&nbsp;<form method=\"get\">\n\t\t<select name=\"p\" onchange=\"submit()\">";
			foreach (range(1,$pages) as $value) {
				$s = $value == $cp ? ' selected="selected"' : '';
				$out .= "\n\t\t\t<option value=\"$value\"$s>$value</option>";
			}
			$out .= "\n\t</select>\n\t</form>&nbsp;of&nbsp;$pages&nbsp;\n\t";
			$out .= $cp < $pages ? "<a href=\"".replace_url_param('p', $cp+1)."\">></a>" : "<span class=\"disabled\">></span>";
			$out .= $cp < $pages ? "<a href=\"".replace_url_param('p', $pages)."\">>></a>" : "<span class=\"disabled\">>></span>";
		}

		$out .= "\n</div>";

		return $out;
	}
}

/*class FX_Table_Explorer extends FX_Explorer
{
	var $table_name = '';
	var $schema_id = 0;
	var $set_id = 0;
}

class FX_Object_Explorer extends FX_Explorer
{
	var $object_type_id = 0;
}*/


function table_explorer_qqqqqqqq($options=array())
{
	global $fx_db, $object_type_id;
	
	$tmpl = '
	<div class="object-explorer">
		<div class="actions">
			%%ACTIONS%%
		</div>
		<div class="list">
			%%CONTENT%%
		</div>
		%%NAVIGATOR%%
	</div>';
	
	//-----------------------------------------------------------------------------
	$tmpl_actions = $tmpl_list = $tmpl_navigator;
	$ipp_options = array(10,20,50,100);
	//$filter_system = array_key_exists('filter_system', $options) ? $options['filter_system'] > 0 : false;
	$bulk_actions = array_key_exists('bulk_actions', $options) && $options['bulk_actions'] < 0 ? false : true;
	$set_id = array_key_exists('set_id',$options) ? (int)$options['set_id'] : '';
	$schema_id = array_key_exists('schema_id',$options) ? (int)$options['schema_id'] : '';
	//-----------------------------------------------------------------------------

	$tmpl_actions = '<div class="actions">';

	$tmpl_actions .= $bulk_actions ? '
	<form action="" method="post" >
		<select name="form_action">
			<option value="0">'._('Bulk Actions').'</option>
			<option value="delete_selected">'._('Delete').'</option>
			<option value="move_to_trash" disabled>'._('Move to trash').'</option>
		</select>
		<input type="hidden" name="items" value="">
		<input type="button" class="button small light-gray" value="'._('Apply').'" onClick="perform_bulk_action(this)">
	</form>' : '';

/*	$tmpl_actions .= '
	<form action="" method="post">
		<input type="hidden" name="set_show_system">
		<input type="submit" class="button small light-gray" value="'.($_SESSION['show_system']?'Hide system':'Show system').'"> 
	</form>	
	<div class="ipp">Items per page: ';*/

	$tmpl_actions .= '<div class="ipp">'._('Items per page').': ';

	for($i=0; $i<count($ipp_options); $i++) {
		$tmpl_actions .= $ipp_options[$i] == $_GET['ipp'] ? $ipp_options[$i].'&nbsp;' : '<a href="'.replace_url_param('ipp',$ipp_options[$i]).'" title="'.$ipp_options[$i].' items per page">'.$ipp_options[$i].'</a>&nbsp;';
	}

	$tmpl_actions .='</div></div>';

	//-----------------------------------------------------------------------------

	if (!array_key_exists('table', $options)) {
		return new FX_Error(__FUNCTION__, _('Empty "table" parameter'));
	}
	
	if (!_table_exists($options['table'])) {
		return new FX_Error(__FUNCTION__, 'Table "'.$options['table'].'" does not exists.');
	}

	$primary_key = $fx_db->get_primary_keys($options['table']);
	$obj_fields = array_unique($options['fields']);
	
	if(!in_array($primary_key,$options['fields']) && !is_array($primary_key)) {
		array_unshift($obj_fields,$primary_key);
	}
		
	$condition = $filter = array();
	
	foreach($_GET as $key => $value) {
		list($prefix,$field) = explode('-', $key);
		if($prefix == 'filter' && in_array($field, $obj_fields)) $filter[] = $field." LIKE '%".$value."%'";
	}
	
	if(strlen((string)$schema_id) && $fx_db->is_table_field_exists($options['table'], 'schema_id')) $condition[] = 'schema_id='.$schema_id;
	if(strlen((string)$set_id && $fx_db->is_table_field_exists($options['table'], 'set_id'))) $condition[] = 'set_id='.$set_id;

	$condition = $condition ? implode(' AND ', $condition) : '';
	
/*	if($filter_system && $system_exists_in_table && !in_array('system',$obj_fields))
	{
		$obj_fields[] = 'system';
	}
	
	if($filter_system && $condition)
	{
		$condition .= $_SESSION['show_system'] ? ' OR system = 1' : ' AND system <> 1';
	}
	elseif($filter_system && !$condition)
	{
		$condition .= $_SESSION['show_system'] ? 'system = 1' : 'system <> 1';
	}*/
		
	if($condition) {
		$condition = $filter ? " WHERE (".$condition.") AND (".implode(' AND ', $filter).")" : " WHERE ".$condition;
	}
	elseif($filter) {
		$condition = " WHERE ".$filter;
	}

	//SORT BY and SORT ORDER
	//-------------------------------------------------------------------------------------------
	if(isset($_GET['sort']) && in_array($_GET['sort'], $obj_fields)) {
		$order_by = $_GET['sort'];
	}
	elseif(array_key_exists('order_by',$options) && in_array($options['order_by'], $obj_fields)) {
		$order_by = $options['order_by'];
	}
	else {
		$order_by = $obj_fields[0];
	}

	if(isset($_GET['order'])) {
		$order = strtoupper($_GET['order']);
		if($order != 'ASC' && $order != 'DESC') $order = 'DESC';
	}
	else {
		$order = 'DESC';
	}

	//-------------------------------------------------------------------------------------------
	
	$query_count = "SELECT NULL FROM ".$options['table'].$condition;

	$res = $fx_db -> prepare($query_count);
	$res -> execute();

	if($res -> execute()) {	
		$obj_count = $res -> rowCount();
	}
	else {
		$error_info = $res -> errorInfo();
		return new FX_Error(__FUNCTION__, $error_info[2]);
	}
	
	//-----------------------------------------------------------------------------
	$cur_page = isset($_GET['p']) ? $_GET['p'] : 1;
	$ipp = isset($_GET['ipp']) ? $_GET['ipp'] : $ipp_options[0];
	$offset = $obj_count <= $ipp ? 0 : $ipp*($cur_page-1);		
	//-----------------------------------------------------------------------------

	$query_items = "SELECT ".implode(',', $obj_fields)." FROM ".$options['table'].$condition." ORDER BY ".$order_by." ".$order." LIMIT ".$offset.",".$ipp;

	//echo '<p>'.$query_items.'</p>';

	$res = $fx_db -> prepare($query_items);
	$res -> execute();

	if($res -> execute()) {	
		$records = $res->fetchAll();
	}
	else {
		$error_info = $res -> errorInfo();
		return new FX_Error(__FUNCTION__, $error_info[2]);
	}

	if($obj_count)
	{
		if($i = array_search('system',$obj_fields,true)) {
			unset($obj_fields[$i]);
		}		

		$tmpl_content = '<table>';

		$fc = count($obj_fields);

		$tmpl_content .= '<tr>';
		
		$tmpl_content .=  $bulk_actions ? '<th width="1px"><input type="checkbox" onchange="check_all_objects(this)" title="Select all"></th><th width="1px"></th>' : '<th width="1px"></th>';
		
		for($i=0; $i<$fc; $i++) {
			$w = $i!= $fc-1 ? ' width="1px"' : '';

			$field_name = $obj_fields[$i];
			$field_caption = $field_name == $primary_key ? 'ID' : ucwords(str_replace('_', ' ', $obj_fields[$i])); 
			
			$tmpl_content .= '<th'.$w.'><nobr>&nbsp;<a href="'.replace_url_param('order',$order=='ASC'?'DESC':'ASC',replace_url_param('sort',$field_name)).'" title="Sort by '.$field_name.'">'.$field_caption.'</a>';
			
			if($field_name == $sort_by) {
				if($order == 'ASC') {
					$tmpl_content .= $order == 'ASC' ? '<div class="asc"></div>' : '<div class="desc"></div>';
				}
			}
			
			$tmpl_content .= '&nbsp;</nobr></th>';
		}
		
		$tmpl_content .= '<th width="1px"></th>';

		//FILTER
		//----------------------------------------------------------------------------

		$tmpl_content .= '</tr>';

		$tmpl_content .= '<tr>';		
		$tmpl_content .= $bulk_actions ? '<th></th>' : '';
		$tmpl_content .= '<th></th>';		
		
		for($i=0; $i<$fc; $i++) {
			$fn = 'filter-'.$obj_fields[$i];
			$tmpl_content .= '<th><div class="criteria"><input class="criteria" type="text" id="'.$fn.'" name="'.$fn.'" value="'.$_GET[$fn].'"></div></th>';
		}

		$tmpl_content .= '
		<th style="text-align:left">
			<div class="filter" title="Apply filter" onclick="submit_explorer_filter(this,\''.urlencode(current_page_url()).'\')"></div>
			<div class="fc" title="Change fields"></div>
		</th>';

		$tmpl_content .= '</tr>';
		
		//---------------------------------------------------------------------------- 
		
		foreach($records as $record)
		{
			$record_id = $record[$primary_key];
			
			//$is_system = $record['system'];
			//unset($record['system']);
			
			//$tmpl_content .= $is_system && $filter_system ? '<tr class="system" title="System instance">' : '<tr>';
			$tmpl_content .= '<tr>';
			
			if ($bulk_actions) {
				$tmpl_content .= '<td><input type="checkbox" class="checks" value="'.$record_id.'"></td>';
			}

			$tmpl_content .= '<td class="index">&nbsp;'.($offset++ + 1).'&nbsp;</td>';
			
			$last_td = '<td class="overflow">'.array_pop($record).'</td>';
	
			$tmpl_content .= '<td>'.implode('&nbsp;</td><td>&nbsp;', array_values($record)).'&nbsp;</td>'.$last_td;

			$action_url = $options['action_url'] ? $options['action_url'] : '';
		
			$tmpl_content .= '<td>';
			
			$options['actions'] = array_unique($options['actions']);
			
			if (is_array($primary_key)) {
				$current_key = array_flip($primary_key);
				
				$delete_url = '';

				foreach($current_key as $key => &$value) {
					$value = $record[$key];
					$delete_url .= '<input type="hidden" name="'.$key.'" value="'.$record[$key].'">';
				}
				
				$edit_url = replace_url_params($current_key);
				$view_url = '#';
			}
			else {
				$edit_url = replace_url_param($primary_key, $record[$primary_key]);
				$view_url = '#';
				$delete_url = '<input type="hidden" name="'.$primary_key.'" value="'.$record[$primary_key].'">';	
			}
			
            switch ($primary_key) {
                case 'enum_type_id': $object_type = 'enum'; break;
                case 'object_type_id': $object_type = 'object'; break;
                case array('object_type_1_id', 'object_type_2_id'): $object_type = 'link'; break;
            }
			
			for($j=0; $j<count($options['actions']); $j++) {
				switch($options['actions'][$j]) {
					case 'view':
						$tmpl_content .= '<a class="tiny-button view" href="#" onclick="view_table_object(\''.$record[$primary_key].'\', \''.$object_type.'\')"></a>';
					break;
					case 'edit':
						$tmpl_content .= '<a class="tiny-button edit" href="'.$edit_url.'"></a>';
					break;
					case 'delete':
						$tmpl_content .= '
						<form style="display:inline" method="post" action="'.$action_url.'">
							<input type="hidden" name="form_action" value="delete">
							'.$delete_url.'
							<input class="tiny-button delete" type="button" value="">
						</form>';
					break;					
					default: '<div class="button small light-gray">'.$options['actions'][$j].'</div>';
				}						
			}
			
			$tmpl_content .= '</td>';
			$tmpl_content .= '</tr>';
		}

		$tmpl_content .= '</table>';
	}
	else {
		$tmpl_content = '<div class="empty">'._('No items to show').'</div>';
	}

	$tmpl = str_replace('%%ACTIONS%%',$tmpl_actions,$tmpl);
	$tmpl = str_replace('%%CONTENT%%',$tmpl_content,$tmpl);
	$tmpl = str_replace('%%NAVIGATOR%%',object_explorer_navigation($obj_count, $ipp, $cur_page),$tmpl);

	if($echo) echo $tmpl;
	else return $tmpl;
}

?>