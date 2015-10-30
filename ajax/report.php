<?php
session_start();
ob_start();
require_once dirname(dirname(__FILE__))."/fx_load.php";
require_once CONF_REP_WIDGETS_DIR . '/rep_abstract.php';
validate_script_user();
ob_end_clean();





if(isset($_REQUEST['id']) && !empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
    $report = (int)$_REQUEST['id'];
}
elseif(isset($_REQUEST['widgets']) && !empty($_REQUEST['widgets'])) {
    $name = $_REQUEST['name'];
    $format = $_REQUEST['format'];
    $orientation = $_REQUEST['orientation'];
    $report_widgets = json_decode(rawurldecode($_REQUEST['widgets']), true);
    $report_hf_widgets = json_decode(rawurldecode($_REQUEST['headerFooter']), true);
    $report_options = json_decode(rawurldecode($_REQUEST['reportOptions']), true);
    
    $report = array('widgets'=>$report_widgets, 'headerFooter'=>$report_hf_widgets, 'report_options'=>$report_options);
    
} else {
    die('error!');
}


$obj = new FX_Report($report, $name);
$obj->fx_get_report_pdf($format, $orientation);