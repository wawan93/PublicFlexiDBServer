$(document).ready(function(){
    var $table = $('#explorer-table'),
        options = {
            width: "100%",
            colModal: []
        },
        trs = $table.find('tbody tr');

    options.fixedCols = trs.first().find('.fixed-key-field').length;
    $table.find('thead th').each(function(index){
        options.colModal[index] = {
            width: $(this).width(),
            align: 'center'
        }
    });
    options.height = (trs.first().height() + 2) * (trs.length + 1);

    $table.fxdHdrCol(options);


    var originalTable = $('.explorer-content tbody').last(),
        fixedTable = $('.explorer-content tbody').first();
    var mo = function(table, index, state) {
        if(state == 'over') {
            $($(table).find('tr')[index]).addClass('hover');

        } else {
            $($(table).find('tr')[index]).removeClass('hover');
        }
    };

    var bind_mouse_tr = function(trIndex) {
        $(this).mouseover(function(){
            mo(fixedTable, trIndex, 'over');
            mo(originalTable, trIndex, 'over');
        });
        $(this).mouseleave(function(){
            mo(fixedTable, trIndex, 'out');
            mo(originalTable, trIndex, 'out');
        });
    };

    originalTable.find('tr').each(function(i){bind_mouse_tr.call(this,i);});

    fixedTable.find('tr').each(function(i){bind_mouse_tr.call(this,i);});

    $('.explorer-content tbody tr input[type=checkbox]').click(function(event){
        event.stopImmediatePropagation();
    });

    $('.explorer-content thead tr input[type=checkbox]').click(function(event){
        if($(this).prop('checked')) {
            $('.explorer-content tbody tr input[type=checkbox]').prop('checked',true);
        } else {
            $('.explorer-content tbody tr input[type=checkbox]').prop('checked',false);
        }
    });

    $('.explorer-content tbody tr').click(function(event){
        var object_type_id = $(this).attr('data-object-type-id');
        var object_id = $(this).attr('data-object-id');

        view_object(object_type_id, object_id);
    });

});