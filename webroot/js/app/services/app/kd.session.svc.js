angular
	.module('kd.session.svc', [])
	.service('KdSessionSvc', KdSessionSvc);

KdSessionSvc.$inject = ['$q', '$http'];

function KdSessionSvc($q, $http) {
	var $this = this;
	var _base = '';
	var _controller = 'restful';
	var _model = '_session';

	var service = {
		base: base,
		check: check,
		read: read,
		write: write,
		remove: remove
	};

    return service;

    function base(url) {
    	_base = url;
    };

    function check(key) {
    	var settings = {
            headers: {'Content-Type': 'application/json'},
            defer: true,
            method: 'CHECK',
            url: toURL() + '/' + key
        };
        return ajax(settings);
    };

    function read(key) {
    	var settings = {
            headers: {'Content-Type': 'application/json'},
            defer: true,
            method: 'GET',
            url: toURL() + '/' + key
        };
        return ajax(settings);
    };

    function write(key, value) {
    	var data = {};
    	data[key] = value;

        var settings = {
            headers: {'Content-Type': 'application/json'},
            data: data,
            defer: true,
            method: 'POST',
            url: toURL()
        };
        return ajax(settings);
    };

    function remove(key) {

    };

    function ajax(settings) {
        var success = null;
        var error = null;
        var deferred = null;

        var requireDeferred = settings.defer != undefined && settings.defer == true;

        if (requireDeferred) {
            deferred = $q.defer();
        }

        var hasSuccessCallback = settings.success != undefined;

        if (hasSuccessCallback && !requireDeferred) {
            success = settings.success;
        } else if (hasSuccessCallback && requireDeferred) {
            success = function(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    settings.success(response, deferred);
                }
            };
        } else if (!hasSuccessCallback && requireDeferred) {
            success = function(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    deferred.resolve(response.data.data);
                }
            };
        }
        
        if (settings.error != undefined) {
            error = settings.error;
        } else {
            if (requireDeferred) {
                error = function(error) {
                    deferred.reject(error);
                };
            }
        }

        if (settings.headers == undefined) {
            settings.headers = {'Content-Type': 'application/x-www-form-urlencoded'};
        }

        if (success == null && error == null) {
            return $http(settings);
        }

        var httpResponse = $http(settings).then(success, error);
        return requireDeferred ? deferred.promise : httpResponse;
    };

    function toURL() {
        var url = [_base, _controller, _model].join('/');
        
        return url;
    };
};
