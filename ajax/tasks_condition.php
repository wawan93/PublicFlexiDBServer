<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();
		
	$name = $_POST['name'];
	$selected = $_POST['selected'];
	
	$conditions = array("=="=>"=", "!="=>"&ne;", ">"=>"&gt;", ">="=>"&ge;", "<"=>"&lt;", "<="=>"&le;");

	$out = '<select class="condition-select" style="display:none" name="'.$name.'-condition">';
	
	foreach ($conditions as $key => $value) {
		$selected = $key == $selected ? ' selected="selected"' : '';
		$out .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
	}

	$out .= '</select>';
	
	echo $out;