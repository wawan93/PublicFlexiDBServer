<?php
/*
Widget Name: Query Widget
Description: Query Widget Description
Version: 0.0.1
Author: FlexiLogin
Author URL: http://FlexiLogin.com
License: GPLv2 or later
*/

class Widget_Query extends FX_Widget
{
        function widget_default_style()
        {
            $out = array(
                "th_bg"=>array("rgb"=>"rgb(255, 255, 255)", "hex"=>"#ffffff"),
                "th_col"=>array("rgb"=>"rgb(0, 0, 0)", "hex"=>"#000000"),
                "td_bg"=>array("rgb"=>"rgb(255, 255, 255)", "hex"=>"#ffffff"),
                "td_col"=>array("rgb"=>"rgb(0, 0, 0)", "hex"=>"#000000"),
                "bor_col"=>array("rgb"=>"rgb(0, 0, 0)", "hex"=>"#000000"),
                "bor_size"=>"1",
                "font_size"=>"14",
                "title_size"=>"16"
            );
            
            return $out;
        }
        
        function global_widget_style($args)
        {
            $out = '.widget_content_'.$this->widget_class.' {
                            display:flex;
                            flex:1;
                            flex-flow:column;
                            width:100%;
                        }
                        .widget_content_'.$this->widget_class.' table {
                            height:100%;
                            width:100%;
                            overflow:hidden;
                        }
                        .widget_content_'.$this->widget_class.' td, th {
                            border: 1px solid black;
                            padding: 7px;
                        }
                        .widget_content_'.$this->widget_class.' div.query_name {
                            padding:10px;
                            text-align: center;
                            white-space: nowrap;
                            overflow: hidden;
                        }
                        .widget_content_'.$this->widget_class.' div.table_div {
                            flex:1;
                            overflow:hidden;
                        }
                        .widget_content_'.$this->widget_class.' .query_name {
                            font-size: '.$args['title_size'].'px;
                            font-weight: bold;
                        }
                        .widget_content_'.$this->widget_class.' th, widget_content_'.$this->widget_class.' td {
                            font-size: '.$args['font_size'].'
                        }
                        .widget_content_'.$this->widget_class.' th {
                            background-color: '.$args['th_bg']['rgb'].'
                        }
                        .widget_content_'.$this->widget_class.' th {
                            color: '.$args['th_col']['rgb'].'
                        }
                        .widget_content_'.$this->widget_class.' td {
                            background-color: '.$args['td_bg']['rgb'].'
                        }
                        .widget_content_'.$this->widget_class.' td {
                            color: '.$args['td_col']['rgb'].'
                        }
                        .widget_content_'.$this->widget_class.' {
                            font-size: '.$args['font_size'].'px
                        }
                        .widget_content_'.$this->widget_class.' th, .widget_content_'.$this->widget_class.' td {
                            border-color: '.$args['bor_col']['rgb'].'
                        }
                        .widget_content_'.$this->widget_class.' th, .widget_content_'.$this->widget_class.' td {
                            border-width: '.$args['bor_size'].'px
                        }';
            return $out;
        }
        //Called for local/widget style
        function widget_style($args)
        {
                $out = '<style>
                            #'.$this->widget_id.' .query_name {
                                font-size: '.$args['title_size'].'px
                            }
                            #'.$this->widget_id.' th, #'.$this->widget_id.' td {
                                font-size: '.$args['font_size'].'px
                            }
                            #'.$this->widget_id.' th {
                                background-color: '.$args['th_bg']['rgb'].'
                            }
                            #'.$this->widget_id.' th {
                                color: '.$args['th_col']['rgb'].'
                            }
                            #'.$this->widget_id.' td {
                                background-color: '.$args['td_bg']['rgb'].'
                            }
                            #'.$this->widget_id.' td {
                                color: '.$args['td_col']['rgb'].'
                            }
                            #'.$this->widget_id.' th, #'.$this->widget_id.' td {
                                border-color: '.$args['bor_col']['rgb'].'
                            }
                            #'.$this->widget_id.' th, #'.$this->widget_id.' td {
                                border-width: '.$args['bor_size'].'px
                            }
                        </style>';
                return $out;
        }
        
        function widget_pdf($widget_code)
        {
            global $pdf;
            
            $widget_sizeX = $widget_code['widget_dimensions']['size_x'];
            $widget_sizeY = $widget_code['widget_dimensions']['size_y'];
            $widget_posX = $widget_code['widget_dimensions']['pos_x'];
            $widget_posY = $widget_code['widget_dimensions']['pos_y'];
            
            $tablePaddingX = 1;
            $pdf->setCellPaddings('', $tablePaddingX, '', $tablePaddingX);
            
            
            //Set colours
            $report_options = $pdf->get_report_options();
            $style_type = $widget_code['options']['style_type'];
            if ($style_type === "global") {
                $style = $report_options[$this->widget_class];
            } else {
                $style = $widget_code['options']['style'];
            }
            
            list($bor_col['r'], $bor_col['g'], $bor_col['b']) = str_replace("rgb(", "", str_replace(")", "", explode(", ", $style['bor_col']['rgb'])));
            $border_width = $style['bor_size']/10;
            $border_table = array('LRTB'=>array('width'=>$border_width, 'color'=>array($bor_col['r'],$bor_col['g'],$bor_col['b'])));
            $border_widget = array('LRTB'=>array('width'=>0.1));
            
            //Get font size in pt - there is a difference between getFontSize() and getFontSizePt()...check carefully when adding individual fonts
            $pdf->SetFont('times', '', $pdf->pixelsToUnits($style['font_size']));
            $font_size = $pdf->getFontSizePt();
            
            list($th_fill['r'], $th_fill['g'], $th_fill['b']) = explode(", ", str_replace("rgb(", "", str_replace(")", "", $style['th_bg']['rgb'])));
            list($th_text['r'], $th_text['g'], $th_text['b']) = explode(", ", str_replace("rgb(", "", str_replace(")", "", $style['th_col']['rgb'])));
            
            list($td_fill['r'], $td_fill['g'], $td_fill['b']) = explode(", ", str_replace("rgb(", "", str_replace(")", "", $style['td_bg']['rgb'])));
            list($td_text['r'], $td_text['g'], $td_text['b']) = explode(", ", str_replace("rgb(", "", str_replace(")", "", $style['td_col']['rgb'])));
            
            
            $data_rows = array();
            $query_id = $widget_code['options']['widget_options']['query_id'];
            $fields = $widget_code['options']['widget_options']['fields'];
            $query = get_object(TYPE_QUERY, $query_id);
            
            //Put all table data in array
            $query_name = $query['display_name'];
            
            $query_object = exec_fx_query((int)$query_id);
            if (count($fields)>0) {
                $field_row = array();
                foreach ($fields as $field) {
                    array_push($field_row, $field);
                }
                array_push($data_rows, $field_row);
                if ($query_object) {
                    foreach ($query_object as $object) {
                        $object_row = array();
                        foreach ($fields as $field) {
                            if (array_key_exists($field, $object)) {
                                array_push($object_row, $object[$field]);
                            }
                        }
                        array_push($data_rows, $object_row);
                    }
                }
            }
            
            //Put data in column format
            $table_cols = max(array_map('count', $data_rows));
            $data_columns = array();
            for ($i=0; $i<$table_cols; $i++) {
                $data_columns[$i] = array();
                foreach ($data_rows as $r) {
                    array_push($data_columns[$i], $r[$i]);
                }
            }
            
           
            //Find column width
            $col_widths = array();
            foreach ($data_columns as $c) {
                $col_w = array('size'=>0, 'entry'=>'');
                foreach ($c as $entry) {
                    if (strlen($entry) > $col_w['size']) {
                        $col_w['size'] = strlen($entry);
                        $col_w['entry'] = $entry;
                    }
                }
                $col_w['cell_width'] = $pdf->GetStringWidth($col_w['entry'], 'times', '', $font_size, false) + 2*$tablePaddingX;
                $col_w['cell_height'] = $pdf->getStringHeight($col_w['cell_width'], $col_w['entry'], false, false, 1, 'LTRB');
                array_push($col_widths, $col_w);
            }
            
            
            
            
            
            $pdf->SetXY($widget_posX+1, $widget_posY+1);
            $pdf->StartTransform();
            $pdf->Rect($widget_posX+1, $widget_posY+1, $widget_sizeX-2, $widget_sizeY-2, 'CNZ');
            
            $col_num = count($col_widths);
            foreach ($col_widths as $w) {
                $header_width += $w['cell_width'];
                $table_height += $w['cell_height'];
            }
            if ($header_width<($widget_sizeX-2)) {
                $header_width=$widget_sizeX-2;
                for ($i=0; $i<$col_num; $i++) {
                    $col_widths[$i]['cell_width'] = $header_width/$col_num;
                }
            }
            
            
            $pdf->setCellPaddings('', $tablePaddingX, '', $tablePaddingX+1);
            $pdf->SetFont('times', 'B', $pdf->pixelsToUnits($style['title_size']));
            $header_height = $pdf->getStringHeight($header_width, $query_name, false, true, 0, '');
            $pdf->Cell($header_width, $header_height, $query_name, '', 0, "C", false);
            $pdf->SetFont('times', '', $pdf->pixelsToUnits($style['font_size']));
            $pdf->setCellPaddings('', $tablePaddingX, '', $tablePaddingX);
            
            $row_num = count($data_columns["0"]);
            if (($header_height + $table_height)<($widget_sizeY-2)) {
                for ($i=0; $i<$col_num; $i++) {
                    $col_widths[$i]['cell_height'] = ($widget_sizeY-2-$header_height)/$row_num;
                }
            }
            
            
            foreach ($data_columns as $col_num=>$c) {
                $column_cell_num = 0;
                foreach ($c as $cell_num=>$cell_text) {
                    if($cell_num===0) {
                        $pdf->SetFillColor($th_fill['r'], $th_fill['g'], $th_fill['b']);
                        $pdf->SetTextColor($th_text['r'], $th_text['g'], $th_text['b']);
                        $alignment = "C";
                        $pdf->SetFont('times', 'B', $pdf->pixelsToUnits($style['font_size']));
                    } else {
                        $pdf->SetFillColor($td_fill['r'], $td_fill['g'], $td_fill['b']);
                        $pdf->SetTextColor($td_text['r'], $td_text['g'], $td_text['b']);
                        $alignment = "L";
                        $pdf->SetFont('times', '', $pdf->pixelsToUnits($style['font_size']));
                    }
                    $cursor_posX = $widget_posX + 1;
                    for ($i=0; $i<$col_num; $i++) {
                        $cursor_posX += $col_widths[$i]['cell_width'];
                    }
                    $cursor_posY = $widget_posY + 1 + $column_cell_num*$col_widths[$col_num]['cell_height'] + $header_height;
                    $pdf->SetXY($cursor_posX, $cursor_posY);
                    $cell_width = $col_widths[$col_num]['cell_width'];
                    $cell_height = $col_widths[$col_num]['cell_height'];
                    $pdf->Cell($cell_width, $cell_height, $cell_text, $border_table, 0, $alignment, true);
                    $column_cell_num++;
                }
            }
            $pdf->StopTransform();
        }
        
        
        
        
        function content_resize_fn()
        {
            $out = '
                    console.debug("Query resize function");
                    ';
            return $out;
        }
        
        
        
        
        function content_header($args)
        {
                if ((isset($this->style)) && ($this->style_type === "widget")) {
                    $out = $this->widget_style($this->style);
                }
            
                return $out;
        }
        
	function content($args)
	{
                extract($args);
                
                $query = get_object(TYPE_QUERY, $query_id);
                $query_name = $query['display_name'];
                
                $out = '<div class="query_name">'.$query_name.'</div>';
                
                $query_object = exec_fx_query((int)$query_id);
                $out .= '<div class="table_div">
                            <table>';
                if ($query_object) {
                    //Field labels
                    $out .= '<tr>';
                    if ($fields) {
                        foreach ($fields as $field) {
                            $out .= '<th>'.$field.'</th>';
                        }
                    }
                    else {
                        $out .= 'No fields selected.';
                    }
                    $out .= '</tr>';
                    //Objects
                    foreach ($query_object as $object) {
                        $out .= '<tr>';
                        foreach ($fields as $field) {
                            if (array_key_exists($field, $object)) {
                                $out .= '<td>'.$object[$field].'</td>';
                            }
                        }
                        $out .= '</tr>';
                    }
                } else {
                    $out .= '<tr><td>No query objects found...</td></tr>';
                }
                $out .= '</table></div>';
                
		return $out;
	}
        
        function content_footer($args)
        {
                return "";
        }
        
        
        
        
        function style_options($args)
        {
                $style_type = $this->style_type;
                if ($style_type === 'global') {
                    $checked = "checked";
                }
                
                
                $th_bg = $args['th_bg']['hex'];
                $th_col = $args['th_col']['hex'];
                $td_bg = $args['td_bg']['hex'];
                $td_col = $args['td_col']['hex'];
                $bor_col = $args['bor_col']['hex'];
                $bor_size = $args['bor_size'];
                $font_size = $args['font_size'];
                $title_size = $args['title_size'];
                
                if ($this->style) {
                    $reset_btn = '<label>Use global: <input id="global-input" type="checkbox" onchange="toggleGlobal();" '.$checked.'/></label>';
                }
                $font_selects = '
                    <label for="table_font_size">Font size:</label>
                    <select  id="table_font_size" name="table_font_size">';
                for ($i=5; $i<=25; $i++) {
                    $selected = ($i==$font_size) ? "selected" : "";
                    $font_selects .= '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
                }
                $font_selects .= '</select>
                                  <br>
                                  <label for="table_title_size">Title size:</label>
                                  <select  id="table_title_size" name="table_font_size">';
                for ($i=5; $i<=25; $i++) {
                    $selected = ($i==$title_size) ? "selected" : "";
                    $font_selects .= '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
                }
                $font_selects .= '</select><br>';
                

                $out .= '
                        <script src="'.CONF_SITE_URL.'js/jquery.minicolors.js"></script>
                        <link rel="stylesheet" href="'.CONF_SITE_URL.'style/jquery.miniColors.css">
                        <script type="text/javascript">
                            function '.$this->widget_class.'_styles() {
                                var th_bg = {
                                                rgb: $("#table_th_bg").minicolors("rgbString"),
                                                hex: $("#table_th_bg").minicolors("value")
                                            };
                                var th_col = {
                                                rgb: $("#table_th_text").minicolors("rgbString"),
                                                hex: $("#table_th_text").minicolors("value")
                                            };
                                var td_bg = {
                                                rgb: $("#table_td_bg").minicolors("rgbString"),
                                                hex: $("#table_td_bg").minicolors("value")
                                            };
                                var td_col = {
                                                rgb: $("#table_td_text").minicolors("rgbString"),
                                                hex: $("#table_td_text").minicolors("value"),
                                            };
                                var bor_col = {
                                                rgb: $("#table_bor_col").minicolors("rgbString"),
                                                hex: $("#table_bor_col").minicolors("value")
                                            };

                                var bor_size = $("#table_bor_size").val();
                                var font_size = $("#table_font_size").val();
                                var title_size = $("#table_title_size").val();

                                var table_style = {
                                                    th_bg: th_bg,
                                                    th_col: th_col,
                                                    td_bg: td_bg,
                                                    td_col: td_col,
                                                    bor_col: bor_col,
                                                    bor_size: bor_size,
                                                    font_size: font_size,
                                                    title_size : title_size
                                                    };
                                return table_style;
                            }
                            $(document).ready(function() {
                                $("#table_th_bg, #table_th_text, #table_td_bg, #table_td_text, #table_bor_col").minicolors({
                                    change: function() {
                                        console.log("Report and widget function");
                                    }
                                });';
                    if ($style_type === 'global') {
                        $out .= 'toggleGlobal();';
                    }
                    $out .= '});

                            function toggleGlobal() {
                                if ($("#global-input").prop(\'checked\')) {
                                    var report_options = parent.angular.element("#GrdisterCtrl").scope().report_options;
                                    $("#table_th_bg").minicolors("value", report_options.'.$this->widget_class.'.th_bg.hex).prop(\'disabled\', true);
                                    $("#table_th_text").minicolors("value", report_options.'.$this->widget_class.'.th_col.hex).prop(\'disabled\', true);
                                    $("#table_td_bg").minicolors("value", report_options.'.$this->widget_class.'.td_bg.hex).prop(\'disabled\', true);
                                    $("#table_td_text").minicolors("value", report_options.'.$this->widget_class.'.td_col.hex).prop(\'disabled\', true);
                                    $("#table_bor_col").minicolors("value", report_options.'.$this->widget_class.'.bor_col.hex).prop(\'disabled\', true);
                                    $("#table_bor_size").val(report_options.'.$this->widget_class.'.bor_size).prop(\'disabled\', true);
                                    $("#table_font_size").val(report_options.'.$this->widget_class.'.font_size).prop(\'disabled\', true);
                                    $("#table_title_size").val(report_options.'.$this->widget_class.'.title_size).prop(\'disabled\', true);
                                } else {
                                    $("#table_th_bg, #table_th_text, #table_td_bg, #table_td_text, #table_bor_col, #table_bor_size, #table_font_size, #table_title_size").prop(\'disabled\', false);
                                }
                            }

                        </script>
                        
                        <input type="text" id="table_th_bg" name="table_th_bg" style="width: 65px; height: 22px;" value="'.$th_bg.'">
                        <label for="table_th_bg">Table header background</label>
                        <br>
                        <input type="text" id="table_th_text" name="table_th_text" style="width: 65px; height: 22px;" value="'.$th_col.'">
                        <label for="table_th_text">Table header text</label>
                        <br>
                        <input type="text" id="table_td_bg" name="table_td_bg" style="width: 65px; height: 22px;" value="'.$td_bg.'">
                        <label for="table_td_bg">Table cell background</label>
                        <br>
                        <input type="text" id="table_td_text" name="table_td_text" style="width: 65px; height: 22px;" value="'.$td_col.'">
                        <label for="table_td_text">Table cell text</label>
                        <br>
                        <input type="text" id="table_bor_col" name="table_bor_col" style="width: 65px; height: 22px;" value="'.$bor_col.'">
                        <label for="table_bor_col">Table border colour</label>
                        <br>
                        <label for="table_bor_size">Table border size (px):</label>
                        <select  id="table_bor_size" name="table_bor_size">';
                for ($i=0; $i<=5; $i++) {
                    $selected = ($i==$bor_size) ? "selected" : "";
                    $out .= '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
                }
                $out .= '</select>
                        <br>';
                $out .= $font_selects;
                $out .= '<br>'.$reset_btn;

                return $out;
        }
        
        function options_header($args)
        {
                $out = '<div class="widget-options-header">
                            
                            <script type="text/javascript">
                            
                                $(document).ready(function() {
                                    $("#table_th_bg, #table_th_text, #table_td_bg, #table_td_text, #table_bor_col").minicolors({
                                        //****
                                        //This overrides the other change fn
                                        //****
                                        change: function() {
                                        }
                                    });
                                });
                                
                                function returnOptions() {
                                    var queryID = $("#query_id_select").val();
                                    var fields = [];
                                    $("#query_"+queryID+"_fields").find("input[type=checkbox]:checked").each(function () {
                                        fields.push($(this).val());
                                    });
                                    
                                    var style_type = ($("#global-input").prop(\'checked\')) ? "global" : "widget";
                                    
                                    var options = {
                                        widget_id: "'.$this->widget_id.'",
                                        widget_class: "'.$this->widget_class.'",
                                        widget_name: "'.$this->widget_name.'",
                                        schema: "'.$this->schema.'",
                                        mode: "content",
                                        gridster_options: '.json_encode($this->gridster_options).',
                                        style: '.$this->widget_class.'_styles(),
                                        style_type: style_type,
                                        widget_options: {
                                                        query_id: queryID,
                                                        fields: fields
                                                        }
                                    };
                                    return options;
                                }
                                
                                function changeQuery() {
                                    var queryID = $("#query_id_select").val();
                                    $("#query_"+queryID+"_fields").css("display", "block");
                                    $("#query_"+queryID+"_fields").siblings("div").css("display", "none");
                                }
                                
                                function changeStyleDisplay(el) {
                                    if ($(el).is(":checked")) {
                                        $("#query-style-options").css("display", "block");
                                    } else {
                                        $("#query-style-options").css("display", "none");
                                    }
                                }
                                
                                $(document).ready(function() {
                                    changeQuery();
                                    $("#tabs").tabs();
                                });
                            </script>
                        </div>';
                return $out;
        }
        
	function options($args)
	{
                $queries = get_objects_by_type(TYPE_QUERY, $this->schema);
                
                $basic .=  '
                        <label for="query_id_select">Select query:</label>
                        <select name="query_id_select" id="query_id_select" onchange="changeQuery();">
                        '.show_select_options($queries, 'object_id', 'display_name', $args['query_id'], false).'
                        </select>
                        <br>
                        <label>Select fields to show:</label>
                        <br>
                ';
                foreach ($queries as $query) {
                    if ($query['object_id']===$args['query_id']) {
                        if ($args['fields']) {
                            $selected = "some";
                        } else {
                            $selected = "none";
                        }
                    } else {
                        $selected = "all";
                    }
                    $fields = json_decode($query['code'], true);
                    $basic .= '<div id="query_'.$query['object_id'].'_fields" style="display: none">';
                    foreach ($fields as $field) {
                        if ($selected === "some") {
                            if (in_array($field['alias'], $args['fields'])) {
                                $selected_prop = "checked";
                            } else {
                                $selected_prop = "";
                            }
                        } else if ($selected === "none") {
                            $selected_prop = "";
                        } else if ($selected === "all") {
                            $selected_prop = "checked";
                        }
                        $basic .= '<label><input type="checkbox" value="'.$field['alias'].'" '.$selected_prop.'>'.$field['caption'].'</label><br>';
                    }
                    $basic .= '</div>';
                }
                
                
                
                $out = '<div id="tabs">
                            <ul>
                                <li><a href="#tabs-1">Basic</a></li>
                                <li><a href="#tabs-2">Style</a></li>
                            </ul>
                            <div id="tabs-1">
                                '.$basic.'
                            </div>
                            <div id="tabs-2">
                                '.$this->style_options($this->style).'
                            </div>
                        </div>';
                
		return $out;
	}
        
        function options_footer($args)
        {
                $out = '<div class="widget-options-footer">
                        </div>';
                return $out;
        }
}