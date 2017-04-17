angular.module('alert.svc', [])
.service('AlertSvc', function($http) {
    return {
        getMessage: function(scope, message) {
            var url = angular.baseUrl + '/Translations/translate/' + message;
            $http.get(url)
            .then(function(response){
                scope.message = response.data.translated_text;
            }, function(error) {
                scope.message = message;
            });
        },

        success: function(scope, message) {
            scope.class = 'alert-success';
            this.getMessage(scope, message);
        },

        error: function(scope, message) {
            scope.class = 'alert-danger';
            this.getMessage(scope, message);
        },

        warning: function(scope, message) {
            scope.class = 'alert-warning';
            this.getMessage(scope, message);
        },

        info: function(scope, message) {
            scope.class = 'alert-info';
            this.getMessage(scope, message);
        },

        reset: function(scope) {
            scope.class = '';
            scope.message = null;
        }
    }
});
