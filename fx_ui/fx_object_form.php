<?php

// Object form parameters:
// object_id = Object ID to show
// object_type_id = Object type ID to show
// fields = array(object_id|name|display_name|type|parent|description|image)
// buttons = array(update|child|replicate|reset|back)
// mode = view|edit
// custom_fields = array(name,label,control)

function object_form($options = array())
{
	global $fx_error;

	//TODO: custom object actions
	//TODO: hooks for custom object actions	

	if (array_key_exists('object_type_id', $options) && $options['object_type_id']) {
		$object_type_id = $options['object_type_id'];
	}
	elseif (isset($_REQUEST['object_type_id'])) {
		$object_type_id = $_REQUEST['object_type_id'];
	}
	else {
		$object_type_id = 0;
	}

	if (array_key_exists('object_id', $options) && $options['object_id']) {
		$object_id = $options['object_id'];
	}
	elseif (isset($_REQUEST['object_id'])) {
		$object_id = $_REQUEST['object_id'];
	}
	else {
		$object_id = 0;
	}

	if (isset($_REQUEST['object_action'])) {
		$object_action = $_REQUEST['object_action'];
	}
	else {
		$object_action = false;
	}

	if (isset($_REQUEST['target_object'])) {
		list($object_type_2_id, $object_2_id) = explode('.', $_REQUEST['target_object']);
	}
	else {
		$object_type_2_id = 0;
		$object_2_id = 0;
	}

	$custom_fields = array_key_exists('custom_fields', $options) ? $options['custom_fields'] : array();

	$IOResult = false;

	if(isset($_POST['object_action'])) {
		switch($_POST['object_action']) {
			case 'cancel':
				$IOResult = do_actions('fx_object_form_cancel', $object_type_id, $object_id);
			break;
			case 'link':
				$IOResult = do_actions('fx_object_form_link', $object_type_id, $object_id, $object_type_2_id, $object_2_id);	
			break;
			case 'unlink':
				$IOResult = do_actions('fx_object_form_unlink', $object_type_id, $object_id, $object_type_2_id, $object_2_id);
			break;
			case 'delete':
			case 'remove':
				$IOResult = do_actions('fx_object_form_delete', $object_type_id, $object_id);
			break;
			case 'delete_selected':
				$IOResult = do_actions('fx_object_form_delete_selected', $object_type_id, $_POST['items']);
			break;
			case 'update':
				$IOResult = do_actions('fx_object_form_update', $_POST);
			break;
			case 'replicate':
				$IOResult = do_actions('fx_object_form_replicate', $object_type_id, $object_id);
			break;
			case 'restore':
				$IOResult = do_actions('fx_object_form_restore', $object_type_id, $object_id, $_POST['revision']);
			break;
			default:
				$IOResult = new FX_Error(__FUNCTION__, _('Unknown object action'));
		}
	}

	if (!$fx_error->is_empty) {
		$errors = $fx_error->get_error_messages();
		for ($i=0; $i<count($errors); $i++) {
			echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
		}
	}

	if (is_fx_error($IOResult)) {
		$errors = $IOResult->get_error_messages();
		for ($i=0; $i<count($errors); $i++) {
			echo '<div class="msg-error">ERROR: '.$errors[$i].'</div>';
		}
	}

	if(!$object_id || !$object_type_id) {
		echo '<p class="info">'._('Please select the object').'</div>';
		return;
	}

	if(!object_exists($object_type_id, $object_id)) {
		echo '<p class="error">'._('Object with the specified ID does not exist').'</div>';
		return;
	}
	
	$object = get_object($object_type_id, $object_id);
	
	if (is_fx_error($object)) {
		echo '<div class="msg-error">'.$object->get_error_message().'</div>';
		return;		
	}

	if(!$type_data = get_type($object_type_id, 'none')) {
		echo '<div class="msg-error">'._('The object is associated with the invalid type').'</div>';
		return;
	}		

	$disabled = array_key_exists('mode', $options) && $options['mode'] == 'view' ? 'disabled="disabled"' : '';
	$base_fields_only = array_key_exists('base_only', $options) && $options['base_only'] ? true : false;
	$custom_fields = $options['custom_fields'] ? $options['custom_fields'] : array();
	$revisions = get_revisions_list($object_type_id, $object_id);
	
	$strongly_linked_count = count(get_object_strong_links($object_type_id, $object_id, true));
	$strongly_linked_msg = $strongly_linked_count ? ' '._('and strongly linked').' ('.$strongly_linked_count.') '._('objects') : '';
	
	do_actions('object_form_before');
?>
    <script type="text/javascript">
        $(function() {
            $('.toggle-button').on('click', function() {
                var el = $(this).data('toggle')
                $('#'+el).toggle(100)
            })
        })
    </script>
	<form method="post" action="" name="objectsForm"  enctype="multipart/form-data">
    	<input type="hidden" name="object_type_id" value="<?php echo $object_type_id ?>">
        <input type="hidden" name="object_id" value="<?php echo $object_id ?>">
        <input type="hidden" id="object_action" name="object_action" value="">
        <input type="hidden" id="revision" name="revision" value="">
    
        <table class="profileTable">
    
        <?php if(in_array('object_id',$options['fields'])): ?>
        <tr style="font-size:14px">
            <th><?php echo _('Object ID') ?>:</th>
            <td><b><?php echo $object_type_id.'.'.$object['object_id'] ?></b></td>
        </tr>
        <?php endif; ?>
        
        <?php if(in_array('created',$options['fields'])): ?>
        <tr>
            <th><?php echo _('Created') ?>:</th>
            <td><?php echo date('F j, Y \a\t g:i:s a', $object['created']) ?></td>
        </tr>
        <?php endif; ?>
        
        <?php if(in_array('modified',$options['fields'])): ?>
        <tr>
            <th><?php echo _('Modified') ?>:</th>
            <td><?php echo date('F j, Y \a\t g:i:s a', $object['modified']) ?></td>
        </tr>
        <?php endif; ?>
        
        <?php if(in_array('type',$options['fields'])): ?>
        <tr> 
            <th><?php echo _('Type') ?>:</th>
            <td><a href="<?php echo URL ?>design_editor/design_types?object_type_id=<?php echo $object_type_id ?>" title="Go to type"><?php echo $type_data['display_name']; ?></a></td>
        </tr>
        <?php endif; ?>
    
        <?php if(in_array('display_name',$options['fields'])): ?>
        <tr>
            <th><div class="star"></div><label for="field_display_name"><?php echo _('Display name') ?>:</label></th>
            <td>
                <input <?php echo $disabled?> type="text" maxlength="64" id="field_display_name" name="display_name" value="<?php echo $object['display_name'] ?>" size="20"/>
                <div class="hint" title="<?php echo _('Object name which will be shown to user') ?>"></div>
            </td>
        </tr>
        <?php endif; ?>

		<?php if (!$base_fields_only): ?>
        <tr>
            <th><?php echo _('Advanced Options') ?>:</th>
            <td><input type="button" class="button small toggle_advanced" data-toggle="advanced_options" value="<?php echo _('Show / Hide') ?>"></td>
        </tr>
        <tr>
            <td colspan="2">
                <div id="advanced_options" class="advanced_options">
                    <table class="profileTable">
						 <?php if(in_array('name',$options['fields'])): ?>
                        <tr>
                            <th><div class="star"></div><label for="field_name"><?php echo _('System Name') ?>:</label></th>
                            <td>
                                <input <?php echo $disabled ?> type="text" maxlength="64" id="field_name" name="name" value="<?php echo $object['name'] ?>" size="20"/>
                                <div class="hint" title="<?php echo _('Object sytem name') ?>"></div>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php
                        if (!$base_fields_only)
                        {
                            $type_fields = get_type_fields($object_type_id, 'custom');

                            if(is_fx_error($type_fields))
                            {
                                echo '
                                <tr>
                                    <th></th>
                                    <td colspan="2"><font color="red">ERROR: '._('Unable to get fields list').'. '.$type_fields->get_error_message().'</font></td>
                                </tr>';
                            }
                            elseif($type_fields)
                            {
                                foreach($type_fields as $field => $field_options)
                                {
									if ($custom_fields[$field] === false) {
										continue;
									}
									
                                    $field_options['value'] = $object[$field];

                                    if ($field_options['name'] == 'associated_type') {
                                        $f_label = _('Associated Type');
                                        $f_control = '
                                        <select id="associated_type" name="associated_type">
                                            <option value="0">'._('Select type').'</option>
                                            '.show_select_options(get_schema_types($_SESSION['current_schema'], 'none'), 'object_type_id', 'display_name', $object[$field], false).'
                                        </select>';
                                    }
                                    elseif ($fc = get_field_control(array_merge(array('object_type_id'=>$object['object_type_id'], 'object_id'=>$object['object_id']), $field_options), $disabled ? true : false)) {
                                        $f_label = $custom_fields[$field]['label'] ? $custom_fields[$field]['label'] : $fc['label'];
                                        $f_control = $custom_fields[$field]['control'] ? $custom_fields[$field]['control'] : $fc['control'];
                                    }
                                    else {
                                        $f_label = '<font color="red">'.$field.'</font>';
                                        $f_control = '<font color="red">'._('Unable to get field control').'</font>';
                                    }

                                    echo '
                                    <tr>
                                        <th><nobr>'.$f_label.'<nobr></th>
                                        <td>'.$f_control.'</td>
                                    </tr>';
                                }
                            }
                        }

                        ?>
                    </table>
                </div>
            </td>
        </tr>
        <?php endif; //show_base_only ?>

        <?php if (!$disabled): ?>
        <tr>
            <th></th>
            <td><div class="star"></div> - <?php echo _('mandatory field') ?></td>
        </tr>
        <?php endif; ?>
        
		<?php if ($revisions): ?>
        <tr>
            <td colspan="2"><hr/></td>
        </tr>
        <tr>
            <th><?php echo _('Revisions') ?>:</th>
            <td>
            <?php			
				if ($revisions) {
					echo '<i>'.count($revisions).' '._('revision(s) available for this object').'</i><br>';
					for($i=0; $i<count($revisions); $i++) {
						echo ($i+1).') '.date('F j, Y \a\t g:i:s a', $revisions[$i]).'
							<input class="button small" type="button" value="'._('Restore').'" onclick="restore_object(\''.$object_type_id.'\',\''.$object_id.'\',\''.$revisions[$i].'\')"/><br>';
					}
				}
			?>
            </td>
        </tr>
        <?php endif; ?>
         
        </table>
        
        <?php do_actions('object_form_after'); ?>
        
        <hr/>

        <div>

            <?php if(in_array('update',$options['buttons'])): ?>
            <input class="button green" type="button" value="<?php echo _('Update') ?>" onclick="$('#object_action').attr('value','update'); submit();"/>
            <?php endif; ?>

            <?php if(in_array('replicate',$options['buttons'])): ?>
            <input class="button green" type="button" value="<?php echo _('Replicate') ?>" onclick="if(confirm('<?php echo _('Are you sure you want to replicate this object?') ?>')){$('#object_action').attr('value','replicate');submit();}else return;"/>
            <?php endif; ?>

            <?php if(in_array('reset',$options['buttons'])): ?>
            <input class="button blue" type="reset" value="<?php echo _('Reset') ?>"/>
            <?php endif; ?>

            <?php if(in_array('delete',$options['buttons'])): ?>
            <input class="button red" type="button" value="<?php echo _('Delete') ?>" onclick="if(confirm('<?php echo _('Are you sure you want to delete this object').$strongly_linked_msg.'?' ?>')){$('#object_action').attr('value','delete');submit();}else return;"/>
            <?php endif; ?>

            <?php if(in_array('cancel',$options['buttons'])): ?>
            <input class="button red" type="button" value="<?php echo _('Cancel') ?>" onclick="$('#object_action').attr('value','cancel');submit();"/>
            <?php endif; ?>
            
            <?php if(in_array('permalink',$options['buttons'])): ?>
            &nbsp;<span style="font-size:20px; color:#CCC">|</span>&nbsp;
            <input class="button" type="button" value="<?php echo _('Permalink') ?>" onclick="window.prompt('<?php echo _('Object permalink') ?>:','<?php echo $_SERVER['HTTP_HOST'].replace_url_params(array('schema_id'=>$_SESSION['current_schema'], 'set_id'=>$_SESSION['current_set'])); ?>')"/>
            <?php endif; ?>
            
            <?php
				if(array_key_exists('custom_buttons', $options)) {
					echo '&nbsp;<span style="font-size:20px; color:#CCC">|</span>&nbsp;';
					foreach($options['custom_buttons'] as $value) {
						echo $value;
					}					
				}
			?>
        </div>
        
	</form>
	<?php
}

