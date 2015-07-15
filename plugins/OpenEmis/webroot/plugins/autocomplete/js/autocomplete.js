$(document).ready(function() {
	Autocomplete.init();
});

var Autocomplete = {
	loader: '<span class="autocomplete-loader"></span>',
	text: '<span class="autocomplete-text"></span>',

	init: function() {
		this.attachAutoComplete('.autocomplete');
	},

	select: function(event, ui) {
		// var val = ui.item.value;
		// for (var i in val) {
		// 	element = $("input[autocomplete='"+i+"']");
			
		// 	if (element.length > 0) {
		// 		if(element.get(0).tagName.toUpperCase() === 'INPUT') {
		// 			element.val(val[i]);
		// 		} else {
		// 			element.html(val[i]);
		// 		}
		// 	}
		// }
		// this.value = ui.item.label;
		// Autocomplete.uiItems = val;
		return false;
	},

	// keyup: function() {
	// 	var val = Autocomplete.uiItems;
	// 	for(var i in val) {
	// 		target = $("input[autocomplete='"+i+"']");
	// 		if( typeof target !== 'string' ){
	// 			if(target.get(0).tagName.toUpperCase() === 'INPUT') {
	// 				target.val('');
	// 			} else {
	// 				target.html('');
	// 			}
	// 		}
	// 	}
	// },

	focus: function(event, ui) {
		this.value = ui.item.label;
		Autocomplete.select(event, ui);
		event.preventDefault();
	},

	beforeSearch: function(event, ui) {
		var obj = $(event.target);
		var text = obj.parent().find('.autocomplete-text');
		if (text.length > 0) {
			text.remove();
		}
		obj.after(Autocomplete.loader);
	},

	searchComplete: function(event, ui) {
		var obj = $(event.target);
		var loader = obj.parent().find('.autocomplete-loader');
		if (loader.length > 0) {
			loader.remove();
		}

		var data = ui.content;
		Autocomplete.filter(data);

		if (data.length == 0) {
			var noResultsTxt = obj.attr('autocomplete-no-results');
			if (noResultsTxt == undefined) {
				noResultsTxt = 'No Results';
			}
			var text = $(Autocomplete.text);
			var cls = obj.attr('autocomplete-class');console.log(cls);
			if (cls != undefined) {
				text.addClass(cls);
			}
			text.html(noResultsTxt);
			obj.after(text);
		}
	},

	filter: function(data) {
		var excludes = [];
		$('[autocomplete-exclude]').each(function() {
			excludes.push($(this).attr('autocomplete-exclude').toString());
		});
		
		for (var i=0; i<data.length; i++) {
			value = data[i].value.toString();
			if ($.inArray(value, excludes) != -1) {
				data.splice(i, 1);
				--i;
			}
		}
	},

	attachAutoComplete: function(e) {
		$(e).each(function() {
			var obj = $(this);
			var url = obj.attr('autocomplete-url');
			
			var length = obj.attr('length');
			if (length === undefined) length = 2;
			
			obj.autocomplete({
				source: url,
				minLength: length,
				select: Autocomplete.select,
				focus: Autocomplete.focus,
				response: Autocomplete.searchComplete,
				search: Autocomplete.beforeSearch
			});//.on( 'keyup', Autocomplete.keyup );
		});
	}
};
