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
	InstitutionSiteProgrammes.init();
});

var InstitutionSiteProgrammes = {
    yearId: '#SchoolYearId',
	programmeId: '#EducationProgrammeId',
	
	init: function() {
		$('#programmes .icon_plus').click(InstitutionSiteProgrammes.addProgramme);
	},
	
	addProgramme: function() {
		var alertOpt = {
			id: 'programme_alert',
			parent: '.content_wrapper',
			type: alertType.error,
			position: 'center'
		}
		var maskId;
		var table = $('.table_body');
		var select = table.find('select');
		var url = getRootURL() + $('.icon_plus').attr('url');
		if(select.length>0) {
			if(select.val().isEmpty()) {
				alertOpt['text'] = i18n.InstitutionSites.textProgrammeSelect;
				$.alert(alertOpt);
			} else { // add selected programme
				var programmeId = select.val();
				var ajaxParams = {programmeId: programmeId};
				var ajaxSuccess = function(data, textStatus) {
					var callback = function() {
						if(data.type != ajaxType.success) {
							alertOpt['text'] = data.msg;
							$.alert(alertOpt);
						} else {
							window.location.reload();
						}
					};
					$.unmask({id: maskId, callback: callback});
				};
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: url,
					data: ajaxParams,
					beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
					success: ajaxSuccess
				});
			}
		} else { // fetch programme list
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
				url: url,
				beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
				success: ajaxSuccess
			});
		}
	}
}
