<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$object_type_id = (int)$_GET['type'];
	$old_name = $_GET['field'];
	$close_window = false;
	$IOResult = false;

	if(isset($_POST['form_action']) && $_POST['form_action'] == 'rename')
	{
		$new_name = normalize_string($_POST['new_name']);
		$IOResult = change_custom_field_name($_POST['object_type_id'], $_POST['old_name'], $new_name);
		if(!is_fx_error($IOResult))
		{
			$IOResult = 'Field name successfuly changed.';
			$_POST['new_name'] = '';
			$close_window = true;
		}
	}

	$type_data = get_type($object_type_id, 'custom');
?>
<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">
	<script language="javascript">
        <?php if($close_window): ?>

        (function() {
            var oldName = '<?php echo $old_name ?>';
            var newName = '<?php echo $new_name ?>';

            window.parent.document.getElementById("ctrl-" + oldName).value = newName;
            window.parent.document.getElementById("disp-" + oldName).value = newName;

            window.parent.document.getElementById("ctrl-" + oldName).id = 'ctrl-' + newName;
            window.parent.document.getElementById("disp-" + oldName).id = 'disp-' + newName;
        })();

        <?php
        $old_name = $new_name;
        endif;
        ?>
	</script>
</head>
<body class="popup">

<?php if(!$type_data): ?>

	<div class="error">Set the valid object type.</div>

<?php elseif ($type_data['system']): ?>

	<div class="error">You can't change the name of field in system type.</div>
    
<?php elseif (!isset($type_data['fields'][$old_name])): ?>

	<div class="error">Specified field does not exist in this type</div>

<?php else: ?>

	<?php
        if($IOResult)
        {
            if (is_fx_error($IOResult)) {
                $errors = $IOResult->get_error_messages();
                for ($i=0; $i<count($errors); $i++) echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
            }
            else echo '<div class="msg-info">'._('Field name successfully changed').'</div>';
        }	
	?>
    
    <form method="post" action="">
        <input type="hidden" name="form_action" value="rename">
        <input type="hidden" name="object_type_id" value="<?php echo $object_type_id ?>"/>
        <input type="hidden" name="old_name" value="<?php echo $old_name ?>"/>
        <input type="hidden" name="need_to_close" value="<?php echo $close_window;  ?>"/>
        <table class="profileTable">
        <tr>
            <th><label>Object Type ID:</label></th>
            <td><?php echo $type_data['object_type_id']?></td>
        </tr>  
        <tr>
            <th><label>Type Name:</label></th>
            <td><?php echo $type_data['display_name']?></td>
        </tr>
        <tr>
            <th><label for="old_name">Old Field Name:</label></th>
            <td><?php echo $old_name?></td>
        </tr>
        <tr>
            <th><label for="new_name">New Field Name:</label></th>
            <td><input type="text" maxlength="64" name="new_name" value="<?php echo $_POST['new_name'] ?>" size="20"/></td>
        </tr>
        </table>
    
        <hr>
        <input class="button green" type="submit" value="Save"/>
        <input class="button blue" type="reset" value="Reset"/>
        <input class="button red" type="button" id="close-dialog-window" value="Close"/>
    </form>
    
<?php endif; ?>

</body>
</html>