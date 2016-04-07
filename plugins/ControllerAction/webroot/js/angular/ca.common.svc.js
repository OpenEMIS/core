angular.module('ca.common.svc', [])
.service('caCommonSvc', ['$sce', '$timeout', '$http', '$q', function ($sce, $timeout, $http, $q) {

    this.htmlEntities = function(str) {
        return String(str).replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /**
     * convert object to URI component for AngularJS POST requests
     * @source http://stackoverflow.com/questions/19254029/angularjs-http-post-does-not-send-data
     */
    this.serialize = function (obj) {
        var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

        for(name in obj) {
            value = obj[name];

            if(value instanceof Array) {
                for(i=0; i<value.length; ++i) {
                    subValue = value[i];
                    fullSubName = name + '[' + i + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += serialize(innerObj) + '&';
                }
            }
            else if(value instanceof Object) {
                for(subName in value) {
                    subValue = value[subName];
                    fullSubName = name + '[' + subName + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += serialize(innerObj) + '&';
                }
            }
            else if(value !== undefined && value !== null)
                query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
        }

        return query.length ? query.substr(0, query.length - 1) : query;
    };

    this.ajax = function (params) {
        $http.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
        var deferred = $q.defer();
        var defaultParams = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        };
        var mergedParams = angular.merge(defaultParams, params);
        $http(mergedParams).then(
            function successCallback(_response) {
                deferred.resolve(_response.data);
            }, function errorCallback(_error) {
                deferred.reject(_error);
            }, function progressCallback(_response) {
            }
        );
        return deferred.promise;
    }

}]);

