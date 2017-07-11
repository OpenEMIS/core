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
    Controller.originalPageSections = [];
    Controller.redirectUrl = '';
    Controller.ready = false;

    // function
    Controller.changeModule = changeModule;
    Controller.checkAllInSection = checkAllInSection;
    Controller.postForm = postForm;
    Controller.formatSections = formatSections;
    Controller.updateQueryStringParameter = updateQueryStringParameter;
    Controller.changePermission = changePermission;
    Controller.setPermission = setPermission;

    angular.element(document).ready(function () {
        SecurityPermissionEditSvc.init(angular.baseUrl);
        Controller.ready = false;
        var module = Controller.modules[0].key;
        UtilsSvc.isAppendLoader(true);
        SecurityPermissionEditSvc.getPermissions(Controller.roleId, module)
        .then(function(permissions) {
            Controller.pageSections = Controller.formatSections(permissions);
            Controller.originalPageSections = angular.copy(Controller.pageSections);
            Controller.ready = true;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    });

    function formatSections(permissions)
    {
        var sections = [];
        var previousCategory = [];
        var counter = -1;
        var enabled = 0;
        var tmpSection = {};
        angular.forEach(permissions, function(value, key) {
            if (previousCategory.indexOf(value.category) === -1) {
                enabled = 0;
                counter++;
                previousCategory.push(value.category);
                tmpSection[value.category] = {items: [value], name: value.category, enabled: 0, counter: counter};
                angular.forEach(value.Permissions, function(val, k) {
                    value.Permissions[k] = parseInt(val);
                    if (k != 'id' && val > 0) {
                        enabled = 1;
                    }
                });
                tmpSection[value.category]['enabled'] = enabled;
            } else {
                angular.forEach(value.Permissions, function(val, k) {
                    value.Permissions[k] = parseInt(val);
                    if (k != 'id' && val > 0) {
                        enabled = 1;
                    }
                });
                tmpSection[value.category]['enabled'] = enabled;
                tmpSection[value.category]['items'].push(value);
            }
        });
        angular.forEach(tmpSection, function(value, key) {
            sections[value.counter] = value;
        });

        return sections;
    }


    function changeModule (module) {
        Controller.ready = false;
        UtilsSvc.isAppendLoader(true);
        SecurityPermissionEditSvc.getPermissions(Controller.roleId, module.key)
        .then(function(permissions) {
            Controller.pageSections = Controller.formatSections(permissions);
            Controller.originalPageSections = angular.copy(Controller.pageSections);
            Controller.ready = true;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function changePermission(functionArr, type, value)
    {
        switch (type) {
            case 'view':
                if (value == 0) {
                    Controller.setPermission(functionArr, 'edit', 0);
                    Controller.setPermission(functionArr, 'add', 0);
                    Controller.setPermission(functionArr, 'delete', 0);
                    Controller.setPermission(functionArr, 'execute', 0);
                }
                break;
            case 'edit':
                if (value == 0) {
                    Controller.setPermission(functionArr, 'add', 0);
                    Controller.setPermission(functionArr, 'delete', 0);
                } else {
                    Controller.setPermission(functionArr, 'view', 1);
                }
                break;
            case 'add':
                if (value == 0) {
                    Controller.setPermission(functionArr, 'delete', 0);
                } else {
                    Controller.setPermission(functionArr, 'view', 1);
                    Controller.setPermission(functionArr, 'edit', 1);
                }
                break;
            case 'delete':
                if (value == 0) {
                    Controller.setPermission(functionArr, 'delete', 0);
                } else {
                    Controller.setPermission(functionArr, 'view', 1);
                    Controller.setPermission(functionArr, 'edit', 1);
                    Controller.setPermission(functionArr, 'add', 1);
                }
                break;
            case 'execute':
                if (value == 1) {
                    Controller.setPermission(functionArr, 'view', 1);
                }
                break;
        }
    }

    function setPermission(permission, type, value)
    {
        var typeName = '_' + type;
        if (permission[typeName] == null) {
            permission['Permissions'][typeName] = 0;
        } else {
            permission['Permissions'][typeName] = value;
        }
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

    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    }

    function postForm() {
        var permissions = [];
        var originalPageSections = Controller.originalPageSections;
        for (var i = 0; i < Controller.pageSections.length; i++) {
            var section = Controller.pageSections[i];
            for (var j = 0; j < section.items.length ; j++) {
                // Logic to save only items that are modified
                if (section.items[j].Permissions._view != originalPageSections[i].items[j].Permissions._view
                    || section.items[j].Permissions._edit != originalPageSections[i].items[j].Permissions._edit
                    || section.items[j].Permissions._add != originalPageSections[i].items[j].Permissions._add
                    || section.items[j].Permissions._delete != originalPageSections[i].items[j].Permissions._delete
                    || section.items[j].Permissions._execute != originalPageSections[i].items[j].Permissions._execute) {
                    var securityFunction = {'id': section.items[j].id, '_joinData': section.items[j].Permissions};
                    permissions.push(securityFunction);
                }
            }
        }
        permissions = UtilsSvc.urlsafeBase64Encode(JSON.stringify(permissions));

        var postData = {
            'id': Controller.roleId,
            'security_functions': permissions
        };
        UtilsSvc.isAppendLoader(true);
        SecurityPermissionEditSvc.savePermissions(postData)
        .then(function(response) {
            var error = response.data.error;
            if (error instanceof Array && error.length == 0) {
                Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'alertType', 'success');
                Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'message', 'general.edit.success');
                $http.get(Controller.alertUrl)
                .then(function(response) {
                    $window.location.href = Controller.redirectUrl;
                    UtilsSvc.isAppendLoader(false);
                }, function (error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            } else {
                AlertSvc.error(Controller, 'The record is not updated due to errors encountered.');
                UtilsSvc.isAppendLoader(false);
            }
        }, function(error){
            console.log(error);
        });
    }
}
