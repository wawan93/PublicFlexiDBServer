<?php
$site_object = array(
    'domain_name' => $_SERVER['SERVER_NAME'],
    'linux_username' => 'root',
    'linux_password' => 't0msk'
);
?>

<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
            <div class="icons terminal"></div>
            <h1>PHP Unit</h1>
        </div>
        <div class="content">
            <?php
            if (isset($_POST['run_tests'])) {
                if (!$ssh = new Net_SSH2($site_object['domain_name'])) {
                    echo '<div class="error">Invalid host</div>';
                }
                if (!$ssh -> login($site_object['linux_username'], $site_object['linux_password'])) {
                    echo '<div class="error">Login failed</div>';
                } else {
                    $command = 'phpunit --bootstrap '.CONF_FX_DIR.'/fx_load.php '.CONF_FX_DIR.'/tests';
                    $test_results = $ssh -> exec($command);
                }
            }
            ?>
            <form action="" method="post">
                <input type="hidden" name="run_tests">
                <input type="submit" value="Run tests!" class="button green">
            </form>
            <textarea style="height: 300px; width: 100%; max-width: 100%;"><?php echo $test_results?></textarea>
        </div>
    </div>
</div>