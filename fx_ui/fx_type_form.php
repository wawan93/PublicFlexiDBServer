<?php

function type_form($options = array())
{
    global $fx_error;
    //TODO: add read only mode
    //TODO: custom type action

    $IOResult = false;

    if (isset($_GET['object_type_id'])) {
        $object_type_id = $_GET['object_type_id'];
    }
	elseif (isset($_POST['object_type_id'])) {
        $object_type_id = $_POST['object_type_id'];
    }
	else {
        $object_type_id = 0;
    }

    if (isset($_POST['form_action'])) {
        switch ($_POST['form_action']) {
            case 'cancel':
                $IOResult = do_actions('fx_type_form_cancel', $object_type_id);
                break;
            case 'er_tool':
                $IOResult = do_actions('fx_type_form_er_tool', $object_type_id);
                break;
            case 'default_query':
                $IOResult = do_actions('fx_type_form_default_query', $object_type_id);
                unset($_POST);
                break;
            case 'default_form':
                $IOResult = do_actions('fx_type_form_default_form', $object_type_id);
                unset($_POST);
                break;
            case 'type_objects':
				$_SESSION['c_et'] = $object_type_id;
                fx_redirect(URL.'data_editor/data_objects');
                break;
			case 'remove':
            case 'delete':
                $IOResult = do_actions('fx_type_form_delete', $object_type_id);
                break;
            case 'delete_selected':		
                $IOResult = new FX_Error(__FUNCTION__, _('Bulk removing disabled for types'));
                break;
            case 'update':
                $IOResult = do_actions('fx_type_form_update', $_POST);
                break;
            default:
                $IOResult = new FX_Error(__FUNCTION__, _('Unknown object type action'));
        }
    }

    if (!$fx_error->is_empty) {
        $errors = $fx_error->get_error_messages();
        for ($i = 0; $i < count($errors); $i++) {
            echo '<div class="msg-error">ERROR: '.$errors[$i] .'</div>';
        }
    }

    if (is_fx_error($IOResult)) {
        $errors = $IOResult->get_error_messages();
        for ($i = 0; $i < count($errors); $i++) {
            echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
        }
    }

    if (!(int)$object_type_id) {
        echo '<p class="info">'._('Please select the object type or create another one').'</div>';
        return;
    }

    $type = get_type($object_type_id, 'custom');

    if (is_fx_error($type)) {
        echo '<div class="msg-error">'.$type->get_error_message().'</div>';
        return;
    }

    $object_number = (int)get_object_number($object_type_id);

    $type_name = isset($_POST['name']) ? $_POST['name'] : $type['name'];
    $type_display_name = isset($_POST['display_name']) ? $_POST['display_name'] : $type['display_name'];
    $type_name_format = isset($_POST['name_format']) ? $_POST['name_format'] : $type['name_format'];
    $type_revisions_number = isset($_POST['revisions_number']) ? $_POST['revisions_number'] : $type['revisions_number'];
    $type_description = isset($_POST['description']) ? $_POST['description'] : $type['description'];
    $type_prefix = isset($_POST['prefix']) ? $_POST['prefix'] : $type['prefix'];
    $type_fields = isset($_POST['fields']) ? $_POST['fields'] : $type['fields'];
	
	do_actions('type_form_before');
?>

    <form method="post" action="" id="typesForm">
        <input type="hidden" name="form_action" id="form_action" value="">
        <input type="hidden" name="object_type_id" value="<?php echo $object_type_id ?>">
        <table class="profileTable">
            <tr style="font-size:14px">
                <th><?php echo _('Type ID') ?>:</th>
                <td><b><?php echo $object_type_id ?></b></td>
            </tr>
            <tr>
                <th><?php echo _('Objects Number') ?>:</th>
                <td>
					<?php echo $object_number ?>
					<div class="hint" title="<?php echo _('The number of objects of this type') ?>"></div>
                </td>
            </tr>

            <tr>
                <th><label for="display_name"><?php echo _('Display Name') ?>:</label></th>
                <td><input type="text" maxlength="30" id="display_name" name="display_name" value="<?php echo $type_display_name ?>" size="20"/>
                </td>
            </tr>            
            <tr>
                <th><label for="description"><?php echo _('Description') ?>:</label></th>
                <td><textarea id="description" name="description" cols="40" rows="4"><?php echo $type_description ?></textarea></td>
            </tr>
            <tr>
                <th><?php echo _('Advanced Options') ?>:</th>
                <td><input type="button" class="button small toggle_advanced" data-toggle="type_advanced_options" value="<?php echo _('Show / Hide') ?>"></td>
            </tr>
            <tr>
                <td colspan="2">
                    <div id="type_advanced_options" class="advanced_options">
                        <table class="profileTable">
                            <tr>
                                <th><label for="name"><?php echo _('Name') ?>:</label></th>
                                <td>
                                    <input type="text" maxlength="30" id="name" name="name" value="<?php echo $type_name ?>" size="20"/>
                                    <div class="hint" title="<?php echo _('Leave it blank to generate from Display Name') ?>"></div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="name_format"><?php echo _('Name Format') ?>:</label></th>
                                <td>
                                    <input type="text" maxlength="128" id="name_format" name="name_format" value="<?php echo $type_name_format ?>" size="40"/>
                                    <div class="hint" title="<?php echo _('Example') ?> %first_name% %last_name%"></div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="revisions_number"><?php echo _('Revisions Number') ?>:</label></th>
                                <td>
                                    <input type="text" maxlength="2" id="revisions_number" name="revisions_number" value="<?php echo $type_revisions_number ?>" size="2"/>
                                    <div class="hint" title="<?php echo _('1 means without revision (only current revision)') ?>"></div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="prefix"><?php echo _('Object Prefix') ?>:</label></th>
                                <td>
                                    <input type="text" maxlength="16" id="prefix" name="prefix" value="<?php echo $type_prefix ?>" size="10"/>
                                    <div class="hint" title="<?php echo _('Will be prepend to object name automatically') ?>"></div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label>Roles Permissions:</label></th>
                <td><input type="button" class="button small blue" value="<?php echo _('Edit') ?>" onclick="showRolesPermissions(<?php echo $object_type_id?>)"></td>
            </tr>
            <tr>
                <th><label><?php echo _('Custom Fields') ?>:</label></th>
                <td>
                    <input type="button" class="button small green" id="addFieldToSomeType" onclick="addTypeField('#fieldsTable');" value="<?php echo _('Add') ?>">
                </td>
            </tr>
        </table>
        
        
        
		<hr>
        <div id="fieldsContainer">
        	<ul id="fieldsTable" class="editor">
				<?php
                    foreach ($type_fields as $field) {
                        $field['object_type_id'] = $type['object_type_id'];
                        $new_field = array_key_exists( $field['name'], $type['fields'] ) ? false : true;
                        fx_type_form_print_field($field, $new_field);
                    }
                ?>
            	<li id="li-no-fields" class="empty"<?php echo $type_fields ? ' style="display:none"' : ''; ?>>
				<?php echo _('No custom fields in the this type') ?>
                </li>
        	</ul>
		</div>
		<hr>
<!--        <input class="button green" type="button" value="<?php echo _('Update') ?>" onclick="$('#form_action').attr('value','update');submitTypeForm($('#typesForm'));">-->

		<?php do_actions('type_form_after'); ?>

		<input class="button green" type="button" value="<?php echo _('Update') ?>" onclick="if(<?php echo $object_number ?>){if(confirm('There is a <?php echo $object_number ?> object(s) of this type. Changes cannot be canceled. Are you sure you want to update current type?')){$('#form_action').attr('value','update'); submitTypeForm($('#typesForm'));}}else{$('#form_action').attr('value','update'); submitTypeForm($('#typesForm'));}">
        <input class="button blue" type="reset" value="<?php echo _('Reset') ?>"/>
        <input class="button red" type="button" value="<?php echo _('Delete') ?>" onclick="if(confirm('There is a <?php echo $object_number ?> object(s) of this type. Are you sure you want to delete current type?')){$('#form_action').attr('value','delete');submit();}" name="delete">
        <input class="button red" type="button" value="<?php echo _('Cancel') ?>" onclick="$('#form_action').attr('value','cancel');submit();"/>
       
        <?php if (!$type['system']): ?> 
                   
        &nbsp;<span style="font-size:20px; color:#CCC">|</span>&nbsp;
        <input class="button blue" type="button" value="<?php echo _('ER Tool') ?>" onclick="$('#form_action').attr('value','er_tool');submit();"/>
        <input class="button blue" type="button" value="<?php echo _('Query') ?>" onclick="$('#form_action').attr('value','default_query');submit();"/>
        <input class="button blue" type="button" value="<?php echo _('Form') ?>" onclick="$('#form_action').attr('value','default_form');submit();"/>
        <input class="button blue" type="button" value="<?php echo _('Objects') ?>" onclick="$('#form_action').attr('value','type_objects');submit();"/>

		<?php endif; ?>

    </form>
    <style>
        .accordion .nested select {
            padding: 5px;
            width: calc(100% - 200px);
        }
        .accordion .nested {
            width: calc(100% - 320px);
            display: inline-block;
        }
    </style>

    <script type="text/javascript">
        $(document).ready(function () {
            $('li.palette-item').each(function (index) {
                var $item = $(this),
                    dd = $item.find('.type_select_dropdown'),
                    $nested = $item.find('.nested');

                initAccordion($item);
                dd.bind('change',function(e){
                    if(dd.val() == 'enum') {
                        dd.css('width','200px');
                        $nested.show();
                        dd.removeAttr('name'); dd.removeClass('_field');
                        $nested.find('select').attr('name','fields[type]');
                        $nested.find('select').addClass('_field');
                    }
					else {
                        $nested.hide();
                        dd.css('width','calc(100% - 120px)');
                        dd.attr('name','fields[type]'); dd.addClass('_field');
                        $nested.find('select').removeAttr('name');
                        $nested.find('select').removeClass('_field');
                    }
                });
            });
        });
    </script>

<?php
}

