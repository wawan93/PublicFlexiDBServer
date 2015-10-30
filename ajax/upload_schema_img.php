<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();
	
	$schema_id = (int)$_GET['schema_id'];
	$field_name = 'icon';

	if (isset($_POST['submit_schema'])) {
		$result = update_object(array('object_type_id'=>TYPE_DATA_SCHEMA, 'object_id'=>$schema_id));
		if (is_fx_error($result)) {
			$error = $result->get_error_message();
		}
	}

	$file_info = array('name'=>'', 'type'=>'', 'size'=>0);

	$args = '?schema_id='.$schema_id;
	$action_url = CONF_AJAX_URL.basename(__FILE__).$args;
	
	$object = get_object(TYPE_DATA_SCHEMA, $schema_id);
	
	if (is_fx_error($object)) {
		die($object -> get_error_message());
	}
	
	$field_value = $object[$field_name];

	$field_data = get_custom_type_field(TYPE_DATA_SCHEMA, $field_name);

	if (is_fx_error($field_data)) {
		die($field_data -> get_error_message());
	}

	$tmp_dir = CONF_UPLOADS_DIR.'/temp/';
	$tmp_url = CONF_UPLOADS_URL.'temp/';

	$obj_dir = CONF_UPLOADS_DIR.'/'.TYPE_DATA_SCHEMA.'/'.$schema_id.'/';
	$obj_url = CONF_UPLOADS_URL.TYPE_DATA_SCHEMA.'/'.$schema_id.'/';

	$tmp_res = new FX_Temp_Resource(TYPE_DATA_SCHEMA, $schema_id);
	
	if(is_fx_error($tmp_res)) {
		die($tmp_res -> get_error_message());
	}

	$error = '';

	if (isset($_POST['submit_schema'])) {
		update_object(array('object_type_id'=>TYPE_DATA_SCHEMA, 'object_id'=>$schema_id));
	}

	if(isset($_POST['revert_file'])) {
		$tmp_res -> remove($field_name);
	}

	if(isset($_POST['remove_file'])) {
		$tmp_res -> add($field_name, '');
	}

	if (!empty($_FILES["tmp_file"]["name"])) {
		$filesize = (int)($_FILES["tmp_file"]["size"]);
		$is_image = array_shift(explode('/',$_FILES["tmp_file"]['type'])) == 'image' ? true : false;

		if ($filesize == 0) {
			$error = 'Empty file';
		}
		elseif ($filesize > CONF_MAX_FILE_SIZE) {
			$error = _('File is too big. Maximum file size').' = '.CONF_MAX_FILE_SIZE;
		}
		elseif (!$is_image) {
			$error = _('Image file required for this field');
		}
		else {
			//move_uploaded_file($_FILES["tmp_file"]["tmp_name"], $tmp_dir.$_FILES["tmp_file"]["name"]);	
			img_resize($_FILES["tmp_file"]["tmp_name"], 
					   $tmp_dir.$_FILES["tmp_file"]["name"], 
					   CONF_IMG_SCHEMA_ICON, 
					   CONF_IMG_SCHEMA_ICON, 
					   0xFFFFFF, 
					   100, 
					   'max');
					   
			if(!is_file($tmp_dir.$_FILES["tmp_file"]["name"])) {
				$error = _('Unable to upload file');
			}
			else {
				$tmp_res -> add($field_name, $_FILES["tmp_file"]["name"]);
			}
		}
	}

	$img_type = 'empty';

	if ($cur_res = $tmp_res -> get($field_name)) {
		if ($cur_res['field_value']) {
			$img_url = $cur_res['file_url'];
		}
		else {
			$img_url = CONF_IMAGES_URL.'mime_image.png';
		}
		$img_type = 'temp';
	}
	elseif (is_file($obj_dir.$field_value)) {
		$img_url = $obj_url.$field_value;
		$img_type = 'perm';
	}
	else {
		$img_url = CONF_IMAGES_URL.'mime_image.png';
	}
?>

<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="<?php echo URL?>js/jquery.min.js"></script>
	<script language="javascript">
        var imgLoader  = $('<img>');
        imgLoader.attr('src', '<?php echo URL ?>images/fx_loader.gif');
        imgLoader.attr('width', '64px');
        imgLoader.attr('height', '64px');

		function permalink_promt(url) {
		    window.prompt("File permalink:", url);
		}
		
		<?php if($error) echo "alert('".$error."')"; ?>

	</script>
    <style type="text/css">
		BODY {
			background-color:#FFF;
			padding:0;
			margin:0;
		}
	</style>
</head>
<body>
	<div class="upload-wrapper">
        <div class="fx-image-preview">
        	<img class="channel_image" id="file-preview" width="160px" height="160px" src="<?php echo $img_url.'?'.time() ?>">
        </div>
        <div class="fx-upload-form">

            <form id="form-upload" method="post" action="<?php echo $action_url; ?>" enctype="multipart/form-data">
                <input id="upload_input" type="file" name="tmp_file" onChange="submit(); $('#file-preview').replaceWith(imgLoader);" on>
            </form>
            
            <div class="button green" onClick="$('#upload_input').trigger('click');"><?php echo _('Select Image') ?></div></br>
			
			<?php if ($img_type == 'temp'): ?>
            
            <form id="form-revert" method="post" action="<?php echo $action_url; ?>">
                <input type="hidden" name="revert_file">
            </form>
            <form id="form-submit" method="post" action="<?php echo $action_url; ?>">
                <input type="hidden" name="submit_schema">
            </form>
            <div class="button blue" onClick="$('#form-revert').submit()"><?php echo _('Revert') ?></div></br>
            <div class="button green" onClick="$('#form-submit').submit()"><?php echo _('Submit') ?></div>
            
            <?php endif; ?>
            
			<?php if($img_type == 'perm'): ?>
            
				<?php if(!$cur_res): ?>
                <form id="form-remove" method="post" action="<?php echo $action_url; ?>">
                    <input type="hidden" name="remove_file">
                </form>
                <div class="button red" onClick="$('#form-remove').submit()"><?php echo _('Remove') ?></div></br>
                <?php endif; ?>

            <?php endif; ?>           
        </div>
	</div>
</body>
</html>