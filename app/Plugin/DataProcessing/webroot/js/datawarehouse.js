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
        objDatawarehouse.populateByModule($(".numeratorModuleOption"), "numerator");
        objDatawarehouse.populateByModule($(".numeratorOperatorOption"), "numerator");
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
        var operatorID = $('.'+objType+"OperatorOption");
        var fieldOptionID = $('.'+objType+"FieldOption");
        var fieldID = $('.'+objType+"FieldID");
      
        if(moduleID === ""){
            operatorID.children('option:not(:first)').remove();
            fieldOptionID.children('option:not(:first)').remove();
            fieldID.val("");
        }else{
            url = $(obj).attr('url');
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+url+moduleID,
                success: function(data){
                    operatorID.children('option:not(:first)').remove();
                    fieldOptionID.children('option:not(:first)').remove();
                    fieldID.val("");

                    if(data == null){
                        return;
                    }
                    
                    $.each(data.fieldOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(fieldOptionID);
                    });

                     $.each(data.operatorOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(operatorID);
                    });

                }
            });
        }
    },
    populateByOperator: function(obj, objType){
        var operatorID = $(obj).val();
        var fieldOptionID = $('.'+objType+"FieldOption");
        var fieldID = $('.'+objType+"FieldID");
      
        if(moduleID === ""){
            fieldOptionID.children('option:not(:first)').remove();
            fieldID.val("");
        }else{
            url = $(obj).attr('url');
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+url+moduleID,
                success: function(data){
                    fieldOptionID.children('option:not(:first)').remove();
                    fieldID.val("");

                    if(data == null){
                        return;
                    }

                     $.each(data.operatorOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(operatorID);
                    });

                }
            });
        }
    }
}