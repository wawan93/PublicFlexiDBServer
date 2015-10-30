<?php

if (!intval($_SESSION['current_schema'])) {
    fx_show_metabox(array('body' => array('content' => new FX_Error('empty_data_schema', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));
    return;
}

if (!$app_group = get_schema_app_group($_SESSION['current_schema'])) {
    fx_redirect(URL.'app_editor/app_group');
}

$app_id = $app_group['object_id'];
$schema = $_SESSION["current_schema"];


global $options, $styles_values;


$options = json_decode('{"global":{"font":"font","border_radius":"5","top_navigation":"rgba(31, 31, 31, 1)","app_background":"rgba(64, 64, 64, 0)","background_image":"http://flexibug.com/flexiweb/uploads/10088/32/10088-32-image.jpg","background_image_scale":"Cover","background_image_repeat":"Repeat-y","fx_button":"Yellow","box_shadow_color":"rgba(64, 64, 64, 0)","box_shadow_size":null},"loader":{"first_color":"#41ABDC","second_color":"#EE3834","third_color":"#A4CD39"},"widget_inset_font":{"header_font_color":"rgba(255,255,255,1)","font_color":"rgba(94, 94, 94, 1)","link_color":"rgba(255, 136, 0, 1)"},"widget_inset_background":{"header_background":"rgba(67, 52, 36, 1)","background":"rgba(194, 186, 186, 0)","list_header_background":"rgba(214, 202, 186, 1)","pages_switcher":"rgba(255, 255, 255, 0.89)","odd_stripes_background":"rgba(255, 255, 255, 1)","even_stripes_background":"rgba(240, 240, 240, 1)","border_color":"rgba(64, 64, 64, 0)"},"widget_offset":{"header_font_color":"rgba(255, 255, 255, 1)","font_color":"rgba(255, 255, 255, 1)","link_color":"rgba(31, 163, 236, 1)"},"button_normal":{"font_color":"rgba(255, 255, 255, 1)","background":"rgba(71, 54, 43, 1)","border_color":"rgba(0, 0, 0, 0)"},"button_hover":{"font_color":"rgba(255, 255, 255, 1)","background":"rgba(242, 149, 87, 1)","border_color":"rgba(0, 0, 0, 0)"},"button_pressed":{"font_color":"rgba(68,68,68,1)","background":"rgba(167,167,167,1)","border_color":"#444"},"button_apply_normal":{"font_color":"rgba(255,255,255,1)","background":"rgba(164, 206, 58, 1)","border_color":"rgba(22, 143, 236, 0)"},"button_apply_hover":{"font_color":"rgba(255,255,255,1)","background":"rgba(117, 150, 39, 1)","border_color":"rgba(22, 143, 236, 0)"},"button_apply_pressed":{"font_color":"rgba(255,255,255,1)","background":"rgba(22,143,236,1)","border_color":"rgba(22,143,236,1)"},"button_danger_normal":{"font_color":"rgba(255,255,255,1)","background":"rgba(238, 56, 52, 1)","border_color":"rgba(233, 114, 114, 0)"},"button_danger_hover":{"font_color":"rgba(255,255,255,1)","background":"rgba(163, 37, 33, 1)","border_color":"rgba(187, 89, 89, 0)"},"button_danger_pressed":{"font_color":"rgba(255,255,255,1)","background":"rgba(187,89,89,1)","border_color":"rgba(187,89,89,1)"}}', true);
//$options = json_decode(get_fx_option('server_default_app_theme', $options));


$sizes = array(8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 22, 24, 26, 28, 30, 32);
$styles = array('Normal','Lighter','Bold','Bolder');
$fonts = array("Arial", "Helvetica", "Times New Roman", "Trebuchet MS", "Yanone Kaffeesatz");


$options_gui_groups = array(
    "Global" => array("global"),
    "Loader" => array('loader'),
    "Inset Widgets" => array("widget_inset_font", "widget_inset_background"),
    "Widgets without INSET style" => array("widget_offset"),
    "Normal Button" => array("button_normal", "button_hover", "button_pressed"),
    "Apply Button (Save, Update, etc)" => array("button_apply_normal", "button_apply_hover", "button_apply_pressed"),
    "Danger Button (Delete, Cancel, etc)" => array("button_danger_normal", "button_danger_hover", "button_danger_pressed")
);

list (, $version_id) = explode('.', $_GET['id']);
$versions = get_app_data($app_id);

if($app_id && $version_id) {
    $fullID = $app_id. '.'. $version_id;
    $object = get_application($fullID);


    if($_POST['restore_to_defaults']) {
        $object['style'] = $options;
        update_object($object);
    }
    else if($_POST['import'] && !empty($_FILES)) {
        $fgc = file_get_contents($_FILES['theme']['tmp_name']);
        if($fgc != null) {
//            fx_print(bzdecompress($fgc));
            $styles_values = json_decode(bzdecompress($fgc), true);
        }
    }
    else {
        $styles_values = $object['style'];
    }
}

?>


<?php
if(!$versions) {
    fx_show_error_metabox(_('There is no versions for current application'));
}

else {
    ?>
    <div class="leftcolumn wide">
        <div class="metabox palette">
            <div class="header">
                <div class="icons app_editor"></div>
                <h1>Applications</h1>
            </div>
            <div class="content">
                <select id="version-select" style="margin: 0.4em;">
                    <option value="">Not selected</option>
                    <?php
                        $version_type_id = get_type_id_by_name(0, "app_data");
                        foreach ($versions as $id => $version) { ?>
                            <option value='<?php echo $id; ?>'
                                <?php echo ($id == $version_id) ? "selected" : ""; ?> >
                                <?php echo $version["display_name"]; ?>
                            </option>
                        <?php }
                    ?>
                </select>

                <?php if($version_id) :?>
                    <div>
                        <div class="button green" id="save_button">Save</div>
                        <form method="post" id="default_values_form" style="display: inline-block">
                            <input type="submit" name="restore_to_defaults" class="button blue" value="Restore to Default">
                        </form>
                    </div>

                    <div>
                        <div class="button blue" id="export_button">Export</div>

                        <form method="post" enctype="multipart/form-data" id="importForm" style="display: inline-block">
                            <div class="button blue" id="import_visible_button">Import</div>
                            <input type="file" name="theme" style="display: none" id="uploadThemeFile">
                            <input type="submit" name="import" class="button blue" id="import_button" style="display: none" value="Import">
                        </form>
                    </div>

                <?php endif; ?>
            </div>
        </div>

        <?php if($version_id) : ?>

        <div class="metabox palette">
            <div class="header">
                <div class="icons app_editor"></div>
                <h1>Colors</h1>
            </div>
            <div class="content">
                <form id="colors-tabs" method="post">
                    <?php
                    foreach($options_gui_groups as $gui_group => $options_groups_names){
                        $options_groups = array();
                        foreach($options_groups_names as $name => $val){
//                                $options_groups[$name] = $options[$name];
                            $options_groups[$val] = $options[$val];
                        }

                        print_options_gui_group($gui_group, $options_groups);
                    }
                    ?>
                </form>
            </div>
        </div>

    <?php endif; ?>
    </div>
    <div class="rightcolumn">
        <div class="metabox">
            <div class="header">
                <div class="icons app_editor"></div>
                <h1>Editor</h1>
            </div>
            <div class="content">
                <?php if($version_id) : ?>
                <iframe id="preview-frame" name="preview-frame" style="width: 495px; height: 800px;"
                        src="<?php echo URL.'mobile_app/generate.php?id='.$fullID.'&is_local_using=true'; ?>"></iframe>

                <?php else : ?>
        <p class="info">
            Please, select an application.
        </p>
    <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function hex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
    $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
    $rgbArray = array();
    if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
        $colorVal = hexdec($hexStr);
        $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
        $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
        $rgbArray['blue'] = 0xFF & $colorVal;
    } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
        $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
        $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
        $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
    } else {
        return false; //Invalid hex color code
    }
    return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
}
function rgb2HEX($rgbStr, $rgbOut = true) {
    if(!preg_match('/^#[a-f0-9]{6}$/i', $rgbStr))  {
        $color = explode(',',$rgbStr);

        $temp = explode('(',$color[0]);
        $color[0] = $temp[1];

        $temp = explode(')',$color[3]);
        $color[3] = $temp[0];

        $hexStr = '#';
        for($i = 0; $i < 3; $i++) {
            $numb = dechex($color[$i]);
            if(strlen($numb)== 1) {
                $hexStr .= '0'.$numb;
            } else {
                $hexStr .= $numb;
            }
        }
        return ($rgbOut)? $hexStr : $color[3];
    }
    else{
        return ($rgbOut)? $rgbStr : '1';
    }


}
function rgba_string($color) {
    if(preg_match('/^#[a-f0-9]{6}$/i', $color))  {
        return 'rgba('.hex2RGB($color,true).',1)';
    }
    else return $color;
}


