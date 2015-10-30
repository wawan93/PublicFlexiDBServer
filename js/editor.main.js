/*
* Редактор содержит:
* 1) палитру (возможно несколько);
* 2) стек (возможно несколько);
* 3) блок управления (кнопки "сохранить", "удалить").
*
* Общий блок управления редактором:
* 1) name;
* 2) выбор существующего объекта;
* 3) save;
* 4) delete.
*
* Палитра содержит элементы, каждый из которых представляет из себя:
* 1) конструктор блока (render_div);
* 1.1) вероятно с возможностью выставления сохраненных данных (fill_div);
* 2) обработчик события добавления в стек;
* 3) обработчик события изменения данных (app_editor - добавление страницы, остальные - смена типа);
* 4) ВОЗМОЖНО гибкий обработчик:
*
*   palette.someItem.bind('someAction', function() { //console.log ('qq'); } );
*   editor.trigger('someAction');
*   // qq -> на каждый элемент
*
* 5) метод collectData;
* 6) метод renderData.
*
* Стек: лишь аккумулирует элементы палитры, сортирует их.
* Необходимо добавить метод для расширения настроек jquery-sortable в случае необходимости.
* 1) имеет методы collectData и renderData - запускает одноименные для каждого из элементов.
*
*
* Редактор:
* 1) состояние (saved, changed);
* 2) ссылки на stack, palette
*
*
* */
ich.grabTemplates();

var PaletteItem = function(data, editor) {
    var item = this;

    this.data = data;
    this.editor = editor;

    this.palette = null;
    this.stack = null;

    this.create = function() {};
    this.modify = function() {};

    this.render = function(data) {
        var $clone = item.$div.clone();
        var $inputs = $clone.find('input, select');

        $.each(data, function(attr, value) {
            var $input =  $inputs.filter('[name="' + attr + '"]');
            if($input.attr('type') == 'checkbox')  $input.prop('checked', parseInt(value));
            else $input.val(value);
        });

        $clone.attr('data-container', data.container);
        $clone.attr('data-palette', data.palette);

        //item.fill($clone, data);
        return $clone;
    };
    this.fill = function($item, data){
        return $item;
    };

    this.collect = function($item) {
        var data = $item.data();
        var collection = {
            name: data.field,
            palette: data.palette,
            container: data.container
        };

        $.each($item.find('input, select'), function() {
            var $input = $(this);
            collection[$input.attr('name')] = $input.attr('type') == 'checkbox' ? $input.prop('checked') ? 1 : 0 : $input.val();
        });

        var res = $.extend({}, item.data, collection);
        return res;
    };


    this.addedCallback = function($item, callback) {
        initAccordion($item);
    };
    this.added = function($item) {
        $item.find('.remove').bind('click', function() {
            var itemData = $item.data();

            if(editor.identifier)
                editor.palettes[itemData.palette].$div
                    .find('[data-' + editor.identifier + '="' + itemData[editor.identifier] + '"]')
                    .removeClass('hidden');

            $item.remove();
            item.editor.setUnsavedState();
        });
        $item.find('input, select').bind('change', editor.setUnsavedState);
        $item.find("input, textarea").keyup(editor.setUnsavedState);
        item.addedCallback($item);

        $item.find(".HTMLEditor").each(function(){
            var $area = $(this);
            var name = $area.attr("name");
            if(name && data[name]){
                $area.html(data[name]);
            }
        });
    };

    this.init = function() {
        var $item = this.$div = item.create();
        $item.data('modify', item.modify);
        $item.data('added', item.added);
        $item.data('collect', item.collect);
        $item.data('render', item.render);
        $item.draggable(item.editor.draggableOptions);
    };
};

var Palette = function($palette, Constructor, editor) {
    var palette = this;
    this.$div = $palette;
    this.Constructor = Constructor;

    this.getConstructor = function() {
        return this.Constructor;
    };

    this.fill = function(data) {
        var currentPalette = [];

        $palette.empty();

        if(typeof data === 'undefined' || data == null)
            return;

        $.each(data, function(_, item) {
            var element = new palette.Constructor(item, editor);

            var $item = element.$div.attr('data-palette', palette.$div.attr('id'));
            $item.bind('dblclick', function() {
                $.each(editor.containers, function(_, container) {
                    var $clone = $item.clone();
                    var itemData = $item.data();

                    container.$div.append($clone);

                    $clone.data('modify', element.modify);
                    $clone.data('added', element.added);
                    $clone.data('collect', element.collect);
                    $clone.data('render', element.render);

                    $clone.data().added($clone);
                    editor.setUnsavedState();

                    if(editor.identifier)
                        editor.palettes[itemData.palette].$div
                            .find('[data-' + editor.identifier + '="' + itemData[editor.identifier] + '"]')
                            .addClass('hidden');
                })

            });
            currentPalette.push($item);
        });

        $palette.append(currentPalette);
    };
};

