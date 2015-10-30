<?php

	global $fx_error;

	$locale_settings = get_fx_option('locale_settings', array());
	$local_ftp_options = get_fx_option('local_ftp_options', array());
	$rss_1 = get_fx_option('rss_options_1', array());
	$rss_2 = get_fx_option('rss_options_2', array());
    $date_format_settings = get_fx_option('fx_datetime_format', array());
    $fx_default_revision_number = get_fx_option('fx_default_revision_number', 1);
	$debug_mode = get_fx_option('debug_mode', 0);

	$timezones = timezone_identifiers_list();

	if (isset($_GET['refresh_update_servers'])) {		
		$dfx_version = _update_version_info();
		
		if (is_fx_error($dfx_version)) {
			$fx_error->add('refresh_update_ftp_list', $dfx_version->get_error_message());
		}
		else {
			fx_redirect(replace_url_param('refresh_update_servers', ''));
		}
	}

	if (isset($_POST['clear_cache'])) {		
		clear_user_cache();
		clear_query_cache();
		clear_api_cache();
		delete_fx_option('system_types_cache');
	}

	if (isset($_POST['set_debug_mode'])) {		
		update_fx_option('debug_mode', $_POST['debug_mode'] ? 1 : 0);
		fx_redirect();
	}

	if (isset($_POST['set_locale_settings'])) {
		$locale_settings['timezone'] = $_POST['timezone'];
		$locale_settings['locale'] = $_POST['locale'];
		
		update_fx_option('locale_settings', $locale_settings);
		fx_redirect();
	}

	if (isset($_POST['set_local_ftp_options'])) {

		$local_ftp_options['ftp_username'] = $_POST['ftp_username'];
		$local_ftp_options['ftp_password'] = $_POST['ftp_password'];
		
		update_fx_option('local_ftp_options', $local_ftp_options);
		fx_redirect();
	}
	
	if (isset($_POST['set_rss_1'])) {
		$rss_1['rss_title'] = $_POST['rss_1_title'];
		$rss_1['rss_url'] = $_POST['rss_1_url'];
		$rss_1['rss_items'] = $_POST['rss_1_items'];
		$rss_1['rss_show_content'] = isset($_POST['rss_1_show_content']) ? 1 : 0;
		$rss_1['rss_show_date'] = isset($_POST['rss_1_show_date']) ? 1 : 0;
		$rss_1['rss_show_author'] = isset($_POST['rss_1_show_author']) ? 1 : 0;
		
		update_fx_option('rss_options_1', $rss_1);

		fx_redirect();
	}
	
	if (isset($_POST['set_rss_2'])) {
		$rss_2['rss_title'] = $_POST['rss_2_title'];
		$rss_2['rss_url'] = $_POST['rss_2_url'];
		$rss_2['rss_items'] = $_POST['rss_2_items'];
		$rss_2['rss_show_content'] = isset($_POST['rss_2_show_content']) ? 1 : 0;
		$rss_2['rss_show_date'] = isset($_POST['rss_2_show_date']) ? 1 : 0;
		$rss_2['rss_show_author'] = isset($_POST['rss_2_show_author']) ? 1 : 0;
		
		update_fx_option('rss_options_2', $rss_2);
		
		fx_redirect();
	}

    if (isset($_POST['set_revision_number'])) {
        $fx_default_revision_number = (int)$_POST['default_revision_number']>0 ? (int)$_POST['default_revision_number'] : 1;
        update_fx_option('fx_default_revision_number'. $fx_default_revision_number);
		fx_redirect();
    }

    if (isset($_POST['set_datetime_format'])) {
        if ($_POST['date_format'] !== 'custom') {
            $date_format = array('custom'=>false, 'format'=>$_POST['date_format']);
        } else {
            if ($_POST['custom_date_format'] && $_POST['custom_date_format'] != '') {
                $date_format = array('custom'=>true, 'format'=>$_POST['custom_date_format']);
            }
        }

        if ($_POST['time_format'] !== 'custom') {
            $time_format = array('custom'=>false, 'format'=>$_POST['time_format']);
        } else {
            if ($_POST['custom_time_format'] && $_POST['custom_time_format'] != '') {
                $time_format = array('custom'=>true, 'format'=>$_POST['custom_time_format']);
            }
        }

        $format = array('date'=>$date_format, 'time'=>$time_format);
        update_fx_option('fx_datetime_format', $format);
        $date_format_settings = $format;
		clear_query_cache();
		fx_redirect();
    }
	
	if (!empty($_FILES['theme_file']))
	{
		$filesize = (int)($_FILES["theme_file"]["size"]);

		if ($filesize == 0) {
			$fx_error->add('theme_file', _('Empty file'));
		}
		elseif ($filesize > CONF_MAX_FILE_SIZE) {
			$fx_error->add('theme_file', _('File is too big. Maximum file size').' = '.CONF_MAX_FILE_SIZE);
		}
		else {
			if ($content = file_get_contents($_FILES["theme_file"]["tmp_name"])) {
				$content = bzdecompress($content);
				if (!$res = update_fx_option('server_default_app_theme', $content)) {
					$fx_error->add('theme_file', _('Unable to update option').' [server_default_app_theme]');
				}
			}
			else {
				$fx_error->add('theme_file', _('Unable to read theme file'));
			}
		}
		
		if ($fx_error->is_empty()) {
			fx_redirect();
		}
	}
	
	if (isset($_POST['remove_default_theme'])) {
		if (!$res = delete_fx_option('server_default_app_theme')) {
			$fx_error->add('theme_file', _('Unable to delete option').' [server_default_app_theme]');
		}

		if ($fx_error->is_empty()) {
			fx_redirect();
		}
	}
	
	$locale_domains = array();
	
	if (is_dir(CONF_LOCALE_DIR)) {
		if ($cur_dir = @opendir(CONF_LOCALE_DIR)) {
			while (($item = readdir($cur_dir)) != false) {
				if ($item != '.' && $item != '..') {
					$locale_domains[$item] = $item;
				}
			}
		}
	}

