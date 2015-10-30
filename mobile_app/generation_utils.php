<?php
ob_start();
require_once dirname(__FILE__) . "/../fx_load.php";

$resources = array(
    "css" => array(
        "normalize",
        "main",
        "jquery-ui.custom",
        "jquery.timepicker",
        "multiple-select",
        'fullcalendar',
        'fullcalendar.print',
        "flexiapp"

    ),
    "js" => array(
        "jquery",
        "jquery-ui.custom.min",
        "jquery.timepicker.min",
        "jquery.multiple.select",
        "blowfish",
        "ICanHaz.min",
        "d3.min",
        "charts",
        "themeroller",
        "utils",
        "date",
        "flexiwebUI",
        "flexiwebAPI",
        "swipe",
        "iscroll",
        "google.maps",
        'fullcalendar',
        "genericApp"
    )
);

/*******************************************************************************
 * Replace id of components like queries and forms with it JSON code in
 * application JSON
 * @param string $app_json application JSON code
 * @return string application JSON code
 ******************************************************************************/
function insert_components_code($app_json)
{
    $query_type_id = get_type_id_by_name(0, "query");
    $data_form_type_id = get_type_id_by_name(0, "data_form");
    $app_array = json_decode($app_json, true);
    foreach($app_array["pages"] as &$page)
    {
        foreach($page["elements"] as &$element)
        {
            if($element["type"] == "QueryList")
            {
                $db_object = get_object($query_type_id,  $element["query"]);
                if(is_fx_error($db_object)){
                    continue;
                }
                $element["query"] = json_decode($db_object["code"], true);
            }
            else if($element["type"] == "DataForm")
            {
                $db_object = get_object($data_form_type_id,  $element["form"]);

                if(is_fx_error($db_object)){
                    continue;
                }

//                var_dump($db_object);
                $element["form"] = json_decode($db_object["code"], true);
                $element["typeId"] = $db_object["object_type"];
                $element['filter_by_set'] = $db_object['filter_by_set'];
                $element['link_with_user'] = $db_object['link_with_user'];
            }
        }
    }
    return json_encode($app_array);
}

function insert_ids($app_json)
{
    $app_array = json_decode($app_json, true);
    $counter = 0;
    foreach($app_array["pages"] as &$page)
    {
        foreach($page["elements"] as &$element)
        {
            $element["id"] = $element["type"].($counter++);
        }
    }
    return json_encode($app_array);
}

/*******************************************************************************
 * Generate Phonegap Build Zip file
 * @param int $schema_id schema_id for application
 * @param int $app_id application_id
 * @param null|string $filename_to_save if `null` will be rendered to browser
 *                                      response
 ******************************************************************************/
