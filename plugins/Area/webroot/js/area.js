$(document).ready(function() {
	Area.init();
});

var Area = {
	init: function(){
		$('.areapicker').each(function() {
			var selectedOption = $(this).find('select:enabled:last');
			var value = selectedOption.val();
			var closestObject = selectedOption.closest('.areapicker');
			var url = $(this).find('select').attr('url');
			var formError = $(this).find('select').attr('form-error');
			var modelName = $(this).find('select').attr('data-source');
			modelName += "/" + $(this).find('select').attr('target-model');
			var displayCountry = $(this).find('select').attr('display-country');
			url += "/" + modelName + "/" + value + "/" + displayCountry;
			Area.populate(closestObject,url, formError);
		});
	},
	reload: function(obj) {
		var value = $(obj).val();
		var url = $(obj).attr('url');
		var formError = $(obj).attr('form-error');
		var modelName= $(obj).attr('data-source');
		modelName += "/" + $(obj).attr('target-model');
		var parent = $(obj).closest('.areapicker');
		var displayCountry = $(obj).attr('display-country');
		url += "/" + modelName + "/" + value + "/" + displayCountry;
		Area.populate(parent, url, formError);
	},
	populate: function(objToUpdate, url, formError) {
		$.ajax({
			url: url,
			type: "GET",
			data: {
				'formerror':formError
			},
			success: function(data){
				objToUpdate.html(data);
				lastSelectedValue = objToUpdate.find('select').last().val();
				// Populate hidden value
				$( objToUpdate ).next().val(lastSelectedValue);
			}
		});
	}
}
