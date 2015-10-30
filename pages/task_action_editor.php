<?php
    $tasks = array();

	scan_task_dir(CONF_FX_DIR."/tasks", $tasks);

	$custom_dirs = do_actions('fx_custom_task_actions', array());

	foreach ($custom_dirs as $dir) {
		scan_task_dir($dir, $tasks);
	}

    if (isset($_POST['task_file'])) {
        $_SESSION['task_file'] = $_POST['task_file'];
        header('Location: '.replace_url_param());
    }

    $current_task = $_SESSION['task_file'] ? $_SESSION['task_file'] : reset($tasks);

    $file_path = $tasks[$current_task]['path'];

    if (isset($_POST['file_content'])) {
        $f = fopen($file_path, 'w');
        fwrite($f, $_POST['file_content']);
        fclose($f);
    }

?>
<style rel="stylesheet" type="text/css">
.script_editor__file_list { float: right; width: 200px; }
.script_editor__file_list li { margin: 6px 0; }
.script_editor__file_list a { color: #21759B; }
.script_editor__file_list li.active { background-color: #E4F2FD; padding: 5px 3px 5px 12px; -webkit-border-radius: 3px; border-radius: 3px; }
.script_editor__file_list li.active>a{ font-weight: bold; }
.script_editor__file_wrapper { position: relative; margin-right: 220px; }
.script_editor__file_content { font-family: Consolas,Monaco,monospace !important; font-size: 12px; background: #F9F9F9; outline: 0; width: 100%; height: 400px; max-width: 100%; }
</style>
<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
        	<div class="icons tasks"></div>
            <h1>Task Action Editor</h1>
        </div>
        <div class="script_editor__file_list">
            <form method="post" action="" id="task_file">
            <input type="hidden" name="task_file" id="task_file_value">
            <h3>Task Action files</h3>
            <?php
                echo '<ul>';
                foreach ($tasks as $task_name => $task) {
                    $selected = $current_task == $task_name ? ' class="active"': '';
                    echo '<li '.$selected.'><a href="#" class="select_task_file" data-task="'.$task_name.'">'.$task['name'].'</a></li>';
                }
                echo '</ul>';
            ?>
            </form>
        </div>
        <div class="script_editor__file_wrapper">
            <form method="post" action="" id="save_file_content">
                <textarea class="script_editor__file_content" name="file_content"><?php
                $f = file_get_contents($file_path);
                echo htmlentities($f);
                ?></textarea>
            </form>
        </div>
        <div style="clear: both"></div>
            <div class="content">
            <?php
            if (is_writable($file_path)) {
                echo '<input type="submit" value="save" class="button green" form="save_file_content">';
            } else {
                echo 'You need to make this file writable before you can save your changes.';
            }
            ?>
        </div>
    </div>

</div>
<script type="text/javascript">
    $(function() {
        $('#plugin').on('change', function() {
            $('#plugin_list_form').submit();
        })
        $('.select_task_file').on('click', function(e) {
            e.preventDefault();
            $('#task_file_value').val($(this).data('task'))
            $('#task_file').submit()
            return false;
        })
    })
</script>

