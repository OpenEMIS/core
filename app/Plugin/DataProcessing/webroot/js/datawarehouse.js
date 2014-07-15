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
    objDatawarehouse.init();
});

var objDatawarehouse = {
    init: function() {
        // objDatawarehouse.populateByModule($(".numeratorModuleOption"), "numerator");
        //objDatawarehouse.populateByModuleOperator($(".numeratorOperatorOption"), "numerator");
        //objDatawarehouse.populateByField($(".numeratorFieldOption"), "numerator");
    },
    getTrainingNeedTypeSelection: function(obj){
        if($(obj).val()== "1"){
            $('.divCourse').removeClass('hide');
            $('.divNeed').addClass('hide');
        }else{
            $('.divNeed').removeClass('hide');
            $('.divCourse').addClass('hide');
        }
    },
    populateByModule: function(obj, objType){
        var moduleID = $(obj).val();
        var operatorOption = $('.'+objType+"OperatorOption");
        var fieldOption = $('.'+objType+"FieldOption");
        var fieldID = $('.'+objType+"FieldID");
        
        var addDimensionRow = $('.'+objType+'-add-dimension-row');
        if(moduleID === ""){
            operatorOption.children('option:not(:first)').remove();
            fieldOption.children('option:not(:first)').remove();
            fieldID.val("");
            if(!addDimensionRow.hasClass('hide')){
                addDimensionRow.addClass('hide');
            }
            //$('.'+objType+"-dimension-row tbody").children().remove();
            //$('.delete-'+objType+"-dimension-row").children().remove();
        }else{
            url = $(obj).attr('url');
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+url+moduleID,
                success: function(data){
                    operatorOption.children('option:not(:first)').remove();
                    fieldOption.children('option:not(:first)').remove();
                    fieldID.val("");
                    if(addDimensionRow.hasClass('hide')){
                        addDimensionRow.removeClass('hide');
                    }
                    //$('.'+objType+"-dimension-row tbody").children().remove();
                    //$('.delete-'+objType+"-dimension-row").children().remove();

                    if(data == null){
                        return;
                    }
                    
                    $.each(data.fieldOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(fieldOption);
                    });

                     $.each(data.operatorOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(operatorOption);
                    });

                }
            });
        }
    },
    populateByModuleOperator: function(obj, objType){
        var operatorOption = $(obj).val();
        var moduleID = $('.'+objType+"ModuleOption").val();
        var fieldOption = $('.'+objType+"FieldOption");
        var fieldID = $('.'+objType+"FieldID");
      
        if(operatorOption=== "" || moduleID === ""){
            fieldOption.children('option:not(:first)').remove();
            fieldID.val("");
        }else{
            url = $(obj).attr('url');
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+url+moduleID+'/'+operatorOption,
                success: function(data){
                    fieldOption.children('option:not(:first)').remove();
                    fieldID.val("");

                    if(data == null){
                        return;
                    }

                     $.each(data.fieldOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(fieldOption);
                    });

                }
            });
        }
    },
    populateByField: function(obj, objType){
        var fieldOption = $(obj).val();
        var fieldID = $('.'+objType+"FieldID");
        if(fieldOption === ""){
            fieldID.val("");
        }else{
            fieldID.val(fieldOption);
        }
    },

    addDimensionRow: function(obj, objType) {
        var table = $('.'+objType+'-dimension-row');
        var index = table.find('.table_row').length + $('.delete-'+objType+'-dimension-row input').length;
        var moduleID =  $('.'+objType+"ModuleOption").val();
        var maskId;
        var params = {index: index, module_id: moduleID, type: objType};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body').append(data);
                //var element = '#searchTrainee' + index;
                //var url = getRootURL() + table.attr('url') + '/' + index + '/' + $('.training_course').val();
                //objTrainingSessions.attachAutoComplete(element, url, objTrainingSessions.selectTrainee);
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
    populateByDimensionOption: function(obj, index, objType){
        var dimensionOption = $(obj).val();
        var dimensionValueOption = $('.'+objType+index+"DimensionValueOption");

      
        if(dimensionOption === ""){
            console.log(dimensionOption);
            dimensionValueOption[0].options.length = 0;
        }else{
            url = $(obj).attr('url');
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+url+dimensionOption,
                success: function(data){
                     console.log(data);
                    dimensionValueOption[0].options.length = 0;

                    if(data == null){
                        return;
                    }

                     $.each(data.dimensionValueOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(dimensionValueOption);
                    });

                }
            });
        }
    },

}