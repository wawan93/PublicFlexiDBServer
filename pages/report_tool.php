<?php
    
    $schema = $_SESSION['current_schema'];
    if(!$schema) {
        fx_show_metabox(array('body' => array('content' => new FX_Error('show_templates', 'Please select Data Schema.')), 'footer' => array('hidden' => true)));
        exit;
    }

    $report_type_id = get_type_id_by_name(0,'report');
    if(isset($_GET["object_id"]) && $_GET["object_id"]>0){
        $obj = get_object($report_type_id, $_GET["object_id"]);
        if(!is_fx_error($obj)){
            $data = json_encode($obj);
            $report_data = json_decode($obj['code'], true);
            $name = $obj["display_name"];
        } else {
            fx_show_metabox(array('body' => array('content' => 'Invalid object ID!'), 'footer' => array('hidden' => true)));
            exit;
        }
    }
    $report_type_fields = get_type_fields($report_type_id,'custom');
    if(is_fx_error($report_type_fields)) {
        fx_show_metabox(array('body' => array('content' => '<pre>'.print_r($report_type_fields,true).'</pre>'), 'footer' => array('hidden' => true)));
        exit;
    }

    $report_select = '<option value="-1">New Report</option>';
    $report_select .= show_select_options(
        get_objects_by_type($report_type_id, $schema),
        'object_id',
        'display_name',
        (int)$_GET['object_id'],
        false
    );
    $name = $name ? $name : 'new';
    $reports_main_metabox = <<<END
        <label for="file_name">Report Name:</label>
        <input type="text" id="file_name" name="file_name" value="$name">
        <label for="report_select">Select report</label>
        <select id="report_select" name="report_select">
            {$report_select}
        </select>
        <input type="button" id="save-button" class="button green" value="Save">
        <input type="button" id="remove-button" class="button red" value="Remove">
END;


    $page_formats = get_enum_fields((int)$report_type_fields['format']['type']);
    if(is_fx_error($page_formats) || $page_formats==false) {
        fx_show_metabox(array('body' => array('content' => new FX_Error('Page formats', 'No fields')), 'footer' => array('hidden' => true)));
        exit;
    }
    $selected_format_name = $_GET['object_id']>0 ? $obj['format'] : $report_type_fields['format']['default_value'];
    $page_format_select = '';
    foreach($page_formats as $k=>$format) {
        $page_format_select .= '<option ';
        $page_format_select .= $k==$selected_format_name ? 'selected' : '';
        $page_format_select .= ' value="'.$k.'">'.$format.'</option>';
    }
    $selected_orientation_name = $_GET['object_id']>0 ? $obj['orientation'] : $report_type_fields['orientation']['default_value'];
    $page_orientation_select = '';
    foreach($report_type_fields['orientation']['enum'] as $k=>$orient) {
        $page_orientation_select .= '<option ';
        $page_orientation_select .= $k==$selected_orientation_name ? 'selected' : '';
        $page_orientation_select .= ' value="'.$k.'">'.$orient.'</option>';
    }
    $selected_hf = $_GET['object_id']>0 ? $obj['page_numbers'] : $report_type_fields['page_numbers']['default_value'];
    
    $query_list_select = '<select name="value" class="query_select_input field-val">';
    $query_list_select .= show_select_options(
        get_objects_by_type(get_type_id_by_name(0,'query'), $schema),
        'object_id',
        'display_name', 0, false
    );
    $query_list_select .= '</select>';

    $chart_list_select = '<select name="value" class="chart_select_input field-val">';
    $chart_list_select .= show_select_options(
        get_objects_by_type(get_type_id_by_name(0,'chart'), $schema),
        'object_id',
        'display_name', 0, false
    );
    $chart_list_select .= '</select>';
    
    
    if ($report_data) {
        $widgets = $report_data['widgets'];
        $hf_widgets = $report_data['headerFooter'];
        $report_options = $report_data['report_options'];
    } else {
        //Default report options
        $report_options = array(
            "general"=>array(
                "columns"=>6,
                "bg_img"=>"none",
                "bg_img_path"=>"",
                "bg_img_op"=>0.5,
                "bg_img_valign"=>"cent",
                "bg_img_halign"=>"cent",
                "header"=>"true",
                "footer"=>"true"
            )
        );
        $widgets = array();
        $hf_widgets = array("header"=>"", "footer"=>"");
    }
    
    
    $active_widgets = get_fx_option('active_widgets_'.$schema, array());
    require_once CONF_REP_WIDGETS_DIR . '/rep_abstract.php';
    foreach ($active_widgets as $widget_name=>$widget_info) {
        //$widget_dir = $widget_info['path'];
        $widget_class = $widget_info['class'];
        $widget_params = array("widget_class"=>$widget_class, "widget_name"=>$widget_name, "schema"=>$schema, "mode"=>"options");
        $widget_list .= '<li class="collapsible-block" onclick="showWidgetPopup(\''.rawurlencode(json_encode($widget_params)).'\')"><div class="not-collapsible handle"><span class="title">Add '.$widget_name.'</span></div></li>';
        
        require_once $widget_info['path'];
        
        if (class_exists($widget_class)) {  //Check if class has been imported properly
            $widget_refl = new ReflectionClass($widget_class);
            $widget_object = $widget_refl->newInstanceArgs(array($widget_params));
            
            if (!$report_data) {
                $report_options[$widget_class] = $widget_object->get_def_style_param();
            }
            $global_widget_style .= $widget_object->get_global_style($report_options[$widget_class]);
            $resize_functions .=    'if (widget.options.widget_class === "'.$widget_class.'") {
                                        '.$widget_object->get_resize_function().'
                                    } ';
            $resize_stop_functions .=    'if (widget.options.widget_class === "'.$widget_class.'") {
                                        '.$widget_object->get_resize_stop_function().'
                                    } ';
        }
    }
    if (count($active_widgets)) {
        $widget_list .= '<li class="collapsible-block" ng-click="clear()"><div class="not-collapsible handle"><span class="title">Clear</span></div></li>';
    } else {
        $widget_list .= '<a href="'.URL.'report/report_widgets"><li class="collapsible-block"><div class="not-collapsible handle"><span class="title">Activate widgets</span></div></li></a>';
    }
