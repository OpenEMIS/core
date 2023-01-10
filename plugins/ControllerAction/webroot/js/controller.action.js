$(document).ready(function() {
	ControllerAction.init();
});

var ControllerAction = {
	init: function() {
		this.fieldMapping();
	},

	fieldMapping: function(obj) {
		if (obj == undefined) {
			$('[field-target]').each(function() {
				$($(this).attr('field-target')).val($(this).attr('field-value'));
				if ($(this).prop('tagName') == 'A') {
					$(this).attr('href', '#');
				}
			});
		} else {
			$($(obj).attr('field-target')).val($(obj).attr('field-value'));
			if ($(obj).prop('tagName') == 'A') {
				$(obj).attr('href', '#');
			}
		}
	}
};
