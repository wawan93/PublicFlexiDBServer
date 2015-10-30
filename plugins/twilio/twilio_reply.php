<?php

require_once dirname(dirname(dirname(__FILE__)))."/fx_load.php";
$twilio_settings = get_fx_option('twilio_settings', array());

$enabled = $twilio_settings['reply'][$_REQUEST['To']]['enable'];
$text = $twilio_settings['reply'][$_REQUEST['To']]['text'];

if ($enabled && $text && $_REQUEST['From'] != $_REQUEST['To']) {
    echo '<?xml version="1.0" encoding="UTF-8"?>
        <Response>
            <Message>'.$text.'</Message>
        </Response>';
    }
?>