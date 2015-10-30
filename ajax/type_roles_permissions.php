<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

?>

<script type="text/javascript">

    if (typeof(jQuery) == "undefined") {
        var iframeBody = document.getElementsByTagName("body")[0];
        var jQuery = function (selector) { return parent.jQuery(selector, iframeBody); };
        var $ = jQuery;

    }
    $(document).ready(function() {
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

        $('#saveRolePermissionsButton').click(function() {
            var fx_dir = window.flexiweb.site_url;
            $formdata = $('#typeRolePermissions').serialize()
            $.ajax({
                type: 'post',
                url: fx_dir + 'ajax/save_type_roles_permissions.php',
                data: $formdata,
                dataType: 'json',
                success: function(resp) {
                    var msg = ''
                    if (resp.status === 'success') {
                        msg = 'Roles have been succesfully saved!'
                    } else if (resp.status === 'error') {
                        msg = 'Error! '
                        if (resp.message) msg += resp.message
                    }
                    if ($('#infoMessage').length == 1) {
                        $infoMessage = $('#infoMessage')
                    } else {
                        $infoMessage = $('<div>', {'text': msg, 'class': 'info '+resp.status, 'style': 'display: none', 'id': 'infoMessage'})
                        $('#typeRolePermissions').before($infoMessage)
                    }
                    $infoMessage.hide().fadeIn(150)
                }
            })
        })
    });

</script>
<?php

if (isset($_REQUEST['object_type_id']) && $_REQUEST['object_type_id']):

	$object_type_id = $_REQUEST['object_type_id'];
	$role_type = get_type_id_by_name(0, 'role');
	$schema_types = get_schema_types($_SESSION['current_schema'], 'none');

	$roles = get_objects_by_type($role_type, $_SESSION['current_schema']);
?>
<form method="post" id="typeRolePermissions">
	<input type="hidden" name="object_type_id" value="<?php echo $object_type_id?>">
	<table class="simpleTable">
		<tr align="center">
			<th>&nbsp;Role&nbsp;</th>
			<th>&nbsp;Read (GET)&nbsp;<br><input type="checkbox" title="Select All" id="getAll"></th>
			<th>&nbsp;Create (POST)&nbsp;<br><input type="checkbox" title="Select All" id="postAll"></th>
			<th>&nbsp;Edit (PUT)&nbsp;<br><input type="checkbox" title="Select All" id="putAll"></th>
			<th>&nbsp;Delete (DELETE)&nbsp;<br><input type="checkbox" title="Select All" id="deleteAll"></th>
		</tr>
		<?php
			foreach ($roles as $role) {
				$permissions = json_decode($role['permissions']);
				$cp = isset($permissions->$object_type_id) ? (int)$permissions->$object_type_id : 0;
				echo '<tr align="center">
					<td>'.$role['display_name'].($role['data_set_role'] ? ' (s)': '').'</td>
					<td> <input type="checkbox" class="getCheckbox" name="flags['.$role['object_id'].'][get]" '.($cp & U_GET ? " checked=\"checked\"" : "").'> </td>
					<td> <input type="checkbox" class="postCheckbox" name="flags['.$role['object_id'].'][post]" '.($cp & U_POST ? " checked=\"checked\"" : "").'> </td>
					<td> <input type="checkbox" class="putCheckbox" name="flags['.$role['object_id'].'][put]" '.($cp & U_PUT ? " checked=\"checked\"" : "").'> </td>
					<td> <input type="checkbox" class="deleteCheckbox" name="flags['.$role['object_id'].'][delete]" '.($cp & U_DELETE ? " checked=\"checked\"" : "").'> </td>
				</tr>';
		
			}

		?>
	</table>
	<hr>
	<input type="button" class="button green" value="<?php echo  _('Save') ?>" id="saveRolePermissionsButton">
	<input type="button" class="button red" value="<?php echo  _('Cancel') ?>" onclick="$('#dialog').dialog('close')">
</form>

<?php else: ?>

	<div class="error"><?php echo  _('Empty Object Type ID') ?></div>
	<hr>
    <input type="button" class="button" value="<?php echo  _('Cancel') ?>" onclick="$('#dialog').dialog('close')">

<?php endif; ?>
