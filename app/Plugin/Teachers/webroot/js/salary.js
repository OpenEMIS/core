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

$(document).ready(function() {
    $('.total_salary_additions').bind('DOMSubtreeModified', function() {
        var additions = $('.total_salary_additions').text();
        var deductions = $('.total_salary_deductions').text();
        var grossSalary = $('.total_gross_salary').val();
        if(isNaN(additions) || additions==""){
            additions = 0;
        }
        if(isNaN(deductions) || deductions==""){
            deductions = 0;
        }
        if(isNaN(grossSalary) || grossSalary==""){
            grossSalary = 0;
        }
        $('.total_salary_additions_input').val(additions);
        $('.total_net_salary').val((parseFloat(grossSalary)+parseFloat(additions))-parseFloat(deductions));
    });
    $('.total_salary_deductions').bind('DOMSubtreeModified', function() {
        var additions = $('.total_salary_additions').text();
        var deductions = $('.total_salary_deductions').text();
        var grossSalary = $('.total_gross_salary').val();
        if(isNaN(additions) || additions==""){
            additions = 0;
        }
        if(isNaN(deductions) || deductions==""){
            deductions = 0;
        }
        if(isNaN(grossSalary) || grossSalary==""){
            grossSalary = 0;
        }
        $('.total_salary_deductions_input').val(deductions);
        $('.total_net_salary').val((parseFloat(grossSalary)+parseFloat(additions))-parseFloat(deductions));
    });
    $('.total_gross_salary').change(function() {
        var additions = $('.total_salary_additions').text();
        var deductions = $('.total_salary_deductions').text();
        var grossSalary = $('.total_gross_salary').val();
         if(isNaN(additions) || additions==""){
            additions = 0;
        }
        if(isNaN(deductions) || deductions==""){
            deductions = 0;
        }
        if(isNaN(grossSalary) || grossSalary==""){
            grossSalary = 0;
        }
        $('.total_net_salary').val((parseFloat(grossSalary)+parseFloat(additions))-parseFloat(deductions));
    });
});



var Salary = {
      show : function(id){
        $('#'+id).css("visibility", "visible");
    },
    hide : function(id){
        $('#'+id).css("visibility", "hidden");
    },
    addAddition: function(obj) {
        var table = $('#salary');
        var size = $('.additions div.table_row').length + $('.deleteAddition input').length;
        var maskId;
        var url = getRootURL() + "Teachers/salaryAdditionAdd";
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
                    $('.additions').prepend(data);

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
            var inputId = $('.addition_amount');
            var input = row.find(inputId);

            var additions = input.val(); 
            var final_additions = parseFloat($('.total_salary_additions').text()) - parseFloat(additions);
            if(isNaN(final_additions) || final_additions==""){
                final_additions = 0;
            }
            $('.total_salary_additions').text(final_additions);
            $('.total_salary_additions_input').val(final_additions);

            var name = div.attr('name').replace('{index}', index);

            var controlId = $('.addition-control-id');
            var input = row.find(controlId);
            div.append(input.attr('name', name));
        }
        row.remove();
    },




    addDeduction: function(obj) {
        var table = $('#salary');
        var size = $('.deductions div.table_row').length + $('.deleteDeduction input').length;
        var maskId;
        var url = getRootURL() + "Teachers/salaryDeductionAdd";
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
                    $('.deductions').prepend(data);

                };
                $.unmask({id: maskId, callback: callback});
            }
        });
    },

    deleteDeduction: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('data-id');
        if(id != undefined) {
            var div = $('.deleteDeduction');

            var index = div.find('input').length;
            var inputId = $('.deduction_amount');
            var input = row.find(inputId);

            var deductions = input.val();
            var final_deductions = parseFloat($('.total_salary_deductions').text()) - parseFloat(deductions);
            if(isNaN(final_deductions) || final_deductions==""){
                final_deductions = 0;
            }
            $('.total_salary_deductions').text(final_deductions);
            $('.total_salary_deductions_input').val(final_deductions);

            var name = div.attr('name').replace('{index}', index);

            var controlId = $('.deduction-control-id');
            var input = row.find(controlId);
            div.append(input.attr('name', name));
        }
        row.remove();
    }
}
