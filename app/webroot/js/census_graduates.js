$(document).ready(function() {
	$('#graduates .table_row input').keyup(function() {
		CensusGraduates.computeTotal(this);
	});
});

var CensusGraduates = {
	computeTotal: function(obj) {
		var row = $(obj).closest('.table_row');
		var male = row.find('#CensusGraduateMale');
		var female = row.find('#CensusGraduateFemale');
		if(male.val().isEmpty()) {
			male.val(0);
			obj.select();
		}
		if(female.val().isEmpty()) {
			female.val(0);
			obj.select();
		}
		
		row.find('.cell_total').html(male.val().toInt() + female.val().toInt());
		
		var total = 0;
		$('#graduates .table_row').each(function() {
			total += $(this).find('.cell_total').html().toInt();
		});
		$('#graduates .table_foot .cell_value').html(total);
	}
};