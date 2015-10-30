// OLD INIT
//var window.flexiweb.site_url = function(){ return window.flexiweb.site_url; }
window.FXUI = {};

if(typeof window.flexiweb === 'undefined' || window.flexiweb == null) {
    window.flexiweb = { site_url : '' };
}


var initTinyMCE = function() {
    if(typeof tinyMCE === 'undefined') return;

    tinyMCE.init({
        mode:"textareas",
        theme:"advanced",
        //editor_deselector : "mceNoEditor, #CodeMirrorWindow textarea",
        editor_selector : "mceEditor",
        entity_encoding : "raw",
        plugins:"emotions,spellchecker,advhr,insertdatetime,preview",
        theme_advanced_buttons1:"newdocument,|,bold,italic,underline,|,justifyleft,justifycenter,justifyright,fontselect,fontsizeselect,formatselect",
        theme_advanced_buttons2:"bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,|,code,preview,|,forecolor,backcolor",
        theme_advanced_toolbar_location:"top",
        theme_advanced_toolbar_align:"left",
        theme_advanced_statusbar_location:"bottom"
    });

}

initTinyMCE();

/* UTILS */
Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};
var hasOwnProperty = Object.prototype.hasOwnProperty;
function isEmpty(obj) {

    // null and undefined are "empty"
    if (obj == null) return true;

    // Assume if it has a length property with a non-zero value
    // that that property is correct.
    if (obj.length > 0)    return false;
    if (obj.length === 0)  return true;

    // Otherwise, does it have any properties of its own?
    // Note that this doesn't handle
    // toString and valueOf enumeration bugs in IE < 9
    for (var key in obj) {
        if (hasOwnProperty.call(obj, key)) return false;
    }

    return true;
};
function getObjectSize(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};
function sortObject(obj) {
    var arr = [];
    for (var prop in obj) {
        if (obj.hasOwnProperty(prop)) {
            arr.push({
                'key': prop,
                'value': obj[prop]
            });
        }
    }
    arr.sort(function(a, b) { return a.value - b.value; });
    //arr.sort(function(a, b) { a.value.toLowerCase().localeCompare(b.value.toLowerCase()); }); //use this to sort as strings
    return arr; // returns array
}

function isBlank(str) {
    return (!str || /^\s*$/.test(str));
}
function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

arrayKeys = function(arr){return $.map(arr, function(v, k){return k;});};

//http://habrahabr.ru/post/149581/
//https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind?redirectlocale=en-US&redirectslug=JavaScript%2FReference%2FGlobal_Objects%2FFunction%2Fbind
Function.prototype.bind = Function.prototype.bind || function (oThis) {
    if (typeof this !== "function") {
        throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
    }
    var aArgs = Array.prototype.slice.call(arguments, 1),
        fToBind = this,
        fNOP = function () {},
        fBound = function () {
            return fToBind.apply(this instanceof fNOP && oThis
                    ? this
                    : oThis,
                aArgs.concat(Array.prototype.slice.call(arguments)));
        };
    fNOP.prototype = this.prototype;
    fBound.prototype = new fNOP();
    return fBound;
};

$(document).ready(function() {
    if (typeof jQuery.ui != 'undefined') {
        // UI loaded
        $("#fieldsTable").sortable({
            axis:'y',
            items: "li:not(.ui-disabled)",
            placeholder: "placeholder",
            start: function(e,ui){
                ui.placeholder.height(ui.item.height());
            },
            helper: function(e, element)
            {
                var $originals = element.children();
                var $helper = element.clone();
                $helper.children().each(function(index)
                {
                    // Set helper cell sizes to match the original sizes
                    $(this).width($originals.eq(index).width())
                });
                return $helper;
            }
        });
        $('#fieldsTable .button input').unbind('change').bind('change', function() {
            var $this = $(this);
            var $parent = $(this).parents('.button');

            if(!$parent.hasClass('green')) {
                $parent.addClass('green');
                $this.attr('checked','checked');
                $this.parents('.palette-item').css('background', '#ddffdd');
            } else {
                $parent.removeClass('green');
                $this.removeAttr('checked');
                $this.parents('.palette-item').css('background', '#efefef');
            }
        });
        $("._date").datepicker();
    }

    $('input.criteria').keypress(function(e){
        if(e.which == 13) { submit_explorer_filter(this, window.location.origin+''+window.location.pathname ) }
    });

    $(".delete").unbind('click').bind('click', function() {
        console.log('qq');
        if (confirm('Are you sure you want to delete this instance?')) {
            $(this).parent().submit();
        }
    });
    $(".unlink").unbind('click').bind('click', function() {
        if (confirm('Are you sure you want to unlink these objects?')){
            $(this).parent().submit();
        }
    });
    $(".HTMLEditor").addClass("mceNoEditor");

    if( inIframe() ) {
        var iframe = window.parent.document.getElementById('iframe');
        $(iframe).load(function() {
            $.each($('.toggle_advanced'), function() {
                initToggle(this, function() {
                    iframe.height = $('#iframe_inner_wrapper').outerHeight() + 'px';
                });
            });
        });
    }
    else {
        $.each($('.toggle_advanced'), function() {
            initToggle(this);
        });
    }
});

