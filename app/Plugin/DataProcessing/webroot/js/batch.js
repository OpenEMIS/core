var checked = true;
function toggleSelect() {
	if(checked) {
		$('.indicators:checked').removeAttr('checked');
		$('#toggleBtn').val('Select All');
		checked = false;
	} else {
		$('.indicators:not(:disabled)').attr('checked', 'checked');
		$('#toggleBtn').val('Clear All');
		checked = true;
	}
}

var batch = {
	indicatorBase: getRootURL() + 'Batch/Indicator/',
	
	runBatch: function() {
		var format = $('#format').val();
		var list = $.map($('.indicators:checked'), function(e, i) {
			return +e.value;
		});
		
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: this.indicatorBase + 'runBatch',
			data: {
				format: format,
				list: list
			},
			beforeSend: function(jqXHR) {
				$.mask({parent: '#indicator', text: i18n.Batch.textRunning});
			},
			success: function(data, textStatus) {
				$.unmask(function() {
					if(data.type == ajaxType.success) {
						$.dialog({title: 'Batch Result', content: i18n.Batch.textExecuteSuccess});
					}
				});
			}
		});
	},
	
	listLogs: function() {
		var dlgOpt = {
			title: 'Batch Logs',
			ajaxUrl: this.indicatorBase + 'listLogs',
			width: 300
		};
		
		$.dialog(dlgOpt);
	}
}