<script id="DataForm-template" type="text/html">
    <div class="widget_container">
        <div class="wrap">
            <h3><?php echo _('Form'); ?>:</h3>
            <select name="form_id" class="data-from-select">
                <?php foreach ($common_context["dataForms"] as $id => $form) { ?>
                    <option value="<?php echo $id; ?>">
                        <?php echo $form["display_name"]; ?>
                    </option>
                <?php } ?>
            </select>

        </div>

         <div class="wrap">
             <h3><?php echo _('Form mode'); ?>:</h3>
             <select name="mode" class="data-form-mode">
                 <option value="create"><?php echo _('Create'); ?></option>
                 <option value="edit"><?php echo _('Edit'); ?></option>
                 <option value="createWithActiveLink"><?php echo _('Create with active link'); ?></option>
             </select>
         </div>

         <div class="wrap">
            <h3><?php echo _('Table style'); ?>:</h3>
             <select name="table_style">
                 <option value=""><?php echo _('No borders'); ?></option>
                 <option value="borderedRows"><?php echo _('Bordered rows'); ?></option>
                 <option value="borderedColumns"><?php echo _('Bordered columns'); ?></option>
                 <option value="borderedCells"><?php echo _('Bordered cells'); ?></option>
             </select>
         </div>

        <div class="wrap">
            <h3><?php echo _('Table stripes'); ?>:</h3>
            <label>Activate</label>
            <input type="checkbox" name="table_stripes">
        </div>

        <div class="wrap">
            <h3><?php echo _('Use active object ID'); ?>:</h3>
            <label>Activate</label>
            <input type="checkbox" name="qrMode">
        </div>
    </div>
</script>

