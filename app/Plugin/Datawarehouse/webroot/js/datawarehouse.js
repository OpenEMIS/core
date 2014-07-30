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

         $('.nav-tabs > li  > a').click(function(){
            if(!$(this).parent().hasClass('disabled')){
                var type = $(this).attr('href').replace('#tab-', "");
                $('#DatawarehouseIndicatorType').val(type);
            }
        });
        // objDatawarehouse.populateByModule($(".numeratorModuleOption"), "numerator");
        //objDatawarehouse.populateByModuleOperator($(".numeratorOperatorOption"), "numerator");
        //objDatawarehouse.populateByField($(".numeratorFieldOption"), "numerator");

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
                url: getRootURL()+url+moduleID+'/'+dimensionId,
                success: function(data){
                    if(data == null){
                        return;
                    }
                    
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
        var operatorOption = $('.'+objType+"OperatorOption");
        var maskId;
        if(moduleID === ""){
            operatorOption.children().remove();
        }else{
            url = $(obj).attr('url');
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+url+moduleID+'/'+objType,
                success: function(data){
                    operatorOption.children().remove();
                   // $('.'+objType.'Dimension .filter-dimension').remove();
                    if(data == null){
                        return;
                    }
                    $('.'+objType+'Dimension .form-dimension').remove();
                    
                    $('.'+objType+'Dimension').prepend(data.dimensionRow);

                    $.each(data.operatorOption, function(key, value) {              
                        $('<option>').val(key).text(value).appendTo(operatorOption);
                    });
                    $.unmask({id: maskId});
                },
                 beforeSend: function (jqXHR) { maskId = $.mask({parent: "."+objType+"Dimension"}); }
            });
        }
    },
    populateByDimension: function(obj, objType) {
        alert($(obj).val());
    } 

}