/**
 * Print input for swatch
 *
 * @param $swatch   {string|NULL} name of swatch or `NULL` for global
 * @param $group
 * @param $option
 * @param $value
 */
function print_option_input($group, $option){
    global $options, $styles_values;



    $value = isset($styles_values[$group][$option]) ? $styles_values[$group][$option] : $options[$group][$option] ;



    $name = $group .".".$option;

    switch($option) {
        case 'background_image': {
            echo '<div id="background-selector"></div><input type="hidden" name="'.$name.'" value="'.$value.'">';
            break;
        }
        case 'font': {
            echo '<select name="'.$name.'"><option value="sans-serif">FONT</option></select>';
            break;
        }
        case 'fx_button': {
            $colors = Array ('Black', 'Blue', 'Gold', 'Green', 'Grey', 'Navy', 'Orange', 'Original', 'Pink', 'Purple', 'Red', 'Torquoise', 'White', 'Yellow');
            echo '<select name="'.$name.'">';
            foreach ($colors as $index => $color) {
                $selected = ($value == $color)?' selected=selected':'';

               echo '<option value="'.$color.'"'.$selected.' >'.$color.'</option>';
            }
            echo '</select>';
            break;
        }

        case 'background_image_scale': {
            $scales = Array ('Cover', 'Contain', 'Initial');
            echo '<select name="'.$name.'">';
            foreach ($scales as $index => $option) {
                $selected = ($value == $option)?' selected=selected':'';

                echo '<option value="'.$option.'"'.$selected.' >'.$option.'</option>';
            }
            echo '</select>';
            break;
        }
        case 'background_image_repeat': {
            $repeats = Array ('No-repeat', 'Repeat', 'Repeat-x', 'Repeat-y');
            echo '<select name="'.$name.'">';
            foreach ($repeats as $index => $option) {
                $selected = ($value == $option)?' selected=selected':'';
                echo '<option value="'.$option.'"'.$selected.' >'.$option.'</option>';
            }
            echo '</select>';
            break;
        }
        case 'box_shadow_size':
        case 'border_radius': {
            echo '<input name="'.$name.'" type="number" value="'.$value.'">';
            break;
        }
        default: {?>
            <input
                value="<?php echo rgb2HEX($value,true);?>"
                class="color-picker bg"
                data="<?php echo $name; ?>"
                data-opacity="<?php echo rgb2hex($value,false);?>"
                >
            <input
                name='<?php echo $name; ?>'
                type="hidden"
                value="<?php echo rgba_string($value);?>"
                >
        <?php
        break;
        }
    }
}
function print_options_gui_group($gui_name, $groups){ ?>
    <div class="collapsible-block">

    <div class="not-collapsible">
        <span class="title">
            <?php echo ucwords(str_replace('_',' ',$gui_name)) ?>
        </span>

        <div class="collapse-button"></div>
    </div>
    <div class="collapsible themeroller_settings" style="display:none;">
        <table class="themeroller_table">
            <?php

            foreach($groups as $group_name => $group_options) {
                if(count($groups) > 1){
                    echo '<tr><td class="group">'.strtoupper(ucwords(str_replace('_',' ',$group_name))).'</td></tr>';
                }
                ?>
                <?php foreach($group_options as $option => $value ) { ?>
                    <tr>
                        <td>
                            <label><?php echo ucwords(str_replace('_',' ', $option)); ?> </label>
                        </td>
                        <td style="padding-left: 15px; min-width: 100px; ">
                            <?php print_option_input($group_name, $option); ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php }

            ?>
        </table>
    </div>
    </div>
<?php } ?>

<style>
    .minicolors {
        width: 100%;
    }

    .minicolors-swatch {
        float: left;
    }

    .leftcolumn .metabox .content input {
        width: auto
    }

    .leftcolumn .metabox .content label {
        font-weight: 100;
    }

    .leftcolumn .metabox .group{
        margin: 0.5em;
        padding: 0.6em 0 0.4em;
        font-weight: 600;
    }

    .collapsible-block {
        overflow: inherit
    }
</style>
<script type="text/javascript" src="<?php echo CONF_SITE_URL?>mobile_app/js/Blowfish.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        var timeout = flexiweb.loaderShow();

        var applicationId = '<?php echo $app_id; ?>',
            versionId = '<?php echo $version_id; ?>',
            typeId = '<?php echo TYPE_APP_DATA;?>';

        var $versionSelect = $('#version-select');

        $versionSelect.bind('change', function(){
            var version_id = $versionSelect.val();
            location.search = (version_id > 0) ? "id="+ applicationId + '.' + version_id : "";
        });


//        var $preview = initPreview($('#preview-frame-size'), $('#preview-frame-orientation'), $('#preview-frame'));

        var previewFrame = document.getElementById("preview-frame");
        if(typeof previewFrame !== 'undefined' && previewFrame != null) {
            previewFrame.onload = function() {
                var $previewFrame = $("#preview-frame");
                var $previewFrameContent = $previewFrame.contents();
                var $backgroundPreview = $previewFrameContent.find('body');
                var $backgroundSelector = $("#background-selector");
                var $exportButton = $('#export_button');
                var $importButton = $('#import_visible_button');
                var $uploadFile = $('#uploadThemeFile');

                $backgroundSelector.ImageSelector({
                    placeholderSize:"large",
                    imageUrl: $('[name="global.background_image"]').val(),
                    path: ' ',
                    callback: function(u, p){

//                        if(!u && u != 'none') {
//                            $backgroundPreview.css('background-image','url('+ u +')');
//                            $backgroundPreview.css('background-repeat','repeat');
//                            $backgroundPreview.css('background-attachment','fixed');
//                            $backgroundPreview.find('.ui-page').css('background','transparent');
//                        }
//                        else {
//                            $backgroundPreview.css('background-image','none');
//                        }
//
                        $backgroundSelector.next('input').val( u );
                        $backgroundSelector.next('input').trigger('change');

                    }
                }, $backgroundPreview );

                $exportButton.bind('click', function() {
                    console.log(window.flexiweb.site_url + 'ajax/get_theme_archive.php?app_id=' + applicationId + '.' + $versionSelect.val());
                    window.open(window.flexiweb.site_url + 'ajax/get_theme_archive.php?app_id=' + applicationId + '.' + $versionSelect.val());
                });
                $importButton.bind('click', function() {
                    $('#uploadThemeFile').trigger('click');
                });
                $uploadFile.unbind('change').bind('change', function(){
                    $('#import_button').trigger('click');
                });

                if($previewFrame.length > 0) {
                    flexiweb.callFunction.getObject(typeId, versionId, function(object){
                        object.withEditor = true;
                        var themeroller = new Themeroller(object);
                        var $form = $('#colors-tabs');
                        var initThemeEditor = function( $form, data ) {
                            var $colorPicker = $('.color-picker'),
                                $collapseButton = $('.collapse-button');

                            $colorPicker.minicolors({
                                opacity: true,
                                hide: function(hex, rgba) {
                                    var $currentPicker = $(this),
                                        inputName = $currentPicker.attr('data'),
                                        val = $(this).minicolors('rgbaString');

                                    themeroller.previewRedraw(inputName, val);

                                }
                            });

                            $form.fx_accordion();

                            // redraw application when one of style values changed
                            $form.find('input[type="number"], input[type="hidden"], select').change(function(){
                                themeroller.previewRedraw($(this).attr('name'), $(this).val());
                            });

                            // find all inputs values and make styles
                            $.each($form.find('input, select'), function() {
                                var $this = $(this);
                                var optionName;

                                if(optionName = $this.attr('name')) {
                                    themeroller.previewRedraw(optionName, $this.val());
                                }
                            });

                            //for fixed top navigation menu
//                            $previewFrameContent.find('.page').css('padding-top', $previewFrameContent.find(".fx_top_navigation").outerHeight() + 'px');

                            $collapseButton.unbind('click').bind('click',collapseBlock);
                            $collapseButton.first().trigger('click');

                        };

                        initThemeEditor($form, object.style);

                        $("#save_button").bind('click',function(){
                            object.style = $form.toDeepJson();
                            flexiweb.callFunction.updateObject(object, function(){
                                alert('Object successfully changed!');
                            });
                        });

                        var el = document.getElementById('default_values_form');
                        el.addEventListener('submit', function(){
                            return confirm('Are you sure you want to restore default settings?');
                        }, false);
                        flexiweb.loaderHide(timeout);
                    });
                }
                else {
                    flexiweb.loaderHide(timeout);
                }
            }
        }
        else {
            flexiweb.loaderHide(timeout);
        }
    });
</script>