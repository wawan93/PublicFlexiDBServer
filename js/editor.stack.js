/**
 * Class of stack editor
 * @param {$}           $stack          main editor area
 * @param {$|undefined} $palette        palette of elements
 * @param {$|undefined} $buffer         copy-paste buffer
 * @param {function}    addCallback     callback function for added items
 * @param {function}    editCallback    fire on any changes in `$stack`
 * @constructor
 */
var StackEditor = function($stack, $palette, $buffer, addCallback, editCallback, draggableOptions){
    var _this = this;
    this.$stack = $stack;
    this.$palette = $palette;
    this.$buffer = $buffer;

    this.showAndHideElems = function() {
        var $elemsInStack = _this.getItems().find('.field-name');
        $palette.find('.palette-item, .collapsible-block').show();
        $elemsInStack.each(function(key, value) {
            $palette.find('.field-name:contains("' +  $(value).text() + '")').parents('.palette-item, .collapsible-block').hide();
        })

    }

    this.updatePalette = function(){
        var defaultDraggableOptions = {
            helper: function(e, ui){
                return $(this).clone().width($(this).width());
            },
            connectToSortable: $stack,
            revert:"invalid",
            revertDuration: 1
        };

        draggableOptions = $.extend(defaultDraggableOptions, draggableOptions);
        $palette.find(".palette-item, .collapsible-block").draggable(draggableOptions);

        _this.showAndHideElems();
    };

    this.$stack.sortable({
        forcePlaceholderSize:true,
        items: ".palette-item:not(.ui-disabled)",
        placeholder: "placeholder",
        start: function(e,ui){
            ui.placeholder.height(ui.item.outerHeight());
        },
        helper: function(e, element)
        {
            var $originals = element.children();
            var $helper = element.clone();
            $helper.children().each(function(index){
                // Set helper cell sizes to match the original sizes
                $(this).width($originals.eq(index).width())
            });
            return $helper;
        },
        over: function(e,ui){
            ui.item.css("display", "none");
        },
        revert:false,
        update:function (event, ui) {
            var $target = $(ui.item);
            //$target.css("display", "table-row");
            _this.editCallback();
            if (!$target.hasClass("in-buffer") && _this.addCallback) {
                _this.addCallback($target);
            }
            _this.updateStackUI();
        },
        stop: function(e, ui) {
            $(this).find('.collapse-button').unbind('click');
            $(this).find('.collapse-button').click(collapseBlock);
        },
        connectWith: $buffer//"#buffer"
    });

    if(this.$palette){
        this.updatePalette()
    }
    if($buffer){
        $buffer.droppable({
            drop:function (event, ui) {
                var source = $(ui.draggable);
                var clone = source.clone()
                    .show()
                    .addClass("in-buffer")
                    .removeClass("in-stack")
                $(this).append(clone);
                clone.draggable({
                    helper:"clone",
                    connectToSortable:$stack,
                    revert:"invalid",
                    cancel:".mapPreview, input, button"
                });
                clone.disableSelection();
                this.updateBufferUI();
            },
            items: ":not(.ui-disabled)",
            tolerance:"pointer",
            accept:function (item) {
                return item.hasClass("in-stack") && !item.hasClass("in-buffer")
            }

        });
    }

    this.addHandlers = function($item){
        var _this = this;
        $item.find(".remove").click(function(){
            _this.remove($item);
        });
        $item.dblclick(function(){
            if($item.is(".collapsed")){
                $item.removeClass("collapsed");
            }
        });
        $item.find("a.collapse").click(function(){
            $item.toggleClass("collapsed");
        });
    };

    this.addCallback = function($item){
        anyInputChange($item, this.editCallback);
        $item.addClass("in-stack");
        this.addHandlers($item);
        if(addCallback){
            addCallback($stack,$item)
        }
    };
    this.editCallback = function(){
        $(this).trigger("edit");
        if(editCallback){
            editCallback();
        }
    };

};


StackEditor.prototype.updateStackUI = function(){
    if(this.$stack.children(":not(.hide-in-stack)").length == 0){
        this.$stack.addClass("empty");
    } else {
        this.$stack.removeClass("empty");
    }
};

StackEditor.prototype.updateBufferUI = function(){
    if(this.$buffer.children(":visible").length == 0){
        this.$buffer.addClass("empty");
    } else {
        this.$buffer.removeClass("empty");
    }
};

/**
 * Get current content of stack
 * @return {$} items
 */
StackEditor.prototype.getItems = function(){
    return this.$stack.children("*");
};

/**
 * Clear stack
 */
StackEditor.prototype.clear = function(){
    this.$stack.empty();
    $(this).trigger("edit");
    this.updateStackUI();
    if(this.$buffer){
        this.$buffer.empty();
        this.updateBufferUI();
    }
};

/**
 * Add item to stack
 * @param {$} $item item to add
 */
StackEditor.prototype.add = function($item){
    this.$stack.append($item);
    this.addCallback($item);
    this.editCallback($item);
    this.updateStackUI();
    $(this).trigger("edit");
};

/**
 * Remove item from stack
 * @param $item item to remove
 */
StackEditor.prototype.remove = function($item){
    this.editCallback($item);
    $item.remove();
    this.updateStackUI();
    $(this).trigger("edit");
};

StackEditor.prototype.addHandlersToExistItems = function(){
    var _this = this;
    anyInputChange(this.$stack, this.editCallback);
    $.each(this.$stack.find("li"), function(i, item){
        _this.addHandlers($(item));
    });
};

