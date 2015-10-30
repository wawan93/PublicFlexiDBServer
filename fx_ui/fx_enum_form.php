<?php

function enum_form($options = array())
{
	global $fx_error;
	//TODO: add read only mode
	//TODO: custom enum action

	$IOResult = false;

	$enum_type_id = isset($_REQUEST['enum_type_id']) ? $_REQUEST['enum_type_id'] : 0;

	if(isset($_POST['form_action']))
	{
		switch($_POST['form_action'])
		{
			case 'cancel':
				$IOResult = do_actions('fx_enum_form_cancel', $enum_type_id);
			break;
			case 'delete':
				$IOResult = do_actions('fx_enum_form_delete', $enum_type_id);
			break;
			case 'delete_selected':
				$IOResult = do_actions('fx_enum_form_delete_selected', $_POST['items']);
			break;
			case 'update':
				$IOResult = do_actions('fx_enum_form_update', $_POST);
			break;
			default:
				$IOResult = new FX_Error(__FUNCTION__, _('Unknown enum type action'));
		}
	}

	if (!$fx_error->is_empty) {
		$errors = $fx_error->get_error_messages();
		for ($i=0; $i<count($errors); $i++) echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
	}

	if (is_fx_error($IOResult)) {
		$errors = $IOResult->get_error_messages();
		for ($i=0; $i<count($errors); $i++) echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
	}
	
	if (!(int)$enum_type_id) {
		echo '<p class="info">'._('Please select the enum type or create another one').'</div>';
		return;
	}	

	$enum = get_enum_type($enum_type_id, true);

	if (is_fx_error($enum)) {
		echo '<div class="msg-error">'.$enum -> get_error_message().'</div>';
		return;		
	}	

	$enum_name = isset($_POST['name']) ? $_POST['name'] : $enum['name'];
	$enum_fields = isset($_POST['fields']) ? $_POST['fields'] : $enum['fields'];

	do_actions('enum_form_before');

	?>

    <script type="text/javascript">

        (function($){
            $(function() {
                colorPickerInit()
                $('.minicolors').on('mousedown', function() {
                    $(this).closest('li').sortable('disable');
                })
                $('.minicolors').on('mouseup', function() {
                    $(this).closest('li').sortable( 'enable' )
                })
            })
        })(jQuery)
    </script>
    <form method="post" action="" id="enumForm">
        <input type="hidden" name="form_action" id="form_action" value="">
        <input type="hidden" name="enum_type_id" value="<?php echo $enum_type_id?>">
        <table class="profileTable">
            <tr style="font-size:14px">
                <th><?php echo _('Enum ID') ?>:</th>
                <td><b><?php echo $enum_type_id?></b></td>
            </tr>
            <tr>
                <th><label for="name"><?php echo _('Enum Name') ?>:</label></th>
                <td><input type="text" maxlength="30" id="name" name="name" value="<?php echo $enum_name ?>" size="20"/></td>
            </tr>
            <tr>
                <th><label><?php echo _('Fields') ?>:</label></th>
                <td>
                	<input type="button" class="button small green" id="addFieldToSomeType" onclick="addEnumField('#fieldsTable');" value="<?php echo _('Add') ?>">
                </td>
            </tr>
        </table>

        <div id="fieldsContainer">
            <ul id="fieldsTable" class="editor" style="width: 480px">
            <?php
				foreach ($enum_fields as $value => $field) {
					fx_enum_form_print_field($field['enum_field_id'], $value, $field['label'], $field['color'], $field['opacity']);
				}
			?>
            </ul>
            <hr>
        </div>
        
        <?php do_actions('enum_form_after'); ?>
        
		<div class="frame-footer">
            <input class="button green" type="button" value="<?php echo _('Update') ?>" onclick="$('#form_action').attr('value','update');submitTypeForm($('#enumForm'));">
            <input class="button blue" type="reset" value="<?php echo _('Reset') ?>"/>
            <input class="button red" type="button" value="<?php echo _('Delete') ?>" onclick="if(confirm('<?php echo _('Are you sure you want to delete current enum type?') ?>')){$('#form_action').attr('value','delete');submit();}" name="delete">
            <input class="button red" type="button" value="<?php echo _('Cancel') ?>" onclick="$('#form_action').attr('value','cancel');submit();"/>
        </div>
    </form>

	<?php
}

