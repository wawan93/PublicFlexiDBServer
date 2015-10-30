<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}

	$schema = $_SESSION['current_schema'];

	$fsm_type_id = TYPE_FSM_EVENT;

	if(isset($_GET['object_id'])){
		$obj = get_object($fsm_type_id,intval($_GET['object_id']));
	}

	$name = isset($obj['display_name']) ? $obj['display_name'] : '';

	$fsm_objects_list = get_objects_by_type($fsm_type_id, $schema);

    if(is_fx_error($fsm_objects_list)) {
        $fsm_objects_list = '<div class="msg-error">'.$fsm_objects_list->get_error_message().'</div>';
    } else {
        $fsm_objects_list = '
	<label for="fsm_name">FSM name:</label>
	<input type="text" id="fsm_name" value="'.$name.'">
	<label for="fsm_objects">Select FSM:</label><br />
	<select id="fsm_objects">
		<option value="-1">New</option>
		'.($fsm_objects_list ? show_select_options($fsm_objects_list, 'object_id', 'display_name', isset($_GET['object_id']) ? (int)$_GET['object_id'] : 0, false ) : '').'
	</select>';
    }


foreach (get_schema_types($schema,'custom') as $type) {
		$add = false;
		foreach ($type['fields'] as $key=>$field) {
			if (is_numeric($field['type'])) {
				$add = true;
			}
		}
		if ($add) {
			$rel_types[] = $type;
		}
	}

	$type_disabled = $obj['object_type'] ? ' disabled' : '';

	$fsm_objects_list .= '
	<label for="object_types">Types:</label>
	<select id="object_types"'.$type_disabled.'>
		'.show_select_options($rel_types, 'object_type_id', 'display_name', isset($_GET['object_id']) ? (int)$_GET['object_id'] : 0, false ).'
	</select>';

	$type_fields_disabled = $obj['object_field'] ? ' disabled' : '';

	$fsm_objects_list .= '
	<label for="type_fields">Fields:</label>
	<select id="type_fields"'.$type_fields_disabled.'></select>';

	$fsm_objects_list .= '
	<label for="field_values">Initial State:</label>
	<select id="field_values">
	</select>';

	if ($obj) {
		$enabled = $obj['enabled'] ? ' checked ' : '';
	}
	else {
		$enabled = ' checked ';
	}

	$fsm_objects_list .= '
	<label for="fsm_enabled">Enabled:
		<input type="checkbox" id="fsm_enabled"'.$enabled.'>
	</label>';

	if ($schema_roles = get_objects_by_type(TYPE_ROLE, $_SESSION['current_schema'])) {
		$event_roles = (array)json_decode($event['roles'], true);
		$role_ctrl = "<select multiple=\"multiple\" class='multi roles' name=\"roles\">";

		foreach ((array)$schema_roles as $role) {
			$role_id = $role['object_id'];
			$checked = in_array($role_id, $event_roles) ? " selected=\"selected\"" : "";
			$role_ctrl .= '<option'.$checked.' value="'.$role_id.'">'.$role['display_name'].'</option>';
		}

		$role_ctrl .= '</select>';
	}
	else {
		$role_ctrl = "<font color=\"red\">"._('No roles available in current Data Schema')."</font>";
	}

?>

<script type="text/javascript" src="<?php echo URL; ?>/js/ClassesV2.js"></script>
<script type="text/javascript" src="<?php echo URL; ?>/js/ICanHaz.min.js"></script>
<div class="leftcolumn">
<?php
	fx_show_metabox(array(
		'header' => array('hidden' => false, 'content'=>'FSM Editor'),
		'body' => array('content' => $fsm_objects_list),
		'footer' => array(
			'hidden' => false,
			'content' => '
			<input type="button" id="save-button" class="button green" value="Save">
			<input type="button" id="remove-button" class="button red" value="Delete">',
		)
	));
	fx_show_metabox(array(
		'header' => array('content' => 'Add action'),
		'body' => array('content' => '<ul id="palette" class="editor compact"></ul>'),
		'footer' => array('hidden' => true)
	));
?>
</div>

<div class="rightcolumn">
<?php
	fx_show_metabox(array(
		'header' => array('content' => 'Actions'),
		'body' => array('content' => '<ul id="fsm_stack" class="editor"></ul>'),
		'footer' => array('hidden' => true)
	));
?>
</div>

