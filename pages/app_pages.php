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

if (isset($_GET['object_id'])) {
    $app_data_id = $_GET['object_id'];
}
elseif (isset($_GET['id'])) {
    $app_data_id = $_GET['id'];
}

if (isset($_GET['object_id'])) {
    list (, $app_data_id) = explode('.', $_GET['object_id']);
} elseif (isset($_GET['id'])) {
    list (, $app_data_id) = explode('.', $_GET['id']);
}

if (!$app_id):

    fx_show_metabox(array('body' => array('content' => new FX_Error('app_release_manager', _('Please select Application'))),
        'footer' => array('hidden' => true)));

    $options = array(
        'set_id' => 0,
        'schema_id' => $schema,
        'object_type_id' => TYPE_APPLICATION,
        'fields' => array('object_id', 'created', 'modified', 'description', 'display_name'),
        'actions' => array('edit')
    );

    $mb_data = array(
        'header' => array('hidden' => true),
        'body' => array('content' => object_explorer($options)),
        'footer' => array('hidden' => true)
    );

    fx_show_metabox($mb_data);

else:

    $application_type_id = TYPE_APP_DATA;

    $common_context["application_object_type"] = TYPE_APPLICATION;

    $applications = get_objects_by_type(TYPE_APPLICATION, $schema);

    $common_context["queries"] = get_objects_by_type(TYPE_QUERY, $schema);

    $reformed_queries = array();

    foreach ($common_context["queries"] as $id => $query_obj) {
        $flat_fields_array = array();
        $flat_fields_array_without_joins = array();


        $query_obj["code"] = (array)json_decode($query_obj["code"], true);

        foreach ($query_obj["code"] as $type => $type_fields) {
            $flat_fields_array[$type_fields['name']][] = $type_fields['alias'];

            if ($type_fields['parent_type'] == 0) {
                $flat_fields_array_without_joins[$type_fields['name']] = $type_fields['alias'];
            }

        }
//        $query_obj["code"] = $flat_fields_array;
        $query_obj["code_without_joins"] = $flat_fields_array_without_joins;

        $reformed_queries[$id] = $query_obj;
    }

    $common_context["old_queries"] = get_objects_by_type(TYPE_QUERY, $schema);
    $common_context["queries"] = $reformed_queries;
    $common_context["charts"] = get_objects_by_type(TYPE_CHART, $schema);
    $common_context["dataForms"] = get_objects_by_type(TYPE_DATA_FORM, $schema);
    $common_context["enum"] = get_schema_enums($schema);
    $common_context["types"] = get_schema_types($schema) + get_schema_types(0);
    $common_context["schema_id"] = $schema;
    $common_context["api_key"] = $_SESSION["api_key"];

    //  filter data forms by type
    $filtered_data_forms = array();
    $forms = get_objects_by_type(TYPE_DATA_FORM, $schema);

    foreach ($forms as $id => $form_data) {
        $arr = array();
        //        $arr['id'] = $form_data['object_id'];
        $arr['display_name'] = $form_data['display_name'];
        $filtered_data_forms[$form_data['object_type']][$form_data['object_id']] = $arr;
    }

    $filtered_data_forms = json_encode($filtered_data_forms);

    $common_context["active_application_id"] = $app_id;

    $versions = get_app_data($app_id);

    $data = $versions[$app_data_id];
    $app_name = $data['name'];
    $version_name = $data['version'];
    $app_display_name = $data['display_name'];
    $data = json_encode($data);


    function app_editor_controls_content ($versions, $app_id, $app_data_id ) { ?>

        <label for="object_name">Version Name:</label>
        <input type="text" id="object_name" value="">

        <label for="object_select">Select Version:</label>
        <select id="object_select">
            <option value="<?php echo $app_id;?>"><?php echo _('New version'); ?></option>
            <?php foreach ($versions as $id => $app_data) { ?>
                <option value='<?php echo $app_id . '.' . $id; ?>' <?php echo ($id == $app_data_id) ? "selected" : ""; ?> ><?php echo $app_data['display_name']; ?></option>
            <?php } ?>
        </select>


        <div>

            <label><?php echo _('Start page')?>:</label>
            <select id="start-page-select"></select>
        </div>
        <div>
            <label><?php echo _('Bottom navigation menu')?>:<input type="checkbox" id="bottom_navigation_menu"></label>
        </div>
<!--        <div>-->
<!--            <label>--><?php //echo _('iBeacon')?><!--:<input type="checkbox" id="ibeacon_enable"></label>-->
<!--        </div>-->
    <?php }


    function app_editor_controls_footer($app_id) {  ?>
        <input type="button" id="save" class="button green" value="Save">
        <input type="button" id="remove" class="button red" value="Remove">

<!--        <a href="--><?php //echo CONF_SITE_URL . 'app_editor/app_objects?object_id=' . $app_id;?><!--"-->
<!--           class="button blue" id="release-button">--><?php //echo _('App Manager'); ?><!--</a>-->

<!--        <input type="button" class="button gray" id="go-to-release-button" value="Release">-->

        <a href="<?php echo CONF_SITE_URL . 'app_editor/app_release_manager?object_id=' . $app_id;?>"
           class="button blue" id="release-button"><?php echo _('Release Manager');?></a>
        <hr>

        <input type="button" class="button blue" id="download-single-app-button" value="Download Specify App">
        <input type="button" class="button blue" id="download-button" style="display: inline-block;float: none;" value="Download Generic App">
        <input type="button" class="button green" id="switch_display_mode" data-is-design-state="true" value="Switch to Design mode">
    <?php }

    function widgets_content() {?>
        <ul class="widgets app_editor compact" id="widgets"></ul>
    <?php }

    function app_editor_content ($common_context){?>
        <div class="form-inline">
            <label> <?php echo _('Name')?> <input id="page-name-input"> </label>
<!--            <span>-->
<!--                --><?php //echo _('Context type')?><!--:-->
<!--                <a id="context-type-label" target="_blank"></a>-->
<!--            </span>-->
<!--            <select id="context_type"></select>-->
<!---->
<!--            <a href="#" class="button go-to-type" id="go_to_context_type" target="_blank" title="Go to type">></a>-->

            <label class="hide_page_in_navigation">
                <?php echo _('Hide this Page In Navigation')?>
            </label>

            <input type="checkbox" id="hide-in-navigation-checkbox">

        </div>
        <hr>

        <div id="common-widgets" class="app_editor"></div>
        <div id="stack" class="editor app_editor"></div>
    <?php }
    function app_editor_header (){?>
        <ul class="tabs" id="pages-tabs-container">
            <li id="add-page-button">CREATE NEW PAGE</li>
        </ul>
    <?php }
    function preview_content() {?>
        <div class="preview-nav">
            Screen Resolution:
            <select id="preview-frame-size">
                <option data-width="320" data-height="480">320x480</option>
                <option data-width="480" data-height="800" selected="selected">480x800</option>
                <option data-width="640" data-height="960">640x960</option>
                <option data-width="640" data-height="1136">640x1136</option>
                <option data-width="720" data-height="1280">720x1280</option>
                <option data-width="768" data-height="1024">768x1024</option>
                <option data-width="900" data-height="1400">900x1400</option>
                <option data-width="1536" data-height="2048">1536x2048</option>
            </select>
            <select id="preview-frame-orientation">
                <option value="portrait">Portrait</option>
                <option value="landscape">Landscape</option>
            </select>
        </div>
        <iframe id="preview-frame" name="preview-frame"></iframe>
        <iframe id="download-frame"></iframe>
    <?php }

    $app_controls = array(
        'header' => array('content' => $app_display_name ? $app_display_name  : _("New Version")),
        'body' => array('function' => 'app_editor_controls_content', 'args' => array($versions, $app_id, $app_data_id), 'id' => 'app_selection'),
        'footer' => array('function' => 'app_editor_controls_footer', 'args' => array($app_id))
    );
    $widgets = array(
        'header' => array('content' => _('Widgets')),
        'body' => array('function' => 'widgets_content', 'id' => 'app_selection'),
        'footer' => array('hidden' => true)
    );
    $app_editor = array(
        'header' => array('function' => 'app_editor_header', 'class' => 'with_tabs'),
        'body' => array('function' => 'app_editor_content'),
        'footer' => array('hidden' => true)
    );
    $preview = array(
        'header' => array('content' => _('Preview')),
        'body' => array('function' => 'preview_content'),
        'footer' => array('hidden' => true)
    );

    echo '<div class="leftcolumn">';
        fx_show_metabox($app_controls);
        fx_show_metabox($widgets, false, 'palette');
    echo '</div>';

    echo '<div class="rightcolumn">';
        fx_show_metabox($app_editor, 'pages_editor_metabox', 'tabbed');
        fx_show_metabox($preview, 'preview_metabox');
    echo '</div>';

    $schema_id = $_SESSION['current_schema'];
    $apps_with_versions = get_app_version_list($schema_id);
    $apps_json = json_encode($apps_with_versions);

    ?>

    <span id="templates" style="display: none">
       <?php include dirname(__FILE__) . '/../fx_ui/fx_app_editor_widgets.php'; ?>
    </span>

    <script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/editor.main.js"></script>
    <script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/editor.application.js"></script>
    <script type="text/javascript" src="<?php echo CONF_SITE_URL?>mobile_app/js/blowfish.js"></script>


    <script>
        window.onPreviewPageChange = $.noop;
        var commonContext = JSON.parse("<?php echo addslashes(json_encode($common_context)) ?>");
        console.log(commonContext);

        $(document).ready(function(){
            var ibeacons = '<?php echo json_encode(get_objects_by_type(TYPE_IBEACON_UUID, 0)); ?>';
            var parent = '<?php echo $app_id; ?>';
            var filteredForms = JSON.parse('<?php echo $filtered_data_forms?>');
            new ApplicationEditor({ commonContext: commonContext, beacons: ibeacons, parent_app: parent, filteredForms: filteredForms });
            initPreview($('#preview-frame-size'), $('#preview-frame-orientation'), $('#preview-frame'));
        });
    </script>


    <div id="create-page-dialog" style="display: none">
        <label>
            <?php echo _('Name'); ?>:
            <input type="text" id="create-page-dialog-name-input" value="New">
        </label>
        <label>
            <?php echo _('Active object type'); ?>:
            <select id="create-page-dialog-context-type-select">
                <option value=""><?php echo _('No'); ?></option>
                <option value="<?php echo get_type_id_by_name(0, 'subscription'); ?>"><?php echo _('Subscription'); ?></option>
                <?php foreach ($common_context["types"] as $id => $type) { ?>
                    <option value="<?php echo $id; ?>"><?php echo $type["display_name"]; ?></option>
                <?php } ?>
            </select>
        </label>
        <hr>
        <a href="#" class="button green" id="create-page-dialog-create-button"><?php echo _('Create'); ?></a>
        <a href="#" class="button red" id="create-page-dialog-cancel-button"><?php echo _('Cancel'); ?></a>
    </div>
<?php endif; ?>