angular.module('alert.service', [])
.factory('AlertSvc', function($rootScope) {
    return {
        getMessage: function(message) {
            return message;
        },

        success: function(message) {
            $rootScope.class = 'alert-success';
            $rootScope.message = this.getMessage(message);
        },

        error: function(message) {
            $rootScope.class = 'alert-danger';
            $rootScope.message = this.getMessage(message);
        },

        warning: function(message) {
            $rootScope.class = 'alert-warning';
            $rootScope.message = this.getMessage(message);
        },

        info: function(message) {
            $rootScope.class = 'alert-info';
            $rootScope.message = this.getMessage(message);
        }
    }
});
