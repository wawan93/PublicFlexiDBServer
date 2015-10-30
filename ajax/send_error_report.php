<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	$flexidb_version = get_fx_option('flexidb_version');
	$db_version = get_fx_option('db_version');

	if (isset($_POST['send_report'])) {
		$issue_options = array('title' => $_POST['title'],
							   'report' => $_POST['report'],
							   'issue_type' => $_POST['issue_type'],
							   'dfx_version' => $flexidb_version,
							   'db_version' => $db_version);

		$fx_api = new FX_API_Client();
		$result = $fx_api -> execRequest('error', 'post', $issue_options);

		if (!is_fx_error($result)) {
			unset($_POST);
			$msg = '<div class="msg-info">Error report successfully sent</div>';
		}
		else {
			$msg = '<div class="msg-error">'.$result->get_error_message().'</div>';
		}
        
	}
	

?>
<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">
<!--    <script type="text/javascript" src="--><?php //echo URL?><!--js/jquery.min.js"></script>-->

    <style type="text/css">
		.report-input
		{
			width:100%;
		}
		label {
			font-weight:bold;
		}
		.msg-empty {
			height:33px;
		}
	</style>
</head>
<body class="popup">
	<?php echo $msg ? $msg : '<p>You can add an issue on <a href="https://github.com/FlexiDB/PublicFlexiDBServer/issues/new" target="_blank">GitHub</a></p>'; ?>
    <i>Please fill the form bellow to send error report or suggest new feature</i><br>
    <font size="-2"><i>Please do not abuse the opportunity to send an error message. Thank you for understanding :)</i></font>
	<hr>
	<form method="post">
    	<input type="hidden" name="send_report"/>
        <label>DFX Version:</label>&nbsp;<?php echo $flexidb_version ?></br>
        <label>DB Version:</label>&nbsp;<?php echo $db_version ?>
        <hr>
        <label for="issue_type">Issue Type:</label><br/>
        <select name="issue_type" style="width: 200px">
            <option value="bug" <?php if($_POST['issue_type']=='bug') echo 'selected="selected"'; ?>>Bug</option>
            <option value="work_flow_issue" <?php if($_POST['issue_type']=='work_flow_issue') echo 'selected="selected"'; ?>>Work Flow Issue</option>
            <option value="new_feature" <?php if($_POST['issue_type']=='new_feature') echo 'selected="selected"'; ?>>New Feature</option>
        </select></br>
        <label for="title">Title:</label><br/>
        <input class="report-input" type="text" id="title" name="title" value="<?php echo $_POST['title'] ?>"/></br>
        <label for="report">Report:</label><br>
        <textarea class="report-input" name="report" id="report" rows="7"><?php echo $_POST['report'] ?></textarea>
        <hr>
        <input type="submit" class="button green" value="Send"/>
        <input type="button" class="button red" id="close-dialog-window" value="Close"/>
    </form>
	<script language="javascript">
	<?php 
		if(is_fx_error($send_result)) echo 'alert("'.addslashes($send_result->get_error_message()).'");';
		elseif($send_result) echo 'alert("'.$send_result.'");';
	?>
	</script>
</body>
</html>
