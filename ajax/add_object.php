<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

    $object_type_id = (int)$_GET['object_type_id'];
    $set_id = isset($_GET['set_id']) ? (int)$_GET['set_id'] : intval($_SESSION['current_set']);
    $schema_id = isset($_GET['schema_id']) ? (int)$_GET['schema_id'] : $_SESSION['current_schema'];
    $referer = isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER'];
    $redirect_to = false;
    $IOResult = false;
	
    $mandatory_fields = $not_mandatory_fields = array();

    if (isset($_POST['object_action']) && $_POST['object_action'] == 'add')
    {
        $IOResult = add_object($_POST);
        
        if (!is_fx_error($IOResult))
        {
			if ($object_type_id == TYPE_APPLICATION) {

				$app_data_type_id = TYPE_APP_DATA;

				$app_data = array();
				$app_data['object_type_id'] = $app_data_type_id;
				$app_data['schema_id'] = $schema_id;
				$app_data['set_id'] = $set_id;
				$app_data['display_name'] = $IOResult.'.development';
				$app_data['version'] = 'development';
				$app_data['description'] = $_POST['description'];
				$app_data['is_development'] = 1;
				$app_data_id = add_object($app_data);

				if (!is_fx_error($app_data_id)) {
					add_link($object_type_id, $IOResult, $app_data_type_id, $app_data_id);
				}
			}
			
            if(is_url($referer)) {
				$redirect_to = replace_url_params(array('object_id' => $IOResult, 'object_type_id' => $object_type_id), $referer);
			}
            else {
				$IOResult = new FX_Error('add_object', _('Object successfully added but redirect url is invalid'));
			}
        }
    }

?>
<html>
<head>
    <meta http-equiv="content-language" content="en"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">
    <link href="<?php echo URL?>style/jquery-ui.custom.css" rel="stylesheet" type="text/css">


    <script type="text/javascript" src="<?php echo URL?>js/jquery.js"></script>
    <script type="text/javascript" src="<?php echo URL?>js/jquery-ui.custom.min.js"></script>

    <script type="text/javascript" src="<?php echo URL?>js/general.js"></script>
	<script type="text/javascript" src="<?php echo URL?>js/flexiweb.js"></script>
    <script language="javascript">
        <?php if($redirect_to) echo 'window.parent.location = "'.$redirect_to.'";'; ?>
    </script>
</head>

