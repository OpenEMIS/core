$(document).ready(function() {
	InstitutionSiteTeachers.init();
});

var InstitutionSiteTeachers = {
	yearId: '#SchoolYearId',
	
	init: function() {
		//$('.btn_save').click(InstitutionSiteStudents.saveStudentList);
		this.attachSortOrder();
		$('.icon_search').click(InstitutionSiteTeachers.search);
		$('#teachersEdit .icon_plus').click(InstitutionSiteTeachers.addPosition);
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(this.yearId).val();
	},
	
	attachSortOrder:function() {
		$('[orderby]').click(function() {
			var order = $(this).attr('orderby');
			var sort = $(this).hasClass('icon_sort_up') ? 'desc' : 'asc';
			$('.orderBy').val(order);
			$('.order').val(sort);
			var action = $('form').attr('action')+'/page:'+$('.page').val();
			$('form').attr('action', action);
			$('form').submit();
		});
	},
	
	search: function() {
		var alertOpt = {
			id: 'search_alert',
			parent: '.info',
			position: 'center'
		}
		var id = $('#IdentificationNo').val();
		var maskId;
		var ajaxParams = {id: id};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(data.type == ajaxType['success']) {
					if(data['status'] > 0) { // teacher is already employed for that site
						window.location.href = getRootURL() + $('.info').attr('url') + data['id'];
					} else {
						$('#TeacherId').val(data['id']);
						$('#FirstName').val(data['first_name']);
						$('#LastName').val(data['last_name']);
						$('#Gender').val(data['gender']);
					}
				} else if(data.type == ajaxType['alert']) {
					alertOpt['type'] = data['alertOpt']['type'];
					alertOpt['text'] = data['alertOpt']['text'];
					$.alert(alertOpt);
				}
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: getRootURL() + $(this).attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.info'}); },
			success: ajaxSuccess
		});
	},
	
	validateTeacherAdd: function() {
		return $('#TeacherId').val()!=0;
	},
	
	addPosition: function() {
		var index = 0;
		var lastRow = $('#employment .table_body .table_row:last');
		if(lastRow.length > 0) {
			index = lastRow.attr('row-id');
		}
		var alertOpt = {
			id: 'teacher_alert',
			parent: '.content_wrapper',
			type: alertType.error,
			position: 'center'
		}
		var maskId;
		var ajaxParams = {index: index};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(!$(data).hasClass('alert')) {
					$('#employment .table_body').append(data);
					jsDate.initDatepicker();
					jsTable.fixTable('#employment .table');
				} else {
					alertOpt['parent'] = '#employment';
					alertOpt['type'] = $(data).attr('type');
					alertOpt['text'] = $(data).html();
					$.alert(alertOpt);
				}
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $(this).attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#employment'}); },
			success: ajaxSuccess
		});
	},
	
	deletePosition: function(obj) {
		var keys = $(obj).closest('.table_cell').siblings('.key').val();
		$('#employment').before($('<input>').attr({type: 'hidden', name: 'delete[]', value: keys}));
		jsTable.doRemove(obj);
	}
}