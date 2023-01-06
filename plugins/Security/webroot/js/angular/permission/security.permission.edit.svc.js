angular
    .module('security.permission.edit.svc', ['kd.data.svc'])
    .service('SecurityPermissionEditSvc', SecurityPermissionEditSvc);

SecurityPermissionEditSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function SecurityPermissionEditSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        translate: translate,
        getRoleData: getRoleData,
        getPermissions: getPermissions,
        savePermissions: savePermissions
    };

    var models = {
        SecurityFunctions: 'Security.SecurityFunctions',
        SecurityRoles: 'Security.SecurityRoles'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('Permissions');
        KdDataSvc.init(models);
    };

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }

    function getRoleData(roleId) {
        return SecurityRoles.get(roleId).ajax({defer: true});
    }

    function getPermissions(roleId, module) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return SecurityFunctions
            .select()
            .find('permissions', {'roleId': roleId, 'module': module, 'translate': 1})
            .ajax({success: success, defer: true});
    }

    function savePermissions(data) {
        SecurityRoles.reset();
        return SecurityRoles.edit(data);
    }
};
