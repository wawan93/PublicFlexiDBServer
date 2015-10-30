<?php

	$IOResult = false;

	if (!empty($_FILES["logo_image"]))
	{
		$filesize = (int)($_FILES["logo_image"]["size"]);

		if ($filesize == 0) {
			$IOResult = 'Empty file';
		}
		elseif ($filesize > CONF_MAX_FILE_SIZE) {
			$IOResult = 'File is too big. Maximum file size = '.CONF_MAX_FILE_SIZE;
		}
		elseif (array_shift(explode('/',$_FILES["logo_image"]["type"])) != 'image') {
			$IOResult = 'Image file required for company logo';
		}
		else {
			$file_name = 'logo-'.time().'.jpg';
			img_resize($_FILES["logo_image"]["tmp_name"], CONF_UPLOADS_DIR.'/'.$file_name, 184, 80, 0xFFFFFF, 100, $_POST['scale_by']);
			
			if(!is_file(CONF_UPLOADS_DIR.'/'.$file_name)) {
				$IOResult = 'Unable to upload file';
			}
			else {
				update_fx_option('server_logo', CONF_UPLOADS_URL.$file_name);
				header('Location: '.$_SERVER['REQUEST_URI']);
			}
		}
	}

    define('CONF_IMG_THUMB_DEFAULT', 64);
    define('CONF_IMG_SMALL_DEFAULT', 128);
    define('CONF_IMG_MEDIUM_DEFAULT', 512);
    define('CONF_IMG_LARGE_DEFAULT', 768);
    define('CONF_IMG_ORIGINAL_DEFAULT', 1024);
    define('CONF_IMG_QUALITY_DEFAULT', 90);

    $default_image_settings = array(
        'img_original_max_width' => CONF_IMG_ORIGINAL_DEFAULT,
        'img_original_max_height' => CONF_IMG_ORIGINAL_DEFAULT,
        'img_thumb_height' => CONF_IMG_THUMB_DEFAULT,
        'img_thumb_width' => CONF_IMG_THUMB_DEFAULT,
        'img_small_height' => CONF_IMG_SMALL_DEFAULT,
        'img_small_width' => CONF_IMG_SMALL_DEFAULT,
        'img_medium_height' => CONF_IMG_MEDIUM_DEFAULT,
        'img_medium_width' => CONF_IMG_MEDIUM_DEFAULT,
        'img_large_height' => CONF_IMG_LARGE_DEFAULT,
        'img_large_width' => CONF_IMG_LARGE_DEFAULT,
        'img_original_quality' => CONF_IMG_QUALITY_DEFAULT,
        'img_thumb_quality' => CONF_IMG_QUALITY_DEFAULT,
        'img_small_quality' => CONF_IMG_QUALITY_DEFAULT,
        'img_medium_quality' => CONF_IMG_QUALITY_DEFAULT,
        'img_large_quality' => CONF_IMG_QUALITY_DEFAULT
    );
    if (isset($_POST['image_settings']) && $_POST['image_settings']) {
        unset($_POST['image_settings']);
        update_fx_option('image_settings', $_POST);
    }
    $image_settings = get_fx_option('image_settings', $default_image_settings);


	//========================================================================================

	if($IOResult)
	{
		echo '<div class="msg-error">'.$IOResult.'</div>';
	}
?>

<div class="rightcolumn">
    <div class="metabox">                
        <div class="header">
            <div class="icons settings"></div>
            <h1><?php echo _('Server Appearance') ?></h1>
        </div>
        <div class="content">
            <h1><?php echo _('Server Logo') ?></h1>
            <form action="" method="post" enctype="multipart/form-data">
                <table class="profileTable">
                <tr>
                    <tr>
                        <th></th>
                        <td class="prompt">
                            <p><?php echo _('Upload your server logo. It will appear in the left top corver.') ?></p>
                            <p><?php echo _('The image must be in proportion to 180x80 pixels, otherwise it will be truncated to fit into these dimensions.') ?></p>
                        </td>
                    </tr>
                    <th><label for="logo_image"><?php echo _('Image') ?></label></th>
                    <td>
                        <?php
							if ($img_src = get_fx_option('server_logo', false)) { 
								echo '<img src="'.$img_src.'?'.time().'" width="180px" height="80px"/><br>';
							}
                        ?>
                        <input type="file" name="logo_image" id="logo_image"/>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label><?php echo _('Scale by') ?></label>
                    </th>
                    <td>
                        <label for="scale_by_max">
                            <input type="radio" name="scale_by" id="scale_by_max" checked="checked" value="max"/>&nbsp;max
                        </label>
                        <label for="scale_by_min">
                            <input type="radio" name="scale_by" id="scale_by_min" value="min"/>&nbsp;min
                        </label>
                    <td>
                </tr>
                <tr>
                    <th>
                    </th>
                    <td>
                        <input type="submit" class="button green" value="<?php echo _('Submit') ?>"/>
                    </td>
                </tr>  
                </table>
            </form>
		</div>
	</div>
