/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

var Salary = {
    addAddition: function(obj) {
        /*var table = $('.table-content');
        var index = table.find('tr').length + $('.delete input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('tbody').append(data);
                var element = '#search' + index;
                var url = getRootURL() + table.attr('url');
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
        });*/
        var table = $('#salary');
        var size = $('.additions div.table_row').length + $('.deleteAddition input').length;
        var maskId;
        var url = getRootURL() + "Teachers/salaryAdditionAdd";
        alert(size);

        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: url,
            data: {order: size},
            beforeSend: function(jqXHR) {
                maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textAddingRow});
            },
            success: function (data, textStatus) {
                var callback = function() {
                    $('.additions').append(data);

                };
                $.unmask({id: maskId, callback: callback});
            }
        });
    },

    deleteAddition: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('data-id');
        if(id != undefined) {
            var div = $('.deleteAddition');

            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);

            var controlId = $('.addition-control-id');
             alert(controlId.innerHTML());
            var input = row.find(controlId).attr({type: 'hidden', name: name});
            alert(name);
            
            div.append(input);

            alert(id);
        }
        row.remove();
    },
}
