<?php
	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}

	function _schema_settings_ctrl()
	{
		global $fx_error, $data_schema;

		$ss = get_schema_settings($data_schema['object_id']);

		if (isset($_POST['set_schema_settings']))
		{
			$ss['link_option'] = $_POST['link_option'];

			$ss['schema_db'] = array(
				'db_host' => $_POST['db_host'],
				'db_user' => $_POST['db_user'],
				'db_pass' => $_POST['db_pass']
			);
			
			$ss['default_auto_disabled'] = isset($_POST['default_auto_disabled']) ? 1 : 0;
			
			$schema_settings[$data_schema['object_id']] = $ss;
			
			update_fx_option('schema_settings', $schema_settings);
			
			fx_redirect();
		}
		
		$strong_link_option = intval($ss['link_option']);
		
		$lo_forbid_check = $strong_link_option == LINK_OPTION_FORBID || !$strong_link_option ? ' checked="checked"' : '';
		$lo_delete_check = $strong_link_option == LINK_OPTION_DELETE ? ' checked="checked"' : '';
		
		if ($data_schema['schema_db']) {
			$schema_db = $ss['schema_db'];
			$schema_db_host = isset($schema_db['db_host']) ? $schema_db['db_host'] : DB_HOST;
			$schema_db_user = isset($schema_db['db_user']) ? $schema_db['db_user'] : DB_USER;
			$schema_db_pass = isset($schema_db['db_pass']) ? $schema_db['db_pass'] : DB_PASS;
		}

		$default_auto_disabled = intval($ss['default_auto_disabled']) ? ' checked="checked"' : '';

	?>
        <h1><?php echo _('Strong links') ?></h1>
        <form action="" method="post">
            <input type="hidden" name="set_schema_settings"/>
            <table class="profileTable">
            <tr>
            	<td colspan="2"><?php echo _('Select action on removing an object which has with strongly linked child objects') ?>?</td>
            </tr>
            <tr>
                <th><input type="radio" name="link_option" id="link_option_1" value="<?php echo LINK_OPTION_FORBID ?>"<?php echo $lo_forbid_check ?>/></th>
                <td><label for="link_option_1"><?php echo _('Do not delete the object until exist its children') ?></label></td>
            </tr>            
            <tr>
                <th><input type="radio" name="link_option" id="link_option_2" value="<?php echo LINK_OPTION_DELETE ?>"<?php echo $lo_delete_check ?>/></th>
                <td><label for="link_option_2"><?php echo _('Delete all linked objects') ?></label></td>
            </tr>
            <tr>
                <td colspan="2"><hr></td>
            </tr>
            
            <?php if ($data_schema['schema_db']): ?>
            <tr>
                <th><label><?php echo _('DB Name') ?></label></td>            
                <td><input disabled="disabled" type="text" value="<?php echo '_db_'.$data_schema['name']; ?>"/></th>
            </tr>
            <tr>
                <th><label for="db_host"><?php echo _('DB Host') ?></label></td>            
                <td><input type="text" name="db_host" id="db_host" value="<?php echo $schema_db_host; ?>"/></th>
            </tr>            
            <tr>
                <th><label for="db_user"><?php echo _('DB User') ?></label></td>            
                <td><input type="text" name="db_user" id="db_user" value="<?php echo $schema_db_user; ?>"/></th>
            </tr>
            <tr>
                <th><label for="db_pass"><?php echo _('DB Password') ?></label></td>            
                <td><input type="text" name="db_pass" id="db_pass" value="<?php echo $schema_db_pass; ?>"/></th>
            </tr>
            <tr>
                <td colspan="2"><hr></td>
            </tr>
            <?php endif; ?>

            <tr>
                <th><input type="checkbox" id="default_auto_disabled" name="default_auto_disabled"<?php echo $default_auto_disabled; ?>/></td>            
                <td><label for="default_auto_disabled"><?php echo _('Disable auto creation for of default types and forms') ?></label></th>
            </tr>

            <tr>
                <th></th>
                <td><input type="submit" class="button green" value="<?php echo _('Save') ?>"/></td>
            </tr>
            </table>
        </form>
	<?php
	}
	
	$mb_data = array('body' => array('function' => '_schema_settings_ctrl'),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);
?>