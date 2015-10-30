<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();
	
	$object_type_id = (int)$_GET['type'];
	$object_id = (int)$_GET['object'];
	$field_name = normalize_string($_GET['field']);
	$file_info = array('name'=>'', 'type'=>'', 'size'=>0);

	$args = '?type='.$object_type_id.'&object='.$object_id.'&field='.$field_name;
	
	$action_url = CONF_AJAX_URL.basename(__FILE__).$args;
	
	$object = get_object($object_type_id, $object_id);
	
	if (is_fx_error($object)) {
		die(_('Unable to get object').'. '.$object -> get_error_message());
	}
	
	$field_value = $object[$field_name];

	$field_data = get_custom_type_field($object_type_id, $field_name);

	if (is_fx_error($field_data)) {
		die(_('Unable to get field data').'. '.$field_data -> get_error_message());
	}

	$tmp_dir = CONF_UPLOADS_DIR.'/temp/';
	$tmp_url = CONF_UPLOADS_URL.'temp/';
	
	if (!is_dir($tmp_dir)) {
		mkdir($tmp_dir, 0777, true);
	}
	
	$obj_dir = CONF_UPLOADS_DIR.'/'.$object['object_type_id'].'/'.$object['object_id'].'/';
	$obj_url = CONF_UPLOADS_URL.$object['object_type_id'].'/'.$object['object_id'].'/';

	$tmp_res = new FX_Temp_Resource($object_type_id, $object_id);
	
	if(is_fx_error($tmp_res)) {
		die($tmp_res -> get_error_message());
	}

	$error = '';

	if(isset($_POST['revert_file'])) {
		$tmp_res -> remove($field_name);
	}

	if(isset($_POST['remove_file'])) {
		$tmp_res -> add($field_name, '');
		//if (is_fx_error($tmp_res -> $last_error)) die($tmp_res -> $last_error -> get_error_message());
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
		elseif ($field_data['type'] == 'image' && !$is_image) {
			$error = _('Image file required for this field');
		}
		else {
			move_uploaded_file($_FILES["tmp_file"]["tmp_name"], $tmp_dir.$_FILES["tmp_file"]["name"]);	
			if(!is_file($tmp_dir.$_FILES["tmp_file"]["name"])) $error = _('Unable to upload file');
			else $tmp_res -> add($field_name, $_FILES["tmp_file"]["name"]);
		}
	}

	$file_url = '';

	if($cur_res = $tmp_res -> get($field_name)) {
		if($cur_res['file_path']) {
			$file_info['name'] = $cur_res['field_value'];
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$file_info['type'] = finfo_file($finfo, $cur_res['file_path']);
			finfo_close($finfo);
			$file_info['size'] = filesize($cur_res['file_path']);
			$file_url = $cur_res['file_url'];
			$mime_type = array_shift(explode('/',$file_info['type']));
		}
	}
	elseif(is_file($obj_dir.$field_value)) {
		$file_info['name'] = $field_value;
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$file_info['type'] = finfo_file($finfo, $obj_dir.$field_value);
		finfo_close($finfo);
		$file_info['size'] = filesize($obj_dir.$field_value);
		$file_url = $obj_url.$field_value;
		$mime_type = array_shift(explode('/',$file_info['type']));
	}

	
	if(!$file_url) {
		if ($field_data['type'] == 'image') {
			$img_url = CONF_IMAGES_URL.'mime_image.png';
			$img_title = _('Empty image');
		}
		else {
			$img_url = CONF_IMAGES_URL.'mime_empty.png';
			$img_title = _('Empty file');
		}
	}
	else {
		switch($mime_type) {
			case 'image':
				if (!$cur_res) $img_url = $field_data['type'] == 'image' ? $obj_url.'thumb_'.$field_value : CONF_IMAGES_URL.'mime_image.png';
				else $img_url = $field_data['type'] == 'image' ? $file_url : CONF_IMAGES_URL.'mime_image.png';
				//$img_url = $field_value ? $obj_url.'thumb_'.$field_value : $file_url;
				$img_title = _('View image');
			break;
			case 'application':
				$img_url = CONF_IMAGES_URL.'mime_application.png';
				$img_title = _('Download file');
			break;
			case 'video':
				$img_url = CONF_IMAGES_URL.'mime_video.png';
				$img_title = _('Download file');
			break;
			case 'audio':
				$img_url = CONF_IMAGES_URL.'mime_audio.png';
				$img_title = _('Download file');
			break;
			default:
				$img_url = CONF_IMAGES_URL.'mime_text.png';
				$img_title = _('Download file');			
		}		
	}