var DataContainer = function($container, editor) {
    var container = this;
    this.$elements = $();
    this.temp_collect = null;
    this.temp_added = null;
    this.temp_modify = null;
    this.editor = editor;
    this.$div = $container;

    this.new = false;

    $container.sortable({
        forcePlaceholderSize:true,
        items: ".ui-draggable",
        placeholder: "collapsible-placeholder",
        start: function(e,ui){
            ui.placeholder.height(ui.item.outerHeight());
            container.temp_collect = $(ui.item).data('collect');
            container.temp_added = $(ui.item).data('added');
            container.temp_modify = $(ui.item).data('modify');
        },
        receive: function(event, ui) {
            //console.log('receive');
            var $item = $(ui.item);
            var itemData = $item.data();
            //console.log(itemData);
            container.temp_collect = itemData.collect;
            container.temp_added = itemData.added;
            container.temp_modify = itemData.modify;
            container.new = true;

            $item.data().modify($item);

            // hide item in palette
            if(editor.identifier)
                editor.palettes[itemData.palette].$div
                    .find('[data-' + editor.identifier + '="' + itemData[editor.identifier] + '"]')
                    .addClass('hidden');
        },

        update: function(event, ui) {
            //console.log('update');
            var $item = $(ui.item);
            $item.data('collect', container.temp_collect);
            $item.data('added', container.temp_added);
            $item.data('modify', container.temp_modify);

            $item.attr('data-container', $container.attr('id'));

            container.$elements = $.map($container.children(), function(item){
                return $(item);
            });

            container.editor.stateChange(false);
        },

        stop: function(event, ui) {
            //console.log('stop');
            var $item = $(ui.item);

            if(container.new) {
                $item.data().added($item);
                container.new = false;
            }

            container.$elements = $.map($container.children(), function(item){
                return $(item);
            });
        }
    });

    this.fill = function(data) { };

    this.checkItems = function() {
        container.$elements = $.map($container.children(), function(item){
            return $(item);
        });
    }

    this.collect = function(arg) {
        container.checkItems();

        var data = $.map(container.$elements, function(i) {
            var dt = $(i).data('collect')($(i));
            return dt
        });

        if(arg) {
            var t = {};
            $.each(data, function(_, item) {
                t[item[arg]] = item;
            });
            data = t;
        }

        return data;
    };
};

