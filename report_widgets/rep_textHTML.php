<?php
/*
Widget Name: Text/HTML Widget
Description: Text/HTML Widget Description
Version: 0.0.1
Author: FlexiLogin
Author URL: http://FlexiLogin.com
License: GPLv2 or later
*/

class Widget_txtHTML extends FX_Widget
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
                        word-break:break-all;
                    }';
            return $out;
        }
        
        function widget_style($args)
        {
                $out = '.widget_content_'.$this->widget_class.' {word-break:break-all;}';
            
                return $out;
        }
        
        function widget_pdf($widget_code)
        {
            global $pdf;
            
            $widget_sizeX = $widget_code['widget_dimensions']['size_x'];
            $widget_sizeY = $widget_code['widget_dimensions']['size_y'];
            $widget_posX = $widget_code['widget_dimensions']['pos_x'];
            $widget_posY = $widget_code['widget_dimensions']['pos_y'];
            
            $content = $widget_code['options']['widget_options']['content'];
            
            $border = array('LRTB'=>array('width'=>0.1));
            
            //$pdf->SetXY($widget_posX, $widget_posY);
            //$pdf->Cell($widget_sizeX, $widget_sizeY, "", $border);
            
            $pdf->SetXY($widget_posX+1, $widget_posY+1);
            $pdf->writeHTMLCell($widget_sizeX-2, $widget_sizeY-2, $widget_posX+1, $widget_posY+1, $content);
        }
        
        
        
        
        function content_resize_fn()
        {
            $out = '
                    console.debug("TextWidget resize function");
                    ';
            return $out;
        }
        
        
        
        
        function content_header($args)
        {
                return $out;
        }
        
	function content($args)
	{
                extract($args);
		return $content;
	}
        
        function content_footer($args)
        {
                return "";
        }
        
        
        
        
        function style_options($args)
        {
            $out = "<h2>Text/HTML style options</h2>";
            return $out;
        }
        
        function options_header($args)
        {
                $out = '<div class="widget-options-header">
                            
                            
                            <script type="text/javascript" src="'.URL.'extensions/tiny_mce/tiny_mce.js"></script>

                            <script type="text/javascript">
                                
                                $(document).ready(function() {
                                    tinymce.init({
                                        mode : "exact",
                                        elements: "tinymce_container",
                                        width: "100%",
                                        height: "100%",
                                        autoresize_min_height: 200,
                                        autoresize_max_height: 800,
                                        init_instance_callback: "defaultContent"
                                    });
                                });
                                
                                function defaultContent(inst){
                                    inst.setContent('.json_encode($args['content']).');
                                }
                                
                                function returnOptions() {
                                    var content = tinymce.get("tinymce_container").getContent();
                                    if (!content) {
                                        alert("Content is empty");
                                        return false;
                                    } else {
                                        var options = {
                                            widget_id: "'.$this->widget_id.'",
                                            widget_class: "'.$this->widget_class.'",
                                            widget_name: "'.$this->widget_name.'",
                                            schema: "'.$this->schema.'",
                                            mode: "content",
                                            gridster_options: '.json_encode($this->gridster_options).',
                                            widget_options: {
                                                            content: content
                                                            }
                                        };

                                        return options;
                                    }
                                }
                                
                            </script>
                            
                            <style>
                                body {overflow: hidden;}
                                #tinymce_container {width:100%; height:100%;}
                            </style>
                        </div>';
                return $out;
        }
        
	function options($args)
	{
                $out = '<div class="widget-options-content">
                            <div id="tinymce_container"></div>
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