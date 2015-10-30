(function ($) {
    var arrayCheckBoxNameRegExp = /(\w*)\[(\w*)]/;

    var setValueByInputName = function(data, name){

    };

    $.fn.extend({
        renderInputsData: function (data) {
            var $this = $(this);
            $this.find("input[type!='checkbox'],select,textarea").each(function(){
                var $input = $(this);
                var name = $input.attr("name");
                if(name && data[name]){
                    $input.val(data[name]);
                }
            });
            $this.find(".HTMLEditor").each(function(){
                var $area = $(this);
                var name = $area.attr("name");
                if(name && data[name]){
                    $area.html(data[name]);
                }
            });
        },
        collectInputsData: function(){
            var data = {};
            var $this = $(this);
            $this.find("input[type!='checkbox'],select,textarea").each(function(){
                var $input = $(this);
                var name = $input.attr("name");
                if(name){
                    data[name] = $input.val();
                }
            });

            $this.find(".HTMLEditor").each(function(){
                var $area = $(this);
                var name = $area.attr("name");
                if(name){
                    data[name] = $area.html();
                }
            });
            return data;
        }
    });
})(jQuery);

//http://stackoverflow.com/questions/5020695/jquery-draggable-element-no-longer-draggable-after-drop
$.ui.draggable.prototype.destroy = $.noop();

/**
* Class of palette item
* @param {$} $liPrototype JQuery object to be used as prototype for items
* @param {function} collectDataCallback function to collect data from JQ
* object. Takes 1 argument with this object and returns `object`
* @param {function} renderDataCallback function to render data into JQ object.
* Takes 2 arguments: JQObject and object with data.
* @constructor
*/
PaletteItem = function($liPrototype, collectDataCallback, renderDataCallback, addedToStackCallback){
    this.$liPrototype = $liPrototype;
    this.collectDataCallback = collectDataCallback || function(){return{}};
    this.renderDataCallback = renderDataCallback || $.noop;
    this.addedToStackCallback = addedToStackCallback || function($liPrototype){
        initAccordion($liPrototype);
    };
};

/**
* Class of palette
* @param {string} id id of palette
* @param $palette JQ object to store palette items. Probably &lt;ul&gt;
* @param $stacks array or single JQ object to be connected with this palette
* @param {bool} cloneInStack <i>true</i> if item could be added to stack twice
* @param {function(key, item)} select$liPrototypeCallback function to select JQ
* element from palette to be prototype for item in stack when render data. Takes 2
* arguments: key and data linked to item
* @param {Object} draggableOptions options to add\override draggable options
* @constructor
*/
Palette = function(id, $palette, $stacks, cloneInStack,
                   select$liPrototypeCallback, draggableOptions){
    this.id = id;
    this.$palette = $palette;
    this.$stacks = $stacks;
    this.cloneInStack = cloneInStack;
    var defaultDraggableOptions = {
        helper: function(e, ui){
            var $original = $(this);
            var $item = $original.clone(true);
            $item.width($(this).width());
            $item.height($(this).height());
            $item.find(".collapsible").css("display", "block");
            return $item;
        },
        connectToSortable: this.$stacks,
        revert:true,
        revertDuration: 1
    };
    this.draggableOptions = $.extend(
        defaultDraggableOptions, draggableOptions || {}
    );
    this._select$liPrototype = select$liPrototypeCallback;
};

/**
* Get rendered JQ item to be added to stack by data
* @param {string} key data key
* @param {Object} data values
* @return JQ item with rendered values by data
*/
Palette.prototype.getRenderedItem = function(key, data){
    var $original = this._select$liPrototype(key, data);
    if(!$original){
        return null;
    }
    var $item = $original.clone(true);
    $item.data("$original", $original);
    var addedToStackCallback = $item.data("addedToStackCallback");
    if(addedToStackCallback){
        addedToStackCallback($item);
    }
    return $item;
};

Palette.prototype.addStack = function(stack){
    var stackElement = stack instanceof $ ? stack.get(0) : stack.$stack.get(0);
    this.$stacks.push(stackElement);
    this.$palette.children("li").draggable("option", "connectToSortable", this.$stacks);
};

