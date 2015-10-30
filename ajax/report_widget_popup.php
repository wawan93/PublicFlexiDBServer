<?php

	session_start();
        require_once dirname(dirname(__FILE__))."/fx_load.php";
        validate_script_user();
        require CONF_REP_WIDGETS_DIR . '/rep_abstract.php';
        
        $widget_params = json_decode($_GET['widgetParams'], TRUE);
        $report_params = json_decode($_GET['reportParams'], TRUE);
        
        echo '<script type="text/javascript" src="'.URL.'js/jquery.min.js"></script>';
        echo '<script type="text/javascript" src="'.URL.'js/jquery-ui.custom.min.js"></script>';
        echo '<link rel="stylesheet" href="'.URL.'style/jquery-ui.custom.css"/>';
        
        $schema = $_SESSION['current_schema'];
        $widget_class = $widget_params['widget_class'];
        
        if (!isset($widget_params['style']) || ($widget_params['style_type']!=='widget')) {
            $widget_params['style'] = $report_params[$widget_class];
        }
        
        $active_widgets = get_fx_option('active_widgets_'.$schema, array());
        
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
        
?>