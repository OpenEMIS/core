angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc'])
    .controller('DirectoryAddCtrl', DirectoryAddCtrlController);

DirectoryAddCtrlController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddSvc'];

function DirectoryAddCtrlController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddSvc) {
    
}