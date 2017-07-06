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
            'key': 'Administration',
            'name': 'Administration'
        }
    ];
    Controller.selectedModule = 'Institutions';
    Controller.roleId = 0;
    Controller.pageSections = [];

    // function
    Controller.changeModule = changeModule;
    Controller.checkAllInSection = checkAllInSection;

    angular.element(document).ready(function () {
        SecurityPermissionEditSvc.init(angular.baseUrl);
        var module = Controller.modules[0].key;
        SecurityPermissionEditSvc.getPermissions(Controller.roleId, module)
        .then(function(permissions) {
            var sections = {};
            var previousCategory = '';
            var counter = -1;
            var enabled = 0;
            angular.forEach(permissions, function(value, key) {
                if (previousCategory != value.category) {
                    enabled = 0;
                    counter++;
                    previousCategory = value.category;
                    sections[counter] = [];
                    sections[counter] = {items: [value], name: value.category, enabled: 0};
                    angular.forEach(value.Permissions, function(val, k) {
                        value.Permissions[k] = parseInt(val);
                        if (k != 'id' && val > 0) {
                            enabled = 1;
                        }
                    });
                    sections[counter]['enabled'] = enabled;
                } else {
                    angular.forEach(value.Permissions, function(val, k) {
                        value.Permissions[k] = parseInt(val);
                        if (k != 'id' && val > 0) {
                            enabled = 1;
                        }
                    });
                    sections[counter]['enabled'] = enabled;
                    sections[counter]['items'].push(value);
                }
            });
            Controller.pageSections = sections;
        }, function(error) {

        });
    });

    function changeModule (module) {
        SecurityPermissionEditSvc.getPermissions(Controller.roleId, module.key)
        .then(function(permissions) {
            var sections = {};
            var previousCategory = '';
            var counter = -1;
            var enabled = 0;
            angular.forEach(permissions, function(value, key) {
                if (previousCategory != value.category) {
                    enabled = 0;
                    counter++;
                    previousCategory = value.category;
                    sections[counter] = [];
                    sections[counter] = {items: [value], name: value.category, enabled: 0};
                    angular.forEach(value.Permissions, function(val, k) {
                        value.Permissions[k] = parseInt(val);
                        if (k != 'id' && val > 0) {
                            enabled = 1;
                        }
                    });
                    sections[counter]['enabled'] = enabled;
                } else {
                    angular.forEach(value.Permissions, function(val, k) {
                        value.Permissions[k] = parseInt(val);
                        if (k != 'id' && val > 0) {
                            enabled = 1;
                        }
                    });
                    sections[counter]['enabled'] = enabled;
                    sections[counter]['items'].push(value);
                }
            });
            Controller.pageSections = sections;
        }, function(error) {

        });
    }

    function checkAllInSection(key) {
        var enabled = Controller.pageSections[key]['enabled'];
        angular.forEach(Controller.pageSections[key]['items'], function(value, key) {
            if (value._view != null) {
                value.Permissions._view = enabled;
            }
            if (value._edit != null) {
                value.Permissions._edit = enabled;
            }
            if (value._add != null) {
                value.Permissions._add = enabled;
            }
            if (value._delete != null) {
                value.Permissions._delete = enabled;
            }
            if (value._execute != null) {
                value.Permissions._execute = enabled;
            }
        });
    }
}
