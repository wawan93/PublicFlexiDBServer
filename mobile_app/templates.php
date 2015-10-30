<script id="specific_application" type="text/html">
    <div class="application_block" id="application">
        <style type="text/css" id="generator_styles"></style>

        <div id="body_background"></div>

        <div class="fx_top_navigation" id="top_navigation">
            <div class="buttons">
                <input type="button" class="fx_button back" value="">
                <input type="button" class="fx_button refresh" value="">
                <input type="button" class="fx_button exit" value="">
            </div>
            <div class="with_set_selector">
                <select class="fx_app_navigation_select" style="display: inline-block;"></select>
            </div>
        </div>

        <div id="application_pages"></div>

    </div>
</script>

<script id="specific_app_page" type="text/html">
    <div class="page" data-page-id="{{id}}">
        <ul class="page_content scroller_content"></ul>
    </div>
</script>


<!-- pages -->
<script id="page_profile" type="text/html">
    <img class="avatar" src="{{avatar}}">
    <img class="qrCode" src="{{qr_code}}">
    <h2 class="title">{{display_name}}</h2>
    <ul class="user_data"></ul>

    <div class="button red removeLocalData">Remove Local Data</div>
    <div class="button green edit">Edit details</div>
    <div class="button red logout">Logout</div>

</script>

<script id="page_application_info" type="text/html">
    <div class="separate_block">
        <div class="button back" style="display: block">Back</div>
        <table class="versionsList"></table>
    </div>
    <div class="separate_block">
        <img class="app_icon" src="{{icon_large}}">
        <h2 class="title">{{title}}</h2>
        <div class="description">{{description}}</div>
    </div>
    <div class="separate_block app_marks">
        <div class="app_overall_mark">
            <div class="app_overall_mark_inner">
                <span>{{rating}}</span>
                <div class="stars">
                    <div class="active_stars"></div>
                </div>
            </div>
        </div>
        <div class="app_number_each_mark"></div>
    </div>
    <div class="separate_block app_review" style="display: none"></div>
    <div class="separate_block">
        <h2 class="title">App Info</h2>
        <ul class="app_info"></ul>
        <div class="contacts">{{contacts}}</div>
    </div>
</script>

<script id="page_reviews" type="text/html">
    <div class="button back">Back</div>
    <div class="separate_block app_marks"></div>
    <div class="app_reviews"></div>
</script>
<script id="page_subscription" type="text/html">
    <div class="button back">Back</div>

    <div class="warning">You must provide the following information for subscribe:</div>
    <ul class="baseFields"></ul>

    <div class="warning"> Please fill next fields before subscribe:</div>
    <ul class="sfxFields"></ul>

    <div class="button submit green">Submit</div>
</script>
<script id="page_datasets" type="text/html">
    <div class="button back">Back</div>

    <div class="dataset_header">
        <img class="dataset_logo" src="{{icon_large}}">
        <p class="dataset_title_big">Data Sets for</p>
        <p class="dataset_title_general">{{application_name}}</p>
        <p class="dataset_title_italic">Select required data sets for download</p>

    </div>

    <table class="dataset_list"></table>
    <div class="button back">Back</div>
    <div class="button blue download" style="margin-bottom: 1em">Download</div>
</script>

<script id="dataset_row" type="text/html">
    <tr class="dataset_row">
        <td class="dataset_name">{{display_name}}</td>
        <td class="downloaded">(Downloaded: Never.)</td>
        <td>
            <div class="checkbox inactive" data-set="{{object_id}}">
            </div>
        </td>
    </tr>
</script>

<!-- app review template -->
<script id="app_review" type="text/html">
    <h4 class="authorName title">{{user.display_name}}</h4>
    <p style="font-style: italic">{{review}}</p>
    <div class="stars" data-stars="{{rating}}">
        <div class="active_stars"></div>
    </div>
</script>

<!-- line with data -->
<script id="property_read_only" type="text/html">
    <li class="property" data-type="{{property_name}}">
        <span class="property_name">{{property_name}}:</span>
        <span class="property_value" data-type="{{property_name}}">{{property_value}}</span>
    </li>
</script>
<script id="property_edit" type="text/html">
    <li class="property" data-type="{{property_name}}">
        <span class="property_name">{{property_name}}:</span>
        <input class="property_value" type="text" name="{{property_name}}" value="{{property_value}}">
    </li>
</script>

<!-- buttons -->
<script id="buttons_block" type="text/html">
    <tr class="buttons_block" data-version="{{version}}">
        <td class="title">
            {{version}}
        </td>
        <td class="withButton">
            <div class="button install blue">Install</div>
            <div class="button start green">Start</div>
        </td>
        <td class="withButton">
            <div class="button uninstall red">Uninstall</div>
        </td>
    </tr>
</script>

