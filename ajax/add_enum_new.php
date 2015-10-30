<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	if(isset($_GET['schema_id'])) $schema_id = (int)$_GET['schema_id'];
	else $schema_id = $_SESSION['current_schema'];
	
	$is_system = (int)$_GET['system'] ? true : false;
	
	$referer = isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER'];
	$redirect_to = false;
	$IOResult = false;

	if(isset($_POST['enum_action']) && $_POST['enum_action'] == 'add')
	{
		$enum_array = $_POST;

		$IOResult = add_enum_type($enum_array);

		if(!is_fx_error($IOResult))
		{
			if(is_url($referer)) $redirect_to = replace_url_param('enum_type_id', $IOResult, $referer);
			else $IOResult = new FX_Error('add_enum', 'Enum type successfully added but redirect url is invalid.');
		}
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
    <style type="text/css">

	</style>
</head>
<body class="popup">

	<?php
	
        if($IOResult)
        {
            if (is_fx_error($IOResult))
            {
                $errors = $IOResult->get_error_messages();
                for ($i=0; $i<count($errors); $i++) echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
            }
            else echo '<div class="msg-info">Enum type successfully added. Enum Type ID #'.$IOResult.'</div>';
        }	
	
	?>
    
    <form method="post" action="" name="objectsForm">
        <input type="hidden" name="enum_action" value="add">
        <input type="hidden" name="referer" value="<?php echo $referer ?>">
        <input type="hidden" name="schema_id" value="<?php echo $schema_id ?>"/>
        <input type="hidden" name="system" value="<?php echo $is_system ? '1' : '0' ?>">
        <table class="profileTable">
        <tr>
            <th><div class="star"></div><label for="name">Name:</label></th>
            <td><input type="text" maxlength="64" id="name" name="name" value="<?php echo $_POST['name'] ?>" size="20"/></td>
        </tr>
        <tr>
            <th></th>
            <td><div class="star"></div> - mandatory field</td>
        </tr>
        </table>
        <hr>
        <input class="button green" type="submit" value="Save"/>
        <input class="button blue" type="reset" value="Reset"/>
        <input class="button red" type="button" id="close-dialog-window" value="Close"/>
    </form>
</body>
</html>