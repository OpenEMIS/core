var Webhook = {
    /**
     * JQuery event triggering
     * @param string url - URL to the webhook controller listwebhooks function
     * @param string eventKey - Triggered event type e.g. logout
     * @return void
     */
    triggerEvent: function (url, eventKey) {
        for (var i = 0; i < eventKey.length; i++) {
            var urlWithEvent = url.replace(/\/$/, "") + '/' + eventKey[i];
            var xhr = new XMLHttpRequest();
            xhr.open('GET', urlWithEvent);
            xhr.send(null);
            xhr.onreadystatechange = function () {
                var DONE = 4; // readyState 4 means the request is done.
                var OK = 200; // status 200 is a successful return.
                if (xhr.readyState === DONE) {
                    if (xhr.status === OK) {
                        var response = JSON.parse(xhr.responseText);
                        for (var i = 0; i < response.data.length ; i++) {
                            var data = response.data[i];
                            var responseUrl = data.url;
                            var method = data.method;
                            var xmlRequest = new XMLHttpRequest();
                            xmlRequest.open(method, responseUrl);
                            // To modify the send data when there is a need to send parameters
                            xmlRequest.send(null);
                        }
                    } else {
                      console.log('Error: ' + xhr.status); // An error occurred during the request.
                    }
                }
            };
        }
    }


}
