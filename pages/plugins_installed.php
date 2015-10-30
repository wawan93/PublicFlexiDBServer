<?php

if (isset($_POST['activate_plugin'])) {
    activate_plugin($_POST['plugin_name'], $_POST['plugin_path']);
    header('Location: ' . replace_url_param());
}

if (isset($_POST['deactivate_plugin'])) {
    deactivate_plugin($_POST['plugin_name']);
    header('Location: ' . replace_url_param());
}

if (isset($_POST['deactivate_all'])) {
	add_log_message('plugins_deactivate_all', _('All plugins were deactivated'));
    delete_fx_option('active_plugins');
    header('Location: ' . replace_url_param());
}

$active_plugins = get_fx_option('active_plugins');

$plugins = array();

scan_plugin_dir(CONF_FX_DIR."/plugins", $plugins);

?>

<div class="rightcolumn">
    <div class="metabox">
        <div class="header">
            <div class="icons settings"></div>
            <h1>Plugins</h1>
        </div>
        <div class="content">
                 
		<style type="text/css">
            .stack-item {
                background: #efefef;
                border: solid 1px #ccc;
                margin-top: 5px;
                position:relative;
            }
            
            .stack-item:after { 
               content: " ";
               display: block; 
               height: 0; 
               clear: both;
             
            }
            .stack-item .title {
                float:left; 
                border-right:1px solid #ccc; 
                text-align:center; 
                padding:10px;
                width:15%;
                min-width:150px;
                height:100%;
            }
            .stack-item .content {
                padding:10px;
                padding-bottom:20px;
                width:80%;
                float:left;
            }
            .stack-item .item-info {
                position:absolute;
                
                bottom:3px;
                right:3px;
            }
            
        </style>

        <?php
		
        	if ($plugins) {		
				if ($active_plugins) {
					echo '
					<form method="post">
						<input type="hidden" name="deactivate_all">
						<input type="submit" class="button small red" value="Deactivate All">
					</form>';
				}

				echo '<hr>';

				foreach ($plugins as $plugin) {
					
					if (array_key_exists($plugin['name'], $active_plugins)) {
						$ctrl = '
						<form method="post">
							<input type="hidden" name="deactivate_plugin">
							<input type="hidden" name="plugin_name" value="' . $plugin['name'] . '">
							<input type="submit" class="button small red" value="Deactivate">
						</form>';
					} 
					else {
						$ctrl = '
						<form method="post">
							<input type="hidden" name="activate_plugin">
							<input type="hidden" name="plugin_name" value="' . $plugin['name'] . '">
							<input type="hidden" name="plugin_path" value="' . $plugin['path'] . '">
							<input type="submit" class="button small green" value="Activate">
						</form>';
					}
					
					echo '
					<div class="stack-item">
						<div class="title">
							<p><strong>'.$plugin['name'].'</strong></p>
							<p>&nbsp;</p>
							' . $ctrl . '
						</div>
						<div class="content">
							<p>'.($plugin['description'] ? $plugin['description'] : '<font color="#CCCCCC">Description is not available</font>').'</p>
						</div>
						 <div class="item-info">
							'.($plugin['version'] ? 'Version '.$plugin['version'].'&nbsp;|&nbsp;' : '').'
							'.($plugin['author'] ? ' by <a href="'.$plugin['author_url'].'">'.$plugin['author'].'</a>&nbsp;|&nbsp;' : '').'
							'.($plugin['url'] ? '<a href="'.$plugin['url'].'">Visit plugin site</a>&nbsp;|&nbsp;' : '').'
							'.($plugin['license'] ? 'License: '.$plugin['license'] : '').'                
						</div>
					</div>';
				}
			}
			else {
				echo '<div class="info">'._('Plugins not found').'</div>';
			}
        ?>         
        </div>
    </div>
</div>