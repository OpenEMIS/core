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
	CensusFinance.init();
});

var CensusFinance = {
    arrSource : {},
    arrFinanceData : {},
    currentFType : {},
    init : function(){
        this.getFinanceData(); 
        this.getSource();
    },
    show : function(id){
        $('#'+id).css("visibility", "visible");
    },
    hide : function(id){
        $('#'+id).css("visibility", "hidden");
    },
    BacktoList : function(){
        window.location = getRootURL()+"Census/finances";
    },
    getSource : function (){
        $.ajax({ 
            type: "post",
            dataType: "json",
            url: getRootURL()+"Census/financeSource",
            success: function(data){
                var tpl = '<option value="0">'+ i18n.General.textSelect +'</option>';
                CensusFinance.arrSource = data;
                $.each(data,function(i,o){
                    tpl += '<option value="'+o.FinanceSource.id+'">'+o.FinanceSource.name+'</option>';
                })  
                $('select[name=data\\[CensusFinance\\]\\[Source\\]]').html(tpl);
            }
        });
    },
    getFinanceData : function (){
        $.ajax({ 
            type: "post",
            dataType: "json",
            url: getRootURL()+"Census/financeData",
            success: function(data){
                
                CensusFinance.arrFinanceData = data;
            }
        });
    },
    changeType :function(objNature){
        var tpl = '<option value="0">'+ i18n.General.textSelect +'</option>';
        var p = $(objNature).val();
        var select = $(objNature).parent().next().next().find('select');
        select.find('option').remove();
        select.append(tpl);
        
        var fType = null;
        $.each(CensusFinance.arrFinanceData,function(i,o){
            if(o.FinanceNature.id == p){
                CensusFinance.currentFType = o.FinanceType ;
                fType = o.FinanceType ;
                return false;  //exit the each
            }
        })
        if (!$.isEmptyObject(fType)){
            $.each(fType,function(i2,o2){
                tpl += '<option value="'+o2.id+'">'+o2.name+'</option>';
            })
        }
        var select = $(objNature).parent().next().find('select');
        select.find('option').remove();
        select.append(tpl);
    },
    changeCategory :function(objType){
        var tpl = '<option value="0">'+ i18n.General.textSelect +'</option>';
        var p = $(objType).val();
        
        var fCat = null;
           
        $.each(CensusFinance.currentFType,function(i,o){
            if(o.id == p){
                fCat = o.FinanceCategory ; 
                return false;  //exit the each
            }
        })
       
        
        if (!$.isEmptyObject(fCat)){
            $.each(fCat,function(i2,o2){
                tpl += '<option value="'+o2.id+'">'+o2.name+'</option>';
            })
        }
        var select = $(objType).parent().next().find('select');
        select.find('option').remove();
        select.append(tpl);
    },
    validateAdd : function(){
		var bool = true;
		var errorMessages = [];
        var alertOpt = {
            parent: '#CensusFinanceAdd',
            title: "Error have occurred.",
            type: alertType.error,
            position: 'top',
            css: {},
            autoFadeOut: true

        };

        if($('#FinanceSource').val() == '0'){
			errorMessages.push('Finance Source  is required!');
            bool = false;
        }
        if($('#FinanceCategory').val() == '0'){
            errorMessages.push('Finance Category is required!');
            bool = false;
        }
		if($('input[name*="[amount]"]').val().trim() == ''){
			errorMessages.push('Amount field is required!');
            bool = false;
		}
		
		
        if(bool){
            $.mask({text: i18n.General.textSaving });
        }else{
            alertOpt.text = errorMessages.shift();
            $.alert(alertOpt);
		}
        return bool;
        
    },
    validateEdit : function(){
        var bool = true;
		var errorMessages = [];
        var alertOpt = {
            parent: '.section_group',
            title: "Error have occurred.",
            type: alertType.error,
            position: 'top',
            css: {},
            autoFadeOut: true
        };
		
        $('select[name*="[finance_category_id]"]').each(function(i,o){
            if($(o).val() == "0" || $(o).val() == ""){
                errorMessages.push('Category is required!');
                bool = false;
            }
        });
		
        if(bool){
            $.mask({text: i18n.General.textSaving });
        }else{
            alertOpt.text = errorMessages.shift();
            $.alert(alertOpt)
		}
        return bool;
    },
    confirmDeletedlg : function(id){
        $.dialog({
            'id' :'deletedlg',
            'title': i18n.General.textDeleteConfirmation,
            'content': i18n.General.textDeleteConfirmationMessage,
            'buttons':[
                    {'value':'Yes','callback':function(){
                            $.ajax({
                                    type: "post",
                                    dataType: "json",
                                    url: getRootURL()+"Census/financesDelete/"+id
                            });
                            $.closeDialog({id:'deletedlg',onClose:function(){
                                    //location.reload();

                            }});
                            $("#censusfinance_row_"+id).fadeOut(300, function() { $(this).remove(); });
                      }}],
            'closeBtnCaption':'No'
        })
    }
}
