angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc'])
    .controller('DirectoryAddCtrl', DirectoryAddController);

DirectoryAddController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddSvc'];

function DirectoryAddController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddSvc) {

    var vm = this;
    vm.action = 'view';

    vm.addText="Add button";
    
    vm.init=function(){
        alert('Init Ctrl');
        DirectoryaddSvc.init();
    }
    
    angular.element(document).ready(function () {
        vm.addText="Add button";
        vm.init();
    });
}