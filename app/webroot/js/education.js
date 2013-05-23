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
	education.init();
});

var education = {
	init: function() {
		$('.radio_wrapper input').each(function() {
			$(this).click(function() {
				var id = $(this).attr('id');
				var maskId = $.mask({parent: '.content_wrapper', position: true, top: '100px'});
				
				setTimeout(function() {
					var callback = function() {
						$('.programme_group.current').removeClass('current');
						$('.programme_group[level="' + id + '"]').addClass('current');
					};
					$.unmask({id: maskId, callback: callback});
				}, 300);
			});
		});
		
		$('.programme_grades .wrapper').each(function() {
			$(this).click(function() { education.switchGrade(this); });
		});
		
		$('.btn_cancel').click(function() { window.location.href = $('#view').attr('href'); });
		$('#education_setup .icon_plus').click(education.add);
	},
	
	navigateTo: function(obj) {
		var isEdit = $('#education_setup').hasClass('edit');
		window.location.href = getRootURL() + 'Education/' + (!isEdit ? 'setup/' : 'setupEdit/') + $(obj).val();
	},
	
	switchSystem: function() {
		window.location.href = $('#education form').attr('action') + '/index/' + $('#EducationSystemId').val();
	},
	
	switchGrade: function(obj) {
		if(!$(obj).hasClass('selected')) {
			var gradeObj = $(obj);
			var id = gradeObj.attr('grade-id');
			var parent = gradeObj.closest('.section_group');
			var subjectBody = parent.find('.programme_subjects .box_body');
			var selectedSubjects = subjectBody.find('.subject_list[grade-id="' + id + '"]');
			
			subjectBody.find('.subject_list:visible').fadeOut(100, function() {
				var maskId = $.mask({parent: subjectBody});
				setTimeout(function() {
					$.unmask({id: maskId, callback: function() {
						selectedSubjects.fadeIn(100, function() { $(this).removeClass('none'); });
						gradeObj.parent().find('.selected').removeClass('selected');
						gradeObj.addClass('selected');
					}});
				}, 100);
			});
		}
	},
	
	add: function() {
		var params = {};
		var parent = $(this).parent().parent();
		$('#params').find('span').each(function() {
			params[$(this).attr('name')] = $(this).text();
		});
		
		parent.find('.params').find('span').each(function() {
			params[$(this).attr('name')] = $(this).text();
		});
		
		var list = parent.find('.table_view');
		params['count'] = $('li').length;
		
		if(params['category'] === 'GradeSubject') {
			var subjectIds = [];
			$('.EducationSubjectId').each(function() {
				subjectIds.push($(this).val());
			});
			params['subjectIds'] = subjectIds;
		}
		
		if(params['category'] === 'Programme') {
			education.addProgrammeDialog(params);
		} else {
			var maskId;
			var url = getRootURL() + 'Education/setupAdd';
			
			$.ajax({
				type: 'GET',
				dataType: 'text',
				url: url,
				data: params,
				beforeSend: function (jqXHR) {
					maskId = $.mask({parent: '.content_wrapper'});
				},
				success: function (data, textStatus) {
					var callback = function() {
						if(data.length > 0) {
							list.append(data);
							jsList.init(list);
						} else {
							if(params['category'] === 'GradeSubject') {
								var alertOpt = {
									id: 'subjectAlert',
									parent: '.section_group',
									title: i18n.General.textDismiss,
									text: i18n.Education.noMoreSubjects,
									type: alertType.info,
									position: 'center'
								}
								$.alert(alertOpt);
							}
						}
					};
					$.unmask({id: maskId, callback: callback});
				}
			});
		}
	},
	
	addProgrammeDialog: function(params) {
		var dlgId = 'add_programme_dialog';
		var addBtn = {
			value: i18n.General.textAdd,
			callback: function() {
				var alertOpt = {
					id: 'programmeAlert',
					parent: '#' + dlgId + ' .dialog-box',
					title: i18n.General.textDismiss,
					type: alertType.error
				}
				if($('#EducationProgrammeName').val().isEmpty()) {
					alertOpt['text'] = i18n.Education.emptyProgrammeName,
					$.alert(alertOpt);
				} else if($('#EducationProgrammeDuration').val().isEmpty()) {
					alertOpt['text'] = i18n.Education.emptyDuration,
					$.alert(alertOpt);
				} else {
					$('#' + dlgId + ' form').submit();
				}
			}
		};
		
		var url = getRootURL() + 'Education/setupProgrammeAddDialog';
		
		var dlgOpt = {
			id: dlgId,
			title: i18n.Education.textAddProgramme,
			ajaxUrl: url,
			ajaxParam: params,
			buttons: [addBtn]
		};
		
		$.dialog(dlgOpt);
	}
};
