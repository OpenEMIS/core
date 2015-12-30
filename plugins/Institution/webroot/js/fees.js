
$(function() {
	fees.init();
});

var fees = {
	
	init: function() {},
	
	load: function(className) {
		$('.inputs_'+className).each(function(index, value) {
			$(this).trigger('onblur');
		});
	},
	
	selectAll: function(obj) {
		$(obj).select();
	},
	
	checkDecimal: function(obj, dec) {
		var numbers = (obj.value).split('.');
		if (numbers.length > 1) {
			obj.value = numbers[0] + '.' + fees.appendDecimals(numbers[1], dec);
		} else if (numbers.length == 1 && numbers[0]!='') {
			obj.value = numbers[0] + '.' + fees.appendDecimals(false, dec);
		} else {
			obj.value = '0.' + fees.appendDecimals(false, dec);
		}
	},
	
	appendDecimals: function(numberPart, dec) {
		if (numberPart.length>dec) {
			return numberPart.substring(0, dec);	
		} else if (!numberPart) {
			numberPart = 0;
			for (var i=1; i<dec; i++) {
				numberPart = numberPart + '0';
			}
			return numberPart;	
		} else {
			var remainder = dec - numberPart.length;
			for (var i=0; i<remainder; i++) {
				numberPart = numberPart + '0';
			}
			return numberPart;	
		}
	}
};