<link rel="stylesheet" href="<?php echo URL;?>/style/multiple-select.css" />
<script src="<?php echo URL;?>/js/jquery.multiple.select.js"></script>
<script>
    var FsmPaletteItem = function (data) {
        var $li = ich["item"](data);
        PaletteItem.call(this, $li, this.collectData, this.renderData,this.addedToStackCallback);
    };
    FsmPaletteItem.prototype.collectData = function ($li) {
        var input_data = $li.collectInputsData();
        input_data.event_condition = {
            field: $li.find('.event_condition_field').val(),
            operator: $li.find('.event_condition_operator').val(),
            value: $li.find('.event_condition_value').val()
        };
        input_data.roles = $li.find('select.roles').first().val();
        return input_data;
    };
    FsmPaletteItem.prototype.renderData = function ($li, data) {
        $li.renderInputsData(data);

        if(data.roles){
            console.log(data.roles);
            $li.find('select.roles').first().val(data.roles);
        }

        $li.find('.event_condition_field').val(data.event_condition.field);
        $li.find('.event_condition_operator').val(data.event_condition.operator);
        $li.find('.event_condition_value').val(data.event_condition.value);
        var $roles = $li.find('select.roles').first();
        var mss = $li.find('.ms-parent');
        $roles.multipleSelect();
        if(mss.length>1) mss.last().remove();
        initAccordion($li);

//        editor.saved = true;
    };
    FsmPaletteItem.prototype.addedToStackCallback = function($li) {
        $li.find('.title').html('<input type="text" name="name" placeholder="action name">');
        $li.find('.palette-item-data select[name=start_state]').html($('#field_values').html());
        $li.find('.palette-item-data select[name=end_state]').html($('#field_values').html());
        $li.find('.event_condition_field').empty().html(window.$cond_fields.html());
        var $roles = $li.find('select.roles').first();
        var mss = $li.find('.ms-parent');
        $roles.multipleSelect();
        if(mss.length>1) mss.last().remove();
        initAccordion($li);
    };

    var FsmEditor = function($nameInput, $objectSelect, $stack,
        $typeSelect, $typeFields, $fieldValues, $addAction, data, schemaId, setId)
    {
        ich.grabTemplates();
        var _this = this;
        _this.typeId = <?php echo $fsm_type_id; ?>;
        this.objectId = <?php echo isset($_GET['object_id']) ? (int)$_GET['object_id'] : 0; ?>;
        this.stack = new Stack($stack);
        this.fieldsPalette = new Palette(
            "fields", $('#palette'), $stack, false, function(key, data){
                return this.$palette.children("li");
            }, {});

        this.clearAll = function(){
            $typeFields.empty();
            $fieldValues.empty();
        };
        this.addedToStackCallback = function(){
            console.log('addedToStack motherfucker!');
        };

        this.findErrorsCallback = function() {
            console.log('find errors');
        };
        var renderDataCallback = function(data) {
            if(typeof data === 'undefined') return false;
            if(typeof data.code === 'undefined') return false;

            var code = JSON.parse(data.code);
            console.log('render data callback',code);
            _this.stack.renderDataUsingPalette(code,_this.fieldsPalette);
            _this.saved = true;

            console.log(_this);


        };

        this.checkUnique = function(object) {
            var q = [
                {
                    name: 'object_type',
                    object_type_id: this.typeId,
                    criteria: '='+$typeSelect.val()
                },
                {
                    name: 'object_field',
                    object_type_id: this.typeId,
                    criteria: '=\''+$typeFields.val()+"'"
                }
            ];


            var data = {
                query: q,
                main_type: this.typeId
            }

            flexiweb.callFunction.getQueryResult(data, function(data){
                if(data.length === 0){
//                    flexiweb.callFunction.addObject({object_array: object, 'function': 'add_object'}, function(resp){
                    flexiweb.callFunction.addObject(object, function(resp){
                        console.log(resp);

                        if(!isNumber(resp)){
//                        resp = JSON.parse(resp);
//                            alert(resp.errors.add_object[0]);
                            return;
                        }
                        _this.saved = true;
                        window.location = '?object_id='+resp;



                    });
                } else {
                    alert('FSM with this Type and Field already exists');
                }
            });

//            flexiweb.callFunction.updatePreview(this.typeId, q, '', 0, 0, 0, );
        };

        this.pre_validate = function(){
            var abort = false;
            $stack.find('input[name=name]').each(function(i){
                if($(this).val() == '')
                    abort = 'action_name is required!';
            });
            $stack.find('li.palette-item').each(function(i){
                var start = $(this).find('select[name=start_state]').first();
                var end = $(this).find('select[name=end_state]').first();
                if(start.val() == end.val()) abort = 'Start state cannot be equal to end state';
            });

            if(abort){
                alert(abort);
                return false;
            }
            return true;
        };
        this.save = function () {
            var collectedData = _this.stack.collectData();
            if(!this.pre_validate()) return false;
            var object = {
                    'name': $nameInput.val(),
                    'code': JSON.stringify(collectedData),
                    'object_type_id': _this.typeId,
                    'object_type': $typeSelect.val(),
                    'object_field': $typeFields.val(),
                    'initial_state': $fieldValues.val(),
                    'enabled': $('#fsm_enabled').prop('checked')? 1 : 0,
                    'display_name': $nameInput.val(),
                    'schema_id': schemaId
            };

            if(!_this.objectId){
                _this.checkUnique(object);
            } else {
                console.log('_this.objectId',_this.objectId);
                object.object_id = _this.objectId;
                object.function = 'update_object';
                flexiweb.callFunction.updateObject(object, function(resp){
                    console.log(resp);
                    _this.saved = true;
                });
            }
        };

        this.remove = function () {
            doWithConfirmation(function () {
                flexiweb.callFunction.removeObject(_this.typeId, $objectSelect.val(), function (resp) {
                    if (resp) {
                        window.onbeforeunload = undefined;
                        window.location.search = "";
                    }
                });
            }, 'Delete?');
        };

        DataObjectEditor.call(this, <?php echo $fsm_type_id; ?>, "fsm_event",
            $nameInput, $objectSelect, $('#save-button'), $('#remove-button'),
            [], [this.stack], undefined, renderDataCallback, this.findErrorsCallback,
            data, schemaId, setId
        );

        _this.data = data;





        $typeSelect.unbind('change').bind('change',function(e){
            _this.clearAll();

            if(!$(this).val()) return false;

            flexiweb.callFunction.getTypeFields($(this).val(),function(fields){
                window.$cond_fields = $('<select></select>').empty();

                if( typeof fields === 'undefined') return false;
                $.each(fields, function(index,field){
                    window.$cond_fields.append('<option value="'+field.name+'" data-type="'+field.type+'">'+field.name+'</option>');
                    if(parseInt(field.type)){
                        $typeFields.append('<option value="'+field.name+'" data-type="'+field.type+'">'+field.name+'</option>');
                    }
                });

                $stack.find('.event_condition_field').empty().html(window.$cond_fields.html());
                $typeFields.unbind('change').bind('change',function(e){
                    var args = {
                        data: {
                            'function' : 'get_enum_fields',
                            'enum_type_id': $typeFields.find('option:selected').attr('data-type')
                        }
                    };
                    flexiweb.callFunction.generalRequest(args,function(resp){
                        $fieldValues.empty();
                        console.log('enum_fields',resp);

                        if( typeof resp === 'undefined') return false;

                        $.each(resp, function(index,item){
                            var $op = $('<option value="'+index+'">'+item+'</option>');
                            $fieldValues.append($op);
                        });

                        if(_this.data){
                            _this.renderData(data);
                            $fieldValues.val(_this.data.initial_state).change();
                        } else {
                            $stack.find('.palette-item-data select[name=start_state]').html($fieldValues.html());
                            $stack.find('.palette-item-data select[name=end_state]').html($fieldValues.html());
                        }
                    });
                });

                if(_this.data){
                    $typeFields.val(_this.data.object_field).change();
                } else {
                    $typeFields.change();
                }
            });
        });


        if(_this.data){
            $typeSelect.val(_this.data.object_type).change();
        } else {
            $typeSelect.change();
        }

        var $li = new FsmPaletteItem();
        _this.fieldsPalette.update([$li]);

        _this.saved = true;
    };

    $(document).ready(function(){
        var data = <?php echo $obj ? json_encode($obj) : 'undefined'; ?>;
        window.defaultValues = {
            event_condition_value: '',
            event_condition_operator: '=='
        };
        window.editor = new FsmEditor($('#fsm_name'),$('#fsm_objects'),
            $('#fsm_stack'),$('#object_types'), $('#type_fields'), $('#field_values'), $('#addAction'),
            data, <?php echo $schema; ?>, <?php echo intval($_SESSION['current_set']); ?>
        );
        window.editor.saved = true;

        var firstStart = true;

        $('#field_values').bind('change', function(){

            if(!firstStart) {
                editor.saved = false;
            }
            firstStart = false;

        });
        $('#fsm_enabled').bind('click', function(){ editor.saved = false; });
    });
