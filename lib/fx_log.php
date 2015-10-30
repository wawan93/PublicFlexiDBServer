<?php

/*******************************************************************************
 * Remove Log Message
 * @log_id int - message ID
 * @return bool - remove result
 ******************************************************************************/
function remove_log_message($msg_id)
{
	return delete_object(TYPE_LOG_MSG, $msg_id) === true ? true : false;
}
//*********************************************************************************

/*******************************************************************************
 * Drop Log
 * @return bool - drop result
 ******************************************************************************/
function drop_log($log_id)
{
	global $fx_db;
	$sth = $fx_db -> prepare("DELETE FROM table_name ".DB_TABLE_PREFIX."object_type_".TYPE_LOG_MSG);
	return $sth -> execute() ? true : false;
}
//*********************************************************************************

/*******************************************************************************
 * Add Log Message
 * @code string - message ID
 * @msg string - message ID
 * @return bool - remove result
 ******************************************************************************/
function add_log_message($code, $msg = '', $data = '')
{
	$log = array('object_type_id' => TYPE_LOG_MSG,
				 'schema_id' => 0,
				 'display_name' => 'msg',
				 'code' => normalize_string($code),
				 'msg' => $msg,
				 'data' => $data);

	$msg_id = add_object($log);
	
	return is_numeric($msg_id) ? $msg_id : false;
}
//*********************************************************************************

?>