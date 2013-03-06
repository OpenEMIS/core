$(document).ready(function() {
	CensusClasses.init();
});

var CensusClasses = {
	init: function() {
		$('#add_multi_class').click(Census.addMultiGradeRow);
	}
}