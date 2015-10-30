<?php

    require CONF_REP_WIDGETS_DIR . '/rep_abstract.php';

    $schema = $_SESSION['current_schema'];
    if(!$schema) {
        fx_show_metabox(array('body' => array('content' => new FX_Error('show_templates', 'Please select Data Schema.')), 'footer' => array('hidden' => true)));
        exit;
    }
    
    if (isset($_POST['activate_widget'])) {
        activate_widget($_POST['widget_name'], $_POST['widget_path'], $_POST['widget_class']);
        header('Location: ' . replace_url_param());
    }

    if (isset($_POST['deactivate_widget'])) {
        deactivate_widget($_POST['widget_name']);
        header('Location: ' . replace_url_param());
    }

    if (isset($_POST['deactivate_all'])) {
        remove_fx_option('active_widgets_'.$schema);
        header('Location: ' . replace_url_param());
    }

    $active_widgets = get_fx_option('active_widgets_'.$schema, array());
    $widgets = array();

    scan_widget_dir(CONF_REP_WIDGETS_DIR, $widgets);
?>

<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
            <div class="icons settings"></div>
            <h1>Portal Widgets</h1>
        </div>
        <div class="content">
                 
            <style type="text/css">
                .stack-item {
                    background: #efefef;
                    border: solid 1px #ccc;
                    margin-top: 5px;
                    position:relative;
                }

                .stack-item:after { 
                   content: " ";
                   display: block; 
                   height: 0; 
                   clear: both;

                }
                .stack-item .title {
                    float:left; 
                    border-right:1px solid #ccc; 
                    text-align:center; 
                    padding:10px;
                    width:15%;
                    min-width:150px;
                    height:100%;
                }
                .stack-item .content {
                    padding:10px;
                    padding-bottom:20px;
                    width:80%;
                    float:left;
                }
                .stack-item .item-info {
                    position:absolute;

                    bottom:3px;
                    right:3px;
                }

            </style>
        
            <?php

                    if ($widgets)
                    {
                            if ($active_widgets) {
                                    echo '
                                    <form method="post">
                                            <input type="hidden" name="deactivate_all">
                                            <input type="submit" class="button small red" value="Deactivate All">
                                    </form>';
                            }

                            echo '<hr>';

                            foreach ($widgets as $widget) {

                                    if (array_key_exists($widget['name'], $active_widgets)) {
                                            $ctrl = '
                                            <form method="post">
                                                    <input type="hidden" name="deactivate_widget">
                                                    <input type="hidden" name="widget_name" value="' . $widget['name'] . '">
                                                    <input type="submit" class="button small red" value="Deactivate">
                                            </form>';
                                    } 
                                    else {
                                            $ctrl = '
                                            <form method="post">
                                                    <input type="hidden" name="activate_widget">
                                                    <input type="hidden" name="widget_name" value="' . $widget['name'] . '">
                                                    <input type="hidden" name="widget_path" value="' . $widget['path'] . '">
                                                    <input type="hidden" name="widget_class" value="' . $widget['class'] . '">
                                                    <input type="submit" class="button small green" value="Activate">
                                            </form>';
                                    }

                                    echo '
                                    <div class="stack-item">
                                            <div class="title">
                                                    <p><strong>'.$widget['name'].'</strong></p>
                                                    <p>&nbsp;</p>
                                                    ' . $ctrl . '
                                            </div>
                                            <div class="content">
                                                    <p>'.($widget['description'] ? $widget['description'] : '<font color="#CCCCCC">Description is not available</font>').'</p>
                                            </div>
                                             <div class="item-info">
                                                    '.($widget['version'] ? 'Version '.$widget['version'].'&nbsp;|&nbsp;' : '').'
                                                    '.($widget['author'] ? ' by <a href="'.$widget['author_url'].'">'.$widget['author'].'</a>&nbsp;|&nbsp;' : '').'
                                                    '.($widget['license'] ? 'License: '.$widget['license'] : '').'                
                                            </div>
                                    </div>';
                            }
                            }
                    else {
                            echo '<div class="info">'._('Widgets not found').'</div>';
                    }
            ?>         
        </div>
    </div>
</div>