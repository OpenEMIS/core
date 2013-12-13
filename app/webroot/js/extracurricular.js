// JavaScript Document
$(function() {
	objExtracurricular.attachAutoComplete();
});


var objExtracurricular = {
	attachAutoComplete: function() {
		// advanced search
		$(".autoComplete").autocomplete({
			source: $(".autoComplete").attr('url'),//"searchAutoComplete",
			minLength: 2,
			select: function(event, ui) {
				$('.autoComplete').val(ui.item.label);
				return false;
			}
		});
	},
}