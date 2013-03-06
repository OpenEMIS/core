$(document).ready(function() {
	CensusTeachers.init();
});

var CensusTeachers = {
	init: function() {
		$('#add_multi_teacher').click(Census.addMultiGradeRow);
	}
};