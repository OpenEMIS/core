angular.module('alert.svc', [])
.service('AlertSvc', function() {
    return {
        getMessage: function(message) {
            return message;
        },

        success: function(scope, message) {
            scope.class = 'alert-success';
            scope.message = this.getMessage(message);
        },

        error: function(scope, message) {
            scope.class = 'alert-danger';
            scope.message = this.getMessage(message);
        },

        warning: function(scope, message) {
            scope.class = 'alert-warning';
            scope.message = this.getMessage(message);
        },

        info: function(scope, message) {
            scope.class = 'alert-info';
            scope.message = this.getMessage(message);
        },

        reset: function(scope) {
            scope.class = '';
            scope.message = null;
        }
    }
});
