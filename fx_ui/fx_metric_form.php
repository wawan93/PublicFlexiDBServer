<?php

function metric_form($options = array())
{
	global $fx_error;

	$IOResult = false;

	$metric_id = isset($_REQUEST['metric_id']) ? $_REQUEST['metric_id'] : 0;

	if (isset($_POST['form_action']))
	{
		switch($_POST['form_action'])
		{
			case 'cancel':
				$IOResult = do_actions('fx_metric_form_cancel', $metric_id);
			break;
			case 'delete':
				$IOResult = do_actions('fx_metric_form_delete', $metric_id);
			break;
			case 'delete_selected':
				$IOResult = do_actions('fx_metric_form_delete_selected', $_POST['items']);
			break;
			case 'update':
				$IOResult = do_actions('fx_metric_form_update', $_POST);
			break;
			default:
				$IOResult = new FX_Error(__FUNCTION__, _('Unknown metric action'));
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
	
	if (!(int)$metric_id) {
		echo '<p class="info">'._('Please select the metric or create another one').'</div>';
		return;
	}	

	$metric = get_metric($metric_id, true);

	if (is_fx_error($metric)) {
		echo '<div class="msg-error">'.$metric -> get_error_message().'</div>';
		return;		
	}	

	$metric_name = isset($_POST['name']) ? $_POST['name'] : $metric['name'];
	$metric_description = isset($_POST['description']) ? $_POST['description'] : $metric['description'];
	$units = isset($_POST['units']) ? $_POST['units'] : $metric['units'];
	$currency_checked = isset($_POST['is_currency']) || $metric['is_currency'] ? ' checked="checked"' : '';

	do_actions('metric_form_before');
?>
    <form method="post" action="" id="metricForm">
        <input type="hidden" name="form_action" id="form_action" value="">
        <input type="hidden" name="metric_id" value="<?php echo $metric_id?>">
        <table class="profileTable">
            <tr style="font-size:14px">
                <th><?php echo _('Metric ID') ?>:</th>
                <td><b><?php echo $metric_id?></b></td>
            </tr>
            <tr>
                <th><label for="name"><?php echo _('Metric Name') ?>:</label></th>
                <td><input type="text" maxlength="64" id="name" name="name" value="<?php echo $metric_name ?>" size="20"/></td>
            </tr>
            <tr>
                <th><label for="description"><?php echo _('Description') ?>:</label></th>
                <td><textarea id="description" name="description" cols="40" rows="4"><?php echo $metric_description ?></textarea></td>
            </tr>
            <tr>
                <th><label for="is_currency"><?php echo _('Currency') ?>:</label></th>
                <td><input type="checkbox" id="is_currency" name="is_currency"<?php echo $currency_checked ?>/></td>
            </tr>
            <tr>
                <th><label><?php echo _('Units') ?>:</label></th>
                <td>
                	<input type="button" class="button small green" id="addFieldToSomeType" onclick="addMetricUnit('#fieldsTable');" value="<?php echo _('Add') ?>">
                </td>
            </tr>
        </table>

        <div id="fieldsContainer">
            <ul id="fieldsTable" class="editor" style="width: 480px">
            <?php
				if ($currency_checked) {
					foreach ($units as $unit) {
						fx_metric_form_print_currency_unit($unit['unit_id'], $unit['name'], $unit['decimals']);
					}
				}
				else {
					foreach ($units as $unit) {
						fx_metric_form_print_unit($unit['unit_id'], $unit['name'], $unit['factor'], $unit['decimals']);
					}
				}
			?>
            </ul>
            <hr>
        </div>        

		<?php do_actions('metric_form_after'); ?>

        <input class="button green" type="button" value="<?php echo _('Update') ?>" onclick="$('#form_action').attr('value','update');submitTypeForm($('#metricForm'));">
        <input class="button blue" type="reset" value="<?php echo _('Reset') ?>"/>
        <input class="button red" type="button" value="<?php echo _('Delete') ?>" onclick="if(confirm('<?php echo _('Are you sure you want to delete current metric?') ?>')){$('#form_action').attr('value','delete');submit();}" name="delete">
        <input class="button red" type="button" value="<?php echo _('Cancel') ?>" onclick="$('#form_action').attr('value','cancel');submit();"/>
    </form>

	<?php
}

function fx_metric_form_cancel($metric_id)
{
	fx_redirect(replace_url_param('metric_id', ''));
}

function fx_metric_form_delete($metric_id)
{
	$IOResult = delete_metric($metric_id);
	if(!is_fx_error($IOResult)) {
		fx_redirect(replace_url_param('metric_id', ''));
	}
	return $IOResult;
}

function fx_metric_form_delete_selected($metrics = array())
{
	$metrics = (array)explode(',', $metrics);
	$count = count($metrics);

	if (!$count) {
		return new FX_Error(__FUNCTION__, _('Nothing to delete'));
	}
	
	foreach ($metrics as $metric_id) {
		$res = delete_metric($metric_id);
		if (!is_fx_error($res)) {
			$count--;
		}
	}

	if(!$count) {
		fx_redirect(replace_url_param('metric_id', ''));
	}

	return new FX_Error(__FUNCTION__, $count.' '._('metric(s) was not deleted'));
}

function fx_metric_form_update($metric)
{
	$sort_order = 0;
	$new_units = array();
	
	foreach($metric['units'] as $unit) {
		if (!strlen((string)$unit['name'])) {
			return new FX_Error(__FUNCTION__, _('Please set all names'));
		}
		$new_units[] = array('unit_id' => $unit['unit_id'], 'name' => $unit['name'], 'factor' => $unit['factor'], 'decimals' => intval($unit['decimals']));
	}

	$metric['units'] = $new_units;

	$IOResult = update_metric($metric);
		
	if (!is_fx_error($IOResult)) {
		fx_redirect(replace_url_param('metric_id', $metric['metric_id']));
	}
	
	return $IOResult;
}

add_action('fx_metric_form_cancel', 'fx_metric_form_cancel', 10, 1);
add_action('fx_metric_form_delete', 'fx_metric_form_delete', 10, 1);
add_action('fx_metric_form_delete_selected', 'fx_metric_form_delete_selected', 10, 1);
add_action('fx_metric_form_update', 'fx_metric_form_update', 10, 1);

function fx_metric_form_print_unit($unit_id = 0, $name = '', $factor = '', $decimals = 0)
{
	
	?>
    <li class="palette-item" style="overflow: visible ; z-index: inherit">
    	<input name="units[unit_id]" class="_field" type="hidden" value="<?php echo $unit_id ?>"/>
        <input name="units[name]" title="Name" style="width: 100px" class="_field" type="text" value="<?php echo $name ?>" placeholder="<?php echo _('Name') ?>"/>
        <input name="units[factor]" title="Factor" style="width: 60px" class="_field" type="text" value="<?php echo $factor ?>" placeholder="<?php echo _('Factor') ?>"/>
        <input name="units[decimals]" title="Decimals" style="width: 60px" class="_field" type="text" value="<?php echo $decimals ?>" placeholder="<?php echo _('Decimals') ?>"/>
        <img class="remove" onclick="$(this).parents('li').remove()" src="<?php echo CONF_SITE_URL; ?>images/remove.png" alt="×">
    </li>    
    <?php
}

function fx_metric_form_print_currency_unit($unit_id = 0, $name = '', $decimals = 0)
{
	?>
    <li class="palette-item" style="overflow: visible ; z-index: inherit">
    	<input name="units[unit_id]" class="_field" type="hidden" value="<?php echo $unit_id ?>"/>
        <input name="units[name]" title="Name" style="width: 100px" class="_field" type="text" value="<?php echo $name ?>" placeholder="<?php echo _('Name') ?>"/>
        <input name="units[factor]" title="Factor" style="width: 60px" class="_field" type="text" value="auto" disabled="disabled"/>
        <input name="units[decimals]" title="Decimals" style="width: 60px" class="_field" type="text" value="<?php echo $decimals ?>" placeholder="<?php echo _('Decimals') ?>"/>
        <img class="remove" onclick="$(this).parents('li').remove()" src="<?php echo CONF_SITE_URL; ?>images/remove.png" alt="×">
    </li>    
    <?php
}