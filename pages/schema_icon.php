<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}
	
	$schema_object = get_object(TYPE_DATA_SCHEMA, $_SESSION['current_schema']);
	
	if (is_fx_error($schema_object)) {
		fx_show_error_metabox($schema_object->get_error_message());
		return;
	}
	
	global $fx_error;
	$fx_api = new FX_API_Client();

	/*===========================================================================================*/

	if (isset($_POST['submit_local_icon']))
	{
		if ($schema_object['icon']) {
			$img_path = CONF_UPLOADS_DIR.'/'.TYPE_DATA_SCHEMA.'/'.$schema_object['object_id'].'/'.$schema_object['icon'];
			
			$options = array('channel_id' => $schema_object['channel'] ,'image'=> '@'.$img_path);
			
			$result = $fx_api -> execRequest('channel/image', 'post', $options);
			
			if (is_fx_error($result)) {
				$fx_error->add('submit_local_icon', $result->get_error_message());
			}
		}
		else {
			$fx_error->add('submit_local_icon', _('Current Data Schema has no icon'));
		}
	}
	
	/*===========================================================================================*/
	
	if (isset($_POST['set_default_icon']))
	{
		$result = $fx_api -> execRequest('channel/image', 'delete', array('channel_id' => $schema_object['channel']));
		if (is_fx_error($result)) {
			$fx_error->add('submit_local_icon', $result->get_error_message());
		}
	}	
	
	/*===========================================================================================*/
		
	function _get_current_channel_image($schema_object)
	{
		$schema_id = $schema_object['object_id'];
		$channel_id = get_schema_channel($schema_id);

		if (!$channel_id) {
			return '
			<p>&nbsp;</p>
			<div class="info">'._('Current Data Schema has no Channel').'</div>
			<p>&nbsp;</p>
			<a class="button green" href="'.URL.'schema_admin/schema_channel'.'">'._('Create Channel').'</a>';
		}

		$fx_api = new FX_API_Client();

		$channel_object = $fx_api -> execRequest('channel/object', 'GET', 'channel_id='.$channel_id);		

		if (is_fx_error($channel_object)) {
			return $channel_object;
		}
		
		$out = '
		<div class="upload-wrapper">
			<div class="fx-image-preview">
				<img class="channel_image" src="'.$channel_object['icon_medium'].'?'.time().'">
			</div>
			<div class="fx-upload-form">
				<form action="" method="post" id="form-submit-local"><input type="hidden" name="submit_local_icon"></form>
				<div class="button green" onClick="$(\'#form-submit-local\').submit()">'._('Submit Local').'</div>
				<br>
				<form action="" method="post" id="form-set-default"><input type="hidden" name="set_default_icon"></form>
				<div class="button red" onClick="$(\'#form-set-default\').submit()">'._('Set Default').'</div>
				<br>
				<input class="colorpicker" type="text" value="#2e2e2e">
			</div>
			<div style="clear: both;"></div>
		</div>
		<p>&nbsp;</p>';
		
		return $out;
	}
	

	function _ctrl_schema_image($schema_object)
	{
		global $fx_error;
		
		if (is_fx_error($fx_error)) {
			$errors = $fx_error->get_error_messages();
			for ($i=0; $i<count($errors); $i++) echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
		}
		
		$schema_id = $schema_object['object_id'];

		$out = '
		<p>&nbsp;</p>
		<h2>'._('Local Image').':</h2>
		<hr>
		<iframe class="upload-schema-icon" frameborder="0" vspace="0" hspace="0" scrolling="no" src="'.CONF_AJAX_URL.'upload_schema_img.php?schema_id='.$schema_id.'"></iframe>	
		<h2>'._('Channel/Application Image').':</h2>
		<hr>';
		
		$channel_image = _get_current_channel_image($schema_object);

		if (is_fx_error($channel_image)) {
			$out .= '<font color="#CC0000" size="+2">'.$channel_image->get_error_message().'</font>';
		}
		else {
			$out .= $channel_image;
		}

		echo $out;
	}
	
	$mb_data = array('header' => array('suffix' => ' - '.$schema_object['display_name']),
					 'body' => array('function' => '_ctrl_schema_image', 'args' => array('schema_object'=>$schema_object)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);
?>


<script type="text/javascript">
	$('.colorpicker').minicolors({
		opacity: false,
		changeDelay: 100,
		change: function(hex) {
			$('.channel_image').css('border-color', hex);
		}
	});
</script>