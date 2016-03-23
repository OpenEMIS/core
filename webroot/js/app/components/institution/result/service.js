angular.module('institution.result.service', [])
.service('ResultService', function($q, $http, $location) {
    function getResults(_scope) {
        var deferred = $q.defer();

        var _url = '';
        var _params = $location.search();
        console.log($location.search());

        $http({
            method: 'GET', // 'POST'
            url: _url,
            params: _params,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            deferred.resolve(_response);
        }, function errorCallback(_response) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    return {
        getResults: getResults
    }
});
