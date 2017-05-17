angular
    .module('security.permission.edit.svc', ['kd.data.svc'])
    .service('SecurityPermissionEditSvc', SecurityPermissionEditSvc);

SecurityPermissionEditSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function SecurityPermissionEditSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getPermissions: getPermissions,
        translate: translate
    };

    var models = {
        SecurityFunctions: 'Security.SecurityFunctions'
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

    function getPermissions(roleId, module) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return SecurityFunctions
            .select()
            .find('permission', {'roleId': roleId, 'module': module})
            .ajax({success: success, defer: true});
    }
};
