<?php

	session_start();
        require_once dirname(dirname(__FILE__))."/fx_load.php";
        validate_script_user();
        require CONF_REP_WIDGETS_DIR . '/rep_abstract.php';
        
        $schema = $_SESSION['current_schema'];
        $active_widgets = get_fx_option('active_widgets_'.$schema, array());
        
        if (isset($_POST['widgetParams'])) {
            $widget_params = $_POST['widgetParams'];
            
            $widget_class = $widget_params['widget_class'];
            
            foreach ($active_widgets as $widget_name=>$widget_info) {
                if ($widget_info['class'] === $widget_class) {
                    //Requested widget is activated
                    require $widget_info['path'];

                    if (class_exists($widget_class)) {  //Check if class has been imported properly
                        $widget_refl = new ReflectionClass($widget_class);
                        $widget_object = $widget_refl->newInstanceArgs(array($widget_params));
                        $widget = $widget_object->show_widget();

                        echo $widget;
                    }
                }
            }
            
        } else if (isset($_POST['reportParams'])) {
            $report_params = $_POST['reportParams'];
            
            foreach ($active_widgets as $widget_name=>$widget_info) {
                //Requested widget is activated
                require $widget_info['path'];
                $widget_class = $widget_info['class'];

                if (class_exists($widget_class)) {  //Check if class has been imported properly
                    $widget_refl = new ReflectionClass($widget_class);
                    $widget_params = array("widget_class"=>$widget_class, "widget_name"=>$widget_name, "schema"=>$schema, "mode"=>"options");
                    $widget_object = $widget_refl->newInstanceArgs(array($widget_params));
                    $widget_global_styles .= $widget_object->get_global_style($report_params[$widget_class]);
                }
            }
            echo $widget_global_styles;
        }
?>