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

var BankAccounts = {
    bankBranchData : {},
    init : function(){
      this.getBankBranch();  
    },
    BacktoList : function(){
        window.location = getRootURL()+"InstitutionSites/bankAccounts";
    },
    getBankBranch : function (){
        $.ajax({ 
            type: "post",
            dataType: "json",
            url: getRootURL()+"Teachers/bankAccountsBankBranches",
            success: function(data){
                BankAccounts.bankBranchData = data;
            }
        });
    },
    changeBranch :function(thisobj){
        var tpl = '<option value="0">'+ i18n.General.textSelect +'</option>';
        var p = $(thisobj).val();
        var brnchs = null;
        $.each(BankAccounts.bankBranchData,function(i,o){
            if(o.Bank.id == p){ 
                brnchs = o.BankBranch ; 
                return false;  //exit the each
            }
        })
        if (!$.isEmptyObject(brnchs)){
            $.each(brnchs,function(i2,o2){
                tpl += '<option value="'+o2.id+'">'+o2.name+'</option>';
            })
        }
        var select = $('.branch').find('select');
        select.html(tpl);
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
                                    url: getRootURL()+"InstitutionSites/bankAccountsDelete/"+id
                            });
                            $.closeDialog({id:'deletedlg',onClose:function(){
                                    //location.reload();

                            }});
                            $("#bankaccount_row_"+id).fadeOut(300, function() { $(this).remove(); });
                      }}],
            'closeBtnCaption':'No'
        })
    },
    getBranchesAfterChangeBank: function(obj){
        var bankId = $(obj).val();
        var branchSelect = $('.branch').find('select');
        var emptyOption = '<option value="">--Select--</option>';
        
        if(bankId === ""){
            branchSelect.html(emptyOption);
        }else{
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+"Teachers/getBranchesByBankId/"+bankId,
                success: function(data){
                    var newBranchOptions = '';
                    
                    if(data == null){
                        return;
                    }
                    
                    $.each(data, function(i,v){
                        var branch = v.BankBranch;
                        newBranchOptions += '<option value="'+branch.id+'">'+branch.name+'</option>';
                    });
                    
                    branchOptions = newBranchOptions.length > 0 ? emptyOption+newBranchOptions : emptyOption;
                    
                    branchSelect.html(branchOptions);
                }
            });
        }
    }
}

BankAccounts.init();
