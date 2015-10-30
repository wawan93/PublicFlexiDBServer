<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$object_type_id = (int)str_replace('er_','',$_GET['object_type_id']);

	$type_data = get_type($object_type_id);

	$form_link = URL.'component/component_form_editor';
	if ($type_data['default_form_id'] && object_exists(TYPE_DATA_FORM, $type_data['default_form_id'])) {
		$form_link .= '?object_id='.$type_data['default_form_id'];
	}
	
	$query_link = URL.'component/component_query_editor';
	if ($type_data['default_query_id'] && object_exists(TYPE_QUERY, $type_data['default_query_id'])) {
		$query_link .= '?object_id='.$type_data['default_query_id'];
	}

?>
<form action="<?php echo URL; ?>design_editor/design_types" method="get">
    <input type="hidden" name="object_type_id" value="<?php echo $object_type_id ?>"/>
    <table class="profileTable">
    <tr>
        <th>Type ID: </th>
        <td><?php echo $object_type_id; ?></td>
    </tr>
    <tr>
        <th>Display Name: </th>
        <td><?php echo $type_data['display_name']; ?></td>
    </tr>
    <tr>
        <th>Description: </th>
        <td><?php echo $type_data['description'] ? $type_data['description'] : 'none'; ?></td>
    </tr>
    <tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
        <th></th>
        <td>
        <?php
            foreach(filter_field_list($type_data['fields']) as $key => $value) {
                $mandatory = $value['mandatory'] ? '<sup><font color=red>*</font></sup>' : '';
                $description = $value['description'] ? ' - <i>'.$value['description'].'</i>' : '';
                echo '<b>'.($value['caption'] ? $value['caption'] : $value['name']).'</b> ('.strtoupper($value['type']).')'.$mandatory.$description.'<br>';
            }
        ?>
        </td>
    </tr>
    </table>
    <hr>
    <center>
        <input class="button green" type="submit" value="Edit"/>
        <a href="<?php echo $form_link ?>" class="button blue">Form</a>
        <a href="<?php echo $query_link ?>" class="button blue">Query</a>
        <a href="<?php echo URL.'data_editor/data_objects?set_cet&object_type_id='.$object_type_id ?>" class="button blue">Objects</a>
        <input class="button red" type="button" id="close-dialog-window" value="Close"/>
    </center>
</form>