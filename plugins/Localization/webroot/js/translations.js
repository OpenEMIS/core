var Translations = {
	compile: function (obj){
		var url = $( '#translation' ).attr('compile-url');
		var value = $( '#translation' ).val();
		$.ajax({
			type: 'POST',
			url: url,
			data: {
				locale : value
			}
		});
	}	
};