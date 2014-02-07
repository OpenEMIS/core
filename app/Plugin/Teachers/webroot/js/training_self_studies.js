
$(document).ready(function() {
    objTrainingSelfStudies.init();
});

var objTrainingSelfStudies = {
    init: function() {
        $(".icon_plus").unbind( "click" );
        $('.icon_plus').click(objTrainingSelfStudies.addRow);
    },

    validateFileSize: function(obj) {
      //this.files[0].size gets the size of your file.
      var fileSize = obj.files[0].size;
      var fileAttr = $(obj).attr('index');
      if(fileSize/1024 > 2050){
        $('.file_index_' + fileAttr).parent().append('<div id="fileinput_message_' + fileAttr + '" class="error-message custom-file-msg">Invalid File Size</div>');
      }else{
        $("#fileinput_message_" + fileAttr).remove();
    
      }
    },


    addRow: function() {
        var table = $('.file_upload');
        var size = table.find('.table_row').length;

        var maskId;
        var controller = $('#controller').text();

        var url = getRootURL() + controller + '/attachmentsTrainingSelfStudyAdd';
    
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
                var url = getRootURL() + controller + '/attachmentsTrainingSelfStudyDelete/';
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


     errorFlag: function() {
        var errorMsg = $('.custom-file-msg').length;
        if(errorMsg==0){
            return true;
        }else{
            return false;
        }
    }

}