</div>








<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
            <div class="icons settings"></div>
            <h1><?php echo _('Image Settings') ?></h1>
        </div>
        <div class="content">
            <form action="" method="post">
                <input type="hidden" name="image_settings" value="1">
                <h2>Original Image</h2>
                <table class="profileTable">
                    <tr>
                        <th>
                            <label for="img_original_max_width">Max width</label>
                        </th>
                        <td>
                            <input type="number" name="img_original_max_width" id="img_original_max_width" min="0" value="<?php echo $image_settings['img_original_max_width'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_original_max_height">Max height</label>
                        </th>
                        <td>
                            <input type="number" name="img_original_max_height" id="img_original_max_height" min="0" value="<?php echo $image_settings['img_original_max_height'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_original_quality">Quality</label>
                        </th>
                        <td>
                            <input type="number" name="img_original_quality" id="img_original_quality" min="0" max="100" value="<?php echo $image_settings['img_original_quality'];?>">
                        </td>
                    </tr>
                </table>
                <hr>
                <h2>Thumbnail</h2>
                <table class="profileTable">
                    <tr>
                        <th>
                            <label for="img_thumb_width">Width</label>
                        </th>
                        <td>
                            <input type="number" name="img_thumb_width" id="img_thumb_width" min="0" value="<?php echo $image_settings['img_thumb_width'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_thumb_height">Height</label>
                        </th>
                        <td>
                            <input type="number" name="img_thumb_height" id="img_thumb_height" min="0" value="<?php echo $image_settings['img_thumb_height'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_thumb_quality">Quality</label>
                        </th>
                        <td>
                            <input type="number" name="img_thumb_quality" id="img_thumb_quality" min="0" max="100" value="<?php echo $image_settings['img_thumb_quality'];?>">
                        </td>
                    </tr>
                </table>
                <hr>
                <h2>Small Image</h2>
                <table class="profileTable">
                    <tr>
                        <th>
                            <label for="img_small_enabled">Enabled</th>
                        </th>
                        <td>
                            <input type="checkbox" name="img_small_enabled" id="img_small_enabled" <?php echo ($image_settings['img_small_enabled']) ? 'checked' : ''?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_small_width">Width</label>
                        </th>
                        <td>
                            <input type="number" name="img_small_width" id="img_small_width" min="0" value="<?php echo $image_settings['img_small_width'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_small_height">Height</label>
                        </th>
                        <td>
                            <input type="number" name="img_small_height" id="img_small_height" min="0" value="<?php echo $image_settings['img_small_height'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_small_quality">Quality</label>
                        </th>
                        <td>
                            <input type="number" name="img_small_quality" id="img_small_quality" min="0" max="100" value="<?php echo $image_settings['img_small_quality'];?>">
                        </td>
                    </tr>
                </table>
                <hr>
                <h2>Medium Image</h2>
                <table class="profileTable">
                    <tr>
                        <th>
                            <label for="img_medium_enabled">Enabled</th>
                        </th>
                        <td>
                            <input type="checkbox" name="img_medium_enabled" id="img_medium_enabled" <?php echo ($image_settings['img_medium_enabled']) ? 'checked' : ''?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_medium_width">Width</label>
                        </th>
                        <td>
                            <input type="number" name="img_medium_width" id="img_medium_width" min="0" value="<?php echo $image_settings['img_medium_width'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_medium_height">Height</label>
                        </th>
                        <td>
                            <input type="number" name="img_medium_height" id="img_medium_height" min="0" value="<?php echo $image_settings['img_medium_height'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_medium_quality">Quality</label>
                        </th>
                        <td>
                            <input type="number" name="img_medium_quality" id="img_medium_quality" min="0" max="100" value="<?php echo $image_settings['img_medium_quality'];?>">
                        </td>
                    </tr>
                </table>
                <hr>
                <h2>Large Image</h2>
                <table class="profileTable">
                    <tr>
                        <th>
                            <label for="img_large_enabled">Enabled</th>
                        </th>
                        <td>
                            <input type="checkbox" name="img_large_enabled" id="img_large_enabled" <?php echo ($image_settings['img_large_enabled']) ? 'checked' : ''?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_large_width">Width</label>
                        </th>
                        <td>
                            <input type="number" name="img_large_width" id="img_large_width" min="0" value="<?php echo $image_settings['img_large_width'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_large_height">Height</label>
                        </th>
                        <td>
                            <input type="number" name="img_large_height" id="img_large_height" min="0" value="<?php echo $image_settings['img_large_height'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="img_large_quality">Quality</label>
                        </th>
                        <td>
                            <input type="number" name="img_large_quality" id="img_large_quality" min="0" max="100" value="<?php echo $image_settings['img_large_quality'];?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                        </th>
                        <td>
                            <input type="submit" class="button green" value="Submit">
                        </td>
                    </tr>
                </table>
            </form>
		</div>
	</div>
</div>