/**
* Update collection of palette items
* @param {Array} paletteItems new items
* @see {@link PaletteItem}
*/
Palette.prototype.update = function(paletteItems){
    var $toAppend = $($.map(paletteItems, function(paletteItem, i){
        var $item = paletteItem.$liPrototype.clone(true);

        //$item.removeAttr('id');

        if(!this.cloneInStack){
            $item.bind("addedToStack", function(e){$item.hide();  e.stopPropagation();});
            $item.bind("removedFromStack", function(e){$item.show();  e.stopPropagation();});
        }
        $item.data({
            "collectDataCallback": paletteItem.collectDataCallback,
            "renderDataCallback": paletteItem.renderDataCallback,
            "addedToStackCallback": paletteItem.addedToStackCallback,
            "$original": $item,
            "palette": this
        });
        return $item;
    }.bind(this)));
    var _this = this,
        $current_palette = _this.$palette.empty();

    $.each($toAppend, function(i, item) {
        $(item).draggable(_this.draggableOptions);
        $current_palette.append(item);
    }).bind(this);

    $(this).trigger("updated");
};


/**
* Class of stack
* @param $stack JQ object to contain stack items
* @constructor
*/
Stack = function($stack){
    this.$stack = $stack;
    $stack.sortable({
        forcePlaceholderSize:true,
        items: ".ui-draggable",
        placeholder: "collapsible-placeholder",
        start: function(e,ui){
            ui.placeholder.height(ui.item.outerHeight());
        },
        helper: function(e, element)
        {
            var $originals = element.children();
            var $helper = element.clone(true);
            $helper.children().each(function(index){
                $(this).width($originals.eq(index).width())
            });
            return $helper;
        },
        over: function(e,ui){
            ui.item.css("display", "none");
        },
        revert: false,
        update: function() {$(this).trigger("edited");}.bind(this),
        stop: function() {
            $(this).find('.collapse-button')
                .unbind('click')
                .click(collapseBlock);
        },
        receive: function(event, ui) {
            var $li = $(ui.item);
            var $stackItem = this.$stack.data("uiSortable").currentItem
                .data("$original", $li)
                .trigger("addedToStack");

            $li.data("addedToStackCallback")($stackItem);
            this.register($stackItem);
            return true;
        }.bind(this)
    });
};

