<?php

	$schema = $_SESSION['current_schema'];

	if (!$schema) {
		fx_show_metabox(array('body' => array('content' => new FX_Error('empty_data_schema', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));
		return;
	}
	
	if (isset($_GET['object_id'])) {
		$object_id = (int)$_GET['object_id'];
		$obj = get_object(TYPE_DATA_FORM, $object_id);
		if (!is_fx_error($obj)) {
			$data = json_encode($obj);
			$name = $obj['display_name'];
			$current_type = $obj['object_type'];
		}
	}
	else {
		$data = false;
		$current_type = 0;
	}


    if($data) {
        $data = json_decode($data, true);
        $data['code'] = json_decode($data['code'], true);
    }
?>


<div class="leftcolumn">
    <div class="metabox" id="controls">
        <div class="header">
            <div class="icons widgets"></div>
            <h1>Data Form</h1>
        </div>
        <div class="content" id="tasks">
            <label for="object_name">Data Form Name:</label>
            <input type="text" id="object_name" value="<?php echo $name; ?>">

            <label for="object_select">Select Data Form:</label>
            <select id="object_select">
                <option value="-1">New Data Form</option>
                <?php show_select_options(get_objects_by_type(TYPE_DATA_FORM, $schema), 'object_id', 'display_name', $object_id); ?>
            </select>

            <label for="type_select">Object Type:</label>
            <select id='type_select'>
                <?php show_select_options(get_schema_types($schema, "none"), 'object_type_id', 'display_name', $current_type); ?>
            </select>

            <label for="link-with-user">Link with user<input type="checkbox" name="link_with_user" id="link_with_user"></label>
            <label for="filter_by_set">Filter links by set <input type="checkbox" id="filter_by_set" name="filter_by_set"></label>

            <a href="<?php echo URL;?>design_editor/design_er" id="go-to-er-diagram" class="button blue">ER Diagram</a>
            <a href="<?php echo URL;?>design_editor/design_types" id="go_to_type" class="button blue">Edit type</a>
        </div>
        <div class="footer">
            <input type="button" id="save" class="button green" value="Save">
            <input type="button" id="remove" class="button red" value="Remove">

        </div>
    </div>
    <div class="metabox">
        <div class="header">
            <div class="icons widgets"></div>
            <h1>Fields</h1>
        </div>
        <div class="content">
            <ul id="fields" class="editor compact"></ul>
        </div>
    </div>

    <div class="metabox">
        <div class="header">
            <div class="icons widgets"></div>
            <h1>Links</h1>
        </div>
        <div class="content">
            <ul id="links" class="editor compact"></ul>
        </div>
    </div>
</div>

<div class="rightcolumn">
<div class="metabox">
    <div class="header">
        <div class="icons widgets"></div>
        <h1>Data form editor</h1>
    </div>

    <div class="content">
        <ul id="stack" class="editor form_editor"></ul>
    </div>
    <div class="footer">
    </div>
</div>

</div>

<script id="FieldPaletteItem" type="text/html">
    <div class="palette-item" data-field="{{name}}">
            <span class="title">
                <span class='field-name'>{{name}}</span>
                (<span class='field-type'>{{type}}</span>)
            </span>

        <img class="remove" src="<?php echo URL;?>images/remove.png" alt="×">

        <div class="palette-item-data">
            <input type="hidden" name="mandatory">
            <table class="accordion">
                <tr>
                    <td></td>
                    <td class="dt"><a href="#" title="Form Control Type">Type</a></td>
                    <td class="dd">
                        <select name="control-type">
                            <option value="hidden">Hidden value</option>
                        </select>
                    </td>
                    <td class="dt"><a href="#" title="Control options">Opt.</a></td>
                    <td class="dd">
                        <span>Readonly <input type="checkbox" name="readonly"></span>
                    </td>
                    <td></td>
                </tr>
            </table>
        </div>
    </div>
</script>
<script id="LinkPaletteItem" type="text/html">

    <div class="palette-item" data-linked-type-id="{{object_type_id}}">
        <span class="title">
          Link to  <span class='linked-type-name'>{{display_name}}</span>
            (<span class='link-relation'>{{relationName}}</span>)
        </span>

        <img class="remove" src="<?php echo URL;?>/images/remove.png" alt="×">

        <div class="palette-item-data">
            <table class="accordion">
                <tr>
                    <td></td>

                    <td class="dt"><a href="#" title="Form Control Type">Type</a></td>
                    <td class="dd">
                        <select name="control-type">
                        </select>
                    </td>
                    <td class="dt"><a href="#" title="Control options">Opt.</a></td>
                    <td class="dd">
                        <span>Readonly <input type="checkbox" name="readonly"></span>
                        <span>Mandatory <input type="checkbox" name="mandatory"></span>
                    </td>
                    <td></td>

                </tr>
            </table>
        </div>
    </div>
</script>


<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/editor.main.js"></script>
<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/editor.form.js"></script>



<script>
    $(document).ready(function(){ new FormEditor(); });
</script>