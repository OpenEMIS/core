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
	InstitutionSiteResults.init();
});

var InstitutionSiteResults = {
    periodId: '#AcademicPeriodId',
	programmeId: '#EducationProgrammeId',
	classId: '#InstitutionSiteClassId',
	
	init: function() {
		
	},
	
	navigateProgramme: function(obj, both) {
		var href = getRootURL() + $(obj).attr('url') + '/' + $(this.periodId).val();
		if(both && $(this.programmeId).length==1) {
			href += '/' + $(this.programmeId).val();
		}
		window.location.href = href;
	},
	
	navigateClass: function(obj, both) {
		var href = getRootURL() + $(obj).attr('url') + '/' + $(this.periodId).val();
		if(both && $(this.classId).length==1) {
			var classId = $(this.classId).val();
			if(classId != null) {
				href += '/' + classId;
			}
		}
		window.location.href = href;
	}
}