<?php
	if (isset($_POST['dropbox_api_key'])) {
		$dropbox_api['key'] = $_POST['dropbox_api_key'];
		$dropbox_api['secret'] = $_POST['dropbox_api_secret'];
		update_fx_option('fx_dropbox_keys', $dropbox_api);
	}
	
	$dropbox_api = get_fx_option('fx_dropbox_keys', array());
?>

<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
            <div class="icons settings"></div>
            <h1>External API Keys</h1>
        </div>
        <div class="content">
            <h2>Dropbox API</h2>
             <form method="post">
                <table class="profileTable">
                    <tr>
                        <th></th>
                        <td><p>You can create Dropbox App <a href="https://www.dropbox.com/developers/apps" target="_blank">here</a></p></td>
                    </tr>
                    <tr>
                        <th><label for="dropbox_api_key">App key:</label></th>
                        <td><input type="text" name="dropbox_api_key" id="dropbox_api_key" value="<?php echo $dropbox_api['key'] ? $dropbox_api['key'] : ''; ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="dropbox_api_secret">App secret (if you have):</label></th>
                        <td><input type="text" id="dropbox_api_key" name="dropbox_api_secret" value="<?php echo $dropbox_api['secret'] ? $dropbox_api['secret'] : ''; ?>"></td>
                    </tr>
                </table>
                <input type="submit" class="button green" value="Save"/>
            </form>
        </div>
    </div>
</div>