(function Flexiweb () {
    var flexiweb = window.flexiweb;
    $(function() {
        // create menu
        (function() {
            var flexiweb = this;
            var $menu = $('#menu').addClass('noselect');

            if($menu.length == 0)
                return;

            flexiweb.standard_menu = localStorage.menu == 'standard';

            this.setActiveState = function() {
                var $menuTabs = $menu.find('.menu_tab');
                var activePage = this.session_page || this.session_cat;

                // activate saved state
                $menuTabs.unbind('click').bind('click', function() {
                    var $currentTab = $(this);
                    var $menuList = $menu.find('.menu_list');
                    var activeMenu = $currentTab.attr('data');

                    // hide all menus
                    $menuList.hide();
                    $menuTabs.removeClass('active');

                    // show activated menu
                    $currentTab.addClass('active');
                    $menuList.filter('#' + activeMenu).show();
                });

                $menuTabs.filter('[data="'+ $('#' + activePage).parent().attr('id') + '"]').trigger('click');

            };
            this.createMenu = function(){
                var $collapseButton = $('#collapse');
                var strongClass = 'standard', lightClass = 'compact';
                var activeClass = 'active';
                var $submenus = $menu.find('.submenu');
                var $menuItems = $menu.find('.category');
                var $currentCat = $('#' + this.session_cat);

                $.each($menuItems, function() {
                    var link = this.dataset.link;
                    if(typeof link !== 'undefined' && link != null) {
                        this.onclick = function() {
                            window.location.href = link;
                        }
                    }
                });

                var createFullsizeMenu = function() {
                    var $currentPage = $('#' + this.session_page);

                    $menu.removeClass(lightClass).addClass(strongClass);

                    $currentCat.parent().addClass(activeClass).show();
                    $currentCat.addClass(activeClass);
                    $currentPage.addClass(activeClass);

                    // open submenu by click on category name (app_editoe, design_editor, etc)
                    $collapseButton.add('#header').unbind('mouseover');
                    $submenus.find("a").unbind('click');
                    $submenus.unbind('mouseleave');

                    $menuItems.unbind('mouseover').bind('click', function () {
                        var $this = $(this),
                            $current = $this.next('.submenu'),
                            $old;

                        $.each($submenus, function(key, value) {
                            if($(value).hasClass(activeClass)) {
                                $old = $(this);
                                $old.slideToggle('fast','swing').removeClass(activeClass);
                                $old.prev('.category').removeClass(activeClass);
                            }
                        });

                        if(!$current.is($old)) {
                            $current.addClass(activeClass).slideToggle('fast','swing');
                            $this.addClass(activeClass);
                        }
                    });
                };
                var createCompactMenu = function() {
                    $menu.removeClass(strongClass).addClass(lightClass);

                    // open submenu by click on category name (app_editor, design_editor, etc) LIGHT_MODE
                    $currentCat.addClass(activeClass);

                    $menuItems.unbind().mouseover(function () {
                        var $this = $(this),
                            $current = $this.next('.submenu'),
                            $old;

                        $menuItems.removeClass(activeClass);
                        $this.addClass(activeClass);
                        $.each($submenus, function(key, value) {
                            if($this.hasClass(activeClass)) {
                                $old = $(this);
                                $old.hide().removeClass(activeClass);
                            }
                        })

                        $current.addClass(activeClass).show();
                    });
                    $submenus.hide();

                    $submenus.mouseleave(hideSubMenu);
                    $submenus.find("a").click(hideSubMenu);
                    $("#header, #collapse").mouseover(hideSubMenu);

                    function hideSubMenu() {
                        $menuItems.removeClass(activeClass);
                        $submenus.removeClass(activeClass).hide();
                    }

                    $('#switchForCompactMode').unbind('click').bind('click', function() {
                        $menu.find('.menu_tab').filter(':not(.active)').trigger('click');
                    })
                };

                if(flexiweb.standard_menu) createFullsizeMenu();
                else createCompactMenu();
            };

            // light && strong menu versions
            if( typeof localStorage.menu === 'undefined' ) {
                flexiweb.standard_menu = true;
                localStorage.menu = 'standard';
                $.session("menu_state", localStorage.menu);
            }

            // init collapse button
            $('#collapse').unbind('click').bind('click', function(){
                flexiweb.standard_menu = !flexiweb.standard_menu;
                localStorage.menu = flexiweb.standard_menu ? 'standard' : 'compact';
                $.session("menu_state", localStorage.menu);
                flexiweb.createMenu();
            });

            flexiweb.setActiveState();
            flexiweb.createMenu();

            $menu.show();

        }).apply(flexiweb);

        // action on resize event
        $(window).resize(function() {
            var size = $(window).width();
            var actualState;
            var previousState = actualState = flexiweb.standard_menu;

            if (flexiweb.standard_menu && size < 900)
                actualState = 'compact';

            else if(!flexiweb.standard_menu && size >= 900)
                actualState = 'standard';

            if(previousState != actualState) {
                localStorage.menu = flexiweb.standard_menu ? 'standard' : 'compact';
                $('#collapse').trigger('click');
            }
        });

        // general js
        (function() {
            var iOS = false,
                p = navigator.platform;

            iOS = p === 'iPad' || p === 'iPhone' || p === 'iPod';

            if(iOS) {
                $('.tab-content').css('height','100%');
                $('.tab-pane.active').css('height','100%');
            }

            $('#bug').click(function(){ send_error_report(); });
        })();

        // loader
        (function() {
            //$.extend(this, {
            //    $loader: $('#ajax_overlay'),
            //    loaderShow: function() {
            //        if(this.$loader.length == 0)
            //            (function() {
            //                var $loader = $('<div>').attr('id', 'ajax_overlay').append($('<img>').attr('src', this.site_url + '/images/loader.gif'));
            //                $('body .tab-pane.active').append($loader);
            //                this.$loader = $loader;
            //            }).apply(this);
            //
            //        this.$loader.show();
            //    },
            //    loaderHide: function() { this.$loader.hide(); }
            //});


            var loader = this;
            var timeouts = loader.timeouts = [];

            $.extend(this, {
                loaderShow: function() {
                    var $loader = $('#loader');

                    if($loader.length == 0) {
                        $loader = $('<div>').attr('id', 'loader').append($('<img>').attr('src', this.site_url + '/images/loader.gif'));
                        $('body .tab-pane.active').append($loader);


                        //$loader = $('<div>').attr('id', 'loader');
                        //$loader = ich['loader_spinner']();
                        //$('body').append($loader)
                    }

                    $loader.show();

                    var timeout = setTimeout(function() {
                        if($loader.is(":visible")) {
                            loader.loaderHide(timeout);
                        }
                    }, 20000);

                    timeouts.push(timeout);
                    return timeout;

                },
                loaderHide: function(id) {
                    if(typeof id !== 'undefined' && id != null) {
                        clearTimeout(id);
                        timeouts.splice(timeouts.indexOf(id), 1);
                    }

                    if(timeouts.length <= 0) {
                        $('#loader').hide();
                    }
                }

            });


        }).apply(flexiweb);

        // hide and show options block
        //(function() {
        //    var visible = 'visible';
        //    var hidden = 'hidden';
        //    var states;
        //
        //    try {
        //        states = JSON.parse(localStorage.toggle_state);
        //    }
        //    catch(e) {
        //        states = {};
        //    }
        //
        //    var initToggle = function( id ) {
        //        var $control = $('.toggle_advanced[data-toggle="' + id + '"]');
        //        var $target = $('#' + id);
        //
        //        $control.unbind('click').bind('click', function() {
        //            var state;
        //            if($target.hasClass(visible)) {
        //                $target.removeClass(visible).hide('slow');
        //                state = hidden;
        //            }
        //            else {
        //                $target.addClass(visible).show('slow');
        //                state = visible;
        //            }
        //
        //            states[id] = state;
        //            localStorage.toggle_state = JSON.stringify(states);
        //            //console.log(prev + ' => ' + state + ' #### ', states);
        //        });
        //
        //        if(states[id] == visible)
        //            $control.trigger('click');
        //
        //    };
        //
        //    $.each($('.toggle_advanced'), function() {
        //        initToggle(this.dataset.toggle);
        //    });
        //})();

        // call fx function
        flexiweb.callFunction = {};
        (function() {
            this.generalRequest = function (args, callback) {
                if (!args.url)
                    args.url = flexiweb.site_url + 'ajax/call_fx_func.php';

                if (!args.type)
                    args.type = "GET";

                if(typeof callback !== 'function')// || callback == null || getClass.call(callback) != '[object Function]';)
                    callback = $.noop;

                var data = {
                    url: args.url,
                    type: args.type,
                    data: args.data
                };

                $.ajax(data).always(function (response) {

                    //console.log(data.function, ' ::: ', response);

                    response = JSON.parse(response);

                    if(typeof response === 'undefined')
                        return;

                    if(typeof response.errors !== 'undefined' || response.error != null ) {
                        var $errors_text = 'Errors: \n';

                        $.each(response.errors, function(error_code, errors) {
                            $.each(errors, function(index, error) {
                                $errors_text += ('- ' + error +"\n");
                            });
                        });

                        //console.log(response);
                        alert($errors_text);
                        //error_callback({});

                        //if(typeof error_callback === 'undefined' && error_callback == null)
                        //    error_callback = callback;
                        //error_callback();
                        callback();
                    }

                    else {
                        callback(response);
                    }

                    //callback(response);
                    //if(data.statusText === 'timeout' || data.statusText === 'error') {
                    //    var $errors_text = 'Errors: \n';
                    //
                    //    $.each(data.errors, function(error_code, errors) {
                    //        $.each(errors, function(index, error) {
                    //            $errors_text += (index + '. ' + error +"\n");
                    //        });
                    //    });
                    //
                    //    console.log($errors_text);
                    //    alert($errors_text);
                    //    error_callback({});
                    //}
                    //else {
                    //    var response = data.responseText;
                    //}
                    //var renderedResponse;
                    //try {
                    //    renderedResponse = JSON.parse(response);
                    //}
                    //catch (e) {
                    //    renderedResponse = {};
                    //    var msg = 'JSON parse error.';
                    //    console.log(msg);
                    //    alert(msg);
                    //    throw new Error(msg);
                    //}
                    //
                    //if (typeof callback !== 'undefined' && callback != null) {
                    //    if (typeof renderedResponse.errors !== 'undefined') {
                    //        console.log(renderedResponse.errors);
                    //        alert(renderedResponse.errors)
                    //    }
                    //    else {
                    //        callback(renderedResponse);
                    //    }
                    //}
                });
            };
            var request = this.generalRequest;

            //QUERY EDITOR
            this.getQueryResult = function (data, callback) {
                var limit = data.limit || 0;
                var offset = data.page * limit || 0;
                var dataset = data.set_id || 0 ;

                var args = {
                    type: 'POST',
                    data: $.extend({
                        function: 'exec_fx_query',
                        limit: limit,
                        offset: offset,
                        set_id: dataset
                    }, data)
                };
                request(args, callback);
            };
            this.getQueryCount = function(data, callback) {
                var args = {
                    type: 'GET',
                    data: $.extend({
                        function: 'exec_fx_query_count',
                        set_id: data.set_id
                    }, data)
                };
                request(args, callback);
            };


            this.saveQuery = function (id, display_name, mainTypeId, joinedTypes, objectTypeId, queryCode, hideEmpty, filterBySet, callback) {
                var args = {
                    type: 'POST',
                    data: {
                        function: 'update_object',
                        object_array: {
                            object_id: id,
                            code: queryCode,
                            display_name: display_name,
                            main_type: mainTypeId,
                            joined_types: joinedTypes,
                            hide_empty: hideEmpty ? 1 : 0,
                            filter_by_set: filterBySet ? 1 : 0,
                            object_type_id: objectTypeId, //query_object ID
                            schema_id: window.session['current_schema']
                        }
                    }
                };

                if (id < 0) {
                    args.data.function = 'add_object';
                    args.data.object_array.object_id = undefined;
                    window.onbeforeunload = undefined;

                    callback = function (response) {
//                console.log('window.location.search, ', response);
                        window.location.search = "object_id=" + response;
                    }

                }

                request(args, callback);
            };

            //OBJECTS
            this.objectExists = function (typeId, objectId, callback) {
                var args = {
                    data: {
                        function: 'object_exists',
                        object_id: objectId,
                        object_type_id: typeId
                    }
                };
                request(args, callback);
            };
            this.getObject = function (typeId, objectId, callback) {
                var args = {
                    data: {
                        function: 'get_object',
                        object_id: objectId,
                        object_type_id: typeId
                    }
                };

                request(args, callback);
            };
            this.addObject = function (object, callback) {
                var args = {
                    type: "POST",
                    data: {
                        function: 'add_object',
                        object_array: object
                    }
                };

                request(args, callback);

            };
            this.updateObject = function (object, callback) {
                var args = {
                    type: "POST",
                    data: {
                        function: 'update_object',
                        object_array: object
                    }
                };
                request(args, callback);

            };
            this.removeObject = function (typeId, objectId, callback) {
                var args = {
                    data: {
                        function: 'delete_object',
                        object_id: objectId,
                        object_type_id: typeId
                    }
                };
                request(args, callback);
            };
            this.addAppVersion = function(version, callback) {
                var args = {
                    type: "POST",
                    data: {
                        function: 'add_app_version',
                        object_array: undefined,
                        app_array: version
                    }
                };

                request(args, callback);
            }

            //LINKS
            this.getAllLinks = function (typeId, id, callback) {
                var _this = this;
                this.getActualLinks(typeId, id, function (actualLinks) {
                    if (!actualLinks.errors) {
                        _this.getPossibleLinks(typeId, function (possibleLinks) {
                            if (!possibleLinks.errors) {
                                callback();
                            }
                        })
                    }
                });
            };
            this.getTypeLinks = function (typeId, callback) {
                var args = {
                    data: {
                        function: 'get_type_links',
                        object_type_id: typeId
                    }
                }
                request(args, callback)
            };
            this.getPossibleLinks = function (typeId, callback) {
                var args = {
                    data: {
                        function: 'get_possible_links_by_type',
                        object_type_id: typeId
                    }
                }
                request(args, callback)
            };
            this.getActualLinks = function (typeId, id, linkedObjectTypeId, callback) {
                var args = {
                    function: 'get_actual_links',
                    data: {
                        object_type_id: typeId,
                        object_id: id,
                        linked_object_type_id: linkedObjectTypeId
                    }
                }
                request(args, callback)
            };

            this.checkLink = function(typeId1, typeId2, callback) {
                var args = {
                    data: {
                        function: 'link_type_exists',
                        object_type_1_id: typeId1,
                        object_id: typeId2
                    }
                };
                request(args, callback);
            };

            //OBJECTS AND FIELDS
            this.getTypesTree = function (code, callback) {
                var args = {
                    type: "POST",
                    data: {
                        function: "_build_query_tree",
                        code: code
                    }
                };
                request(args, callback);
            };
            this.getTypeFields = function (typeId, callback) {
                var args = {
                    data: {
                        function: "get_type_fields",
                        object_type_id: typeId,
                        fields_mode: "all",
                        filter_fields: true
                    }
                };
                request(args, callback);
            };

            this.getObjectsByType = function (typeId, callback) {
                var args = {
                    data: {
                        function: 'get_type_id_by_name',
                        schema_id: 0,
                        object_type_id: typeId
                    }

                }
                request(args, callback);
            };
            this.getObjectTypeIdByName = function (typeName, callback) {
                var args = {
                    data: {
                        function: 'get_type_id_by_name',
                        schema_id: 0,
                        type_name: typeName
                    }
                }
                request(args, callback);
            };


            this.getSchemaTypes = function(id, fieldsMode, callback) {
                var args = {
                    data: {
                        function: 'get_schema_types',
                        schema_id: id,
                        fields_mode: fieldsMode
                    }
                };

                request(args, callback);
            }

            this.getEnumFields = function(id, callback) {
                var args = {
                    data: {
                        function: 'get_enum_fields',
                        enum_type_id: id
                    }
                }
                request(args, callback);
            }

            this.getObjectFieldValue = function (typeId, id, field, callback) {
                var args = {
                    function: 'get_object_field',
                    data: {
                        object_type_id: typeId,
                        object_id: id,
                        field: field
                    }
                }
                request(args, callback);
            };
            this.updateObjectFieldValue = function (typeId, id, field, value, callback) {
                var args = {
                    data: {
                        function: 'update_object_field',
                        type: typeId,
                        object_id: id,
                        field: field,
                        value: value
                    }
                }
                request(args, callback);
            };

            this.getSchemaER = function (callback) {
                var args = {
                    data: {
                        function: '_get_schema_er',
                        schema_id: flexiweb.schema_id
                    }
                };
                request(args, callback);
            };
            this.removeTempER = function (callback) {
                var args = {
                    data: {
                        function: 'delete_fx_option',
                        option_name: 'er_tmp_' + flexiweb.schema_id
                    }
                };
                request(args, callback);
            }

        }).apply(flexiweb.callFunction);
    });
    return flexiweb;
})();