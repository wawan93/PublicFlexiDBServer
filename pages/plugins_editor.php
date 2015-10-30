<?php
    global $files;

    $plugins = array();
    $files = array();
    scan_plugin_dir(CONF_FX_DIR."/plugins", $plugins);

    if (isset($_POST['plugin'])) {
        if ($_SESSION['plugin'] !== $_POST['plugin']) {
            $_SESSION['plugin_file'] = false;
        }
        $_SESSION['plugin'] = $_POST['plugin'];
        header('Location: '.replace_url_param());
    }

    if (isset($_POST['plugin_file'])) {
        $_SESSION['plugin_file'] = $_POST['plugin_file'];
        header('Location: '.replace_url_param());
    }

    $current_plugin = $_SESSION['plugin'] ? $_SESSION['plugin'] : reset($plugins);

    function fx_scan_dir($dir_path, $file_path) {
        global $files;
        $files_ = scandir($dir_path.'/'.$file_path);
        unset($files_[0]);
        unset($files_[1]);
        foreach ($files_ as $f => $file) {
            $files_[$f] = $file_path.'/'.$file;
            if (!is_file($dir_path.'/'.$file_path.'/'.$file)) {
                fx_scan_dir($dir_path, $file_path.'/'.$file);
                unset($files_[$f]);
            }
        }
        $files = array_merge($files, $files_);
    }

    fx_scan_dir(CONF_FX_DIR."/plugins/", $current_plugin);


    $current_file = $_SESSION['plugin_file'] ? $_SESSION['plugin_file'] : reset($files);

    $file_path = CONF_FX_DIR."/plugins/".$current_file;

    if (isset($_POST['file_content'])) {
        $f = fopen($file_path, 'w');
        fwrite($f, $_POST['file_content']);
        fclose($f);
    }

?>
<style rel="stylesheet" type="text/css">
.script_editor__file_list { float: right; width: 200px; }
.script_editor__file_list li { margin: 6px 0; word-break: break-word; word-wrap: break-word;}
.script_editor__file_list a { color: #21759B; }
.script_editor__file_list li.active { background-color: #E4F2FD; padding: 5px 3px 5px 12px; -webkit-border-radius: 3px; border-radius: 3px; }
.script_editor__file_list li.active>a{ font-weight: bold; }
.script_editor__file_wrapper { position: relative; margin-right: 220px; }
.script_editor__file_content { font-family: Consolas,Monaco,monospace !important; font-size: 12px; background: #F9F9F9; outline: 0; width: 100%; height: 400px; max-width: 100%; }
</style>

<link rel="stylesheet" type="text/css" href="<?php echo CONF_SITE_URL?>style/codemirror.css">
<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/codemirror.js"></script>

<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/codemirror.clike.js"></script>
<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/codemirror.xml.js"></script>
<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/codemirror.javascript.js"></script>
<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/codemirror.css.js"></script>
<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/codemirror.htmlmixed.js"></script>
<script type="text/javascript" src="<?php echo CONF_SITE_URL?>js/codemirror.php.js"></script>

<script>
    $(function() {
        var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
            lineNumbers: true,
            matchBrackets: true,
            mode: "application/x-httpd-php",
            indentUnit: 4,
            indentWithTabs: true
        });
    })
</script>

<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
        	<div class="icons settings"></div>
            <h1>Plugin Editor</h1>
        </div>
        <div class="content">
            <div class="fileedit-sub">
                <form action="" method="post" id="plugin_list_form">
                    <strong><label for="plugin">Select plugin to edit: </label></strong>
                    <select name="plugin" id="plugin" onchange="submit()">
                        <?php
                        foreach ($plugins as $plugin) {
                            if (!in_array($plugin, array('.', '..'))) {
                                $plugin_path = explode('/', $plugin['path']);
                                $plugin_path = $plugin_path[count($plugin_path)-2];
                                $selected = $plugin_path == $_SESSION['plugin'] ? 'selected' : '';
                                echo '<option value="'.$plugin_path.'" '.$selected.'>'.$plugin['name'].'</option>';
                            }
                        } ?>
                    </select>
                    <input type="submit" class="button small blue" value="Select">
                </form>
            <br class="clear">
            </div>
        </div>
        <div class="script_editor__file_list">
            <form method="post" action="" id="plugin_file">
            <input type="hidden" name="plugin_file" id="plugin_file_value">
            <h3>Plugin files</h3>
            <?php
                echo '<ul>';
                foreach ($files as $file) {
                    $selected = $current_file == $file ? ' class="active"': '';
                    echo '<li '.$selected.'><a href="#" class="select_plugin_file">'.$file.'</a></li>';
                }
                echo '</ul>';
            ?>
            </form>
        </div>
        <div class="script_editor__file_wrapper">
            <form method="post" action="" id="save_file_content">
                <textarea class="script_editor__file_content" name="file_content" id="code"><?php
                $f = file_get_contents($file_path);
                echo htmlentities($f);
                ?></textarea>
            </form>
            <div class="content">
            <?php
            if (is_writable($file_path)) {
                echo '<input type="submit" value="save" class="button green" form="save_file_content">';
            } else {
                echo '<b>You need to make this file writable before you can save your changes.</b>';
            }
            ?>
            </div>
        </div>
        <div style="clear: both"></div>

    </div>

</div>
<script type="text/javascript">
    $(function() {
        $('#plugin').on('change', function() {
//            $('#plugin_list_form').submit();
        })
        $('.select_plugin_file').on('click', function(e) {
            e.preventDefault();
            $('#plugin_file_value').val($(this).html())
            $('#plugin_file').submit()
            return false;
        })
    })
</script>

