var flexiweb = window.flexiweb;
var FieldItem = function(data, editor) {
    PaletteItem.call(this, data, editor);
    // field and elementType -> need to remove from old forms
    this.create = function() {
        var controls = {
            int: ['textbox'],
            varchar: ['textbox', 'textarea'],
            boolean: ['radio', 'checkbox'],
            ip: ['textbox'],
            url: ['textbox'],
            email: ['textbox'],
            text: ['textbox', 'textarea'],
            html: ['textbox', 'textarea'],
            float: ['textbox'],
            password: ['textbox'],
            datetime: ['textbox', 'datepicker', 'datetimepicker', 'calendar'],
            date: ['textbox', 'datepicker', 'calendar'],
            time: ['textbox', 'timepicker'],
            image: ['imageSelect'],
            file: ['fileSelect'],
            qr: ['qrCode'],
            numeric: ['dropdown', 'radioButtons'],
            links: {
                1: ['dropdown', 'radio'],
                2: ['checkboxGroup', 'checkboxDropdown'],
                3: ['dropdown', 'radio'],
                4: ['checkboxGroup', 'checkboxDropdown']
            }
        };
        var $item, $controlTypeSelect;

        var type = data.type = data.type.toLowerCase();

        $item = ich["FieldPaletteItem"](data);
        $controlTypeSelect = $item.find('select[name="control-type"]');

        if($.isNumeric(type)) type = 'numeric';

        $.each(controls[type], function(_, control){
            var $opt = $('<option>').val(control).text(control);
            if(control == 'textbox') $opt.attr('selected', 'selected');
            $controlTypeSelect.append($opt);
        });

        //console.log($controlTypeSelect.find('option[value="textbox"]'))
        //if($controlTypeSelect.find('option[value="textbox"]').length > 0)
        //    $controlTypeSelect.val('textbox');


        if (type == 'file') {
            $item.find('input[name="value"]').remove();
            $item.find('.palette-item-data').children('span').remove();
        }

        if ((data['mandatory'] == true) && ((data['name'] != 'object_id') && (data['name'] != 'object_type_id'))) {
            $item.find('.title').append('<div class="star"></div>');
//          mandatoryFields.push(data['name']);
        }
        $item.find('input[name="mandatory"]').val(data['mandatory']);


        return $item;
    };
    this.init();
};
var LinkItem = function(data, editor) {
    PaletteItem.call(this, data, editor);
    var item = this;
    this.create = function() {
        var controls = {
            1: ['dropdown', 'radio'],
            2: ['checkboxGroup', 'checkboxDropdown'],
            3: ['dropdown', 'radio'],
            4: ['checkboxGroup', 'checkboxDropdown']
        };
        var relations = {
            1: "1-1",
            2: "1-N",
            3: "N-1",
            4: "N-N",
            none: 'Error'
        };
        var $item, $controlTypeSelect;

        $item = ich["LinkPaletteItem"]($.extend(data, {relationName: relations[data.relation]}));
        $controlTypeSelect = $item.find('select[name="control-type"]');

        $.each(controls[data.relation], function(_, control){
            $controlTypeSelect.append($('<option>').val(control).text(control))
        });

        return $item;
    };
    this.collect = function($item) {
        var data = $item.data();

        var collection = {
            //elementType: "link",
            name: data.field,
            palette: data.palette,
            container: data.container
        };

        $.each($item.find('input, select'), function() {
            var $input = $(this);
            collection[$input.attr('name')] = $input.val();
        });

        return $.extend({}, item.data, collection);
    };
    this.init();
};
var FormEditor = function(args) {
    var editor = this;

    if(typeof args === 'undefined' || args == null)
        args = {};

    flexiweb.callFunction.getObjectTypeIdByName('data_form', function (response) {
        args.object_type_id = response;
        args.object_id = getUrlParameter('object_id');
        args.set_id = flexiweb.set_id;
        args.page = $('.tab-pane.active');
        args.identifier = 'field';

        Editor.call(editor, args);

        $.extend(editor, args);

        editor.itemConstructor = { fields : FieldItem, links: LinkItem };

        editor.handleTypeData = function(items) {
            return items;
        };

        editor.modifyLoadedObject = function(data) {
            var code = data.code;

            $.each(code, function(_, element) {
                var palette = element.palette;
                if(typeof palette === 'undefined' || palette == null)
                    palette = element.elementType == 'links' ? 'links' : 'fields';
            });

            //data.code = JSON.stringify(code);
            data.code = code;
            return data;
        };


        /* This method loaded palette depend on data. */
        editor.loadType = function(type, callback) {
            if(typeof callback === 'undefined' || callback == null)
                callback = $.noop;

            editor.$goToType.attr('href', window.flexiweb.site_url + "design_editor/design_types?object_type_id=" + type);

            var timeout = flexiweb.loaderShow();
            var data = {};
            flexiweb.callFunction.getTypeFields(type, function (fields) {
                data.fields = fields;
                flexiweb.callFunction.getTypeLinks(type, function (links) {
                    $.each(links, function(linkedTypeId, link) {
                        link.linked_type_id = linkedTypeId;
                    });

                    data.links = links;

                    var renderedItems = editor.dataElements = editor.handleTypeData(data);
                    editor.clearContainers();
                    editor.initPalettes();
                    editor.loadTypeCallback();

                    callback(renderedItems);
                    flexiweb.loaderHide(timeout);
                });
            });

        };

        /* This method collect application data from editor. */
        editor.specialCollectData = function() {
            return {
                link_with_user: editor.$page.find('#link_with_user').prop("checked")? 1 : 0,
                filter_by_set: editor.$page.find('#filter_by_set').prop("checked")? 1 : 0
            };
        };

        /* This method inits all required elements for editor. */
        editor.specialPreInit = function() {
            var $page = editor.$page;
            editor.$linkWithUser = $page.find('#link_with_user').bind('change', editor.setUnsavedState);
            editor.$filterBySet = $page.find('#filter_by_set').bind('change', editor.setUnsavedState);
            editor.$goToType = $page.find('#go_to_type');
        };

        /* This method render application object. */
        editor.specialRenderData = function(data) {
            editor.$linkWithUser.prop("checked", parseInt(data.link_with_user));
            editor.$filterBySet.prop("checked", parseInt(data.filter_by_set));
            editor.$goToType.attr('href', window.flexiweb.site_url + "design_editor/design_types?object_type_id=" + data.object_type);
        };

        editor.init();
    });
};