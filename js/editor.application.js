var initPreview = function ($sizeInput, $orientationInput, $previewFrame) {
    var updatePreviewSize = function () {
        var data = $sizeInput.children(":selected").data();
        var isPortrait = $orientationInput.val() == "portrait";
        $previewFrame.css({
            "width": parseInt(isPortrait? data.width : data.height) + "px",
            "height": isPortrait ? data.height : data.width + "px"
        });
    };
    $sizeInput.change(updatePreviewSize);
    $orientationInput.change(updatePreviewSize);
    updatePreviewSize();

    $('#preview-form').submit();

    return $previewFrame;
};
var getWidget = function(name, editor) {
    var WidgetClasses = {};
    var commonContext = editor.commonContext;
    var widget = {
        updatePages: function($li, callback) {
            var $linkSelect = $li.find('[name="link"]');
            $linkSelect.find("option:not(:first)").remove();

            $.each(editor.pages, function(id, page){
                $linkSelect.append($("<option value='"+page.id+"'>").text(page.name));
            });

            //if(typeof callback !== 'undefined' && callback != null)
            if(!!callback) callback();
        },
        addedCallback: function() {},
        collect: function() { return {}},
        fill: function() {}
    };


    /*
    * Widgets. Each object contain three individual methods:
    * 1. addedCallback - what to do when widget was added into stack;
    * 2. fill - what to do on load object;
    * 3. collect - how to collect data from current widget.
    *
    * by default functions do/return nothing
    * */

    WidgetClasses.iBeacon = {
        addedCallback: function($item){
            var $select = $item.find('[name="uuid"]')
            var beacons = JSON.parse(editor.beacons);
            $.each(beacons, function(k, beacon) {
                $select.append($('<option/>').val(k).text(beacon.display_name));
            });
            $select.bind('change', editor.setUnsavedState);
        },
        collect: function($item) {
            return {
                uuid: $item.find('[name="uuid"]').val()
            }
        },
        fill: function($item, data) {
            $item.find('[name="uuid"]').val(data.uuid)
        }
    };
    WidgetClasses.LogoutButton = {};
    WidgetClasses.DataSetSelect = {};
    WidgetClasses.HTMLBlock = {
        addedCallback: function($li){
            var type = commonContext.types[editor.pages[editor.active_page].contextType];
            var $HTMLEditor = $li.find(".HTMLEditor").HTMLEditor();

            console.log(commonContext,type);

            if(type){
                var fields = {};

                $.each(type.fields, function(fieldName, fieldOptions) {
                    var alias = fieldOptions.alias;
                    fields[fieldName] = {
                        name: alias ? alias: fieldName
                    }
                });

                console.log(fields)
                $HTMLEditor.empty().HTMLEditor("setFields", fields);
            }

            $HTMLEditor.bind('change', editor.setUnsavedState)
        },
        collect: function($item) {
            return {
                content: encodeURI($item.find('[name="content"]').html())
            }
        },
        fill: function($item, data) {
            $item.find('[name="content"]').html(decodeURI(data.content));
        }

    };
    WidgetClasses.DataForm = {
        addedCallback: function($li){
            var $createOption = $li.find(".data-form-mode > option[value='create']");
            var $dataFormSelect = $li.find(".data-form-select");

            $dataFormSelect.bind('change', function(){
                //var canCreateNew = !!parseInt(commonContext.dataForms[$(this).val()]["can_create_new"]);
                editor.setUnsavedState();
                //$createOption.attr("disabled", canCreateNew);
            }).trigger('change');
        },
        fill: function($li, data){
            $li.find("[name='table_stripes']").prop("checked", modifyValue(data.table_stripes));
            $li.find('[name="qrMode"]').prop('checked', modifyValue(data.qrMode));
        },
        collect: function($li){
            var b =  {
                form_id: $li.find("[name='form_id']").val(),
                mode: $li.find("[name='mode']").val(),
                table_style: $li.find("[name='table_style']").val(),
                table_stripes: $li.find("[name='table_stripes']").prop("checked") ? 1: 0,
                qrMode: $li.find('[name="qrMode"]').prop("checked")? 1: 0
            };
            return b;
        }
    };
    WidgetClasses.QRCodeScanner = {
        addedCallback: function($li){
            widget.updatePages($li);
        },
        collect: function($li){
            return {
                linked_page: $li.find('select[name="link"]').val()
            }
        },
        fill: function($li, data){
            widget.updatePages($li, function(){
                //$li.find('select[name="link"]').attr('data-value', data.linked_page);
                $li.find('select[name="link"]').val(data.linked_page);
            });

        }
    };
    WidgetClasses.Chart = {
        change: function($li, callback) {
            var $query = $li.find('[name="query"]');
            var $x = $li.find('[name="x-axis"]');
            var $y = $li.find('[name="y-axis"]');

            var $grouped = $li.find('[name="grouped"]');
            var $groupBy = $li.find('[name="group_by"]');
            var $groupFor = $li.find('[name="group_for"]');
            var queryData = commonContext.queries[$query.val()];
            editor.setUnsavedState();

            if(typeof queryData !== 'undefined') {
                var queryCode = queryData.code;
                $x.empty();
                $y.empty();

                var deniedGroups = true;
                var joinedTypes = queryData.joined_types;

                if(typeof joinedTypes !== 'undefined' && joinedTypes != null && joinedTypes != '') {
                    var parsedJoinedTypes = JSON.parse(joinedTypes);
                    var joins = parsedJoinedTypes[queryData.main_type];
                    if(typeof joins !== 'undefined' && joins != null) {
                        if(joins.length > 0) {
                            deniedGroups = false;
                        }
                    }

                }

                $groupBy.prop('disabled', deniedGroups);
                $groupFor.prop('disabled', deniedGroups);
                $grouped.prop('disabled', deniedGroups);

                if(deniedGroups)
                    $grouped.prop('checked', false);

                $groupBy.empty();
                $groupFor.empty();

                $groupBy.unbind('change').bind('change', function() {
                    $y.empty();

                    var id = $groupBy.find('option:selected').attr('data-id');
                    if(typeof id !== 'undefined' && id != null) {
                        flexiweb.callFunction.getEnumFields(id, function(fields) {
                            for( var value in fields ) {
                                $y.append($('<option>').val(value).text(fields[value]));
                            }
                        });
                    }
                });
                $grouped.unbind('change').bind('change', function() {
                    var checked = $grouped.prop('checked');
                    editor.setUnsavedState();


                    $y.empty();
                    if(checked) {
                        $groupBy.trigger('change');
                    }
                    else {
                        $.each(queryCode, function(i, field) {
                            $y.append($('<option>').val(field.alias).text(field.caption));
                        });
                    }
                });

                $.each(queryCode, function(i, field) {
                    var $opt = $('<option>').val(field.alias).text(field.caption);
                    $x.append($opt.clone());
                    if($.isNumeric(field.type))
                        $groupBy.append($opt.clone().attr('data-id', field.type));
                    else
                        $groupFor.append($opt.clone());
                });

                $grouped.trigger('change');

                if(typeof callback !== 'undefined' && callback != null)
                    callback();
            }
        },
        addedCallback: function($li){
            var $query = $li.find('[name="query"]');
            var widget = this;

            $li.find('[name="zoom"]').prop('checked', true);
            $query.unbind('change').bind('change', function() {
                widget.change($li);
            }).trigger('change');

        },
        collect: function($li){
            var data =  {
                chart_type: $li.find('[name="chart_type"]').val(),
                query: $li.find('[name="query"]').val(),
                zoom: $li.find('[name="zoom"]').prop('checked') ? 1 : 0,
                xAxis: $li.find('[name="x-axis"]').val(),
                yAxis: (function() {
                    var yAxis = [];
                    $.each($li.find('[name="y-axis"]').find(':selected'), function() {
                        yAxis.push($(this).val());
                    });

                    return yAxis;
                })(),

                grouped: $li.find('[name="grouped"]').prop('checked')? 1 : 0,
                group_by: $li.find('[name="group_by"]').val(),
                group_for: $li.find('[name="group_for"]').val()
            };


            var types = {};
            $.each($li.find('.wrap.types').find('input'), function(){
                var $this = $(this);
                types[$this.attr('name')] = $this.prop('checked') ? 1 :0;
            });

            data.types = types;

            return data;
        },
        fill: function($li, data){
            $li.find('[name="query"]').val(data.query);//.trigger('change');

            this.change($li, function() {

                var grouped = modifyValue(data.grouped);

                $li.find('[name="zoom"]').prop('checked', modifyValue(data.zoom));
                $li.find('[name="chart_type"]').val(data.chart_type);
                $li.find('[name="x-axis"]').val(data.xAxis);
                $li.find('[name="grouped"]').prop('checked', grouped);
                $li.find('[name="group_by"]').val(data.group_by);
                $li.find('[name="group_for"]').val(data.group_for);

                if(grouped) {
                    var $y = $li.find('[name="y-axis"]');
                    var $groupBy = $li.find('[name="group_by"]');
                    $y.empty();

                    flexiweb.callFunction.getEnumFields($groupBy.find('option:selected').attr('data-id'), function(fields) {
                        for( var value in fields ) {
                            $y.append($('<option>').val(value).text(fields[value]));
                        }
                        $y.val(data.yAxis);
                    });
                }
                else {
                    $li.find('[name="y-axis"]').val(data.yAxis);
                }

                var $types = $li.find('.wrap.types');
                var types = data.types;
                if(typeof types  === 'object' && typeof types !== 'null') {
                    $.each(types, function(type, val) {
                        $types.find('input[name="' + type + '"]').prop('checked', modifyValue(val));
                    })
                }
            });
        }
    };
    WidgetClasses.Gallery = {
        addedCallback: function($li){
            var $querySelect = $li.find('.query-select');
            $querySelect.unbind('change').bind('change', function(){
                editor.setUnsavedState();
                var query = $querySelect.val();

                if(query === 'undefined' || query == null)
                    return;

                query = commonContext.queries[$querySelect.val()];

                var joinedTypes = query.joined_types;
                var $imageFieldSelect = $li.find('[name="image_field"]');
                var $titleFieldSelect = $li.find('.title-field-select');

                $imageFieldSelect.empty().append($('<option/>').val("").text('Not selected'));
                $titleFieldSelect.empty().append($('<option/>').val("").text('Not selected'));

                var jt, typesList = [];

                if(joinedTypes != '') {
                    try {
                        jt = JSON.parse(joinedTypes)

                    } catch(e) {}

                    if(isEmpty(jt)) {
                        typesList.push(query.main_type);
                    }

                    else {
                        getTypesArrayFromJoinedTypes(jt, typesList);
                    }
                }
                else {
                    typesList.push(query.main_type);
                }

                var imageFields = [];
                var notImageFields = [];

                //$.each(typesList, function(index, typeId){
                    //var fields = commonContext.types[typeId].fields;
                //});

                var fields = query.code;

                $.each(fields, function(fieldName, fieldOptions) {
                    var alias = fieldOptions.alias || fieldName;
                    if(fieldOptions.type == 'image') imageFields.push(alias);
                    else notImageFields.push(alias)
                });

                $.each(imageFields, function(index, imageField) {
                    $imageFieldSelect.append($('<option/>').val(imageField).text(imageField));
                });

                $.each(notImageFields, function(index, titleField) {
                    $titleFieldSelect.append($('<option/>').val(titleField).text(titleField));
                });
            });
            $querySelect.trigger('change')
        },
        collect: function($li){
            return {
                query: $li.find('.query-select').val(),
                image_field: $li.find('[name="image_field"]').val(),
                title_field: $li.find('.title-field-select').val(),
                image_style: $li.find('.image-style-select').val()
            }
        },
        fill: function($li, data){
            $li.find('.query-select').val(data.query).trigger('change');
            $li.find('[name="image_field"]').val(data.image_field);
            $li.find('.title-field-select').val(data.title_field);
            $li.find('.image-style-select').val(data.image_style);
        }
    };
    WidgetClasses.QueryList = {
        updateFields: function($li, callback){
            var $querySelect = $li.find(".query-select");
            var $HTMLEditor = $li.find(".HTMLEditor").HTMLEditor();
            var $standardFieldsDiv = $li.find(".standard-fields");
            var $criteriaSearchFieldsDiv = $li.find(".criteria-search-fields");
            var $imageFields = $li.find('[name="image_field"]');
            var $queryEnum = $li.find('[name="query_enum"]');
            var id = $querySelect.val();
            var $beaconFieldSelect = $li.find('[name="beacon_field"]').empty();
            var $content = $li.find('[name="content"]');


            if(typeof id === 'undefined' || id == null)
                return;

            var query = commonContext.queries[id];

            if(typeof query === 'undefined' || query == null)
                return;

            if(typeof query.main_type === 'undefined' || query.main_type == null)
                return;

            var queryObjectType = commonContext["types"][query.main_type];

            if(typeof queryObjectType === 'undefined' || queryObjectType == null)
                return;

            $standardFieldsDiv.empty();
            $criteriaSearchFieldsDiv.empty();
            $beaconFieldSelect.empty();
            $content.empty();
            $queryEnum.add($imageFields).empty().append($('<option>').val("").text('Not selected'));

            $.each(query.code, function(_, fieldData) {
                var type = fieldData.type;

                if(typeof type === 'undefined' || type == null)
                    return;

                var alias = fieldData.alias;
                var $label = $('<label>').text(alias);
                var $criteriaCheckbox = $("<input type='checkbox'>").prop('checked', true).attr('name','criteria_search').val(alias);
                var $standardCheckbox = $("<input type='checkbox'>").prop('checked', true).attr('name','standard_fields').val(alias);
                var $option = $("<option>").val(alias).text(alias);
                var $span = $('<span>').addClass('nowrap');//.css('white-space', 'nowrap');

                $standardFieldsDiv.append($span.clone().append($standardCheckbox).append($label.clone()));
                $criteriaSearchFieldsDiv.append($span.clone().append($criteriaCheckbox).append($label.clone()));//.append().append(alias);
                $beaconFieldSelect.append($option.clone());

                if(type.toLowerCase() == "image")
                    $imageFields.append($option.clone());

                if($.isNumeric(type)) {
                    $queryEnum.append($option.clone());
                }

            });

            $standardFieldsDiv.add($criteriaSearchFieldsDiv).find('input').bind('change', editor.setUnsavedState);

            var fields = $.extend({}, query.code);
            $HTMLEditor.HTMLEditor("setFields", fields);

            if(typeof callback !== 'undefined' && callback != null)
                callback();
        },
        //updateLinks: function($li, callback) {
        //    var $linkSelect = $li.find('[name="link"]');
        //    $linkSelect.find("option:not(:first)").remove();
        //    var ppages = [];
        //    $.each(editor.pages, function(id, page){
        //        ppages.push(id);
        //        $linkSelect.append($("<option value='"+page.id+"'>").text(page.name));
        //    });
        //
        //    if(typeof callback !== 'undefined' && callback != null)
        //        callback();
        //},
        special: function($li) {},
        addedCallback: function($li){
            var widget = this;
            $li.find(".query-select").unbind('change').bind('change', function(){
                //widget.updateLinks($li);
                widget.updateFields($li, function(){
                    widget.updatePages($li, function(){
                        editor.setUnsavedState();
                    });

                });
            }).trigger('change');

            this.special($li);

            $li.find(".display-type").bind('change', function(){
                if($(this).val() == "standard"){
                    $li.find(".HTMLEditor").hide();
                    $li.find(".standard-fields").show();
                }
                else {
                    $li.find(".HTMLEditor").show();
                    $li.find(".standard-fields").hide();
                }
            }).trigger('change');
            $li.find(".search-type").bind('change', function(){
                var $criteriaSearchFieldsDiv = $li.find(".criteria-search-fields");
                if($(this).val() == "criteria"){
                    $criteriaSearchFieldsDiv.show();
                } else {
                    $criteriaSearchFieldsDiv.hide();
                }
            }).trigger('change');

        },
        collect: function($li){
            var b = {};

            $.each($li.find('input'), function(){
                b[this.getAttribute("name")] = this.value;
            });

            var data = {
                query:            $li.find(".query-select").val(),
                display_type:     $li.find("[name='display_type']").val(),
                link:             $li.find("[name='link']").val(),
                filter_by_object: $li.find("[name='filter_by_object']").prop("checked") ? 1 : 0,
                thumbnail_field:  $li.find('[name="image_field"]').val(),
                query_enum:       $li.find('[name="query_enum"]').val(),
                items_per_page:   $li.find('[name="items_per_page"]').val(),
                search_type:      $li.find('[name="search_type"]').val(),
                beacon:           $li.find('[name="beacons"]').val(),
                beacon_field:     $li.find('[name="beacon_field"]').val(),
                table_stripes:    $li.find('[name="table_stripes"]').val(),
                table_style:      $li.find('[name="table_style"]').val()
            };

            if($li.find(".display-type").val() == "standard"){
                var $checkedInputs = $li.find(".standard-fields input:checked");
                data.standard_fields = $.map($checkedInputs, function(checkbox){
                    return $(checkbox).attr("value");
                });
                $li.find(".display-type").change();
                delete data.content;
            }
            else {
                data.content = encodeURI($li.find('[name="content"]').html());//$li.find('[name="content"]').html();
                delete data.standard_fields;
            }

            if($li.find(".search-type").val() == "criteria"){
                var $checkedInputs = $li.find(".criteria-search-fields input:checked");
                data.criteriaSearchFields = $.map($checkedInputs, function(checkbox){
                    return $(checkbox).attr("value");
                });
            }

            return data;
        },
        fill: function($li, data){
            var widget = this;
            $li.find('.query-select').val(data.query);

            var standardFields = data.standard_fields || data.standardFields;

            widget.updateFields($li, function() {
                widget.updatePages($li, function(){
                //widget.updateLinks($li, function(){
                    $li.find('[name="query_enum"]').val(data.query_enum);
                    $li.find('[name="image_field"]').val(data.thumbnail_field);
                    $li.find("[name='filter_by_object']").prop("checked", modifyValue(data.filter_by_object));
                    $li.find('[name="link"]').val(data.link);
                    $li.find('[name="content"]').html(decodeURI(data.content));
                    $li.find('[name="search_type"]').val(data.search_type);
                    $li.find('[name="items_per_page"]').val(data.items_per_page);
                    $li.find('[name="update_rate"]').val(data.update_rate);
                    $li.find('[name="display_type"]').trigger('change');
                    $li.find('[name="beacons"]').val(data.beacons);
                    $li.find('[name="beacon_field"]').val(data.beacon_field);

                    if(data.display_type == "standard" && standardFields) {
                        var $inputsDiv = $li.find(".standard-fields");
                        $inputsDiv.find("input").prop('checked', false);
                        $.each(standardFields, function(i, field){
                            $inputsDiv.find("input[value='"+field+"']").prop("checked", true);
                        });
                    };

                    if(data.search_type == "criteria" && data.criteriaSearchFields) {
                        var $inputsDiv = $li.find(".criteria-search-fields");
                        $.each(data.criteriaSearchFields, function(i, field){
                            $inputsDiv.find("input[value='"+field+"']").prop("checked", true);
                        });
                    };
                })
            });
        }
    };
    WidgetClasses.iBeaconQuery = $.extend({}, WidgetClasses.QueryList, {
        special: function($li) {
            var $beaconSelect = $li.find('select[name="beacons"]').empty();

            var beacons = JSON.parse(editor.beacons);
            if(typeof editor.beacons.errors !== 'undefined' || editor.beacons.errors != null)
                return;

            $.each(beacons, function(k, beacon) {
                $beaconSelect.append($('<option/>').val(k).text(beacon.display_name));
            });
        }
    });
    WidgetClasses.Maps = {
        addedCallback: function ($li) {
            var $querySelect = $li.find('select[data-type="query"]');
            var $nameSelect = $li.find('select[data-type="name"]');
            var $locationSelect = $li.find('select[data-type="location"]');
            var $locationSecondSelect = $li.find('select[data-type="location_additional"]');

            $querySelect.unbind('change').bind('change', function () {
                editor.setUnsavedState();

                var currentValue = $querySelect.val();
                var $selectsWithFields = $nameSelect.add($locationSelect).add($locationSecondSelect);

                $selectsWithFields.empty();
                $locationSecondSelect.append($('<option/>').val('-1').text('Not selected'));

                if (currentValue) {
                    $.each(commonContext.queries[currentValue].code, function (i, field) {
                        $selectsWithFields.append($('<option>').val(field.alias).text(field.alias));
                    });
                }
            }).trigger('change');
        },
        collect: function ($li) {
            return {
                query: $li.find('select[data-type="query"]').val(),
                nameField: $li.find('select[data-type="name"]').val(),
                locationField: $li.find('select[data-type="location"]').val(),
                locationSecondField: $li.find('select[data-type="location_additional"]').val()
            }
        },
        fill: function ($li, data) {
            $li.find('select[data-type="query"]').val(data.query).trigger('change');
            $li.find('select[data-type="name"]').val(data.nameField);
            $li.find('select[data-type="location"]').val(data.locationField);
            $li.find('select[data-type="location_additional"]').val(data.locationSecondField);
        }
    };
    WidgetClasses.DataSetRoles = {
        collect: function($li) {
            return {
                oneRole: $li.find('[name="oneRole"]').prop('checked') ? 1 : 0
            }
        },
        fill: function($li, data) {
            $li.find('[name="oneRole"]').prop('checked', modifyValue(data.oneRole));
        }
    };
    WidgetClasses.Calendar = {
        addedCallback: function($li){
            var oldNumberOfQueries;
            var $numberOfQueries = $li.find('select[name="numberOfQueries"]');
            var $queriesSettings = $li.find('.otherQueries');
            var $template = $li.find('.firstQuery').children();
            $numberOfQueries.bind('change', function(){
                editor.setUnsavedState();

                var newNumberOfQueries = $numberOfQueries.val(), i;

                if(!oldNumberOfQueries) oldNumberOfQueries = 1;

                //console.log(oldNumberOfQueries , ' - > ', newNumberOfQueries);

                if( oldNumberOfQueries > newNumberOfQueries ) {
                    for(i = newNumberOfQueries; i < oldNumberOfQueries; i++) {
                        $queriesSettings.find('.calendarQuery[data-query-order="'+ i +'"]').remove();
                    }
                }
                else {
                    for (i = oldNumberOfQueries; i < newNumberOfQueries; i++) {
                        var $currentTemplate = $template.clone();

                        $.each($currentTemplate.find('select'), function(index, item) {
                            var $item = $(item);

                            switch ($item.attr('data-type')) {
                                case 'date':
                                case 'time':
                                case 'title':
                                    $item.empty(); break;
                                default: $item.val('');
                            }
                        });
                        $queriesSettings.append($('<div class="calendarQuery" data-query-order='+ i +'>').append($currentTemplate));
                    }
                }

                $.each($li.find('select[data-type="query"]'), function(index, querySelect){
                    var $querySelect = $(querySelect);

                    $querySelect.bind('change', function(){
                        var currentValue = this.value,
                            $options = $('<select>'),
                            $currentQueryDiv = $(this).parents('.calendarQuery'),
                            $dateStartSelect = $currentQueryDiv.find('select[data-type="dateStart"]'),
                            $dateEndSelect = $currentQueryDiv.find('select[data-type="dateEnd"]'),
                            $timeStartSelect = $currentQueryDiv.find('select[data-type="timeStart"]'),
                            $timeEndSelect = $currentQueryDiv.find('select[data-type="timeEnd"]'),
                            $titleSelect = $currentQueryDiv.find('select[data-type="title"]'),
                            $emptyOption = $('<option>').val('').text('Unknown');


                        var $formSelect = $li.find('select[data-type="form"]');
                        $formSelect.empty().append('<option value="">Please select form</option>');


                        if(currentValue && currentValue != '') {
                            $.each(commonContext['queries'][currentValue].code_without_joins, function(fieldName, fieldDisplayName){
                                $options.append($('<option>').val(fieldName).text(fieldDisplayName));
                            });

                            var queryMainType = commonContext['queries'][currentValue].main_type;

                            if(editor.filteredForms[queryMainType]) {
                                $.each(editor.filteredForms[queryMainType], function(id, object) {
                                    $formSelect.append($('<option>').val(id).text(object.display_name));
                                })
                            }
                        }

                        var optionsHtml = $options.html();

                        $dateStartSelect.empty().append(optionsHtml);
                        $dateEndSelect.empty().append($emptyOption.clone()).append(optionsHtml);
                        $timeStartSelect.empty().append(optionsHtml);
                        $timeEndSelect.empty().append($emptyOption.clone()).append(optionsHtml);
                        $titleSelect.empty().append(optionsHtml);

                    })
                });
                oldNumberOfQueries = newNumberOfQueries;

                $li.find('select').bind('change', editor.setUnsavedState())

            }).trigger('change');
        },
        collect: function($li) {
            var $queries = $li.find('.calendarQuery');
            var res = {};

            $.each($queries, function(index, div) {
                var $currentQueryDiv = $(div);
                var queryNumber = $currentQueryDiv.attr('data-query-order');

                if(!res[queryNumber]) res[queryNumber] = {};

                $.each($currentQueryDiv.find('select'), function(){
                    res[queryNumber][$(this).attr('data-type')] = this.value;
                })
            });

            //res['numberOfQueries'] = $li.find('[name="numberOfQueries"]').val();
            return {
                calendar_data: res,
                numberOfQueries: $li.find('[name="numberOfQueries"]').val()
            };
        },
        fill: function($li, data) {
            $li.find('select[name="numberOfQueries"]').val(data.numberOfQueries).trigger('change');

            if(data.calendar_data) {
                $.each(data.calendar_data, function(queryOrder, object){
                    if(object !== null && typeof object === 'object') {
                        var $currentQueryDiv = $li.find('.calendarQuery[data-query-order="'+ queryOrder +'"]');
                        $.each(object, function(name, value) {
                            var $currentSelect = $currentQueryDiv.find('select[data-type="'+ name +'"]');
                            $currentSelect.val(value);
                            if(name == 'query')
                                $currentSelect.trigger('change');
                        })
                    }
                })
            }


        }
    };

    $.extend(widget, WidgetClasses[name]);

    return widget;
};

