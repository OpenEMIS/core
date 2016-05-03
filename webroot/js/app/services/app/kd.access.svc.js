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
        permissionKey = 'Permissions.';
        key = permissionKey.concat(key);
    	KdSessionSvc.read(key)
    	.then(function(permissionRoles){
    		var permission = false;
            if (permissionRoles == null) {
                permissionRoles = [];
            }

            if (roles == null) {
                if (permissionRoles.length > 0) {
                    permission = true;
                }
            } else {
                var compareRolesArray = [roles, permissionRoles];
                var result = compareRolesArray.shift().filter(function(v) {
                	return compareRolesArray.every(function(a) {
                    	return a.indexOf(v) !== -1;
                    });
                });
                if (result.length > 0) {
                	permission = true;
                }
            }
            deferred.resolve(permission);
        }, function(error) {
        	deferred.reject(error);
        });

        return deferred.promise;
    };
};
