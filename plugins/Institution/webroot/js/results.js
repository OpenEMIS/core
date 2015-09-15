$(document).ready(function() {	
	results.init();
});

var results = {
	init: function() {
		$('.resultGrade').prop({'disabled': 'disabled'});
		$('.resultMark').on('keyup', function() {$(this).trigger('change');})
						.on('change', function() {results.changed(this);})
						.trigger('change');
	},
	changed: function(element) {
		var highest = 0;
		var val = $(element).val();
		var select = $(element).closest('tr').find('.resultGrade');
		select.find(":selected").removeAttr('selected');
		select.find("option").each( function(key, option) {
			if (highest < $(option).data('max')) {
				highest = $(option).data('max');
			}
			if (($(option).data('min') == val || $(option).data('min') < val) && ($(option).data('max') > val || $(option).data('max') == val)) {
				$(option).prop('selected', 'selected');
				$('#' + $(select).prop('id') + "-id").val($(option).val());
			}
		});
		if (val > highest) {
			$(element).val(highest);
		}
	},
}