var flexiweb = window.flexiweb;

var Widget = function(data, editor) {

    var name = data.name;

    PaletteItem.call(this, name, editor);

    var item = this;

    this.create = function() {
        var $item = ich["common-widget-template"]();
        var $content = $();
        var templateName = name + '-template';

        $item.attr('data-widget', name);
        $item.attr('data-palette', data.palette);

        try {
            $content = ich[templateName]();
        }
        catch(e) {}

        // replace standard widgets' methods on invididual
        $.extend(this, getWidget(name, editor));

        var temp = item.collect;

        if(typeof temp === 'undefined' || temp == null)
            temp = function() { return {} };

        item.collect = function($item) {
            var data = $item.data();

            var collection = {
                name: name,
                palette: data.palette,
                container: data.container,
                page: $item.attr('data-page'),
                type: $item.data().widget,
                title: $item.find("[name='title']").val(),
                inset_style: $item.find("[name='inset_style']").prop("checked") ? 1 : 0,
                header_bar: $item.find("[name='header_bar']").prop("checked") ? 1 : 0
            };

            var res = $.extend(temp($item), collection);
            return res;
        };

        $item.find('.widget_content').append($content);
        $item.find('span.title').append(name);

        return $item;
    };
    this.added = function($item) {
        //console.log('added app widget')
        $item.find('.remove').bind('click', function() {
            $item.remove();
            editor.setUnsavedState();
        });
        $item.find('input, select').bind('change', editor.setUnsavedState);
        $item.find("input, textarea").keyup(editor.setUnsavedState);
        $item.attr('data-page', data.page || editor.active_page);
        $item.find('.collapse-button').unbind('click').bind('click', collapseBlock);
        item.addedCallback($item);
    };
    this.init();
};