function fx_type_form_cancel($object_type_id)
{
	fx_redirect(replace_url_param('object_type_id', ''));
}

function fx_type_form_delete($object_type_id)
{
    $IOResult = delete_type($object_type_id);
    if (!is_fx_error($IOResult)) {
		fx_redirect(replace_url_param('object_type_id', ''));
        $_SESSION['c_et'] = 0;
    }
    return $IOResult;
}

function fx_type_form_update($type_array)
{
    if (array_key_exists('fields', $type_array)) {
        foreach ((array)$type_array['fields'] as $sort_order => $field_options) {
            //if(!strlen((string)$field_options['name']))	return new FX_Error('update_type', 'One of the custom fields has an empty name.');
            $type_array['fields'][$sort_order]['sort_order'] = $sort_order;
            if (empty($type_array['fields'][$sort_order]['caption'])) {
                $type_array['fields'][$sort_order]['caption'] = $type_array['fields'][$sort_order]['name'];
            }
            if (empty($type_array['fields'][$sort_order]['description'])) {
                $type_array['fields'][$sort_order]['description'] = $type_array['fields'][$sort_order]['name'];
            }
        }
    }
	else {
        $type_array['fields'] = array();
    }

    $IOResult = update_type($type_array);
	
    if (!is_fx_error($IOResult)) {
		fx_redirect($_SERVER["REQUEST_URI"]);
    }

    return $IOResult;
}

