var BankAccounts = {
    bankBranchData : {},
    init : function(){
      this.getBankBranch();  
    },
    show : function(id){
        $('#'+id).css("visibility", "visible");
    },
    hide : function(id){
        $('#'+id).css("visibility", "hidden");
    },
    BacktoList : function(){
        window.location = getRootURL()+"InstitutionSites/bankAccounts";
    },
    getBankBranch : function (){
        $.ajax({ 
            type: "post",
            dataType: "json",
            url: getRootURL()+"InstitutionSites/bankAccountsBankBranches",
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
        var select = $(thisobj).parent().next().find('select');
        select.find('option').remove();
        select.append(tpl);
    },
    validateAdd : function(){
        var bool = true;
        var errorMessages = [];
        var alertOpt = {
            // id: 'alert-' + new Date().getTime(),
            parent: '.section_group',
            title: 'Click to dismiss',
            text: "Error have occurred.",
            type: alertType.error, // alertType.info or alertType.warn or alertType.error
            position: 'center',
            css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
            autoFadeOut: true
        };

        if($('input[type="radio"][name*="[active]"]').length > 0 && $('input[type="radio"][name*="[active]"]:checked').length < 1){
//            errorMessages.push(i18n.BankAccounts.validateAddActive);
            errorMessages.push("Please select an account as active.");
            bool = false;
        }

        $('select[name*="[bank_branch_id]"]').each(function(i,o){
            if(!bool){
                return false;
            }
            if($(o).val() == "0"){
                errorMessages.push(i18n.BankAccounts.validateAddBranch);
                bool = false;
            }
        });

        if(bool){
            $.mask({text: i18n.General.textSaving});
        }else{
            alertOpt.text = errorMessages.shift();
            $.alert(alertOpt);
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
                                    url: getRootURL()+"InstitutionSites/bankAccountsDelete/"+id
                            });
                            $.closeDialog({id:'deletedlg',onClose:function(){
                                    //location.reload();

                            }});
                            $("#bankaccount_row_"+id).fadeOut(300, function() { $(this).remove(); });
                      }}],
            'closeBtnCaption':'No'
        })
    }
}

BankAccounts.init();