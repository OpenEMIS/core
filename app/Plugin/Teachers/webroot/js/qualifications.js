$(document).ready(function() {
    objTeacherQualifications.init();
});

var objTeacherQualifications = {
    certificatesData : {},
    init : function(){
      this.getQualificationCertificates();
      $('.link_add').click(function() { objTeacherQualifications.addRow(); })
    },
    show : function(id){
        $('#'+id).css("visibility", "visible");
    },
    hide : function(id){
        $('#'+id).css("visibility", "hidden");
    },
    BacktoList : function(){
        window.location = getRootURL()+"Teachers/qualifications";
    },
    getQualificationCertificates : function (){
        $.ajax({ 
            type: "post",
            dataType: "json",
            url: getRootURL()+"Teachers/categoryCertificates",
            success: function(data){
            // console.log(data);
                objTeacherQualifications.certificatesData = data;
            }
        });
    },
    changeCertificates :function(thisobj){
        var tpl = '<option value="0">'+i18n.General.textSelect+'</option>';
        var p = $(thisobj).val();
        var certs = null;
        $.each(objTeacherQualifications.certificatesData,function(i,o){
            if(o.TeacherQualificationCategory.id == p){ 
                certs = o.TeacherQualificationCertificate; 
                return false;  //exit the each
            }
        })
        if (!$.isEmptyObject(certs)){
            $.each(certs,function(i2,o2){
                tpl += '<option value="'+o2.id+'">'+o2.name+'</option>';
            })
        }
        var select = $(thisobj).parent().next().find('select');
        select.find('option').remove();
        select.append(tpl);
    },
    validateAdd : function(){
        var bool = true;
        var error_messages = [];
        $('select[name*="[teacher_qualification_certificate_id]"]').each(function(i,o){
            // console.log($(o).val());
            if($(o).val() == "0"){
//                alert('Certificate is required!');
                error_messages.push(i18n.Qualifications.textCertificateRequired);
                bool = false;
            }
        });
        $('select[name*="[certificate_no]"]').each(function(i,o){
            // console.log($(o).val());
            if($(o).val() == "0"){
//                alert('Certificate is required!');
                error_messages.push(i18n.Qualifications.textCertificateNoRequired);
                bool = false;
            }
        });
        $('select[name*="[teacher_qualification_institution_id]"]').each(function(i,o){
            // console.log($(o).val());
            if($(o).val() == "0"){
//                alert('Institute is required!');
                error_messages.push(i18n.Qualifications.textInstituteRequired);
                bool = false;
            }
        });
        console.info(error_messages);
        if(bool){
            $.mask({text: i18n.General.textSaving});
        }else{
            if(error_messages.length > 0 )
            {
                var alertOpt = {
                    // id: 'alert-' + new Date().getTime(),
                    parent: '.edit',
                    title: i18n.General.textDismiss,
                    text: error_messages.shift(),//data.msg,
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
                                    url: getRootURL()+"Teachers/qualificationsDelete/"+id
                            });

                            $.closeDialog({id:'deletedlg',onClose:function(){
                                    //location.reload();

                            }});
                            $("#qualifications_row_"+id).fadeOut(300, function() { $(this).remove(); });
                      }}],
            'closeBtnCaption': i18n.General.textNo
        })
    },

    addRow: function() {
        var size = $('.table_body div.table_row').length;
        var maskId;
        var url = getRootURL() + 'Teachers/qualificationsAdd';

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