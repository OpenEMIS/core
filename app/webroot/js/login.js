$(document).ready(function() {
	Login.init();
});


var Login = {
	// methods
    init: function() {
    },
    
 	switchLang: function(obj) {
        var params = {lang: $(obj).val(), username: $('#SecurityUserUsername').val(),userpassword:$('#SecurityUserPassword').val()};
        var success = function(data, status) {
            window.location.href = getRootURL();
        };
        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: getRootURL() + $(obj).attr('url'),
            data: params,
            success: success
        });
    },
}