?>

<!--Include Google Hosted AngularJS-->
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.1/angular.min.js"></script>

<!--Include  Angular Gridster-->
<script src="<?php echo CONF_EXT_URL; ?>angular-gridster/jquery.resize.js"></script>
<link rel="stylesheet" href="<?php echo CONF_EXT_URL; ?>angular-gridster/angular-gridster.min.css"/>
<link rel="stylesheet" href="<?php echo CONF_EXT_URL; ?>angular-gridster/style.css"/>
<script src="<?php echo CONF_EXT_URL; ?>angular-gridster/angular-gridster.min.js"></script>

<style>
    #page_header, #page_footer {
        width: 99%;
        height: 70px;
    }

    .short_codes {
        float: left;
        width: 20%;
    }

    #editor {
        border: 2px dashed #dddddd;
    }

</style>

<script type="text/javascript">
    function changeGridsterColumns () {
        var cols = $("#gridster_columns").val();
        angular.element('#GrdisterCtrl').scope().gridsterOpts.columns = cols;
        angular.element('#GrdisterCtrl').scope().report_options.general.columns = cols;
    }
    
    function openAdvanced () {
        var dialog = '<div id="report-advanced-dialog" title="Report - advanced options"></div>';
        $('body').append(dialog);
        $("#report-advanced-dialog").html('<iframe id="report-options-iframe" style="margin:0; padding:0; overflow:scroll;\n\
                                    frameborder="0" vspace="0" hspace="0" height="100%" width="100%"\n\
                                    src="<?php echo CONF_AJAX_URL; ?>report_advanced.php?options='+encodeURIComponent(JSON.stringify(getReportOpts()))+'"></iframe>');
        $("#report-advanced-dialog").dialog({
                                    autoOpen: false,
                                    position: {my: "center", at: "center", of: window},
                                    height: 600,
                                    width: 450,
                                    buttons:
                                    [
                                        {
                                            text: "Save",
                                            click: function() {
                                                var options = $("#report-options-iframe").get(0).contentWindow.reportOptions();
                                                angular.element('#GrdisterCtrl').scope().report_options = options;
                                                changeGridsterColumns();
                                                angular.element('#GrdisterCtrl').scope().changed = true;
                                                angular.element('#GrdisterCtrl').scope().$apply();
                                                getGlobalStyles(options);
                                                $("#report-advanced-dialog").dialog('close');
                                            }
                                        },
                                        {
                                            text: "Cancel",
                                            click: function() {
                                                $("#report-advanced-dialog").dialog('close');
                                            }
                                        }
                                    ]
                                });
        $("#report-advanced-dialog").dialog("open");
        $('#report-advanced-dialog').bind('dialogclose', function(event) {
            $(this).remove();
        });
        
    }
    
    function changeGlobalReportStyle(options) {
        console.debug(options);
        //realtime colours
        //angular.element('#GrdisterCtrl').scope().report_options = options;
    }
    
    function getGlobalStyles(options) {
        var request = $.ajax({
            method: "POST",
            url: "<?php echo CONF_AJAX_URL; ?>report_widget_ajax.php",
            data: {reportParams: options}
        });
        request.done(function(data) {
            $("#global_style_options").html(data);
        });
        request.fail(function(jqXHR, textStatus) {
            alert("Request failed: " + textStatus);
        });
    }
    
    (function() {
        var app = angular.module('app', ['gridster']);
        app.controller('GrdisterCtrl',['$scope', '$http', '$timeout', function ($scope, $http, $timeout) {
            $scope.gridsterOpts = {
                columns: 6,
                floating: false,
                swapping: true,
                resizable: {
                    start: function(event, $element, widget) {
                        $scope.changed = true;
                    },
                    resize: function(event, $element, widget) {
                        <?php echo $resize_functions; ?>
                    },
                    stop: function(event, $element, widget) {
                        <?php echo $resize_stop_functions; ?>
                    }
                },
                draggable: {
                    enabled: true,
                    start: function(event, $element, widget) {
                        $scope.changed = true;
                    }
                }
            };
            $scope.report_options = <?php echo json_encode($report_options); ?>;
            $scope.items = [];
            $scope.changed = false;
            $scope.$watch('[report_options, items, changed]', function () {
                if (typeof(window.designer) !== 'undefined') {
                    if ($scope.items.length > 0 && $scope.changed) {
                        window.designer.saved = false;
                    } else {
                        window.designer.saved = true;
                    }
                }
            }, true);
            $scope.init = function () {
                var widgets = <?php echo json_encode($widgets); ?>;
                if (widgets.length > 0) {
                    for (var i=0; i<widgets.length; i++) {
                        var grd_opts = {
                                        sizeX: widgets[i].sizeX,
                                        sizeY: widgets[i].sizeY,
                                        row: widgets[i].row,
                                        col: widgets[i].col
                                    };
                        $scope.addWidget(widgets[i].options, grd_opts);
                    }
                    $scope.changed = false;
                    $scope.$apply();
               }
            };
            $scope.addWidget = function(options, gridster_opts) {
                
                if (gridster_opts === '') {
                    gridster_opts = {
                                        sizeX: 1,
                                        sizeY: 1
                                    };
                }
                options.gridster_options = gridster_opts;
                var responsePromise = $http({
                                        method: "POST",
                                        url: "<?php echo CONF_AJAX_URL; ?>report_widget_ajax.php",
                                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                        data: $.param({widgetParams:options})
                                    });
                responsePromise.success(function(data, status, headers, config) {
                    $scope.items.push({
                        sizeX: gridster_opts.sizeX,
                        sizeY: gridster_opts.sizeY,
                        col: gridster_opts.col,
                        row: gridster_opts.row,
                        id: options.widget_id,
                        class: options.widget_class,
                        options: options,
                        content: data
                    });
                });
                responsePromise.error(function(data, status, headers, config) {
                    alert("addWidget AJAX failed...");
                });
                $scope.changed = true;
            };
            $scope.editWidget = function(id) {
                    angular.forEach($scope.items, function(value, key) {
                        if (value.id === id) {
                            var widgetParams = value.options;
                            widgetParams.mode = "options";
                            widgetParams.gridster_options = {sizeX: value.sizeX, sizeY: value.sizeY};
                            showWidgetPopup(encodeURIComponent(JSON.stringify(widgetParams)));
                            widgetParams.mode = "content";
                        }
                    });
                    //$scope.changed = true;
            };
            $scope.updateWidget = function(options) {
                    var responsePromise = $http({
                                            method: "POST",
                                            url: "<?php echo CONF_AJAX_URL; ?>report_widget_ajax.php",
                                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                            data: $.param({widgetParams:options})
                                        });
                    responsePromise.success(function(data, status, headers, config) {
                        var updatedWidget = {};
                        angular.forEach($scope.items, function(value, key) {
                            if (value.id === options.widget_id) {
                                var updatedItem = value;
                                value.options = options;
                                value.content = data;
                                updatedWidget = value;
                            }
                        });
                        //if updatedWidget is not empty
                        $scope.removeWidget(options.widget_id);
//                        $scope.$apply();
                        $timeout(function() {
                            $scope.items.push(updatedWidget);
                        });
                    });
                    responsePromise.error(function(data,status, headers, config) {
                        alert("updateWidget AJAX failed...");
                    });
                    $scope.changed = true;
            };
            $scope.removeWidget = function(id) {
                    angular.forEach($scope.items, function(value, key) {
                        if (value.id === id) {
                            $scope.items.splice(key, 1);
                        }
                    });
                    $scope.changed = true;
            };
            $scope.clear = function() {
                    $scope.items = [];
                    $scope.changed = true;
            };
        }]);
        app.directive('widgetBody', function ($compile) {
            return function(scope, el, attrs) {
                el.replaceWith($compile(scope.item.content)(scope));
            };
        });
        
    })();
    
    function showWidgetPopup(widgetParams) {
        widgetParams = JSON.parse(decodeURIComponent(widgetParams));
        
        var insertButton = {
            text: "Insert",
            click: function() {
                var options = $("#widget-options-iframe").get(0).contentWindow.returnOptions();
                if (options) {
                    angular.element('#GrdisterCtrl').scope().addWidget(options, '');
                    angular.element('#GrdisterCtrl').scope().$apply();
                    $("#widget-options-dialog").dialog('close');
                }
            }
        };
        var cancelButton = {
            text: "Cancel",
            click: function() {
                $("#widget-options-dialog").dialog('close');
            }
        };
        var saveButton = {
            text: "Save",
            click: function() {
                var options = $("#widget-options-iframe").get(0).contentWindow.returnOptions();
                if (options) {
                    angular.element('#GrdisterCtrl').scope().updateWidget(options);
                    angular.element('#GrdisterCtrl').scope().$apply();
                    $("#widget-options-dialog").dialog('close');
                }
            }
        };
        
        var title = "";
        var buttons = [];
        
        if (widgetParams.hasOwnProperty("widget_id")) {
            //ID passed - edit case
            title = "Edit "+widgetParams.widget_name;
            buttons = [saveButton, cancelButton];
        } else {
            // Add case
            //widgetParams.schema = "<?php echo $schema; ?>";
            title = "Add new "+widgetParams.widget_name;
            buttons = [insertButton, cancelButton];
        }
        
        var dialog = '<div id="widget-options-dialog" title="'+title+'"></div>';
        $('body').append(dialog);
        $("#widget-options-dialog").html('<iframe id="widget-options-iframe" style="margin:0; padding:0;\n\
                                    frameborder="0" vspace="0" hspace="0" height="100%" width="100%"\n\
                                    src="<?php echo CONF_AJAX_URL; ?>report_widget_popup.php?widgetParams='+encodeURIComponent(JSON.stringify(widgetParams))+'&reportParams='+encodeURIComponent(JSON.stringify(getReportOpts()))+'"></iframe>');
        $("#widget-options-dialog").dialog({
                                    autoOpen: false,
                                    position: {my: "center", at: "center", of: window},
                                    height: 600,
                                    width: 450,
                                    buttons: buttons
                                });
        $("#widget-options-dialog").dialog("open");
        $('#widget-options-dialog').bind('dialogclose', function(event) {
            $(this).remove();
        });
    }
    
    function editWidget(id) {
        angular.element('#GrdisterCtrl').scope().editWidget(id);
    }
    
    function deleteWidget(id) {
        angular.element('#GrdisterCtrl').scope().removeWidget(id);
        angular.element('#GrdisterCtrl').scope().$apply();
    }
    
    window.current_object_id = <?php echo $_GET['object_id']?(int)$_GET['object_id']:-1; ?>;
    
    function downloadPDF() {
        var loc = '<?php echo CONF_AJAX_URL.'report.php';?>';
        loc += '?id=' + window.current_object_id;
        loc += '&orientation=' + $('#page_orientation').val();
        loc += '&format=' + $('#format_select').val();
        loc += '&name=' + $('#file_name').val();
        var form = '<form id="download_form" target="_blank" action="' + loc + '" method="post" style="display:none;">' +
                        '<textarea name="widgets">' + encodeURIComponent(JSON.stringify(getWidgets())) + '</textarea>' +
                        '<textarea name="headerFooter">' + encodeURIComponent(JSON.stringify(getHeaderFooter())) + '</textarea>' +
                        '<textarea name="reportOptions">' + encodeURIComponent(JSON.stringify(getReportOpts())) + '</textarea>' +
                    '</form>';
            
        $("body").append(form);
        document.forms["download_form"].submit();
        document.forms["download_form"].remove();
    }
    
    $(document).ready(function () {
        changeGridsterColumns();
        initMCE("page_header_mce"); initMCE("page_footer_mce");
        
        
        var ReportDesigner = function($nameInput, $objectSelect,
                                      $saveButton, $removeButton,
                                      data, schemaId, setId)
        {
            
            var _this = this;
            
            var collectDataCallback = function () {
                var code = {
                                widgets: getWidgets(),
                                headerFooter: getHeaderFooter(),
                                report_options: getReportOpts()
                            };
                angular.element('#GrdisterCtrl').scope().changed = false;
                angular.element('#GrdisterCtrl').scope().$apply();
                return {
                    format: $('#format_select').val(),
                    orientation: $('#page_orientation').val(),
                    code: JSON.stringify(code)
                };
            };

            var renderDataCallback = function(data) {
                console.log('render data callback!');
                console.log(data);
            };
            
            var findErrorsCallback = function(data) {
                console.log(data);
            };
            
            DataObjectEditor.call(this,
                <?php echo $report_type_id; ?>, "report", $nameInput,
                $objectSelect, $saveButton, $removeButton, '',
                '', collectDataCallback, renderDataCallback,
                findErrorsCallback, data,  schemaId, setId
            );
        };
        ReportDesigner.prototype = new DataObjectEditor();
        ReportDesigner.prototype.constructor = ReportDesigner;
        
        //Get data from Angular here, schema and set
        var data = <?php echo $data ? $data : 'undefined'; ?>;
        var schema = <?php echo $schema; ?>;
        
        window.designer = new ReportDesigner(
            $('#file_name'), $('#report_select'),
            $('#save-button'), $('#remove-button'),
            data, schema, 0
        );

        angular.element('#GrdisterCtrl').scope().init();

            
    });
    
    function getHeaderFooter() {
        var hfWidgets = {};
        var headerCont = encodeURI(tinymce.get('page_header_mce').getContent({format : 'raw'}));
        var footerCont = encodeURI(tinymce.get('page_footer_mce').getContent({format : 'raw'}));

        hfWidgets = {header: headerCont, footer: footerCont};
        
        return hfWidgets;
    }
    
    function getWidgets() {
        var widgets = angular.element('#GrdisterCtrl').scope().items;
        angular.forEach(widgets, function(value, key) {
            delete value.content;
        });
        return widgets;
    }
    
    function getReportOpts() {
        var opts = angular.element('#GrdisterCtrl').scope().report_options;
        return opts;
    }
    
    function reportChanged() {
        angular.element('#GrdisterCtrl').scope().changed = true;
        angular.element('#GrdisterCtrl').scope().$apply();
    }
