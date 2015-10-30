<?php
include_once "./generation_utils.php";

function get_application_data ($id, $local_using) {
    $app = get_application($id);
    $app['base_api_url'] = CONF_API_URL;
    $app['base_url'] = CONF_SITE_URL;
    $app['channel_id'] = get_schema_channel($app['schema_id']);
    $app['is_local_using'] = $local_using;
    $app['date_format'] = FX_DATE_FORMAT;
    $app['time_format'] = FX_TIME_FORMAT;

    if(!$app['channel_id'] && !$local_using) {
        echo 'Error: there is no channel associated with current schema.';
        exit();
    }

    if($local_using) {
        $server_settings = get_fx_option('server_settings', array());
        $app['channel_token'] = $server_settings['dfx_key'];
    }

    return $app;
};

if($_POST['is_local_using'] && $_POST['parent_app_id']) {
    $app = get_application_data($_POST['parent_app_id'], true);
    $app['code'] = $_POST['code'];
    echo generate_application(json_encode($app));
}
else if($_GET['id']) {
    $app = get_application_data($_GET['id'], $_GET['is_local_using']);
    $app['code'] = json_encode($app['code']);
    echo generate_application(json_encode($app));
}
else {
    echo generate_application();
}

?>