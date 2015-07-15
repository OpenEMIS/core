var Translations = {
	compile: function (obj){
		var urlPost = $( this ).attr('url-post');
		var url = urlPost;
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