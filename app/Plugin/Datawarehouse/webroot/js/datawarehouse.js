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
        $('.numeratorDimension .filter-dimension input:checkbox').click(function() {
            objDatawarehouse.generateSubgroup('numerator');
        });
        $('.denominatorDimension .filter-dimension input:checkbox').click(function() {
            objDatawarehouse.generateSubgroup('denominator');
        });

         $('.nav-tabs > li  > a').click(function(){
            if(!$(this).parent().hasClass('disabled')){
                var type = $(this).attr('href').replace('#tab-', "");
                $('#DatawarehouseIndicatorType').val(type);
            }
        });
        // objDatawarehouse.populateByModule($(".numeratorModuleOption"), "numerator");
        //objDatawarehouse.populateByModuleOperator($(".numeratorOperatorOption"), "numerator");
        objDatawarehouse.populateByOperator($(".numeratorFieldOption"), "numerator");
        objDatawarehouse.populateByOperator($(".denominatorFieldOption"), "denominator");

        //objDatawarehouse.getUnitType($("#DatawarehouseIndicatorDatawarehouseUnitId"));
        objDatawarehouse.setSelectedTab();
    },

    generateSubgroup: function(objType){
        var dimensionId = [];
        $('.'+objType+'Dimension .filter-dimension input:checkbox:checked').each(function () {
            dimensionId.push($(this).val());
        });

        var moduleID = $('.'+objType+'ModuleOption').val();

        if(dimensionId.length>0){
            url = 'Datawarehouse/ajax_populate_subgroup/';
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+url+moduleID+'/'+dimensionId+'/'+objType,
                success: function(data){
                    if(data == null){
                        return;
                    }
                    $('.'+objType+'Dimension .form-subgroup').remove();
                    
                    $('.'+objType+'Dimension .divSubgroupList').append(data.subgroupRow);
                    $.unmask({id: maskId});
                },
                 beforeSend: function (jqXHR) { maskId = $.mask({parent: "."+objType+"Dimension"}); }
            });
        }
    },
    getUnitType: function(obj){
        if($(obj).val()== "1"){
            $('#divDenominator').addClass('hide');
        }else{
            $('#divDenominator').removeClass('hide');
        }
    },
    setSelectedTab: function(){
        $('.tab-pane').each(function(){
            $(this).removeClass('active');
        });
       $($('.nav-tabs .active > a').attr('href')).addClass('active');
    },
    populateByModule: function(obj, objType){
        var moduleID = $(obj).val();
        var fieldOption = $('.'+objType+"FieldOption");
        var maskId;
        if(moduleID === ""){
            fieldOption.children().remove();
        }else{
            url = $(obj).attr('url');
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+url+moduleID+'/'+objType,
                success: function(data){
                    fieldOption.children().remove();
                   // $('.'+objType.'Dimension .filter-dimension').remove();
                    if(data == null){
                        return;
                    }
                    $('.'+objType+'Dimension .form-dimension').remove();
                    
                    $('.'+objType+'Dimension').prepend(data.dimensionRow);

                    $.each(data.fieldOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(fieldOption);
                    });
                    $.unmask({id: maskId});
                },
                 beforeSend: function (jqXHR) { maskId = $.mask({parent: "."+objType+"Dimension"}); }
            });
        }
    },
    populateByDimension: function(obj, objType) {
        alert($(obj).val());
    },
    populateByOperator: function(obj, objType){
        var fieldOption = $(obj).val();
        var operatorID = $('.'+objType+"OperatorOption");
        if(fieldOption === ""){
            operatorID.val("");
        }else{
            operatorID.val($(obj).find('option:selected').text());
        }
    },


}