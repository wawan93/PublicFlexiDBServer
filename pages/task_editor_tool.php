<?php

	if (!intval($_SESSION['current_schema'])) {
		fx_show_metabox(array('body' => array('content' => new FX_Error('empty_data_schema', _('Please select Data Schema'))), 'footer' => array('hidden' => true)));
		return;
	}

	$new_actions = get_fx_option('active_tasks', array());
	$taskList = get_objects_by_type(get_type_id_by_name(0,'task'), $_SESSION['current_schema']);


	$local_ftp_options = get_fx_option('local_ftp_options', array());

	$show_cron = $local_ftp_options['ftp_username'] && $local_ftp_options['ftp_password'] ? true : false;
	
	if (!$show_cron) {
		echo '
		<div class="msg-error">
			'._('Please set local FTP credentials to be able to use scheduled task').'.&nbsp;
			<a href="'.URL.'settings/settings_general">General Settings</a>
		</div>';
	}



?>

<div class="leftcolumn">
    <div class="metabox" id="controls">
        <div class="header">
            <div class="icons tasks"></div>
            <h1>Task</h1>
        </div>
        <div class="content">
            <label for="task-name">Task Name:</label><input type="text" id="task-name">

            <label for="task-select">Select task:</label>
            <select id="task-select">
                <option value='new'>New Task</option>
                <?php show_select_options($taskList, 'object_id', 'display_name', $_GET['object_id']); ?>
            </select>
            
            <label for="enabled-input">Enabled</label>
            <select name="enabled" id="enabled-input">
                <option value='1'>Yes</option>
                <option value='0'>No</option>
            </select>

            <label for="priority-input">Priority</label>
            <input type="number" name="priority" id="priority-input" min="0" step="1.0">

        </div>
        <div class="footer">
            <input type="button" id="save-button" class="button" disabled="disabled" value="Save changes">
            <input type="button" id="delete-button" class="button" disabled="disabled" value="Delete Task">
        </div>
    </div>
    <div class="metabox">
        <div class="header">
            <div class="icons tasks"></div>
            <h1>Actions</h1>
        </div>
        <ul class="content" id="newActions">

		<?php
			foreach($new_actions as $methods):
			foreach($methods as $endpoints):
			foreach($endpoints as $class => $data):

			$form_action = $form_reaction = '<h2>No parameters</h2>';
			
			$accept_list = '';
			$source_only = false;
			if (class_exists($data['class'])) {

				$action_reflection = new ReflectionClass($data['class']);
				$action = $action_reflection -> newInstanceArgs();	
				
				if (!method_exists($action, 'action')) {
					$accept_list = ' accept-list="source-only"';
					$source_only = true;
				}
			}
			else {
//                echo $data['class'].' not exist';
				$accept_list = ' accept-list="invalid"';
			}

		?>

        <li class="collapsible-block" data-sender="list" data="<?php echo $class ?>"<?php echo $accept_list; ?>>
            <div class="not-collapsible handle">
                <span class="title"><?php echo $data['name']; ?></span>
                <div class="collapse-button"></div>
            </div>
            <div class="collapsible" style="display: none">
                <?php echo $data['description'] != '' ? $data['description']: 'No description';?>
            </div>

            <div class="collapsible-block settings">
                <div class="not-collapsible">
                    <span class="title">Parameters</span>
                    <div class="collapse-button"></div>
                </div>
                <div class="collapsible" style="display: none">
                                
					<?php
						//Schedule is available for actions with "action" method only
                    	if ($source_only === false && $show_cron):
					?>  
                                      
                    <div class="switcher" data="switch">
                        <span data="condition" class="ui-button ui-state-default ui-corner-left">Condition</span>
                        <span data="schedule" class="ui-button ui-state-default ui-corner-right">Schedule</span>
                    </div>
                    <div class="schedule-settings" style="display: none">
                        <h2>Schedule Settings</h2>
                        <p>&nbsp;</p>
                        <p>Action will be performed according the schedule</p>
                        <p>&nbsp;</p>
                        <ul data="scheduling">
                            <li data="Minutes">
                                <input type="checkbox" name="checkMinutes">
                                <label data="amountMinutes"></label>
                                <div class="timeSlider" data="sliderMinutes"></div>
                            </li>

                            <li data="Hours">
                                <input type="checkbox" name="checkHours">
                                <label data="amountHours"></label>
                                <div class="timeSlider" data="sliderHours"></div>
                            </li>

                            <li data="Days">
                                <input type="checkbox" name="checkDays">
                                <label data="amountDays"></label>
                                <div class="timeSlider" data="sliderDays"></div>
                            </li>

                            <li data="Month">
                                <input type="checkbox" name="checkMonth">
                                <label data="amountMonth"></label>
                                <div class="timeSlider" data="sliderMonth"></div>
                            </li>