<body class="popup">
	<div id="iframe_inner_wrapper">
	<?php if (!$type_data = get_type($object_type_id,'none')): ?>
    
        <p class="error">Set the valid object type.</p>
    
    <?php else: ?>

    <?php
    
        if($IOResult) {
            if (is_fx_error($IOResult)) {
                $errors = $IOResult->get_error_messages();
                for ($i=0; $i<count($errors); $i++) {
					echo '<div class="msg-error">'.$errors[$i].'</div>';
				}
            }
            else {
				echo '<div class="msg-info">'._('Object successfully added').'. ID #'.$IOResult.'</div>';
			}
        }   
    
		if ($set_id === 0 && !$type_data['system']) {
			echo '<center><p><font color="red"><b><i>'._('Warning').': '._('You are going to add the object into the root data set').'</i></b></font></p></center>';
		}

    ?>
    
    <form method="post" action="" name="objectsForm">
        <input type="hidden" name="object_action" value="add">
        <input type="hidden" name="referer" value="<?php echo $referer ?>">
        <input type="hidden" name="object_type_id" value="<?php echo $object_type_id ?>"/>
        <input type="hidden" name="set_id" value="<?php echo $set_id ?>"/>
        <input type="hidden" name="schema_id" value="<?php echo $object_type_id != TYPE_DATA_SCHEMA ? $schema_id : 0 ?>"/>
        <table class="profileTable">
        <tr>
            <th><label>Type:</label></th>
            <td><?php echo $type_data['display_name']?></td>
        </tr>
        <tr>
            <th><div class="star"></div><label for="display_name">Display Name:</label></th>
            <?php if($type_data['name_format']): ?>
            <td>will be formed by template</td>
            <?php else: ?>
            <td>
                <input type="text" maxlength="64" id="display_name" name="display_name" value="<?php echo $_POST['display_name'] ?>" size="20"/>
                <div class="hint" title="Object name which will be shown to user."></div>
            </td>
            <?php endif;
            ?>
        </tr>

        <?php
			$type_fields = get_type_fields($object_type_id, 'custom');

			if (is_fx_error($type_fields)) {
				echo '
				<tr>
					<th></th>
					<td colspan="2"><font color="red">'._('Unable to get fields list').'. '.$type_fields->get_error_message().'</font></td>
				</tr>';
			}
			elseif ($type_fields) {
				foreach ($type_fields as $field => $field_options) {
					if ($field_options['mandatory']) {
						$mandatory_fields[$field] = $field_options;
					}
					else {
						$not_mandatory_fields[$field] = $field_options;
					}
				}
			}

			foreach ($mandatory_fields as $field => $field_options)
			{
				echo '<tr>';
	
				$field_options['value'] = $_POST[$field];

				if (($fc = do_actions('fx_type_custom_field_control', $object_type_id, $field_options)) === false) {
					continue;
				}
	
				if ($fc && $fc != $object_type_id) {
					echo '<th><nobr>'.$fc['label'].'</nobr></th>';
					echo '<td>'.$fc['control'].'</td>';
				}
				else {
					if ($field_options['type'] == 'image' || $field_options['type'] == 'file') {
						echo '<th><nobr>'.($field_options['caption'] ? $field_options['caption'] : $field).':</nobr></th>';
						echo '<td>'._('You can upload file to existing object').'</td>';
					}
					elseif ($fc = get_field_control($field_options)) {
						echo '<th><nobr>'.$fc['label'].'</nobr></th>';
						echo '<td>'.$fc['control'].'</td>';
					}
					else {
						echo '<th><font color="red">'.$field.'</font></th>';
						echo '<td><font color="red">'._('Unable to get field control').'</font></td>';
					}
				}
	
				echo '</tr>';
			}

			$started_vision = sizeof($not_mandatory_fields) == 0 ? 'none' : 'block';

			$nm_content = '';

			foreach ($not_mandatory_fields as $field => $field_options)
			{
				$field_options['value'] = $_POST[$field];
	
				if (($fc = do_actions('fx_type_custom_field_control', $object_type_id, $field_options)) === false) {
					continue;
				}

				$nm_content .= '<table class="profileTable">';
				$nm_content .= '<tr>';
	
				if ($fc && $fc != $object_type_id) {
					$nm_content .= '<th><nobr>'.$fc['label'].'</nobr></th>';
					$nm_content .= '<td>'.$fc['control'].'</td>';
				} 
				else {
					if($field_options['type'] == 'image' || $field_options['type'] == 'file') {
						$nm_content .= '<th><nobr>'.($field_options['caption'] ? $field_options['caption'] : $field).':</nobr></th>';
						$nm_content .= '<td>'._('You can upload file to existing object').'</td>';
					}
					elseif($fc = get_field_control($field_options)) {
						$nm_content .= '<th><nobr>'.$fc['label'].'</nobr></th>';
						$nm_content .= '<td>'.$fc['control'].'</td>';
					}
					else {
						$nm_content .= '<th><font color="red">'.$field.'</font></th>';
						$nm_content .= '<td><font color="red">'._('Unable to get field control').'</font></td>';
					}
				}
	
				$nm_content .= '</tr>';
				$nm_content .= '</table>';
			}

			if ($nm_content) {
				echo '
				<tr>
					<th></th>
					<td><input type="button" class="toggle_advanced button small" data-toggle="advanced_options" value="'._('Advanced options').'"></td>
				</tr>
				<tr>
					<td colspan="2">
						<div id="advanced_options" class="advanced_options" style="display: '.$started_vision.'">
						'.$nm_content.'
					</td>
				</tr>';
			}
        ?>

        <tr>
            <th></th>
            <td><div class="star"></div> - <?php echo _('mandatory field') ?> </td>
        </tr>
        </table>
		<div class="frame-footer">
            <hr>
            <input class="button green" type="submit" value="<?php echo _('Submit') ?>"/>
            <input class="button blue" type="reset" value="<?php echo _('Reset') ?>"/>
            <input class="button red" type="button" id="close-dialog-window" value="<?php echo _('Cancel') ?>"/>
        </div>
    </form>

	<?php endif; ?>
    </div>
</body>
</html>