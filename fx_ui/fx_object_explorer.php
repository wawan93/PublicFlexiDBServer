<?php


function object_explorer($options=array())
{
	$tmpl = '
	<div class="object-explorer">
		%%ACTIONS%%
		%%LIST%%
		%%NAVIGATOR%%
	</div>';

	$tmpl_filter = '
	<div class="filter">
		<div class="header">
			<h3>%%FILTER_TITLE%%</h3>
		</div>
		%%FILTER_CONTENT%%
	</div>';

	$tmpl_actions = '
	<div class="actions">
		actions
	</div>';

	$tmpl_list = '
	<div class="list">
		%%LIST_CONTENT%%
	</div>';

	$tmpl_navigator = '';
	$query = array();
	$allowed_types = array();
	$ipp_options = array(10,20,50,100);
	
	$set_id = array_key_exists('set_id',$options) ? $options['set_id'] : $_SESSION['current_set'];
	$schema_id = array_key_exists('schema_id',$options) ? $options['schema_id'] : 0;//$_SESSION['current_schema'];
	$bulk_actions = array_key_exists('bulk_actions', $options) && $options['bulk_actions'] < 0 ? false : true;
	$read_only = array_key_exists('read_only', $options) ? $options['read_only'] > 0 : false;

	if (array_key_exists('filter_by_link', $options)) {
		list ($linked_object_type_id, $linked_object_id) = explode('.', $options['filter_by_link']);
	}
	else {
		$linked_object_type_id = $linked_object_id = null;
	}
	
	if (array_key_exists('object_type_id',$options) && $options['object_type_id'])
	{
		$types = is_array($options['object_type_id']) ? $options['object_type_id'] : array($options['object_type_id']);

		for($i=0; $i<count($types); $i++) {
			if(!is_fx_error($types[$i]) && $types[$i]) {
				if($ct = get_type($types[$i], 'none')) {
					$allowed_types[$ct['object_type_id']] = $ct;
				}
			}
		}
	}
	else {
		$allowed_types = get_schema_types($schema_id, 'none');
	}
	
	if (isset($_SESSION['c_et']) && array_key_exists($_SESSION['c_et'], $allowed_types)) {
		$object_type_id = $_SESSION['c_et'];
	}
	else {
		$object_type_id = array_shift(array_keys($allowed_types));
	}

	if (count($allowed_types) > 1) {
		$type_select = '
			<input type="hidden" name="set_cet">
			<select name="object_type_id" onchange="submit()">
				<option value="0">'._('Select type').'</option>
				'.show_select_options($allowed_types, 'object_type_id', 'display_name', $object_type_id, false).'
			</select>';
	}
	else {
		$type_select = '<strong>'.$allowed_types[$object_type_id]['display_name'].'</strong>&nbsp;';
	}

	if (!$read_only) {
		$add_button = $object_type_id ? '<div class="button green" onclick="add_object('.$object_type_id.','.$schema_id.','.$set_id.')">'._('Add Object').'</div>' : '<div class="button grey" disabled="disabled">'._('Add Object').'</div>';
	}
	else {
		$add_button = '';
	}

	$tmpl_types = '
	<div class="filter">
		<form action="" method="post">
			'._('Current type').':&nbsp;
			'.$type_select.'
			'.$add_button.'
		</form>
	</div>';

	//ACTIONS -----------------------------------------------------------------

	$url = '?';
	//unset($_GET['p']);
	foreach($_GET as $key => $value) {
		$url .= $key != 'ipp' && $key != 'path' ? $key.'='.$value.'&' : '';
	}

	$tmpl_actions = '
	<div class="actions">';

	$tmpl_actions .= $bulk_actions ? '
	<div class="bulk">
		<form action="" method="post">
			<input type="hidden" name="object_action" value="delete_selected">
			<input type="hidden" name="items" value="">
			<input type="hidden" name="object_type_id" value="'.$object_type_id.'">
			<a href="#" onClick="delete_selected(this)">Delete selected</a>
		</form>
	</div>' : '';

	$tmpl_actions .= '
	<div class="ipp">'._('Items per page').': ';

	for($i=0; $i<count($ipp_options); $i++) {
		$tmpl_actions .= $ipp_options[$i]==$_GET['ipp'] ? $ipp_options[$i].'&nbsp;' : '<a href="'.$url.'ipp='.$ipp_options[$i].'" title="'.$ipp_options[$i].' '._('Items per page').'">'.$ipp_options[$i].'</a>&nbsp;';
	}

	$tmpl_actions .='</div></div>';

	//NAVIGATION --------------------------------------------------------------

	$tmpl_navigator = object_explorer_navigation($a, $q, $cp);

	//OBJECT LIST -------------------------------------------------------------

	// ORDER BY
	//-------------------------------------------
	
	if (isset($_GET['sort'])) {
		$sort_by = normalize_string($_GET['sort']);
	}
	elseif ($options['order_by']) {
		$sort_by = $options['order_by'];
	}
	else {
		$sort_by = $primary_key;
	}

	if (!_table_field_exists($options['table'],$sort_by)) {
		$sort_by = $primary_key;
	}

	if(strtoupper($_GET['order']) == 'ASC') {
		$order = 'ASC';
	}
	else { 
		$order = 'DESC';
	}

	if (!$object_type_id) {
		$list_content = '<div class="empty">'._('Please select object type').'</div>';
	}
	else 
	{
		$options['fields'] = array_unique($options['fields']);
		
		foreach ($options['fields'] as $field) {
			$query[$field] = array('name' => $field, 'object_type_id' => $object_type_id);
            $filter_field = $_GET['filter-'.$field];
            if (isset($filter_field) && $filter_field) {
                $criteria = (int)$filter_field ? '='.$filter_field : 'LIKE "%'.$filter_field.'%"';
                $query[$field]['criteria'] = $criteria;
            }
		}
        if (isset($_GET['sort']) && isset($_GET['order'])) {
            $sortable_field = $_GET['sort'];
            $order = strtoupper($_GET['order']) == 'ASC' ? 'ASC' : 'DESC';
            $query[$sortable_field]['order'] = $order;
//            $query['sort_by'] = $_GET['sort'];
        }

		if (array_key_exists('schema_id', $options)) {
			$query['schema_id'] = array('name' => 'schema_id', 'object_type_id' => $object_type_id, 'criteria' => '='.$schema_id);
			if (!in_array('schema_id', $options['fields'])) {
				$query['schema_id']['hide'] = 1;
			}
		}

		if (array_key_exists('set_id', $options)) {
			$query['set_id'] = array('name' => 'set_id', 'object_type_id' => $object_type_id, 'criteria' => '='.$set_id);
			if (!in_array('set_id', $options['fields'])) {
				$query['set_id']['hide'] = 1;			
			}
		}

		$obj_count = exec_fx_query_count($query, $set_id, null, $linked_object_type_id, $linked_object_id);

		$cur_page = isset($_GET['p']) ? $_GET['p'] : 1;
		$ipp = isset($_GET['ipp']) ? $_GET['ipp'] : $ipp_options[0];
		$offset = $obj_count <= $ipp ? 0 : $ipp*($cur_page-1);

		$tmpl_navigator = object_explorer_navigation($obj_count, $ipp, $cur_page);

		$objects = exec_fx_query($query, $set_id, $ipp, $offset, null, $linked_object_type_id, $linked_object_id);
		$type_fields = get_type_fields($object_type_id);

		if (is_fx_error($objects)) {
			$list_content = '<div class="error">'.$objects->get_error_message().'</div>';
		}
		elseif (!$objects && !$filter_field) {
			$list_content = '<div class="empty">No items to show</div>';
		}
		else {
			$list_content = '<table>';

			$fc = count($options['fields']);

			$list_content .= '<tr>';
			$list_content .= $bulk_actions ? '<th width="1px"><input type="checkbox" onchange="check_all_objects(this)" title="Select all"></th><th width="1px"></th>' : '<th width="1px"></th>';

			for($i=0; $i < $fc; $i++) {
				$field_name = $options['fields'][$i];
				$field_caption = $type_fields[$field_name]['caption'] ? $type_fields[$field_name]['caption'] : $field_name; 

				$list_content .= '<th'.($i!=$fc-1?' width="1px"':'').'><nobr>&nbsp;<a href="'.replace_url_param('order', $order=='ASC'?'DESC':'ASC', replace_url_param('sort', $field_name)).'" title="Sort by '.$field_name.'">'.$field_caption.'</a>';

				if($field_name == $sort_by) {
					if($order == 'ASC') $list_content .= '<div class="asc"></div>';
					else $list_content .= '<div class="desc"></div>';
				}

				$list_content .= '&nbsp;</nobr></th>';
			}

			$list_content .= '<th width="1px"></th>';
	
			//FILTER
			//----------------------------------------------------------------------------

			$list_content .= '</tr>';
	
			$list_content .= '<tr>';		
			$list_content .= $bulk_actions ? '<th></th>' : '';
			$list_content .= '<th></th>';	
			
			for($i=0; $i<$fc; $i++) {
				$fn = 'filter-'.normalize_string($options['fields'][$i]);
				$list_content .= '<th><div class="criteria"><input class="criteria" type="text" id="'.$fn.'" name="'.$fn.'" value="'.$_GET[$fn].'"></div></th>';
			}

			$list_content .= '
			<th style="text-align:left">
				<div class="filter" title="Apply filter" onclick="submit_explorer_filter(this,\''.urlencode(current_page_url()).'\')"></div>
			</th>';
	
			$list_content .= '</tr>';
			
			//---------------------------------------------------------------------------- 
			
			foreach($objects as $object_id => $object)
			{
				foreach ($object as $key => &$value) {
					$field_type = $type_fields[$key]['type'];
					
/*					if ($key == 'created' || $key == 'modified') {
						$value = date("d/m/Y",$value);
					}
					*/
					switch ($field_type) {
						case 'DATETIME':
							$value = date("d/m/Y",$value);
						break;
						case is_numeric($field_type):
							$value = $type_fields[$key]['enum'][$value];
						break;
					}
				}
				
				$list_content .= $is_system && $filter_system ? '<tr class="system" title="System instance">' : '<tr>';
				
				if ($bulk_actions) {
					$list_content .= '<td><input type="checkbox" class="checks" value="'.$object_id.'"></td>';
				}
				
				$list_content .= '<td class="index">&nbsp;'.($offset++ + 1).'&nbsp;</td>';				
				
				$last_td = '<td class="overflow">'.array_pop($object).'</td>';
				
				$list_content .= '<td>'.implode('&nbsp;</td><td>&nbsp;', array_values($object)).'&nbsp;</td>'.$last_td;
	
				$action_url = $options['action_url'] ? $options['action_url'] : '';
			
				$list_content .= '<td>';
				
				$options['actions'] = array_unique($options['actions']);
			
				for($j=0; $j<count($options['actions']); $j++) {
					switch($options['actions'][$j]) {
						case 'view':
							$list_content .= '<a class="tiny-button view" href="#" onclick="view_object(\''.$object_type_id.'\',\''.$object_id.'\')"></a>';
						break;
						case 'edit':
							$list_content .= '<a class="tiny-button edit" href="'.replace_url_params(array('object_type_id'=>$object_type_id,'object_id'=>$object_id)).'"></a>';
						break;
						case 'delete':
							$list_content .= '
							<form style="display:inline" method="post" action="'.$action_url.'">
								<input type="hidden" name="object_action" value="delete">
								<input type="hidden" name="object_type_id" value="'.$object_type_id.'">
								<input type="hidden" name="object_id" value="'.$object_id.'">
								<input class="tiny-button delete" type="button" value="">
							</form>';
						break;
						default:
							$list_content .= '<div class="button small light-gray">'.$options['actions'][$j].'</div>';
					}						
				}
				
				$list_content .= '</td>';
				$list_content .= '</tr>';
			}
			$list_content .= '</table>';
		}
	}

	$tmpl_list = str_replace('%%LIST_CONTENT%%',$list_content,$tmpl_list);

	//-------------------------------------------------------------------------
	
	$tmpl = str_replace('%%FILTER%%',$tmpl_filter,$tmpl);
	$tmpl = str_replace('%%ACTIONS%%',$tmpl_actions,$tmpl);
	$tmpl = str_replace('%%LIST%%',$tmpl_list,$tmpl);
	$tmpl = str_replace('%%NAVIGATOR%%',$tmpl_navigator,$tmpl);
	
	return $tmpl_types.$tmpl;
}

