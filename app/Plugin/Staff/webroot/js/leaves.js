
$(document).ready(function() {
	objStaffLeaves.init();
});

var objStaffLeaves = {
	init: function() {
		//alert('here');
		objStaffLeaves.compute_work_days();
		//$(".icon_plus").unbind("click");
		//$('.icon_plus').click(jsForm.insertNewInputFile);
	},
	validateFileSize: function(obj) {
		//this.files[0].size gets the size of your file.
		var fileSize = obj.files[0].size;
		var fileAttr = $(obj).attr('index');
		if (fileSize / 1024 > 2050) {
			$('.file_index_' + fileAttr).parent().append('<div id="fileinput_message_' + fileAttr + '" class="error-message custom-file-msg">Invalid File Size</div>');
		} else {
			$("#fileinput_message_" + fileAttr).remove();

		}
	},
	/*deleteFile: function(id) {
		//	alert(getRootURL() + $('form').attr('deleteurl'));
		var dlgId = 'deleteDlg';
		var btn = {
			value: i18n.General.textDelete,
			callback: function() {
				var maskId;
				//var controller = $('#controller').text();
				var url = getRootURL() + $('form').attr('deleteurl');
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: url,
					data: {id: id},
					beforeSend: function(jqXHR) {
						maskId = $.mask({parent: '.content_wrapper', text: i18n.Attachments.textDeletingAttachment});
					},
					success: function(data, textStatus) {
						var callback = function() {
							var closeEvent = function() {
								var successHandler = function() {
									$('[file-id=' + id + ']').parent().fadeOut(600, function() {
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
	},*/
	compute_work_days: function() {
		/*var dateFrom = new Date($('#StaffLeaveDateFromYear').val()+'-'+ $('#StaffLeaveDateFromMonth').val()+'-'+$('#StaffLeaveDateFromDay').val());
		 var dateTo = new Date($('#StaffLeaveDateToYear').val()+'-'+$('#StaffLeaveDateToMonth').val()+'-'+$('#StaffLeaveDateToDay').val());*/
		var startDate = $('#StaffLeaveDateFromDay :input').val();
		var newStartDate = startDate.split("-").reverse().join("-");

		var endDate = $('#StaffLeaveDateToDay :input').val();
		var newEndDate = endDate.split("-").reverse().join("-");

		var dateFrom = new Date(newStartDate);
		var dateTo = new Date(newEndDate);
		var flag = true;
		var day, daycount = 0;

		if (dateFrom > dateTo) {
			flag = false;
		}
		while (flag)
		{
			day = dateFrom.getDay();
			if (day != 0 && day != 6) {
				daycount++;
			}
			dateFrom.setDate(dateFrom.getDate() + 1);
			if (dateFrom > dateTo)
			{
				flag = false;
			}
		}

		$('.compute_days').val(daycount);
	},
	errorFlag: function() {
		var errorMsg = $('.custom-file-msg').length;
		if (errorMsg == 0) {
			return true;
		} else {
			return false;
		}
	}


}