function fx_object_form_cancel($object_type_id, $object_id)
{
	fx_redirect(replace_url_param('object_id', ''));
}

function fx_object_form_link($object_type_id, $object_id, $object_type_2_id, $object_2_id)
{
	return add_link($object_type_id, $object_id, $object_type_2_id, $object_2_id);
}

function fx_object_form_unlink($object_type_id, $object_id, $object_type_2_id, $object_2_id)
{
	return delete_link($object_type_id, $object_id, $object_type_2_id, $object_2_id);
}

function fx_object_form_delete($object_type_id, $object_id)
{
	$IOResult = delete_object($object_type_id, $object_id);
	
	if(!is_fx_error($IOResult)) {
		fx_redirect(replace_url_param('object_id', ''));
	}
	return $IOResult;
}

function fx_object_form_delete_selected($object_type_id, $objects = array())
{
	if (!$object_type_id) {
		return new FX_Error(__FUNCTION__, _('Unknown Object Type ID'));
	}
	
	$objects = (array)explode(',', $objects);
	$count = count($objects);

	if (!$count) {
		return new FX_Error(__FUNCTION__, _('Nothink to delete'));
	}
	
	foreach ($objects as $object_id) {
		$res = delete_object($object_type_id, $object_id);
		if (!is_fx_error($res)) {
			$count--;
		}
	}

	if (!$count) {
		fx_redirect(replace_url_param('object_id', ''));
		return true;
	}
	
	return new FX_Error(__FUNCTION__, $count.' '._('object(s) was not deleted'));
}

