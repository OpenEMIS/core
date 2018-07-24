<script type="text/javascript">
var APP_CONFIGS = {
    'ngApp' : 'OE_Core'
};

agGrid.initialiseAgGridWithAngular1(angular);
angular.module('<?= $ng_app ?>', <?= $ng_modules ?>).run(function() {
    <?= in_array('agGrid', json_decode($ng_modules, true)) ? 'agGrid.LicenseManager.setLicenseKey("Community_Solutions_Foundation_CSF_Devs_13_October_2018__MTUzOTM4NTIwMDAwMA==500b28c724d110b0af8aa885bf13c66a");' : '' ?>

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
