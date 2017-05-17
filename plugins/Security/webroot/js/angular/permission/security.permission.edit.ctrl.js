angular.module('security.permission.edit.ctrl', ['utils.svc', 'alert.svc', 'security.permission.edit.svc'])
    .controller('SecurityPermissionEditCtrl', SecurityPermissionEditController);

SecurityPermissionEditController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'SecurityPermissionEditSvc'];

function SecurityPermissionEditController($scope, $q, $window, $http, UtilsSvc, AlertSvc, SecurityPermissionEditSvc) {

    var Controller = this;

    // variable
    Controller.modules = [
        {
            'key': 'Institutions',
            'name': 'Institutions'
        },
        {
            'key': 'Directory',
            'name': 'Directory'
        },
        {
            'key': 'Reports',
            'name': 'Reports'
        },
        {
            'key': 'Administrations',
            'name': 'Administrations'
        }
    ];
    Controller.selectedModule = 'Institutions';
    Controller.roleId = 0;

    // function
    Controller.changeModule = changeModule;

    angular.element(document).ready(function () {
        SecurityPermissionEditSvc.init(angular.baseUrl);
        var module = Controller.modules[0].key;
        SecurityPermissionEditSvc.getPermissions(Controller.roleId, module)
        .then(function(permissions) {
            console.log(permissions);
        }, function(error) {

        });
    });
    function changeModule (module) {
        console.log(module);
    }
}
