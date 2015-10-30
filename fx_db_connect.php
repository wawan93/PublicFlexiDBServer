<?php

    require_once CONF_FX_DIR.'/db_wrapper/abstract_wrapper.php';

	global $fx_db;

    try {
        $fx_db = DB_Wrapper::connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $fx_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
		echo '
        <center>
            <h1>FlexiDB Database Error</h1>
            <p>&nbsp;</p>
            <p>'.$e->getMessage().'</p>
        </center>';
    }