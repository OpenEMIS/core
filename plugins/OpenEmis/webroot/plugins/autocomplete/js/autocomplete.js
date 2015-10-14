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
		var obj = $(event.target);
		var value = ui.item.value;
		this.value = ui.item.label;

		var target = obj.attr('autocomplete-target');
		if (target != undefined) {
			$('[autocomplete-value="' + target + '"]').val(value);
		}

		var submit = obj.attr('autocomplete-submit');
		if (submit != undefined) {
			eval(submit);
		}
		return false;
	},

	focus: function(event, ui) {
		event.preventDefault();
	},

	beforeSearch: function(event, ui) {
		var obj = $(event.target);
		var loader = obj.parent().find('.autocomplete-loader');
		if (loader.length > 0) {
			loader.remove();
		}
		var text = obj.parent().find('.autocomplete-text');
		if (text.length > 0) {
			text.remove();
		}
		obj.after(Autocomplete.loader);

		var beforeSearch = obj.attr('autocomplete-before-search');
		if (beforeSearch != undefined) {
			eval(beforeSearch);
		}
	},

	searchComplete: function(event, ui) {
		var obj = $(event.target);
		var loader = obj.parent().find('.autocomplete-loader');
		if (loader.length > 0) {
			loader.remove();
		}

		var data = ui.content;
		Autocomplete.filter(obj, data);

		if (data.length == 0) {
			var noResultsTxt = obj.attr('autocomplete-no-results');
			if (noResultsTxt == undefined) {
				noResultsTxt = 'No Results';
			}
			var text = $(Autocomplete.text);
			var cls = obj.attr('autocomplete-class');
			if (cls != undefined) {
				text.addClass(cls);
			}

			if (noResultsTxt != 'false') {
				text.html(noResultsTxt);
				obj.after(text);
			}

			var onNoResults = obj.attr('autocomplete-on-no-results');
			if (onNoResults != undefined) {
				eval(onNoResults);
			}
		}
	},

	filter: function(obj, data) {
		var target = obj.attr('autocomplete-target');

		if (target != undefined) {
			var excludes = [];
			$('[autocomplete-ref="' + target + '"]').find('[autocomplete-exclude]').each(function() {
				excludes.push($(this).attr('autocomplete-exclude').toString());
			});
			
			for (var i=0; i<data.length; i++) {
				value = data[i].value.toString();
				if ($.inArray(value, excludes) != -1) {
					data.splice(i, 1);
					--i;
				}
			}
		}
	},

	/**
	 * This "source" function exists to make autocomplete fires an event when the input is cleared after it has a selected value.
	 * Autocomplete does not support onBlur event.
	 * Checking of minimum length required before triggering the search will be done here.
	 * 
	 * http://stackoverflow.com/questions/6851645/jquery-ui-autocomplete-input-how-to-check-if-input-is-empty-on-change
	 * http://forum.jquery.com/topic/autocomplete-and-change-event
	 *
	 * "request" parameter is an object with an attribute "term" having the input's value.
	 * "response" is autocomplete's callback function, in this case to trigger Autocomplete.searchComplete.
	 * It only accepts one parameter which is the search result.
	 */
	source: function(request, response) {
		var url = this.element.attr('autocomplete-url');
		var length = this.element.attr('length');
		if (length === undefined) length = 2;
			
		if (request.term.length >= length) {
			$.ajax({
	            url: url,
	            dataType: "json",
	            data: {
	                term: request.term
	            },
	            success: function(data) {
	                response(data);
	            },
	            error: function() { 
	                response(false);
	            }
	        });
	    } else {
	    	response(false);
	    }
	},

	attachAutoComplete: function(e) {
		$(e).each(function() {
			var obj = $(this);
			
			obj.autocomplete({
				// autocomplete options
				source: Autocomplete.source,
					// minimum length check before triggering the search will be done in "source" function.
				minLength: 0,

				// autocomplete events
				focus: Autocomplete.focus,
				response: Autocomplete.searchComplete,
				search: Autocomplete.beforeSearch,
				select: Autocomplete.select,

			});

			obj.focus(function() { $(this).select(); });
		});
	}
};