</script>

<div id="GrdisterCtrl" ng-app="app" ng-controller="GrdisterCtrl">

<div class="leftcolumn">
    <?php
        fx_show_metabox(array('body' => array('content' => $reports_main_metabox),
                              'footer' => array('hidden' => true)));
        fx_show_metabox(array('header' => array('content' => 'Widgets'),
                              'body' => array('content' => '<ul id="active-widget-list" class="editor compact">'.$widget_list.'</ul>'),
                              'footer' => array('hidden' => true)));
    ?>

</div>
<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
            <div class="icons "></div>
            <h1>Editor<i></i></h1>
        </div>
        <div class="content">
            <div class="options">
                <label for="format_select">Page Format</label>
                <select id="format_select" name="format_select" onchange="reportChanged();">
                    <?php echo $page_format_select; ?>
                </select>
                <label for="page_orientation">Page orientation</label>
                <select id="page_orientation" name="page_orientation" onchange="reportChanged();">
                    <?php echo $page_orientation_select; ?>
                </select>
                <label>Edit header and footer <input type="checkbox" onchange="showHideHF(this);"/></label>
                <label for="gridster_columns">Number of columns:</label>
                <select id="gridster_columns" name="gridster_columns" onchange="changeGridsterColumns(); reportChanged();">
                    <?php   for ($i=5; $i<=10; $i++) {
                                $selected = ($i==$report_options['general']['columns']) ? "selected" : "";
                                echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
                            }
                    ?>
                </select>
                <button onclick="openAdvanced();">Advanced</button>
                <br>
                <div id="header_and_footer" style="display:none;">
                    <div class="metabox">
                        <div class="header">
                            Page header
                        </div>
                        <div class="content">
                            <div class="short_codes">
                                <a href="#" class="button small ">date</a>
                                <a href="#" class="button small ">display_name</a>
                                <a href="#" class="button small ">page</a>
                            </div>
                            <div style="display:inline-block; width:100%;" class="hf" id="page_header_mce_div">
                                <div id="page_header_mce"></div>
                            </div>
                        </div>
                    </div>
                    <div class="metabox">
                        <div class="header">
                            Page footer
                        </div>
                        <div class="content">
                            <div class="short_codes">
                                <a href="#" class="button small ">date</a>
                                <a href="#" class="button small ">display_name</a>
                                <a href="#" class="button small ">page</a>
                            </div>
                            <div style="display:inline-block; width:100%;" class="hf" id="page_footer_mce_div">
                                <div id="page_footer_mce"></div>
                            </div>
                        </div>
                    </div>
                    <hr style="clear: both">
                </div>
            </div>
            <div id="gridster-container" gridster="gridsterOpts">
                <style id="global_style_options">
                    <?php echo $global_widget_style; ?>
                </style>
                <ul>
                    <li gridster-item="item" ng-repeat="item in items" id="{{item.id}}" data-class="{{item.class}}">
                        <widget-body></widget-body>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php
        $results_preview_html = '<input type="button" id="download" class="button green" value="Download as PDF" onclick="downloadPDF();">';
        $results_preview_html .= '<div id="results"></div>';
        fx_show_metabox(array(
            'header' => array('content' => 'Preview'),
            'body' => array('content' => $results_preview_html),
            'footer' => array('hidden' => true)
        ));
    ?>
