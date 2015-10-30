
var loadXAxis = function(code,selected){
    var str = '<option value="-1"> - select - </option>';
    $.each(code,function(k,v){
        var n;
        if(!v.alias) n = v.name;
        else n = v.alias;
        str += '<option ';
        if(n==selected) str += 'selected';
        str += ' value="'+ n +'">'+ n +'</option>';
    });
    $('#XAxis').html(str);
};

$('.color').minicolors({letterCase:'uppercase',position:'top right',defaultValue:'000000'});

var ChartEditorPaletteItem = function(data) {
    if(!data.alias) data.alias = data.name;
    var $li = ich['chartItem'](data);
    PaletteItem.call(this, $li, this.collectData,this.renderData, this.addToStack);
};
ChartEditorPaletteItem.prototype.collectData = function($li) {
    return $.extend($li.collectInputsData(), {
        name: $li.find(".field-name").text(),
        type: $li.find(".field-type").text().toLowerCase(),
        color: $li.find(".color").val(),
        width: $li.find("input[name='width']").val()
    });
};
ChartEditorPaletteItem.prototype.renderData = function($li, data) {
    $li.renderInputsData(data);
};
ChartEditorPaletteItem.prototype.addToStack = function($li) {
    $li.find('.color').minicolors('destroy');
    $li.find('.color').minicolors({letterCase:'uppercase',position:'top right',defaultValue:'000000'});
    initAccordion($li);
};

var ChartEditor = function($nameInput, $objectSelect, $saveButton,
                           $removeButton, $palette, $stack,
                           $querySelect, $results,
                           data, schemaId, setId)
{
    var _this = this;
    this.$resultsTable = $results;

    $('#update-preview').bind("click",function(){
        this.updatePreview();
    }.bind(this));

    var select$liPrototype = function(key, data){
        return this.$palette.children("li[data-name='"+key+"']");
    };
    this.fieldsPalette = new Palette(
        "fields", $palette, $stack, false, select$liPrototype, {}
    );
    this.mainStack = new Stack($stack);
    this.remove = function () {
        doWithConfirmation(function () {
            flexiweb.callFunction.removeObject(window.chart_type_id, $objectSelect.val(), function (resp) {
                if (resp) {
                    window.onbeforeunload = undefined;
                    window.location.search = "";
                }
            });
        }, 'Remove?');

    };

    this.getChart = function() {
        var chart = {};
        chart[$querySelect.val()] = _this.mainStack.collectData("name");

        if(chart[$querySelect.val()] != undefined)
            return chart;
        else
            return false;
    };
    this.getCurrentObject = function() {
        var texts = {};
        $('.p-item').each(function(){
            texts[$(this).find('.field-name').text()] = $(this).find('.field-val').val();
        });

        if(!texts.x || !texts.g_width || !texts.g_height){ return false; }
        return texts;
    };

    var collectDataCallback = function(){

        var texts = {};
        $('.p-item').each(function(){
            texts[$(this).find('.field-name').text()] = $(this).find('.field-val').val();
        });
        texts['code'] = JSON.stringify(this.getChart());

        return texts;
    };
    var renderDataCallback = function(data){
        var new_code = {};
        $.each(data, function(k,v){
            var $item = $('.'+k);
            if($item.length > 0){
                $item.find('.field-val').val(v);
            }
        });
        if (data['code'] == '') return false;
        var code = JSON.parse(data["code"]);
        for(var id in code){break;} //magic: Check is empty object
        code = code[id];

        if(firstPaletteLoad)
        {
            $(this).bind("firstPaletteLoaded", function(){
                var code = JSON.parse(data["code"]);
                for(var id in code){break;} //magic: Check is empty object
                code = code[id];
                _this.mainStack.renderDataUsingPalette(code,_this.fieldsPalette);
            });
        }
        _this.mainStack.renderDataUsingPalette(new_code,_this.fieldsPalette);

    };
    var findErrorsCallback = function(data){
        var query = JSON.parse(data["code"]);
        for(var id in query){
            var flag = true;
            for(var feild in query[id]){
                flag = false;
                break;
            }
            if(flag){
                return "No fields";
            }
            break;
        };
        return false;
    };
    this.updatePreview = function(){
        var chart_fields = this.getChart();
        var cur_obj = this.getCurrentObject();
        if(!chart_fields || !cur_obj) {
            alert('No fields!'); return;
        }
        $('.info').remove();
        $('#results').html('<img src="" id="res_img" alt="" />');

        $.ajax({
                url: window.url + 'ajax/draw_chart.php',
                data: {
                id: window.cur_obj_id,
                    obj: JSON.stringify(cur_obj),
                    fields: JSON.stringify(chart_fields)
            },
            beforeSend: function(){
                $('#res_img').attr('src', window.url + '/images/fx_loader.gif');
            },
            success: function() {
                var time = new Date().getTime();
                $('#res_img').attr('src', window.url + 'uploads/' + window.chart_type_id + '/' + window.cur_obj_id + '/chart.png?'+time);
            },
            fail: function(data) {
                $('#results').html('<div class="error">'+data+"</div>");
            }
        });

    };

    DataObjectEditor.call(this,
        window.chart_type_id, "chart", $nameInput,
        $objectSelect, $saveButton, $removeButton, [this.fieldsPalette],
        [this.mainStack], collectDataCallback, renderDataCallback,
        findErrorsCallback, data,  schemaId, setId
    );

    var firstPaletteLoad = true;

    $querySelect.change(function(){
        this.objId = $querySelect.val();
        $('#goToQuery').attr('href',window.url+'/component/component_query_editor?object_id='+this.objId);
        $.getJSON(
            window.url + 'ajax/call_fx_func.php',{
                function: 'get_object',
                object_type_id: window.query_type_id,
                object_id: this.objId
            },function(queryColumns){
                //var queryCode = JSON.parse(queryColumns['code']);
                var queryCode = queryColumns['code'];

                loadXAxis(queryCode,window.x);
                var newItems = $.map(queryCode, function(item){
                    return new ChartEditorPaletteItem(item);
                });
                this.fieldsPalette.update(newItems);

                if(data && data.code) {
                    var data_code = JSON.parse(data.code), tmp_code = {};
                    for(var qid in data_code){break;}
                    tmp_code[qid] = {};
                    data_code = data_code[qid];
                    $.each(data_code,function(k,v){
                        if($palette.find('li[data-name='+k+']').length) tmp_code[qid][k] = v;
                        else console.log(k,'no')
                    });
                    data.code = JSON.stringify(tmp_code);
                    console.log(data,tmp_code,data_code);
                }

                if(firstPaletteLoad) {
                    this.renderInitData(tmp_code ? tmp_code[qid] : {});
                    firstPaletteLoad = false;
                }
                $('.p-item select, .p-item input').focus(function(){
                    $(_this.mainStack).trigger("edited");
                });
                $(this).trigger("firstPaletteLoaded");
            }.bind(this)
        );
    }.bind(this));
    if(data !== undefined) {
        $querySelect.val(data.query_id).change();
    } else {
        $querySelect.change();
    }
};

ChartEditor.prototype = new DataObjectEditor();
ChartEditor.prototype.constructor = ChartEditor;