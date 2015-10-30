<?php

/********************************************************************************
 * Generate HTML code of links explorer
 * @param int $object_type_id Active object type ID. Will be replaced if set in GET or POST 
 * @param int $object_id Active object ID. Will be replaced if set in GET or POST
 * @param int $schema_id Current actual type to filter results
 * @param ("none"|"system"|"nonsystem") $filter_by Current actual type to filter results
 * @return string HTML code of the actual links explorer
 *******************************************************************************/
function links_explorer($object_type_id, $object_id, $schema_id, $filter_by = 'none')
{	
	if(!$object_type_id) {
		return new FX_Error(__FUNCTION__, _('Empty Object Type ID'));
	}
	
	if(!$object_id) {
		return new FX_Error(__FUNCTION__, _('Empty Object ID'));
	}

	if (!$schema_id) {
		return new FX_Error(__FUNCTION__, _('Empty Data Schema ID'));
	}	

	$type_links = get_type_links($object_type_id);

	if(is_fx_error($type_links)) {
		return $type_links;
	}

	if (!$type_links) {
		return '
		<div class="links-explorer">
			<div class="info-msg">'._('No link types for this object').'</div>
		</div>';
	}

	$tmpl_type_filter = $tmpl_links = '';
	$possible_count = $actual_count = 0;
	$relations = array(-1=> 'N/A', 1=>'1-1', 2=>'1-N', 3=>'N-1', 4=>'N-N');

	if ($filter_by != 'none') {

		if (is_numeric($filter_by) && isset($type_links[$filter_by])) {
			$type_links = array($filter_by => $type_links[$filter_by]);
		}
		
		foreach ($type_links as $id => $link) {
			if ($filter_by == 'system') {
				if (!$link['system']) {
					unset($type_links[$id]);
				}
				else {
					$type_links[$id]['display_name'] = $type_links[$id]['display_name'].' ('.$relations[$type_links[$id]['relation']].')';
				}
			}
			elseif ($filter_by == 'nonsystem') {
				if ($link['system']) {
					unset($type_links[$id]);
				}
				else {
					$type_links[$id]['display_name'] = $type_links[$id]['display_name'].' ('.$relations[$type_links[$id]['relation']].')';
				}
			}
		}
	}

	reset($type_links);

	if (isset($type_links[$type_filter])) {
		$one_type = true;
	}
	else {
		$one_type = false;
		if (isset($_POST['set_clt'])) {
			$type_filter = $_POST['set_clt'];
		}
		elseif (isset($_SESSION['clt']) && array_key_exists($_SESSION['clt'], $type_links)) {
			$type_filter = $_SESSION['clt'];
		}
		else {
			$type_filter = key($type_links);
		}
	}

	$tmpl = '
	<div class="links-explorer">
		%%TMPL_TYPE_FILTER%%
		%%TMPL_LINKS%%
	</div>';

	if (!$one_type) {
		$tmpl_type_filter .= '
		<div class="type-filter">
			<form action="" method="post">
				<input type="hidden" name="set_clt"/>
				<label for="clt"><strong>'._('Object Type').':</strong>&nbsp;</label>
				<select id="clt" name="clt" onchange="submit()">';
		
		foreach ($type_links as $t_id=>$t_data) {
			$s = $type_filter == $t_id ? ' selected="selected"' : '';
			$strength = $t_data['strength'] ? 'strong' : 'weak';
			$tmpl_type_filter .= '<option value="'.$t_id.'"'.$s.'>'.$t_data['display_name'].' ('.$strength.')</option>';
		
		}

		$tmpl_type_filter .= '
				</select>
				<span class="summary" title="'._('Possible links').' / '._('Actual links').'">&nbsp;%%POSSIBLE_COUNT%%&nbsp;/&nbsp;%%ACTUAL_COUNT%%&nbsp;</span>
				<font color="#999" size="-2">&nbsp;'._('The type of objects that can be linked to the current').'</font>
			</form>
		</div>';
	}

	if (!$type_filter) {
		$tmpl_links = '<div class="info-msg">'._('Select object type').'</div>';
	}
	else {

		$tmpl_links = '
		<div class="clearfix">
			<div class="lc">
				%%TMPL_POSSIBLE_LINKS%%
			</div>
			<div class="rc">
				%%TMPL_ACTUAL_LINKS%%
			</div>
		</div>';

		$tmpl_possible_links = $tmpl_actual_links = '
		<table width="100%">
		<tr>
			<td width="4%"></td>
			<td width="8%"></td>
			<td></td>
			<td width="10%"></td>
		</tr>';
			
		// Possible links
		//======================================================================================
		
		$possible_links = get_possible_links($object_type_id, $object_id, $pt, $schema_id);

		if (is_fx_error($possible_links)) {
			return $possible_links;
		}
		elseif (!isset($possible_links[$type_filter])) {
			$tmpl_possible_links = '<div class="info-msg">'._('No possible links for this object').'</div>';
		}
		else {
			foreach($possible_links[$type_filter] as $id => $link_data) {
				$possible_count++;
				//$strength = $link_data['strength'] ? 'strong' : 'weak';
				$tmpl_possible_links .= '
				<tr class="link-wrap'.($possible_count % 2 ? ' odd' : '').'">
					<td>&nbsp;'.$possible_count.')&nbsp;</td>
					<td class="min-midth">&nbsp;'.$type_filter.'.'.$id.'&nbsp;</td>
					<td>&nbsp;<a href="'.URL.'data_editor/data_objects?object_type_id='.$type_filter.'&object_id='.$id.'">'.$link_data['display_name'].'</a></td>
					<td>
						<form method="post" class="link-btn">
							<input type="hidden" name="object_action" value="link">
							<input type="hidden" name="target_object" value="'.$type_filter.'.'.$id.'">
							<input class="tiny-button link" title="'._('Link').'" type="submit" value="">
						</form>
					</td>
				</tr>';
			}
			$tmpl_possible_links .= '</table>';
		}		
		
		// Actual links
		//======================================================================================

		$actual_links = get_actual_links($object_type_id, $object_id, $type_filter, $schema_id);

		if (is_fx_error($actual_links)) {
			return $actual_links;
		}
		elseif (!isset($actual_links[$type_filter])) {
			$tmpl_actual_links = '<div class="info-msg">'._('No actual links for this object').'</div>';
		}
		else {
			foreach($actual_links[$type_filter] as $id => $link_data) {
				$actual_count++;
				$meta = $link_data['meta'] ? '<span class="meta" title="This link has metadata">meta</span>' : '';
				$tmpl_actual_links .= '
				<tr class="link-wrap'.($actual_count % 2 ? ' odd' : '').'">
					<td>&nbsp;'.$actual_count.')&nbsp;</td>
					<td class="min-midth">&nbsp;'.$type_filter.'.'.$id.'&nbsp;</td>
					<td>&nbsp;<a href="'.URL.'data_editor/data_objects?object_type_id='.$type_filter.'&object_id='.$id.'">'.$link_data['display_name'].'</a>&nbsp;'.$meta.'</td>
					<td>
						<form method="post" class="link-btn">
							<input type="hidden" name="object_action" value="unlink">
							<input type="hidden" name="target_object" value="'.$type_filter.'.'.$id.'">
							<input class="tiny-button unlink" title="'._('Unlink').'" type="submit" value="">
						</form>
					</td>
				</tr>';
			}
			$tmpl_actual_links .= '</table>';
		}
		
		//======================================================================================
		
		$tmpl_links = str_replace('%%TMPL_POSSIBLE_LINKS%%', $tmpl_possible_links, $tmpl_links);
		$tmpl_links = str_replace('%%TMPL_ACTUAL_LINKS%%', $tmpl_actual_links, $tmpl_links);		
		
	}

	$tmpl = str_replace('%%TMPL_TYPE_FILTER%%', $tmpl_type_filter, $tmpl);
	$tmpl = str_replace('%%POSSIBLE_COUNT%%', $possible_count, $tmpl);
	$tmpl = str_replace('%%ACTUAL_COUNT%%', $actual_count, $tmpl);	
	$tmpl = str_replace('%%TMPL_LINKS%%', $tmpl_links, $tmpl);

	return $tmpl;
}