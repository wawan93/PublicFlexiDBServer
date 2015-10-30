<?php

function table_explorer($options=array())
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
	$bulk_actions = false; //array_key_exists('bulk_actions', $options) && $options['bulk_actions'] < 0 ? false : true;
	$set_id = array_key_exists('set_id',$options) ? (int)$options['set_id'] : '';
	$schema_id = array_key_exists('schema_id',$options) ? (int)$options['schema_id'] : '';
	//-----------------------------------------------------------------------------

	$tmpl_actions = '<div class="actions">';


	$tmpl_actions .= $bulk_actions ? '
	<div class="bulk">
		<form action="" method="post">
			<input type="hidden" name="object_action" value="delete_selected">
			<input type="hidden" name="items" value="">
			<input type="hidden" name="object_type_id" value="'.$object_type_id.'">
			<a href="#" onClick="delete_selected(this)">Delete selected</a>
		</form>
	</div>' : '';


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

	$primary_key = _get_primary_keys($options['table']);
	//$system_exists_in_table = _table_field_exists($options['table'], 'system');
	$obj_fields = array_unique($options['fields']);
	
	if(!in_array($primary_key,$options['fields']) && !is_array($primary_key)) {
		array_unshift($obj_fields,$primary_key);
	}
		
	$condition = $filter = array();
	
	foreach($_GET as $key => $value) {
		list($prefix,$field) = explode('-', $key);
		if($prefix == 'filter' && in_array($field, $obj_fields)) $filter[] = $field." LIKE '%".$value."%'";
	}
	
	if(strlen((string)$schema_id) && _table_field_exists($options['table'], 'schema_id')) $condition[] = 'schema_id='.$schema_id;
	if(strlen((string)$set_id && _table_field_exists($options['table'], 'set_id'))) $condition[] = 'set_id='.$set_id;

	$condition = $condition ? implode(' AND ', $condition) : '';
		
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

	$res = $fx_db -> prepare($query_items);
	$res -> execute();

	if($res -> execute()) {	
		$records = $res->fetchAll();
	}
	else {
		$error_info = $res -> errorInfo();
		return new FX_Error(__FUNCTION__, $error_info[2]);
	}

	if($obj_count || $filter)
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
		</th>';

		$tmpl_content .= '</tr>';
		
		//---------------------------------------------------------------------------- 

		foreach($records as $record)
		{
			$record_id = $record[$primary_key];
			
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