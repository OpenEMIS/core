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

var InstitutionSiteStudentBehaviour = {
	validateBehaviourAdd: function() {
		var title = $('#title').val();
		
		var maskId;
		var ajaxParams = {title: title};
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
						css: {left: '400px', top: '126px'}
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
			url: getRootURL() + 'InstitutionSites/studentsbehaviourCheckName',
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
			success: ajaxSuccess
		});
		return false;
	}
}
