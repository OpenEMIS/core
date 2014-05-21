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
	
	if($("#studentNameAutoComplete").length === 1){
		$("#studentNameAutoComplete").autocomplete({
			source: function(request, response) {
				$.ajax({
					url: getRootURL() + 'InstitutionSites/attendanceStudentSearchStudent',
					dataType: "json",
					data: {
						term: request.term,
						classId: $('select#classId').val()
					},
					success: function(data) {
						response(data);
					}
				});
			},
			minLength: 2,
			select: function(event, ui) {
				$('#studentNameAutoComplete').val(ui.item.label);
				$('#hiddenStudentId').val(ui.item.value);
				return false;
			}
		});
	}
	
	if($("#fullDayAbsent").length === 1){
		var valFullDayAbsent = $("#fullDayAbsent").val();
		var lastDateAbsent = $("input#lastDateAbsent");
		var startTimeAbsent = $("#startTimeAbsent");
		var endTimeAbsent = $("#endTimeAbsent");
		
		if(valFullDayAbsent === 'Yes'){
			startTimeAbsent.prop('disabled', true);
			endTimeAbsent.prop('disabled', true);
		}else{
			lastDateAbsent.prop('disabled', true);
		}
		
		$("#fullDayAbsent").change(function(){
			if($(this).val() === 'Yes'){
				lastDateAbsent.prop('disabled', false);
				startTimeAbsent.prop('disabled', true);
				endTimeAbsent.prop('disabled', true);
			}else{
				lastDateAbsent.prop('disabled', true);
				startTimeAbsent.prop('disabled', false);
				endTimeAbsent.prop('disabled', false);
			}
		});
	}
	
});