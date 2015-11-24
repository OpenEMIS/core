$(document).ready(function() {	
	results.init();
});

var results = {
	init: function() {
		$('.resultMark').on('keyup', function() {$(this).trigger('change');})
						.on('change', function() {results.changed(this);})
						.on('click', function() {results.clicked(this);})
						;
	},
	clicked: function(element) {
		$(element).select();
	},
	changed: function(element) {
		var val = parseInt($(element).val());
		var highest = parseInt($(element).parent().siblings('.maxMark').val());
		var select = $(element).closest('tr').find('.resultGrade');

		select.find(":selected").removeAttr('selected');
		if (val == '') {
			/**
			 * using first() since the first option is set for empty value
			 */
			select.find("option").first().prop('selected', 'selected');
		} else {
			if (val > highest) {
				val = highest;
			}
			select.find("option").each( function(key, option) {
				if (($(option).data('min') == val || $(option).data('min') < val) && ($(option).data('max') > val || $(option).data('max') == val)) {
					$(option).prop('selected', 'selected');
				}
			});
			$(element).val(parseInt(val));
		}
	},
}
