<?php
/*
Widget Name: Image Widget
Description: Image Widget Description
Version: 0.0.1
Author: FlexiLogin
Author URL: http://FlexiLogin.com
License: GPLv2 or later
*/

class Widget_Image extends FX_Widget
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
                        height:100%;
                        width:100%;
                    }
                    .widget_header_'.$this->widget_class.' .img-info-window{
                        position: absolute;
                        display: none;
                        left: 10px;
                        bottom: 10px;
                        text-align: left;
                        padding: 5px;
                        background-color: black;
                        color: red;
                        z-index: 5;
                    }';
            return $out;
        }
        
        function widget_style($args)
        {
                $out = '';
                        
                return $out;
        }
        
        function widget_pdf($widget_code)
        {
            global $pdf;
            
            $widget_sizeX = $widget_code['widget_dimensions']['size_x'];
            $widget_sizeY = $widget_code['widget_dimensions']['size_y'];
            $widget_posX = $widget_code['widget_dimensions']['pos_x'];
            $widget_posY = $widget_code['widget_dimensions']['pos_y'];
            
            $image_path = $widget_code['options']['widget_options']['img_path'];
            
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
            
            //PNG does not work for some reason - convert it to jpg
            $file_ext = pathinfo($image_path, PATHINFO_EXTENSION);
            if (strtolower($file_ext) === 'png') {
                $image = imagecreatefrompng($image_path);
                $outputFile = CONF_UPLOADS_DIR.'/temp/'.time().'pngToJpegConv.jpg';
                imagejpeg($image, $outputFile, 50);
                $pdf->SetXY($widget_posX+1, $widget_posY+1);
                $pdf->Image($outputFile, $widget_posX+1, $widget_posY+1, $widget_sizeX-2, $widget_sizeY-2, '', '', '', false, '', '', false, false, 0, $H.$V, false, false);
                imagedestroy($image);
            } else {
                $pdf->SetXY($widget_posX+1, $widget_posY+1);
                $pdf->Image($image_path, $widget_posX+1, $widget_posY+1, $widget_sizeX-2, $widget_sizeY-2, '', '', '', false, '', '', false, false, 0, $H.$V, false, false);
            }
        }
        
        
        
        
        function content_resize_fn()
        {
            $out = '
                    var wc = $($element).width();
                    var hc = $($element).height();
                    var rc = wc/hc;
                    var wi = widget.options.widget_options.img_w;
                    var hi = widget.options.widget_options.img_h;
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
                
                $wi = $args['img_w'];
                $hi = $args['img_h'];
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
                        </style>
                        <script type="text/javascript">
                            $(document).ready( function() {
                                window.setTimeout( function () {
                                    $infoDIV = $("#'.$this->widget_id.'").find(".img-info-window");
                                    $("#'.$this->widget_id.'").hover(
                                        function() {    //HoverIn
                                            $($infoDIV).css("display", "block");
                                        },
                                        function() {    //HoverOut
                                            $($infoDIV).css("display", "none");
                                        }
                                    );
                                }, 1000);
                                
                            });
                        </script>';
                $out .= '<div class="img-info-window">
                            Container dimension ratio (width x height): '.$wc.' x '.$hc.'
                        </div>';
                return $out;
        }
        
	function content($args)
	{
                extract($args);
                $out = '<img src="'.$img_url.'"></img>';
		return $out;
	}
        
        function content_footer($args)
        {
                return "";
        }
        
        
        
        
        function style_options($args)
        {
            $out = '
                    <script type="text/javascript">
                        function '.$this->widget_class.'_styles() {
                            var image_style = {};
                            return image_style;
                        }
                    </script>
                    
                    <p>No global image options</p>';
            return $out;
        }
        
        function options_header($args)
        {
                $out = '<div class="widget-options-header">
                            <script type="text/javascript">
                                function returnOptions() {
                                    var imgURL = $("#selected-image-input").val();
                                    if (imgURL === "") {
                                        alert("Image not selected");
                                        return false;
                                    }
                                    var imgH = $("#selected-image-input").attr("data-height");
                                    var imgW = $("#selected-image-input").attr("data-width");
                                    var imgPath = $("#selected-image-input").attr("data-path");

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
                                                        img_url: imgURL,
                                                        img_h: imgH,
                                                        img_w: imgW,
                                                        img_path: imgPath,
                                                        vert_align: vertAlign,
                                                        hor_align: horAlign
                                                        }
                                    };

                                    return options;
                                }
                                
                                function toImages(){
                                    //show images and hide options
                                    $("#schema-images").css("display", "inline-block");
                                    $("#image-options").css("display", "none");
                                    $(".widget-options-footer").css("display", "none");
                                }
                                function toOptions(){
                                    //hide images and show options
                                    $("#schema-images").css("display", "none");
                                    $("#image-options").css("display", "inline-block");
                                    $(".widget-options-footer").css("display", "inline-block");
                                }
                                
                                $(document).ready(function() {
                                    $("#images-inner-iframe").load(function() {
                                        $(this).contents().find("img").bind("click", function() {
                                            var imgURL = $(this).attr("src");
                                            var imgAlt = $(this).attr("alt");
                                            $("#selected-image-input").val(imgURL);
                                            var infoDiv = $(this).next().html();
                                            var widthHeight = infoDiv.split(" x ");
                                            var imgPath = $(this).attr("data-path");
                                            $("#selected-image-input").attr("data-width", widthHeight[0]);
                                            $("#selected-image-input").attr("data-height", widthHeight[1]);
                                            $("#selected-image-input").attr("data-path", imgPath);
                                            toOptions();
                                        });
                                        $(this).contents().find(".wrap").css("overflow", "auto");
                                    });
                                });
                            </script>
                            
                            <style>
                                #schema-images {display:none}
                                iframe {height: 90%; width:100%;}
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
                
                $out = '<div class="widget-options-content">
                            
                            <div id="schema-images">
                                <iframe id="images-inner-iframe" src="'.CONF_AJAX_URL.'show_schema_images.php?schema='.$this->schema.'"
                                    frameborder="0" scrolling="no"></iframe>
                                <button onclick="toOptions();">Back</button>
                            </div>

                            <div id="image-options">
                                <input type="text" id="selected-image-input" data-height="'.$img_h.'" data-width="'.$img_w.'" data-path="'.$img_path.'" disabled value="'.$img_url.'"/>
                                <button onclick="toImages();">Select</button>
                                <br>
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