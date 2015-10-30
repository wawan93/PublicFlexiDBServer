<?php
/*
Widget Name: Chart Widget
Description: Chart Widget Description
Version: 0.0.1
Author: FlexiLogin
Author URL: http://FlexiLogin.com
License: GPLv2 or later
*/

class Widget_Chart extends FX_Widget
{
        function widget_default_style()
        {
            $out = array(
            );
            
            return $out;
        }
        
        function global_widget_style($args)
        {
            $out = '.widget_content_'.$this->widget_class.' {
                        width:100%;
                        height:100%;
                    }
                    .widget_content_'.$this->widget_class.' img {
                        width:100%;
                        position: relative;
                        top: 50%;
                        transform: translateY(-50%);
                    }';
            return $out;
        }
    
        function widget_style($args)
        {
                return $out;
        }
        
        function widget_pdf($widget_code)
        {
            global $pdf;
            
            $chart_id = $widget_code['options']['widget_options']['chart_id'];
            
            $chart_dir = CONF_UPLOADS_DIR
                    . '/' . get_type_id_by_name(0, 'chart')
                    . '/' . $chart_id . '/chart.jpg';
            
            $widget_sizeX = $widget_code['widget_dimensions']['size_x'];
            $widget_sizeY = $widget_code['widget_dimensions']['size_y'];
            $widget_posX = $widget_code['widget_dimensions']['pos_x'];
            $widget_posY = $widget_code['widget_dimensions']['pos_y'];
            
            switch ($widget_code['options']['widget_options']['hor_align']) {
                case 'left':
                    $H = 'L';
                    break;
                case 'cent':
                default:
                    $H = 'C';
                    break;
                case 'right':
                    $H = 'R';
                    break;
            }
            switch ($widget_code['options']['widget_options']['vert_align']) {
                case 'top':
                    $V = 'T';
                    break;
                case 'cent':
                default:
                    $V = 'M';
                    break;
                case 'bot':
                    $V = 'B';
                    break;
            }
            
            $border = array('LRTB'=>array('width'=>0.1));
            
            //$pdf->SetXY($widget_posX, $widget_posY);
            //$pdf->Cell($widget_sizeX, $widget_sizeY, "", $border);
            
            //$pdf->SetXY($widget_posX+1, $widget_posY+1);    //Move inwards of widget cell
            //$pdf->Cell($widget_sizeX-2, $widget_sizeY-2, "", $border);  //Smaller inner cell for table container
            
            $pdf->SetXY($widget_posX+1, $widget_posY+1);
            $pdf->Image($chart_dir, $widget_posX+1, $widget_posY+1, $widget_sizeX-2, $widget_sizeY-2, '', '', '', false, '', '', false, false, 0, $H.$V, false, false);
        }
        
        
        
        
        function content_resize_fn()
        {
            $out = '
                    var wc = $($element).width();
                    var hc = $($element).height();
                    var rc = wc/hc;
                    var wi = widget.options.widget_options.g_w;
                    var hi = widget.options.widget_options.g_h;
                    var ri = wi/hi;
                    if (ri<rc) {
                        $($element).find("img").css("height", "100%");
                        $($element).find("img").css("width", "auto");
                    } else if (ri>rc) {
                        $($element).find("img").css("height", "auto");
                        $($element).find("img").css("width", "100%");
                    } else if (ri === rc) {
                        $($element).find("img").css("height", "100%");
                        $($element).find("img").css("width", "100%");
                    }
                    var containerSizeX = widget.sizeX;
                    var containerSizeY = widget.sizeY;
                    $($element).find(".img-info-window").html("Container dimension ratio (width x height): "+containerSizeX+" x "+containerSizeY);
                    ';
            return $out;
        }
        
        function content_resize_stop_fn()
        {
            return 'window.setTimeout( function () {'
                    .$this->content_resize_fn().
                    '}, 300);';
        }
        
        
        
        
        function content_header($args)
        {
                $wc = $this->gridster_options['sizeX'];
                $hc = $this->gridster_options['sizeY'];
                $rc = $wc/$hc;
                
                $wi = $args['g_w'];
                $hi = $args['g_h'];
                $ri = $wi/$hi;
                
                switch ($args['vert_align']) {
                    case 'top':
                        $Y = 0;
                        break;
                    case 'cent':
                    default:
                        $Y = 50;
                        break;
                    case 'bot':
                        $Y = 100;
                        break;
                }
                switch ($args['hor_align']) {
                    case 'left':
                        $X = 0;
                        break;
                    case 'cent':
                        $X = 50;
                        break;
                    case 'right':
                        $X = 100;
                        break;
                }
                $alignment = 'position: relative; left: '.$X.'%; top: '.$Y.'%; -webkit-transform: translate(-'.$X.'%, -'.$Y.'%); -ms-transform: translate(-'.$X.'%, -'.$Y.'%); transform: translate(-'.$X.'%, -'.$Y.'%);';
                
                if ($ri<$rc) {
                    $style = 'height:100%; width: auto;';
                } else if ($ri>$rc) {
                    $style = 'height:  auto; width:100%;';
                } else if ($ri===$rc) {
                    $style = 'height:100%; width:100%;';
                }
                
                $out = '<style>
                            #'.$this->widget_id.'  img {'.$style.$alignment.'}
                        </style>';
                return $out;
        }
        
	function content($args)
	{
                extract($args);
                
                $url = CONF_UPLOADS_URL
                    . get_type_id_by_name(0, 'chart')
                    . '/' . $chart_id . '/chart.png';
                $out = "<img src='$url'/>";
		return $out;
	}
        
        function content_footer($args)
        {
                return "";
        }
        
        
        
        
        function style_options($args)
        {
            $out = "<h2>Chart style options</h2>";
            return $out;
        }
        
        function options_header($args)
        {
                $out = '<div class="widget-options-header">
                            <script type="text/javascript">
                                function returnOptions() {
                                    
                                    var chart_id = $("#chart-select").val();
                                    var g_w = $("#chart-preview-"+chart_id).data("width");
                                    var g_h = $("#chart-preview-"+chart_id).data("height");
                                    
                                    var vertAlign = $("input[name=vert-align]:checked").val();
                                    var horAlign = $("input[name=hor-align]:checked").val();

                                    var options = {
                                        widget_id: "'.$this->widget_id.'",
                                        widget_class: "'.$this->widget_class.'",
                                        widget_name: "'.$this->widget_name.'",
                                        schema: "'.$this->schema.'",
                                        mode: "content",
                                        gridster_options: '.json_encode($this->gridster_options).',
                                        widget_options: {
                                                        chart_id: chart_id,
                                                        g_w: g_w,
                                                        g_h: g_h,
                                                        vert_align: vertAlign,
                                                        hor_align: horAlign
                                                        }
                                    };
                                    
                                    return options;
                                }
                                
                                function showChart() {
                                    var currentChart = $("#chart-select").val();
                                    $(".chart-preview").each(function () {
                                        $(this).css("display", "none");
                                    });
                                    $("#chart-preview-"+currentChart).css("display", "block");
                                }
                                
                                $(document).ready(function() {
                                    showChart();
                                });
                            </script>

                            <style>
                                .chart-preview {width: 100%}
                                .chart-preview img {width: 100%}
                            </style>

                        </div>';
                return $out;
        }
        
	function options($args)
	{
                extract($args);
                
                foreach (array('top', 'cent', 'bot') as $va) {
                    if ($vert_align == $va) {
                        $valign[$va] = "checked";
                    } else {
                        $valign[$va] = "";
                    }
                }
                foreach (array('left', 'cent', 'right') as $ha) {
                    if ($hor_align == $ha) {
                        $halign[$ha] = "checked";
                    } else {
                        $halign[$ha] = "";
                    }
                }
                if (!(array_filter($valign))) $valign['cent'] = "checked";
                if (!(array_filter($halign))) $halign['cent'] = "checked";
                
                $chart_type = get_type_id_by_name(0,'chart');
                $charts = get_objects_by_type($chart_type, $this->schema);
                
                foreach ($charts as $chart) {
                    $url = CONF_UPLOADS_URL
                        . $chart_type
                        . '/' . $chart['object_id'] . '/chart.png';
                    $img_divs .=    "<div class='chart-preview' id='chart-preview-".$chart['object_id']."' data-width='".$chart['g_width']."' data-height='".$chart['g_height']."'>
                                        <img src='$url'/>
                                    </div>";
                }
                
                $chart_list_select = '<select name="chart-select" id="chart-select" onchange="showChart();">';
                $chart_list_select .= show_select_options(
                    $charts,
                    'object_id',
                    'display_name', $chart_id, false
                );
                $chart_list_select .= '</select>';
                
                $out = '<div class="widget-options-content">
                            <label for="chart-select">Select chart: </label>
                            '.$chart_list_select.'
                            <br>
                            <div id="align-options">
                                <label>Vertical alignment:</label>
                                <input type="radio" name="vert-align" id="v1" value="top" '.$valign['top'].' /><label for="v1">Top</label>
                                <input type="radio" name="vert-align" id="v2" value="cent" '.$valign['cent'].' /><label for="v2">Centre</label>
                                <input type="radio" name="vert-align" id="v3" value="bot" '.$valign['bot'].' /><label for="v3">Bottom</label>
                                <br>
                                <label>Horizontal alignment:</label>
                                <input type="radio" name="hor-align" id="h1" value="left" '.$halign['left'].' /><label for="h1">Left</label>
                                <input type="radio" name="hor-align" id="h2" value="cent" '.$halign['cent'].' /><label for="h2">Centre</label>
                                <input type="radio" name="hor-align" id="h3" value="right" '.$halign['right']. '/><label for="h3">Right</label>
                            </div>
                            <br>
                            '.$img_divs.'
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