/* GENERAL FUNCTIONS */
function doWithConfirmation(callback, text, afterClose) {
    var $conformationWindow = $("#conformationWindow");
    //TODO change to template
    if ($conformationWindow.length == 0) {
        $conformationWindow = $('<div id="conformationWindow">\
            <div id="conformationWindowText"></div>\
            <a href="#" id="conformationButton" class="button green">Yes, do it</a>\
            <a href="#" class="button red" \
            onclick="$(this).parent(\'div\').dialog(\'close\');">No</a>\
        </div>');
        $("body").append($conformationWindow);
        $conformationWindow.dialog({
            autoOpen:false,
            modal:true,
            title:"Conformation"
        });
    }
    var $conformationButton = $conformationWindow.find("#conformationButton");
    $conformationWindow.find("#conformationWindowText").html(text);
    $conformationWindow.dialog("open");
    $conformationButton.unbind("click");
    $conformationWindow.unbind("dialogclose");
    if (afterClose) {
        $conformationWindow.bind("dialogclose", function (event, ui) {
            afterClose();
        });
    }
    $conformationButton.click(function () {
        callback();
        $conformationWindow.dialog("close");
    });
};
function renderResponse (data, successMsg) {
    if(data.errors) {
        var $errors_text = 'Errors: \n';
        $.each(data.errors, function(error_code, errors) {
            $.each(errors, function(index, error) {
                $errors_text += (index + '. ' + error +"\n");
            });
        });
        alert($errors_text);
    }
    else {
        alert(successMsg);
        console.log(data);
    }
};
function collapseBlock($item) {
    var $elem = $(this).closest(".collapsible-block");

    if($item instanceof jQuery)
        $elem = $item.closest(".collapsible-block");

    if($elem.hasClass("open")) {
        $elem.find('.collapsible:first').hide('blind');
        $elem.removeClass("open");
    } else {
        $elem.addClass("open");
        $elem.find('.collapsible:first').show('blind');
    }
}
function anyInputChange($container, callback){
    $container.find("input, textarea").keydown(callback);
    $container.find("input[type='checkbox']").click(callback);
    $container.find("select").change(callback);
}

/* UNSORTED */

function submitTypeForm(form) {
    $('select').unbind('change');
    var i = 0;
    $.each($('#fieldsContainer .palette-item'), function() {
        $.each($(this).find('._field'), function() {
            var currentName = this.name;
            if(typeof currentName !== 'undefined' && currentName != null && currentName != '') {
                var group = currentName.split('[')[0];
                var item = currentName.split('[')[1].replace(']','');
                this.name = group + '[' + i + ']' +'[' + item + ']'
            }
        });
        i++;
    });

    form.submit();
}
function _default_value_change(){
    var $item = $(this);

    var defaultValues = window.defaultValues;
    if(defaultValues == undefined) return false;

    var $alias = $item.parents('.accordion').find('input[name=alias]');

    if($item.attr('name') == 'aggregation' && $item.val() != '') {
        var alias = $item.parents('.palette-item').attr('data-type-name')+'_'+
            $item.parents('.palette-item').find('.field-name').text();
        $alias.val(alias + '_' + $item.val());
    }
    if($item.val() != defaultValues[$item.attr('name')] && defaultValues[$item.attr('name')] != undefined) {
        $item.parents('.dd').prev('.dt').find('a').css('color','#cc4444');
    } else {
        $item.parents('.dd').prev('.dt').find('a').css('color','#000000');
    }
}
function initAccordion(item) {
    var allPanels = item.find('.accordion  .dd').hide();
    item.find('.accordion input, .accordion select')
        .bind('change', _default_value_change);
    item.find('.accordion input, .accordion select').each(_default_value_change);
    item.find('.accordion  .dt > a').click(function () {
        allPanels.hide();
        $(this).parent().siblings('.dt').find('a').css('background', '#cdcdcd');
        $(this).parent().next().show();
        $(this).css('background', '#eeeeee');
        return false;
    }).first().click();
}

function SetDefaults(element) {
    var $default = element.parents('.accordion').find('.dd:last-child');
    $default.empty();
    if(parseInt(element.val())>0) {
        var args = {
            data:{
                function: 'get_enum_fields',
                enum_type_id: element.val()
            }
        };
        flexiweb.callFunction.generalRequest(args, function(resp){
            var $select = $('<select class="_field" name="fields[default_value]" ></select>');
            $default.append($select);
            $.each(resp,function(key,value){
                $select.append('<option value="'+key+'">'+value+'</option>');
            });
        });
    } else {
        $default.html('<input class="_field" name="fields[default_value]" type="text" value="" placeholder="Default">');
    }
}
function addTypeField(wrap){
    $.ajax({
        url: window.flexiweb.site_url + 'ajax/add_type_field.php',
        success: function (html) {

            $('#li-no-fields', wrap).hide();

            var $li = $(html),
                dd = $li.find('.type_select_dropdown'),
                $nested = $li.find('.nested');
            $select = $nested.find('select', '.nested');

            initAccordion($li);
            dd.bind('change',function(e) {

                if (dd.val() == 'enum') {
                    dd.css('width','200px');
                    dd.removeAttr('name');
                    dd.removeClass('_field');

                    $nested.show();

                    $select.attr('name','fields[type]');
                    $select.addClass('_field');
                }
                else {
                    dd.css('width','calc(100% - 120px)');
                    dd.attr('name','fields[type]');
                    dd.addClass('_field');

                    $nested.hide();

                    $select.removeAttr('name');
                    $select.removeClass('_field');
                }
            });
            $li.appendTo(wrap);

            $('#fieldsTable .button input').unbind('change');
            $('#fieldsTable .button input').change(function() {
                var $this = $(this);
                var $parent = $(this).parent('.button');

                if(!$parent.hasClass('green')) {
                    $parent.addClass('green');
                    $this.find('input').attr('checked','checked');
                    $this.parents('.palette-item').css('background', '#ddffdd');
                } else {
                    $parent.removeClass('green');
                    $this.find('input').removeAttr('checked');
                    $this.parents('.palette-item').css('background', '#efefef');
                }
            });
        }
    });
}
function colorPickerInit () {
    $('.colorpicker').minicolors({
        opacity: true,
        changeDelay: 100,
        change: function(hex, opacity) {
            $(this).closest('li').find('input[name="fields[opacity]"]').val(opacity);
        }
    })
}

