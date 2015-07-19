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
			var modelName = $(this).find('select').attr('data-source');
			modelName += "/" + $(this).find('select').attr('target-model');
			var fieldName = $(this).find('select').attr('field-name');
			url += "/" + modelName + "/" + fieldName + "/" + value;
			Area.populate(closestObject,url);
			// Update hidden field value
			$(this).next().val(value);
		});
	},
	reload: function(obj) {
		var value = $(obj).val();
		var url = $(obj).attr('url');
		var modelName= $(obj).attr('data-source');
		modelName += "/" + $(obj).attr('target-model');
		var parent = $(obj).closest('.areapicker');
		var fieldName = $(obj).attr('field-name');
		url += "/" + modelName + "/" + fieldName + "/" + value;
		Area.populate(parent, url)
		// Update hidden field value
		$( parent ).next().val(value);
	},
	populate: function(objToUpdate, url) {
		$.ajax({
			url: url,
			type: "GET",
			data: {},
			//traditional: true,
			success: function(data){
				//console.log(data);
				objToUpdate.html(data);
				//objToUpdate.append(data);
			}
		});	
	}
}