function fx_object_form_update($object_array)
{
	if (is_fx_error($object_array)) {
		return $object_array;
	}

	$IOResult = update_object($object_array);
	
	if (is_fx_error($IOResult)) {
		return $IOResult;
	}
	elseif ($IOResult === false) {
		$IOResult = new FX_Error(__FUNCTION__, _('Fields have not changed. Nothing to update'));
	}
	else {
		fx_redirect($_SERVER["REQUEST_URI"]);
	}
}

function fx_object_form_replicate($object_type_id, $object_id)
{
	$new_object_id = replicate_object($object_type_id, $object_id);
	if (!is_fx_error($new_object_id)) {
		fx_redirect(replace_url_param('object_id', $new_object_id));
	}
	return $new_object_id;
}

function fx_object_form_restore($object_type_id, $object_id, $revision)
{
	$IOResult = rollback_object($object_type_id, $object_id, $revision);
	if (!is_fx_error($IOResult)) {
		fx_redirect(replace_url_param('object_id', $object_id));
	}
	return $IOResult;
}

add_action('fx_object_form_cancel', 'fx_object_form_cancel', 10, 2);
add_action('fx_object_form_link', 'fx_object_form_link', 10, 4);
add_action('fx_object_form_unlink', 'fx_object_form_unlink', 10, 4);
add_action('fx_object_form_delete', 'fx_object_form_delete', 10, 2);
add_action('fx_object_form_delete_selected', 'fx_object_form_delete_selected', 10, 2);
add_action('fx_object_form_update', 'fx_object_form_update', 10, 1);
add_action('fx_object_form_replicate', 'fx_object_form_replicate', 10, 2);
add_action('fx_object_form_restore', 'fx_object_form_restore', 10, 3);