<?php

	session_start();
        require_once dirname(dirname(__FILE__))."/fx_load.php";
        validate_script_user();
        
        $schema = $_SESSION['current_schema'];
        
        $opts = json_decode($_GET['options'],true);
        
        $active_widgets = get_fx_option('active_widgets_'.$schema, array());
        require_once CONF_REP_WIDGETS_DIR . '/rep_abstract.php';
        foreach ($active_widgets as $widget_name=>$widget_info) {
            $widget_class = $widget_info['class'];
            $widget_params = array("widget_class"=>$widget_class, "widget_name"=>$widget_name, "schema"=>$schema, "mode"=>"options");

            require_once $widget_info['path'];
            if (class_exists($widget_class)) {
                $widget_refl = new ReflectionClass($widget_class);
                $widget_object = $widget_refl->newInstanceArgs(array($widget_params));
                //There are restrictions on what chars the name can contain - need to filter illegal ones out
                $widget_name_filtered = str_replace(" ", "-", $widget_name);
                $ws_list_els .= '<li><a href="#'.$widget_name_filtered.'">'.$widget_name_filtered.'</a></li>';
                $ws_div_els .= '<div id="'.$widget_name_filtered.'">'.$widget_object->get_style_options($opts[$widget_class]).'</div>';
                $ws_vars .= 'if (typeof('.$widget_class.'_styles) == "function") {
                                opts.'.$widget_class.'='.$widget_class.'_styles();
                            }';


            }
        }
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo CONF_SITE_URL; ?>js/jquery-ui.custom.min.js"></script>
<link rel="stylesheet" href="<?php echo CONF_SITE_URL; ?>style/jquery-ui.custom.css"/>

<script type='text/javascript'>
    
    
    $(document).ready(function () {
//        $("#table_th_bg, #table_th_text, #table_td_bg, #table_td_text, #table_bor_col").minicolors({
//            change: function() {
//                parent.changeGlobalReportStyle(reportOptions());
//            }
//        });
        
        //Global
        toOptions();
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
                showHideAdvancedImgOpts();
            });
            $(this).contents().find(".wrap").css("overflow", "auto");
        });
        //Enable tabs
        $("#tabs").tabs();
        $("#opacity-slider").slider({
            min: 0,
            max: 100,
            step: 1,
            value: <?php echo $opts['general']['bg_img_op'];?>*100,
            slide: function(event, ui) {
                $("#opacity-amount").val(ui.value / 100);
            }
        });
        $("#opacity-amount").val("<?php echo $opts['general']['bg_img_op'];?>");
        showHideAdvancedImgOpts();
    });
    
    function reportOptions() {
        var bg_img = $("#selected-image-input").val();
        var bg_img_path = $("#selected-image-input").attr("data-path");
        var bg_img_op = $("#opacity-slider").slider("value")/100;
        var bg_img_valign = $("input[name=vert-align]:checked").val();
        var bg_img_halign = $("input[name=hor-align]:checked").val();
        var header = $("input[id=header-checkbox]:checked").val() === "on" ? "true" : "false";
        var footer = $("input[id=footer-checkbox]:checked").val() === "on" ? "true" : "false";
        
        var opts = {
            general: {
                bg_img: bg_img,
                bg_img_path: bg_img_path,
                bg_img_op: bg_img_op,
                bg_img_valign: bg_img_valign,
                bg_img_halign: bg_img_halign,
                header: header,
                footer: footer
            }
        };
        <?php echo $ws_vars; ?>
        
        return opts;
    }
    
    function toImages(){
        //show images and hide options
        $("#schema-images").css("display", "inline-block");
        $("#advanced-options").css("display", "none");
    }
    function toOptions(){
        //hide images and show options
        $("#schema-images").css("display", "none");
        $("#advanced-options").css("display", "inline-block");
    }
    function removeImage(){
        $("#selected-image-input").val("none");
    }
    function showHideAdvancedImgOpts() {
        var inputVal = $("#selected-image-input").val();
        if (inputVal !== "none") {
            $("#advanced-img-opts").css("display", "block");
        } else {
            $("#advanced-img-opts").css("display", "none");
        }
    }

</script>


<div id="tabs">
    <ul>
        <li><a href="#general">General</a></li>
        <li><a href="#headerFooter">Header and Footer</a></li>
        <?php echo $ws_list_els; ?>
    </ul>
    <div id="general">
        <div id="advanced-options" style="width:100%;">
            <label for="report_background_image">Background image:</label>
            <input type="text" id="selected-image-input" data-height="" data-width="" data-path="<?php echo $opts['general']['bg_img_path'];?>" disabled value="<?php echo $opts['general']['bg_img'];?>"/>
            <button onclick="toImages();">Select</button>
            <button onclick="removeImage(); showHideAdvancedImgOpts();">Remove</button>
            <div id="advanced-img-opts">
                <label>Background opacity:</label><input type="text" id="opacity-amount" readonly style="border:0; font-weight:bold;">
                <div id="opacity-slider"></div>
                <br>
                <label>Vertical alignment:</label><br>
                <input type="radio" name="vert-align" id="v1" value="top" <?php echo $opts['general']['bg_img_valign']==='top' ? 'checked' : ''; ?>/><label for="v1">Top</label><br>
                <input type="radio" name="vert-align" id="v2" value="cent" <?php echo $opts['general']['bg_img_valign']==='cent' ? 'checked' : ''; ?> /><label for="v2">Centre</label><br>
                <input type="radio" name="vert-align" id="v3" value="bot" <?php echo $opts['general']['bg_img_valign']==='bot' ? 'checked' : ''; ?> /><label for="v3">Bottom</label><br>
                <br>
                <label>Horizontal alignment:</label><br>
                <input type="radio" name="hor-align" id="h1" value="left" <?php echo $opts['general']['bg_img_halign']==='left' ? 'checked' : ''; ?> /><label for="h1">Left</label><br>
                <input type="radio" name="hor-align" id="h2" value="cent" <?php echo $opts['general']['bg_img_halign']==='cent' ? 'checked' : ''; ?> /><label for="h2">Centre</label><br>
                <input type="radio" name="hor-align" id="h3" value="right" <?php echo $opts['general']['bg_img_halign']==='right' ? 'checked' : ''; ?> /><label for="h3">Right</label><br>
            </div>
        </div>
        <div id="schema-images" style="width:100%;">
            <iframe id="images-inner-iframe" src="<?php echo CONF_AJAX_URL;?>show_schema_images.php?schema=<?php echo $schema;?>"
                frameborder="0" scrolling="no" height="95%" width="100%"></iframe>
            <br>
            <button onclick="toOptions();">Back</button>
        </div>
    </div>
    <div id="headerFooter">
        <label><input id="header-checkbox" type="checkbox" <?php echo $opts['general']['header']==='true' ? 'checked' : ''; ?>/> Header</label>
        <br>
        <label><input id="footer-checkbox" type="checkbox" <?php echo $opts['general']['footer']==='true' ? 'checked' : ''; ?>/> Footer</label>
    </div>
    <?php echo $ws_div_els; ?>
</div>