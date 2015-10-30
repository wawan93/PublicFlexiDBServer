function widgets() {
    var widgetTemplate =  {
        /* Empty widget*/
        Empty: function() {
            
            this.render = function(){
                return $();
            };
            this.fill = function(contextObject, callback) {
                callback();
            }
        },

        /**
         * HTML Widget. This widget allows the user to display any HTML code.
         */
        HTMLBlock : function() {
            this.render = function(){
                return $("<div/>").addClass('fx_html_block')
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            this.fill = function(contextObject, callback){
                var object = $.extend({ uploads_url: FXAPI.UPLOADS_URL }, contextObject);
                var data = contextObject ? fillTemplate(this.content, object) : this.content;
                this.$contentDiv.empty().html(decodeURI(data));
                callback();
            }
        },

        /**
         * Data Set Select. This widget allows the user to choose a data set.
         */
        DataSetSelect : function() {
            this.render = function(){
                var currentWidget = this;
                var currentApplication = currentWidget.application;
                var $widgetContent = ich["setSelect"]();
                var $select = $widgetContent.filter('select');
                var $quitFromAppButton = $widgetContent.filter('.backToGenericApp');
                var $changeSetButton = $widgetContent.filter('.select_set');

                $changeSetButton.unbind('click').bind('click', function() {
                    $changeSetButton.attr('disabled', true);
                    DFXAPI.activeDataSet = $select.find(':selected').val();

                    //var startPageId = currentApplication.startPage.id || currentApplication.startPage;
                    //console.log(currentApplication)

                    var startPageId = currentApplication.startPage.id;

                    currentApplication.navigateToPageById(startPageId, undefined, false);
                    $('.fx_top_navigation, .fx_bottom_navigation').show();
                    $changeSetButton.attr('disabled', false);
                });

                $quitFromAppButton.bind('click', function(){
                    var genericApp = currentApplication.genericApp;
                    currentApplication.$div.remove();
                    genericApp.$genericApp.show();
                    genericApp.$genericApp.find('#home_button').trigger('click');
                    genericApp.recalculateSizes();
                });


                ////console.log('CONTENT: ', currentWidget.$contentDiv.html())
                currentWidget.$contentDiv.append($widgetContent);

                return $('<div/>').addClass('fx_dataset_select inset')
                    .append(currentWidget.$header)
                    .append(currentWidget.$contentDiv);
            };
            this.fill = function(contextObject, callback){
                var currentWidget = this;
                var genericApp = currentWidget.application.genericApp;
                var $widgetContent = currentWidget.$contentDiv;
                var $select = $widgetContent.find('select');

                $select.empty();

                if(genericApp.isSpecificApp)
                    $widgetContent.find('.backToGenericApp').remove();

                DFXAPI.getUserSets(function(sets) {
                    DFXAPI.dataSets = sets;

                    var len = 0;
                    for ( var i in sets ) { len++; }

                    $.each(sets, function(setId, object) {
                        $select.append($('<option>').val(setId).text(object['display_name']))
                    });
                    $widgetContent.find('.select_set').prop('disabled', len < 1);

                    callback();

                    if(!DFXAPI.activeDataSet && len == 1 ) {
                        $widgetContent.find('.select_set').trigger('click');
                    }

                }, function(errors) {
                    currentWidget.printErrors(errors);
                    callback()
                });
            };
        },

        /**
         * Query List. This widget displays queries.
         * Query results are available for the user.
         *
         *      CURRENT_WIDGET = {
         *          tableStyle = 'borderedRows' | 'borderedCells' | 'borderedColumns',
         *          tableStripes = 'horizontal' | 'vertical' | 'both',
         *          itemsPerPage = (int),
         *          display_type = 'standard' | 'HTML',
         *          standardFields = (array),
         *          searchString = (string),
         *          query = (int) | (object)
         *      }
         *
         * Also there are parameters used to control appearance of the widget.
         *
         * The format of the output data can be one of the following:
         * - Custom HTML. It means that the user creates their own format for displaying the output data.
         * - Standard. In this case, the user can check fields of the current query that must be shown in the table.
         *
         * The user can choose:
         *
         * 1. Number of elements per page.
         *
         * 2. Table stripes style:
         * 2.1. No stripes;
         * 2.2. Table with horizontal stripes;
         * 2.3. Table with vertical stripes;
         * 2.4. Table with vertical and horizontal stripes cells;
         *
         * 3. Table border styles:
         * 3.1. No borders;
         * 3.2. Bordered rows;
         * 3.3. Bordered columns;
         * 3.4. Bordered cells;
         *
         * 4. Filter by Context object - this is ....
         *
         * 5. Linked page is a page, which will be shown when the user clicks one of the query items.
         * Also the current query item will be sent as contextObject to
         *
         */
        QueryList : function() {
            this.render = function(){
                return $("<div>").addClass('fx_querylist')
                    .append(this.$header)
                    .append(this.$contentDiv)
            };

            /**
             * @param {Object} contextObject Object data
             * @param {string} searchString String by which the search will be performed.
             */
            this.initNavigation = function(contextObject) {
                var currentWidget = this;
                var genericApp = currentWidget.application.genericApp;
                var $navigationBlock = currentWidget.$contentDiv.find('.fx_querylist_navigation');

                currentWidget.currentPage = currentWidget.currentPage || 1;

                $navigationBlock.find('.current_page_number').text(currentWidget.currentPage);
                $navigationBlock.find('.total_page_number').text(currentWidget.pagesCount);
                $navigationBlock.find('.navigate_button').unbind('click').bind('click', function(){
                    var $this = $(this);

                    if($this.hasClass('previous_page') && currentWidget.currentPage > 1) {
                        currentWidget.currentPage--;
                    }
                    else if($this.hasClass('next_page') && currentWidget.currentPage < currentWidget.pagesCount) {
                        currentWidget.currentPage++;
                    }
                    else return;

                    $navigationBlock.find('.current_page_number').text(currentWidget.currentPage);
                    var timeout = genericApp.loaderShow();

                    currentWidget.fillBody(contextObject, function() {
                        genericApp.loaderHide(timeout);
                    });

                });
            };
            this.fillHead = function(contextObject, callback) {
                var currentWidget = this;

                if (currentWidget.display_type != 'html') {
                    var $tableHeadContent = [];

                    var $thTemplate = $('<th></th>').attr('data-order','asc');

                    if (currentWidget.thumbnail_field) {
                        var thumb = currentWidget.thumbnail_field;
                        $tableHeadContent.push($thTemplate.clone().attr('data-field', thumb).text(thumb.replace('_', ' ')));
                    }

                    if(currentWidget.display_type == 'html') {
                        $.each(currentWidget.standard_fields, function (k, alias) {
                            var field = currentWidget.fields[alias];
                            var $th = $thTemplate.clone().attr('data-field', alias).text(field.caption);

                            $th.unbind('click').bind('click', function() {
                                var order = this.dataset.order = this.dataset.order == 'asc'? 'desc' : 'asc';
                                currentWidget.sort = { sort_key: alias, order: order };
                                currentWidget.searchString = currentWidget.$contentDiv.find('.search_input').val();
                                currentWidget.$contentDiv.find('.fx_querylist_navigation').find('.current_page_number').text(1);
                                currentWidget.fillBody(contextObject);
                            });

                            $tableHeadContent.push($th);

                            //$.each(currentWidget.fields, function (index, field) {
                            //    if ((field.name == fieldName) || (field.alias == fieldName)) {
                                //}
                            //});
                        });
                        currentWidget.$contentDiv.find('.fx_querylist_head').append($tableHeadContent);
                    }
                }

                callback();
            };
            this.fillBody = function(contextObject, callback) {
                var currentWidget = this;
                var genericApp = currentWidget.application.genericApp;

                var filterByContextObject = !!contextObject && modifyValue(currentWidget.filter_by_object);
                var linkedObjectTypeId = filterByContextObject ? contextObject.object_type_id : undefined;
                var linkedObjectId = filterByContextObject ? contextObject.object_id : undefined;

                var itemsPerPage = parseInt(currentWidget.items_per_page) || 10;
                var searchString = (!currentWidget.searchString) ? '' : currentWidget.searchString;
                var searchSTR = (searchString == "") ? undefined : searchString;
                var sortData = currentWidget.sort;
                var isUpdated = {};
                var updateRate = currentWidget.update_rate || 3;
                var offset = (currentWidget.currentPage - 1) * itemsPerPage;
                if(!offset || offset < 1)
                    offset = 0;

                if(!callback) callback = $.noop;

                var sortData = currentWidget.sort;

                var args = {
                    query: currentWidget.object_id,
                    linked_object_type_id: linkedObjectTypeId,
                    linked_object_id: linkedObjectId,
                    filter_by: searchSTR,
                    limit: currentWidget.isBeacon? undefined : itemsPerPage,
                    offset: currentWidget.isBeacon? undefined : offset
                };


                if(typeof sortData !== 'undefined' && sortData != null)
                    $.extend(args, sortData);


                DFXAPI.getQueryCount(args, function(itemsCount) {
                    currentWidget.pagesCount = Math.ceil(itemsCount / itemsPerPage) || 1;
                    currentWidget.initNavigation(contextObject);
                    DFXAPI.getQueryResult(args, function(queryResponse) {
                        var fillContent = function(data, beacons) {
                            var queryItems = [];
                            var content = decodeURI(currentWidget.content);

                            $.each(data, function(currentObjectId, object) {
                                var $currentDiv = $('<tr></tr>').attr('data-object-id', currentObjectId);//.hide();//.append($queryItems);
                                var $thumb;

                                if(currentWidget.isBeacon && currentWidget.application.genericApp.isMobile.any())
                                    $currentDiv.hide();

                                if(currentWidget.display_type == 'html') {
                                    $currentDiv.append('<td>' + fillTemplate(content, object) + '</td>');
                                }
                                else {
                                    var thumb = currentWidget.thumbnail_field;

                                    if(thumb !== 'undefined' && thumb != null && thumb != 0) {
                                        //var val = object[currentWidget.thumbnail_field];
                                        //if(val !== 'undefined' && val != null && val != '')
                                        //if(val)
                                        if(object[currentWidget.thumbnail_field])
                                            $thumb = $('<img>')
                                                .addClass('thumbnail_with_border_in_query')
                                                .attr('src', DFXAPI.UPLOADS_URL + currentWidget.main_type + '/' + currentObjectId + '/thumb_' + object[currentWidget['thumbnail_field']]);

                                        $currentDiv.append($('<td></td>').append($thumb));
                                    }

                                    if(currentWidget.query_enum) {
                                        var field = currentWidget.fields[currentWidget.query_enum];
                                        if(typeof field === 'undefined' || field == null) return;

                                        var enumFields = field.enum_fields;
                                        if(typeof enumFields === 'undefined' || enumFields == null) return;

                                        var currentEnumField = enumFields[object[currentWidget.query_enum]];

                                        $.each(enumFields, function(_, data) {
                                            if(data.label == object[currentWidget.query_enum]) {
                                                currentEnumField = data;
                                                return;
                                            }
                                        });

                                        if(typeof currentEnumField === 'undefined' || currentEnumField == null) return;

                                        var color = currentEnumField.color;

                                        if(isHex(color)) {
                                            var components = hexToRgb(color);
                                            color = 'rgba(' + components.r + ',' + components.g + ',' + components.b + ',' + currentEnumField.opacity + ')';
                                        }

                                        if(!$currentDiv.hasClass('with_colored_enum'))
                                            $currentDiv.addClass('with_colored_enum');

                                        $currentDiv.css('background-color', color );
                                    }


                                    if(!!currentWidget.standard_fields){
                                        $.each(currentWidget.standard_fields, function(k, alias) {
                                            //var field = currentWidget.fields[alias];
                                            //var alias = field.alias;
                                            //var fieldVal = (alias != '') ? object[alias] : object[alias];
                                            var fieldVal = object[alias];
                                            var $td = $('<td>').addClass('fx_querylist_item ' + alias).text(fieldVal);

                                            if(alias == currentWidget.beacon_field) {
                                                var beaconId = currentWidget.uuid;
                                                var major, minor, name;

                                                if(typeof beaconId === 'undefined' || beaconId == null)
                                                    return;

                                                if(typeof fieldVal === 'undefined' || fieldVal == null)
                                                    return;

                                                $.each(beacons, function(i, beacon) {
                                                    if(beacon.display_name == fieldVal) {
                                                        major = beacon.major;
                                                        minor = beacon.minor;
                                                        fieldVal = major + '.' + minor;
                                                        name = beaconId + '.' + fieldVal;
                                                        return;
                                                    }
                                                });

                                                if(typeof name === 'undefined' || name == null)
                                                    return;

                                                $td.addClass('beacon').attr('data-id', fieldVal).text('');

                                                if(typeof cordova !== 'undefined' && typeof cordova.plugins.locationManager !== 'undefined' && cordova != null) {
                                                    var delegate = new cordova.plugins.locationManager.Delegate();
                                                    delegate.didDetermineStateForRegion = function (pluginResult) {};
                                                    delegate.didStartMonitoringForRegion = function (pluginResult) {};
                                                    delegate.didRangeBeaconsInRegion = function (pluginResult) {
                                                        if(pluginResult.beacons.length > 0) {
                                                            var isChanged = false;

                                                            $.each(pluginResult.beacons, function(i, currentBeacon) {
                                                                var major = currentBeacon.major;
                                                                var minor = currentBeacon.minor;

                                                                if(!isUpdated.hasOwnProperty(major))
                                                                    isUpdated[major] = {};

                                                                if(!isUpdated[major].hasOwnProperty(minor))
                                                                    isUpdated[major][minor] = false;

                                                                var actualState = isUpdated[major][minor];

                                                                if(!actualState) {
                                                                    actualState = true;
                                                                    isChanged = true;
                                                                    setTimeout(function(){
                                                                        var val, text;
                                                                        var id = major + '.' + minor;

                                                                        switch(currentBeacon.proximity) {
                                                                            case 'ProximityImmediate': text = 'Immediate'; val = 3; break;
                                                                            case 'ProximityNear': text = 'Near'; val = 2; break;
                                                                            case 'ProximityFar': text = 'Far'; val = 1; break;
                                                                            default: text = ''; val = 0;
                                                                        }
                                                                        ////console.log('DETECTED: ' + id + ', PROXIMITY: ' + text, ' :: ', name);

                                                                        var $td = currentWidget.$contentDiv.find('[data-id="'+ id +'"]').html(text);
                                                                        var $tr = $td.parent().attr('data-sort', val);

                                                                        if(val == 0) $tr.hide();
                                                                        else $tr.show();
                                                                        actualState = false;
                                                                    }, updateRate);
                                                                }
                                                            });

                                                            if(isChanged) sort();
                                                        }
                                                    };

                                                    cordova.plugins.locationManager.setDelegate(delegate);
                                                    // required in iOS 8+
                                                    cordova.plugins.locationManager.requestWhenInUseAuthorization();
                                                    // or cordova.plugins.locationManager.requestAlwaysAuthorization()

                                                    var beaconRegion = new cordova.plugins.locationManager.BeaconRegion(name, beaconId, major, minor);
                                                    cordova.plugins.locationManager.startMonitoringForRegion(beaconRegion).fail(console.error).done();
                                                    cordova.plugins.locationManager.startRangingBeaconsInRegion(beaconRegion).fail(console.error).done();
                                                }
                                            }

                                            $currentDiv.append($td);
                                        });
                                    }
                                }

                                if(!!currentWidget.link) {
                                    $currentDiv.addClass('link');
                                    $currentDiv.unbind('click').bind('click', function(){
                                        object.object_id = currentObjectId.split('-')[0];
                                        object.object_type = currentWidget.main_type;
                                        currentWidget.application.navigateToPageById(currentWidget.link, object, false);
                                    });
                                }

                                queryItems.push($currentDiv);
                            });
                            currentWidget.$contentDiv.find('.fx_querylist_content').empty().append(queryItems);
                        };

                        if(currentWidget.isBeacon) {
                            var magicFunction = function(clb) {
                                var beacon = currentWidget.beacon;
                                if(typeof beacon !== 'undefined' && beacon != null && beacon != 0) {
                                    DFXAPI.getBeacons(beacon, function(beacons) {
                                        currentWidget.uuid = beacons[0].uuid;
                                        clb(beacons);
                                    }, function(errors) {
                                        currentWidget.printErrors(errors);
                                        clb();
                                    });
                                }
                                else clb();
                            };

                            magicFunction(function(beacons) {
                                var $beaconHead = currentWidget.$contentDiv.find('th[data-field="' + currentWidget.beacon_field  + '"]');
                                $beaconHead.unbind('click').bind('click', function() {
                                    this.dataset.order = (this.dataset.order == 'asc'? 'desc' : 'asc');
                                    sort();
                                });
                                var sort = function() {
                                    var $wrapper = currentWidget.$contentDiv.find('.fx_querylist_content');
                                    var order = $beaconHead.attr('data-order');

                                    if(order == 'asc') {
                                        $wrapper.find('tr').sort(function (a, b) {
                                            return +a.dataset.sort - +b.dataset.sort;
                                        }).appendTo( $wrapper );
                                    }

                                    else {
                                        $wrapper.find('tr').sort(function (a, b) {
                                            return +b.dataset.sort - +a.dataset.sort;
                                        }).appendTo( $wrapper );
                                    }
                                };
                                currentWidget.$contentDiv.find('.fx_querylist_navigation, .fx_querylist_search').remove();
                                fillContent(queryResponse, beacons);
                                callback();
                                //genericApp.loaderHide();
                            });
                        }
                        else {
                            fillContent(queryResponse);
                            callback();
                        }
                    }, function(errors) {
                        currentWidget.printErrors(errors);
                        callback();
                    });
                }, function(errors) {
                    currentWidget.printErrors(errors);
                    callback();
                })

            };

            /**
             * @param {int} queryListId Query object identifier
             * @param {int} currentPageNumber Requested page number
             * @param {string} tableClasses List of table classes that are used for styling
             */
            this.fill = function(contextObject, callback){
                var currentWidget = this;
                var genericApp = currentWidget.application.genericApp;

                // workout for old apps
                if(typeof currentWidget.display_type === 'undefined' || currentWidget.display_type == null) {
                    currentWidget.display_type = currentWidget.displayType;
                }

                if(typeof currentWidget.standard_fields === 'undefined' || currentWidget.standard_fields == null) {
                    currentWidget.standard_fields = currentWidget.standardFields;
                }

                if(typeof currentWidget.inset_style === 'undefined' || currentWidget.inset_style == null) {
                    currentWidget.inset_style = currentWidget.insetStyle;
                }

                if(typeof currentWidget.items_per_page === 'undefined' || currentWidget.items_per_page == null) {
                    currentWidget.items_per_page = currentWidget.itemsPerPage;
                }

                var inset = currentWidget.inset_style;

                DFXAPI.getWidget(currentWidget.query, 'query', function(queryObject) {
                    var namedFields = {};
                    for(var i in queryObject.fields) {
                        var item = queryObject.fields[i];
                        namedFields[item.alias] = item;
                    }

                    var tableClasses = "";
                    $.extend(currentWidget, queryObject);
                    currentWidget.fields = namedFields;

                    if(inset)  tableClasses = "bordered ";
                    switch (modifyValue(currentWidget.table_style)) {
                        case 'borderedRows': tableClasses += 'bordered_rows'; break; //+
                        case 'borderedCells': tableClasses += 'bordered_cells'; break; //+
                        case 'borderedColumns': tableClasses += 'bordered_columns'; break; // -
                    }
                    switch (modifyValue(currentWidget.table_stripes)) {
                        case 'horizontal': tableClasses += ' stripes_horizontal'; break;
                        case 'vertical': tableClasses += ' stripes_vertical'; break;
                        case 'both': tableClasses += ' stripes_horizontal stripes_vertical'; break;
                    }

                    var $queryList = ich['queryListWidget']();

                    var $searchBox = $queryList.filter('.fx_querylist_search');
                    $searchBox.find('.start_search').unbind('click').bind('click', function() {
                        var timeout = genericApp.loaderShow();
                        currentWidget.searchString = $searchBox.find('.search_input').val();
                        currentWidget.currentPage = 1;
                        currentWidget.fillBody(contextObject, function() {
                            genericApp.loaderHide(timeout);
                        });
                    });

                    $queryList.find('.fx_querylist_content').addClass(tableClasses);
                    currentWidget.$contentDiv.empty().append($queryList);

                    //console.log('before fillHead clb')

                    currentWidget.fillHead(contextObject, function() {
                        //console.log('fillHead clb');
                        currentWidget.fillBody(contextObject, callback);
                    });
                }, function(errors) {
                    currentWidget.printErrors(errors);
                    //currentWidget.$contentDiv.empty().append($('<p></p>').text('Connection timed out.'));
                    callback();
                });
            };
        },

        /**
         * iBeaconQuery.
         */
        iBeaconQuery : function() {
            this.isBeacon = true;
            
            widgetTemplate.QueryList.call(this);
        },

        /**
         * Logout Button. This widget creates logout button. ???
         */
        //LogoutButton : function() {
        //    this.render = function(){
        //        var $logoutButton = $('<div></div>').addClass('fx_logout').text('Logout');
        //        var app = this.application;
        //        $logoutButton.click(function(){
        //            DFXAPI.clearUser();
        //            app.navigateToPageById('login', undefined, false);
        //        });
        //        return $logoutButton;
        //    };
        //    this.fill = function(contextObject, callback) {
        //        callback();
        //    }
        //},

        /**
         * Gallery. This widget creates carousel with images from selected query.
         */
        Gallery : function() {
            this.render = function(){
                return $('<div/>').addClass('fx_gallery')
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            this.fill = function(contextObject, callback){
                var currentWidget = this,
                    imageField = currentWidget.image_field,
                    imageStyle = currentWidget.image_style,
                    titleField = currentWidget.title_field,
                    queryId = currentWidget.query;

                currentWidget.$contentDiv.empty();

                DFXAPI.getQueryResult({ query: queryId }, function(queryResult) {
                    if(!queryResult) { callback(); return }
                    var $images = $('<div/>').addClass('images swipe-wrap'),
                        bgSize = (imageStyle == 'height')? 'auto 100%' : '100% auto',
                        height = '20em';

                    $.each(queryResult, function(index, object){
                        var imageName = object[imageField];
                        if(!!imageName && imageName != '') {
                            var nameParts = imageName.split('-'),
                                objectTypeId = nameParts[0],
                                objectId = nameParts[1],
                                imgUrl = DFXAPI.UPLOADS_URL + objectTypeId + '/' + objectId + '/'+ imageName,

                                $image = $('<div/>').css({
                                    'background-image': 'url(' + imgUrl + ')',
                                    'background-repeat': 'no-repeat',
                                    'background-size': bgSize,
                                    'background-position': 'center',
                                    'height' : height
                                });

                            $image.append($('<label/>').addClass('galleryItemTitle').text(object[titleField]));
                            $images.append($image);
                        }

                    });

                    var $outerSwipeDiv = $('<div/>').addClass('swipe').append($images);
                    currentWidget.$contentDiv.append($outerSwipeDiv);

                    window['gallerySwipe'] = Swipe($outerSwipeDiv.get(0),{
                        startSlide: 0,
                        speed: 300,
                        continuous: true,
                        disableScroll: false,
                        stopPropagation: false,
                        callback: function(index, elem) {},
                        transitionEnd: function(index, elem) {}
                    });
                    callback();
                }, function(errors) {
                    currentWidget.printErrors(errors);
                    callback();
                });
            };
        },

        /**
         * Data Form. This widget allows the user to fill and read data to/from forms.
         *
         * The user can create an object, create a linked object and update an object.
         *
         * There are several types of fields for data input:
         * 1. Text field;
         * 2. File;
         * 3. Image;
         * 4. Date, time picker;
         * 5. Dropdown;
         * 6. Calendar;
         * 7. QR Code;
         *
         * Also the user can create, change and remove links to object(-s).
         */
        DataForm: function() {
            var widget = this;

            this.render = function(){
                var $dataForm = $('<form>').addClass('fx_dataform').attr('id', this.id);

                this.$contentDiv = $('<div></div>').addClass('fx_widget_content fx_dataform_content');

                $dataForm.bind('submit', function(event){
                    event.preventDefault();
                });
                return $dataForm
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            /**
             * @param {Object} item contains data about current field;
             */
            this.createInput = function( item ){
                var controlType = item['control-type'];
                var TIMEPICKER_FORMAT = this.application.data.time_format; //'H:i';
                var DATEPICKER_FORMAT = this.application.data.date_format; //"dd.mm.yy";
                var $input = $('<input>').addClass('fx_dataform_input')
                    .attr('name', item['name'])
                    .attr('data-control-type', controlType);


                if(parseInt(item.readonly) || item.readonly == 'on' )
                    $input.attr('disabled', 'disabled');

                if(item.unit)
                    $input.attr('data-unit', item.unit);

                if(controlType == 'textbox') {
                    return $input.attr('type','text');
                }
                else if(controlType == 'textarea') {
                    return $('<textarea></textarea>').attr('name', item['name'])
                }
                else if(controlType == 'hidden') {
                    return $input.attr('type','hidden');
                }
                else if(controlType == 'spinner') {
                    return $input.attr('type','number');
                }
                else if(controlType == 'fileSelect') {
                    return $input.attr('type','hidden');
                }
                else if(controlType == 'imageSelect') {
                    return $input.attr('type','hidden');
                }
                else if(controlType == 'qrCode') {
                    return $input.attr('type', 'hidden');
                }
                else if(controlType == 'dropdown') {
                    var $select = $("<select></select>").addClass('fx_dataform_input').attr('name', item.name).attr('data-control-type', controlType);
                    if(typeof item.enum !== 'null' && typeof item.enum === 'object') {
                        $.each(item.enum, function(value, label) {
                            $select.append($('<option></option>').val(value).text(label));
                        });
                    }
                    return $select;
                }
                else if(controlType == 'calendar') {
                    var $calendar = $('<div></div>').datepicker({
                        altFormat: DATEPICKER_FORMAT,
                        dateFormat: DATEPICKER_FORMAT,
                        autoclose: true,
                        onSelect: function(dateText) {
                            var date = dateText.split('.');
                            var newValue = new Date(date[2], date[1] - 1, date[0], 0, 0, 0, 0);
                            $input.val(newValue.getTime() / 1000);
                        }
                    });
                    return $('<div></div>').append($input.attr('type','hidden')).append($calendar);
                }
                else if(controlType == 'timepicker') {
                    var $timepicker = $('<input>').addClass('time').timepicker({ timeFormat : TIMEPICKER_FORMAT });
                    $timepicker.bind('change', function(){
                        var time = this.value.split(':');
                        var today = new Date();
                        var dd = today.getDate();
                        var mm = today.getMonth()+1; //January is 0!
                        var yyyy = today.getFullYear();
                        var newValue = new Date(yyyy, mm, dd, time[0], time[1], 0, 0);
                        $input.val( newValue.getTime() / 1000 );
                    });

                    var $container = $('<div></div>')
                        .append($input.attr('type', 'hidden'))
                        .append($timepicker);

                    return $container;
                }
                else if(controlType == 'datepicker' || controlType == 'datetimepicker') {
                    var timepicker = controlType == 'datetimepicker';
                    var $container = $('<div></div>').append($input.attr('type', 'hidden'));
                    var onChangeInputsValue = function() {
                        var newValue;
                        var date = $container.find('input.date').val().split('.');
                        var time;

                        if(timepicker)
                            time = $container.find('input.time').val().split(':');

                        //if(typeof time === 'undefined' || time == null || time == '')
                        if(!time)
                            time = [0,0];

                        newValue = new Date(date[2], date[1] - 1, date[0], time[0], time[1], 0, 0);
                        $input.val(newValue.getTime() / 1000 ); //set new value;
                    };
                    var $dateControls = createDateSelector({ callback:onChangeInputsValue });
                    $container.append($dateControls);
                    if(timepicker) {
                        var $timeControls = $('<input>').addClass('time');
                        $timeControls.timepicker({ timeFormat : TIMEPICKER_FORMAT });
                        $timeControls.bind('change', function(){
                            onChangeInputsValue();
                        });
                        $container.append($timeControls)
                    }
                    return $container;
                }
                else if(controlType == 'radioButtons') {
                    var $label, $control;
                    var $container = $('<div>');

                    if(typeof item.enum !== 'undefined' && item.enum != null) {
                        $.each(item.enum, function(value, name) {
                            var id = item.name + '_' + value.replace(' ', '_').toLowerCase();
                            $label = $('<label></label>').attr('for', id).text(name).css('margin-right', '5px');
                            $control = $('<input type="radio">').attr('id', id).val(value).attr('name', item.name);
                            $container.append($('<span>').append($label).append($control));
                        });

                        $container.find('input').first().prop('checked', true);
                    }

                    return $container;

                }
            };
            /**
             * @param {Object} item contains data about current field;
             */
            this.createFormElement = function( item ){
                var isField = (item.elementType == 'field' || item.palette == 'fields');

                var controlType = item['control-type'];
                var currentWidget = this;

                if(isField) {
                    var $label = $('<label>').text(item.name.replace('_', ' ')).addClass('fx_dataform_label');
                    var $controls = currentWidget.createInput(item);
                    var $option = $('<div></div>').addClass("fx_dataform_option")
                        .append($label)
                        .append($controls).addClass(item['name']).addClass('fx-control-' + controlType);

                    return $option;
                }
                else {
                    var $linkControls = $('<div></div>').attr({
                        'class': 'fx_dataform_link',
                        'data-control-type' : controlType,
                        'data-type-id': item['linked_type_id']
                    });

                    //var $label = $('<label></label>').addClass('fx_link_title').text(item['display_name'].replace('_', ' '));
                    var $label = $('<label></label>').addClass('fx_link_title').text(item['display_name']);
                    return $linkControls.append($label);
                }
            };
            /**
             * @param {Object} contextObject;
             */
            this.setMode = function(contextObject, callback) {
                var currentWidget = this;
                var genericApp = currentWidget.application.genericApp;
                var formMode = currentWidget.mode;
                var thereIsError = false;
                var $controlsDiv = currentWidget.$div.find('.fx_dataform_controls');
                var $submitButton = $('<input>').attr('type', 'button').addClass('fx_button button_rounded fx_button_apply');
                var $inputs = currentWidget.$div.find('input, textarea, select').not('[type="button"]');

                currentWidget.filesList = {};

                if(FXAPI.offlineMode)
                    $submitButton.prop('disabled', true).addClass('gray')

                if($controlsDiv.length == 0)
                    $controlsDiv = $('<div></div>').addClass('fx_dataform_controls');

                $controlsDiv.empty();

                if(modifyValue(currentWidget.table_stripes))
                    currentWidget.$div.find('.fx_widget_content').addClass('stripes_horizontal');

                if(formMode == 'createWithActiveLink' ) {
                    if(!contextObject) thereIsError = true;
                    else if(!contextObject['object_id']) thereIsError = true;
                }

                if(thereIsError){
                    //var $message = $('<div></div>').addClass("fx_dataform_option")
                    //    .append($('<label>').text())
                    //
                    //currentWidget.$contentDiv.empty().append($message);

                    currentWidget.printErrors('Error: No object ID supplied.');
                    callback();
                }
                else {
                    var currentTypeId = currentWidget.object_type;
                    var $linkControls = currentWidget.$div.find('.fx_dataform_link[data-control-type]');

                    var actionAfterLinks = function(type, CO, clb) {
                        var buttonText, buttonClass, inputsFill;

                        if(type == 'edit') {
                            buttonText = 'Update'; buttonClass = 'updateObject'; inputsFill = true;
                        }
                        else {
                            buttonText = 'Add'; buttonClass = 'createNewObject'; inputsFill = false;
                        }

                        $submitButton.addClass(buttonClass).val(buttonText);
                        $submitButton.unbind('click').bind('click', function() {
                            var data = currentWidget.collectDataFromInputs(currentWidget.$div.find('input, textarea, select').not('[type="button"]'));
                            data.object_type = currentTypeId;

                            if(type == 'createWithActiveLink')
                                data._links[CO['object_type']] = CO['object_id'];

                            if(type == 'create' || type == 'createWithActiveLink') {
                                currentWidget.addObject(data, CO);
                            }
                            else if(type == 'edit') {
                                data.object_id = CO.object_id;
                                currentWidget.updateObject(data);
                            }
                        });

                        if(formMode == 'edit')
                            $controlsDiv.append(currentWidget.initDeleteButton(CO));



                        //if(type == 'edit')
                        currentWidget.initInputs($inputs, inputsFill, CO);

                        clb();
                    };
                    var actionOnSuccess = function(links) {
                        currentWidget.initLinks(links, $linkControls);
                        actionAfterLinks(formMode, contextObject, callback)
                    };
                    var actionOnError = function() {
                        $linkControls.remove();
                        actionAfterLinks(formMode, contextObject, callback);
                    };


                    if(formMode == 'create' || formMode == 'createWithActiveLink') {
                        DFXAPI.getPossibleLinks(currentTypeId, actionOnSuccess, actionOnError);
                    }
                    else if(formMode == 'edit' && !!contextObject) {
                        DFXAPI.getAllLinks(contextObject.object_id, currentTypeId, actionOnSuccess, actionOnError);
                    }
                    else {
                        currentWidget.printErrors('Warning: There is no active object.');
                        $controlsDiv = $();

                        genericApp.cordovaAlert('There is no active object.');
                        callback();
                    }

                    $controlsDiv.append($submitButton);
                    $(currentWidget.$div).append($controlsDiv);
                }
            };
            /**
             * @param {Object} contextObject;
             * @param {Function} callback
             */
            this.fill = function(contextObject, callback){
                var currentWidget = this;
                var $widgetContent = currentWidget.$contentDiv;
                // workout for old apps when form_id saved in param 'form'
                var formId = currentWidget.form_id || currentWidget.form;
                var thereIsNoContextObject = true;

                //currentWidget.application.genericApp.loaderShow();
                $widgetContent.empty();

                if(typeof contextObject !== 'undefined' && contextObject != null) {
                    if(typeof contextObject.object_id !== 'undefined' && contextObject.object_id != null)
                        thereIsNoContextObject = false;
                }

                if(!thereIsNoContextObject)
                    contextObject.object_type = contextObject.object_type_id;

                DFXAPI.getWidget(formId, 'form', function(formObject){
                    $.each(formObject.fields, function(_, item) {
                        var $item = currentWidget.createFormElement(item);
                        $widgetContent.append($item);
                    });
                    $.extend(currentWidget, formObject);

                    currentWidget.setMode(contextObject, callback);

                }, function(errors) {
                    currentWidget.printErrors(errors);
                    callback();
                });
            };

            /**
             * @param {Object} $inputs List of fields for data input that should be filled;
             * @param {boolean} neededToFillInputs If selected fields for data input must be filled, this parameter is true;
             * @param {Object} contextObject;
             */
            this.initInputs = function($inputs, neededToFillInputs, contextObject) {
                var currentWidget = this,
                    genericApp = currentWidget.application.genericApp,
                    currentTypeId,
                    currentObjectId;

                if(!contextObject) neededToFillInputs = false;
                else currentObjectId = contextObject['object_id'];

                $.each($inputs, function(i, currentInput) {
                    var $currentInput = $(currentInput);
                    var inputName = $currentInput.attr('name') || '';

                    if(inputName.indexOf("_links") == -1 && inputName) {
                        var controlType = $currentInput.attr('data-control-type');
                        var unit = $currentInput.attr('data-unit') || 0;
                        var inputValue;
                        var $currentBlock = $();
                        $currentInput.val('');

                        if(neededToFillInputs) {
                            inputValue = contextObject[inputName];

                            if((controlType != 'fileSelect') && inputValue)
                                $currentInput.val(inputValue);
                        }
                        else {
                            $.each(currentWidget.fields, function(i, field){
                                if(field.name == inputName) {
                                    var defaultValue = field.default_value;

                                    if(controlType == 'fileSelect') return;
                                    if(!defaultValue) return;

                                    $currentInput.val(defaultValue);
                                }
                            });
                        }

                        if(controlType == 'textbox' && unit != 0) {
                            $.each(currentWidget.fields, function(i, field){
                                if(field.name == inputName) {
                                    $currentBlock = $currentInput.parent();
                                    var $select = $('<select>').attr('name', '_units['+ inputName +']');
                                    var $option = $('<option>');

                                    if(field.is_currency)
                                        $select.attr('data-currency', 1);

                                    if(typeof field.units !== 'undefined')
                                        $.each(field.units, function(id, metric) {
                                            var $opt = $option.clone().val(id).text(metric.name)
                                                .attr('data-decimals', metric.decimals)
                                                .attr('data-factor', metric.factor);
                                            if(id == unit) {
                                                $opt.prop('selected', true);
                                            }

                                            $select.append($opt);
                                        });

                                    $select.focus(function() {
                                        var prevOptions = $select.find('option:selected').data();
                                        var previousRatio = prevOptions.factor;
                                        var previousCurrency = $select.find('option:selected').text();

                                        $select.unbind('change').bind('change', function() {
                                            var $selected = $select.find('option:selected');

                                            if($select.data().currency) {
                                                var newCurrency = $selected.text();
                                                DFXAPI.getExchangeRate($currentInput.val(), previousCurrency, newCurrency, function(resp) {
                                                    $currentInput.val(resp);
                                                    previousCurrency = newCurrency;
                                                }, function(error) {
                                                    //TODO
                                                });
                                            }
                                            else {
                                                var optionParams = $selected.data();
                                                var newValue = Math.floor(parseFloat($currentInput.val()) * previousRatio / optionParams.factor )
                                                    .toFixed(parseInt(optionParams.decimals));

                                                $currentInput.val(newValue);
                                                previousRatio = optionParams.factor;
                                            }

                                        });
                                    });

                                    $select.find('option[value="'+ unit + '"]').prop('selected', true);
                                    $currentBlock.append($select);
                                }
                            });
                        }
                        else if(controlType == 'imageSelect') {

                            $currentBlock = $currentInput.parent();
                            var $image = $('<img>');

                            var $newPhoto = $('<input type="button">').addClass('take_photo fx_button').val("Take New"),
                                $library = $('<input type="button">').addClass('take_photo fx_button').val("Choose Existing");

                            $currentInput.attr('type','hidden');
                            $currentBlock.append($currentInput);
                            $image.addClass('thumbnail_with_border');

                            if(contextObject)
                                currentTypeId = contextObject.object_type;

                            if(!$currentInput.val()) $image.attr('src', DFXAPI.UPLOADS_URL + 'empty_medium.png');
                            else $image.attr('src', DFXAPI.UPLOADS_URL + currentTypeId+ '/' + currentObjectId + '/thumb_'+ inputValue);

                            if(navigator.camera) {
                                var pictureSource = navigator.camera.PictureSourceType;
                                var destinationType = navigator.camera.DestinationType;

                                $newPhoto.click(function(){
                                    navigator.camera.getPicture(function(imagePath){
                                            $image.attr('src', imagePath);
                                            currentWidget.filesList[inputName] = imagePath;

                                        }, function(errorMsg) {
                                            genericApp.cordovaAlert(errorMsg)
                                        },
                                        { quality: 50, correctOrientation: true });
                                });
                                $library.click(function(){
                                    navigator.camera.getPicture(function(imagePath){
                                            $image.attr('src', imagePath);
                                            currentWidget.filesList[inputName] = imagePath;

                                        }, function(errorMsg) {
                                            genericApp.cordovaAlert(errorMsg)
                                        },

                                        {
                                            quality: 50,
                                            destinationType: destinationType.FILE_URI,
                                            sourceType: pictureSource.SAVEDPHOTOALBUM,
                                            //sourceType: source,
                                            correctOrientation: true
                                        }
                                    );

                                });
                            }

                            var $div = $('<div>').append($image).append($newPhoto).append($library);
                            $currentBlock.append($div);

                        }
                        else if(controlType == 'fileSelect') {
                            $currentBlock = $currentInput.parent().empty();

                            if(neededToFillInputs) {
                                if(inputValue) {
                                    var $link = $('<a></a>').text('Download File');

                                    $link.click(function(){
                                        openURL(DFXAPI.UPLOADS_URL + currentTypeId + '/' + currentObjectId + '/' + inputValue);
                                    });
                                    $currentBlock.append($link);
                                }
                            }


                        }
                        else if(controlType == 'qrCode') {
                            $currentBlock = $currentInput.parent().empty();

                            if(neededToFillInputs) {
                                $currentInput.hide();
                                var $qrCode = $('<img>').attr('src', DFXAPI.UPLOADS_URL + currentTypeId+ '/' + currentObjectId + '/' + inputName + '.png');

                                $currentBlock.append($currentInput).append($qrCode);
                            }
                        }
                        else if(controlType == 'datepicker' || controlType == 'datetimepicker'){
                            if(neededToFillInputs) {
                                var formatedDate = new Date(inputValue * 1000);
                                var day = parseInt(formatedDate.toString('dd'));
                                var month = parseInt(formatedDate.toString('MM'));
                                var year = parseInt(formatedDate.toString('yyyy'));

                                $currentBlock = $currentInput.parent();
                                $currentBlock.find('select.day').val(day);
                                $currentBlock.find('select.month').val(month);
                                $currentBlock.find('select.year').val(year).trigger('change');
                                if(controlType == 'datetimepicker') {
                                    var $tpicker = $currentBlock.find('input.time');
                                    $tpicker.timepicker('setTime', formatedDate);
                                    $tpicker.trigger('change');
                                }
                            }
                            else {
                                $currentInput.parent().find('select:first-child').trigger('change');
                            }

                        }
                        else if(controlType == 'timepicker') {
                            var formatedDate = new Date(inputValue * 1000);
                            $currentInput.val(formatedDate.toString('hh:mm'));
                            var $tpicker =  $currentInput.parent().find('input.time');
                            $tpicker.timepicker('setTime', formatedDate);
                            $tpicker.trigger('change');
                        }
                        else if(controlType == 'calendar') {
                            $currentInput.parent().find('div.hasDatepicker').datepicker("setDate", new Date(inputValue*1000) );
                        }
                    }

                });
            };
            /**
             * @param {Object} currentWidget The application object;
             * @param {Object} contextObject;
             */
            this.initDeleteButton = function(contextObject) {
                var currentWidget = this;
                var genericApp = currentWidget.genericApp;
                var $deleteButton = $('<input>').attr('type','button')
                    .addClass('deleteObject fx_button fx_button_danger')
                    .val('Delete');

                $deleteButton.unbind('click').bind('click',function(){
                    if(confirm('Are you sure to delete this object?')) {
                        DFXAPI.removeObject(contextObject, function() {
                            currentWidget.application.genericApp.cordovaAlert('Object removed!');
                            currentWidget.application.goToPreviousPage();
                            contextObject = undefined;
                        }, function(errors){
                            genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                        });
                    }
                    return $deleteButton;
                });

                if(FXAPI.offlineMode)
                    $deleteButton.prop('disabled', true).addClass('gray');

                return $deleteButton;
            };

            /**
             * @param {Object} $inputs List of fields for data input, from which this data should be collected;
             */
            this.collectDataFromInputs = function($inputs) {
                var currentWidget = this,
                    links = {},
                    $serializeDataFromForm  = currentWidget.$div.serializeArray(),
                    object = {};

                $.each($inputs, function(i, currentInput) {
                    var $currentInput = $(currentInput),
                        name = $currentInput.attr('name');

                    if(name) {
                        if(name.indexOf("_links") == -1)
                            object[name] = $currentInput.val();
                    }
                });

                // find links
                $.each($serializeDataFromForm, function(index, object) {
                    var name = object.name;
                    if(name.indexOf("_links") !== -1) {
                        var nameParts = name.split('[');
                        var linkedObjectType = nameParts[1].split(']')[0];
                        if(!links[linkedObjectType]) links[linkedObjectType] = [];

                        if(nameParts.length > 2) {
                            var linkedObject = nameParts[2].split(']')[0];
                            links[linkedObjectType].push(linkedObject) ;
                        }
                        else {
                            links[linkedObjectType].push(object.value) ;

                        }
                    }
                });

                object['_links'] = links;
                return object;
            };
            /**
             * @param {Object} links List of links.
             * @param {Object} $linkInputs Fields for data input on the page which contain data on object's links;
             */

            this.initLinks = function(links, $linkInputs) {
                var currentWidget = this;
                $.each($linkInputs, function(){
                    var $linkCheckbox,
                        $linkLabel,
                        $currentBlock = $(this),
                        currentTypeIdLinks = $currentBlock.attr('data-type-id'),
                        currentControlType = $currentBlock.attr('data-control-type');

                    if(!links[currentTypeIdLinks]) {
                        var $label = $currentBlock.find('.fx_link_title');
                        $currentBlock.empty().append($label).append('<span>No objects to link.</span>');
                    }
                    else {
                        var $info = $('<div></div>').addClass('fx_linking_types');

                        switch(currentControlType) {
                            case 'dropdown': {
                                var $select = $('<select></select>').attr('name','_links[' + currentTypeIdLinks + ']');
                                $select.append($('<option>None</option>').val(''));


                                $.each(links[currentTypeIdLinks], function(linkedTypeId, link) {
                                    var $option = $('<option>' + link['display_name'] + '</option>').val(linkedTypeId);

                                    if(link['actuality'] == 'actual')
                                        $option.attr('selected','selected');
                                    $select.append($option);
                                })
                                $info.append($select);
                                break;
                            }
                            case 'radio': {
                                var checked = false;

                                $.each(links[currentTypeIdLinks], function(linkedTypeId, link) {
                                    $linkLabel = $('<label></label>').text(link['display_name']);
                                    $linkCheckbox = $('<input type="radio">').attr('name','_links[' + currentTypeIdLinks + ']').val(linkedTypeId);

                                    if(link['actuality'] == 'actual') {
                                        $linkCheckbox.prop('checked',true).attr('checked', 'checked');
                                        checked = true;
                                    }
                                    $info = $info.append($linkLabel.append($linkCheckbox));
                                })

                                var $noneButton = $('<label>None</label>').append($('<input type="radio">').attr('name','_links[' + currentTypeIdLinks + ']').val(''));
                                if(!checked) $noneButton.find('input').attr('checked', 'checked').prop('checked',true);
                                $info = $info.prepend($noneButton);
                                break;

                            }
                            case 'checkboxDropdown': {
                                var $multiSelect = $('<select/>')
                                    .attr('name','_links[' + currentTypeIdLinks + ']')
                                    .css('width', '100%');

                                $.each(links[currentTypeIdLinks], function(linkedTypeId, link) {
                                    var $currentOption = $('<option/>').val(linkedTypeId).text(link['display_name']);

                                    if(link['actuality'] == 'actual')  {
                                        $currentOption.attr('selected', 'selected');
                                    }
                                    $multiSelect.append($currentOption);
                                });
                                $info = $info.append($multiSelect);

                                $multiSelect.multipleSelect();
                                $multiSelect.remove();

                                break;
                            }
                            case 'checkboxGroup':
                            default: {
                                $.each(links[currentTypeIdLinks], function(linkedTypeId, link) {
                                    $linkLabel = $('<label></label>').text(link['display_name']);
                                    $linkCheckbox = $('<input type="checkbox">')
                                        .attr('name', '_links[' + currentTypeIdLinks + '][' + linkedTypeId + ']');

                                    if(link['actuality'] == 'actual')  {
                                        $linkCheckbox.prop('checked',true).attr('checked', 'checked');
                                    }
                                    $info = $info.append($linkLabel.append($linkCheckbox));
                                })
                                break;

                            }
                        }

                        var $label = $currentBlock.find('.fx_link_title');
                        $currentBlock.empty().append($label).append($info);
                    }
                });
                //currentWidget.application.genericApp.ifThereIsNoErrors(links, function(){});
            };

            this.uploadImage = function(filesList, type, id, callback) {
                if(!isEmpty(filesList)) {
                    var fileField, fileValue;

                    for (var file in filesList) {
                        fileField = file;
                        fileValue = filesList[file];
                        delete filesList[file];
                        break;
                    }

                    var step = function() {
                        widget.uploadImage(filesList, type, id, callback);
                    };

                    DFXAPI.uploadImage(fileValue, fileField, type, id, step, step);
                }
                else {
                    callback();
                }
            };

            /**
             * @param {Object} contextObject;
             */
            this.updateObject = function(contextObject) {
                var currentWidget = this;
                var linkWithUser = currentWidget.link_with_user;
                var genericApp = currentWidget.application.genericApp;
                var timeout = genericApp.loaderShow();

                DFXAPI.updateObject(contextObject, linkWithUser, function() {
                    widget.uploadImage($.extend({},currentWidget.filesList), contextObject.object_type, contextObject.object_id, function() {
                        genericApp.loaderHide(timeout);
                        currentWidget.application.refreshPage(contextObject);
                        genericApp.cordovaAlert('Object updated!');
                    });
                }, function(errors) {
                    genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                    genericApp.loaderHide(timeout);
                });
            };

            /**
             * @param {Object} object Contains data on an object which will be created;
             * @param {Object} contextObject;
             */
            this.addObject = function(object, contextObject) {
                var currentWidget = this;
                var linkWithUser = currentWidget.link_with_user;
                var genericApp = currentWidget.application.genericApp;
                var timeout = genericApp.loaderShow();

                DFXAPI.addObject(object, linkWithUser, function(id) {
                    widget.uploadImage($.extend({},currentWidget.filesList), object.object_type, id, function() {
                        genericApp.loaderHide(timeout);
                        currentWidget.application.refreshPage(contextObject);
                        genericApp.cordovaAlert('New object created!');
                    });
                }, function(errors) {
                    genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                    genericApp.loaderHide(timeout);
                });
            };
        },

        /**
         * QR Code Scanner. This widget allows the user to scan a QR code and display data from it.
         */
        QRCodeScanner: function() {
            this.render = function(){
                var currentWidget = this,
                    currentApp = currentWidget.application,
                    genericApp = currentApp.genericApp,
                    $contentDiv = this.$contentDiv,
                    $scanButton = $('<input>').attr('type','button').addClass('fx_button scanObject').css('margin', '0.4em').val('Scan'),
                    targetPage = currentWidget.linked_page;

                var actionOnSuccess = function(result) {
                    var scanResult = getURLParams(result.text);

                    if(scanResult.object_type) {
                        currentWidget.application.navigateToPageById(targetPage, scanResult, false);
                    }
                    else if (scanResult.user_id) {
                        var to = genericApp.loaderShow();
                        DFXAPI.getSubscription(scanResult.user_id, function(response){
                            var contextObject = {
                                object_id: response.object_id,
                                object_type: response.object_type_id
                            };

                            if(!!targetPage)
                                currentWidget.application.navigateToPageById(targetPage, contextObject, false);

                            ////console.log('QRCodeScanner :: getSubscriptionRes', response);
                            //currentWidget.application.genericApp.ifThereIsNoErrors(response, function() {});
                            genericApp.loaderHide(to);
                        }, function() {
                            genericApp.loaderHide(to);
                        });
                    }
                    else {
                        var widget = {
                            type: 'HTMLBlock',
                            header_bar: 1,
                            inset_style: 1,
                            title: 'Temporary page',
                            content: result.text
                        };

                        currentWidget.application.createTempPage(widget, function($div) {
                            genericApp.recalculateSizes();
                            currentApp.navigateToPageById('temp', undefined, false);
                            //$('.fx_top_navigation').show();

                            var content = widget.content;
                            if(content.indexOf("http") == 0) {
                                var $span = $('<span></span>').addClass('link_href').text(content).click(function(){ window.open(content, '_system'); });
                                $div.find('.fx_widget_content').empty().append($span)
                            }
                        });
                    }

                };
                var actionOnFail = function(error) {
                    genericApp.cordovaAlert("Scanning failed: " + error);
                };

                $scanButton.unbind('click').bind('click',function() {
                    if(genericApp.isPhonegap()) {
                        cordova.plugins.barcodeScanner.scan(actionOnSuccess, actionOnFail);
                    }
                    else {
                        //actionOnSuccess({text: 'http://flexilogin.com/'});

                        // test subscription
                        actionOnSuccess({text: 'http://flexilogin.com/user.php?user_id=7'});
                    }
                });

                $contentDiv.append($scanButton);

                return $('<div/>').addClass('fx_qrScanner')
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            this.fill = function(contextObject, callback){
                callback();
            };
        },

        /**
         * Calendar Widget. This widget allows the user to:
         * 1. display events from queries in the calendar;
         * 2. add events to any date.
         *
         * USED 'FullCalendar' PLUGIN.
         */
        Calendar: function() {
            this.render = function(){
                return $('<div/>').addClass('fx_calendar')
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            this.fill = function(contextObject, callback){
                var currentWidget = this;

                var recursiveGetQueriesForCalendar = function(calendarData, queries, callback, error_callback) {
                    if(!isEmpty(calendarData)) {
                        $.each(calendarData, function(index, queryData) {
                            var queryId = queryData.query;
                            DFXAPI.getWidget(queryId, 'query', function(queryInfo){
                                DFXAPI.getQueryResult({query: queryId}, function(queryResult){
                                    delete calendarData[index];
                                    queries[queryId] = $.extend( { query_result : queryResult, query_data: queryInfo}, queryData);
                                    recursiveGetQueriesForCalendar(calendarData, queries, callback, error_callback);
                                }, function(errors) {
                                    error_callback(errors);
                                });
                            }, function(errors) {
                                error_callback(errors);
                            });
                        })
                    }
                    else callback(queries);
                }
                var calendarQueries = {};
                var i = 0;

                $.each(this.calendar_data, function(index, object){
                    if(object !== null && typeof object === 'object') {
                        calendarQueries[i] = object;
                        i++;
                    }
                });

                recursiveGetQueriesForCalendar(calendarQueries, {}, function(queries){
                    var $calendarDiv = currentWidget.$contentDiv.addClass('fullcalendar');
                    var events = [];

                    $.each(queries, function(queryId, query) {
                        var title = query.title,
                            date_start = query.dateStart,
                            time_start = query.timeStart,
                            date_end = query.dateEnd,
                            time_end = query.timeEnd,
                            color = query.color,
                            form = query.form;

                        var queryData = query.query_data;
                        if(queryData) {
                            $.each(queryData, function(){
                                var fields = queryData.fields;
                                if(fields) {
                                    $.each(fields, function(index, fieldsOptions) {
                                        var currentName = fieldsOptions.name,
                                            currentAlias = fieldsOptions.alias;

                                        if(currentAlias) {
                                            if(date_start == currentName)  date_start = currentAlias;
                                            if(time_start == currentName) time_start = currentAlias;
                                            if(title == currentName)  title = currentAlias;

                                            if(date_end) {
                                                if(date_end == currentName)  date_end = currentAlias;
                                                if(time_end == currentName)  time_end = currentAlias;
                                            }
                                        }
                                    })
                                }
                            });
                        }

                        $.each(query.query_result, function(id, object) {
                            var dateStart = object[date_start];

                            if(dateStart) {
                                var event = {
                                    object_id: id,
                                    title: object[title],
                                    color: color,
                                    form: form
                                };

                                if(queryData)
                                    event.object_type = queryData.main_type;

                                //event.start = new Date(parseInt(object[date_start]* 1000)).toString("yyyy-MM-dd");
                                event.start = new Date(object[date_start]).toString("yyyy-MM-dd");

                                //console.log('date_start: ' + object[date_start]);
                                //console.log('time_start: ' + object[time_start]);
                                //console.log('1: ' + event.start);

                                if(object[time_start]) {
                                    var time = new Date(object[time_start]);

                                    if(time != "Invalid Date") {
                                        time = time.toString("hh:mm");
                                        event.start += 'T' + time;
                                    }
                                }
                                //console.log('2:' + event.start)

                                var dateEnd;


                                try {
                                    dateEnd = object[date_end];
                                    if(dateEnd) {
                                        var date_end = query.dateEnd;
                                        if(date_end) {
                                            event.end = new Date(object[date_end]).toString("yyyy-MM-dd");
                                            if(object[time_end])
                                                event.end += 'T' + new Date(object[time_end]).toString("hh:mm");
                                        }
                                    }

                                }
                                catch(e) {
                                    event.end = undefined;
                                }

                                events.push(event);
                            }
                        });
                    });

                    $calendarDiv.empty();
                    $calendarDiv.fullCalendar({
                        dayClick: function() {},
                        eventClick : function(event) {
                            var eventId = event.form;
                            if(eventId) {
                                var currentApp = currentWidget.application;
                                var genericApp = currentApp.genericApp;

                                var widget = {
                                    type : 'DataForm',
                                    form: eventId,
                                    mode: 'edit',
                                    header_bar: 1,
                                    inset_style: 1,
                                    title: 'Temporary page'
                                };

                                currentWidget.application.createTempPage(widget, function($div) {
                                    genericApp.recalculateSizes();
                                    var contextObj = {
                                        object_id: event.object_id.split('-')[0],
                                        object_type: event.object_type
                                    };
                                    currentApp.navigateToPageById('temp', contextObj);
                                    //$('.fx_top_navigation').show();
                                });
                            }
                        },
                        events: events
                    });
                    $calendarDiv.find('.fc-button.fc-button-prev').prependTo($calendarDiv.find('.fc-header .fc-header-left'));
                    $calendarDiv.find('.fc-header-title').prependTo($calendarDiv.find('.fc-header .fc-header-center'));
                    $calendarDiv.fullCalendar( 'today' );
                    callback();
                }, function(errors) {
                    currentWidget.printErrors(errors);
                    callback();
                });
            };
        },

        /**
         * Chart. This widget displays charts from the DFX server.
         * It's just image.
         */
        Chart: function() {
            this.render = function(){
                return $('<div/>').addClass('chart')
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            this.fill = function(contextObject, callback){
                var currentWidget = this;
                var $contentDiv = currentWidget.$contentDiv;
                var data = { query: currentWidget.query };

                if(modifyValue(currentWidget.grouped)) {
                    data.group = {
                        by: currentWidget.group_by,
                        for: currentWidget.group_for
                    };
                }

                DFXAPI.getQueryResult(data, function(queryData) {
                    var id = 'svg_' + Math.floor(Math.random() * 100);
                    var $button = $('<div>').addClass('button_chart');
                    var $buttonsDiv = $('<div>').addClass('buttons_block');
                    var genericApp = currentWidget.application.genericApp;
                    //var width = (genericApp.isMobile.Android()) ? genericApp.getWidth() : $contentDiv.width();
                    var chartWidth = genericApp.getMinimalSide();

                    if(genericApp.isMobile.Android()) {
                        if(!chartWidth)
                            chartWidth = $contentDiv.width();
                    }

                    chartWidth *= 0.95;

                    $contentDiv.empty().append($('<div>').attr('id', id));

                    if(!currentWidget.xAxis || !currentWidget.yAxis) {
                        //currentWidget.$contentDiv.empty().append($('<p></p>').text());
                        currentWidget.printErrors('Chart data is corrupted.');
                        callback();
                        return;
                    }

                    var currentChartData = {
                        queryData: queryData,
                        x: currentWidget.xAxis,
                        y: currentWidget.yAxis,
                        zoom: modifyValue(currentWidget.zoom),
                        chartType: currentWidget.chart_type,
                        withoutAnimation: true,
                        containerId: id,
                        duration: 500,
                        containerWidth: chartWidth
                    };

                    if(modifyValue(currentWidget.grouped)) {
                        currentChartData.x = currentWidget.group_for;
                    }


                    try {
                        var chart = new Chart(currentChartData);
                        var buttonsList = ['line', 'area', 'bar', 'stacked_bar', 'xpie', 'ypie'];

                        buttonsList.forEach(function(current) {
                            if(currentWidget.types[current]) {
                                $buttonsDiv.append($button.clone().addClass(current + '_type').bind('click', function() {
                                    $('.button_chart').removeClass('active');
                                    $(this).addClass('active');
                                    chart.redrawChart(current);
                                }));
                            }
                        });

                        $buttonsDiv.find('.' + currentWidget.chart_type + '_type').addClass('active');
                        $contentDiv.append($buttonsDiv);
                    }
                    catch(e) {
                        console.log(e.message);
                        currentWidget.printErrors(e.message);
                    }



                    callback();
                }, function(errors) {
                    currentWidget.printErrors(errors);
                    callback();
                });
            };
        },

        /**
         * Data Set Roles. This widget allow admin to add and remove roles for users.
         * Visible only for accepted users list.
         */
        DataSetRoles: function() {
            this.render = function(){
                return $('<div/>').addClass('dataSetRoles')
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            this.fill = function(contextObject, callback){
                var currentWidget = this;
                DFXAPI.getRoles(function(roles){
                    var userRoles = roles;
                    currentWidget.$contentDiv.empty();
                    //function(){
                    var $currentWidget = ich['widget_roles']();
                    var $removeUserRole = $currentWidget.filter('.removeRoles');
                    var $addUserRole = $currentWidget.filter('.addRoles');
                    var $userSelect = $removeUserRole.find('.userSelect');

                    var updateUserList = function(){
                        DFXAPI.getRoles(function(updatedRoles){
                            $userSelect.empty().append($('<option/>').val(0).text('Please select user'));

                            $.each(updatedRoles.users, function(sfxId, object) {
                                var $option = $('<option/>').val(sfxId).text(object.display_name);
                                $userSelect.append($option);
                            });

                            reinitUserChangeListener(updatedRoles);
                            $userSelect.trigger('change');
                        }, function(errors){
                            currentWidget.printErrors(errors);
                            callback();
                        })
                    };
                    var reinitUserChangeListener = function(data) {
                        $userSelect.unbind('change').bind('change', function(){
                            var userId = $userSelect.val();
                            var $rolesList = $removeUserRole.find('.userRoles');
                            $rolesList.empty();

                            if(userId) {
                                var currentSfx;
                                try {
                                    currentSfx = data.users[userId];
                                }
                                catch(e) {
                                    $.each(data.users, function(sfxId, sfxData) {
                                        if(sfxData.user_id == userId) {
                                            currentSfx = sfxData;
                                            return;
                                        }
                                    })
                                }

                                if(currentSfx) {
                                    $.each(currentSfx.roles, function(index, roleId){
                                        var $currentRoleRow = ich['user_role']({roleName: data.roles[roleId]});
                                        $currentRoleRow.find('.button').unbind('click').bind('click', function(){
                                            $(this).parents('.user_role').remove();
                                            DFXAPI.removeRoles(userId, roleId, function(response){
                                                if(response) {
                                                    updateUserList();
                                                }
                                            }, function(errors){
                                                currentWidget.printErrors(errors);
                                                callback();
                                            });
                                        });
                                        $rolesList.append($currentRoleRow);
                                    });
                                }

                            }
                        });
                    };

                    // fill select by users
                    $.each(userRoles.users, function(sfxId, object) {
                        var $option = $('<option/>').val(sfxId).text(object.display_name);
                        $userSelect.append($option);
                    });

                    // fill existing user roles
                    reinitUserChangeListener(userRoles);

                    // fill all roles for adding
                    $.each(userRoles.roles, function(key, value){
                        $addUserRole.find('select').append($('<option/>').val(key).text(value));
                    });

                    //add user role button listener
                    $addUserRole.find('.button.blue').unbind('click').bind('click', function(){
                        var userID = $addUserRole.find('input').val();
                        var roleId = $addUserRole.find('select').find('option:selected').val();
                        DFXAPI.addRole(userID, roleId, function(response){ if(response) { updateUserList(); }}, function(errors){
                            currentWidget.printErrors(errors);
                            callback();
                        });
                    });
                    currentWidget.$contentDiv.css('padding','0.4em 0.4em').append($currentWidget);
                    callback();
                }, function(errors) {
                    //currentWidget.$contentDiv.empty().append($('<p></p>').text('Connection timed out.'));
                    currentWidget.printErrors(errors);
                    callback();
                });

            };
        },

        /**
         * Maps. This widget displays map with points from selected query.
         */
        Maps: function() {
            this.render = function(){
                return $('<div/>').addClass('fx_maps')
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            this.fill = function(contextObject, callback) {
                var currentWidget = this;

                if(FXAPI.offlineMode) {
                    currentWidget.printErrors('Current widget does not work in offline mode');
                    callback();
                    return;
                }

                var args = { query: currentWidget.query };

                DFXAPI.getQueryResult( args, function(response) {
                    currentWidget.$contentDiv.append($('<div/>').attr('id', 'map-canvas').css('height', '500px'));
                    // google maps
                    var mapOptions = { zoom: 16 };
                    var map = new google.maps.Map(document.getElementById('map-canvas'),  mapOptions);

                    var geocoder = new google.maps.Geocoder();

                    var markers = [];
                    var locationField = currentWidget.locationField;
                    var locationSecondField = currentWidget.locationSecondField;
                    var nameField = currentWidget.nameField;

                    var b = 0;
                    var firstLoad = false;
                    $.each(response, function(i, object) {
                        var address = object[locationField];
                        if(locationSecondField > 0) {
                            address += ',' + object[locationSecondField];
                        };

                        geocoder.geocode({'address': address }, function(results, status) {
                            if (status == google.maps.GeocoderStatus.OK) {
                                if(!firstLoad) {
                                    map.setCenter(results[0].geometry.location);
                                    firstLoad = true;
                                }

                                var marker = new google.maps.Marker({
                                    title: object[nameField],
                                    map: map,
                                    position: results[0].geometry.location,
                                    clickable: true
                                });
                                marker.info = new google.maps.InfoWindow({
                                    content: '<b>' + object[nameField] + '</b>'
                                });
                                google.maps.event.addListener(marker, 'click', function() {
                                    marker.info.open(map, marker);
                                });

                                markers.push(marker);

                            }
                            else {
                                currentWidget.application.genericApp.cordovaAlert("Geocode was not successful for the following reason: " + status + "(" + address + ")");
                            }
                        });
                    });

                    callback();
                }, function(errors) {
                    currentWidget.printErrors(errors);
                    callback();
                });
            };
        },

        /**
         * iBeacon. This widget get beacon data and scan visible area for selected beacons.
         * There is an action in app depends of ist proximity.
         * DFX has plugin for create and edit beacons. Each beacon contain such data as:
         * 1. UUID;
         * 2. Major;
         * 3. Minor;
         * 4. Action if proximity is:
         *  4.1. Unknown;
         *  4.2. Far;
         *  4.3. Near;
         *  4.4. Immediate;
         *
         * Current version of widget do nothing.
         */

        iBeacon: function() {
            this.render = function(){
                return $('<div/>').addClass('fx_beacons')
                    .append(this.$header)
                    .append(this.$contentDiv);
            };
            this.fill = function(contextObject, callback) {
                var widget = this;
                DFXAPI.getBeacons(widget.uuid, function(beacons) {

                    var formatedBeacons = {};

                    $.each(beacons, function(_, beacon) {
                        var major = beacon.major;
                        var minor = beacon.minor;

                        if(typeof formatedBeacons[major] === 'undefined' || formatedBeacons[major] == null)
                            formatedBeacons[major] = {};

                        if(typeof formatedBeacons[major][minor] === 'undefined' || formatedBeacons[major][minor] == null)
                            formatedBeacons[major][minor] = beacon;

                    });

                    if(typeof cordova !== 'undefined' && typeof cordova.plugins.locationManager !== 'undefined' && cordova != null) {
                        var delegate = new cordova.plugins.locationManager.Delegate();

                        delegate.didDetermineStateForRegion = function (pluginResult) {};
                        delegate.didStartMonitoringForRegion = function (pluginResult) {};
                        delegate.didRangeBeaconsInRegion = function (pluginResult) {
                            var numberBeaconsInRange = pluginResult.beacons.length;
                            //logToDom('[DOM] didRangeBeaconsInRegion: ' + JSON.stringify(pluginResult));
                            //console.log('[DOM] didRangeBeaconsInRegion: ' + JSON.stringify(pluginResult));
                            if(numberBeaconsInRange > 0) {
                                $.each(pluginResult.beacons, function(i, currentActiveBeacon) {
                                    var addBeaconListener = function(beaconData, currentProximity) {
                                        var analyseProximity = function(action) {
                                            var beaconAction = JSON.parse(action);

                                            // try to find all beacon widgets
                                            //var $beaconWidgets = widget.$div.find('.fx_beacons .fx_widget_content');
                                            //var $beaconWidgets = app.$div.find('.fx_beacons .fx_widget_content');

                                            var $currentBeaconDiv = widget.$div.find('.beaconDiv[data-type="beacon_'+ beaconData.major +'"]');
                                            if($currentBeaconDiv.length == 0) {
                                                $currentBeaconDiv = $('<div/>').addClass('beaconDiv').attr('data-type', 'beacon_' + beaconData.major);
                                                widget.$contentDiv.append($currentBeaconDiv);
                                            }

                                            if($currentBeaconDiv.attr('data-active-state') != currentProximity) {
                                                $currentBeaconDiv.empty().attr('data-active-state', currentProximity);

                                                switch (beaconAction.type) {
                                                    case 'image': {
                                                        $currentBeaconDiv.append($('<img>').attr('src', beaconAction.data));
                                                        break;
                                                    }
                                                    case 'app_page': {
                                                        var $button = $('<div/>').addClass('fx_button').text('Go to ' + beaconData.name).bind('click', function () {
                                                            widget.application.navigateToPageById(beaconAction.data, undefined, false);
                                                        });
                                                        $currentBeaconDiv.append($button);
                                                        break;
                                                    }
                                                    case 'text':
                                                    default:{
                                                        $currentBeaconDiv.append($('<span/>').text(beaconAction.data));
                                                        break;
                                                    }
                                                }
                                            }
                                        };
                                        var modifyProximityString = function(currentProximity){
                                            return [currentProximity.substr(0,9), currentProximity.substr(9)].join('_').toLowerCase();
                                        };
                                        analyseProximity(beaconData[modifyProximityString(currentProximity)]);
                                    };

                                    var beaconData = formatedBeacons[currentActiveBeacon.major][currentActiveBeacon.minor];
                                    addBeaconListener(beaconData, currentActiveBeacon.proximity);

                                });
                            }
                        };

                        cordova.plugins.locationManager.setDelegate(delegate);
                        // required in iOS 8+
                        cordova.plugins.locationManager.requestWhenInUseAuthorization();
                        // or cordova.plugins.locationManager.requestAlwaysAuthorization()

                        $.each(beacons, function(i, object) {
                            var beaconRegion = new cordova.plugins.locationManager.BeaconRegion(object.name, object.uuid, object.major, object.minor);
                            // Start monitoring.
                            cordova.plugins.locationManager.startMonitoringForRegion(beaconRegion).fail(console.error).done();
                            // Start ranging.
                            cordova.plugins.locationManager.startRangingBeaconsInRegion(beaconRegion).fail(console.error).done();
                        });
                    }
                    callback();
                }, function(errors){
                    widget.printErrors(errors);
                    callback();
                });
            };
        }
    };

    return widgetTemplate
}

function Widget (application, widgetData) {
    var widget = this;
    var printErrors = function(response) {
        var $message = $('<div></div>').addClass("fx_error");

        var i = 1;

        if(typeof response.errors !== 'undefined' && response.errors != null) {
            $.each(response.errors, function(_, msg) {
                $message.append($('<p></p>').text(i + '. ' + msg));
                i++;
            });
        }
        else {
            $message.append($('<p></p>').text(i + '. ' + response));
        }


        this.$contentDiv.empty().append($message);
    };

    if(widgetData){
        this.application = application;
        this.$contentDiv = $('<div/>').addClass('fx_widget_content');
        $.extend(this, widgetData);

        var headerBar = this.headerBar || this.header_bar;

        if(headerBar && typeof this.title !== 'undefined' && this.title != null && this.title != '')
            this.$header = $('<h3/>').addClass('fx_widget_title').text(this.title.replace('_', ' '));

        this.application.widgets[widgetData.type].call(this);

        widget.fillWidget = widget.fill;

        widget.printErrors = printErrors;
        widget.fill = function(obj, callback) {
            try {
                widget.fillWidget(obj, callback);
            }
            catch(e) {
                widget.printErrors(e.message);
                callback();
            }
        };



        this.$div = this.render().addClass('fx_widget');

        var inset = widgetData.insetStyle || widgetData.inset_style;
        if(inset)
            this.$div.addClass('inset');
    }
};

/**
 * @param {Object} pageInfo Data on a page, namely: widgets, show or hide in navigation an so on.
 * @param {Object} application Link to an application object;
 */
function Page (pageInfo, application) {
    this.id =  pageInfo.id;
    this.name = pageInfo.name;
    var toAppend = [];

    if(pageInfo.elements === 'undefined' || pageInfo.elements == null)
        pageInfo.elements = {};

    this.widgets = $.map(pageInfo.elements, function(widgetData) {
        //var element = elements[elementInfo.type](elementInfo, application);
        var element = new Widget(application, widgetData);
        //toAppend.push($('<li>').append(element.$div));
        toAppend.push(element.$div);
        return element;
    });
    this.contextType = pageInfo.contextType || pageInfo.context_type;
    this.hideInNavigation = pageInfo.hideInNavigation || pageInfo.hidden;
    this.application = application;

    var $page = ich['specific_app_page'](pageInfo);
    $page.attr('id', 'page_' + this.id);
    $page.find('.page_content').append(toAppend);

    this.$div = $page;

    /**
     * The function shows the selected page with contextObject passed to this page.
     * @param {Object} contextObject;
     * @param {Function} action
     */
    this.fill = function(contextObject, action){
        var page = this;
        var application = page.application;
        var widgets = this.widgets.slice();

        var recursiveWidgetFilling = function(widgets, stack, callback) {
            if(widgets.length > 0) {
                var currentWidget = widgets.shift();

                try {
                    currentWidget.fill(contextObject, function() {
                        stack.push(currentWidget.type);
                        recursiveWidgetFilling(widgets, stack, callback);
                    });
                }
                catch(e) {
                    console.log('catch error: ' + e.message)
                    currentWidget.printErrors(e.message);
                    stack.push(currentWidget.type);
                    recursiveWidgetFilling(widgets, stack, callback);
                }

            }
            else {
                callback(stack);
            }
        };
        recursiveWidgetFilling(widgets, [], action);
        application.genericApp.createKeyboardAppearListeners();
    };
    /**
     * The function will shows the selected page's content.
     */
    this.showContent = function() {
        this.$div.children().css('visibility', '');
    };
    /**
     * The function will hide the selected page's content.
     */
    this.hideContent = function() {
        this.$div.children().css('visibility', 'hidden');
    };
    /**
     * The function will show the selected page.
     */
    this.show = function(){
        this.$div.addClass('active');
    };
    /**
     * The function will hide the selected page.
     */
    this.hide = function(){
        this.$div.removeClass('active');

        if(typeof this.iScroll !== 'undefined' && this.iScroll != null) {
            this.iScroll.destroy();
            delete this.iScroll;
        }
    };
};

/**
 * The function creates an application class.
 * @param {Object} application Object which contains all data required for creating a specific application;
 * @param {Object} genericApp Link to the Generic Application object;
 */
function Application (application, genericApp){
    var app = this;

    /**
     * The function fills pages.
     * @param {Object} pages List of pages.
     */
    this.getPagesContent = function(pages) {
        var app = this;

        this.activePageId = "startPage";

        app.pages = {"startPage": new Page({
            id: "startPage",
            elements: [{type: "DataSetSelect"}]
        }, app)};

        var toAppend = [app.pages.startPage.$div];

        $.each(pages, function(i, pageInfo){
            var page = new Page(pageInfo, app);
            toAppend.push(page.$div);
            app.pages[page.id] = page;
        });

        ////console.log(app.pages);
        return toAppend;
    };

    /**
     * The function createas div for refresh indicator.
     * @param {Object} pages List of pages.
     */
    //this.createRefreshDiv = function(pages) {
        //return $('<div>').attr('id', 'refresh').text('UPDATING CONTENT...').css({
        //    background : 'rgba(163, 155, 239, 0.43)',
        //    'text-align': 'center'
        //});;
    //};

    /**
     * The function creates the navigation panel for the specific application.
     * @param {Object} customPages List of pages, which should be included into the navigation list.
     */
    this.makeNavigation = function(pages) {
        var app = this;
        var $navigationTop = app.$div.find('#top_navigation').css('visibility', 'hidden');//.hide();
        var $pageSelect = $navigationTop.find('.fx_app_navigation_select');
        var $buttonBack = $navigationTop.find('.fx_button.back');
        var $buttonRefresh = $navigationTop.find('.fx_button.refresh');
        var $quitFromAppButton = $navigationTop.find('.fx_button.exit');
        var genericApp = app.genericApp;

        $buttonBack.unbind('click').bind('click', app.goBack);
        $buttonRefresh.unbind('click').bind('click', function() {
            app.refreshPage(app.contextObject);
        });
        $quitFromAppButton.unbind('click').bind('click', function(){
            $quitFromAppButton.prop('disabled', true);
            delete DFXAPI.activeDataSet;

            if(app.genericApp.isSpecificApp) {
                localStorage.clear();
                genericApp.init();
            }
            else {
                app.$div.remove();
                var $genericApp = genericApp.$genericApp;
                $genericApp.show();
                //genericApp.loaderHide();
                $genericApp.find('#home_button').trigger('click');
                genericApp.recalculateSizes();
            }
            $quitFromAppButton.prop('disabled', false);
        });
        $navigationTop.find('.fx_button.test').unbind('click').bind('click', function(){
            DFXAPI.timeout = (DFXAPI.timeout == 1)? 5000 : 1;
        });

        $.each(pages, function(i, page) {
            if(!modifyValue(page.hidden))
                $pageSelect.append($('<option/>').text(page.name).addClass('navigation_item').val(page.id));
        });
        $pageSelect.bind('change', function(){
            app.navigateToPageById(this.value, undefined, false);
        });

        app.genericApp.recalculateSizes();
    };

    /**
     * The function shows the requested page of the application.
     * @param {int} id Page identifier.
     * @param {Object} queryObject Requested object data;
     * @param {boolean} notSaveInNavigation Value indicating if page changing will be saved in the legend (the navigation history). If the current page is requested, this value will be set to true.
     *
     */
    this.navigateToPageById = function(actualPageId, contextObject, notSaveInNavigation){
        app.contextObject = contextObject;
        var timeout = app.genericApp.loaderShow();

        if(!notSaveInNavigation) {
            if(!app.navigateHistory)
                app.navigateHistory = [];
            app.navigateHistory.push({id: actualPageId, object: contextObject});
        }

        var previousPageId = this.activePageId;
        this.activePageId = actualPageId;

        var previousPage = this.pages[previousPageId];
        var actualPage = this.pages[actualPageId];

        previousPage.hideContent();
        previousPage.hide();
        actualPage.hideContent();
        actualPage.$div.addClass('active');

        var actionOnPageLoad = function() {
            actualPage.showContent();
            app.genericApp.createKeyboardAppearListeners();
            app.genericApp.loaderHide(timeout);
        };

        if(contextObject)  {
            DFXAPI.getObject(contextObject.object_type, contextObject.object_id, function(object){
                app.pages[actualPageId].fill(object, actionOnPageLoad);
            }, function(error) {
                app.genericApp.cordovaAlert(app.genericApp.createErrorMessage(error));
                app.pages[actualPageId].fill(null, actionOnPageLoad);
            });
        }
        else {
            app.pages[actualPageId].fill(null, actionOnPageLoad);
        }

        if(actualPageId != previousPageId) {
            var $topNavigation = $('.fx_top_navigation');
            var $pageSelect = $topNavigation.find('.fx_app_navigation_select');

            if(actualPageId == 'temp') {
                $pageSelect.append($('<option>').text('Temp').addClass('navigation_item').val('temp'));
            }
            else {
                $pageSelect.find('option[value="temp"]').remove();
            }

            // deselect all items, then selet active
            $pageSelect.find('option').prop('selected', false).filter('option[value="' + actualPageId + '"]').prop('selected', true);
            $topNavigation.css('visibility', (actualPageId == 'startPage' ? 'hidden': ''));
        }
    };

    /**
     * The function refreshes data on the current page.
     * @param {Object} contextObject activeObject;
     */
    this.refreshPage = function(contextObject){
        app.navigateToPageById(app.activePageId, contextObject, true);
    };

    this.goToPreviousPage = function() {
        app.navigateHistory.pop();

        var startPageId = this.startPage.id || this.startPage;

        if(app.navigateHistory.length <= 1) {
            app.navigateToPageById(startPageId, undefined, true);
        }
        else {
            app.refreshPage(null)
        }
    };

    this.goBack = function() {
        //console.log('back clicked :: items in history ' +  app.navigateHistory.length + ', items: ',  app.navigateHistory);
        if(app.navigateHistory.length >= 2) {
            // remove active page from history
            app.navigateHistory.pop();
            var lastPage = app.navigateHistory[app.navigateHistory.length - 1];
            app.navigateToPageById(lastPage.id, lastPage.object, true);
        }
    };


    this.createTempPage = function(data, callback) {
        var tempPage = new Page({id: 'temp', elements: [data], hideInNavigation: true }, app);
        app.$div.find('#application_pages').append(tempPage.$div);
        app.pages.temp = tempPage;
        if(!!callback) callback(tempPage.$div);
    };

    this.addhttp = function(url) {
        if (!/^(f|ht)tps?:\/\//i.test(url)) {
            url = "http://" + url;
        }
        return url;
    }

    /**
     * The function init application:
     * 1. creates pages;
     * 2. creates widgets on pages;
     * 3. fill actual page;
     * @param {Object} contextObject activeObject;
     */
    this.init = function() {
        var applicationInfo;
        try {
            applicationInfo = JSON.parse(application.code);
        } catch(e) {
            applicationInfo = application.code;
        }

        this.widgets = widgets();

        genericApp.loaded = true;
        genericApp.application = application;
        app.data = application;
        app.genericApp = genericApp;

        var startPageId = applicationInfo.start_page || applicationInfo.startPage;

        $.extend(DFXAPI, {
            SITE_URL: app.addhttp(application.base_url),
            BASE_API_URL: app.addhttp(application.base_api_url),
            API_KEY: application.channel_key,
            SCHEMA_ID: application.remote_schema_id || application.schema_id,
            UPLOADS_URL: application.base_url + "uploads/",
            encryptKey: application.channel_token,
            db: genericApp.db,
            localUsing: application.is_local_using
        });

        if(genericApp.isSpecificApp) {
            var supportsOrientationChange = "onorientationchange" in window;
            var orientationEvent = supportsOrientationChange ? "orientationchange" : "resize";
            var actionByResize = function() {
                genericApp.recalculateSizes();
                genericApp.createKeyboardAppearListeners();

                if(genericApp.isKeyboardShows && genericApp.isMobile.any()) {
                    genericApp.onShowKeyboard();
                }
            };

            window.addEventListener(orientationEvent, actionByResize, false);
            actionByResize()
        }

        genericApp.createKeyboardAppearListeners();

        var $app = app.$div = ich['specific_application']();

        var theme = new Themeroller({ style: application.style, inApp: true });
        $app.find('style').empty().prepend(theme.createStyles());

        $app.find('#application_pages').empty().append(app.getPagesContent(applicationInfo.pages));

        app.startPage = app.pages[startPageId];
        app.navigateToPageById(app.activePageId, undefined, true);
        app.makeNavigation(applicationInfo.pages);

        var menu = applicationInfo.bottom_navigation;
        if(menu !== null && typeof menu === 'object') {
            var $footer = $('<div/>').addClass('fx_bottom_navigation footer').css('display','none');
            var $list = $('<ul></ul>').addClass('navigate_list');

            if(menu) {
                $.each(menu, function(id, object){
                    var $currentItem = $('<li></li>').addClass('menu_item');
                    $currentItem.css('background-image', 'url("' + object.image + '")');

                    if(!!object.page_id){
                        $currentItem.bind('click', function(){
                            app.navigateToPageById(object.page_id, undefined, false);
                        });
                    }

                    $list.append($currentItem);
                })
            }

            $footer.append($list);
            $app.append($footer);
        }

        $('body').append($app);

        genericApp.$genericApp.not('#loader').hide();
        genericApp.recalculateSizes();

        var onDeviceReady = function() {
            document.addEventListener("backbutton", function(){
                app.$div.find('.fx_button.back').trigger('click');
            }, true);
            var pictureSource = navigator.camera.PictureSourceType;
            var destinationType = navigator.camera.DestinationType;

        };
        document.addEventListener("deviceready", onDeviceReady, true);
        //document.addEventListener('touchmove', function (e) { e.preventDefault(); }, false);
    };

    if(genericApp.isSpecificApp) {
        $(window).load(function() {
            app.init();
        });

        if(genericApp.loaded)
            app.init();
    }
    else {
        app.init();
    }
};