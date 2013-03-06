var Programmes = {
    show : function(id){
        $('#'+id).css("visibility", "visible");
    },
    hide : function(id){
        $('#'+id).css("visibility", "hidden");
    },
    BacktoList : function(){
        window.location = getRootURL()+"InstitutionSites/Programmes";
    },
    validateAdd : function(){
        if($('input[name="file"]').val() == ''){
            alert(i18n.General.textFileRequired);
            return false;
        }
        $.mask({text: i18n.General.textSaving});
        return true;
    },
    validateEdit : function(){
        var bool = true;
        $('select[name*="[status]"]').each(function(i,o){
            //console.log($(o).val());
            if($(o).val() == ""){
                alert(i18n.General.textStatusRequired);
                bool = false;
            }
        });
        if(bool){
            $.mask({text: i18n.General.textSaving});
        }
        return bool;
    },
    confirmDeletedlg : function(id){
        $.dialog({
            'id' :'deletedlg',
            'title': i18n.General.textDeleteConfirmation,
            'content': i18n.General.textDeleteConfirmationMessage,
            'buttons':[
                    {'value': i18n.General.textYes,'callback':function(){ 
                            
                            $.ajax({
                                    type: "post",
                                    dataType: "json",
                                    url: getRootURL()+"InstitutionSites/programmes/"+id
                            });

                            $.closeDialog({id:'deletedlg',onClose:function(){
                                    //location.reload();

                            }});
                            $("#programme_row_"+id).fadeOut(300, function() { $(this).remove(); });
                      }}],
            'closeBtnCaption':i18n.General.textNo
        })
    },
    getAvailableProgrammeList : function(objthis){
        var EducationSystemId = $(objthis).val();
        var maskId = '';
        
        $.ajax({
            type: "post",
            url: getRootURL()+"InstitutionSites/programmesAvailable/"+EducationSystemId,
            beforeSend: function (jqXHR) {
                    maskId = $.mask({parent:'#ProgrammesAdd',text: i18n.General.textLoading});
            },
            success: function(data){
                var callback = function() {
					if(data.length > 0) {
						$('#no-programme').hide();
					} else {
						$('#no-programme').show();
					}
					$('#records').html(data);
                }
                $.unmask({id: maskId, callback: callback});
            }
        });
		$('.controls').show();
    }
}