var ApplicationEditor = function(args) {
    var editor = this;

    if(typeof args === 'undefined' || args == null)
        args = {};

    var timeout = flexiweb.loaderShow();
    flexiweb.callFunction.getObjectTypeIdByName('app_data', function (response) {
        flexiweb.loaderHide(timeout);
        args.object_type_id = response;
        args.object_id = getUrlParameter('object_id');
        args.set_id = flexiweb.set_id;
        args.page = $('.tab-pane.active');
        args.pages = {};
        args.total_pages = 0;
        args.isPagesEditor = 1;
        args.widgets = ['QueryList', 'DataForm', 'Chart', 'Gallery', 'Maps', 'HTMLBlock', 'QRCodeScanner',
            'Calendar', 'iBeacon', 'iBeaconQuery', 'DataSetRoles' ];//.sort();'LogoutButton','DataSetSelect',

        args.newObjectId = args.parent_app;

        Editor.call(editor, args);
        $.extend(editor, args);

        /* This method checks is object correct or not. */
        editor.checkObject = function(object) {
            if(typeof object === 'undefined' || object == null)
                return false;

            return true;
        };

        /* This method inits existed object. */
        editor.loadObject = function(id) {
            id = id.split('.')[1];

            if(typeof id === 'undefined' || id == null) {
                editor.removePages();
                editor.unloadObject();
                editor.addPage();
                editor.stateChange(true);
            }
            else {
                var timeout = flexiweb.loaderShow();
                flexiweb.callFunction.getObject(editor.object_type_id, id, function(object) {

                    //console.log('LOADED OBJECT :: ', object);
                    var isObjectCorrect = editor.checkObject(object);

                    if(!isObjectCorrect) {
                        editor.removePages();
                        editor.unloadObject();
                        editor.$typeSelector.trigger('change');
                    }
                    else {
                        object = editor.modifyLoadedObject(object);
                        window.history.pushState("t", "Title", "?object_id=" + editor.object_id);
                        editor.clearContainers();
                        editor.removePages();
                        editor.$typeSelector.val(object.object_type);
                        editor.$objectName.val(object.display_name);
                        editor.loadedObject = object;
                        editor.render(editor.prepareData(object));
                        editor.specialRenderData(object);
                        editor.$pagesTabs.find('li[id=1]').trigger('click');

                    }
                    flexiweb.loaderHide(timeout);

                });
            }

        };

        editor.modifyLoadedObject = function(object) {

            var newCode = $.extend({}, object.code, { pages : {}});
            var code = object.code;
            var i = 1;

            var start = code.start_page || code.startPage;
            delete code.startPage;


            if(code.pages) {
                $.each(code.pages, function(id, pageData) {
                    if(id == i) {
                        newCode.pages[i] = pageData;
                        i++;
                        return;
                    }

                    pageData.id = i;

                    if(typeof pageData.hidden === 'undefined' || pageData.hidden == null) {
                        pageData.hidden = pageData.hideInNavigation;
                        delete pageData.hideInNavigation;
                    }

                    if(typeof pageData.context_type === 'undefined' || pageData.context_type == null) {
                        pageData.context_type = pageData.contextType == "" ? 0 : pageData.contextType;
                        delete pageData.contextType;
                    }

                    if(id == start)
                        code.start_page = start = i;

                    $.each(pageData.elements, function(_, widget) {
                        if(typeof widget.palette === 'undefined' || widget.palette == null)
                            widget.palette = 'widgets';

                        if(typeof widget.container === 'undefined' || widget.container == null)
                            widget.container = 'stack';

                        if(typeof widget.page === 'undefined' || widget.page == null)
                            widget.page = i;


                        if(typeof widget.name === 'undefined' || widget.name == null)
                            widget.name = widget.type;
                    });

                    newCode.pages[i] = pageData;

                    delete code.pages[id];

                    i++;
                });
            }

            object.code = newCode;

            return object;
        };

        /* This method loaded palette depend on data. */
        editor.loadType = function() {
            var obj = {};
            $.each(editor.widgets, function(_, name) {
                obj[name] = { name : name };
            });

            editor.dataElements = { widgets: obj };
            editor.clearContainers();
            editor.initPalettes();
            editor.loadTypeCallback();
            //flexiweb.loaderHide(timeout);
        };

        /* This method changes format for standard 'collect' method. */
        editor.prepareData = function(data) {
            //ready typical editor data

            var code = data.code;

            var elements = [];
            var pageExist = false;

            $.each(code.pages, function(_, page) {
                editor.addPage(page);
                pageExist = true;

                var widgets = page.elements;
                if(typeof widgets === 'undefined' || widgets == null)
                    return;

                $.each(widgets, function(__, item) {
                    elements.push(item);
                });
            });

            if(!pageExist)
                editor.addPage();

            return elements;
        }

        /* This method saves object. */
        editor.save = function() {
            if(editor.$objectName.val().length == 0) {
                alert('Please, fill display name.');
                return;
            }

            var data = editor.collect();
            var isNewObject = !data.object_id;
            data.schema_id = flexiweb.schema_id;

            var action = isNewObject ? flexiweb.callFunction.addAppVersion : flexiweb.callFunction.updateObject;

            if(!isNewObject)
                data.object_id = data.object_id.split('.')[1];

            data.parent_app_id = editor.parent_app;

            action(data, function(response) {
                if(typeof response.errors === 'undefined' || response.errors == null){
                    if(isNewObject){
                        window.onbeforeunload = undefined;
                        window.location.search = "object_id=" + response ;

                    } else {
                        editor.$objectSelector.find('option:selected').text(data.display_name);
                        editor.stateChange(true);
                    }
                }
            });

        };

        editor.remove = function() {
            if(confirm('Are you sure that you want delete this object?')) {

                flexiweb.callFunction.removeObject(editor.object_type_id, editor.object_id.split('.')[1], function(response) {
                    if(response) {
                        editor.$objectSelector.find('option[value="' +  editor.object_id + '"]').remove();
                        editor.unloadObject();
                        editor.removePages();
                        editor.addPage();
                        editor.$typeSelector.trigger('change');
                        editor.stateChange(true);
                        alert('Object successfully removed.')
                    }
                })
            }
        };

        /* COMMON Application Widgets */

        /* This method creates bottom navigation widget. */
        editor.createNavigationWidget = function() {
            var $content = ich["bottom_navigation_widget"]();

            $content.find('.linked_page').bind('change', editor.setUnsavedState);
            $content.find('.icon_select').ImageSelector({ placeholderSize: 'small', callback: editor.setUnsavedState });
            return $content;
        };

        /* This method creates ibeacon widget. */
        editor.createBeaconWidget = function() {
            var $select = $('<select>').attr('id', 'ibeacon_uuid');
            var beacons = JSON.parse(editor.beacons);

            if(typeof beacons.errors !== 'undefined' && beacons.errors != null) {
                var $span = $('<span>').text('There is error with getting beacons.')
                return $span;
            }


            $.each(beacons, function(k, beacon) {
                $select.append($('<option/>').val(k).text(beacon.display_name));
            });
            $select.bind('change', editor.setUnsavedState);
            //return $select;
            return $();
        };

        /* This method creates all available common widgets: ibeacon and bottom navigation. */
        editor.commonWidgetsInit = function() {
            var $commonWidgets = editor.$commonWidgets = $('#common-widgets');
            //var $widget = ich["common_widget_without_params"]();

            //var $ibeaconSettings = $widget.clone().hide().attr('id', 'beacons_widget');
            //$ibeaconSettings.find('.title').text('iBeacons');
            //$ibeaconSettings.find('.collapsible').append(editor.createBeaconWidget());


            var $bottomNavigation = ich["common_widget_without_params"]().hide().attr('id', "navigation_widget");
            $bottomNavigation.find('.collapsible').append(editor.createNavigationWidget());


            $bottomNavigation.find('.title').text('Bottom Navigation');
            editor.$bottomNavigationSettings = $bottomNavigation;

            editor.$bottomNavigationCheckbox.bind('click', function(){
                editor.setUnsavedState();
                if($(this).prop('checked')) $bottomNavigation.show();
                else $bottomNavigation.hide();
            });


            //editor.$iBeaconCheckbox.bind('click', function(){
            //    editor.setUnsavedState();
            //
            //    if(this.checked) $ibeaconSettings.show();
            //    else $ibeaconSettings.hide();
            //});

            //$commonWidgets.append($ibeaconSettings).append($bottomNavigation);
            $commonWidgets.append($bottomNavigation);
            $commonWidgets.find('.collapse-button').unbind('click').bind('click', collapseBlock);

        };

        /* COMMON Editor Methods */

        /* This method inits empty (new) object. */
        editor.afterInit = function() {
            editor.loadType();
            if(typeof editor.object_id === 'undefined' || editor.object_id == null) {
                editor.$addPage.trigger('click');
            }
            editor.stateChange(true);
        };

        editor.itemConstructor = { widgets : Widget };

        /* This method inits all required elements for editor. */
        editor.specialPreInit = function() {
            editor.$pagesTabs = $('#pages-tabs-container');
            editor.$addPage = $('#add-page-button').bind('click', editor.addPage);
            editor.$contextTypeLabel = $("#context-type-label");
            editor.$startPageSelect = $("#start-page-select").change(editor.setUnsavedState);

            /* page options */
            editor.$pageNameInput = $("#page-name-input").keyup(editor.pageChanged);
            editor.$hideInNavigationCheckbox = $("#hide-in-navigation-checkbox").bind('click', editor.pageChanged);
            editor.$goToContextType = $('#go_to_context_type');

            var $contextTypeSelect = editor.$contextTypeSelector = $('#context_type');
            //$contextTypeSelect.empty();
            //var $opt = $('<option>');
            //
            //$contextTypeSelect.append($opt.clone().val("").text('No'));
            //$.each(editor.commonContext.types, function(id, type) {
            //    $contextTypeSelect.append($opt.clone().val(id).text(type.display_name));
            //});
            //
            //$contextTypeSelect.get(0).selectedIndex = 1;
            //$contextTypeSelect.bind('change', editor.pageChanged);

            editor.$iBeaconCheckbox = $('#ibeacon_enable');//.bind('click', editor.setUnsavedState);
            editor.$bottomNavigationCheckbox = $('#bottom_navigation_menu');//.bind('click', editor.setUnsavedState);

            var $downloadSingleApp = $('#download-single-app-button');
            var $displayModeSwitcher = $('#switch_display_mode');//.attr('disabled', true);
            var $downloadButton = $("#download-button");
            var versionId = editor.object_id;

            if(typeof versionId === 'undefined' || versionId == null || versionId == -1) {
                $downloadSingleApp.removeClass('blue').prop('disabled', true);
            }

            var sendDataToPreviewIframe = function () {
                var source = flexiweb.site_url + '/mobile_app/generate.php';
                var appData = editor.collect();
                appData.is_local_using = true;

                delete appData.isPagesEditor;
                delete appData.display_name;
                delete appData.object_type_id;

                var id = editor.object_id;

                appData.parent_app_id = id;
                appData.object_id = id.split('.')[1];

                var $form = $('<form action="' + source + '" target="preview-frame" method="post" style="display:none;"></form>');

                appData.code = JSON.stringify(appData.code);

                (function (data) {
                    var createInputs = function (name, value) {
                        return $("<input type='hidden' />").attr("name", name).attr("value", value);
                    };

                    $.each(data, function (k, val) {
                        $form.append(createInputs(k, val));
                    });
                })(appData);

                $('body').append($form);
                $form.submit().remove();
            };


            $downloadButton.bind('click', function () {
                window.open(window.flexiweb.site_url + 'mobile_app/get_build_zip.php');
            });
            $downloadSingleApp.bind('click', function () {
                window.open(window.flexiweb.site_url + 'mobile_app/create_single_app.php?id=' + editor.object_id);
            });
            $displayModeSwitcher.unbind('click').bind('click', function () {
                var state = editor.saved;
                var isDesignState = $displayModeSwitcher.attr('data-is-design-state') == 'true';

                if (isDesignState) {
                    $displayModeSwitcher.val('Switch to Preview mode');
                    $('#preview_metabox').hide();
                    $('#pages_editor_metabox').show();
                }
                else {
                    $displayModeSwitcher.val('Switch to Design mode');
                    $('#pages_editor_metabox').hide();
                    $('#preview_metabox').show();

                    if (editor.object_id)
                        sendDataToPreviewIframe(editor.object_id);

                }

                if(state)
                    editor.stateChange(true);

                $displayModeSwitcher.attr('data-is-design-state', !isDesignState);

            }).trigger('click');


            editor.commonWidgetsInit();

        };

        /* This method render application object. */
        editor.specialRenderData = function(data) {
            var code = data.code;
            var bottom = !!code.bottom_navigation && code.bottom_navigation != "false";
            var start = code.start_page || code.startPage;

            editor.$startPageSelect.val(start);

            if(bottom != editor.$bottomNavigationCheckbox.prop('checked'))
                editor.$bottomNavigationCheckbox.trigger('click');

            if(bottom) {
                $.each(code.bottom_navigation, function(itemNumber, object){
                    if(object !== null && typeof object === 'object') {
                        var $menuItem = editor.$bottomNavigationSettings.find('.navigation_item[data-page="'+ itemNumber +'"]');
                        $menuItem.find('select.linked_page').val(object.page_id);
                        if(object.image)
                            $menuItem.find('.icon_select').ImageSelector('setImage', object.image, object.image_path);
                    }
                })
            }

            if(code.startPage)
                editor.goToPage(start);

            if(typeof code.ibeacon !== 'undefined' && code.ibeacon != null && code.ibeacon != false) {
                editor.$iBeaconCheckbox.trigger('click')
            }

            editor.stateChange(true);
            editor.updatePages();

        };

        /* This method collect application data from editor. */
        editor.specialCollectData = function(data) {
            delete data.object_type;

            var navigationMenu = false;

            if(editor.$bottomNavigationCheckbox.prop('checked')) {
                navigationMenu = {};
                $.each(editor.$bottomNavigationSettings.find('.navigation_item'), function(){
                    var $this = $(this),
                        currentItemNumber = $this.attr('data-page'),
                        $imageSelector = $this.find('.icon_select');

                    if(!navigationMenu[currentItemNumber]) navigationMenu[currentItemNumber] = {};



                    var image = $imageSelector.ImageSelector('getURL');
                    var image_path = $imageSelector.ImageSelector('getPath');

                    if(!image || (image == ''))
                        image = window.flexiweb.site_url + 'images/mime_image.png';


                    if(!image_path || (image_path == ''))
                        image_path = 'mime_image.png';


                    navigationMenu[currentItemNumber] = {
                        page_id: $this.find('select').val(),
                        image: image,
                        image_path: image_path
                    };
                });
            }

            $.each(editor.pages, function(_, page) {
                page.elements = [];
            });


            $.each(data.code, function(_, data) {
                editor.pages[data.page].elements.push(data);
            });

            var data =  {
                code: {
                    pages: editor.pages,
                    bottom_navigation: navigationMenu,
                    start_page: editor.$startPageSelect.val(),
                    ibeacon: editor.$iBeaconCheckbox.prop('checked') ? $('#ibeacon_uuid').val() : undefined
                },
                isPagesEditor: true,
                parent_app_id: $("#application-version-select").val()
            };

            return data;
        };

        /* Methods for working with pages.  */

        /* This method returns all pages [array with ids as values]. */
        editor.getPages = function() {
            var pages = $.makeArray(editor.$pagesTabs.find('li').map(function(){
                return parseInt(this.id);
            }));
            pages.shift();
            return pages;
        };

        /* This method contains actions by page changed. */
        editor.pageChanged = function() {
            var name = editor.$pageNameInput.val();
            editor.pages[editor.active_page] = {
                id: editor.active_page,
                //context_type: editor.$contextTypeSelector.val(),
                hidden: editor.$hideInNavigationCheckbox.prop('checked') ? 1: 0,
                name: name
            };

            editor.$pagesTabs.children('[id="' + editor.active_page  +'"]').find('span').text(name);
            editor.updatePages();
            editor.setUnsavedState();
        };

        /* This method change data (ids) on page remove. */
        editor.recalculatePages = function() {
            var pages = editor.getPages();
            var newPages = {};

            $.each(pages, function(newId, oldId) {
                newId = newId + 1;
                var page = editor.pages[oldId];
                page.id = newId;
                newPages[newId] = page;

                editor.$pagesTabs.find('li[id="'+ oldId +'"]').attr('id', newId);
                $.each(editor.containers, function() {
                    this.$div.find('div.widget[data-page="' + oldId + '"]').attr('data-page', newId );
                });
            });
            editor.pages = newPages;
            editor.total_pages = pages.length;
        };

        /* This method turn editor to selected page. */
        editor.goToPage = function(id) {
            editor.active_page = id;
            var $items = editor.$pagesTabs.find('li').removeClass('active');
            $items.filter('[id="' + id + '"]').addClass('active');

            $.each(editor.containers, function() {
                var $items = this.$div.find('div.widget').hide();
                $items.filter('[data-page="' + editor.active_page+ '"]').show();
            });

            var page = editor.pages[id];

            if(!page) return
            if(!page.hidden) page.hidden = false;


            editor.$hideInNavigationCheckbox.prop('checked', modifyValue(page.hidden));
            editor.$pageNameInput.val(page.name);
            //editor.$contextTypeSelector.val(page.context_type);
            editor.$goToContextType.attr('href', flexiweb.site_url + "design_editor/design_types?object_type_id=" + page.context_type);


        };

        /* This method creates new page. If @data will not init, then method creates default new page. */
        editor.addPage = function(data) {
            var id = editor.total_pages = editor.total_pages + 1;
            var options = {
                id: id,
                name: 'New_' + id,
                hidden: 0,
                context_type: 0,
                elements: []
            };

            if(typeof data !== 'undefined' && data != null)
                $.extend(options, data);

            var name = options.name;

            var $page = ich['page_tab']( { name: name,  id: id } );

            $page.bind('click', function() {
                editor.goToPage(this.id);
            });

            $page.find('.delete_page').bind('click', function() {
                editor.removePage($page.attr('id'));
            });

            editor.pages[id] = options;

            editor.$pagesTabs.append($page);
            $page.trigger('click');
            editor.pageChanged();
        };

        /* This method removes selected page. */
        editor.removePage = function(id) {
            if(editor.total_pages == 1)
                return;

            /* remove all pages' items */
            $.each(editor.containers, function(_, container) {
                container.$div.find('div.widget').filter('div[data-page="' + id+ '"]').remove();
            });

            // remove tab
            editor.$pagesTabs.find('li[id="' + id + '"]').remove();

            delete editor.pages[id];



            editor.recalculatePages();

            /* need to decide which page should be active */

            var activePage = editor.active_page;

            if(activePage > editor.total_pages)
                editor.active_page = activePage = activePage - 1;

            editor.goToPage(activePage);
            editor.pageChanged();
        };

        /* This method removes all pages. */
        editor.removePages = function() {
            editor.pages = {};
            editor.total_pages = 0;
            editor.$pagesTabs.children().not(":first-child").remove();
        };

        /* This method update all elements which depend on page data. */
        editor.updatePages = function() {
            var $selects = editor.$startPageSelect;
            $selects = $selects.add(editor.$commonWidgets.children().find('select.linked_page'));

            $.each(editor.containers, function(_, container) {
                $.each(container.$elements, function(_, element) {
                    $selects = $selects.add($(element).find('select[name="link"]'));
                })
            });

            $.each($selects, function() {
                var $select = $(this);
                var value = $select.val();
                var check = false;
                $select.empty();


                if($select.attr('id') != 'start-page-select')
                    $select.append($("<option>").val("").text('No'));

                $.each(editor.pages, function(id, page){
                    $select.append($("<option value='"+ id +"'>").text(page.name));
                    if(id == value)
                        check = true;
                });

                if(check)
                    $select.val(value);
                else
                    $select.val($select.find('option').first().val());
            });
        };

        editor.init();
    });
};