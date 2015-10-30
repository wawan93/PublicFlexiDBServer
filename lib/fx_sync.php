<?php

function confirm_sync($sfx_id, $set_id)
{
	global $fx_db;
	
	$sfx_id = (int)$sfx_id;
	$set_id = (int)$set_id;

    $fx_db->select(DB_TABLE_PREFIX.'sync_tbl', array('id'))
        ->where(array( 'sfx_id'=>$sfx_id, 'set_id'=>$set_id ))
        ->limit(1);
	
	if (is_fx_error($fx_db->select_exec())) {
		return $fx_db->get_last_error();
	}	

	$sync_id = 0;

    if($row = $fx_db->get()) {
		$sync_id = $row['id'];
	}

	if ($sync_id) {
        $pdo = $fx_db->update(
            DB_TABLE_PREFIX.'sync_tbl',
            array('updated'=>time()),
            array('id'=>$sync_id)
        );

		if (is_fx_error($pdo)) {
			return $pdo;
		}
	}
	else {
        $pdo = $fx_db->insert(DB_TABLE_PREFIX.'sync_tbl', array(
            'sfx_id'=>$sfx_id,
            'set_id'=>$set_id,
            'updated'=>time()
        ));

		if (is_fx_error($pdo)) {
			return $pdo;
		}
		
		$sync_id = $fx_db->lastInsertId(DB_TABLE_PREFIX.'sync_tbl');
	}

	return $sync_id;
}

function get_sync($sfx_id, $set_id)
{
	global $fx_db;
	
	$sfx_id = (int)$sfx_id;
	$set_id = (int)$set_id;

    $fx_db->select(DB_TABLE_PREFIX.'sync_tbl')
        ->where(array( 'sfx_id'=>$sfx_id, 'set_id'=>$set_id ))
        ->limit(1);

	if (is_fx_error($fx_db->select_exec())) {
		return $fx_db->get_last_error();
	}
	
	$last_sync = $fx_db->get();
	
	return $last_sync ? $last_sync : array('updated'=>0);
}

function get_sync_map() 
{
	
}