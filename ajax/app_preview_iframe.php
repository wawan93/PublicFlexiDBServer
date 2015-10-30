<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();
s
?>

<!DOCTYPE HTML>
<html>

    <head>
        <script type="text/javascript" src="<?php echo URL ?>js/jquery.min.js"></script>
        <!--    <script type="text/javascript" src="--><?php //echo URL?><!--js/jquery.mobile-1.3.1.min.js"></script>-->
        <style type="text/css" id="generator_styles"></style>


        <link href="<?php echo URL ?>mobile_app/css/normalize.css" rel="stylesheet" type="text/css">
        <link href="<?php echo URL ?>mobile_app/css/main.css" rel="stylesheet" type="text/css">
        <link href="<?php echo URL ?>mobile_app/css/flexiapp.css" rel="stylesheet" type="text/css">
        <link href="<?php echo URL ?>mobile_app/css/jquery-ui.custom.css" rel="stylesheet" type="text/css">
        <link href="<?php echo URL ?>mobile_app/css/jquery.timepicker.css" rel="stylesheet" type="text/css">
    </head>

    <body id="application">
    <div class="fx_top_navigation" style="">
        <input type="button" class="button_back fx_button button_rounded" value="Back">
        <input type="button" class="fx_button button_rounded exit_to_generic_app_button" value="">
        <select class="fx_app_navigation_select" style="display: inline-block;">
            <option class="navigation_item" value="0">Test</option>
            <option class="navigation_item" value="1">Test#2</option>
            <option class="navigation_item" value="2">Test#3</option>
            <option class="navigation_item" value="9">Users</option>
        </select>
    </div>
        <div class="page active" data-page-id="6">
            <form class="fx_dataform fx_widget inset" id="DataForm2">
                <h3 class="fx_widget_title">Data Form Header</h3>

                <div class="fx_widget_content fx_dataform_content stripes_horizontal">
                    <div class="fx_dataform_option display_name fx-control-textarea">
                        <label class="fx_dataform_label">Textarea</label>

                        <div class="fx_dataform_input_container">
                            <textarea name="display_name" data-control-type="textarea"></textarea>
                        </div>
                    </div>
                    <div class="fx_dataform_option url_field fx-control-textbox">
                        <label class="fx_dataform_label">Textbox</label>

                        <div class="fx_dataform_input_container">
                            <input class="fx_dataform_input" name="url_field" type="text" disabled="disabled"
                                   data-control-type="textbox">
                        </div>
                    </div>
                    <div class="fx_dataform_option file_field fx-control-fileSelect">
                        <label class="fx_dataform_label">File: </label>
                        <a href="#" class="fx_dataform_input_container">Download File</a>
                    </div>
                    <div class="fx_dataform_link" data-control-type="checkboxGroup" data-type-id="20">
                        <label class="fx_link_title">Checkboxes</label>

                        <div class="fx_linking_types">
                            <label>Checkbox 1<input type="checkbox" checked="checked"></label>
                            <label>Checkbox 2<input type="checkbox"></label>
                        </div>
                    </div>
                    <div class="fx_dataform_link" data-control-type="radio" data-type-id="24">
                        <label class="fx_link_title">Radiobuttons</label>

                        <div class="fx_linking_types">
                            <label>None<input type="radio" name="radio[]"></label>
                            <label>Radio option <input type="radio" name="radio[]" checked="checked"></label>
                        </div>
                    </div>
                    <div class="fx_dataform_option image_field fx-control-imageSelect">
                        <label class="fx_dataform_label">image_field</label>

                        <div class="fx_dataform_input_container">
                            <input class="fx_dataform_input" name="image_field" data-control-type="imageSelect" type="hidden"
                                   value="">

                            <img class="thumbnail_with_border" src="<?php echo CONF_SITE_URL ?>/uploads/empty_medium.png">
                            <input type="button" class="fx_button take_photo" value="Take New">
                            <input type="button" class="fx_button take_photo" value="Choose Existing">
                        </div>
                    </div>
                </div>

                <div class="fx_dataform_controls">
                    <input type="button" class="updateObject fx_button button_rounded fx_button_apply" value="Update Object">
                    <input type="button" class="deleteObject fx_button button_rounded fx_button_danger" value="Delete Object">
                </div>

            </form>

            <div class="fx_query_list fx_widget inset fx_querylist">
                <h3 class="fx_widget_title">Parts (INSET)</h3>
                <div class="fx_widget_content">
                    <div class="fx_widget_content fx_querylist_content" id="214_content">
                        <table id="214_page_1" data-page="1" class="fx_querylist_page bordered  stripes_horizontal" style="display: table;">
                            <thead>
                                <th style="text-transform: capitalize;">Object ID</th>
                                <th style="text-transform: capitalize;">Name</th>
                                <th style="text-transform: capitalize;">Overall Status</th>
                            </thead>

                            <tbody>
                                <tr data-object-id="3" class="link">
                                    <td class="fx_querylist_item object_id">3</td>
                                    <td class="fx_querylist_item display_name">H4567 edited</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Signed Off</td>
                                </tr>

                                <tr data-object-id="5" class="link">
                                    <td class="fx_querylist_item object_id">5</td>
                                    <td class="fx_querylist_item display_name">Part</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Problem</td>
                                </tr>

                                <tr data-object-id="8" class="link">
                                    <td class="fx_querylist_item object_id">8</td>
                                    <td class="fx_querylist_item display_name">TEST</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Not Started</td>
                                </tr>

                                <tr data-object-id="9" class="link">
                                    <td class="fx_querylist_item object_id">9</td>
                                    <td class="fx_querylist_item display_name">One of 10000 parts</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Ready to Test</td>
                                </tr>

                                <tr data-object-id="16" class="link">
                                    <td class="fx_querylist_item object_id">16</td>
                                    <td class="fx_querylist_item display_name">H66544</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">In Progress</td>
                                </tr>
                            </tbody>
                        </table>
                        <table id="214_page_2" data-page="2" class="fx_querylist_page bordered  stripes_horizontal" style="display: none;">
                            <thead>
                                <th style="text-transform: capitalize;">Object ID</th>
                                <th style="text-transform: capitalize;">Name</th>
                                <th style="text-transform: capitalize;">Overall Status</th>
                            </thead>
                            <tbody>
                                <tr data-object-id="17" class="link">
                                    <td class="fx_querylist_item object_id">17</td>
                                    <td class="fx_querylist_item display_name">He's throw test</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Verified</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="fx_querylist_navigation" style="" id="214_navigation">
                    <div class="previous_page navigate_button fx_button">Previous</div>
                    <div class="input_with_pages">
                        <span class="current_page_number">1</span>
                        <span>/</span>
                        <span class="total_page_number">2</span>
                    </div>
                    <div class="next_page navigate_button fx_button">Next</div>
                </div>

                <div class="fx_querylist_search" id="214_search">
                    <input class="search_input" type="text" value="">
                    <input class="start_search fx_button" type="button" value="Search">
                </div>
            </div>
            <div class="fx_query_list fx_widget fx_querylist">
                <h3 class="fx_widget_title">Parts</h3>
                <div class="fx_widget_content">
                    <div class="fx_widget_content fx_querylist_content" id="214_content">
                        <table id="214_page_1" data-page="1" class="fx_querylist_page bordered  stripes_horizontal" style="display: table;">
                            <thead>
                                <th style="text-transform: capitalize;">Object ID</th>
                                <th style="text-transform: capitalize;">Name</th>
                                <th style="text-transform: capitalize;">Overall Status</th>
                            </thead>

                            <tbody>
                                <tr data-object-id="3" class="link">
                                    <td class="fx_querylist_item object_id">3</td>
                                    <td class="fx_querylist_item display_name">H4567 edited</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Signed Off</td>
                                </tr>

                                <tr data-object-id="5" class="link">
                                    <td class="fx_querylist_item object_id">5</td>
                                    <td class="fx_querylist_item display_name">Part</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Problem</td>
                                </tr>

                                <tr data-object-id="8" class="link">
                                    <td class="fx_querylist_item object_id">8</td>
                                    <td class="fx_querylist_item display_name">TEST</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Not Started</td>
                                </tr>

                                <tr data-object-id="9" class="link">
                                    <td class="fx_querylist_item object_id">9</td>
                                    <td class="fx_querylist_item display_name">One of 10000 parts</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Ready to Test</td>
                                </tr>

                                <tr data-object-id="16" class="link">
                                    <td class="fx_querylist_item object_id">16</td>
                                    <td class="fx_querylist_item display_name">H66544</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">In Progress</td>
                                </tr>
                            </tbody>
                        </table>
                        <table id="214_page_2" data-page="2" class="fx_querylist_page bordered  stripes_horizontal" style="display: none;">
                            <thead>
                                <th style="text-transform: capitalize;">Object ID</th>
                                <th style="text-transform: capitalize;">Name</th>
                                <th style="text-transform: capitalize;">Overall Status</th>
                            </thead>
                            <tbody>
                                <tr data-object-id="17" class="link">
                                    <td class="fx_querylist_item object_id">17</td>
                                    <td class="fx_querylist_item display_name">He's throw test</td>
                                    <td class="fx_querylist_item ctx_part_overall_status">Verified</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="fx_querylist_navigation" style="" id="214_navigation">
                    <div class="previous_page navigate_button fx_button">Previous</div>
                    <div class="input_with_pages">
                        <span class="current_page_number">1</span>
                        <span>/</span>
                        <span class="total_page_number">2</span>
                    </div>
                    <div class="next_page navigate_button fx_button">Next</div>
                </div>

                <div class="fx_querylist_search" id="214_search">
                    <input class="search_input" type="text" value="">
                    <input class="start_search fx_button" type="button" value="Search">
                </div>
            </div>
        </div>

    </body>
</html>