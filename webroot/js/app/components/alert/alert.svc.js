angular.module('alert.svc', [])
.service('AlertSvc', ['$http', function($http) {
    return {
        getMessage: function(scope, message, args) {
            if (!angular.isString(message)) {
                scope.class = 'alert-danger';
                message = 'An unexpected error has been encounted. Please contact the administrator for assistance.';
            }

            var url = angular.baseUrl + '/Translations/translate';
            if (args == undefined) {
                args = [];
            }
            $http.post(url, {text: message})
            .then(function(response, message){
                var message = response.data.translated_text;
                for (var i=0; i < args.length; i++ ) {
                    message = message.replace( /%s/, args[i] );
                }
                scope.message = message;
            }, function(error) {
                scope.message = message;
            });
        },

        success: function(scope, message, args) {
            scope.class = 'alert-success';
            this.getMessage(scope, message, args);
        },

        error: function(scope, message, args) {
            scope.class = 'alert-danger';
            this.getMessage(scope, message, args);
        },

        warning: function(scope, message, args) {
            scope.class = 'alert-warning';
            this.getMessage(scope, message, args);
        },

        info: function(scope, message, args) {
            scope.class = 'alert-info';
            this.getMessage(scope, message, args);
        },

        reset: function(scope) {
            scope.class = '';
            scope.message = null;
        }
    }
}]);
