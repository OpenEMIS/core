<script type="text/javascript">
agGrid.initialiseAgGridWithAngular1(angular);
angular.module('<?= $ng_app ?>', <?= $ng_modules ?>).run(function($rootScope) {
    angular.baseUrl = '<?= $this->request->base ?>';
    angular.url = function(url) {
        return angular.baseUrl + '/' + url;
    }
})
.config(['$locationProvider', function($locationProvider){
    $locationProvider.html5Mode({
        enabled: true,
        requireBase: false,
        rewriteLinks: false
    });
}]);
</script>