<!-- data set roles widget -->
<script id="widget_roles" type="text/html">
    <div class="removeRoles">
        <select class="userSelect">
            <option value="0">Please select user</option>
        </select>
        <ul class="userRoles" style="padding: 0;list-style: none;"></ul>
    </div>

    <div class="addRoles">
        <div>
            <label>User ID or API_key: </label>
            <input type="text" data-type="user">
        </div>

        <div>
            <label>Select role:</label>
            <select data-type="roles"></select>
        </div>

        <div class="button add blue">Add</div>
    </div>
</script>
<script id="user_role" type="text/html">
    <li>
        <label>{{roleName}}</label>
        <div class="button">Unset</div>
    </li>
</script>

<!-- app list template -->
<script id="iconForGridLayout" type="text/html">
    <li class="app_icon" data-id="{{app_id}}">
        <div class="inner">
            <div class="icon"></div>
            <label>{{visible_label}}</label>
        </div>
    </li>
</script>
<script id="iconForListLayout" type="text/html">
    <tr class="app_list_view" data-id="{{app_id}}">
        <td class="first_column"></td>
        <td class="icon" style="background-image: url({{icon_large}})"></td>
        <td class="text">
            <p class="title">{{title}}</p>
            <p class="download_date"></p>
        </td>
        <td class="with-button">
            <div class="button blue">Info</div>
            <div class="button green">Data</div>
        </td>
        <td class="last_column"></td>

    </tr>
</script>

<script id="notification_request" type="text/html">
    <tr class="notification app_list_view" data-id="{{id}}">
        <td class="first_column"></td>
        <td class="icon" style="background-image: url({{icon_large}})"></td>
        <td class="text">
            <p class="title">{{title}}</p>
            <p class="">{{text}}</p>
        </td>
        <td class="notification_type">
            <span class="icon request"></span>
        </td>
        <td class="last_column"></td>
    </tr>
</script>
<script id="notification_info" type="text/html">
    <tr class="notification app_list_view" data-id="{{id}}">
        <td class="first_column"></td>
        <td class="icon" style="background-image: url({{icon_large}})"></td>
        <td class="text">
            <p class="title">{{title}}</p>
            <p class="">{{text}}</p>
        </td>
        <td class="notification_type">
            <span class="icon notification"></span>
        </td>
        <td class="last_column"></td>
    </tr>
</script>
<script id="notification_calendar" type="text/html">
    <tr class="notification app_list_view" data-id="{{id}}">
        <td class="first_column"></td>
        <td class="icon" style="background-image: url({{icon_large}})"></td>
        <td class="text">
            <p class="title">{{title}}</p>
            <p class="">{{text}}</p>
        </td>
        <td class="notification_type">
            <span class="icon calendar"></span>
        </td>
        <td class="last_column"></td>
    </tr>
</script>
<script id="notification_update" type="text/html">
    <tr class="notification app_list_view" data-id="{{id}}">
        <td class="first_column"></td>
        <td class="icon" style="background-image: url({{icon_large}})"></td>
        <td class="text">
            <p class="title">{{title}}</p>
            <p class="">{{text}}</p>
        </td>
        <td class="notification_type">
            <span class="icon update"></span>
        </td>
        <td class="last_column"></td>
    </tr>
</script>


<!-- top navigation in app -->
<!--<script id="topNavigation" type="text/html"></script>-->

<!-- dataset select widget -->
<script id="setSelect" type="text/html">
    <select class="setSelect"></select>
    <input type="button" class="fx_button button backToGenericApp" value="Back">
    <input type="button" class="fx_button button select_set" disabled="disabled" value="Start">
<!--    <input type="button" class="fx_button button download" disabled="disabled" value="Download">-->
</script>


<script id="queryListWidget" type="text/html">
    <table class="fx_querylist_data">
        <thead class="fx_querylist_head"></thead>
        <tbody class="fx_querylist_content"></tbody>
    </table>

    <div class="fx_querylist_navigation">
        <div class="previous_page navigate_button fx_button">Previous</div>
        <div class="input_with_pages">
            <span class="current_page_number">1</span>
            <span>/</span>
            <span class="total_page_number"></span>
        </div>
        <div class="next_page navigate_button fx_button">Next</div>
    </div>

    <div class="fx_querylist_search">
        <input class="search_input" type="text" value="">
        <input class="start_search fx_button" type="button" value="Search">
    </div>
</script>

<script id="querylist_navigation" type="text/html"></script>


<!-- datepicker  with 3 selects -->
<script id="dateselector" type="text/html">
    <div>
        <input type="hidden" class="date">
        <select class="input_datepicker day">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
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
            <option value="31">31</option>
        </select>
        <select class="input_datepicker month">
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
        <select class="input_datepicker year">
        </select>
    </div>
</script>

<script id="loader_spinner" type="text/html">
    <div id="loader">
        <div class="spinner">
            <div class="bounce1"></div>
            <div class="bounce2"></div>
            <div class="bounce3"></div>
        </div>
    </div>

</script>