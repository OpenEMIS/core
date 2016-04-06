angular.module('alert.controller', ['alert.service'])
.controller('AlertCtrl', function($rootScope, $scope, AlertSvc) {
	$rootScope.message = null;

    angular.element(document).ready(function () {
    });
});
