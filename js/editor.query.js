var flexiweb = window.flexiweb;
var QueryItem = function(data, editor) {
    PaletteItem.call(this, data, editor);

    var item = this;
    this.create = function() {
        data.type = data.type.toLowerCase();
        var $item = ich["QueryPaletteItem"](data);
        item.modify($item);
        return $item;
    };

    this.modify = function($li) {
        var parentType = $li.attr('data-parent-type');
        var $alias = $li.find("[name='alias']");
        var $caption = $li.find("[name='caption']");
        var duplicates = 0;

        if(!isEmpty(item.editor.joined_types)) {
            var typeDisplayName = data.type_display_name;
            var typeName = data.type_name;
            $caption.val(typeDisplayName +' '+$caption.val());
            $alias.val(typeName+' '+$alias.val());
        }

        $li.siblings().each(function(){
            if($(this).data('name') === $li.data('name')) {
                duplicates++;
            }
        });
        if(duplicates>0) {
            $alias.val($alias.val() + '_' + duplicates);
            $caption.val($caption.val() + ' ' + duplicates);
        }
    };
    this.init();
};

var QueryEditor = function(args) {
    var editor = this;

    if(typeof args === 'undefined' || args == null)
        args = {};

    flexiweb.callFunction.getObjectTypeIdByName('query', function (response) {
        args.object_type_id = response;
        args.object_id = getUrlParameter('object_id');
        args.schema_id = flexiweb.schema_id;
        args.set_id = flexiweb.set_id;
        args.page = $('.tab-pane.active');
        args.defaultValues = {
            criteria: '',
            aggregation: '',
            order: 'none'
        };
        args.typesNumber = 1;
        args.identifier = 'name';

        args.palette_colors = ['#FFFFCC', '#FFD787', '#FFCCCC', '#CC9999', '#CC99CC', '#CCCCFF', '#6699FF'];

        Editor.call(editor, args);

        $.extend(editor, args);

        editor.itemConstructor = { fields : QueryItem };

        editor.convertFormatForPreview = function () {
            var tree = editor.tree;
            var newTree = {};

            $.each(tree, function (parent, object) {
                var joinedTypesList = $.map(object, function(_, child){
                    return child;
                });

                if (!isEmpty(joinedTypesList))
                    newTree[parent] = joinedTypesList;
            });

            return isEmpty(newTree) ? "" : JSON.stringify(newTree);
        };

        editor.fillPreview = function(records, callback) {
            if (records) {
                var $resultsTable = editor.$resultsTable;

                if (records) $('.csv_container').show();
                else $('.csv_container').hide();

                var $headRow = $("<tr></tr>"),
                    fieldsOrder = {},
                    fieldTypes = {},
                    maxOrder = 0;

                $resultsTable.empty().append($headRow);

                var tmp_stack = editor.containers.stack.collect('alias');

                $.each(records, function (i, row) {
                    $.each(row, function (field, value) {
                        fieldsOrder[maxOrder++] = field;
                        $headRow.append("<th>" + ((tmp_stack[field] != undefined && tmp_stack[field].caption != undefined) ? tmp_stack[field].caption : field) + "</th>")
                    });
                    return false;
                });

                $.each(records, function (id, fields) {
                    var $element = $("<tr></tr>");
                    for (var i = 0; i < maxOrder; i++) {
                        var field = fieldsOrder[i],
                            content = "",
                            value = fields[fieldsOrder[i]];

                        if (fieldTypes[field] == "image" || field == "image") {
                            content = "<img src='" + value + "'>";
                        }
                        if (fieldTypes[field] == "date") {
                            content = new Date(value * 1000).toLocaleDateString();
                        } else {
                            content = value;
                        }

                        $element.append($("<td></td>").html(content));
                        $resultsTable.append($element);
                    }
                });

                callback();
            }
        };

        editor.stateChangeCallback = editor.previewCreate =  function(callback) {
            var data = {
                main_type: editor.main_type,
                query: editor.collect().code,
                joined_types: editor.convertFormatForPreview(),
                hide_empty: editor.$hideEmpty.prop('checked'),
                schema_id: flexiweb.schema_id,
                set_id: editor.$filter.prop('checked') ? flexiweb.set_id : '0',
                limit: 25
            };

            if(!callback) callback = $.noop;

            flexiweb.callFunction.getQueryCount(data, function (number){
                $('#total').html(number + ' item(s).');

                var pagesNumber = 1;
                var $navigation = $('#query_navigation .navigation')

                if(!editor.previewActivePage ) editor.previewActivePage = 1;

                if(number > data.limit) {
                    pagesNumber = Math.ceil(number / data.limit);

                    $navigation.removeClass('hidden');
                    $navigation.find('.next, .prev').bind('click', function(){
                        var $this = $(this);
                        var page = editor.previewActivePage || 0;

                        if($this.hasClass('next')) page++;
                        else page--;

                        if(page <= pagesNumber && page > 0) {
                            editor.previewActivePage = page;
                            editor.previewCreate();
                        }
                    });

                    var $pages = $('#query_navigation .pages').empty();

                    for( var i = 1; i <= pagesNumber; i++) {
                        (function(page) {

                            var $pageSelector = $('<span>').addClass('page_link').attr('data-page', page).text(page);

                            $pageSelector.bind('click', function() {
                                editor.previewActivePage = page;
                                editor.previewCreate();
                            });

                            $pages.append($pageSelector);
                        })(i);
                    }
                }
                else {
                    $navigation.addClass('hidden');
                }

                data.offset = data.limit * (editor.previewActivePage - 1) || 0;

                $navigation.find('.page_link').addClass('active').filter('[data-page="' + editor.previewActivePage + '"]').removeClass('active');

                flexiweb.callFunction.getQueryResult(data, function (records) {
                    editor.fillPreview(records, callback);
                })
            });

        };

        editor.renderObject = function(loadedObject, callback) {
            var mainTypeId = editor.main_type = editor.object_type;
            var usedTypes = [ editor.object_type.toString() ];
            var tree = editor.tree = {};
            var usedLinks = {};
            var joinedTypes = editor.joined_types = loadedObject.joined_types;

            if(!callback) callback = $.noop;

            editor.used_types = [mainTypeId];
            editor.loadedObject = loadedObject;

            // create list of used in query types, links and tree.
            $.each(joinedTypes, function(parent, children)  {
                // create used types list
                if($.inArray(parent, usedTypes) === -1)
                    usedTypes.push(parent);

                usedLinks[parent] = [];
                tree[parent] = {};

                // create used links (types pair) list
                $.each(children, function(_, child) {
                    if($.inArray(child, usedTypes) === -1)
                        usedTypes.push(child);

                    usedLinks[parent].push(parent + '-' + child);

                    tree[parent][child] = true;
                });
            });
            editor.tree = tree;

            // create existing links from query
            $.each(joinedTypes, function(parent, children) {});

            // clear editor from old data
            editor.clearContainers();
            editor.$joinedTypes.empty();

            // get really existing links
            editor.getTypesLinks(usedTypes.slice(), [], function(typeLinks){
                editor.getTypeData(usedTypes.slice(), [], function(fields) {
                    var $selects = $();

                    var t = false;
                    $.each(typeLinks, function(_, obj) {
                        $.each(obj, function(id, links) {
                            if(t) return;

                            var $select = editor.initSelect(id, links);
                            var link;

                            if(typeof usedLinks[id] !== 'undefined' && usedLinks[id] != null )
                                link = usedLinks[id].pop();

                            //workout for firefox
                            //if(typeof link  === 'undefined') {
                            //    $select.val(link);
                            //}

                            if(!link) {
                                $select.val($select.find('option').first().val());
                            }
                            else {
                                var $opt = $select.find('option[value="' + link + '"]');

                                if($opt.length == 0) {
                                    t = true;

                                    for (var i in usedTypes) {
                                        if( usedTypes[i] == link.split('-')[1])
                                            delete usedTypes[i];
                                    }
                                }
                                else {
                                    $select.val(link)
                                }
                            }

                            $selects = $selects.add($select);
                        });
                    });

                    editor.$joinedTypes.append($selects);
                    editor.updateTypesList();

                    editor.dataElements = editor.handleTypeData(fields);
                    editor.initPalettes();

                    //var code = JSON.parse(loadedObject.code);
                    var code = loadedObject.code;
                    $.each(code, function(i, element) {
                        if(typeof element.object_type === 'undefined' || element.object_type == null)
                            element.object_type = editor.object_type;

                        var str = element.object_type.toString();
                        var num = parseInt(element.object_type);

                        if(usedTypes.indexOf(str) == -1 && usedTypes.indexOf(num) == -1)
                            code[i] = null;

                    });

                    editor.render(code);
                    editor.createSwitchersForFields();
                    editor.needToLoad = false;
                    editor.previewCreate(callback);
                    editor.stateChange(true);
                })
            });

            editor.specialRenderData(loadedObject);
        };

        /* This method does:
        * 1. Make options for select in palette (for choose which fields need to show) and add listener;
        * 2. Change color of fields in palette;
        * */
        editor.createSwitchersForFields = function () {
            var $tabs = editor.$tabs.empty();
            var $palette = editor.palettes.fields.$div;
            var $stack = editor.containers.stack.$div;

            $.each(editor.used_types, function (index, typeId) {
                var $newSwitcher = $('<option>').text(editor.types[typeId].display_name).val(typeId);
                $tabs.append($newSwitcher);
                $stack.add($palette).find('.palette-item[data-type="' + typeId + '"]').css('background', editor.palette_colors[index])
            });

            $tabs.bind('change', function () {
                $palette.find('.palette-item').hide();
                $palette.find('.palette-item[data-type="' + $tabs.val() + '"]').show();
            }).trigger('change');
        };

        /* This method returns selected type's parent. */
        editor.getParentType = function (id) {
            var parentId = 0;
            $.each(editor.tree, function (parent, childrenList) {
                $.each(childrenList, function (currentChildId, _) {
                    if (currentChildId == id) {
                        parentId = parent;
                    }
                });
            });
            return parentId;
        };

        /* This method collect all types data:
        * 1. Types tree (who is whois parent / children, for example: tree = { TYPE25: { TYPE24: true, TYPE21: true } }
        * 2. Used types list: [25, 24, 21];
        * 3. Joined types list: [24, 21];
        */
        editor.updateTypesList = function () {
            var joinedTypes = [];
            var usedTypes = [];
            var tree = {};

            $.each(editor.$joinedTypes.find('select'), function (index, select) {
                var val = $(select).val().split('-');
                var currentSelectValue = val[1];
                var parentId = val[0];

                if (currentSelectValue) {
                    if (!tree[parentId])
                        tree[parentId] = {};

                    tree[parentId][currentSelectValue] = true;
                    joinedTypes.push(currentSelectValue);
                }
            });

            usedTypes.push(editor.object_type);
            usedTypes = usedTypes.concat(joinedTypes);

            for (var i in usedTypes) {
                usedTypes[i] = parseInt(usedTypes[i]);
            }

            editor.used_types = usedTypes;
            editor.joined_types = joinedTypes;
            editor.tree = tree;
        };

        /* This method returns select with available joined types. */
        editor.initSelect = function(parentId, children) {
            var $select = $('<select class="before_button"></select>')
                .attr('data-parent-id', parentId)
                .attr('data-level', editor.typesNumber);

            editor.typesNumber++;

            var $option = $('<option></option>');
            var $options = $option.clone().val(0).text('Not linked');
            var parent = editor.types[parentId];

            $.each(children, function(id, typeData) {
                if(editor.used_types.indexOf(parseInt(id)) > -1) return false;

                var text = parent.display_name + ' -> ' + typeData.display_name;
                $options = $options.add($option.clone().text(text).val(parentId + '-' + id));
            });

            $select.unbind('change').bind('change', function() {
                var timeout = flexiweb.loaderShow();
                var val = this.value;
                var id = val.split('-')[1];
                var level = this.dataset.level;

                // remove all unused and extra selects
                $.each(editor.$joinedTypes.find('select'), function() {
                    if(this.dataset.level > level)
                        $(this).remove();
                });

                editor.updateTypesList();

                // remove unlinked types' fields
                $.each(editor.containers, function(id, container) {
                    var $container = container.$div;

                    $.each($container.children('div'), function() {
                        var $li = $(this);
                        var type = $li.data().type.toString();

                        if($.inArray(type, container.editor.used_types) == -1) {
                            $li.remove();
                        }
                    });

                    container.checkItems();

                });

                editor.updateTypesList();

                editor.getTypeData(editor.used_types.slice(), [], function(fields) {
                    editor.dataElements = editor.handleTypeData(fields);
                    editor.initPalettes();
                    editor.createSwitchersForFields();

                    if(val != 0) {
                        editor.getTypesLinks([id], [], function(links) {
                            var $select = editor.initSelect(id, links[0][id]);
                            editor.$joinedTypes.append($select);
                            editor.stateChange(false);
                            flexiweb.loaderHide(timeout);
                        });
                    }
                    else {
                        editor.stateChange(false);
                        flexiweb.loaderHide(timeout);
                    }
                });
            });

            return $select.append($options);
        };

        /* The method put all @types' field in @res. */
        editor.getTypeData = function(types, res, callback) {
            if(types.length > 0) {
                var type = types.pop();
                flexiweb.callFunction.getTypeFields(type, function(response) {
                    var opts = {
                        object_type_id: type,
                        object_type: type,
                        parent_type: editor.getParentType(type),
                        type_display_name: editor.types[type].display_name,
                        type_name: editor.types[type].name
                    };

                    $.each(response, function(){
                        res.push($.extend(this, opts));
                    });
                    editor.getTypeData(types, res, callback);
                });
            }
            else {
                callback(res);
            }
        };

        /* This method returns all available links for types. */
        editor.getTypesLinks = function(types, res, callback) {

            if(types.length > 0) {
                var type = types.shift();

                flexiweb.callFunction.getTypeLinks(type, function (links) {

                    var usefulLinks = {};
                    $.each(links, function (linkedId, data) {
                        if ($.inArray(linkedId, editor.used_types) == -1) {
                            usefulLinks[linkedId] = data;
                        }
                    });

                    var obj = {};
                    obj[type] = usefulLinks;
                    res.push( obj );
                    editor.getTypesLinks(types, res, callback);
                });
            }
            else {
                callback(res);
            }
        };

        /* COMMON Editor Methods */

        /* This method inits all required elements for editor. */
        editor.specialPreInit = function() {
            var $page = editor.$page;

            editor.$joinedTypes = $page.find('#joins');
            editor.$tabs = $page.find('#tabsForTypes');

            editor.$filter = $page.find('#filter_by_set').bind('change', function() {
                editor.stateChange(false);
            });
            editor.$hideEmpty = $page.find('#hide_empty').bind('change', function() {
                editor.stateChange(false);
            });
            editor.$hideSystem = $page.find('#hide_system_types').bind('change', function() {
                if(this.checked) {
                    $.each(editor.$typeSelector.find('option'), function() {
                        var $option = $(this);

                        if($option.data().system)
                            $option.not(':selected').css('display', 'none')
                    })
                }
                else {
                    editor.$typeSelector.find('option').css('display','')
                }
                editor.stateChange(false);
            });
            editor.$goToType = $page.find('#go_to_type');

            editor.$results = $page.find('#results');
            editor.$resultsTable = editor.$results.find('#results-table');
            editor.$csv = editor.$results.find('#csv');

        };

        /* This method render application object. */
        editor.specialRenderData = function(data) {
            editor.$filter.prop("checked", parseInt(data.filter_by_set));
            editor.$hideEmpty.prop("checked", parseInt(data.hide_empty));
            editor.$goToType.attr('href', window.flexiweb.site_url + "design_editor/design_types?object_type_id=" + data.main_type || data.object_type);
            editor.$csv.attr('href', flexiweb.site_url + 'ajax/get_query_results_csv.php?query_id=' + editor.object_id + '&set_id=' + editor.set_id)
        };

        /* This method collect application data from editor. */
        editor.specialCollectData = function() {
            return {
                main_type: editor.object_type,
                joined_types:  editor.convertFormatForPreview(),
                filter_by_set: editor.$page.find('#filter_by_set').prop("checked") ? 1 : 0,
                hide_system_types: editor.$page.find('#hide_system_types').prop("checked") ? 1 : 0,
                hide_empty: editor.$page.find('#hide_empty').prop("checked")? 1 : 0
            };
        };

        /* The system reference of Editor Class. */
        editor.modifyLoadedObject = function(data) {
            editor.object_type = data.object_type = data.main_type;
            return data;
        };

        /* The system reference of Editor Class. */
        editor.loadTypeCallback = function() {
            var mainTypeId = editor.main_type = editor.object_type;
            var object = editor.loadedObject;

            if(typeof object !== 'undefined' && object != null && editor.needToLoad) {
                editor.renderObject(object);
            }
            else {
                editor.used_types = {};
                editor.joined_types = {};
                editor.tree = {};
                editor.tree[mainTypeId] = [];

                editor.getTypesLinks([mainTypeId], [], function (links) {
                    var $select = editor.initSelect(mainTypeId, links[0][mainTypeId]);
                    editor.$joinedTypes.append($select);
                });
            }
            $('#mainTypePage').attr('href', window.flexiweb.site_url + 'design_editor/design_types?object_type_id=' + mainTypeId);
        };

        /* This method loaded palette depend on data. */
        editor.loadType = function(_, callback) {
            if(typeof callback === 'undefined' || callback == null)
                callback = $.noop;

            var mainTypeId = editor.main_type = editor.object_type;
            editor.used_types = [mainTypeId];
            editor.joined_types = {};
            editor.tree = {};
            editor.loadedObject = undefined;
            editor.$goToType.attr('href', window.flexiweb.site_url + "design_editor/design_types?object_type_id=" + mainTypeId);

            var timeout = flexiweb.loaderShow();
            editor.updateTypesList();
            editor.getTypeData(editor.used_types.slice(), [], function(res) {
                var renderedItems = editor.dataElements = editor.handleTypeData(res);
                editor.clearContainers();
                editor.$joinedTypes.empty();
                editor.$resultsTable.empty();
                editor.initPalettes();
                editor.createSwitchersForFields();
                editor.loadTypeCallback();
                callback(renderedItems);
                flexiweb.loaderHide(timeout);
            });
        };

        /* This method inits existed object. */
        editor.loadObject = function(id) {
            if(id == -1) {
                editor.unloadObject();
                editor.$resultsTable.empty();
                //editor.$results.show();
            }
            else {
                var timeout = flexiweb.loaderShow();
                flexiweb.callFunction.getObject(editor.object_type_id, id, function(object) {
                    var isObjectCorrect = editor.checkObject(object);

                    if(!isObjectCorrect) {
                        editor.unloadObject();
                        editor.$typeSelector.trigger('change');
                        //editor.$results.show();
                        flexiweb.loaderHide(timeout);
                    }
                    else {
                        object = editor.modifyLoadedObject(object);
                        window.history.pushState("t", "Title", "?object_id=" + id);
                        editor.$typeSelector.val(object.object_type);
                        editor.$objectName.val(object.display_name);
                        editor.renderObject(object, function(){
                            //editor.$results.show();
                            flexiweb.loaderHide(timeout);
                        });

                        //editor.loadType(object.object_type, editor.loadObjectCallback);
                    }
                });
            }

        };

        editor.init();
    });
};