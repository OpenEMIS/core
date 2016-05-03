angular
	.module('kd.access.svc', ['kd.session.svc'])
	.service('KdAccessSvc', KdAccessSvc);

KdAccessSvc.$inject = ['$http', '$q', 'KdSessionSvc'];

function KdAccessSvc($http, $q, KdSessionSvc) {
	var service = {
		checkPermission: checkPermission,
	};

    return service;

    function checkPermission(key, roles) {
    	var deferred = $q.defer();
    	KdSessionSvc.read(key)
    	.then(function(permissionRoles){
    		var permission = false;
            if (permissionRoles == null) {
                permissionRoles = [];
            }
            var compareRolesArray = [roles, permissionRoles];
            var result = compareRolesArray.shift().filter(function(v) {
            	return compareRolesArray.every(function(a) {
                	return a.indexOf(v) !== -1;
                });
            });
            if (result.length > 0) {
            	permission = true;
            }
            console.log(permission);
            deferred.resolve(permission);
        }, function(error) {
        	deferred.reject(error);
        });

        return deferred.promise;
    };
};