/**
* Add some handlers and data needed for usage in stack to
* @param $stackItem item to add handlers
*/
Stack.prototype.register = function($stackItem){
    var $original = $stackItem.data("$original");
    var $this = $(this);
    var data = $original.data();
    var collectDataCallback = data["collectDataCallback"];
    $stackItem.find(".collapsible").css("display", "block");
    $stackItem
        .data("stack", this)
        .data("collectDataCallback", collectDataCallback)
        .bind("removedFromStack", function(e){
        $(this).data("$original").trigger("removedFromStack");
        e.stopPropagation();
    }).dblclick(function(e){
        $(this).removeClass("collapsed");
        e.stopPropagation();
    }).trigger("addedToStack");
    $stackItem.find(".remove:first").click(function(e){
        $stackItem
            .trigger("removedFromStack")
            .remove();
        $this.trigger("edited");
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    $stackItem.find("input[type='checkbox']").click(function(e){
        $this.trigger("edited");
        e.stopPropagation();
    });
    $stackItem.find("a.collapse").click(function(e){
        $stackItem.toggleClass("collapsed");
        e.stopPropagation();
    });
    $stackItem.find("input,textarea").keyup(function(e){
        $this.trigger("edited");
        e.stopPropagation();
    });
    $stackItem.find("select").change(function(e){
        $this.trigger("edited");
        e.stopPropagation();
    });
    $this.trigger("edited");
};

/**
* Clear stack
*/
Stack.prototype.clear = function(){
    var $stack = this.$stack;
    $stack.children().trigger("removedFromStack");
    $stack.empty();
    $(this).trigger("edited");
};

/**
* Returns data collected by all items in stack
* @param {string|undefined} keyFiled. If defined then this field of each
* item data will be used as key in returned object, else will return Array
* @return {object|Array}
*/
Stack.prototype.collectData = function(keyFiled){
    if(keyFiled){
        var data = {};
        $.each(this.$stack.children(), function(i, li){
            var $li = $(li);
            var itemData = $li.data("collectDataCallback")($li);
            data[itemData[keyFiled]] = itemData;
        });
        return data;
    }
    return $.map(this.$stack.children(), function(li){
        var $li = $(li);
        return $li.data("collectDataCallback")($li);
    });
};

//TODO возможно изменить принцип работы здесь
/**
* Render data to stack using specified palette to get items to add
* @param {object|Array} data data to be rendered
* @param {Palette} palette palette to be used to render each item
* @see {@link Palette.getRenderedItem}
*/
Stack.prototype.renderDataUsingPalette = function(data, palette, addToStackCallback){
    this.clear();
    $.each(data, function(key, item){
        var $tmp = palette.getRenderedItem(key, item);
        if(!$tmp){
            return true;
        }
        var $li = $tmp.clone();
        var data = $tmp.data();
        delete data["uiDraggable"];
        $li.data(data);
        this.addItemFromCode($li);
        var renderData = $li.data("renderDataCallback");
        $li.removeAttr('id');
        renderData($li, item);
    }.bind(this));
};

/**
* Add item to then tail of stack
* @param $item item to be added
*/
Stack.prototype.addItemFromCode = function($item){
    this.$stack.append($item);
    $item.data("addedToStackCallback")($item)
    this.register($item);
};

/**
*
* @param {Array} palettes palettes used in editor
* @param {Array} stacks stacks used in editor
* @param {function} collectDataCallback function to collect data. Should return
* object with fields which will be merged to system fields on save. (e.g.
* {code: "{}", other_code_field: "{}"})
* @param {function} renderDataCallback function to render data to editor. Takes
* data object (DB object).
* @param {object|undefined} initData initial data for editor when edit existing
* object or undefined for new object
* @param {function(palette, stack)} paletteUpdatedCallback
* @constructor
*/
Editor = function(palettes, stacks, collectDataCallback, renderDataCallback, initData,
    paletteUpdatedCallback){
    paletteUpdatedCallback = paletteUpdatedCallback || function(palette, stack) {stack.clear();};
    this.palettes = palettes;
    this.stacks = stacks;
    this.collectData = collectDataCallback;
    this.renderData = renderDataCallback;
    this.initData = initData;
    this.isNewObject = !this.initData;
    $.each(palettes, function(i, palette){
        $(palette).bind("updated", function(e){
            $.each(stacks, function(i, stack){
                paletteUpdatedCallback(palette, stack)
            });
            e.stopPropagation();
        })
    });

};

/**
* Class of editor
* @param {int} typeId id of edited type
* @param {string} typeName name of edited type
* @param $nameInput input to edit object display_name
* @param $objectSelect select to change active object (must be filled server
* side, -1 value for new object)
* @param $saveButton button to save current object
* @param $removeButton button to remove current object
* @param {Array} palettes palettes used in editor
* @param {Array} stacks stacks used in editor
* @param {function} collectDataCallback function to collect data. Should return
* object with fields which will be merged to system fields on save. (e.g.
* {code: "{}", other_code_field: "{}"})
* @param {function} renderDataCallback function to render data to editor. Takes
* data object (DB object).
* @param {function} findErrorsCallback function to find errors in DB object
* before save. Takes object to check and returns error string or undefined if
* object is ok;
* @param {object|undefined} initData initial data for editor when edit existing
* object or undefined for new object
* @param {int} schemaId id of schema to save object
* @param {int} setId id of set to save object
* @constructor
* @see {@link Stack}
* @see {@link Palette}
* @param paletteUpdatedCallback
*/
DataObjectEditor = function(typeId, typeName, $nameInput, $objectSelect,
                  $saveButton, $removeButton, palettes, stacks,
                  collectDataCallback, renderDataCallback, findErrorsCallback,
                  initData, schemaId, setId, paletteUpdatedCallback){
    if(!arguments.length){
        return;
    }
    this.apiKey = window.session["api_key"];
    this.API_URL = window.flexiweb.site_url + "/api/v1/";
    this.CALL_FUNC_URL = window.flexiweb.site_url + '/ajax/call_fx_func.php';
    this.typeId = typeId;
    this.typeName = typeName;
    this.$nameInput = $nameInput;
    this.$objectSelect = $objectSelect;
    this.$saveButton = $saveButton;
    this.$removeButton = $removeButton;
    Editor.call(this, palettes, stacks, collectDataCallback, renderDataCallback, initData, paletteUpdatedCallback);
    this.findErrors = findErrorsCallback;
    this.setId = setId;
    this.schemaId = schemaId;
    this._firstPaletteRender = true;
    var editor = this;
    var _saved;
    $.each(stacks, function(i, stack){
        $(stack).bind("edited", function(){
            editor.saved = false;
        });
    });
    $saveButton.click(this.save.bind(this));
    $removeButton.click(this.remove.bind(this));

    if(this.isNewObject)
        $removeButton.attr("disabled", true);

    $objectSelect.change(function(){
        var id = $(this).val();
        if(id >= 0){
            location.search = "object_id="+id;
        } else {
            location.search = "";
        }
    });
    Object.defineProperty(this, "saved", {
        get: function() {return _saved; },
        set: function(val){
            if(val){
                this.$saveButton.attr("disabled", true);
            } else {
                this.$saveButton.removeAttr("disabled");
            }
            _saved = val;
        }.bind(this)
    });
    this.saved = true;

    window.onbeforeunload = function(){
        return _saved ? undefined : "You will lost the changes if you leave this page.";
    }
};

DataObjectEditor.prototype.addStack = function(stack){
    this.stacks.push(stack);
    $(stack).bind("edited", function(){
        this.saved = false;
    }.bind(this));
    $.each(this.palettes, function(i, palette){
        palette.addStack(stack.$stack);
    })
};

DataObjectEditor.prototype.renderInitData = function(){
    if(this.initData && !this.isNewObject){
        this.renderData(this.initData);
        this.$nameInput.val(this.initData.display_name);
    }
    this.saved = true;
};

/**
* Check is string valid to be display name
* @param {string} name
* @return {string|undefined}
*/
DataObjectEditor.prototype.validateName = function(name){
    return name.length > 0 ? undefined : "Empty name";
};

/**
* Save current object in DB. Note: Will reload page if it's new object
*/
DataObjectEditor.prototype.save = function(){
    var _this = this;
    var name = this.$nameInput.val();
    if(!name){
        this.displayErrors("Please, enter form name.");
        return;
    }

    var data = $.extend({
        display_name: name,
        object_id: (this.isNewObject? undefined: _this.$objectSelect.val()),
        object_type_id: this.typeId,
        set_id: this.setId,
        schema_id: parseInt(window.session['current_schema']),
        api_key : this.apiKey
    },this.collectData());

    var post_data = {
        function: (this.isNewObject? "add_object": "update_object"),
        object_array: JSON.stringify(data),
        api_key: this.api_key
    };
    var errors = this.findErrors(data);
    if(errors){
        this.displayErrors(errors);
        return;
    }

    if(data.isPagesEditor && this.isNewObject) {
        post_data.function =  'add_app_version';
        post_data.object_array = undefined;
        post_data.app_array = JSON.stringify(data);
    }

    $.ajax({
       url: this.CALL_FUNC_URL,
       type: "POST",
       data: post_data,
       success: function(response){
           response = JSON.parse(response);
           //try {  }
           //catch(e) { console.log(e.message);}

           if(response.errors){
               _this.displayErrors(response.errors);
           } else {
               if(_this.isNewObject){
                   window.onbeforeunload = undefined;
                   //window.location.search = "object_id=" + response;
               } else {
                   _this.saved = true;
               }
           }
       }
    });
};

/**
* Remove current object from DB. Will reload page
*/
DataObjectEditor.prototype.remove = function(){
    var _this = this;
    $.ajax({
        url: this.CALL_FUNC_URL,
        type: "POST",
        data: {
            function: 'delete_object',
            object_id: _this.$objectSelect.val(),
            object_type_id: this.typeId,
            api_key : this.apiKey
        },
        success: function(response){
            response = JSON.parse(response);
            if(response.errors){
                _this.displayErrors(response.errors);
            } else {
                window.onbeforeunload = undefined;
                window.location.search = "";
            }
        }
    });
};

/**
* Display errors information
* @param errors
*/
DataObjectEditor.prototype.displayErrors = function(errors){
    if(typeof errors == "object"){
        $.each(errors, function(k, v){
            alert(k + "\n" + v);
        });
    } else {
        alert(errors);
    }
};


