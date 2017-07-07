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
    Controller.redirectUrl = '';

    // function
    Controller.changeModule = changeModule;
    Controller.checkAllInSection = checkAllInSection;
    Controller.postForm = postForm;

    angular.element(document).ready(function () {
        SecurityPermissionEditSvc.init(angular.baseUrl);
        var module = Controller.modules[0].key;
        SecurityPermissionEditSvc.getPermissions(Controller.roleId, module)
        .then(function(permissions) {
            var sections = [];
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
            var sections = [];
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
        for (var i = 0; i < Controller.pageSections.length; i++) {
            var section = Controller.pageSections[i];
            for (var j = 0; j < section.items.length ; j++) {
                var securityFunction = {'id': section.items[j].id, '_joinData': section.items[j].Permissions};
                permissions.push(securityFunction);
            }
        }

        permissions = UtilsSvc.urlsafeBase64Encode(JSON.stringify(permissions));

        var postData = {
            'id': Controller.roleId,
            'security_functions': permissions
        };

        SecurityPermissionEditSvc.savePermissions(postData)
        .then(function(response) {
            var error = response.data.error;
            if (error instanceof Array && error.length == 0) {
                Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'alertType', 'success');
                Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'message', 'general.edit.success');
                $http.get(Controller.alertUrl)
                .then(function(response) {
                    $window.location.href = Controller.redirectUrl;
                }, function (error) {
                    console.log(error);
                });
            } else {
                AlertSvc.error(Controller, 'The record is not updated due to errors encountered.');
                angular.forEach(error, function(value, key) {
                    Controller.postError[key] = value;
                })
            }
        }, function(error){
            console.log(error);
        });
    }
}
