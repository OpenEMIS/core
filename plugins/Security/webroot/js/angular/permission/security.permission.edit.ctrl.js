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
    Controller.pageSections = {};

    // function
    Controller.changeModule = changeModule;

    angular.element(document).ready(function () {
        SecurityPermissionEditSvc.init(angular.baseUrl);
        var module = Controller.modules[0].key;
        SecurityPermissionEditSvc.getPermissions(Controller.roleId, module)
        .then(function(permissions) {
            var sections = {};
            var previousCategory = '';
            var counter = -1;
            angular.forEach(permissions, function(value, key) {
                if (previousCategory != value.category) {
                    counter++;
                    previousCategory = value.category;
                    sections[counter] = [];
                    sections[counter] = {items: [value], name: value.category};
                } else {
                    sections[counter]['items'].push(value);
                }
            });
        }, function(error) {

        });
    });

    function changeModule (module) {
        SecurityPermissionEditSvc.getPermissions(Controller.roleId, module)
        .then(function(permissions) {
            var sections = {};
            var previousCategory = '';
            var counter = -1;
            angular.forEach(permissions, function(value, key) {
                if (previousCategory != value.category) {
                    counter++;
                    previousCategory = value.category;
                    sections[counter] = [];
                    sections[counter] = {items: [value], name: value.category};
                } else {
                    sections[counter]['items'].push(value);
                }
            });
        }, function(error) {

        });
    }
}
