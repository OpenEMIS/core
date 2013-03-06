$(document).ready(function() {
    Training.init();
});

var Training = {
    init : function(){
        $('.link_add').click(function() {
            Training.addRow();
        });
    },
    show : function(id){
        $('#'+id).css("visibility", "visible");
    },
    hide : function(id){
        $('#'+id).css("visibility", "hidden");
    },
    BacktoList : function(){
        window.location = getRootURL()+"Teachers/training";
    },
    validateAdd : function(){
        var bool = true;
        var error_message = [];
        $('select[name*="[teacher_training_category_id]"]').each(function(i,o){
            if($(o).val() == "0"){
                error_message.push(i18n.Training.textCategoryRequired);
                bool = false;
            }
        });
        if(bool){
            $.mask({text: i18n.General.textSaving});
        }else{
//            $('#TeacherTrainingTrainingEditForm').css('position', 'relative');
            if(error_message.length > 0){
                var alertOpt = {
                    // id: 'alert-' + new Date().getTime(),
                    parent: '.edit',
                    title: i18n.General.textDismiss,
                    text: error_message.shift(),//data.msg,
                    type: alertType.error, // alertType.info or alertType.warn or alertType.error
                    position: 'top',
                    css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                    autoFadeOut: true
                };

                $.alert(alertOpt);
            }
        }
        return bool;
    },
    cancelEditQualifications:function(){
        window.location = getRootURL()+"Teachers/qualifications";
    },
    confirmDeletedlg : function(id){
        $.dialog({
            'id' :'deletedlg',
            'title': i18n.General.textDeleteConfirmation,
            'content': i18n.General.textDeleteConfirmationMessage,
            'buttons':[
                    {'value':i18n.General.textYes,'callback':function(){ 
                            
                            $.ajax({
                                    type: "post",
                                    dataType: "json",
                                    url: getRootURL()+"Teachers/trainingDelete/"+id
                            });

                            $.closeDialog({id:'deletedlg',onClose:function(){
                                    //location.reload();

                            }});
                            $("#training_row_"+id).fadeOut(300, function() { $(this).remove(); });
                      }}],
            'closeBtnCaption': i18n.General.textNo
        })
    },

    addRow: function() {
        var size = $('.table_body div.table_row').length;
        var maskId;
        var url = getRootURL() + 'Teachers/trainingAdd';

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

                    $('.table_body').append(data);
                    jsForm.initDatepicker($('.table_row:last'));

                };
                $.unmask({id: maskId, callback: callback});
            }
        });
    },

    removeRow: function(obj) {
        var row = $(obj).closest('.table_row');
        var table = row.closest('.table');
        var id = row.attr('data-id');
        row.remove();
    },
}