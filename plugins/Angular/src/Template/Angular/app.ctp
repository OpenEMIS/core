angular.module('<?= $ng_app ?>', <?= $ng_modules ?>).run(function($rootScope) {
	$rootScope.baseUrl = '<?= $this->request->base ?>';
	$rootScope.url = function(url) {
		return $rootScope.baseUrl + '/' + url;
	}
})
.config(['$locationProvider', function($locationProvider){
	$locationProvider.html5Mode({
		enabled: true,
		requireBase: false,
		rewriteLinks: false
	});
}]);
