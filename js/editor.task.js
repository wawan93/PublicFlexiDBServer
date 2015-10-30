
function taskCustomFields(type_elem) {
	var type = type_elem.val();
	var obj_wrap = type_elem.closest('ul');	

	$.ajax({
		url: fx_dir + 'ajax/tasks_fields.php',
		type: "POST",
		data: 'type=' + type,
		async : false,
		success: function(html){
			$('.object_fields').closest('li').detach();
			$('li.custom_field',obj_wrap).detach();
			obj_wrap.append(html);
			updateResults();
		}
	});
}

function taskConditionCtrl()
{	
	$.each($('#actionIf').find('.params .task-param'), function(key, value) {
		if ($(value).prev().attr('class') != 'condition-select') {
			var ctrl = $('<select/>', { name: $(value).attr('name') + '-condition', class: 'condition-select'});
			var keys = ['ignore','==','!=','>','>=','<','<='];
			$.each(keys,function() { $('<option/>', { val: this, text: this	}).appendTo(ctrl); });
			ctrl.val('ignore');
			$(value).before(ctrl);
		}
	});
}

function updateResults()
{
	var $successEvents = $('#actionThen').find('.new-results');

	$('.custom_event').detach();

	$('#actionIf').find('li.custom_field').find('input').each(function( index ) {
		$successEvents.append($('<span class="param event button small green custom_event" data="$$'+$(this).attr('name')+'$$">'+$(this).attr('name')+'</span>').attr('data','$$'+$(this).attr('name')+'$$'))
	});

	$('.custom_event').draggable({
        helper: 'clone',
        containment: $(this).parent().parent()
    });
}

