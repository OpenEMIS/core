$(document).ready(function() {
    objStaffLeaves.init();
});

var objStaffLeaves = {

    init: function() {
        objStaffLeaves.compute_work_days();
        $(".icon_plus").unbind( "click" );
        $('.icon_plus').click(objStaffLeaves.addRow);
    },


    addRow: function() {
        var size = $('.table_row').length;
        var maskId;
        var controller = $('#controller').text();
        var url = getRootURL() + controller + '/attachmentsLeaveAdd';

        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: url,
            data: {size: size},
            beforeSend: function (jqXHR) {
                maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textAddingRow});
            },
            success: function (data, textStatus) {
                var callback = function() {
                    $('.file_upload .table_body').append(data);
                };
                $.unmask({id: maskId, callback: callback});
            }
        });
    },

    deleteFile: function(id) {
        var dlgId = 'deleteDlg';
        var btn = {
            value: i18n.General.textDelete,
            callback: function() {
                var maskId;
                var controller = $('#controller').text();
                var url = getRootURL() + controller + '/attachmentsLeaveDelete/';
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: url,
                    data: {id: id},
                    beforeSend: function (jqXHR) {
                        maskId = $.mask({parent: '.content_wrapper', text: i18n.Attachments.textDeletingAttachment});
                    },
                    success: function (data, textStatus) {
                        var callback = function() {
                            var closeEvent = function() {
                                var successHandler = function() {
                                    $('[file-id=' + id + ']').fadeOut(600, function() {
                                        $(this).remove();
                                        attachments.renderTable();
                                    });
                                };
                                jsAjax.result({data: data, callback: successHandler});
                            };
                            $.closeDialog({id: dlgId, onClose: closeEvent});
                        };
                        $.unmask({id: maskId, callback: callback});
                    }
                });
            }
        };
        
        var dlgOpt = {  
            id: dlgId,
            title: i18n.Attachments.titleDeleteAttachment,
            content: i18n.Attachments.contentDeleteAttachment,
            buttons: [btn]
        };
        
        $.dialog(dlgOpt);
    },
    
    

    compute_work_days: function() {
        var dateFrom = new Date($('#StaffLeaveDateFromYear').val()+'-'+ $('#StaffLeaveDateFromMonth').val()+'-'+$('#StaffLeaveDateFromDay').val());
        var dateTo = new Date($('#StaffLeaveDateToYear').val()+'-'+$('#StaffLeaveDateToMonth').val()+'-'+$('#StaffLeaveDateToDay').val());
        var flag=true;
        var day,daycount=0;
        
        if(dateFrom>dateTo){
            flag = false;
        }
        while(flag) 
        {
            day=dateFrom.getDay();
            if(day != 0 && day != 6){
                daycount++;
            }
            dateFrom.setDate(dateFrom.getDate()+1) ;
            if(dateFrom > dateTo)
            {
                flag=false;
            }
        }

        $('.compute_days').val(daycount);
    }


}