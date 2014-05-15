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
	objInstitutionSite.init();
});

var objInstitutionSite = {
    init :function(){

    },
	getGradeList: function(obj) {
		var programmeId = $(obj).val();
		var exclude = [];
		$('.grades').each(function() {
			if($(this).val() != ''){
				exclude.push($(this).val());
			}
		});
		var maskId;
		var url = getRootURL() + $(obj).attr('url');
		var ajaxParams = {programmeId: programmeId, exclude: exclude};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				$(obj).closest('tr').find('.grades').html(data);
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#grade_list'}); },
			success: ajaxSuccess
		});
	},
	
	positionFocusEvent: function(obj) {
		var input = $(obj);
		if(input.val() == input.attr('empty')) {
			input.val('').removeClass('grey');
		}
	},
	
	positionBlurEvent: function(obj) {
		var input = $(obj);
		if(input.val().isEmpty()) {
			input.val(input.attr('empty')).addClass('grey');
		}
	}
}