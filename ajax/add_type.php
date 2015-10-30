<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$schema_id = (int)$_GET['schema_id'] ? (int)$_GET['schema_id'] : $_SESSION['current_schema'];
	$is_system = (int)$_GET['system'] ? true : false;
	$referer = isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER'];
	$redirect_to = false;
	$IOResult = false;

	if(isset($_POST['type_action']) && $_POST['type_action'] == 'add')
	{
		$type_array = $_POST;
		$IOResult = add_type($type_array);
		
		if(!is_fx_error($IOResult)) {
			if(is_url($referer)) $redirect_to = replace_url_param('object_type_id', $IOResult, $referer);
			else $IOResult = new FX_Error('add_type', _('Object type successfully added but redirect url is invalid'));
		}
	}
?>
<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="<?php echo URL?>js/jquery.js"></script>
    <script type="text/javascript" src="<?php echo URL?>js/general.js"></script>
	<script type="text/javascript" src="<?php echo URL?>js/flexiweb.js"></script>
	<script language="javascript">
        <?php if($redirect_to) echo 'window.parent.location = "'.$redirect_to.'";'; ?>
	</script>
</head>
<body class="popup">
	<div id="iframe_inner_wrapper">
		<?php
            if (is_fx_error($IOResult)) {
                $errors = $IOResult->get_error_messages();
                for ($i=0; $i<count($errors); $i++) {
                    echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
                }
            }
        ?>
        <form method="post" action="" name="objectsForm">
            <input type="hidden" name="type_action" value="add">
            <input type="hidden" name="referer" value="<?php echo $referer ?>">
            <input type="hidden" name="schema_id" value="<?php echo $object_type_id != TYPE_DATA_SCHEMA ? $schema_id : 0 ?>"/>
            <input type="hidden" name="system" value="<?php echo $is_system ? '1' : '0' ?>">
            <table class="profileTable">
            <tr>
                <th><div class="star"></div><label for="display_name"><?php echo _('Display Name') ?>:</label></th>
                <td><input type="text" maxlength="64" id="display_name" name="display_name" value="<?php echo $_POST['display_name'] ?>" size="20"/></td>
            </tr>        
            <tr>
                <th><label for="description"><?php echo _('Description') ?>:</label></th>
                <td><textarea id="description" name="description" cols="40" rows="4"><?php echo $_POST['description'] ?></textarea></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="button" class="button small toggle_advanced" data-toggle="add_type_options" value="<?php echo _('Advanced options') ?>"></td>
            </tr>
            <tr>
                <td colspan="2">
                    <div id="add_type_options" class="advanced_options">
                        <table class="profileTable">
                        <tr>
                            <th><label for="name"><?php echo _('Name') ?>:</label></th>
                            <td><input type="text" maxlength="64" id="name" name="name" value="<?php echo $_POST['name'] ?>" size="20"/></td>
                        </tr>
                        <tr>
                            <th><label for="name_format"><?php echo _('Name Format') ?>:</label></th>
                            <td>
                                <input type="text" maxlength="128" id="name_format" name="name_format" value="<?php echo $_POST['name_format'] ?>" size="20"/>
                                <div class="hint" title="<?php echo _('Example') ?>: %first_name% %last_name%"></div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="revisions_number"><?php echo _('Revisions Number') ?>:</label></th>
                            <td><input type="text" maxlength="2" id="revisions_number" name="revisions_number" value="<?php echo $_POST['revisions_number'] ?>" size="2"/>
                                <div class="hint" title="<?php echo _('Revisions Number') ?>"></div>
                            </td>
                        </tr>               
                        <tr>
                            <th><label for="prefix"><?php echo _('Object prefix') ?>:</label></th>
                            <td><input type="text" maxlength="16" id="prefix" name="prefix" value="<?php echo $_POST['prefix'] ?>" size="5"/>
                                <div class="hint" title="<?php echo _('Will be prepend to object name automatically') ?>"></div>
                            </td>
                        </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <th></th>
                <td><div class="star"></div> - <?php echo _('mandatory field') ?></td>
            </tr>
            </table>
            <div class="frame-footer">
                <hr>    
                <input class="button green" type="submit" value="<?php echo _('Save') ?>"/>
                <input class="button blue" type="reset" value="<?php echo _('Reset') ?>"/>
                <input class="button red" type="button" id="close-dialog-window" value="<?php echo _('Close') ?>"/>
            </div>
        </form>
	</div>
</body>
</html>