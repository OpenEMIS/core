var Translations = {
	compile: function (obj){
		var url = $( '#translation' ).attr('compile-url');
		var value = $( '#translation' ).val();
		$.ajax({
			type: 'POST',
			url: url,
			data: {
				locale : value
			},
			success: function(data) {
				console.log('success');
			},
			error: function(jqXHR, textStatus, errorThrown){
				console.log('error');
			}
		});
	}	
};