function object_explorer_navigation($a, $q, $cp)
{
	$interval = 2;
	$pages = $a%$q ? (int)($a/$q)+1 : $a/$q;
	
	$page_from = $cp > $interval ? $cp - $interval : 1;
	$page_to = $cp < ($pages - $interval) ? $cp + $interval : $pages;

	$url_get = '?';
	foreach($_GET as $key => $value) $url_get .= $key != 'p' && $key != 'path' ? $key.'='.$value.'&' : '';
	
	$out = '<div class="nav-bar">';
	$out .= '<div class="items">'.$a.' item(s)</div>';
	
	if($pages>1) {
		$out .= $cp > 1 ? '<a href="'.$url_get.'p='.($cp-1).'">Prev</a>' : '<span class="disabled">Prev</span>';	
		
		if ($page_from > 1) $out .= $cp==1 ? '<span class="selected">1</span>' : '<a href="'.$url_get.'p=1">1</a>';
		if ($page_from > 2) $out .= '...'; 
		
		for($i=$page_from; $i<=$page_to; $i++) $out .= $i==$cp ? '<span class="selected">'.$i.'</span>' : '<a href="'.$url_get.'p='.$i.'">'.$i.'</a>';
		
		if ($page_to < $pages-1) $out .= '...';
		if ($page_to < $pages) $out .= $cp==$pages ? '<span class="selected">'.$pages.'</span>' : '<a href="'.$url_get.'p='.$pages.'">'.$pages.'</a>';
	
		$out .= $cp < $pages ? '<a href="'.$url_get.'p='.($cp+1).'">Next</a>' : '<span class="disabled">Next</span>';
	}
	
	$out .= '&nbsp;</div>';

	return $out;
}