$(document).ready(function() {
	attachments.init();
});

var attachments = {
	init: function() {
		$('.icon_plus').click(attachments.addRow);

        $('.btn_save').click(function(){
            var validatedFileFields = attachments.validFilenameExist();
            if(validatedFileFields.error){
                var alertOpt = {
                    parent: 'body',
                    type: alertType.error,
                    text: validatedFileFields.messages.shift(),
                    position: 'center'
                };
                $.alert(alertOpt);

                return false;
            }
        });
	},
	
	selectFile: function(obj) {
		var parent = $(obj).closest('.file_input');
		parent.find('input[type="file"]').click();
	},
	
	clearFile: function(obj) {
		
	},
	
	updateFile: function(obj) {
		var parent = $(obj).closest('.file_input');
		parent.find('.file input[type="text"]').val($(obj).val());
	},
	
	deleteFile: function(id) {
		var dlgId = 'deleteDlg';
		var btn = {
			value: 'Delete',
			callback: function() {
				var maskId;
				var controller = $('#controller').text();
				var url = getRootURL() + controller + '/attachmentsDelete/';
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: url,
					data: {id: id},
					beforeSend: function (jqXHR) {
						maskId = $.mask({parent: '.content_wrapper', text: 'Deleting attachment...'});
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
			title: 'Delete Attachment',
			content: 'Do you wish to delete this record?',
			buttons: [btn]
		};
		
		$.dialog(dlgOpt);
	},
	
	addRow: function() {
		var size = $('.table_row').length;
		var maskId;
		var controller = $('#controller').text();
		var url = getRootURL() + controller + '/attachmentsAdd';
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {size: size},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.content_wrapper', text: 'Adding row...'});
			},
			success: function (data, textStatus) {
				var callback = function() {
					$('.file_upload .table_body').append(data);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	deleteRow: function(obj) {
		$(obj).closest('.table_row').remove();
		attachments.renderTable();
	},
	
	renderTable: function() {
		$('.table_row.even').removeClass('even');
		$('.table_row:odd').addClass('even');
	},

    validFilenameExist: function() {
        var validated = {
            error: false,
            messages: [],
            rows: []
        };

        $('.file_input input[type="file"]').each(function (i, o){
            if($(o).val().isEmpty()){
                validated.error = true;
                validated.messages.push("Please select file to be uploaded.");
                return false;
            }
        });

        return validated;
    }
}