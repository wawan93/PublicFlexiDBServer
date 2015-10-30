<?php

	$schema = $_SESSION["current_schema"];
	
	if (!$schema) {
		fx_show_metabox(array('body' => array('content' => new FX_Error('empty_data_schema', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));
		return;
	}
	
	$query_type_id = TYPE_QUERY;
	
	if (isset($_GET["object_id"])) {
		$obj = get_object($query_type_id, $_GET["object_id"]);
		if (!is_fx_error($obj)) {
			$data = json_encode($obj);
			$name = $obj["display_name"];
		}
	}
	else {
		$data = false;
	}

    $queryTypes = get_schema_types($schema, "none") + get_schema_types(0, "none");

?>

<div class="leftcolumn">

    <div class="metabox" id="controls">
        <div class="header">
            <div class="icons widgets"></div>
            <h1>Query</h1>
        </div>
        <div class="content" id="tasks">
            <label for="object_name">Query Name:</label>
            <input type="text" id="object_name" value="">

            <label for="object_select">Select Query:</label>
            <select id="object_select">
                <option value="-1">New Query</option>
                <?php show_select_options(get_objects_by_type(TYPE_QUERY, $schema), 'object_id', 'display_name', $object_id); ?>
            </select>

            <label for="type_select">Object Type:</label>
            <select id='type_select'>
            <?php
                foreach(get_schema_types($schema, "none") as $type => $data) {
                    echo '<option value="'.$type.'">'.$data['display_name'].'</option>';
                }


                foreach(get_schema_types(0, "none") as $type => $data) {
                    echo '<option value="'.$type.'" data-system="1">'.$data['display_name'].'</option>';
                }
            ?>
            </select>

            <hr>

            <a href="<?php echo URL;?>design_editor/design_er" id="go-to-er-diagram" class="button blue">ER Diagram</a>
            <a href="<?php echo URL;?>design_editor/design_types" id="go_to_type" class="button blue">Edit type</a>

            <label for="filter_by_set">Filter links by set <input type="checkbox" id="filter_by_set" name="filter_by_set"></label>
            <label for="hide_system_types">Hide system types <input type="checkbox" id="hide_system_types" ></label>
            <label for="hide_empty"> Hide empty joins <input id="hide_empty" type="checkbox"></label>


            <div id="joins"></div>
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
            <select id="tabsForTypes"></select>
            <hr>
            <ul id="fields" class="editor compact"></ul>
            <p id="there_is_no_joined_fields">
                There is no joined fields.
            </p>
        </div>
    </div>
</div>

<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
            <div class="icons widgets"></div>
            <h1>Query editor</h1>
        </div>
        <div class="content">
            <ul id="stack" class="query_editor editor"></ul>
        </div>
        <div class="footer">
        </div>
    </div>
    <div class="metabox">
        <div class="header">
            <div class="icons widgets"></div>
            <h1>Result preview</h1>
        </div>
        <div class="content" id="results">

            <div class="object-explorer">
                <a href="" id="csv" target="_blank">Download CSV</a>

                <table id="results-table"></table>
                <div class="nav-bar" id="query_navigation">
                    <div class="items" id="total"></div>

                    <div class="navigation">
                        <a href="#" class="prev">Prev</a>
                        <div class="pages" style="display:inline-block;"></div>
                        <a href="#" class="next">Next</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?php echo URL;?>js/editor.main.js"></script>
<script type="text/javascript" src="<?php echo URL;?>js/editor.query.js"></script>

<script id="QueryPaletteItem" type="text/html">
    <div class="palette-item" data-name="{{name}}" data-type="{{object_type}}"  data-parent-type="{{parent_type}}"  style="background: {{color}};">

    <span class="title">
        <span class='field-name'>{{name}}</span>
        (<span class='field-type'>{{type}}</span>)
    </span>
        <img class="remove" src="<?php echo URL;?>images/remove.png" alt="×">

        <div class="palette-item-data">
            <table class="accordion">
                <tr>

                    <td></td>
                    <td class="dt">
                        <a href="">Label</a>
                    </td>

                    <td class="dd">
                        <input name="caption" type="text" placeholder="" value="{{caption}}">
                    </td>

                    <td class="dt">
                        <a href="">Alias</a>
                    </td>

                    <td class="dd">
                        <input name="alias" type="text" placeholder="" value="{{name}}">
                    </td>

                    <td class="dt">
                        <a href="">Order</a>
                    </td>

                    <td class="dd">
                        <select name="order" style="/*width: 75px*/">
                            <option value="none">none</option>
                            <option value="asc">asc</option>
                            <option value="desc">desc</option>
                        </select>
                    </td>

                    <td class="dt">
                        <a href="">Aggr.</a>
                    </td>

                    <td class="dd">
                        <select name="aggregation">
                            <option value="">No aggregation</option>
                            <option value="count">Count</option>
                            <option value="sum">Sum</option>
                            <option value="min">Min</option>
                            <option value="max">Max</option>
                            <option value="avg">Avg</option>
                            <option value="stddev">Stddev</option>
                        </select>
                    </td>

                    <td class="dt">
                        <a href="">Filter</a>
                    </td>

                    <td class="dd">
                        <input name="criteria" placeholder="">
                    </td>
                    <td></td>

                </tr>
            </table>
            <input type="hidden" name="parent_type" value="{{parent_type}}">
            <input type="hidden" name="object_type_id" value="{{object_type_id}}">
        </div>
    </div>
</script>
<script language="javascript">
    $(document).ready(function () {
        var types = <?php echo json_encode($queryTypes); ?>;
        new QueryEditor({ types: types });
    });
</script>