function generate_application($app = null) {
    ob_start();
    global $resources;

    ?>
        <!DOCTYPE HTML>
        <html>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <script src="cordova.js"></script>
<!--            <script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>-->
            <?php
                foreach( $resources ['js'] as $id => $name) {
                    echo '<script language="JavaScript" src="js/'.$name.'.js"></script>'."\n";
                }

                foreach( $resources ['css'] as $id => $name) {
                echo '<link href="css/'.$name.'.css" rel="stylesheet" type="text/css">'."\n";
            }
                $options =  get_fx_option('server_settings');
            ?>
            <script language="JavaScript">
                $(document).ready(function(){
                    $.support.cors = true;
                    FXAPI.SITE_URL = '<?php echo CONF_SITE_URL; ?>';
                    FXAPI.FLEXILOGIN_URL = '<?php echo $options['fx_api_base_url'];?>';
                    var data = <?php echo ($app ? json_encode($app) : 'undefined'); ?>;
                    window.commonApp = new genericApplication(data);
                });
            </script>
            <?php include 'templates.php'; ?>
        </head>
        <body>
            <div id="generic_body_background"></div>
            <div id="login" class='login_page'>
                <div style="margin-bottom: 1em; text-align: left;">
                    <div class="app_logo"></div>
                    <div class="header_controls">
                        <div class="notifications"></div>
                        <div class="network_mode_switcher"></div>
                    </div>
                </div>

                <input type="text" name='user' placeholder="Username" class="text_input">
                <input type='password' name='password' placeholder="Password" class="text_input">
                <label class='remember_label'>
                    Remember Me?
                    <input type="checkbox" name="remember" class='remember_checkbox'>
                </label>

                <br>
                <input type="button" id="login_button" class="button green" value="Log In">
                <a id="restore_password" href="#" onclick="window.open('https://flexilogin.com/reset_password', '_system');">Forgot password?</a>
            </div>
            <div id="generic_application" style="display: none">
                <div class="header">
                    <div class="app_logo"></div>

                    <div class="header_controls">
                        <div class="notifications"></div>
                        <div class="network_mode_switcher"></div>
                    </div>

                </div>
                <div class="content">
                    <div id="page_user_apps" class="generic_app_page">
                        <div class="swipe">
                            <div class="apps_list swipe-wrap" ></div>
                        </div>
                        <div class="navigation_block"><span class="navigation_item" data-page="0"></span>
                        </div>
                    </div>
                    <div id="page_search" class="generic_app_page">
                        <div class="search_block">
                            <input id="search_string">
                            <input type="button" id="start_search" value="Search">
                            <select id="portals" style="display: none">
                            </select>
                        </div>
                        <div class="swipe">
                            <div class="apps_list swipe-wrap"></div>
                        </div>
                        <div class="navigation_block"></div>
                    </div>
                    <div id="page_profile" class="generic_app_page"></div>
                    <div id="page_scanner" class="generic_app_page withoutFooter">
                        <div class="button back">Back</div>
                        <div id="scanData"></div>
                    </div>
                    <div id="page_user_apps_info" class="generic_app_page">
                        <div class="swipe">
                            <div class="apps_list swipe-wrap" ></div>
                        </div>
                        <div class="navigation_block"><span class="navigation_item" data-page="0"></span>
                        </div>
                    </div>

                    <div id="page_notifications" class="generic_app_page">
                        <div class="swipe">
                            <div class="apps_list swipe-wrap" ></div>
                        </div>
                        <div class="navigation_block"><span class="navigation_item" data-page="0"></span>
                        </div>
                    </div>

                    <div id="page_description" class="generic_app_page app_rating withoutFooter"></div>
                    <div id="page_datasets" class="generic_app_page withoutFooter"></div>
                    <div id="page_reviews" class="generic_app_page app_rating withoutFooter"></div>
                    <div id="page_subscription" class="generic_app_page subscribe withoutFooter"></div>
                    <div id="page_datasets_list" class="generic_app_page datasets withoutFooter"></div>
                </div>
                <div class="footer">
                    <div class="inner_footer">
                        <span id="home_button" class="app_menu_item"></span>
                        <span id="user_apps_info_button" class="app_menu_item"></span>
                        <span id="search_button" class="app_menu_item"></span>
                        <span id="profile_button" class="app_menu_item"></span>
                    </div>
                </div>
            </div>
            <div id="loader" style="display: none">
                <div class="spinner">
                    <div class="bounce1"></div>
                    <div class="bounce2"></div>
                    <div class="bounce3"></div>
                </div>
            </div>
        </body>
        </html>
    <?php

    $res = ob_get_contents();
    ob_end_clean();
    return $res;
}


/**
 * @param GenerationContext $generation_context
 * @return string
 */
