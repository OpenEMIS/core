$(document).ready(function() {
	objInstitutionSite.init();
});

var objInstitutionSite = {
    init :function(){

    },
	getGradeList: function(obj) {
		var programmeId = $(obj).val();
		var exclude = [];
		$('.grades').each(function() {
			exclude.push($(this).val());
		});
		var maskId;
		var url = getRootURL() + $(obj).attr('url');
		var ajaxParams = {programmeId: programmeId, exclude: exclude};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				$(obj).closest('.table_row').find('.grades').html(data);
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
			success: ajaxSuccess
		});
	}
}
