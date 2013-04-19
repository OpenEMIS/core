$(document).ready(function() {
	InstitutionSiteStudents.init();
});

var InstitutionSiteStudents = {
	yearId: '#SchoolYearId',
	programmeId: '#InstitutionSiteProgrammeId',
	
	init: function() {
		$('.btn_save').click(InstitutionSiteStudents.saveStudentList);
		this.attachSortOrder();
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(this.yearId).val() + '/' + $(this.programmeId).val();
	},
	
	attachSortOrder:function() {
		$('#students_search [orderby]').click(function(){
			var order = $(this).attr('orderby');
			var sort = $(this).hasClass('icon_sort_up') ? 'desc' : 'asc';
			$('#StudentOrderBy').val(order);
			$('#StudentOrder').val(sort);
			var action = $('#students_search form').attr('action')+'/page:'+$('#StudentPage').val();
			$('#students_search form').attr('action', action);
			$('#students_search form').submit();
		});
	}
}