function generate_config($version_id, $name){
    ob_start();
    ?>
    <widget xmlns="http://www.w3.org/ns/widgets"
            xmlns:gap = "http://phonegap.com/ns/1.0"
            xmlns:android  = "http://schemas.android.com/apk/res/android"
            id="<?php echo $version_id; ?>"
            version="0.1.16"
            ios-CFBundleVersion="20151023">

        <name><?php echo $name; ?></name>
        <description></description>

        <author href="http://flexidemo.co.uk">Flexiweb</author>

        <access origin="*" subdomains="true" />

        <preference name="phonegap-version" value="cli-5.2.0" />
        <preference name="permissions" value="none" />

        <!-- make the statusbar black -->
        <preference name="ios-darkstatusbar" value="true" />
        <preference name="DisallowOverscroll" value="true"/>
        <preference name="SplashScreen" value="screen" />

        <!-- Do not auto hide splash on iOS -->
        <preference name="AutoHideSplashScreen" value="true" />
        <!-- Do not auto hide splash on Android -->
        <preference name="SplashScreenDelay" value="10000"/>

        <!-- this code makes the statusbar text white -->
        <gap:config-file platform="ios" parent="UIViewControllerBasedStatusBarAppearance" overwrite="true">
            <false/>
        </gap:config-file>

        <gap:config-file platform="ios" parent="UIStatusBarStyle">
            <string>UIStatusBarStyleLightContent</string>
        </gap:config-file>

        <!-- Core plugins -->
        <gap:plugin name="org.apache.cordova.battery-status" source="npm"  />
        <gap:plugin name="org.apache.cordova.camera" source="npm" />
        <gap:plugin name="org.apache.cordova.media-capture" source="npm" />
        <gap:plugin name="org.apache.cordova.console" source="npm" />
        <gap:plugin name="org.apache.cordova.contacts" source="npm"  />
        <gap:plugin name="org.apache.cordova.device" source="npm" />
        <gap:plugin name="org.apache.cordova.device-motion" source="npm" />
        <gap:plugin name="org.apache.cordova.device-orientation" source="npm" />
        <gap:plugin name="org.apache.cordova.dialogs" source="npm" />
        <gap:plugin name="org.apache.cordova.file" source="npm"  />
        <gap:plugin name="org.apache.cordova.file-transfer" source="npm" />
        <gap:plugin name="org.apache.cordova.geolocation" source="npm"  />
        <gap:plugin name="org.apache.cordova.globalization" source="npm" />
        <gap:plugin name="org.apache.cordova.inappbrowser" source="npm" />
        <gap:plugin name="org.apache.cordova.media" source="npm"  />
        <gap:plugin name="org.apache.cordova.network-information" source="npm" />
        <gap:plugin name="org.apache.cordova.splashscreen" source="npm" />
        <gap:plugin name="org.apache.cordova.vibration" source="npm" />

        <gap:plugin name="cordova-plugin-whitelist" source="npm" />

        <gap:plugin name="com.phonegap.plugin.statusbar" />
        <gap:plugin name="com.phonegap.plugins.barcodescanner" version="1.1.0" />

        <gap:plugin name="com.unarin.cordova.beacon" version="3.1.2" />
<!--        <gap:plugin name="com.millerjames01.sqlite-plugin" version="1.0.1" />-->
<!--        <gap:plugin name="ch.zhaw.sqlite" version="2.0.0" />-->

        <gap:plugin name="com.brodysoft.sqliteplugin" version="1.0.4" />


        <gap:plugin name="pushwoosh-pgb-plugin" source="npm" />

        <icon src="cordova_128.png"/>
        <icon src="cordova_android_36.png" gap:platform="android" gap:density="ldpi"/>
        <icon src="cordova_android_48.png" gap:platform="android" gap:density="mdpi"/>
        <icon src="cordova_android_72.png" gap:platform="android" gap:density="hdpi"/>
        <icon src="cordova_android_96.png" gap:platform="android" gap:density="xhdpi"/>
        <icon src="cordova_bb_80.png" gap:platform="blackberry"/>
        <icon src="cordova_bb_80.png" gap:platform="blackberry" gap:state="hover"/>
        <icon src="cordova_ios_57.png" gap:platform="ios" width="57" height="57"/>
        <icon src="cordova_ios_72.png" gap:platform="ios" width="72" height="72"/>
        <icon src="cordova_ios_114.png" gap:platform="ios" width="114" height="114"/>
        <icon src="cordova_ios_144.png" gap:platform="ios" width="144" height="144"/>
        <icon src="cordova_64.png" gap:platform="webos"/>
        <icon src="cordova_48.png" gap:platform="winphone"/>
        <icon src="cordova_173.png" gap:platform="winphone" gap:role="background"/>

        <!--android-->
        <gap:splash src="res/screen/Android-320x480.png" gap:platform="android" gap:density="ldpi"/>
        <gap:splash src="res/screen/Android-800x480.png" gap:platform="android" gap:density="mdpi"/>
        <gap:splash src="res/screen/Android-1280x720.png" gap:platform="android" gap:density="hdpi"/>
        <gap:splash src="res/screen/Android-1920x1080.png" gap:platform="android" gap:density="xhdpi"/>

        <!--iphones-->
        <gap:splash src="res/screen/iOS-480x320.png" gap:platform="ios" width="320" height="480"/>
        <gap:splash src="res/screen/iOS-960x640.png" gap:platform="ios" width="640" height="960"/>
        <gap:splash src="res/screen/iOS-1136x640.png" gap:platform="ios" width="640" height="1136"/>
        <gap:splash src="res/screen/iOS-1334x750.png" gap:platform="ios" width="750" height="1334"/>
        <gap:splash src="res/screen/iOS-2208x1242.png" gap:platform="ios" width="1242" height="2208"/>

        <!--ipads-->
        <gap:splash src="res/screen/iOS-1024x768.png" gap:platform="ios" width="768" height="1024"/>
        <gap:splash src="res/screen/iOS-2048x1536.png" gap:platform="ios" width="1536" height="2048"/>

        <gap:splash src="res/screen/blackberry_transparent_300.png" gap:platform="blackberry"/>
        <gap:splash src="res/screen/windows_phone_portrait.png" gap:platform="winphone"/>

    </widget>

    <?php
    $res = ob_get_contents();
    ob_end_clean();
    return $res;
}
function generate_build_zip($full_application_id = null, $filename_to_save = null){
    if(!$filename_to_save) $filename = tempnam("zip_temp", "app");
    else $filename = $filename_to_save;

    Zip(CONF_FX_DIR.'/mobile_app', $filename);

    $zip = new ZipArchive;
    $res = $zip->open($filename);
    $app = null;

    if(!$full_application_id) {
//        $application_id = 'co.uk.flexiweb.genericApp';
        $application_id = 'com.flexilogin.testflight';
        $application_name = 'FlexiLogin';
        $zip_name = 'GenericApp';
    }
    else {
        $app = get_application($_GET['id']);
        $app['code'] = json_decode($app['code']);
        $app['style'] = json_decode($app['style']);
        $app['base_api_url'] = CONF_API_URL;
        $app['base_url'] = CONF_SITE_URL;
        $app['channel_id'] = get_schema_channel($app['schema_id']);

        if(!$app['channel_id']) {
            echo 'Error: there is no channel associated with current schema.';
            exit();
        }

        $application_id = 'co.uk.flexiweb.'.$app['name'];
        $application_name = $app['display_name'];
        $zip_name = $app['name'];
        $app_json = json_encode($app);
    }

    if ($res === TRUE) {
        echo 'ok';
        $zip->addFromString("index.html", generate_application($app_json));
        $zip->addFromString("config.xml", generate_config($application_id, $application_name));
        $zip->deleteName('create_app.php');
        $zip->deleteName('generate.php');
        $zip->deleteName('generation_utils.php');
        $zip->deleteName('get_build_zip.php');
        $zip->deleteName('create_single_app.php');
        $zip->close();
    }

    else {
        echo 'failed, code:' . $res;
    }



    if(!$filename_to_save)
    {
        ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.$zip_name.'.zip');
        header('Content-Length: ' . filesize($filename));
        readfile($filename);
        unlink($filename);
    }


}

function copy_files_into_app($type, $path) {
    global $resources;

    echo "\n";
    echo $path.$type;

    mkdir( $path  . $type, 0777);

    // type = css/js/images
    foreach($resources[$type] as $id => $filename)
    {
        $filename = $type . '/'. $filename;

        if($type != 'images')
            $filename = $filename . '.' . $type;

        if(!file_exists($filename)){
            echo($filename);
        }
        copy($filename, $path .$filename );
    }
}
function Zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}
?>