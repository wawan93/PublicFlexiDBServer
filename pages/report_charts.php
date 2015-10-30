<?php
	require CONF_EXT_DIR . '/pChart/class/pData.class.php';
	require CONF_EXT_DIR . '/pChart/class/pDraw.class.php';
	require CONF_EXT_DIR . '/pChart/class/pImage.class.php';

	global $fx_error;

	$schema = $_SESSION['current_schema'];

	if (!$schema) :
		fx_show_metabox(array('body' => array('content' => new FX_Error('show_templates', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));
	else:
	$cur_obj_id = $_GET['object_id']?(int)$_GET['object_id']:-1;
	$set = $_SESSION['current_set'];
	
	$chart_type_id = get_type_id_by_name(0, 'chart');
	$query_type_id = get_type_id_by_name(0, 'query');
	
	if (isset($_GET['object_id']) && (int)$_GET['object_id']) {
		$obj = get_object($chart_type_id, $_GET['object_id']);
 
		if (is_fx_error($obj)) {
			$fx_error -> add('get_chart', $obj -> get_error_message());
		}
		else {
			$data = json_encode($obj);
			$name = $obj['display_name'];

			$query_code = get_object_field($query_type_id, $obj['query_id'], 'code');
	
			if (is_fx_error($query_code)) {
				$fx_error -> add('get_code', $query_code->get_error_message());
			}
			else {
				$code = json_decode($query_code, true);
			
				if ($code !== NULL) {
					foreach($code as $field) {
						$code[] = $field['alias'] ? $field['alias'] : $field['name'];
					}
				}
				else {
					$code = array();
				}
			}
		}
	}
	else {
		$code = array();
		$data = false;
	}

	if (!$fx_error->is_empty()) :
		fx_show_metabox(array('body' => array('content' => $fx_error), 'footer' => array('hidden' => true)));
	else:
	$rand = time();
?>
<div class="leftcolumn">

    <div class="metabox" id="controls">
        <div class="header">
            <div class="icons query"></div>
            <h1>Chart</h1>
        </div>
        <div class="content" id="tasks">
            <label for="chart-name">Chart Name:</label>
            <div class="control">
            	<input type="text" id="chart-name" value="<?php echo $name; ?>">
            </div>
			
            <label for="chart-select">Select Chart:</label>
            <div class="control">
                <select id="chart-select">
                    <option value="-1">New Chart</option>
                     <?php show_select_options(get_objects_by_type($chart_type_id, $schema), 'object_id', 'display_name', (int)$_GET['object_id']); ?>
                </select>
            </div>

            <div class='p-item chart_type'>
            <label for="chart_type_select_input">Chart Type:</label>
            <?php
                $chart_type_fields = get_type_fields($chart_type_id,'custom');
                $chart_type_type_id = $chart_type_fields['chart_type']['type'];
                $chart_type_enum_fields = get_enum_fields($chart_type_type_id);

                $chart_type_select = '<select id="chart_type_select_input" class="field-val">';
                foreach($chart_type_enum_fields as $k => $v) {
                    $chart_type_select .= "<option value='$k'>$v</option>";
                }
                $chart_type_select .= '</select>';

                echo $chart_type_select;
            ?>
                <span class='field-name' style='display:none;' >chart_type</span>
                <span class='field-type' style='display:none;' ><?php echo $chart_type_type_id; ?></span>
            </div>

            <div class="p-item query_id">
            <label for="query_select_input">Query ID:</label>
            <?php
                $query_list = new FX_Query(array(
                        "object_id" => array(
                            'name' => 'object_id',
                            'object_type_id' => $query_type_id
                        ),
                        "display_name" => array(
                            'name' => 'display_name',
                            'object_type_id' => $query_type_id
                        )
                ), 0);
                $query_list->query_object->set_schema_and_set_id($schema,0);
                $query_list = $query_list->get_result();

                $query_list_select = '<select name="value" class="query_select_input field-val">';
                foreach($query_list as $id => $query){
                    $query_list_select .= "<option value='$id'>";
                    $query_list_select .= $query["display_name"];
                    $query_list_select .= '</option>';
                }
                $query_list_select .= '</select>';
                echo $query_list_select;
            ?>
                <span class='field-name' style='display:none;' >query_id</span>
                <span class='field-type' style='display:none;' >INT</span>
            </div>
            <div class="p-item x">
            <label for="XAxis">X-axis</label>
                <select id='XAxis' class='field-val'></select>
                <span class='field-name' style='display:none;' >x</span>
                <span class='field-type' style='display:none;' >varchar</span>
            </div>

        </div>
        <div class="footer">
            <div>
                <input type="button" id="update-preview" class="button blue" value="Update Preview">
                <a href="#" id="goToQuery" class="button blue" title="Edit query">Edit query</a>
            </div>
            <div>
                <input type="button" id="save-button" class="button green" value="Save">
                <input type="button" id="remove-button" class="button red" value="Remove">
            </div>
        </div>
    </div>
    <div class="metabox">
        <div class="header">
            <div class="icons query"></div>
            <h1>Fields</h1>
        </div>
        <div class="content">
            <ul id="palette" class="editor compact"></ul>
        </div>
    </div>
</div>

<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
            <div class="icons chart"></div>
            <h1>Settings</h1>
        </div>
        <div class="content">
            <table id="chart-options"><?php
//                var_dump($chart_type_fields);
                foreach($chart_type_fields as $k=>$v) {
                    if($v['type'] != 'text' && !in_array($k,array(
                            'chart_type', 'query_id', 'x'
                        ))) {
                        echo "<tr class='p-item $k'>";
                        echo "<td>{$v['caption']}<span class='field-name' style='display:none;' >{$k}</span><span class='field-type' style='display:none;' >{$v['type']}</span></td>";
//                        echo '<td>'.$v['name'].'('.$v['type'].') : </td>';
                        if(in_array($k,array('g_solid_color','g_gradient_start','g_gradient_end')) ) {
                            if(isset($_GET['object_id'])) $val = $obj[$k];
                            else $val =  $v['default_value'];

                            echo "<td><input type='text' class='color field-val' value='$val' /></td>";
                        } elseif(is_numeric($v['type'])) {
                            if(isset($_GET['object_id']))
                                $val = $obj[$k];

                            $fields = get_enum_fields($v['type']);

                            echo "<td><select name='$k' class='field-val'>";
                            foreach($fields as $id=>$name) {
                                echo "<option value='$id'>$name</option>";
                            }
                            echo "</select></td>";

                        }  else {
                            if(isset($_GET['object_id'])) $val = $obj[$k];
                            echo "<td><input type='text' name='$k' value='{$v['default_value']}' class='field-val' ></td>";
                        }
                        echo '</tr>';
                    }
                }
            ?></table>
        </div>
        <div class="footer">
        </div>
    </div>

    <div class="metabox">
        <div class="header">
            <div class="icons chart"></div>
            <h1>Series</h1>
        </div>
        <div class="content">
            <ul id="chartEditor" class="editor">

            </ul>
        </div>
        <div class="footer">
        </div>
    </div>
    <div class="metabox">
        <div class="header">
            <div class="icons chart"></div>
            <h1>Result preview</h1>
        </div>
        <div class="content" id="results">
            <?php
                if(isset($_GET['object_id'])) {
                    $src = $chart_type_id . '/' . (int)$_GET['object_id'] . '/chart.png';
                    if(file_exists(CONF_UPLOADS_DIR.'/'.$src)){
                        echo '<img src="'.CONF_UPLOADS_URL.$src.'" id="res_img" alt="" />';
                    } else{
                        echo '<div class="info">';
                        echo '<p>Push "Update Preview"</p> </div>';
                    }
                } else {
                    echo '<div class="info">';
                    echo '<p>Please fill necessary fields and push Update Preview</p> </div>';
                    $src = '';
                }
            ?>

        </div>
        <div class="footer">
        </div>
    </div>

</div>
        <style>
            li.palette-item {
                overflow: inherit;
            }
        </style>

<script id="chartItem" type="text/html">
    <li class="palette-item" data-name="{{alias}}" data-caption="{{caption}}">
    <span class="title">
        <span class='field-name' >{{alias}}</span>
        (<span class='field-type' >{{type}}</span>)
    </span>
        <img class="remove" src="../images/icon_cross.png" alt="x">

        <div class="palette-item-data">
            <table class="accordion">
                <tr>
                    <td class="dt"><a href="">Color</a></td>
                    <td class="dd">
                        <label>
                            <input type="text" name="color" class="color" value="#000000">
                        </label>
                    </td>
                    <td class="dt"><a href="">Width</a></td>
                    <td class="dd">
                        <input type="text" name="width" value="0" placeholder="line width" maxlength="1" style="width:20px;">
                    </td>
                </tr>
            </table>
        </div>
    </li>
</script>

<script type="text/javascript" src="<?php echo CONF_SITE_URL; ?>js/editor.chart.js"></script>
<script>

$(document).ready(function(){
    ich.grabTemplates();
    window.cur_obj_id = <?php echo $_GET['object_id']?(int)$_GET['object_id']:-1; ?>;
    window.url = '<?php echo CONF_SITE_URL; ?>';
    window.chart_type_id = <?php echo $chart_type_id; ?>;
    window.query_type_id = <?php echo $query_type_id; ?>;
    window.x = '<?php echo $obj['x']?$obj['x']:-1; ; ?>';

    var data = <?php echo $data? $data : "undefined"; ?>;
    window.chartEditor = new ChartEditor(
        $('#chart-name'), $('#chart-select'), $("#save-button"),
        $("#remove-button"), $("#palette"), $('#chartEditor'),
        $('.query_select_input'), $('#results'),
        data, 0, 0
    );
});
</script>
<?php endif; ?>
<?php endif; ?>