<script id="Chart-template" type="text/html">
    <div style="width: 49%; float: left;">
        <div class="wrap">
            <h3><?php echo _('Start Chart State'); ?>:</h3>
            <select name="chart_type">
                <option value="line">Line</option>
                <option value="area">Area</option>
                <option value="bar">Bar</option>
                <option value="stacked_bar" selected="selected">Stacked Bar</option>
                <option value="ypie">Pie</option>
                <option value="xpie">Multiple Pie</option>
            </select>
        </div>
        <div class="wrap">
            <h3><?php echo _('Select query'); ?>: </h3>
            <select name="query">
                <?php foreach ($common_context["queries"] as $id => $query) { ?>
                    <option value="<?php echo $id; ?>">
                        <?php echo $query["display_name"]; ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="wrap">
            <h3><?php echo _('Zoom Area'); ?>: </h3>
            <label>Visible</label>
            <input type="checkbox" checked="checked" name="zoom">
        </div>
        <div class="wrap types">
            <h3><?php echo _('Types'); ?>: </h3>
            <label>Line</label><input type="checkbox" checked="checked" name="line">
            <label>Area</label><input type="checkbox" checked="checked" name="area">
            <label>Bar</label><input type="checkbox" checked="checked" name="bar">
            <label>Stacked</label><input type="checkbox" checked="checked" name="stacked_bar">
            <label>Pie</label><input type="checkbox" checked="checked" name="xpie">
            <label>Multiple Pie</label><input type="checkbox" name="ypie">
        </div>
        <div class="wrap">
            <h3>Grouping:</h3>
            <label>Activate</label>
            <input type="checkbox" name="grouped">
        </div>

    </div>
    <div style="width: 49%; float: left;">
        <div class="wrap">
            <h3>Y:</h3>
            <select name="y-axis" multiple></select>
        </div>
        <div class="wrap">
            <h3>X:</h3>
            <select name="x-axis"></select>
        </div>

        <div class="wrap">
            <h3>Group By:</h3>
            <select name="group_by"></select>
        </div>

        <div class="wrap">
            <h3>Group For:</h3>
            <select name="group_for"></select>
        </div>
    </div>
</script>

<script id="Calendar-template" type="text/html">
    <label><?php echo _('Number of queries'); ?>: </label>
    <select name="numberOfQueries">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
    </select>


    <div class="firstQuery calendarQuery trrr" data-query-order="0">
        <div class="widget_option">
            <h3><?php echo _('Query'); ?>:</h3>

            <select data-type="query">
                <?php show_select_options(get_objects_by_type(get_type_id_by_name(0, 'query'), $schema), 'object_id', 'display_name'); ?>
            </select>
        </div>
        <div class="widget_option">
            <h3><?php echo _('Form'); ?>:</h3>
            <select data-type="form">
                <option value=""><?php echo _('Please select form'); ?></option>
            </select>
        </div>
        <div class="widget_option">
            <h3><?php echo _('Color'); ?>:</h3>
            <select data-type="color">
                <option value="#000000"><?php echo _('Black'); ?></option>
                <option value="#007FFF"><?php echo _('Blue'); ?></option>
                <option value="#5DFC0A"><?php echo _('Green'); ?></option>
                <option value="#000080"><?php echo _('Navy'); ?></option>
                <option value="#FFCC00"><?php echo _('Orange'); ?></option>
                <option value="#ff69b4"><?php echo _('Pink'); ?></option>
                <option value="#9B30FF"><?php echo _('Purple'); ?></option>
                <option value="#FF0000"><?php echo _('Red'); ?></option>
                <option value="#34DDDD"><?php echo _('Turquoise'); ?></option>
                <option value="#ffff00"><?php echo _('Yellow'); ?></option>
            </select>
        </div>
        <div class="widget_option">
            <h3><?php echo _('Title'); ?>:</h3>
            <select data-type="title"></select>
        </div>
        <div class="widget_option">
            <h3><?php echo _('Start date'); ?>:</h3>
            <select data-type="dateStart"></select>
        </div>
        <div class="widget_option">
            <h3><?php echo _('End date'); ?>:</h3>
            <select data-type="dateEnd">
                <option value="-1"><?php echo _('Unknown'); ?></option>
            </select>
        </div>
        <div class="widget_option">
            <h3><?php echo _('Start time'); ?>:</h3>
            <select data-type="timeStart"></select>
        </div>
        <div class="widget_option">
            <h3><?php echo _('End time'); ?>:</h3>
            <select data-type="timeEnd">
                <option value="-1"><?php echo _('Unknown'); ?></option>
            </select>
        </div>
    </div>

    <div class="otherQueries"></div>

</script>

<script id="QueryList-template" type="text/html">
    <div style="width: 49%; float: left;">
        <div class="wrap">
            <h3> <?php echo _('Query'); ?></h3>
            <select name="query" class="query-select">
                <?php foreach ($common_context["queries"] as $id => $query) { ?>
                    <option value="<?php echo $id; ?>"><?php echo $query["display_name"]; ?></option>
                <?php } ?>
            </select> <br>

            <?php echo _('Filter by active object'); ?>
            <input type="checkbox" name="filter_by_object">
        </div>
        <div class="wrap">
            <h3><?php echo _('Linked page'); ?>:</h3>
            <select name="link" >
                <option value=""><?php echo _('No'); ?></option>
            </select>
        </div>
        <div class="wrap">
            <h3><?php echo _('Image field'); ?>:</h3>
            <select name="image_field"></select>
        </div>
        <div class="wrap">
            <h3><?php echo _('Query enum'); ?>:</h3>
            <select name="query_enum"> </select>
        </div>
    </div>
    <div style="width: 49%; float: left;">
        <div class="wrap">
            <h3><?php echo _('Search'); ?>:</h3>
            <select name="search_type" class="search-type">
                <option value="no"><?php echo _('No search'); ?></option>
                <option value="simple"><?php echo _('Simple search'); ?></option>
                <option value="criteria"><?php echo _('Criteria search'); ?></option>
            </select>

            <div class="criteria-search-fields"></div>
        </div>
        <div class="wrap">
            <h3><?php echo _('Display type'); ?>:</h3>
            <select class="display-type" name="display_type">
                <option value="standard" selected><?php echo _('standard'); ?></option>
                <option value="html"><?php echo _('HTML text'); ?></option>
            </select>
            <div class="HTMLEditor" name="content"></div>
            <div class="standard-fields"></div>
        </div>
        <div class="wrap">
            <h3> <?php echo _('Items per page'); ?>:</h3>
            <select name="items_per_page">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5" selected>5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
                <option value="13">13</option>
                <option value="14">14</option>
                <option value="15">15</option>
                <option value="16">16</option>
                <option value="17">17</option>
                <option value="18">18</option>
                <option value="19">19</option>
                <option value="20">20</option>
                <option value="21">21</option>
                <option value="22">22</option>
                <option value="23">23</option>
                <option value="24">24</option>
                <option value="25">25</option>
                <option value="26">26</option>
                <option value="27">27</option>
                <option value="28">28</option>
                <option value="29">29</option>
                <option value="30">30</option>
            </select>
        </div>
        <div class="wrap">
            <h3><?php echo _('Table style'); ?>:</h3>
            <select name="table_style">
                <option value=""><?php echo _('No borders'); ?></option>
                <option value="borderedRows"><?php echo _('Bordered rows'); ?></option>
                <option value="borderedColumns"><?php echo _('Bordered columns'); ?></option>
                <option value="borderedCells"><?php echo _('Bordered cells'); ?></option>
            </select>

            <select name="table_stripes">
                <option value=""><?php echo _('No stripes'); ?></option>
                <option value="horizontal"><?php echo _('Horizontal'); ?></option>
                <option value="vertical"><?php echo _('Vertical'); ?></option>
                <option value="both"><?php echo _('Both'); ?></option>
            </select>

        </div>

    </div>
</script>

<script id="iBeaconQuery-template" type="text/html">
    <div style="width: 49%; float: left;">
        <div class="wrap">
            <h3> <?php echo _('Query'); ?></h3>
            <select name="query" class="query-select">
                <?php foreach ($common_context["queries"] as $id => $query) { ?>
                    <option value="<?php echo $id; ?>"><?php echo $query["display_name"]; ?></option>
                <?php } ?>
            </select> <br>

            <input type="checkbox" name="filter_by_object">
            <?php echo _('Filter by active object'); ?>
        </div>
        <div class="wrap">
            <h3><?php echo _('Linked page'); ?>:</h3>
            <select name="link">
                <option value=""><?php echo _('No'); ?></option>
            </select>
        </div>
        <div class="wrap">
            <h3><?php echo _('Image field'); ?>:</h3>
            <select name="image_field"></select>
        </div>
        <div class="wrap">
            <h3><?php echo _('Query enum'); ?>:</h3>
            <select name="query_enum"> </select>
        </div>

    </div>
    <div style="width: 49%; float: left;">
        <div class="wrap">
            <h3><?php echo _('Search'); ?>:</h3>
            <select name="search_type" class="search-type">
                <option value="no"><?php echo _('No search'); ?></option>
                <option value="simple"><?php echo _('Simple search'); ?></option>
                <option value="criteria"><?php echo _('Criteria search'); ?></option>
            </select>

            <div class="criteria-search-fields"></div>
        </div>
        <div class="wrap">
            <h3><?php echo _('Display type'); ?>:</h3>
            <select class="display-type" name="display_type">
                <option value="standard" selected><?php echo _('standard'); ?></option>
                <option value="html"><?php echo _('HTML text'); ?></option>
            </select>
            <div class="HTMLEditor" name="content"></div>
            <div class="standard-fields"></div>
        </div>
        <div class="wrap">
            <h3> <?php echo _('Items per page'); ?>:</h3>
<!--            <select name="items_per_page">-->
<!--                <option value="1">1</option>-->
<!--                <option value="2">2</option>-->
<!--                <option value="3">3</option>-->
<!--                <option value="4">4</option>-->
<!--                <option value="5" selected>5</option>-->
<!--                <option value="6">6</option>-->
<!--                <option value="7">7</option>-->
<!--                <option value="8">8</option>-->
<!--                <option value="9">9</option>-->
<!--                <option value="10">10</option>-->
<!--                <option value="11">11</option>-->
<!--                <option value="12">12</option>-->
<!--                <option value="13">13</option>-->
<!--                <option value="14">14</option>-->
<!--                <option value="15">15</option>-->
<!--                <option value="16">16</option>-->
<!--                <option value="17">17</option>-->
<!--                <option value="18">18</option>-->
<!--                <option value="19">19</option>-->
<!--                <option value="20">20</option>-->
<!--                <option value="21">21</option>-->
<!--                <option value="22">22</option>-->
<!--                <option value="23">23</option>-->
<!--                <option value="24">24</option>-->
<!--                <option value="25">25</option>-->
<!--                <option value="26">26</option>-->
<!--                <option value="27">27</option>-->
<!--                <option value="28">28</option>-->
<!--                <option value="29">29</option>-->
<!--                <option value="30">30</option>-->
<!--            </select>-->
        </div>
        <div class="wrap">
            <h3><?php echo _('Table style'); ?>:</h3>
            <select name="table_style">
                <option value=""><?php echo _('No borders'); ?></option>
                <option value="borderedRows"><?php echo _('Bordered rows'); ?></option>
                <option value="borderedColumns"><?php echo _('Bordered columns'); ?></option>
                <option value="borderedCells"><?php echo _('Bordered cells'); ?></option>
            </select>

            <select name="table_stripes">
                <option value=""><?php echo _('No stripes'); ?></option>
                <option value="horizontal"><?php echo _('Horizontal'); ?></option>
                <option value="vertical"><?php echo _('Vertical'); ?></option>
                <option value="both"><?php echo _('Both'); ?></option>
            </select>

        </div>
        <div class="wrap">
            <label>Beacon UUID:</label>
            <select name="beacons"></select>
            <br>
            <label>Beacon Field:</label>
            <select name="beacon_field"></select>
            <br>
            <label>Update Rate:</label>
            <select name="update_rate">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5" selected>5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
            </select>

        </div>
    </div>
</script>

<script id="QRCodeScanner-template" type="text/html">
    <div class="widget_option">
        <h3><?php echo _('Link to'); ?>:</h3>
        <select name="link">
            <option value=""><?php echo _('No'); ?></option>
        </select>
    </div>
</script>

<script id="HTMLBlock-template" type="text/html">
    <div class="HTMLEditor" name="content"></div>
</script>

<script id="Gallery-template" type="text/html">
    <div class="widget_option">
        <h3><?php echo _('Select query'); ?>:</h3>
        <select data-type="query" class="query-select">
            <?php show_select_options(get_objects_by_type(get_type_id_by_name(0, 'query'), $schema), 'object_id', 'display_name'); ?>
        </select>
    </div>

    <div class="widget_option">
        <h3><?php echo _('Get images from field'); ?>: </h3>
        <select data-type="image-field" name="image_field"></select>
    </div>

    <div class="widget_option">
        <h3><?php echo _('Get titles from field'); ?>: </h3>
        <select data-type="title-field" class="title-field-select"></select>
    </div>
    
    <div class="widget_option">
        <h3><?php echo _('Fit by'); ?>:</h3>
        <select data-type="image-style" class="image-style-select">
            <option value="height"><?php echo _('Height'); ?></option>
            <option value="width"><?php echo _('Width'); ?></option>
        </select>
    </div>
</script>

<script id="DataSetRoles-template" type="text/html">
    <label><?php echo _('One role per data set'); ?>: </label>
    <input type="checkbox" name="oneRole">
</script>

<script id="Maps-template" type="text/html">
    <div style="overflow: hidden">
        <div class="widget_option">
            <h3><?php echo _('Select query'); ?>:</h3>
            <select data-type="query">
                <?php show_select_options(get_objects_by_type(TYPE_QUERY, $schema), 'object_id', 'display_name'); ?>
            </select>
        </div>

        <div class="widget_option">
            <h3>Name field:</h3>
            <select data-type="name"></select>
        </div>

        <div class="widget_option">
            <h3>Location field:</h3>
            <select data-type="location"></select>
        </div>

        <div class="widget_option">
            <h3>Location additional field:</h3>
            <select data-type="location_additional"></select>
        </div>
    </div>

</script>


<script id="iBeacon-template" type="text/html">
    <select name="uuid"></select>
</script>


<!-- "navigationMenuItem" -> navigation_item -->

<script id="bottom_navigation_widget" type="text/html">
    <div>
        <div class="navigation_item" data-page="1">
            <select class="linked_page"></select>
            <span class="icon_select"></span>
        </div>

        <div class="navigation_item" data-page="2">
            <select class="linked_page"></select>
            <span class="icon_select"></span>
        </div>

        <div class="navigation_item" data-page="3">
            <select class="linked_page"></select>
            <span class="icon_select"></span>
        </div>

        <div class="navigation_item" data-page="4">
            <select class="linked_page"></select>
            <span class="icon_select"></span>
        </div>
    </div>
</script>



<!--".remove" and "*collapse*" classes used in JS !!! -->
<script id="common-widget-template" type="text/html">
    <div class='collapsible-block widget'>
        <div class="not-collapsible handle">
            <span class="title"></span>
            <a href="#" class="collapse-button"></a>
        </div>
        <div class="collapsible" style="display: none; overflow: hidden; text-align: left;">
            <div class="widget_header">
                <div class="widget_header_option">
                    <label><?php echo _('Title'); ?>:</label>
                     <input type="text" name="title">
                </div>

                <div class="widget_header_option">
                    <label><?php echo _('Inset style'); ?></label>
                    <input type="checkbox" name="inset_style" checked>
                </div>

                <div class="widget_header_option">
                    <label><?php echo _('Header bar'); ?></label>
                    <input type="checkbox" name="header_bar" checked>
                </div>

                <img class="remove" src="<?php echo URL;?>images/remove.png" alt="×">
            </div>
            <div class="widget_content"></div>
        </div>
    </div>
</script>

<script id="common_widget_without_params" type="text/html">
    <div class='collapsible-block'>
        <div class="not-collapsible handle">
            <span class="title"></span>
            <a href="#" class="collapse-button"></a>
        </div>
        <div class="collapsible" style="display:none; overflow: hidden; text-align: left;"></div>
    </div>
</script>

<script id="navigation-menu-url-item-template" type="text/html">
    <li class="palette-item" style="display: block;" data-url>
        <?php echo _('URL'); ?>: <input type="text" name="url">
        <img class="remove" src="<?php echo URL;?>images/remove.png" alt="×">

    </li>
</script>

<script id="navigation-menu-page-item-template" type="text/html">
    <li class="palette-item" style="display: block;">
        <span class="page-name"></span>
        <img class="remove" src="<?php echo URL;?>images/remove.png" alt="×">
    </li>
</script>


<script id="page_tab" type="text/html">
    <li id="{{id}}">
        <span style="float:left;">{{name}}</span>
        <a href="#" class="delete_page"></a>
    </li>
</script>


<script id="HTMLEditor" type="text/html">
    <div style="display: none;" id="HTMLEditorWindow">
        <div style="float:left">
            <textarea id="HTMLEditorText" class="mceEditor" style="width: 300px; height: 300px;"></textarea>
        </div>

        <div style="float: right; text-align: center; width: 120px;" id="HTMLEditorShortCodes"></div>

        <div style="clear: both;">
            <div class="divider"><div class="left-grad"> </div><div class="right-grad"></div></div>
            <a href="#" id="saveHTMLButton" class="button green">Save</a>
            <a href="#" id="closeHTMLButton"  class="button red" >Cancel</a>
        </div>
    </div>
</script>
