<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$object_type_id = (int)$_GET['object_type_id'];
	$object_id = (int)$_GET['object_id'];
	$edit_url = replace_url_param('object_type_id', $object_type_id, replace_url_param('object_id', $object_id, $_SERVER['HTTP_REFERER']));

	$object = get_object($object_type_id, $object_id);
?>
<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">
 	<script language="javascript">
        window.onload = function() {
            var editObject = document.getElementById('editObject');
             if(typeof editObject !== 'undefined' && editObject != null) {
                editObject.onclick = function(){
                    window.parent.location = "<?php echo $edit_url ?>";
                };
            }
        }
	</script>
  </head>
<body class="popup">

<?php if(!$type_data = get_type($object_type_id, 'none')): ?>

	<p class="error">ERROR: Unable to get type data</p>

<?php elseif(is_fx_error($object)): ?>

	<p class="error">ERROR: <?php echo $object -> get_error_message() ?></p>

<?php else: ?>
    
    <form method="post" action="" name="objectsForm">
        <table class="profileTable">
        <tr>
            <th>Object ID:</th>
            <td><?php echo $object_type_id.'.'.$object['object_id'] ?></td>
        </tr>
        <tr>
            <th>Created:</th>
            <td><?php echo date('F j, Y \a\t g:i:s a', $object['created']) ?></td>
        </tr>
        <tr>
            <th>Modified:</th>
            <td><?php echo date('F j, Y \a\t g:i:s a', $object['modified']) ?></td>
        </tr>
        <tr>
            <th>Name:</th>
            <td><?php echo $object['name'] ?></td>
        </tr>
        <tr>
            <th>Display name:</th>
            <td><?php echo $object['display_name'] ?></td>
        </tr>
        <tr> 
            <th>Type:</th>
            <td><?php echo $type_data['display_name'] ?></td>
        </tr>

        <?php
 			$type_fields = get_type_fields($object_type_id, 'custom');
		
			if (is_fx_error($type_fields)) {
				echo '
				<tr>
					<th></th>
					<td colspan="2"><font color="red">ERROR: Unable to get fields list. '.$type_fields->get_error_message().'</font></td>
				</tr>';
			}
			elseif ($type_fields)
			{	
				foreach ($type_fields as $field => $field_options)
				{
					echo '<tr>';
					
 					$field_options['value'] = $object[$field];
					$field_options['object_id'] = $object['object_id'];
					
					if ($fc = get_field_control($field_options, true)) {
						
						if (strtolower($field_options['type']) == 'text') {
							if (json_decode($field_options['value']) !== NULL) {
								$fc['control'] = '<i title="'.$field_options['value'].'">JSON Object</i>';
							}
							elseif (is_array(unserialize($field_options['value']))) {
								$fc['control'] = '<i title="'.$field_options['value'].'">Serialized array</i>';
							}
						}
						
						echo '<th><nobr>'.$fc['label'].'</nobr></th>';
						echo '<td>'.$fc['control'].'</td>';
					}
					else {
						echo '<th><font color="red">'.$field.'</font></th>';
						echo '<td><font color="red">Unable to get field control</font></td>';
					}
					
 					echo '</tr>';
				}
			}
         ?>
        </table>
        <hr>
        <input class="button green" type="button" id="editObject" value="Edit"/>
        <input class="button red" type="button" id="close-dialog-window" value="Close"/>
    </form>
    
<?php endif; ?>

</body>
</html>