<!--                            <li data="DayOfWeek">-->
<!--                                <input type="checkbox" name="checkDayOfWeek">-->
<!--                                <label data="amountDayOfWeek"></label>-->
<!--                                <div class="timeSlider" data="sliderDayOfWeek"></div>-->
<!--                            </li>-->


                            <li class="dayoftheweek">
                                <label data="amountDayOfWeek"></label>
                                <table>
                                    <tr>
                                        <td>Mon</td>
                                        <td>Tue</td>
                                        <td>Wed</td>
                                        <td>Thu</td>
                                        <td>Fri</td>
                                        <td>Sat</td>
                                        <td>Sun</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td><input type="checkbox"></td>
                                        <td><input type="checkbox"></td>
                                        <td><input type="checkbox"></td>
                                        <td><input type="checkbox"></td>
                                        <td><input type="checkbox"></td>
                                        <td><input type="checkbox"></td>
                                    </tr>
                                </table>
                            </li>
                        </ul>
                    </div>
 
                    <?php endif; ?>

                    <div class="params">
                    	<?php if (is_object($action) && method_exists($action, 'form')) $action->form(); ?>
                    	<span class="custom-params"></span>
                    </div>
                   
                    <div class="results">
						<?php 
                            if (is_object($action) && method_exists($action, 'params')) {
                                foreach ((array)$action->params() as $code => $title){
                                    echo "\n\t<span class=\"param event button small green\" data=\"%$code%\">$title</span>";
                                }
                            }
                        ?>
                    	
                    </div>
                    
                    <div class="new-results"></div>
                    <span class="custom-results"></span>
                </div>
            </div>
        </li>

        <?php
        	endforeach;
			endforeach;
			endforeach; 
        ?>
        
        </ul>
        <p>&nbsp;</p>
    </div>
</div>

<div class="rightcolumn">
    <div class="metabox threecolumns">
        <div class="header">
            <div class="icons tasks"></div>
            <h1 id="task-header">New Task<sup style="display: none" id="after-task-header">*</sup></h1>
        </div>
        <div class="content">
            <form method="POST">
                <table class="threecolumns">
                    <tr>
                        <th>IF (Source Action)</th>
                        <th>THEN (Success Action)</th>
                        <th>ELSE (Failure Action)</th>
                    </tr>
                    <tr>
                        <td class="empty-list">
                            <span class="delete-action">RELEASE action</span>
                            <ul class="droparea" id="actionIf"></ul>
                            <span class="empty">DROP ACTION HERE</span>
                        </td>

                        <td class="empty-list">
                            <span class="delete-action">RELEASE action</span>
                            <ul class="droparea" id="actionThen"></ul>
                            <span class="empty">DROP ACTION HERE</span>
                        </td>

                        <td class="empty-list">
                            <span class="delete-action">RELEASE action</span>
                            <ul class="droparea" id="actionElse"></ul>
                            <span class="empty">DROP ACTION HERE</span>
                        </td>

                    </tr>
                </table>
            </form>
        </div>
        <div class="footer">
        </div>
    </div>
</div>