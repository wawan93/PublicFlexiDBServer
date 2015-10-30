<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$data = str_replace('er_', '', $_GET['data']);
	
	list($tmp, $object_type_1_id, $object_type_2_id, $relation, $strength) = explode('-', $data);
	
	$link_type_exists = link_type_exists($object_type_1_id, $object_type_2_id) ? true : false;
	
	$elem_label_id = 'er_'.$object_type_1_id.'-er_'.$object_type_2_id;
	$elem_type_id_1 = 'er_'.$object_type_1_id;
	$elem_type_id_2 = 'er_'.$object_type_2_id;
	
	$type1 = get_type($object_type_1_id, 'none');
	$type2 = get_type($object_type_2_id, 'none');
?>

    <table class="profileTable">
    <?php if($link_type_exists): ?>
    <tr>
		<td colspan="2" align="center">
        	<b><font color="#FF0000">Existing link type</font></b>
            <hr>
        </td>
    </tr>    
    <?php endif; ?>
    
    <?php if($type1['name'] != $type2['name']): ?>
    <tr>
		<th>Object Type 1: </th>
		<td><?php echo $type1['display_name'] ?></td>
    </tr>
    <?php else: ?>
    <tr>
		<th>Self Link: </th>
		<td><?php echo $type1['display_name'] ?></td>
    </tr>    
    <?php endif; ?>
    
    <tr>
		<th><label for="relation">Link Relation: </label></th>
		<td>
            <select id="relation" name="relation">
            <?php show_select_options(array('1'=>'one-to-one','2'=>'one-to-many','3'=>'many-to-one','4'=>'many-to-many'), '', '', $relation) ?>
            </select>
        </td>
    </tr>
    <tr>
		<th><label for="strength">Link Strength: </label></th>
		<td>
            <select id="strength" name="strength">
            <?php show_select_options(array('0'=>'weak','1'=>'strong'), '', '', $strength) ?>
            </select>
        </td>
    </tr>
    
	<?php if($type1['name'] != $type2['name']): ?>
    <tr>
		<th>Object Type 2: </th>
		<td><?php echo $type2['display_name'] ?></td>
    </tr>
    <?php endif; ?>
    
    <?php if($link_type_exists): ?>
    <tr>
		<td colspan="2" align="center">
        	<hr>
        	<span style="font-size:10px; font-style:italic; color:#FF0000;"><b>Warning:&nbsp;</b>Changing the relation of an existing link type can lead to the removal of already created links</span>
        </td>
    </tr>
    <?php endif; ?>
	</table>
    <hr>
    <center>
        <input class="button green" type="button" value="Update"/>
        <input class="button red" type="button" value="Delete"/>
        <input class="button blue" type="button" value="Close"/>
    </center>