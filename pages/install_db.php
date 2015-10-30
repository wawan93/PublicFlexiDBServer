<?php

	global $fx_db_install;

    try {
        $fx_db_install = DB_Wrapper::connect('localhost', 'root', 'M1llions', 'flexidev_install');
        $fx_db_install->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
		echo '
        <center>
            <h1>FlexiDB Database Error</h1>
            <p>&nbsp;</p>
            <p>'.$e->getMessage().'</p>
        </center>';
    }
	
	
	echo 'install';
	
	
	// use $fx_db_install instead of $fx_db!!!!!!!
?>