<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}

	function _form_update_permissions($object_array)
	{
		if (is_fx_error($object_array)) {
			return $object_array;
		}
	
		if (isset($_POST['_update_role_flags'])) {
			$permissions = array();
	
			foreach ((array)$_POST['_flags'] as $object_type_id => $flag) {
				$permissions[$object_type_id] = bindec(
					(isset($flag['delete']) ? '1' : '0') . (isset($flag['put']) ? '1' : '0') . (isset($flag['post']) ? '1' : '0') . (isset($flag['get']) ? '1' : '0')
				);
			}
	
			ksort($permissions);
			$object_array['permissions'] = json_encode($permissions);
		}

		return $object_array;
	}
	
	add_action('fx_object_form_update', '_form_update_permissions', 1, 1);

	function _roles_manager($permissions = array())
	{
		if (!$schema_types = get_schema_types($_SESSION['current_schema'], 'none')) {
			return '<font color="#FF0000">There is no custom types in current schema.</font>';
		}

		$out = "
		<input type=\"hidden\" name=\"_update_role_flags\"/>
		<!--<h2><b>What actions (API methods) are available to the user with current role depending on its state:<b></h2>-->
		<h2><b>What actions (API methods) can be performed by the user with objects of the following types:<b></h2>
		<table class=\"simpleTable\" id=\"state-$key\">
		<tr align=\"center\">
			<th>&nbsp;Type&nbsp;</th>
			<th>&nbsp;Read (GET)&nbsp;<br><input type=\"checkbox\" title=\"Select All\" id=\"getAll\"></th>
			<th>&nbsp;Create (POST)&nbsp;<br><input type=\"checkbox\" title=\"Select All\" id=\"postAll\"></th>
			<th>&nbsp;Edit (PUT)&nbsp;<br><input type=\"checkbox\" title=\"Select All\" id=\"putAll\"></th>
			<th>&nbsp;Delete (DELETE)&nbsp;<br><input type=\"checkbox\" title=\"Select All\" id=\"deleteAll\"></th>
		</tr>\n";

		$type_subscription = (get_type(TYPE_SUBSCRIPTION, 'none'));
		
		if (!is_fx_error($type_subscription)) {
			$schema_types += array(TYPE_SUBSCRIPTION => $type_subscription); 
		}

		foreach ($schema_types as $object_type_id => $type_data)
		{
			$cp = isset($permissions[$object_type_id]) ? (int)$permissions[$object_type_id] : 0;

			$out .= "\t\t<tr align=\"center\">\n";
			$out .= "\t\t\t<td align=\"right\">".$type_data['display_name']."</td>\n";
			$out .= "\t\t\t<td><input name=\"_flags[$object_type_id][get]\" class=\"getCheckbox\" type=\"checkbox\"".($cp & U_GET ? " checked=\"checked\"" : "")."></td>\n"; //0001
			$out .= "\t\t\t<td><input name=\"_flags[$object_type_id][post]\" class=\"postCheckbox\" type=\"checkbox\"".($cp & U_POST ? " checked=\"checked\"" : "")."></td>\n"; //0010
			$out .= "\t\t\t<td><input name=\"_flags[$object_type_id][put]\" class=\"putCheckbox\" type=\"checkbox\"".($cp & U_PUT ? " checked=\"checked\"" : "")."></td>\n"; //0100
			$out .= "\t\t\t<td><input name=\"_flags[$object_type_id][delete]\" class=\"deleteCheckbox\" type=\"checkbox\"".($cp & U_DELETE ? " checked=\"checked\"" : "")."></td>\n"; //1000
			$out .= "\t\t</tr>\n";
		}

		$out .= "\t</table>\n";

        $out .= "<script type=\"text/javascript\">
        $(function() {
            $('#getAll').on('change', function() { $('.getCheckbox').prop('checked', $(this).prop('checked')) })
            $('#postAll').on('change', function() { $('.postCheckbox').prop('checked', $(this).prop('checked')) })
            $('#putAll').on('change', function() { $('.putCheckbox').prop('checked', $(this).prop('checked')) })
            $('#deleteAll').on('change', function() { $('.deleteCheckbox').prop('checked', $(this).prop('checked')) })

            $('.getCheckbox').on('change', function() {
                if (!$(this).prop('checked')) { $('#getAll').prop('checked', false) } else {
                    if ($('.getCheckbox:checked').length == $('.getCheckbox').length) {
                        $('#getAll').prop('checked', true)
                    }
                }
            })
            $('.postCheckbox').on('change', function() {
                if (!$(this).prop('checked')) { $('#postAll').prop('checked', false) } else {
                    if ($('.postCheckbox:checked').length == $('.postCheckbox').length) {
                        $('#postAll').prop('checked', true)
                    }
                }
            })
            $('.putCheckbox').on('change', function() {
                if (!$(this).prop('checked')) { $('#putAll').prop('checked', false) } else {
                    if ($('.putCheckbox:checked').length == $('.putCheckbox').length) {
                        $('#putAll').prop('checked', true)
                    }
                }
            })
            $('.deleteCheckbox').on('change', function() {
                if (!$(this).prop('checked')) { $('#deleteAll').prop('checked', false) } else {
                    if ($('.deleteCheckbox:checked').length == $('.deleteCheckbox').length) {
                        $('#deleteAll').prop('checked', true)
                    }
                }
            })
        })
        </script>";

		return $out;
	}

	$current_object = get_current_object();

	if (!is_fx_error($current_object)) {
		$custom_fields = array();
		$custom_fields['permissions']['control'] = _roles_manager(json_decode($current_object['permissions'], true));
	}

	$options = array('object_type_id' => TYPE_ROLE,
					 'fields' => array('object_id','created','modified','display_name'),
					 'custom_fields' => $custom_fields,
					 'buttons' => array('update','reset','cancel','delete','permalink'));
	
	$mb_data = array('header' => array('suffix' => !is_fx_error($current_object) ? ' - '.$current_object['display_name'] : ''),
					 'body' => array('function' => 'object_form', 'args' => array($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);

	$options = array('set_id' => 0,
					 'schema_id' => $_SESSION['current_schema'],
					 'object_type_id' => TYPE_ROLE,
					 'fields' => array('object_id','display_name','name'),
					 'actions' => array('view','edit','delete'));

	$mb_data = array('header' => array('hidden' => true),
					 'body' => array('content' => object_explorer($options)),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);