function addEnumField(wrap) {
    $.ajax({
        url: window.flexiweb.site_url + 'ajax/add_enum_field.php',
        success: function(html){
            $(html).appendTo(wrap);
            colorPickerInit();
        }
    });
}
function addMetricUnit(wrap) {
    $.ajax({
        url: window.flexiweb.site_url + 'ajax/add_metric_unit.php',
        success: function(html){
            $(html).appendTo(wrap);
        }
    });
}

function removeTypeField(field) {
    var wrap = $(field).parents('ul');

    $(field).parents('li').remove();

    if ($('li', wrap).length <= 1) {
        $('#li-no-fields', wrap).show();
    }
}
function showRolesPermissions(objectTypeID) {
    if ($('#dialog').length == 0) {
        var dialog = $('<div>', {'class': 'dialog', 'id': 'dialog'})
        $('body').append(dialog)
    } else {
        var dialog = $('#dialog')
    }
    dialog.html('')
    dialog.load(window.flexiweb.site_url + 'ajax/type_roles_permissions.php?object_type_id='+objectTypeID)
//    dialog.html('<iframe src="'+window.flexiweb.site_url + 'ajax/type_roles_permissions.php?object_type_id='+objectTypeID+'"></iframe>')
    dialog.dialog({'title': 'Roles Permissions', 'width': '530px', 'maxHeight': '600px', modal: true})
}
function testPassword(passwd) {
    var intScore   = 0;
    var strVerdict = "weak";

    // PASSWORD LENGTH
    if (passwd.length == 0) {intScore = 0;}                                  // blank password
    else if (passwd.length<5) {intScore +=3;}                       // length 4 or less
    else if (passwd.length>4 && passwd.length<8) {intScore +=6;}    // length between 5 and 7
    else if (passwd.length>7 && passwd.length<16) {intScore +=12;}  // length between 8 and 15
    else if (passwd.length>15){ intScore +=18;}                     // length 16 or more

    // LETTERS (Not exactly implemented as dictacted above because of my limited understanding of Regex)
    if (passwd.match(/[a-z]/)) {intScore +=1;}                   // [verified] at least one lower case letter
    if (passwd.match(/[A-Z]/)) {intScore +=5;}                   // [verified] at least one upper case letter

    // NUMBERS
    if (passwd.match(/\d+/)) {intScore +=5;}                     // [verified] at least one number
    if (passwd.match(/(.*[0-9].*[0-9].*[0-9])/)) {intScore +=5;} // [verified] at least three numbers

    // SPECIAL CHAR
    if (passwd.match(/.[!,@,#,$,%,^,&,*,?,_,~]/)) {intScore +=5;} // [verified] at least one special character
    if (passwd.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/)){intScore +=5; } // [verified] at least two special characters

    // COMBOS
    if (passwd.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) {intScore +=2;} // [verified] both upper and lower case
    if (passwd.match(/([a-zA-Z])/) && passwd.match(/([0-9])/)) {intScore +=2;} // [verified] both letters and numbers
    if (passwd.match(/([a-zA-Z0-9].*[!,@,#,$,%,^,&,*,?,_,~])|([!,@,#,$,%,^,&,*,?,_,~].*[a-zA-Z0-9])/)){intScore +=2; }// [verified] letters, numbers, and special characters

    if(intScore == 0) {strVerdict = "";}
    else if(intScore < 16){strVerdict = "<span color=maroon>very weak</span>";}
    else if (intScore > 15 && intScore < 25){strVerdict = "<span color=red>weak</span>";}
    else if (intScore > 24 && intScore < 35){strVerdict = "<span color=DarkOrange>mediocre</span>";	}
    else if (intScore > 34 && intScore < 45){strVerdict = "<span color=olive>strong</span>";}
    else {strVerdict = "<span color=green>stronger</span>";}

    document.getElementById('strengthVerdict').innerHTML = strVerdict;
}
function rewrite_days() {
    var days = document.getElementById('day');
    var month = document.getElementById('month');
    var year = document.getElementById('year');
    var days_in_month = new Array(31,28,31,30,31,30,31,31,30,31,30,31);
    if (month.value != 0)
    {
        if ((year.value % 4 == 0) && (month.value == 2))
        {
            days.length = 30;
            days.item(29).value = 29;
            days.item(29).text = 29;
        }
        else
        {
            days.length = days_in_month[month.value - 1] + 1;
            for (var i = 29; i < days.length; i++)
            {
                days.item(i).value = i;
                days.item(i).text = i;
            }
        }
    }
}
function getTypesArrayFromJoinedTypes (jsonData, keys) {
    $.each(jsonData, function(key, value) {
        keys.push(key);

        if($.isArray(value)) {
            $.each(value, function(index, joinedType) {
                keys.push(joinedType)
            })
        }
        else {
            keys.push(value);
        }
    })


}


$.flatten = function (obj){
    var ret = {}
    for (var key in obj){
        if (typeof obj[key] == 'object'){
            var fobj = $.flatten(obj[key]);
            for (var extkey in fobj){
                ret[key+"."+extkey] = fobj[extkey];
            }
        } else {
            ret[key] = String(obj[key]);
        }
    }
    return ret;
};

$.fn.fx_accordion = function() {
    var $accordion = $(this),
        submenus_class = 'submenu',
        category_class = 'category',
        activeClass = 'active',
        $submenus = $accordion.find('.' + submenus_class),
        $accordion_points = $accordion.find('.' + category_class), $old;


    $accordion.addClass('accordion');
    $submenus.hide();

    $accordion_points.click(function () {
        var $this = $(this), $current = $(this).next('.' + submenus_class), $old = undefined;

        $.each($submenus, function(key, value) {
            if($(this).hasClass(activeClass)) {
                $old = $(this);

                $old.hide().slideToggle('fast','swing').removeClass(activeClass);
                $old.prev('.' + category_class).removeClass(activeClass);
            }
        })

        if(!$current.is($old)) {
            $current.addClass(activeClass).slideToggle('fast','swing');
            $this.addClass(activeClass);
        }
    });

};
$.fn.toDeepJson = function() {
    function parse(val){
        if (val == ""){ return null; }
        if (val == "true"){ return true; }
        if (val == "false"){ return false; }
        return val;
    }
    function toNestedObject(obj, arr){
        var key = arr.shift();
        if (arr.length > 0) {
            obj[key] = toNestedObject(obj[key] || {}, arr);
            return obj;
        }
        return key;
    }
    if (this.length == 1){
        return $.makeArray(this[0].elements)
            .filter(function(e){
                return e.name != "" && (e.type == 'radio' ? e.checked : true);
            })
            .map(function(e){
                var names = e.name.split('.');
                if (e.type == 'checkbox') {
                    e.value = e.checked;
                }
                names.push(parse(e.value));
                return names;
            })
            .reduce(toNestedObject, {});
    } else {
        throw({error:"Can work on a single form only"})
    }
};
$.fn.serializeObject = function() {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

$.fn.getXCoordinate = function() {
    return parseInt($(this).css('left'));
};
$.fn.getYCoordinate = function() {
    return parseInt($(this).css('top'));
};
$.fn.getCoordinates = function() {
    return {x: parseInt($(this).css('left')), y: parseInt($(this).css('top'))};
};

$.fn.masterCheckbox = function ($slaveCheckboxes, defaultChecked) {
    var slaveCount = $slaveCheckboxes.length;
    var $this = $(this);
    if(defaultChecked){
        $this.attr("checked", true);
        $slaveCheckboxes.attr("checked", true);
    } else {
        $slaveCheckboxes.removeAttr('checked');
        $this.removeAttr('checked');
    }
    $this.click(function(){
        if($this.is(":checked")){
            $slaveCheckboxes.filter(":not(:checked)").trigger("click");
        } else {
            $slaveCheckboxes.removeAttr('checked');
        }
    });
    $slaveCheckboxes.click(function(){
        var checkedCount = $slaveCheckboxes.filter(":checked").length;
        if(checkedCount == slaveCount)
        {
            $this.attr("checked", true);
        } else {
            $this.removeAttr("checked");
        }
    });
};

/* IMAGE SELECTOR */
(function ($) {
    /**
     * Use methods of this object for storage data between invocations. JQuery
     * `data` method has problems when element moving in DOM
     * @type {Object}
     */
    var uuid = function(){
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
            return v.toString(16);
        });
    }

    Data = {
        CommonDataHash : {},

        clone: function(oldDiv, newDiv){
            var newId = uuid();
            newDiv.attr("data-id", newId);
            var oldData = Data.CommonDataHash[Data.getId(oldDiv, true)];
            //TODO normal clone
            if(oldData != undefined){
                Data.CommonDataHash[newId] = $.extend(true, {}, oldData);
            }
        },

        getId: function(target ,canAddNew){

            var id = target.attr("data-id");
            if(id !== undefined){
                return id;
            }

            if(canAddNew == true){
                id = uuid();
                target.attr("data-id", id);
                return id;
            }
            return undefined;
        },

        save: function (owner, key, value){
            var id = Data.getId(owner, true);
            if(!Data.CommonDataHash.hasOwnProperty(id)){
                Data.CommonDataHash[id] = {}
            }
            Data.CommonDataHash[id][key] = value;
        },

        load: function (owner, key){
            var id = Data.getId(owner);
            if(id === undefined){
                return undefined;
            }
            if(Data.CommonDataHash.hasOwnProperty(id)){
                return Data.CommonDataHash[id][key];
            } else {
                return undefined;
            }
        }
    };

    var mimeImage = window.flexiweb.site_url + "images/mime_image.png";

    var methods = {
        init:function (options, previewImg) {
            var defaults = {
                buttonText:"Select image",
                labelText:"Image",
                placeholderSize : "small",
                callback: function(url, path){}
            };
            options = $.extend(defaults, options);
            $(this).data("options", options);

            return this.each(function () {
                var $this = $(this);
                $this.removeAttr("data-id");
                Data.save($this, "placeholder", mimeImage);
                var valueToSave = undefined;
                var pathToSave = undefined;
                var $predTag = $this.find("img");
                if(options.imageUrl && options.path){
                    valueToSave = options.imageUrl;
                    pathToSave = options.path;
                    Data.save($this, "empty", false);
                } else if ($predTag.length > 0 && $predTag.attr("src") != undefined) {
                    valueToSave = $predTag.attr("src");
                    pathToSave = $predTag.attr("data-path");
                    Data.save($this, "empty", false);
                } else if (!isBlank($this.text()) || $this.text() == "%IMAGEURL%") {
                    var parts = $this.text().split(";");
                    valueToSave = parts[0];
                    pathToSave = parts[1];
                    $this.text("");
                    Data.save($this, "empty", false);
                } else {
                    Data.save($this, "empty", true);
                }

                $this.css("overflow", "auto");
                $this.empty();
                //noinspection JSJQueryEfficiency
                var $selectBtn = $("<a href='#'></a>")
                    .attr("class", "button blue select-image-button")
                    .text(options.buttonText)
                    .click(function(){
                        $(this).parent().ImageSelector('showWindow')
                    });
                $this.append($selectBtn);

                //noinspection JSJQueryEfficiency
                var $unSelectBtn = $("<a href='#'></a>")
                    .attr("class", "button red")
                    .text("Select nothing")
                    .click(function(){
                        $this.ImageSelector("setImage", 'none');
                    });
                $this.append($unSelectBtn);

                if(previewImg == undefined){
                    var imgElement = $('<img class="image-preview-inner">');
                    $this.append(imgElement);
                    Data.save($this, "previewImg", imgElement);
                } else {
                    Data.save($this, "previewImg", previewImg);
                }
                //TODO change

                $this.ImageSelector("setImage", valueToSave, pathToSave);
            });
        },
        getURL: function(){
            var $this = $(this);
            var empty = Data.load($this, "empty");
            var previewImg = Data.load($this, "previewImg");
            if (empty == false){
                return previewImg.attr("src");
            } else {
                return undefined;
            }
        },
        getPath: function(){
            var $this = $(this);
            var empty = Data.load($this, "empty");
            var previewImg = Data.load($this, "previewImg");
            if (empty == false){
                return previewImg.attr("data-path");
            } else {
                return undefined;
            }
        },
        setImage: function(newURL, newPath){
            var $this = $(this);
            var previewImg = Data.load($this, "previewImg");
            var callback = $this.data("options")["callback"];
            if (newURL) {
                previewImg.unbind("load");
                previewImg.attr("src", newURL);
                previewImg.attr("data-path", newPath);
                Data.save($this, "empty", false);
                if(callback){
                    callback(newURL, newPath);
                }
            } else {
                var placeholder = Data.load($this, "placeholder");
                Data.save($this, "empty", true);
                previewImg.attr("src", placeholder);
                previewImg.removeAttr("data-path");
                if(callback){
                    callback(undefined, undefined);
                }
            }
        },
        showWindow: function () {
            var $this = $(this);
            var previewImg = Data.load($this, "previewImg");
            show_schema_images($.session("current_schema"), function(url, path){
                $this.ImageSelector("setImage", url, path);
            });
        },
        prepareBody:function () {
            var selectImageWindow = $('<div style="display: none;" id="selectImageWindow" >             \
                    <div id="uploadedImagesList"  style="overflow-y:auto; max-height: 500px;"></div>       \
                    <a href="#" class="button red cancel-button">Cancel</a>          \
                    <a href="#" class="button green btn-primary select-button">Select</a>                   \
                    <a href="#" class="button gray btn-danger select-nothing-button">Select nothing</a>\
            </div>');
            var $this = $(this);
            $this.append(selectImageWindow);
            selectImageWindow.find(".cancel-button")
                .click(function () {
                    selectImageWindow.dialog("close");
                });

            $("#selectImageWindow").dialog({
                autoOpen: false,
                modal: true,
                title: "Select image",
                height: 600,
                width: 700
            });
            return this;
        }
    };

    $.fn.extend({
        ImageSelector:function (method) {
            if (methods[method]) {
                return methods[ method ]
                    .apply(this, Array.prototype.slice.call(arguments, 1));
            } else if (typeof method === 'object' || !method) {
                return methods.init.apply(this, arguments);
            } else {
                $.error('Method ' + method + ' does not exist');
            }
        }
    });
})(jQuery);

/* HTML EDITOR */
(function ($) {
    var methods = {
        init:function () {
            var editor = this;

            this.bind('click', function() {
                editor.HTMLEditor('showEditor')
            });


            //this.attr("onclick", "$(this).HTMLEditor('showEditor')");
            this.addClass("HTMLEditorArea");
            var $body = $("body");
            if($body.data("HTMLPrepared") == undefined){
                $body.HTMLEditor("prepareBody");
                $body.data("HTMLPrepared", true)
            }
            return this;
        },
        setQueryId: function(queryId){
            this.data("queryId", queryId);
        },
        setFields: function(fields){
            this.data("fields", fields);
        },
        bindOnChange: function(callback){
            this.data("changeCallback", callback)
        },
        showEditor:function () {
            var currentPreviewDiv = $(this);
            var $window = $("#HTMLEditorWindow");
            var $HTMLEditorShortCodes = $("#HTMLEditorShortCodes");
            var $HTMLEditorText = $("#HTMLEditorText");
            $HTMLEditorShortCodes.empty();

            function setFields(fields) {
                $HTMLEditorShortCodes.show();
                $HTMLEditorText.width("300px");

                $.each(fields, function (type, field) {
                    var name = (field.alias ? field.alias : field.name);

                    var $a = $("<span></span>")
                        .attr("href", "#")
                        .attr("class", 'button small insert-shortcut')
                        .text(name);

                    $a.click(function () {
                        var content = $HTMLEditorText.val() + " $$" + name + "$$ ";
                        $HTMLEditorText.val(content);

                        if(tinyMCE)
                            tinyMCE.activeEditor.selection.setContent(" $$" + name + "$$ ");
                    });

                    $HTMLEditorShortCodes.append($a);
                    $HTMLEditorShortCodes.append("<br>");
                });
            }

            if (this.data("fields")){
                setFields(this.data("fields"));
            } else {
                $HTMLEditorShortCodes.hide();
            }

            var _this = this;
            $("#saveHTMLButton").unbind('click').click(function (e) {
                //var data = encode_utf8(tinyMCE.activeEditor.getContent());

                var data = tinyMCE.activeEditor.getContent();
                $(currentPreviewDiv).html(data);
                //$(currentPreviewDiv).html($HTMLEditorText.val());

                if(_this.data("changeCallback")){
                    _this.data("changeCallback")(currentPreviewDiv.html());
                }
                $window.dialog("close");
            });
            $window.dialog("open");
            $HTMLEditorText.val(currentPreviewDiv.html());

            tinyMCE.activeEditor.setContent(currentPreviewDiv.html());
            return this;
        },
        prepareBody:function () {
            var $body = $("body");
            $body.data("HTMLPrepared", true);
            $body.append(ich['HTMLEditor']());

            initTinyMCE();

            var $window = $("#HTMLEditorWindow");
            $window.dialog({
                autoOpen: false,
                modal: true,
                title: "Edit text",
                width: 600,
                resizable: false
            });
            $("#closeHTMLButton").click(function(){
                $window.dialog("close");
            });
            return this;
        }
    };

    $.fn.extend({
        HTMLEditor:function (method) {
            if (methods[method]) {
                return methods[ method ].apply(this, Array.prototype.slice.call(arguments, 1));
            } else if (typeof method === 'object' || !method) {
                return methods.init.apply(this, arguments);
            } else {
                $.error('Method ' + method + ' does not exist');
            }
        }
    });

})(jQuery);

/* TRICK FOR USE JQUERY IN IFRAME (FROM PARENT PAGE) */
(function() {
    if (typeof(jQuery) == "undefined") {
        var iframeBody = document.getElementsByTagName("body")[0];
        var jQuery = function (selector) { return parent.jQuery(selector, iframeBody); };
        var $ = jQuery;
    }
})();

/* MAIN FUNCTIONALITY */
function initDialogWindow(args, clb) {
    var ErrorHasNoUrl = function(){};

    try {
        if(typeof args === 'undefined' || args == null )
            return;

        if(!args.hasOwnProperty('url'))
            throw new ErrorHasNoUrl();

        var iframe = args.iframe;
        iframe = (typeof iframe === 'undefined' || iframe == null || iframe);

        var $dialogForm = $("#dialog-form");
        var $loaderGIF = $('<div>')
            .css('background-image', 'url("' + window.flexiweb.site_url + 'images/loader-small.gif")')
            .css('height', '16px')
            .css('width', '16px');

        $dialogForm.html('').append($('<div>').append($loaderGIF));
        $dialogForm.dialog({
            resizable: args.resizable || false,
            modal: args.modal || true,
            background: "#F9F9F9",
            autoOpen: false,
            width: args.width || 500,
            //width: args.width || '100%',
            title: args.title || 'Title'
        });

        var callback = function($container) {
            if($container.get(0).tagName == 'IFRAME') {
                var id = $container.attr('id');
                $container = $container.contents();
                var newheight;
                var newwidth;

                if(document.getElementById){
                    newheight = document.getElementById(id).contentWindow.document .body.scrollHeight;
                    newwidth = document.getElementById(id).contentWindow.document .body.scrollWidth;
                }

                document.getElementById(id).height = (newheight) + "px";
                document.getElementById(id).width = (newwidth) + "px";
            }

            $container.find('#close-dialog-window').bind('click', function(){
                $dialogForm.dialog('close');
            });

            if(typeof clb !== 'undefined' && clb != null) {
                clb($container, $dialogForm);
            }
        }

        if(iframe) {
            var $iframe = $('<iframe id="iframe" frameborder="0" vspace="0" hspace="0" scrolling="no">');
            $iframe.css({ margin: 0, padding: 0, width: args.width - 30, overflow: 'hidden', display: 'none' });
            $iframe.attr('src', args.url);

            $dialogForm.append($iframe).dialog('open');

            $iframe.get(0).onload = function() {
                $iframe.css('width', '100%');
                console.log($iframe.outerWidth());
                $loaderGIF.hide();
                $iframe.show();
                callback($iframe);
            }
        }

        else {
            $dialogForm.dialog('open').load(args.url, function() {
                callback($dialogForm);
            });
        }
    }
    catch(e) {
        if(e instanceof ErrorHasNoUrl)
            console.log('There is no URL for current dialog window.')
        else throw e;
    }
};

/* DIALOGS */
function show_explorer_filter(table, url) {
    initDialogWindow({
        width: 800,
        title: "Upload Image",
        url: window.flexiweb.site_url+'ajax/explorer_fields.php?table=' + table + '&url=' + encodeURI(url)
    });
};
function show_schema_images(schema, callback) {
    initDialogWindow({
        width: 730,
        title: "Select Image",
        url: window.flexiweb.site_url+'ajax/show_schema_images.php?schema='+schema
    }, function($content, $dialog) {
        $content.find('img').bind('click', function() {
            callback(this.src, this.alt);
            $dialog.dialog('close');
        });
    });
};

function upload_file() {
    initDialogWindow({
        width: 800,
        title: "Upload Image",
        url: window.flexiweb.site_url+'ajax/upload_file.php'
    });
};
function upload_image(schema, dataset, object, field) {
    field = field || '';
    initDialogWindow({
        width: 360,
        title: "Upload Image",
        resizable: true,
        url: window.flexiweb.site_url+'ajax/upload_image.php?schema='+schema+'&set='+dataset+'&object='+object+'&field='+field
    });

};

function send_error_report() {
    initDialogWindow({
        width: 600,
        title: "Send Issue",
        resizable: true,
        url: window.flexiweb.site_url + 'ajax/send_error_report.php'
    }, function($form) {
        $('#closeReport').bind('click', function(){
            $form.dialog('close');
        })
    });
};
function rename_custom_field(type, field) {
    initDialogWindow({
        title: "Rename Custom Field",
        url: window.flexiweb.site_url + 'ajax/rename_custom_field.php?type=' + type + '&field=' + field
    }, function($form, $dialog) {
        if( $form.find('input[name="need_to_close"]').val() ) {
            $dialog.dialog('close');
        }
    });
};

/* WP PLUGINS AND SITES */
function install_wp_plugin(object) {
    initDialogWindow({
        resizable: true,
        width: 450,
        buttons: {Close:function(){ $(this).dialog("close"); document.location.reload(true); }},
        title: "Install WordPress Plugin",
        url: window.flexiweb.site_url+'ajax/install_wp_plugin.php?object='+object
    });
};
function install_wp_theme(object) {
    initDialogWindow({
        resizable: true,
        width: 450,
        buttons: {Close:function(){ $(this).dialog("close"); document.location.reload(true); }},
        title: "Install WordPress Theme",
        url: window.flexiweb.site_url+'ajax/install_wp_theme.php?object='+object
    });
};
function install_website(object, site_type) {
    var user = prompt("Please enter ROOT username");
    var password = prompt("Please enter password");
    var mysql_pass = prompt("Please enter MySQL database ROOT password");

    initDialogWindow({
        resizable: true,
        width: 450,
        buttons: {Close:function(){ $(this).dialog("close"); document.location.reload(true); }},
        title: "Install New Website",
        url: window.flexiweb.site_url+'ajax/install_website.php?object='+object+'&user='+user+'&password='+password+'&mysql_pass='+mysql_pass+'&site_type='+site_type
    });
};
function uninstall_website(object, site_type) {
    var user = prompt("Please enter ROOT username");
    var password = prompt("Please enter password");

    initDialogWindow({
        resizable: true,
        width: 450,
        buttons: {Close:function(){ $(this).dialog("close"); document.location.reload(true); }},
        title: "Uninstall Website",
        url: window.flexiweb.site_url+'ajax/uninstall_website.php?object='+object+'&user='+user+'&password='+password+'&site_type='+site_type
    });
};
function copy_wp_website(object) {
    initDialogWindow({
        title: "Copy WP Website",
        url: window.flexiweb.site_url + 'ajax/copy_wp_website.php?object=' + object
    });
};
function repoint_wp_website(object) {
    initDialogWindow({
        title: "Repoint WP Website",
        url: window.flexiweb.site_url + 'ajax/repoint_wp_website.php?object=' + object
    });
};
function copy_local_wp_website(object){
    initDialogWindow({
        title: "Remote copy WP Website",
        url: window.flexiweb.site_url + 'ajax/copy_local_wp_website.php?object=' + object
    });
};
function copy_remote_wp_website(object) {
    initDialogWindow({
        title: "Local copy WP Website",
        url: window.flexiweb.site_url + 'ajax/copy_remote_wp_website.php?object=' + object
    });
};

function add_object(object_type_id, schema_id, set_id) {
    set_id = set_id || '0';
    schema_id = schema_id || '0';

    initDialogWindow({
        title: "Add New Object",
        url: window.flexiweb.site_url + 'ajax/add_object.php?object_type_id=' + object_type_id + '&schema_id=' + schema_id + '&set_id=' + set_id
    }, function($content) {
        $content.find('.toggle-button').bind('click', function() {
            //var el = $(this).data('toggle');
            $('#' + this.dataset.toggle).toggle(100);
        })
    });
};
function add_type(schema_id, system) {
    schema_id = schema_id || '0';
    system = system || '0';

    initDialogWindow({
        title: "Add New " + (system != 0 ? "System " : "") + "Type",
        url: window.flexiweb.site_url + 'ajax/add_type.php?schema_id=' + schema_id + '&system=' + system
    });
};
function add_enum(schema_id, system) {
    schema_id = schema_id || '0';
    system = system || '0';

    initDialogWindow({
        title: "Add New " + (system != 0 ? "System " : "") + "Enum",
        url: window.flexiweb.site_url + 'ajax/add_enum.php?schema_id=' + schema_id + '&system=' + system
    });
};

function add_metric(schema_id, system) {
    schema_id = schema_id || '0';
    system = system || '0';

    initDialogWindow({
        title: "Add New " + (system != 0 ? "System " : "") + "Metric",
        url: window.flexiweb.site_url + 'ajax/add_metric.php?schema_id=' + schema_id + '&system=' + system
    });
};
function calculate_field_value (select, input_id) {
    var $select = $(select);
    var previousRatio = $select.find('option:selected').attr('data-factor');
    var previousCurrency = $select.find('option:selected').text();
    var input = document.getElementById(input_id);
    $select.unbind('change').bind('change', function() {
        if($select.data().currency) {
            var newCurrency = $select.find('option:selected').text();
            convert_currency(input.value, previousCurrency, newCurrency, function(newValue) {
                input.value = newValue;
                previousCurrency = newCurrency;
            })
        }
        else {
            var optionParams = $select.find('option:selected').data();
            input.value = Math.floor(parseFloat(input.value) * previousRatio / optionParams.factor ).toFixed(parseInt(optionParams.decimals));
            previousRatio = optionParams.factor;
        }
    });
}
function load_units(item) {
    var data = {
        url: window.flexiweb.site_url + 'ajax/load_metric_units.php',
        type: 'GET',
        data: {
            metric_id: item.value
        }
    };

    $.ajax(data).done(function (response) {
        $(item).next('select').html(response)
    });
}
function convert_currency(amount, from, to, clb) {
    var data = {
        url: window.flexiweb.site_url + 'ajax/convert_currency.php',
        type: 'GET',
        data: {
            amount: amount,
            from: from,
            to: to
        }
    };
    $.ajax(data).done(function (response) {
        clb(response);
    });
}

function edit_enum($li) {
    var enum_type_id = $li.parent().find('.select_enum').val() || '0';
    initDialogWindow({
        title: "Edit Enum",
        url: window.flexiweb.site_url + 'ajax/edit_enum.php?enum_type_id=' + enum_type_id
    }, function() {});
};
function view_object(object_type_id, object_id) {
    initDialogWindow({
        title: "View Object",
        width: 400,
        url: window.flexiweb.site_url + 'ajax/view_object.php?object_type_id=' + object_type_id + '&object_id=' + object_id
    });
};
function restore_object(object_type_id, object_id, time, removed) {
    removed = removed || '0';

    initDialogWindow({
        title: "Restore Object",
        url: window.flexiweb.site_url + 'ajax/restore_object.php?object_type_id=' + object_type_id + '&object_id=' + object_id + '&time=' + time + '&removed=' + removed
    });
};

/* OBJECT EXPLORER */
function perform_bulk_action(e) {
    var explorer = $(e).parents('.object-explorer');
    var form = $(e).parents('form');
    var action = $(e).siblings('select');
    var target = $(e).siblings('input[name=items]');

    if(action.val()==0) {
        alert('Please select action');
    }
    else {
        var value = new Array();

        $(".checks",explorer).each(function(index, element) {
            if ($(element).prop('checked')) {
                value.push($(element).val());
            }
        });

        if(value.length==0) {
            alert('No selected items');
        }
        else {
            target.val(value.join(','));

            if (confirm('Are you sure you want to apply this action to selected items (' + value.length + ')?')) {
                form.submit();
            }
            else {
                return;
            }
        }
    }
};
function delete_selected(e) {
    var explorer = $(e).parents('.object-explorer');
    var form = $(e).parents('form');
    //var action = $(e).siblings('select');
    //var action = "remove_selected";
    var target = $(e).siblings('input[name=items]');

    var value = new Array();

    $(".checks",explorer).each(function(index, element) {
        if ($(element).prop('checked')) {
            value.push($(element).val());
        }
    });

    if(value.length==0) {
        alert('No selected items');
    }
    else {
        target.val(value.join(','));

        if (confirm('Are you sure you want to apply this action to selected items (' + value.length + ')?')) {
            console.log($(form).serializeArray());
            form.submit();
        }
        else {
            return;
        }
    }
};

function submit_explorer_filter() {
    var newParams = {};

    $.each($("input.criteria"), function(_, element) {
        newParams[element.name] = element.value;
    });


    var existingParams = getURLParams();
    var params = $.extend({}, existingParams, newParams);

    for (var i in params) {
        if( !params[i] )  delete params[i];
    }

    location.search = $.param(params);
};

function check_all_objects(e) {
    var p = $(e).parents('table');
    var state = $(e).prop("checked");

    if(state) {
        $(".checks",p).prop("checked", true);
    }
    else {
        $(".checks",p).prop("checked", false);
    }
};

function inIframe () {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}
function initToggle( item, clb ) {
    var visible = 'visible', hidden = 'hidden';
    var id = item.dataset.toggle;
    var $control = $('.toggle_advanced[data-toggle="' + id + '"]');
    var $target = $('#' + id);
    var states;

    try {
        states = JSON.parse(localStorage.toggle_state);
    }
    catch(e) {
        states = {};
    }

    $control.unbind('click').bind('click', function() {
        var inited = $target.hasClass(visible) || $target.hasClass(hidden);
        var currentState = states[id];
        var callback = clb || $.noop;

        if(inited) {
            if(currentState == visible) {
                //$target.removeClass(visible).addClass(hidden).hide('fast',callback);
                $target.removeClass(visible).addClass(hidden).hide();
                states[id] = hidden;

            }
            else {
                //$target.removeClass(hidden).addClass(visible).show('fast',callback);
                $target.removeClass(hidden).addClass(visible).show();
                states[id] = visible;
            }
        }
        else {
            if(currentState == visible) {
                //$target.addClass(visible).show('fast', callback);
                $target.addClass(visible).show();
            }
            else {
                $target.addClass(hidden);
            }
        }

        if(localStorage)
            localStorage.toggle_state = JSON.stringify(states);

        callback();


    }).trigger('click');
};



/* new */

//function getQueryParams(qs) {
//    qs = qs.split('+').join(' ');
//
//    var params = {},
//        tokens,
//        re = /[?&]?([^=]+)=([^&]*)/g;
//
//    while (tokens = re.exec(qs)) {
//        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
//    }
//
//    return params;
//}

//var query = getQueryParams(document.location.search);
//alert(query.foo);


function getUrlParameter(sParam){
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++)
    {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam)
        {
            return sParameterName[1];
        }
    }
}

//function getParameterByName(name) {
//    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
//    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
//        results = regex.exec(location.search);
//    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
//}



function getURLParams(url) {
    var uri = url || window.location.search;
    var queryString = {};
    uri.replace(
        new RegExp("([^?=&]+)(=([^&]*))?", "g"),
        function($0, $1, $2, $3) { queryString[$1] = $3; }
    );
    return queryString;
}

var modifyValue = function(value) {
    if(typeof value === 'undefined' || value == null || value == 'false' || value == '0') return 0;
    if(value == 'true' || value == '1') return 1;
    return value;
};