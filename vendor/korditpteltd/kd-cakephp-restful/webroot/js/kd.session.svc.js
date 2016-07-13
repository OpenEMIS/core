angular
    .module('kd.session.svc', [])
    .service('KdSessionSvc', KdSessionSvc);

KdSessionSvc.$inject = ['$q', '$http'];

function KdSessionSvc($q, $http) {
    var $this = this;
    var _base = '';
    var _controller = 'session';
    var _settings = {
        headers: {'Content-Type': 'application/json'},
        defer: true
    };

    var service = {
        base: base,
        write: write,
        check: check,
        read: read,
        remove: remove
    };

    return service;

    function base(url) {
        _base = url;
    };

    function write(key, value) {
        var data = {};
        data[key] = value;

        var settings = _settings;
        settings['method'] = 'POST';
        settings['data'] = data;
        settings['url'] = toURL();
        return ajax(settings);
    };

    function check(key) {
        return send(key, 'CHECK');
    };

    function read(key) {
        return send(key, 'GET');
    };

    function remove(key) {
        return send(key, 'DELETE');
    };

    function send(key, method) {
        var settings = _settings;
        settings['method'] = method;
        settings['url'] = toURL() + '/' + key;
        return ajax(settings);
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
        return [_base, _controller].join('/');
    };
};