?>

<div class="rightcolumn">
    <div class="metabox">                
        <div class="header">
            <div class="icons settings"></div>
            <h1><?php echo _('General Settings') ?></h1>
        </div>
        <div class="content">
			
            <?php print_fx_errors($fx_error) ?>

            <h1><?php echo _('Request Caching') ?></h1>
            <form action="" method="post">
                <input type="hidden" name="clear_cache"/>
                <table class="profileTable">
                <tr>
                    <th></th>
                    <td>
                    	<strong>
							<?php echo caching_enabled() ? '<font color="green">'._('Caching enabled').'</font>' : '<font color="red">'._('Caching disabled').'</font>' ?>
                        </strong>
                    </td>
                </tr>
                <tr>
                    <th></th>
                    <td><?php echo _('To enable/disable caching, change defined constant ').'CACHING_ENABLED' ?></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" class="button green" value="<?php echo _('Clear Cache') ?>"/></td>
                </tr>
                </table>
            </form>
            <hr/> 

            <h1><?php echo _('Debug Mode') ?></h1>
            <form action="" method="post">
                <input type="hidden" name="set_debug_mode"/>
                <table class="profileTable">
                <tr>
                    <th><label for="debug_mode"><?php echo _('Use debug mode') ?></label></th>
                    <td><input type="checkbox" name="debug_mode" id="debug_mode"<?php echo $debug_mode ? ' checked="checked"' : ''?>/></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" class="button green" value="<?php echo _('Save') ?>"/></td>
                </tr>
                </table>
            </form>
            <hr/> 
                    
            <h1><?php echo _('Locale Settings') ?></h1>
            <form action="" method="post">
                <input type="hidden" name="set_locale_settings"/>
                <table class="profileTable">
                <tr>
                    <th><label for="timezone"><?php echo _('Current timezone') ?></label></th>
                    <td>
                        <select id="timezone" name="timezone"> 
                        <option value=""><?php echo _('Please select') ?></option> 
                        <?php show_select_options($timezones, '_label', '', date_default_timezone_get(), true); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="locale"><?php echo _('Current locale') ?></label></th>
                    <td>
                    <?php if ($locale_domains): ?>
                        <select id="locale" name="locale"> 
                        <option value=""><?php echo _('Default') ?></option> 
                        <?php
							$selected = isset($locale_domains[$locale_settings['locale']]) ? $locale_settings['locale'] : '';
                            show_select_options($locale_domains, '', '', $selected, true);
                        ?>
                        </select>
                    <?php 
					else: 
                    	echo _('Only the default locale is available');
                    endif; ?>
                    </td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" class="button green" value="<?php echo _('Save') ?>"/></td>
                </tr>
                </table>
            </form>
            
            <hr/>        
        
            <h1><?php echo _('Local FTP Credentials') ?></h1>
            <form action="" method="post">
                <input type="hidden" name="set_local_ftp_options"/>
                <table class="profileTable">
                <tr>
                    <th></th>
                    <td class="prompt"><?php echo _('Enter your (S)FTP credentials to allow DFX Server to change local files during update') ?></td>
                </tr>
                <tr>
                    <th><label for="ftp_username"><?php echo _('Local FTP Username') ?></label></th>
                    <td><input type="text" name="ftp_username" id="ftp_username" value="<?php echo $local_ftp_options['ftp_username']; ?>" autocomplete="off"/></td>
                </tr>
                <tr>
                    <th><label for="ftp_password"><?php echo _('Local FTP Password') ?></label></th>
                    <td><input type="password" name="ftp_password" id="ftp_password" value="<?php echo $local_ftp_options['ftp_password']; ?>" autocomplete="off"/></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" class="button green" value="<?php echo _('Save') ?>"/></td>
                </tr>
                </table>   
            </form>
            
            <hr/>

            <h1><?php echo _('Revision Number') ?></h1>
            <form action="" method="post">
                <input type="hidden" name="set_revision_number">
                <table class="profileTable">
                    <tr>
                        <th><label for="default_revision_number"><?php echo _('Default revision number') ?></th>
                        <td><input type="text" name="default_revision_number" id="default_revision_number" value="<?php echo $fx_default_revision_number;?>"></td>
                    </tr>
                     <tr>
                         <th></th>
                         <td><input type="submit" class="button green" value="<?php echo _('Save') ?>"/></td>
                     </tr>
                </table>
            </form>

            <hr/>
            
            <h1><?php echo _('Flexiweb RSS') ?> #1</h1>
            
            <form action="" method="post">
                <input type="hidden" name="set_rss_1"/>
                <table class="profileTable">
                <tr>
                    <th><label for="rss_1_title"><?php echo _('Title') ?></label></th>
                    <td><input type="text" name="rss_1_title" id="rss_1_title" size="40" value="<?php echo $rss_1['rss_title']; ?>" /></td>
                </tr>
                <tr>
                    <th><label for="rss_1_url"><?php echo _('RSS feed URL') ?></label></th>
                    <td><input type="text" name="rss_1_url" id="rss_1_url" size="40" value="<?php echo $rss_1['rss_url']; ?>" /></td>
                </tr>
                <tr>
                    <th><label for="rss_1_items"><?php echo _('Items to display') ?></label></th>
                    <td>
                        <select name="rss_1_items" id="rss_1_items" style="width:50px">
                        <?php
                            show_select_options(array_combine(range(1,20,1),range(1,20,1)),'','',(int)$rss_1['rss_items'])
                        ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="rss_1_show_content"><?php echo _('Show Item Content') ?></label></th>
                    <td><input type="checkbox" name="rss_1_show_content" id="rss_1_show_content"<?php echo $rss_1['rss_show_content'] ? ' checked="checked"' : ''?>/></td>
                </tr>
                <tr>
                    <th><label for="rss_1_show_date"><?php echo _('Show Date') ?></label></th>
                    <td><input type="checkbox" name="rss_1_show_date" id="rss_1_show_date"<?php echo $rss_1['rss_show_date'] ? ' checked="checked"' : '' ?>/></td>
                </tr>
                <tr>
                    <th><label for="rss_1_show_author"><?php echo _('Show Author') ?></label></th>
                    <td><input type="checkbox" name="rss_1_show_author" id="rss_1_show_author"<?php echo $rss_1['rss_show_author'] ? ' checked="checked"' : ''?>/></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" class="button green" value="<?php echo _('Save') ?>"/></td>
                </tr>
                </table>   
            </form>
            
            <hr/>
            
            <h1><?php echo _('Flexiweb RSS') ?> #2</h1>
            
            <form action="" method="post">
                <input type="hidden" name="set_rss_2"/>
                <table class="profileTable">
                <tr>
                    <th><label for="rss_2_title"><?php echo _('Title') ?></label></th>
                    <td><input type="text" name="rss_2_title" id="rss_2_title" size="40" value="<?php echo $rss_2['rss_title']; ?>" /></td>
                </tr>
                <tr>
                    <th><label for="rss_2_url"><?php echo _('RSS feed URL') ?></label></th>
                    <td><input type="text" name="rss_2_url" id="rss_2_url" size="40" value="<?php echo $rss_2['rss_url']; ?>" /></td>
                </tr>
                <tr>
                    <th><label for="rss_2_items"><?php echo _('Items to display') ?></label></th>
                    <td>
                        <select name="rss_2_items" id="rss_2_items" style="width:50px">
                        <?php
                            show_select_options(array_combine(range(1,20,1),range(1,20,1)),'','',(int)$rss_2['rss_items'])
                        ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="rss_2_show_content"><?php echo _('Show Item Content') ?></label></th>
                    <td><input type="checkbox" name="rss_2_show_content" id="rss_2_show_content"<?php echo $rss_2['rss_show_content'] ? ' checked="checked"' : ''?>/></td>
                </tr>
                <tr>
                    <th><label for="rss_2_show_date"><?php echo _('Show Date') ?></label></th>
                    <td><input type="checkbox" name="rss_2_show_date" id="rss_2_show_date"<?php echo $rss_2['rss_show_date'] ? ' checked="checked"' : '' ?>/></td>
                </tr>
                <tr>
                    <th><label for="rss_2_show_author"><?php echo _('Show Author') ?></label></th>
                    <td><input type="checkbox" name="rss_2_show_author" id="rss_2_show_author"<?php echo $rss_2['rss_show_author'] ? ' checked="checked"' : ''?>/></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" class="button green" value="<?php echo _('Save') ?>"/></td>
                </tr>
                </table>   
            </form>

            <hr/>

            <h1><?php echo _('Date and Time format') ?></h1>
            <form action="" method="post">
                <input type="hidden" name="set_datetime_format">
                <table class="profileTable">
                    <tr>
                        <th><?php echo _('Date Format') ?>:</th>
                        <td>
                            <?php $checked = $date_format_settings['date']['format'] == "F j, Y" ? 'checked' : ''?>
                            <input type="radio" name="date_format" value="F j, Y" id="format1" <?php echo $checked?>><label for="format1"><?php echo date('F j, Y')?></label>
                            <br>
                            <?php $checked = $date_format_settings['date']['format'] == "Y/m/d" ? 'checked' : ''?>
                            <input type="radio" name="date_format" value="Y/m/d" id="format2" <?php echo $checked?>><label for="format2"><?php echo date('Y/m/d')?></label>
                            <br>
                            <?php $checked = $date_format_settings['date']['format'] == "m/d/Y" ? 'checked' : ''?>
                            <input type="radio" name="date_format" value="m/d/Y" id="format3" <?php echo $checked?>><label for="format3"><?php echo date('m/d/Y')?></label>
                            <br>
                            <?php $checked = $date_format_settings['date']['format'] == "d/m/Y" ? 'checked' : ''?>
                            <input type="radio" name="date_format" value="d/m/Y" id="format4" <?php echo $checked?>><label for="format4"><?php echo date('d/m/Y')?></label>
                            <br>
                            <?php $checked = $date_format_settings['date']['custom'] ? 'checked' : ''?>
                            <input type="radio" name="date_format" value="custom" id="format5" <?php echo $checked?>><label for="format5">
                            <input type="text" name="custom_date_format" value="<?php echo $date_format_settings['date']['format']?>">
                            <span id="custom_date_format"><?php echo date($date_format_settings['date']['format'])?></label></span>
                            <br>
                            <?php $checked = $date_format_settings['format'] == "none" ? 'checked' : ''?>
                            <input type="radio" name="date_format" value="none" id="format6" <?php echo $checked?>><label for="format6">None</label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo _('Time Format')?>:</th>
                        <td>
                            <?php $checked = $date_format_settings['time']['format'] == "g:i a" ? 'checked' : ''?>
                            <input type="radio" name="time_format" value="g:i a" id="time_format1" <?php echo $checked?>><label for="time_format1"><?php echo date('g:i a')?></label>
                            <br>
                            <?php $checked = $date_format_settings['time']['format'] == "g:i A" ? 'checked' : ''?>
                            <input type="radio" name="time_format" value="g:i A" id="time_format2" <?php echo $checked?>><label for="time_format2"><?php echo date('g:i A')?></label>
                            <br>
                            <?php $checked = $date_format_settings['time']['format'] == "H:i" ? 'checked' : ''?>
                            <input type="radio" name="time_format" value="H:i" id="time_format3" <?php echo $checked?>><label for="time_format3"><?php echo date('H:i')?></label>
                            <br>
                            <?php $checked = $date_format_settings['time']['custom'] ? 'checked' : ''?>
                            <input type="radio" name="time_format" value="custom" id="time_format4" <?php echo $checked?>><label for="time_format4">
                            <input type="text" name="custom_time_format" value="<?php echo $date_format_settings['time']['format'];?>">
                            <span id="custom_time_format"><?php echo date($date_format_settings['time']['format'])?></label></span>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td><input type="submit" class="button green" value="<?php echo _('Save') ?>"/></td>
                    </tr>
                </table>
            </form>

            <hr/>
            
            <h1><?php echo _('Default Application Theme') ?></h1>
            <form action="" method="post" enctype="multipart/form-data">
                <table class="profileTable">
                <tr>
                    <tr>
                        <th></th>
                        <td class="prompt">
                            <p><?php echo _('Upload default theme.') ?></p>
                        </td>
                    </tr>
                    <th><label for="theme_file"><?php echo _('App Theme') ?></label></th>
                    <td>
					<?php if ($default_theme = get_fx_option('server_default_app_theme', false)): ?>
                    
                        <p>Default theme uploaded</p>
                    	<input type="hidden" name="remove_default_theme"/>
                   		<input type="submit" class="button red" value="<?php echo _('Remove') ?>"/>
                                            
                    <?php else: ?>

                    	<input type="file" name="theme_file" id="theme_file"/>
                   		<input type="submit" class="button green" value="<?php echo _('Submit') ?>"/>
                        
                    <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                    </th>
                    <td>
                        
                    </td>
                </tr>  
                </table>
            </form>
		</div>
	</div>
</div>