var Editor = function() {
    var editor = this;

    this.saved = true;

    this.itemConstructor = { palette : PaletteItem };
    this.palettes = {};
    this.containers = { stack : true };

    this.dataElements = {};
    this.dataContainers = {};

    this.newObjectId = -1;

    this.initContainers = function() {
        $.each(editor.containers, function(id, _) {
            var $currentContainer = editor.$page.find('#' + id);
            var container = editor.containers[id] = new DataContainer($currentContainer, editor);
            container.fill(editor.dataContainers[id]);
        });
    };
    this.clearContainers = function() {
        $.each(editor.containers, function(id, container) {
            container.$div.empty();
            container.$elements = $();
        });
    };

    this.initPalettes = function() {
        $.each(editor.dataElements, function(id, data) {
            var $currentPalette = editor.$page.find('#' + id);
            var palette = editor.palettes[id] = new Palette($currentPalette, editor.itemConstructor[id], editor);
            palette.fill(data);
        });
    };

    this.collect = function() {
        var code = [];

        for (var container in editor.containers) {
            code = code.concat(editor.containers[container].collect())
        }

        var data =  {
            code: code,
            object_type_id: editor.object_type_id,
            object_type: editor.object_type,
            object_id: editor.object_id,
            display_name: editor.display_name
        };

        if(typeof editor.specialCollectData !== 'undefined' && editor.specialCollectData != null) {
            $.extend(data, editor.specialCollectData(data));
        }

        return data;
    };

    this.fill = function(data) {};
    this.render = function(data) {
        var code;

        try {
            code = JSON.parse(data);
        }
        catch(e) {
            code = data;
        }

        $.each(code, function(_, element) {
                if(!element) return true;

                if(typeof element.palette === 'undefined' || element.palette == null)
                    element.palette = 'fields';

                if(typeof element.container === 'undefined' || element.container == null)
                    element.container = 'stack';

                var container = editor.containers[element.container];
                var Constr = editor.palettes[element.palette].getConstructor();
                var item = new Constr($.extend({}, element), editor);
                item.init();

                var $newItem = item.render(element);

                item.added($newItem);
                item.fill($newItem, element);
                //$newItem = item.render($newItem);


                $newItem.data('added', item.added);
                $newItem.data('collect', item.collect);
                $newItem.data('render', item.render);
                $newItem.data('modify', item.modify);

                container.$div.append($newItem);
                container.$elements = container.$elements.add($newItem);
            });
    };


    this.stateChangeCallback = function() {};
    this.stateChange = function(attr, callback) {
        var saved;

        if(typeof callback === 'undefined' || callback == null)
            callback = editor.stateChangeCallback;

        if(typeof attr !== 'undefined' && attr != null)
            saved = editor.saved = attr;
        else
            saved = editor.saved;

        if(saved) {
            editor.$saveButton.removeClass('green').prop('disabled', true);
        }
        else {
            editor.$saveButton.addClass('green').prop('disabled', false);
            callback();
        }


    };
    this.setUnsavedState = function() {
        editor.stateChange(false);
    };

    this.save = function() {
        if(editor.$objectName.val().length == 0) {
            alert('Please, fill display name.');
            return;
        }

        var data = editor.collect();
        var isNewObject = !data.object_id;

        data.schema_id = flexiweb.schema_id;

        var action = isNewObject ? flexiweb.callFunction.addObject : flexiweb.callFunction.updateObject;

        action(data, function(response) {
            if(typeof response.errors === 'undefined' || response.errors == null){
                if(isNewObject){
                    window.onbeforeunload = undefined;
                    window.location.search ="object_id=" + response;
                }

                else {
                    var dname = data.display_name == "" ? data.name : data.display_name;
                    editor.$objectSelector.find('option:selected').text(dname);
                    editor.stateChange(true);
                }
            }
        });

    };
    this.remove = function() {
        if(confirm('Are you sure that you want delete this object?')) {
            flexiweb.callFunction.removeObject(editor.object_type_id, editor.object_id, function(response) {
                if(response) {
                    editor.$objectSelector.find('option[value="' +  editor.object_id + '"]').remove();
                    editor.unloadObject();

                    editor.$typeSelector.trigger('change');
                    alert('Object successfully removed.')
                }
            })
        }
    };

    this.handleTypeData = function(items) {
        return { fields: items };
    };
    this.loadTypeCallback = function() {};
    this.loadType = function(type, callback) {
        if(typeof callback === 'undefined' || callback == null)
            callback = $.noop;

        editor.stateChange(false);
        var timeout = flexiweb.loaderShow();
        flexiweb.callFunction.getTypeFields(type, function(items) {
            var renderedItems = editor.dataElements = editor.handleTypeData(items);
            editor.loadedObject = undefined;
            editor.clearContainers();
            editor.initPalettes();
            editor.loadTypeCallback();

            callback(renderedItems);


            flexiweb.loaderHide(timeout);
        });
    };

    this.loadObjectCallback = function() {
        var object = editor.loadedObject;
        editor.render(object.code);

        if (typeof editor.specialRenderData !== 'undefined' && editor.specialRenderData != null)
            editor.specialRenderData(object);

        editor.stateChange(true);
    };

    this.modifyLoadedObject = function(data) {
        return data
    };


    this.checkObject = function(object) {

        if(typeof object === 'undefined' || object == null)
            return false;

        var objectType = object.object_type || object.main_type;

        if(typeof objectType === 'undefined' || objectType == null || objectType == '' || objectType == '0') {
            alert('This object has incorrect data. There is no object type.');
            return false;
        }

        return true;
    };

    this.unloadObject = function() {
        window.history.pushState("t", "Title", "?");
        editor.loadedObject = undefined;
        editor.object_id = undefined;
        editor.$objectSelector.val(editor.newObjectId);
        editor.$objectName.val("");
        editor.clearContainers();
        editor.object_type = editor.$typeSelector.val();
    };
    this.loadObject = function(id) {
        if(id == editor.newObjectId)
            editor.unloadObject();
        else {
            flexiweb.callFunction.getObject(editor.object_type_id, id, function(object) {
                var isObjectCorrect = editor.checkObject(object);

                if(!isObjectCorrect) {
                    //editor.unloadObject();
                    editor.$typeSelector.trigger('change');
                }
                else {
                    object = editor.modifyLoadedObject(object);
                    window.history.pushState("t", "Title", "?object_id=" + id);
                    editor.$typeSelector.val(object.object_type);
                    editor.object_type = object.object_type;
                    editor.$objectName.val(object.display_name);
                    editor.loadedObject = object;
                    editor.loadType(object.object_type, editor.loadObjectCallback);
                }
            });
        }

    };

    this.initDraggableSettings = function() {
        var options = editor.draggableOptions;
        var connected = $();

        $.each(editor.containers, function(){
            connected = connected.add(this.$div);
        });

        this.defaultDraggableOptions = {
            helper: function(e, ui){
                var $item = $(this), $clone = $item.clone(true);
                $clone.width($item.width());
                $clone.height($item.height());
                $clone.find(".collapsible").css("display", "block");
                $clone.data('collect', $item.data('collect'));

                return $clone;
            },
            connectToSortable: connected,
            revert:true,
            revertDuration: 1
        };
        this.draggableOptions = this.defaultDraggableOptions;
        if(typeof options !== 'undefined' || options != null) {
            editor.draggableOptions = $.extend(editor.draggableOptions, options);
        }

    };


    this.specialPreInit = function() {};
    this.specialInit = function() {};
    this.afterInit = function() {};

    this.init = function() {
        editor.needToLoad = !!editor.object_id;
        //console.log(editor.needToLoad)

        // necessary data
        if(typeof editor.object_type_id === 'undefined' || editor.object_type_id == null)
            throw new Error('Type doesn`t specified.');

        // main control window
        var $page = this.$page = $(editor.page);

        var $objectSelector = this.$objectSelector = $page.find('select#object_select');

        if(typeof editor.object_id !== 'undefined' && editor.object_id != null)
            $objectSelector.val(editor.object_id);

        var $typeSelector = this.$typeSelector = $page.find('select#type_select');
        var $objectName = this.$objectName = $page.find('input#object_name');
        var $saveButton = this.$saveButton = $page.find('input#save');
        var $removeButton = this.$removeButton = $page.find('input#remove');
        var typePrev, objPrev;

        if($objectName.length == 0)
            throw new Error('There is no object name input.');

        if($objectSelector.length == 0)
            throw new Error('There is no object select.');

        if($saveButton.length == 0)
            throw new Error('There is no save button.');

        if($removeButton.length == 0)
            throw new Error('There is no remove button.');

        editor.specialPreInit();

        $objectName.keyup(function(){
            editor.display_name = this.value;
            editor.stateChange(false, $.noop);
        });

        $saveButton.unbind('click').bind('click', this.save);
        $removeButton.unbind('click').bind('click', this.remove);

        $typeSelector.unbind('focus').unbind('change')
            .bind('focus', function() {
            typePrev = this.value;
        })
            .bind('change', function(){
            //var type = editor.object_type = this.value;
            var value = this.value;

            if(!editor.saved) {
                if(confirm('All unsaved data will be lost. Do you want to continue?')) {
                    editor.needToLoad = true;
                    editor.object_type = value;
                    editor.loadType(value);
                }
                else {
                    $typeSelector.val(typePrev);
                }
            }
            else {
                editor.needToLoad = true;
                editor.object_type = value;
                editor.loadType(value);
            }
        });

        $objectSelector.unbind('change').unbind('focus')
            .bind('focus', function() {
                objPrev = this.value;
            })
            .bind('change', function() {
            var value = this.value;

            if(!editor.saved) {
                if (confirm('All unsaved data will be lost. Do you want to continue?')) {
                    editor.needToLoad = true;
                    editor.object_id = value;
                    editor.loadObject(value);
                }
                else {
                    $objectSelector.val(objPrev);
                }
            }
            else {
                editor.needToLoad = true;
                editor.object_id = value;
                editor.loadObject(value);
            }
        });

        editor.specialInit();
        editor.initContainers();
        editor.initDraggableSettings();
        editor.afterInit();

        if(typeof editor.object_id !== 'undefined' && editor.object_id != null) {
            $objectSelector.trigger('change');
        }
        else {
            $typeSelector.trigger('change');
        }


        editor.saved = true;
        editor.stateChange();
    };

    window.onbeforeunload = function(){
        return editor.saved ? undefined : "You will lost the changes if you leave this page.";
    };
};