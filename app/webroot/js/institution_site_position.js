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

var InstitutionSitePosition = {
	dateChange : function (obj){
		var startDate = $('#startDate').datepicker().data('datepicker').getFormattedDate('yyyy-mm-dd');
		var endDate = $('#endDate').datepicker().data('datepicker').getFormattedDate('yyyy-mm-dd');

		var maskId;
		var InstitutionSiteStaffId = $('#InstitutionSiteStaffId').val();
		var ajaxParams = {startDate: startDate, endDate: endDate, InstitutionSiteStaffId: InstitutionSiteStaffId};
		
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				$('#fte').html(data);
			};

			$.unmask({id: maskId, callback: callback});
		};

		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $(obj).attr('url'),
			data: ajaxParams,
			beforeSend: function(jqXHR) {
				maskId = $.mask({parent: '.content_wrapper '});
			},
			success: ajaxSuccess
		});
	}
}
