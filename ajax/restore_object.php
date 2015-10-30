<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$object_type_id = (int)$_GET['object_type_id'];
	$object_id = (int)$_GET['object_id'];
	$time = $_GET['time'];
	$referer = isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER'];
	$redirect_to = false;
	$IOResult = false;

	if (isset($_POST['object_action']) && $_POST['object_action'] == 'restore') {
		$IOResult = rollback_object($object_type_id, $object_id, $time);
 		if(!is_fx_error($IOResult)) {
			if(is_url($referer)) $redirect_to = $referer;
			else $IOResult = new FX_Error('restore_object', 'Object successfully restored but redirect url is invalid.');
		}
	}
	else {
		$rollback_data = get_changes_to_rollback($object_type_id, $object_id, $time);
		$object = get_object($object_type_id, $object_id, true);
	}

?>
<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">

	<script language="javascript">
        <?php if($redirect_to) echo 'window.parent.location = "'.$redirect_to.'";'; ?>
	</script>
</head>
<body class="popup">

<?php if($redirect_to): ?>

	<div class="msg-info"><?php _('Object successfully restored. You will be redirected in a few seconds...') ?></div>

<?php elseif(is_fx_error($rollback_data)): ?>

    <div class="msg-info">ERROR: <?php echo $rollback_data->get_error_message() ?></div>
    
<?php elseif(is_fx_error($object)): ?>

    <div class="msg-info">ERROR: <?php echo $object->get_error_message() ?></div>

<?php else: ?>

	<?php
        if($IOResult) {
            if (is_fx_error($IOResult)) {
                $errors = $IOResult->get_error_messages();
                for ($i=0; $i<count($errors); $i++) echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
            }
            else {
				echo '<div class="msg-info">'._('Object successfully restored').'</div>';
			}
        }
	?>
    
    <form method="post" action="" name="objectsForm">
        <input type="hidden" name="object_action" value="restore">
        <input type="hidden" name="referer" value="<?php echo $referer ?>">
        <input type="hidden" name="object_type_id" value="<?php echo $object_type_id ?>"/>
        <input type="hidden" name="object_id" value="<?php echo $object_id ?>"/>
        <input type="hidden" name="time" value="<?php echo $time ?>"/>
        <table class="profileTable">
        <td colspan="2">
        	<i>Following fields were changed since <?php echo date('F j, Y \a\t g:i:s a', $time) ?>:</i>
        </td>

        <?php foreach($rollback_data as $field => $value): ?>
        
        <tr>
            <th><?php echo $object[$field]['caption'] ? $object[$field]['caption'] : $field ?>:</th>
            <td><?php echo $value ?></td>
        </tr>
                
        <?php endforeach; ?>
        </table>
    	<hr>
        <input class="button green" type="submit" value="Restore"/>
        <input class="button red" type="button" id="close-dialog-window" value="Cancel"/>
    </form>
    
<?php endif; ?>

</body>
</html>