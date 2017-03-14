var Webhook = {
    triggerEvent: function (url, eventKey) {
        var urlWithEvent = url.replace(/\/$/, "") + '/' + eventKey;
        $.ajax({
            url: urlWithEvent,
            type: "GET",
            success: function(response){
                for (var i = 0; i < response.data.length ; i++) {
                    var data = response.data[i];
                    var responseUrl = data.url;
                    var method = data.method;
                    $.ajax({
                        url: responseUrl,
                        type: method
                    });
                }
            }
        });
    }
}
