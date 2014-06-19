$(document).ready(function() {
	FieldOptions.init();
});

var FieldOptions = {
	init: function() {
		$('span[move]').click(function() {
			FieldOptions.move(this);
		});
	},
	
	move: function(obj) {
		var row = $(obj).closest('tr');
		var form = $('#OptionMoveForm');
		$('.option-id').val(row.attr('row-id'));
		$('.option-move').val($(obj).attr('move'));
		form.submit();
	}
};