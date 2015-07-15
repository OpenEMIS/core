var Translations = {
	compile: function (obj){
		var url = $( this ).attr('url');
		var value = $( '#translation' ).val();
		$.ajax({
			type: 'POST',
			url: url,
			data: {
				locale : value
			},
			success: function(data) {
				alert('Translations applied');
			},
			error: function(jqXHR, textStatus, errorThrown){
				alert('Error when applying translations');
			}
		});
	}	
};