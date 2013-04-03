$(document).ready(function() {
	InstitutionSiteClasses.init();
});

var InstitutionSiteClasses = {
	yearId: '#SchoolYearId',
	
	init: function() {
		$('#classes.add .icon_plus').click(InstitutionSiteClasses.addGrade);
		$('#classes.edit .icon_plus').click(InstitutionSiteClasses.addStudentRow);
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(this.yearId).val();
	},
	
	switchYear: function() {
		$('.table_body .table_row').remove();
	},
	
	checkName: function() {
		var name = $('#ClassName').val();
		var year = $(InstitutionSiteClasses.yearId).val();
		
		var maskId;
		var ajaxParams = {name: name, year: year};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(data === 'true') {
					$('form').submit();
				} else {
					var alertOpt = {
						id: 'class_alert',
						parent: '.content_wrapper',
						type: alertType.error,
						text: data,
						css: {left: '350px', top: '65px'}
					}
					$('.content_wrapper').css('position', 'relative');
					$.alert(alertOpt);
				}
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + 'InstitutionSites/classesCheckName',
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
			success: ajaxSuccess
		});
		return false;
	},
	
	addGrade: function() {
		var exclude = [];
		var yearId = $(InstitutionSiteClasses.yearId).val();
		var index = $('.grades').length;
		$('.grades').each(function() {
			exclude.push($(this).val());
		});
		var maskId;
		var ajaxParams = {exclude: exclude, index: index, yearId: yearId};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(!$(data).hasClass('alert')) {
					$('#grade_list .table_body').append(data);
					jsTable.fixTable('#grade_list');
				} else {
					var alertOpt = {
						id: 'grade_alert',
						parent: '.content_wrapper',
						type: $(data).attr('type'),
						text: $(data).html(),
						position: 'center'
					}
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
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
			success: ajaxSuccess
		});
	},
	
	addStudentRow: function() {
		var table = $(this).parent().siblings('.table').find('.table_body');
		
		var alertOpt = {
			id: 'student_alert',
			parent: '.content_wrapper',
			type: alertType.error,
			position: 'center'
		}
		
		if($('.student_select').length>0) {
			alertOpt['text'] = i18n.InstitutionSites.textClassSelectStudent;
			$.alert(alertOpt);
		} else {
			var maskId;
			var ajaxParams = {index: $('fieldset .table_row').length};
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					if(!$(data).hasClass('alert')) {
						table.append(data);
						jsTable.fixTable(table.parent());
					} else {
						alertOpt['parent'] = table.closest('fieldset');
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
				beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
				success: ajaxSuccess
			});
		}
	},
	
	selectStudent: function(obj) {
		var row = $(obj).closest('.table_row');
		var option = $(obj).find('> option:selected');
		var studentId = $(obj).val();
		
		var maskId;
		var ajaxParams = {studentId: studentId, action: 'add'};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(data.type == ajaxType.success) {
					row.attr('student-id', studentId);
					row.find('[attr="id"]').html(option.attr('id'));
					row.find('[attr="name"]').html(option.attr('name'));
					row.find('[attr="gender"]').html(option.attr('gender'));
				} else {
					var alertOpt = {
						id: 'student_alert',
						parent: '.content_wrapper',
						type: alertType.error,
						text: data.msg,
						position: 'center'
					}
					$.alert(alertOpt);
				}
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: getRootURL() + $(obj).closest('.table_body').attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: $(obj).closest('fieldset')}); },
			success: ajaxSuccess
		});
	},
	
	deleteStudent: function(obj) {
		var row = $(obj).closest('.table_row');
		var studentId = row.attr('student-id');
		
		if(studentId!=0) {
			var maskId;
			var ajaxParams = {studentId: studentId, action: 'delete'};
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					if(data.type != ajaxType.success) {
						var alertOpt = {
							id: 'student_alert',
							parent: '.content_wrapper',
							type: alertType.error,
							text: data.msg,
							position: 'center'
						}
						$.alert(alertOpt);
					} else {
						jsTable.doRemove(obj);
					}
				};
				$.unmask({id: maskId, callback: callback});
			};
			$.ajax({
				type: 'GET',
				dataType: 'json',
				url: getRootURL() + $(row).closest('.table_body').attr('url'),
				data: ajaxParams,
				beforeSend: function (jqXHR) { maskId = $.mask({parent: $(obj).closest('fieldset'), text: i18n.General.textRemoving}); },
				success: ajaxSuccess
			});
		} else {
			jsTable.doRemove(obj);
		}
	}
}