<?php

function get_set_roles($set_id)
{
	if (!$set_id) {
		return new FX_Error(__FUNCTION__, _('Empty Data Set ID'));
	}
	
	if (!object_exists(TYPE_DATA_SET, $set_id)) {
		return new FX_Error(__FUNCTION__, _('Specified Data Set does not exists'));
	}
	
	global $fx_db;
	
	$query = "SELECT r.object_id, r.name, r.display_name, r.permissions FROM ".DB_TABLE_PREFIX."object_type_".TYPE_ROLE." r
			  JOIN ".DB_TABLE_PREFIX."object_type_".TYPE_DATA_SET." s
			  ON r.schema_id = s.schema_id 
			  WHERE s.object_id = :set_id AND r.data_set_role=1";
			  
	$pdo = $fx_db -> prepare($query);

    $pdo -> bindValue(":set_id", $set_id, PDO::PARAM_INT);
    
	if (!$pdo->execute()) {
        return new FX_Error(__FUNCTION__, _('SQL ERROR'));
	}
	
	$result = array();
	
	foreach($pdo->fetchAll() as $row) {
		$row['permissions'] = json_decode($row['permissions'], true);
		$row['permissions'] = $row['permissions'] !== null ? $row['permissions'] : array();
		$result[$row['object_id']] = $row;
	}
	
	return $result;
}

function get_sfx_roles($sfx_id)
{
	if (!(int)$sfx_id) {
		return new FX_Error(__FUNCTION, _('Empty Subscription ID'));
	}

	global $fx_db;

	$default_sfx = get_sfx_base_by_api_key('', true);
	$default_sfx_id = $default_sfx['subscription_id'];

	if ($default_sfx_id && $sfx_id != $default_sfx_id) {
		$dc1 = " OR links.object_2_id = $default_sfx_id";
		$dc2 = " OR links.object_1_id = $default_sfx_id";
	}

	$query = "SELECT roles.object_id, roles.permissions, roles.schema_id, roles.display_name, roles.data_set_role, links.meta
			  FROM ".DB_TABLE_PREFIX."object_type_".TYPE_ROLE." roles 
			  JOIN ".DB_TABLE_PREFIX."link_tbl links
			  ON (roles.object_id = links.object_1_id AND links.object_type_1_id = ".TYPE_ROLE.") OR (roles.object_id = links.object_2_id AND links.object_type_2_id = ".TYPE_ROLE.")
			  WHERE (links.object_type_1_id = ".TYPE_ROLE." AND links.object_type_2_id = ".TYPE_SUBSCRIPTION." AND (links.object_2_id = $sfx_id".$dc1.")) 
						 	OR (links.object_type_2_id = ".TYPE_ROLE." AND links.object_type_1_id = ".TYPE_SUBSCRIPTION." AND (links.object_1_id = $sfx_id".$dc2."))";

	$pdo = $fx_db -> prepare($query);
	
	if (!$pdo->execute()) {
        return new FX_Error(__FUNCTION__, _('SQL ERROR'));
	}

	$roles = $pdo->fetchAll();

	$result = array('roles_schema' => array(), 
					'roles_set' => array(),
					'permissions_schema' => array(),
					'permissions_set' => array(),
					'roles' => array());

	foreach ($roles as $role) {

		if ($role['meta']) {
			$meta = unserialize($role['meta']);
			$meta = is_array($meta) ? $meta : (array)$role['meta'];
		}
		else {
			$meta = $role['meta'];
		}

		$permissions = (array)json_decode($role['permissions'], true);

		$result['roles'][] = $role['object_id'];

		if (!$role['data_set_role'] /*&& !$meta*/) {
			$result['roles_schema'][$role['schema_id']][$role['object_id']] = array('display_name' => $role['display_name'], 'permissions' => $permissions);
			
			foreach($permissions as $type => $perm) {
				$result['permissions_schema'][$role['schema_id']][$type] = $result['permissions_schema'][$role['schema_id']][$type] | $perm;
			}
		}
		elseif ($role['data_set_role'] && $meta) {
			foreach ($meta as $set_id) {
				$result['roles_set'][$set_id][$role['object_id']] = array('display_name' => $role['display_name'], 'permissions' => $permissions);

				foreach($permissions as $type => $perm) {
					$result['permissions_set'][$set_id][$type] = $result['permissions_set'][$set_id][$type] | $perm;
				}
			}	
		}
	}

	return $result;
}

function get_fsm_events($object_type, $object_field)
{
	global $fx_db;

	$query = "SELECT code FROM ".DB_TABLE_PREFIX."object_type_".TYPE_FSM_EVENT." WHERE enabled='1' AND object_type=:object_type AND object_field=:object_field LIMIT 1";

	$pdo = $fx_db -> prepare($query);
    $pdo->bindValue(":object_type", $object_type, PDO::PARAM_INT);
    $pdo->bindValue(":object_field", $object_field, PDO::PARAM_STR);

	if ($pdo -> execute() === false) {
		add_log_message(__FUNCTION__, 'SQL Error: '.$query);
		return false;
	}

	if(!$res = $pdo->fetch()) {
		return array();
	}

	$code = json_decode($res['code'], true);
	$code = $code !== NULL ? $code : array();
	
	return $code;
}

function get_fsm_initial_events($object_type, $object_field)
{
	global $fx_db;

	$query = "SELECT object_id, initial_state FROM ".DB_TABLE_PREFIX."object_type_".TYPE_FSM_EVENT." WHERE enabled='1' AND object_type=:object_type AND object_field=:object_field LIMIT 1";

	$pdo = $fx_db->prepare($query);
    $pdo->bindValue(":object_type", $object_type, PDO::PARAM_INT);
    $pdo->bindValue(":object_field", $object_field, PDO::PARAM_STR);

	if ($pdo -> execute() === false) {
		add_log_message(__FUNCTION__, 'SQL Error: '.$query);
		return false;
	}

	if(!$res = $pdo->fetch()) {
		return false;
	}

	return array($res['object_id'] => $res['initial_state']);
}