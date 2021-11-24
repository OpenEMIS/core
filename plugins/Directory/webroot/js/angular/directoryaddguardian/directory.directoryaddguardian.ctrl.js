angular.module('directory.directoryaddguardian.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryaddguardian.svc'])
    .controller('DirectoryaddguardianCtrl', DirectoryaddguardianController);

DirectoryaddguardianController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddguardianSvc'];

function DirectoryaddguardianController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddguardianSvc) {
   alert('ssss');
    
}