</script>

<style>
    .accordion label, .accordion input {
        display: inline;
    }
</style>


<script id="item" type="text/html">
    <li class="palette-item " data-name="{{name}}" data-caption="{{caption}}" >
    <span class="title">
        <span>New Action</span>
    </span>
        <img class="remove"
             src="<?php echo CONF_SITE_URL; ?>images/remove.png"
             alt="x">
        <div class="palette-item-data">
            <table class="accordion">
                <tbody>
                <tr>
                    <td class="dt"><a href="">States</a></td>
                    <td class="dd">
                        <div style="display: inline-block;">
                            <label>
                                Start state:
                                <select name="start_state" class="start_state"></select>
                            </label>
                            <label>
                                End state:
                                <select name="end_state" class="end_state"> </select>
                            </label>
                        </div>
                    </td>
                    <td class="dt"><a href="">Roles</a></td>
                    <td class="dd">
                        <?php echo $role_ctrl; ?>
                    </td>
                    <td class="dt"><a href="">Cond.</a></td>
                    <td class="dd">
                    <div style="display: inline-block">
                        <?php
                        $event_condition = '
                            <select class="event_condition_field" >
                                <option value="">Select field</option>
                            </select>
                            <select class="event_condition_operator" >
                            ' . show_select_options(
                                    array("==" => "==",
                                        "!=" => "!=",
                                        ">" => "&gt;",
                                        ">=" => "&gt;=",
                                        "<" => "&lt;",
                                        "<=" => "&lt;="),
                                    '','',$event['event_condition']['operator'],
                                    false
                                ) . '
                            </select>
                            <input type="text" class="event_condition_value" >';
                        echo $event_condition;
                        ?>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </li>
</script>