
$(document).ready(function() {
    objTrainingSessions.init();
});

var objTrainingSessions = {

    init: function() {
        var elementLocation = '#searchLocation';
        var elementTrainer = '#searchTrainer';
        var table = $('#training_session');
        var url = getRootURL() + table.attr('url');
        objTrainingSessions.attachAutoComplete(elementLocation, url + '1/', objTrainingSessions.selectLocationField);
        objTrainingSessions.attachAutoComplete(elementTrainer, url + '2/', objTrainingSessions.selectTrainerField);
    },

    selectLocationField: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            if(element.get(0).tagName.toUpperCase() === 'INPUT' && element.get(0).id == 'searchLocation') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },

    selectTrainerField: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            console.log(element.get(0));
            if(element.get(0).tagName.toUpperCase() === 'INPUT' && element.get(0).id == 'searchTrainer') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },


    addTrainee: function(obj) {
        var table = $('.trainee');
        var index = table.find('.table_row').length + $('.delete-trainee input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body').append(data);
                var element = '#searchTrainee' + index;
                var url = getRootURL() + table.attr('url') + '/' + index;
                objTrainingSessions.attachAutoComplete(element, url, objTrainingSessions.selectTrainee);
            };
            $.unmask({id: maskId, callback: callback});
        };
        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: getRootURL() + $(obj).attr('url'),
            data: params,
            beforeSend: function (jqXHR) { maskId = $.mask({parent: table}); },
            success: success
        });
    },

    selectTrainee: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            if(element.get(0).tagName.toUpperCase() === 'INPUT') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },
    
    deleteTrainee: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-trainee');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
    },

    

    attachAutoComplete: function(element, url, callback) {
        $(element).autocomplete({
            source: url,
            minLength: 2,
            select: callback
        });
    }

}
