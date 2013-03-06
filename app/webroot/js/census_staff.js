$(document).ready(function() {
	$('#staff .table_row input').keyup(function() {
		staff.computeTotal(this);
	});
});

var staff = {
	computeTotal: function(obj) {
		var row = $(obj).closest('.table_row');
		var male = row.find('#CensusStaffMale');
		var female = row.find('#CensusStaffFemale');
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
		$('#staff .table_row').each(function() {
			total += $(this).find('.cell_total').html().toInt();
		});
		$('#staff .table_foot .cell_value').html(total);
	}
};