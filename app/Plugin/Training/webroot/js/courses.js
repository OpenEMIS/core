
$(document).ready(function() {
    objTrainingCourses.init();
});

var objTrainingCourses = {
    init: function() {
    },

    addTargetPopulation: function(obj) {
        var table = $('.target_population');
        var index = table.find('.table_row').length + $('.delete-target-population input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body').append(data);
                var element = '#searchTargetPopulation' + index;
                var url = getRootURL() + table.attr('url') + '/' + index;
                objTrainingCourses.attachAutoComplete(element, url, objTrainingCourses.selectTargetPopulation);
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

    selectTargetPopulation: function(event, ui) {
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
    
    deleteTargetPopulation: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-target-population');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
    },

    addPrerequisite: function(obj) {
        var table = $('.prerequisite');
        var index = table.find('.table_row').length + $('.delete-prerequisite input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body').append(data);
                var element = '#searchPrerequisite' + index;
                var url = getRootURL() + table.attr('url') + '/' + index;
                objTrainingCourses.attachAutoComplete(element, url, objTrainingCourses.selectPrerequisite);
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

    selectPrerequisite: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            console.log(element.get(0).tagName);
            if(element.get(0).tagName.toUpperCase() === 'INPUT') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },
    
    deletePrerequisite: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');
        if(id != undefined) {
            var div = $('.delete-prerequisite');
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