?>

<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">
    <link href="<?php echo URL?>extensions/colourbox/colourbox.css" rel="stylesheet" type="text/css">

    <script type="text/javascript" src="<?php echo URL?>js/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo URL?>extensions/colourbox/colourbox.jquery.min.js"></script>

    <script language="javascript">
        var imgLoader;
        $(document).ready(function() {
            imgLoader = $('<img>');
            imgLoader.attr('src', '<?php echo URL ?>images/fx_loader.gif');
            imgLoader.attr('width', '64px');
            imgLoader.attr('height', '64px');

            function permalink_promt(url) {
                window.prompt("File permalink:", url);
            }

            <?php if($error) echo "alert('".$error."')"; ?>

            var s = parent.document.createElement('script')
            s.type = 'text/javascript';
            s.src = '<?php echo URL?>extensions/colourbox/colourbox.jquery.min.js'
            var style = parent.document.createElement('link')
            style.rel = "stylesheet"
            style.type = "text/css"
            style.href = "<?php echo URL?>extensions/colourbox/colourbox.css"
            parent.document.getElementsByTagName('HEAD')[0].appendChild(style);
            parent.document.getElementsByTagName('HEAD')[0].appendChild(s);
            parent.showColorBox = new Function("imageURL", "")
            $(function() {
                $('a.colorbox').click(function (event) {
                    event.preventDefault();
                    imageURL = $(this).attr("href");
                    window.parent.$.colorbox({
                        href: imageURL,
                        transition: 'elastic',
                        scalePhotos: true,
                        scrolling: false,
                        closeButton: false
                    })
                });
            })
        });
	</script>

    <style type="text/css">
		.upload-wrapper { font-size:10px; }
		
		.upload-wrapper IMG { 
			border: 4px solid white;
			-webkit-box-shadow: 0 4px 6px #AAAAAA;
			-moz-box-shadow: 0 4px 6px #AAAAAA;
			box-shadow: 0 4px 6px #AAAAAA;
			border-radius: 4px;
		 }

		.upload-wrapper .button {
			width:auto;
		}

		.upload-wrapper #info {  color:#FF0000; }
	</style>
</head>
<body>
	<div class="upload-wrapper">
        <div class="fx-image-preview">
        	<?php if($file_url): ?>
            <a class="colorbox" href="<?php echo $file_url; ?>" target="_blank">
            	<img id="file-preview" title="<?php echo $img_title ?>" width="64px" height="64px" src="<?php echo $img_url ?>">
            </a>           
            <?php else: ?>
            	<img id="file-preview" title="<?php echo $img_title ?>" width="64px" height="64px" src="<?php echo $img_url ?>">
            <?php endif; ?>
        </div>
        <div class="fx-upload-form">

            <form id="form-upload" method="post" action="<?php echo $action_url; ?>" enctype="multipart/form-data">
                <input id="upload_input" type="file" name="tmp_file" onChange="submit(); $('#file-preview').replaceWith(imgLoader);" on>
            </form>
            
            <div class="button small green" onClick="$('#upload_input').trigger('click');"><?php echo _('Upload') ?></div>
			
			<?php if($cur_res && $file_url): ?>
            
            <form id="form-revert" method="post" action="<?php echo $action_url; ?>">
                <input type="hidden" name="revert_file">
            </form>
            <div class="button small blue" onClick="$('#form-revert').submit()"><?php echo _('Revert') ?></div> 
                   
            <?php endif; ?>
            
			<?php if($file_url): ?>
            
				<?php if(!$cur_res): ?>
                <form id="form-remove" method="post" action="<?php echo $action_url; ?>">
                    <input type="hidden" name="remove_file">
                </form>
                <div class="button small red" onClick="$('#form-remove').submit()"><?php echo _('Remove') ?></div>
                <div class="button small" onClick="permalink_promt('<?php echo $file_url; ?>')"><?php echo _('Permalink') ?></div>
                <?php endif; ?>
            
            </br><strong>Name:</strong>&nbsp;<?php echo $file_info['name']; if($cur_res) echo '<font color="#FF0000"> - '.('Update Object to keep changes').'</font>'; ?>
            </br><strong>Type:</strong>&nbsp;<?php echo $file_info['type'] ?>
            </br><strong>Size:</strong>&nbsp;<?php echo $file_info['size'].' '._('bytes') ?>

            <?php endif; ?>

        </div>
	</div>
</body>
</html>