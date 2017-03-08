var Authentication = {
	populate: function(url) {
		$.ajax({
			url: url,
			type: "GET",
			success: function(data){
				$('#authUri').val(data.authorization_endpoint);
				$('#tokenUri').val(data.token_endpoint);
				$('#userInfoUri').val(data.userinfo_endpoint);
				$('#issuer').val(data.issuer);
				$('#jwksUri').val(data.jwks_uri);
			}
		});
	}
}
