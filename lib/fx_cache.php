<?php

function add_api_cache($subscription_id, $endpoint, $request, $data, $object_type_id)
{
	if (get_api_cache($endpoint, $request)) {
		return true;
	}
	
	global $fx_db;
	
	$cache = array(
		'sfx_id' => intval($subscription_id),
		'request_hash' => sha1($endpoint.$request),
		'time' => time(),
		'data' => $data,
		'object_type_id' => intval($object_type_id)
	);
	
	$res = $fx_db->insert(DB_TABLE_PREFIX."api_cache", $cache);
	
	if (is_fx_error($res)) {
		add_log_message(__FUNCTION__, $res->get_error_message());
		return false;
	}
	
	return true;
}

function get_api_cache($endpoint, $request)
{
	global $fx_db;
	
	$hash = sha1($endpoint.$request);
	
	$fx_db->select(DB_TABLE_PREFIX."api_cache", 'data')->where(array('request_hash' => $hash))->limit(1);

	if (!is_fx_error($fx_db->select_exec())) {
		if ($cache = $fx_db->get()) {	
			return $cache['data'];
		}
	}
	else {
		add_log_message(__FUNCTION__, _('Unable to get cached data').' ['.$hash.']');		
	}
	
	return false;
}

function clear_api_cache_by_type($object_type_id)
{
	global $fx_db;
	
	$res = $fx_db->delete(DB_TABLE_PREFIX."api_cache", array('object_type_id'=>intval($object_type_id)));
	
	if (is_fx_error($res)) {
		add_log_message(__FUNCTION__, $res->get_error_data());
		return false;
	}

	return true;
}

function clear_api_cache_by_user($sfx_id)
{
	global $fx_db;
	
	$res = $fx_db->delete(DB_TABLE_PREFIX."api_cache", array('sfx_id'=>intval($sfx_id)));
	
	if (is_fx_error($res)) {
		add_log_message(__FUNCTION__, $res->get_error_data());
		return false;
	}

	return true;
}

function clear_api_cache()
{
	global $fx_db;
	
	$res = $fx_db->delete(DB_TABLE_PREFIX."api_cache", array('request_hash <>' => ''));
	
	if (is_fx_error($res)) {
		add_log_message(__FUNCTION__, $res->get_error_data());
		return false;
	}

	return true;
}

function clear_api_cache_older_then($time = 0)
{
	global $fx_db;
	
	if ($time == 0) {
		$time = time() - 3600*24; //1 day	
	}
	
	$res = $fx_db->delete(DB_TABLE_PREFIX."api_cache", array('time <' => $time));
	
	if (is_fx_error($res)) {
		add_log_message(__FUNCTION__, $res->get_error_data());
		return false;
	}

	return true;
}

function update_user_cache($sfx)
{
	global $fx_db;

	$sfx_id = $sfx['subscription_id'];
	$data = addslashes(serialize($sfx));

	$fx_db->select(DB_TABLE_PREFIX."sfx_cache", 'sfx_id')->where(array('sfx_id'=>$sfx_id))->limit(1)->select_exec();
	$exists = $fx_db->get();

	if (!$sfx['api_key']) $sfx['api_key'] = '';

	if ($exists) {
		$res = $fx_db->update(DB_TABLE_PREFIX."sfx_cache", array('data'=>$data), array('sfx_id'=>$sfx_id));
	}
	else {
		$res = $fx_db->insert(DB_TABLE_PREFIX."sfx_cache", array('sfx_id'=>$sfx_id, 'api_key'=>$sfx['api_key'], 'data'=>$data));
	}

	if (is_fx_error($res)) {
		add_log_message($exists ? 'update_sfx_cache' : 'insert_sfx_cache', $res->get_error_data().' '.print_r($res->get_error_data(),true));
		return false;
	}

	return true;
}

function clear_user_cache($api_key = false)
{
	clear_api_cache();
	
	global $fx_db;
	
	if ($api_key !== false) {
		$res = $fx_db->delete(DB_TABLE_PREFIX."sfx_cache", array('api_key'=>$api_key));
	}
	else {
		$res = $fx_db->delete(DB_TABLE_PREFIX."sfx_cache");	
	}
	
	if (is_fx_error($res)) {
		add_log_message(__FUNCTION__, $res->get_error_data());
		return false;
	}

	return true;
}

function get_user_cache($id)
{
	global $fx_db;
	
	$condition = is_numeric($id) ? array('sfx_id'=>$id) : array('api_key'=>$id);

	$fx_db->select(DB_TABLE_PREFIX."sfx_cache", 'data')->where($condition)->limit(1)->select_exec();
	$cache = $fx_db->get();

	return $cache ? unserialize(stripslashes($cache['data'])) : false;
}

function update_query_cache($query_id, $query_object, $query_result, $query_map)
{
	global $fx_db;

	$data = addslashes(serialize(array('object'=>$query_object, 'result'=>$query_result, 'map'=>$query_map)));

	$fx_db->select(DB_TABLE_PREFIX."query_cache", 'query_id')->where(array('query_id'=>$query_id))->limit(1)->select_exec();
	$cache_exists = $fx_db->get();

	if ($cache_exists) {
		$res = $fx_db->update(DB_TABLE_PREFIX."query_cache", array('data'=>$data))->where(array('query_id'=>$query_id));
	}
	else {
		$res = $fx_db->insert(DB_TABLE_PREFIX."query_cache", array('query_id'=>$query_id, 'data'=>$data));
	}

	if (is_fx_error($res)) {
		add_log_message($cache_exists ? 'update_query_cache' : 'insert_query_cache', $res->get_error_data());
		return false;
	}

	return true;
}

function get_query_cache($query_id)
{
	global $fx_db;

	$fx_db->select(DB_TABLE_PREFIX."query_cache", 'data')->where(array('query_id'=>intval($query_id)))->limit(1)->select_exec();
	
	$cache = $fx_db->get();
	
	return $cache ? unserialize(stripslashes($cache['data'])) : false;
}

function clear_query_cache($queries = false)
{
	clear_api_cache();
	
	global $fx_db;
	
	if ($queries) {
		
		$code = 'clear_query_cache_by_id';
		
		if (is_numeric($queries) && $queries>0) {
			$queries = array($queries);
		}
		elseif (is_array($queries)) {
			foreach ($queries as $a => $b) {
				if (!is_int($a)) {
					add_log_message($code, _('Passed array contain non-numeric values'));
					return false;
				}
			}
		}
		else {
			add_log_message($code, _('Invalid argument'));
			return false;
		}
		$res = $fx_db->delete(DB_TABLE_PREFIX."query_cache", array('query_id IN'=>(array)$queries));
	}
	else {
		$res = $fx_db->delete(DB_TABLE_PREFIX."query_cache");
		$code = 'clear_query_cache_all';	
	}

	if (is_fx_error($res)) {
		add_log_message($code, $res->get_error_data());
		return false;
	}

	return true;
}

function clear_query_cache_by_type($object_type_id)
{
	clear_api_cache();
	clear_query_cache(get_queries_by_type($object_type_id));
}

function clear_query_cache_by_schema($schema_id) {
	clear_api_cache();
	clear_query_cache(get_queries_by_schema($schema_id));
}	

function get_queries_by_schema($schema_id)
{
	global $fx_db;

	$fx_db->select(DB_TABLE_PREFIX."object_type_".TYPE_QUERY, 'object_id')->where(array('schema_id'=>$schema_id))->select_exec();

	$result = array();

	foreach ($fx_db->get_all() as $row) {
		$result[] = $row['object_id'];
	}

	return $result;
}