function fx_type_form_er_tool($object_type_id)
{
	fx_redirect(URL.'design_editor/design_er');
}

function fx_type_form_default_query($object_type_id)
{
    $query_id = create_default_query($object_type_id);

    if (!is_fx_error($query_id)) {
		fx_redirect(URL.'component/component_query_editor?object_id='.$query_id);
    }

    return $query_id;
}

function fx_type_form_default_form($object_type_id)
{
    $form_id = create_default_form($object_type_id);
	
    if (!is_fx_error($form_id)) {
		fx_redirect(URL.'component/component_form_editor?object_id='.$form_id);
    }
	
    return $form_id;
}

add_action('fx_type_form_cancel', 'fx_type_form_cancel', 10, 1);
add_action('fx_type_form_delete', 'fx_type_form_delete', 10, 1);
add_action('fx_type_form_update', 'fx_type_form_update', 10, 1);
add_action('fx_type_form_er_tool', 'fx_type_form_er_tool', 10, 1);
add_action('fx_type_form_default_query', 'fx_type_form_default_query', 10, 1);
add_action('fx_type_form_default_form', 'fx_type_form_default_form', 10, 1);

function fx_type_form_print_field($field = array(), $new = true)
{
    global $fx_field_types;
	
	$is_enum = is_numeric($field['type']) ? true : false;

    ?>

    
    <li class="palette-item" style="background: <?php if ($field['mandatory']) { echo '#ddffdd'; }
	else{
        echo '#efefef';
    } ?>;
        background-image: none;">
        <span class="title">
		<?php if ($new): ?>
        
            <input class="_field" name="fields[name]" type="text" size="10" value="<?php echo $field['name'] ?>" placeholder="<?php echo _('Display name') ?>" style="padding: 3px; width: 80%;"/>
       
        <?php else: ?>
        
            <input type="button" class="button small blue" value="<?php echo _('Rename') ?>" onclick="rename_custom_field('<?php echo $field['object_type_id'] ?>',$(this).next().val())" title="<?php echo _('Change name of the field') ?>" style="width:50px;"/>
            <input id="ctrl-<?php echo $field['name'] ?>" type="hidden" class="_field" name="fields[name]" value="<?php echo $field['name'] ?>"/>
            <input id="disp-<?php echo $field['name'] ?>" style="border: none; background: transparent; font-weight: bold;" disabled value="<?php echo $field['name'] ?>">
        
        <?php endif; ?>
        </span>
        <img class="remove" onclick="removeTypeField(this)" src="<?php echo CONF_SITE_URL; ?>images/remove.png" alt="×">
        <div class="palette-item-data">
            <table class="accordion">
                <tr>
                    <td class="dt"><a href=""><?php echo _('Type') ?></a></td>
                    <td class="dd">
                        <div style="display: inline-block;">
                            <label class="button mandatory<?php if ($field['mandatory'] == '1') { echo ' green'; } ?>">
                                <input class="_field" name="fields[mandatory]" type="checkbox" style="display:none" <?php if ($field['mandatory']) { echo 'checked="checked"'; } ?>/>
                                <span><?php echo _('Mandatory') ?></span>
                            </label>
                            <select style="width: <?php echo !$is_enum ? 'calc(100% - 120px)' : '200px'; ?>; padding: 5px;" class="<?php if (!$is_enum) echo '_field '; ?> type_select_dropdown" <?php if(!$is_enum) echo 'name="fields[type]"'?>>
                                <?php show_select_options($fx_field_types,'','',$field['type']); ?>
                                <option value="enum" <?php if($is_enum) echo 'selected'?>><?php echo _('Enum') ?></option>
                            </select>
                            <div class="nested"<?php if(!$is_enum) echo ' style="display: none;"'?>>
                                <select class="select_enum <?php if($is_enum) echo '_field'?>" <?php if($is_enum) echo 'name="fields[type]"' ?> onchange="SetDefaults($(this))">
								<?php show_select_options(get_schema_enums($_SESSION['current_schema'], true), '', 'name', $field['type']); ?>
                                </select>
								<?php
								/*
								TODO: need to add new pop-up editor for enums
								<a href="javascript:add_enum(<?php echo $_SESSION['current_schema']?>,'0')" class="button green">Add</a>
                                <a href="#" onclick="view_enum($(this)); return false;" class="button blue">Edit</a><?php 
								
								*/
								?>
                            </div>
                        </div>
                    </td>

                    <td class="dt"><a href=""><?php echo _('Label') ?></a></td>
                    <td class="dd"><input class="_field" name="fields[caption]" type="text" value="<?php echo $field['caption'] ?>"/></td>

                    <td class="dt"><a href=""><?php echo _('Metric') ?></a></td>
                    <td class="dd">
                        <select class="_field" name="fields[metric]" style="width:20%; padding: 5px;" onchange="load_units(this)">
                            <option value="0">None</option>
                            <?php show_select_options(get_schema_metrics($_SESSION['current_schema'], true), '', 'name', $field['metric']); ?>
                        </select>
                        <select class="_field" name="fields[unit]" style="width:60%;" name="fields[unit]">
                        	<?php show_select_options(get_metric_units($field['metric']), '', 'name', $field['unit']); ?>
                        </select>
                    </td>

                    <td class="dt"><a href=""><?php echo _('Desc.') ?></a></td>
                    <td class="dd"><input class="_field wide" name="fields[description]" type="text" value="<?php echo $field['description'] ?>"/></td>
                    
                    <td class="dt"><a href=""><?php echo _('Length') ?></a></td>
                    <td class="dd"><input class="_field thin" name="fields[length]" type="text" value="<?php echo $field['length'] ? $field['length'] : '' ?>"/> </td>

                    <td class="dt"><a href=""><?php echo _('Default') ?></a></td>
                    <td class="dd">
                    <?php
                        if (is_numeric($field['type'])) {
                            echo '<select class="_field" name="fields[default_value]">'.show_select_options(get_enum_fields($field['type']), '', '', $field['default_value'], false).'</select>';
                        }
						else {
                            echo '<input class="_field" name="fields[default_value]" type="text" value="'.$field['default_value'].'" placeholder="'._('Default').'"/>';
                        }
                    ?>
                    </td>
                </tr>
            </table>
        </div>
    </li>
<?php
}

?>