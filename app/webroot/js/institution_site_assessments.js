$(document).ready(function() {
	InstitutionSiteAssessments.init();
});

var InstitutionSiteAssessments = {
    yearId: '#SchoolYearId',
	programmeId: '#EducationProgrammeId',
	
	init: function() {
		
	},
	
	navigateProgramme: function(obj, both) {
		var href = getRootURL() + $(obj).attr('url') + '/' + $(this.yearId).val();
		if(both && $(this.programmeId).length==1) {
			href += '/' + $(this.programmeId).val();
		}
		window.location.href = href;
	}
}