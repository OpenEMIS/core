angular
	.module('kd.access.svc', [])
	.service('KdAccessSvc', KdAccessSvc);

KdSessionSvc.$inject = ['KdSessionSvc'];

function KdAccessSvc(KdSessionSvc) {
	var $this = this;
	var _base = '';
	var _model = '_access';

	var service = {
		check: check,
	};

    return service;

    function check(key, roles) {
    	KdSessionSvc.read(key).then(function(value){
            console.log(roles);
            return value;
        });
    };
};
