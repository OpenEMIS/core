$(document).ready(function() {
	InstitutionSiteResults.init();
});

var InstitutionSiteResults = {
    yearId: '#SchoolYearId',
	programmeId: '#EducationProgrammeId',
	classId: '#InstitutionSiteClassId',
	
	init: function() {
		
	},
	
	navigateProgramme: function(obj, both) {
		var href = getRootURL() + $(obj).attr('url') + '/' + $(this.yearId).val();
		if(both && $(this.programmeId).length==1) {
			href += '/' + $(this.programmeId).val();
		}
		window.location.href = href;
	},
	
	navigateClass: function(obj, both) {
		var href = getRootURL() + $(obj).attr('url') + '/' + $(this.yearId).val();
		if(both && $(this.classId).length==1) {
			href += '/' + $(this.classId).val();
		}
		window.location.href = href;
	}
}