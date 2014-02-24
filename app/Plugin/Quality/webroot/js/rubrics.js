// JavaScript Document
$(document).ready(function() {
});
var rubricsTemplate = {
	addHeader: function(id) {
          //  alert(getRootURL() + 'Quality/rubricsTemplatesSubheaderAjaxAddRow/header');
		$.ajax({
			type: "POST",
			url: getRootURL() + 'Quality/rubricsTemplatesSubheaderAjaxAddRow/header',
			data: {id:id, last_id:$('#last_id').val()},
			success: function (data){
                           // alert(data);
				$('form .table_view').append(data);
				$('#last_id').val(parseInt($('#last_id').val())+1);
			}
		});
	},
	addRow : function(id) {
		$.ajax({
			type: "POST",
			url: getRootURL() + 'Quality/rubricsTemplatesSubheaderAjaxAddRow/criteria',
			data: {id:id, last_id:$('#last_id').val()},
			success: function (data){
                          //  alert(data);
				$('form .table_view').append(data);
				$('#last_id').val(parseInt($('#last_id').val())+1);
			}
		});
	}
};