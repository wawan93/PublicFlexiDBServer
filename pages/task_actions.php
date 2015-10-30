<?php

	$tasks = array();

	scan_task_dir(CONF_FX_DIR."/tasks", $tasks);

	$custom_dirs = do_actions('fx_custom_task_actions', array());
	
	foreach ($custom_dirs as $dir) {
		scan_task_dir($dir, $tasks);
	}

	$active_tasks = get_fx_option('active_tasks');

	if (isset($_POST['enable_task'])) {
		enable_task($tasks[$_POST['enable_task']]);
		header('Location: '.replace_url_param());
	}
	
	if (isset($_POST['disable_task'])) {
		disable_task($tasks[$_POST['disable_task']]);
		header('Location: '.replace_url_param());
	}
	
	if (isset($_POST['disable_all']))  {
		delete_fx_option('active_tasks');
		header('Location: '.replace_url_param());
	}
?>

<div class="rightcolumn">
    <div class="metabox">                
        <div class="header">
        	<div class="icons tasks"></div>
            <h1>Task Actions</h1>
        </div>
        <div class="content">
        <?php
			if ($active_tasks) {
				echo '
				<form method="post">
					<input type="hidden" name="disable_all">
					<input type="submit" class="button small red" value="Disable All">
				</form>';				
			}
		?>
        <hr>
        <table width="100%">
        <tr>
            <td width="1px"></td>
            <td width="1px"><b>Name</b></td>
            <td width="1px"><b>Description</b></td>
            <td width="1px"><b>API</b></td>
            <td><b>Class</b></td>
        </tr>
        <tr><td colspan="5"><hr></td></tr>
		<?php

		foreach($tasks as $task)
		{
			$is_active = false;
			
			$method = $task['method'] ? $task['method'] : 'EMPTY';
			$endpoint = $task['endpoint'] ? $task['endpoint'] : 'EMPTY';
			$class = $task['class'];

			if (isset($active_tasks[$method][$endpoint][$class])) $is_active = true;

			if ($is_active) {
				$ctrl = '
				<form method="post">
					<input type="hidden" name="disable_task" value="'.$task['class'].'">
					<input type="submit" class="button small red" value="Disable">
				</form>';
			}
			else {
				$ctrl = '
				<form method="post">
					<input type="hidden" name="enable_task" value="'.$task['class'].'">
					<input type="submit" class="button small green" value="Enable">
				</form>';	
			}
			
			echo '
			<tr>
				<td>'.$ctrl.'</td>
				<td><b><nobr>&nbsp'.$task['name'].'&nbsp;</nobr></b></td>
				<td><nobr>&nbsp;'.($task['description'] ? $task['description'] : '<font color="#CCCCCC">Description is not available</font>').'&nbsp;</nobr></td>
				<td>&nbsp;<nobr><i>'.strtoupper($task['method']).' '.$task['endpoint'].'</i></nobr>&nbsp;</td>
				<td>&nbsp;&nbsp'.$task['class'].'</td>
			</tr>
			<tr><td colspan="5"><hr></td></tr>';			
		}

		?>
        </table>
        </div>
    </div>
</div>