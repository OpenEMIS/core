/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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
	Assessment.init();
});

var Assessment = {
	init: function() {
		
	},
	
	switchProgramme: function(obj) {
		window.location.href = getRootURL() + $(obj).attr('url') + $('#EducationProgrammeId').val();
	},
	
	loadGradeList: function(obj) {
		var $this = $(obj);
		var maskId;
		var url = $this.attr('url');
		var ajaxParams = {programmeId: $this.val()};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				$('#EducationGradeId').html(data);
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.info'}); },
			success: ajaxSuccess
		});
	},
	
	loadSubjectList: function(obj) {
		var $this = $(obj);
		$('.items .table_body').empty();
		
		if(!$this.val().isEmpty()) {
			var maskId;
			var url = $this.attr('url');
			var ajaxParams = {gradeId: $this.val()};
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					if(!$(data).hasClass('alert')) {
						$('.items .table_body').html(data);
						jsTable.fixTable('.items .table');
					} else {
						var alertOpt = {
							id: 'assessment_alert',
							parent: '.info',
							type: $(data).attr('type'),
							position: 'center',
							text: $(data).html()
						}
						$.alert(alertOpt);
					}
				};
				$.unmask({id: maskId, callback: callback});
			};
			$.ajax({
				type: 'GET',
				dataType: 'text',
				url: getRootURL() + url,
				data: ajaxParams,
				beforeSend: function (jqXHR) { maskId = $.mask({parent: '.info'}); },
				success: ajaxSuccess
			});
		}
	}
}