/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

$(document).ready(function() {
	InstitutionSiteClasses.init();
});

var InstitutionSiteClasses = {
	yearId: '#SchoolYearId',
	
	init: function() {
		$('#classes.add .icon_plus').click(InstitutionSiteClasses.addGrade);
		$('#classes.edit .icon_plus.students').click(InstitutionSiteClasses.addStudentRow);
		$('#classes.edit .icon_plus.teachers').click(InstitutionSiteClasses.addTeacherRow);
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(this.yearId).val();
	},
	
	switchYear: function() {
		$('.table_body .table_row').remove();
	},
	
	validateClassAdd: function() {
		var name = $('#ClassName').val();
		var year = $(InstitutionSiteClasses.yearId).val();
		
		var maskId;
		var ajaxParams = {name: name, year: year, count: $('#grade_list .table_row').length};
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
	},
	
	addTeacherRow: function() {
		var table = $(this).parent().siblings('.table').find('.table_body');
		var parent = table.closest('fieldset');
		var alertOpt = {
			id: 'teacher_alert',
			parent: parent,
			type: alertType.error,
			position: 'center'
		}
		
		if($('.teacher_select').length>0) {
			alertOpt['text'] = i18n.InstitutionSites.textClassSelectTeacher;
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
				beforeSend: function (jqXHR) { maskId = $.mask({parent: parent}); },
				success: ajaxSuccess
			});
		}
	},
	
	selectTeacher: function(obj) {
		var row = $(obj).closest('.table_row');
		var parent = row.closest('fieldset');
		
		var teacherSelect = row.find('.teacher_select');
		var subjectSelect = row.find('.subject_select');
		if(teacherSelect.val().isEmpty()==false && subjectSelect.val().isEmpty()==false) {
			var teacherId = teacherSelect.val();
			var subjectId = subjectSelect.val();
			var teacherOption = teacherSelect.find('> option:selected');
			
			var maskId;
			var ajaxParams = {teacherId: teacherId, subjectId: subjectId, action: 'add'};
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					if(data.type == ajaxType.success) {
						row.attr('teacher-id', teacherId);
						row.attr('subject-id', subjectId);
						row.find('[attr="id"]').html(teacherOption.attr('id'));
						row.find('[attr="name"]').html(teacherOption.attr('name'));
						row.find('[attr="subject"]').html(subjectSelect.find('> option:selected').attr('name'));
					} else {
						var alertOpt = {
							id: 'teacher_alert',
							parent: parent,
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
				beforeSend: function (jqXHR) { maskId = $.mask({parent: parent}); },
				success: ajaxSuccess
			});
		}
	},
	
	deleteTeacher: function(obj) {
		var row = $(obj).closest('.table_row');
		var parent = row.closest('fieldset');
		var teacherId = row.attr('teacher-id');
		var subjectId = row.attr('subject-id');
		
		if(teacherId!=0) {
			var maskId;
			var ajaxParams = {teacherId: teacherId, subjectId: subjectId, action: 'delete'};
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					if(data.type != ajaxType.success) {
						var alertOpt = {
							id: 'teacher_alert',
							parent: parent,
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
				beforeSend: function (jqXHR) { maskId = $.mask({parent: parent, text: i18n.General.textRemoving}); },
				success: ajaxSuccess
			});
		} else {
			jsTable.doRemove(obj);
		}
	}
}
