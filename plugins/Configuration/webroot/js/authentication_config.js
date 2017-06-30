var Authentication = {
	populate: function(url) {
		$.ajax({
			url: url,
			type: "GET",
			success: function(data){
				$('#configsystemauthentications-authorization-endpoint').val(data.authorization_endpoint);
				$('#configsystemauthentications-token-endpoint').val(data.token_endpoint);
				$('#configsystemauthentications-userinfo-endpoint').val(data.userinfo_endpoint);
				$('#configsystemauthentications-issuer').val(data.issuer);
				$('#configsystemauthentications-jwks-uri').val(data.jwks_uri);
			}
		});
	}
}