function fx_enum_form_cancel($enum_type_id)
{
	fx_redirect(replace_url_param('enum_type_id', ''));
}

function fx_enum_form_delete($enum_type_id)
{
	$IOResult = delete_enum_type($enum_type_id);
	if(!is_fx_error($IOResult)) {
		fx_redirect(replace_url_param('enum_type_id', ''));
	}
	return $IOResult;
}

function fx_enum_form_delete_selected($enums = array())
{
	$enums = (array)explode(',', $enums);
	$count = count($enums);

	if (!$count) {
		return new FX_Error(__FUNCTION__, _('Nothing to delete'));
	}
	
	foreach ($enums as $enum_id) {
		$res = delete_enum_type($enum_id);
		if (!is_fx_error($res)) {
			$count--;
		}
	}

	if(!$count) {
		fx_redirect(replace_url_param('enum_type_id', ''));
	}

	return new FX_Error(__FUNCTION__, $count.' '._('enum(s) was not deleted'));
}

function fx_enum_form_update($enum_array)
{
	$sort_order = 0;
	$new_fields = array();

	foreach($enum_array['fields'] as $field) {
		if (!strlen((string)$field['label'])) {
			return new FX_Error(__FUNCTION__, _('Please set all labels'));
		}
		if (!strlen((string)$field['value'])) {
			return new FX_Error(__FUNCTION__, _('Please set all values'));
		}
		if (array_key_exists($field['value'], $new_fields)) {
			return new FX_Error(__FUNCTION__, _('All values must be unique'));
		}

		$new_fields[$field['value']] = array('label' => $field['label'], 'color' => $field['color'], 'opacity' => $field['opacity']);
	}

	$enum_array['fields'] = $new_fields;

	$IOResult = update_enum_type($enum_array);
	
	if(!is_fx_error($IOResult)) {
		fx_redirect(replace_url_param('object_type_id', $object_type_id));
	}
	
	return $IOResult;
}

add_action('fx_enum_form_cancel', 'fx_enum_form_cancel', 10, 1);
add_action('fx_enum_form_delete', 'fx_enum_form_delete', 10, 1);
add_action('fx_enum_form_delete_selected', 'fx_enum_form_delete_selected', 10, 1);
add_action('fx_enum_form_update', 'fx_enum_form_update', 10, 1);

function fx_enum_form_print_field($enum_field_id=0, $value = '', $label = '', $color = '#ffffff', $opacity = '1')
{
	?>
    <li class="palette-item" style="overflow: visible ; z-index: inherit">
    	<input name="fields[enum_field_id]" class="_field" type="hidden" value="<?php echo $enum_field_id ?>"/>
        <input name="fields[value]" style="width: auto" class="_field" type="text" value="<?php echo $value ?>" placeholder="<?php echo _('Value') ?>"/>
        <input name="fields[label]" style="width: auto" class="_field" type="text" value="<?php echo $label ?>" placeholder="<?php echo _('Label') ?>"/>
        <input name="fields[color]" style="width: 65px" class="_field colorpicker" type="text" data-opacity="<?php echo $opacity ?>" value="<?php echo $color ?>" placeholder="<?php echo _('Color') ?>">
        <input name="fields[opacity]" type="hidden" class="_field" value="<?php echo $opacity ?>">
        <img class="remove" onclick="$(this).parents('li').remove()" src="<?php echo CONF_SITE_URL; ?>images/remove.png" alt="×">
    </li>    
    <?php
}