$(document).ready(function(){

    var $source = $('#actionIf'),
        $then = $('#actionThen'),
        $error = $('#actionElse'),
        $palette = $('#newActions'),

        $saveButton = $('#save-button'),
        $deleteButton = $('#delete-button'),

        $deleteActionButton = $('.delete-action'),
        $taskSelect = $('#task-select'),
        $taskName = $('#task-name'),
        $taskHeader = $('#task-header'),
        $taskHeaderStar = '<sup id="after-task-header" style="display: none">*</sup>',
        $prior = $('#priority-input'),
		$enabled = $('#priority-input'),
		
        $schemaId = window.session['current_schema'],
        $setId = 0;

    initTaskEditor();

    var $action, scheduleSwitcher = 0, $newTask, $oldTask;

    $("#newActions > li").draggable({
        helper: function(){
            return $(this).clone().width($(this).width());
        },
        opacity: '.5',
        handle: ".handle",
        connectToSortable: "#actionIf, #actionThen, #actionElse",
        placeholder: "collapsible-placeholder"
    });

    $("#actionIf, #actionThen, #actionElse").sortable({
        handle: ".handle",
        placeholder: "collapsible-placeholder",
        connectWith: "#actionIf, #actionThen, #actionElse",
        start: function(event, ui) {
            ui.item.find('.settings').hide("blind");
        },
        stop: function(event,ui) {
            ui.item.find('.settings').show("blind");
        },
        update: function(event, ui) {
            putInSortableList($(this));
            setEvents();
            editingStateOn();
            $('#actionThen, #actionElse').find('.param-type').text('Input Parameters');
        }
    });



    $(".collapse-button").click(collapseBlock);

    $deleteActionButton.unbind('click');
    $deleteActionButton.click(function() {
        if(confirm('Are you sure you want to delete this instance?')) {
            $(this).next('ul').empty();
            setAvailableContainers();
            setEvents();
            editingStateOn();
        }
    });

    $taskSelect.unbind('change').bind('change', function() {
        if($saveButton.hasClass('green')) {
            if(confirm('The current task is not saved. Are you sure you want to continue?')) {
                $taskSelect.find("option:selected").each(function () {
                    initTaskEditor();
                });
            }
        } else {
            initTaskEditor();
        }

        ($taskSelect.val() != 'new')?
           $deleteButton.addClass('red').removeAttr('disabled'):
           $deleteButton.removeClass('red').attr('disabled','disabled');

        window.history.pushState('', "Title", location.protocol + '//' + location.host + location.pathname  + '?object_id=' + $taskSelect.val());
        editingStateOff();
    });

    $saveButton.click(function() {
        if($taskName.val() == '') {
            alert('Please, enter Task name.');
        }
        else {
            var source = $source.find(' > li').length != 0 ? $source.find(' > li').attr('data') : '',
            action = $then.find(' > li').length != 0? $then.find(' > li').attr('data') : '',
            error = $error.find(' > li').length != 0? $error.find(' > li').attr('data') : '';

            var $id = $taskSelect.val(),
            $queryType = ($taskSelect.val() == 'new')? 'add': 'update';

            if($taskSelect.val() != 'new') $taskSelect.find('option:selected').text($taskName.val());
            $taskHeader.text($taskSelect.find('option:selected').text()).append($taskHeaderStar);

            $newTask = {
                object_id: ($queryType == 'add') ? '': $id,
                display_name: $taskName.val(),
                function: $queryType,

                schema_id: $schemaId,
                set_id: 0,

                source: source,
                source_args: getActionParameters('#actionIf'),

                action: action,
                action_args: getActionParameters('#actionThen'),

                error: error,
                error_args: getActionParameters('#actionElse'),

                priority: $('#priority-input').val(),
				enabled: $('#enabled-input').val(),

                schedule: getCronString()
            };

            $.post(window.flexiweb.site_url + 'ajax/tasks.php', $newTask, function(data){

				var $newData = JSON.parse(data);
				
                if($newData['error']) alert($newData['error']);
                else {
                    if($queryType == 'add') {
                        $taskSelect.find('option').removeAttr('selected');

                        $taskSelect
                            .find('option:first-child')
                            .after($('<option></option>')
                                .attr('value',$newData)
                                .attr('selected','selected')
                                .text($taskName.val()));

                        $taskHeader.text($taskName.val()).append('<sup id="after-task-header">*</sup>');
//                        editingStateOff();

                        $deleteButton.addClass('red').removeAttr('disabled');

                    }
                    editingStateOff();
                }
            });
//            console.log($newTask);

        }
    });

    $deleteButton.click(function(){
        if(confirm('Are you sure you want to delete this task? This action cannot be undone!')) {
            var $id = $taskSelect.val();
            $.post(window.flexiweb.site_url + 'ajax/tasks.php', { object_id: $id, function: 'delete'});
            $taskSelect.find('option[value="'+ $id +'"]').remove();
            $taskName.val('');
            $taskHeader.html('New Task');
            $('.droparea').empty();
            setAvailableContainers();
//            $deleteButton.attr('disabled','disabled');
        }
    });

    function editingStateOff(){
        if($saveButton.hasClass('green')) {
            $saveButton.attr('disabled','disabled').removeClass('green');
            $('#after-task-header').css('display','none');
//            $taskHeaderStar;

            var $listeners = $('.droparea input, .droparea textarea');

            $listeners.unbind('keyup');

            $listeners.keyup(function() {
                editingStateOn();
                $listeners.unbind('keyup');
            });

            var $listeners_2 =  $('.droparea input, .droparea select');

            $listeners_2.change(function() {
                editingStateOn();
                $listeners.unbind('keyup');
            });


        }
    }

    function editingStateOn() {
        if(!$saveButton.hasClass('green')) {
            $('#after-task-header').css('display','inline-block');
            $saveButton.removeAttr('disabled').addClass('green');
            $('.droparea input, .droparea select, .droparea textarea').unbind('keyup');
        }
    }

    function initTaskEditor() {
        $.blockUI();

        $('.droparea').empty();

        if($taskSelect.val() != 'new') {
            $oldTask = $.getJSON(
            window.flexiweb.site_url + '/ajax/tasks.php',
            {
                object_id: $taskSelect.val(),
                function: 'get'
            },
            function($tmp) {

//                console.log($tmp);
                $source.append($palette.find('li[data="'+$tmp['source']+'"]').clone().removeAttr('data-sender'));
                $then.append($palette.find('li[data="'+$tmp['action']+'"]').clone().removeAttr('data-sender'));
                $error.append($palette.find('li[data="'+$tmp['error']+'"]').clone().removeAttr('data-sender'));

                putInSortableList($('#actionIf, #actionThen, #actionElse'));

                if($tmp['schedule'] == '') {
                    $source.find('span[data="condition"]').trigger('click');
                } else {
                    $source.find('span[data="schedule"]').trigger('click');
                    cronToInputs($tmp['schedule']);
                }

                setActionParameters($source, $tmp['source_args']);
                setActionParameters($then, $tmp['action_args']);
                setActionParameters($error, $tmp['error_args']);

                $source.find('.type-selector').trigger('change', {data: $tmp['source_args'], src: $source});

                $taskName.val($tmp['display_name']);
                $('#priority-input').val($tmp['priority']);
				$('#enabled-input option[value="'+$tmp['enabled']+'"]').attr('selected','selected');

                setEvents();
                editingStateOff();
            });
        }
        else {
            $prior.val('');
			$enabled.val('');
            $taskName.val('');
            $('.droparea').empty();
            setAvailableContainers();
        }

        $taskHeader.text($taskSelect.find('option:selected').text()).append($taskHeaderStar);

        $('#task-name, #priority-input').unbind('keyup');
        $('#task-name, #priority-input').keyup(function() {
            editingStateOn();
        });

        $('#enabled-input').unbind('change');
        $('#enabled-input').change(function() {
            editingStateOn();
        });

        editingStateOff();
        $.unblockUI();
    }

    function setAvailableContainers() {
        var args = ['#actionIf','#actionThen','#actionElse'], argsNew = [], argsAdd = [];

        $.each(args, function(key, value) {
            if($(value).is(':empty')) {
                argsNew[argsNew.length] = value;
            } else {
                argsAdd[argsAdd.length] = value;
            }
        })

        var strEmptyActionsId = argsNew.join(', ');
        $("#actionIf, #actionThen, #actionElse").sortable({connectWith: strEmptyActionsId});
        $("#newActions > li").draggable({connectToSortable: strEmptyActionsId});

        $('.empty-list').removeClass('empty-list')
        $(strEmptyActionsId).parent('td').addClass('empty-list');

        return strEmptyActionsId;
    }

    function putInSortableList(actionType) {
        $action = $(actionType).find('> li');
        var $actionType = $(actionType);
        var $settings = $action.find('.settings');

        $action.find('.collapse-button').unbind('click');
        $action.find('.collapse-button').click(collapseBlock);

        var $switch = $action.find('.switcher'),
        $scheduleButton = $switch.find('[data="schedule"]'),
        $conditionButton = $switch.find('[data="condition"]');
		
		var curAction = $actionType.attr('id');
		var actionForm = $actionType.find('.actionForm');
		var reactionForm = $actionType.find('.reactionForm');
		
		if ((curAction == 'actionElse' || curAction == 'actionThen') && $action.attr('accept-list') == 'source-only') {
            alert('This action cannot be used as THEN and ELSE action.');
            $action.remove();
        }
		
		if ($action.attr('accept-list') == 'empty') {
            alert('This action has no any methods (empty action).');
            $action.remove();
        }
		
		if ($action.attr('accept-list') == 'invalid') {
            alert('Invalid action class.');
            $action.remove();
        }
		
        if(curAction == 'actionIf') {
			
            $scheduleButton.click(function() {
                if(!$settings.hasClass('schedule')) {
                    $settings.addClass('schedule').removeClass('condition');
                    $settings.find('.param-type').text('Input Parameters');
                    scheduleSwitcher = 1;

                    if(!$action.hasClass('with-slider')) {
                        $.each($settings.find('ul[data="scheduling"] li:not(.dayoftheweek)'), function(key, value) {
                            sliderConstructor($(this));
                        });
                        $action.addClass('with-slider');
                    }
                    $settings.find('.condition-select').hide();

                    $actionType.find('.dayoftheweek').unbind('click');
                    $actionType.find('.dayoftheweek').click(function() {
                        editingStateOn();
                    });

                    $(this).addClass('ui-state-active');
                    $conditionButton.removeClass('ui-state-active');
                    editingStateOn();
                }
            });

            $conditionButton.click(function() {
                if(!$settings.hasClass('condition')) {
                    $settings.addClass('condition').removeClass('schedule');//.find('.schedule-settings');//.hide('blind');
                    $settings.find('.param-type').text('Condition Parameters');
                    $settings.find('.condition-select').show();
					
					taskConditionCtrl(); // Add condition controls before fields in source action
					
                    scheduleSwitcher = 0;

                    $(this).addClass('ui-state-active');
                    $scheduleButton.removeClass('ui-state-active');
                    editingStateOn();
                }
            });
			
            $conditionButton.trigger('click');
			
			actionForm.show();
			reactionForm.hide();
			
			//taskCustomFields(class, form);
//            editingStateOff();

        }

        if(curAction == 'actionThen') {
            $('#actionIf').find('.type-selector').change()
        }

        setAvailableContainers();
        editingStateOff();

    }

    //toCron & fromCron
    function cronToInputs(cronStr)
	{
        var $sliderType = ['Minutes', 'Hours', 'Days', 'Month'];
        var $args = cronStr.split(' ');
        $.each($args, function(key, value) {
            if(key == $args.length - 1) {
                setCronDaysInputs(value);
            }
            else if(value != '*') {
                var $scope = value.split('-');
                var $scheduling = $source.find('li[data="'+ $sliderType[key] +'"]');
                $scheduling.find('input').trigger('click');
                sliderSwitcher($scheduling);

                $scheduling.find('.timeSlider').slider("values", 0, $scope[0]);
                $scheduling.find('.timeSlider').slider("values", 1, $scope[1]);

                $scheduling.find('label').text(setSliderValues($sliderType[key], $scope[0], $scope[1]));
            }
        });
    }

    function setCronDaysInputs(str)
	{
        var days = str.split(','), list = [], $inputs = $('#actionIf li.dayoftheweek input');

        $.each(days, function(key, value) {
            if(value.length > 1) {
                var list = value.split('-');
                for(var i= list[0]; i< list[1]; i++) {
                    $($inputs[i]).trigger('click');
                }
            } else {
                $($inputs[value]).trigger('click');
            }
        })

    }

    function getCronStringDays(array)
	{
        var cronStr = [];
        var start = undefined, end = undefined;
        var len = array.length;
        $.each(array, function(key, value) {
            if(start == undefined) start = value;
            if(end == undefined) end = value;
            if(value - end > 1)  {
                if(end - start >= 2) {
                    cronStr.push(start + '-' + end);
                }
                else if (end != start){
                    cronStr.push(start + ',' + end);
                }
                else {
                    cronStr.push(end);
                }

                start = value;
                end = value;

            } else {
                end = value;
            }
        });
        if(end - start >= 2) {
            cronStr.push(start + '-' + end);
        }
        else if (end != start){
            cronStr.push(start + ',' + end);
        }
        else {
            cronStr.push(end);
        }
//        if(cronStr.length == 0) return "*";
//        else
        return cronStr.join(',');
    }
	
    function getCronString()
	{
        var cronStr = '', i = 0, days = [], flag = false;

        if($source.find('.settings').hasClass('schedule')) {
            $.each($('#actionIf').find('ul[data="scheduling"]').find('li'), function(key, value) {
                if($(this).hasClass('dayoftheweek')) {
                    $.each($(this).find('input'), function(key, value) {
                        if($(this).is(':checked')) days.push(key);
                    });

                }
                else {
                    if($(value).find('input[type="checkbox"]').is(':checked')) {
                        cronStr += $(value).find('.timeSlider').slider('values', 0) + '-' +
                            $(value).find('.timeSlider').slider('values', 1) + ' ';
                    } else {
                        cronStr += '* ';
                    }
                }
            });
            var cron_days = (getCronStringDays(days) == '')? '*': getCronStringDays(days);
//            console.log('sched' + cronStr + ' ' + cron_days)
            return(cronStr + cron_days);

        }

//        console.log('cond');
        return '';
    }

    //ALL about sliders
    function sliderConstructor($sliderBlock)
	{
        var $newSlider = $sliderBlock.find('.timeSlider');
        var $output = $sliderBlock.find('label');
        var $checkbox = $sliderBlock.find('input');

        var dis = " (disabled)";
        var type = $newSlider.attr('data').replace('slider','');

        var min = 0, max, val;

        switch(type) {
            case 'Hours':       max = 23; break;
            case 'Minutes':     max = 59; break;
            case 'DayOfWeek':   max = 6; break;
            case 'Days':        min = 1; max = 31; break;
            case 'Month':       min = 1; max = 12; break;
        }

        val = [min,max];

        $newSlider.slider({
            range: true,
            min: min,
            max: max,
            values: val,
            slide: function( event, ui ) {
                $output.text(setSliderValues(type, ui.values[0], ui.values[1]));
                editingStateOn();
            }
        });
        $newSlider.slider('disable');
        $output.text(setSliderValues(type, min, max));
        $checkbox.unbind('click');
        $checkbox.click(function() {
            sliderSwitcher($sliderBlock);
            editingStateOn();
        });
        editingStateOff();

    }

    function setSliderValues(type, min, max)
	{
        var output = '',
        day = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],
        month = ["", "Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

        if(type == 'DayOfWeek') {
            (min == max) ?
            output = type + ': ' + day[min]:
            output = type + ': ' + day[min]+" - " + day[max];
        } else if(type == 'Month') {
            (min == max) ?
            output = type + ': ' + month[min] :
            output = type + ': ' + month[min]+" - " + month[max];
        } else {
            (min == max) ?
            output = type + ': ' + min :
            output = type + ': ' + min+" - " + max;
        }
        return output;
    }

    function sliderSwitcher($sliderBlock)
	{
        var $newSlider = $sliderBlock.find('.timeSlider');
        var $output = $sliderBlock.find('label');
        var $checkbox = $sliderBlock.find('input');

        $output.text($output.text().replace(' (disabled)',''));

        if(!$checkbox.prop('checked')) {
            $newSlider.slider('disable');
            $output.text($output.text() + ' (disabled)');
        } else {
            $newSlider.slider('enable');
            $output.text($output.text().replace(' (disabled)',''));
        }
    }

    function getActionParameters(actionType)
	{
        var $args = {}, $thisAction = $(actionType);

        $.each($thisAction.find('.params .task-param'), function(key, value) {

			var paramName  = $(value).attr('name');
			
			if(actionType == '#actionIf') {
                $args[paramName] = {
					  value: $(value).val(),
				   	  condition: $(value).prev().val()
                }
            } else {
				$args[paramName] = $(value).val();
            }
        });

        return (Object.size($args) != 0) ? JSON.stringify($args) : '';
    }

    function setActionParameters(actionTypeObj, args)
	{
        if(args != '') {
			
			var objectTypeElem = false;
			//var parsed_args = JSON.parse(args);
		    var parsed_args = JSON && JSON.parse(args) || $.parseJSON(args);
		   
		    $.each(parsed_args, function(key, value) {

				if(actionTypeObj.attr('id') == 'actionIf') {
					actionTypeObj.find('[name="'+key+'"]').prev().find('option[value="'+value['condition']+'"]').attr('selected','selected');
					value = value['value'];
					
/*					if(key == 'object_type_id') {
						objectTypeElem = $thisArg.find('select[name="object_type_id"]');
					}*/
				}
				
				actionTypeObj.find('input[name="'+key+'"]').val(value);
				actionTypeObj.find('textarea[name="'+key+'"]').html(value);
				actionTypeObj.find('select[name="'+key+'"] option[value="'+value+'"]').attr('selected','selected');
            });

/*			if(objectTypeElem) {

				taskCustomFields(objectTypeElem);
				
				$.each(parsed_args, function(key, value) {
					var $thisArg = actionTypeObj.find('.task-param[name="'+key+'"]');
					
					$thisArg.find('input[name="'+key+'"]').val(value['value']);
					$thisArg.find('select option[value="'+value['condition']+'"]').attr('selected','selected');
				});
			}*/
        }
    }

    function setEvents() {
        if(!$source.is(':empty')) {
            if(!$then.is(':empty')) {
                var $successEvents = $then.find('.new-results');

                if($successEvents.find('.results [data="%error%"]')) {
                    $successEvents.empty();
                    $successEvents.append(
                        $('<h2></h2>').text('Events'),
                        $source.find('.results :not([data="%error%"])').clone()
                    );

					updateResults();
                }
            }

            if(!$error.is(':empty')) {
                var $errorEvents = $error.find('.new-results');

                if($errorEvents.find('.results :not([data="%error%"])')) {
                    $errorEvents.empty();
                    $errorEvents.append(
                        $('<h2></h2>').text('Events'),
                        $('<span></span>')
                            .addClass('param event button small red')
                            .attr('data','%error%')
                            .text('Error')
                    );
                }
            }
        }
        else {
            $('.droparea').find('.new-results').empty();
        }

        $('.droparea .param.event').each(function(){
            $(this).draggable({
                helper: 'clone',
                containment: $(this).parent().parent()
            });
        });

        $('#actionThen input, #actionElse input, #actionThen textarea, #actionElse textarea').droppable({
            accept: '.param.event',
            tolerance: 'pointer',
            over: function(event,ui) {
                $(this).focus();
            },

            drop: function(event, ui) {
                var $this = $(this);
                var pos = $this.getCursorPosition(), substr = $(ui.draggable).attr('data');
                $this.val([$this.val().slice(0, pos), substr, $this.val().slice(pos)].join(''));
                editingStateOn();
            }
        })
    }

    (function($) {
        $.fn.getCursorPosition = function() {
            var input = this.get(0);
            if (!input) return; // No (input) element found
            if ('selectionStart' in input) {
                // Standard-compliant browsers
                return input.selectionStart;
            } else if (document.selection) {
                // IE
                input.focus();
                var sel = document.selection.createRange();
                var selLen = document.selection.createRange().text.length;
                sel.moveStart('character', -input.value.length);
                return sel.text.length - selLen;
            }
        }
    })(jQuery);

    function insertAtCaret(areaId,text) {
        var txtarea = document.getElementById(areaId);
        var scrollPos = txtarea.scrollTop;
        var strPos = 0;
        var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
            "ff" : (document.selection ? "ie" : false ) );
        if (br == "ie") {
            txtarea.focus();
            var range = document.selection.createRange();
            range.moveStart ('character', -txtarea.value.length);
            strPos = range.text.length;
        }
        else if (br == "ff") strPos = txtarea.selectionStart;

        var front = (txtarea.value).substring(0,strPos);
        var back = (txtarea.value).substring(strPos,txtarea.value.length);
        txtarea.value=front+text+back;
        strPos = strPos + text.length;
        if (br == "ie") {
            txtarea.focus();
            var range = document.selection.createRange();
            range.moveStart ('character', -txtarea.value.length);
            range.moveStart ('character', strPos);
            range.moveEnd ('character', 0);
            range.select();
        }
        else if (br == "ff") {
            txtarea.selectionStart = strPos;
            txtarea.selectionEnd = strPos;
            txtarea.focus();
        }
        txtarea.scrollTop = scrollPos;
    }


/*    window.onbeforeunload = function(event) {
        if ($saveButton.hasClass('green')){
            return "You will lost the changes if you leave the page.";
        }
    }*/

    $('body').on('change', '.type-selector', function(e, data) {
		$('.custom-params', $(this).parent()).load( window.flexiweb.site_url + 'ajax/task_fields.php?type=' + $(this).val(), function() {
            if (typeof data !== 'undefined') {
                setActionParameters(data.src, data.data);
            }
            $('.custom-params .task-param').keyup(function() {
                editingStateOn();
            });
            $('.custom-params select.task-param').change(function() {
                editingStateOn();
            });
        });
		$('#actionThen').find('.custom-results').load( window.flexiweb.site_url + 'ajax/task_results.php?type=' + $(this).val(), function() {
            $('.custom-results .event').draggable({
                helper: 'clone',
                containment: $(this).parent().parent()
            });
        });


		//initTaskEditor();
    });

});