</div>
    
</div>

<script type="text/javascript">

    function showHideHF(checkbox) {
        if($(checkbox).prop('checked')){
            $('#header_and_footer').show();
        } else {
            $('#header_and_footer').hide();
        }
    }
    
    $('.short_codes .button').click(function(e){
        var id = $(this).parent().next('.hf').attr('id');
        id = id.replace("_div", "");
        tinymce.get(id).execCommand('mceInsertContent', false, ' $$'+$(this).text()+'$$ ');
    });
    
    
    function initMCE(id) {
        var max_chars = 10;
        var max_lines = 3;
        tinymce.init({
            mode : "exact",
            font_formats: "Arial=arial,helvetica,sans-serif;Courier New=courier new,courier,monospace;AkrutiKndPadmini=Akpdmi-n",
            elements: id,
            width: '100%',
            //theme_advanced_path : false,
            theme: "advanced",
            theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,formatselect,fontsizeselect",
            theme_advanced_buttons2 : "undo,redo,|,link,unlink,help,code,|,insertdate,inserttime,|,forecolor,backcolor",
            theme_advanced_buttons3 : "sub,sup,|,charmap,emotions,|,justifyleft,justifycenter,justifyright,justifyfull",
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            init_instance_callback: "MCEdefaultContent",
            onchange_callback : "reportChanged",
            setup : function(ed) {
                ed.onKeyUp.add(function(ed, evt) {
                    var strip = (tinymce.activeEditor.getContent()).replace(/(<([^>]+)>)/ig,"");
                    var rawContent = tinymce.activeEditor.getContent({format : 'raw'});
                    //console.debug(rawContent.split('<br>').length);
                    var text = strip.split(' ').length + " Words, " +  strip.length + " Characters, " + rawContent.split('<br>').length + " Lines";
                    tinymce.DOM.setHTML(tinymce.DOM.get(tinymce.activeEditor.id + '_path_row'), text);
                });
                ed.onKeyDown.add(function(ed, evt) {
                    //console.debug(evt.keyCode);
                    var rawContent = ed.getContent({format : 'raw'});
                    var line_chars = $(ed.selection.getNode()).text().length;
                    var lines = rawContent.split('<br>').length;
                    if (evt.keyCode !== 8) {
                        if (evt.keyCode === 13) {   //New line
                            if (lines === max_lines) {
                               evt.preventDefault();
                                evt.stopPropagation();
                                return false; 
                            }
                        }
                        //if ( $(ed.getBody()).text().length+1 > max_chars){
                        if (line_chars+1 > max_chars) {
                            if (evt.keyCode !== 13) {
                                evt.preventDefault();
                                evt.stopPropagation();
                                return false;
                            }
                        }
                    }
                });
            }
        });
    }
    function MCEdefaultContent(inst) {
        if (inst.id === "page_header_mce") {
            inst.setContent(decodeURI('<?php echo $hf_widgets['header'];?>'));
        } else if (inst.id === "page_footer_mce") {
            inst.setContent(decodeURI('<?php echo $hf_widgets['footer'];?>'));
        }
    }

</script>
