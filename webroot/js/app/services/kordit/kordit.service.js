angular.module('kordit.service', [])
.factory('korditService', ['$sce', '$timeout', '$http', function ($sce, $timeout, $http) {

    function _htmlEntities(str) {
        return String(str).replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /**
     * convert object to URI component for AngularJS POST requests
     * @source http://stackoverflow.com/questions/19254029/angularjs-http-post-does-not-send-data
     */
    function _serialize(obj) {
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

    $http.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    function _ajax(params) {
        var defaultParams = {
            method: 'GET',
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        };
        // var mergedParams = angular.extend(object1, object2);
        var mergedParams = angular.merge(defaultParams, params);
        $http(mergedParams).then(
            function successCallback(_response) {
                return response.data;
            }, function errorCallback(_error) {

            }, function progressCallback(_response) {

            }
        );
    }

    return {
        serialize: _serialize,
        htmlEntities: _htmlEntities,
        ajax: _ajax
    };
}]);

