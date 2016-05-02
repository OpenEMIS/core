angular
	.module('kd.access.svc', ['kd.session.svc'])
	.service('KdAccessSvc', KdAccessSvc);

KdAccessSvc.$inject = ['$q', 'KdSessionSvc'];

function KdAccessSvc($q, KdSessionSvc) {
	var $this = this;
	var _base = '';
	var _model = '_access';

	var service = {
		check: check,
	};

    return service;

    function checkPermission(key, roles) {
    	var deferred = $q.defer();
    	KdSessionSvc.read(key)
    	.then(function(permissionRoles){
    		var permission = false;
            var compareRolesArray = [roles, permissionRoles];
            var result = compareRolesArray.shift().filter(function(v) {
            	return compareRolesArray.every(function(a) {
                	return a.indexOf(v) !== -1;
                });
            });
            if (result.length > 0) {
            	permission = true;
            }
            deferred.resolve(permission);
        }, function(error) {
        	deferred.reject(error);
        });

        return deferred.promise;
    };
};
