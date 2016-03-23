angular.module('<?= $ng_app ?>', <?= $ng_modules ?>).run(function($rootScope) {

})
.config(['$locationProvider', function($locationProvider){
	$locationProvider.html5Mode({
		enabled: true,
		requireBase: false
	});
}]);
