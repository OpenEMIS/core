angular.module('institution.result.service', [])
.service('ResultService', function($q, $http, $location) {
    function getSubjects(_scope) {
        var deferred = $q.defer();

        var _url = _scope.url('rest/Assessment-AssessmentItems.json?assessment_id=' + $location.search()['assessment_id']);

        $http({
            method: 'GET', // 'POST'
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            deferred.resolve(_response.data.data);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    function getColumnDefs(_scope) {
        var deferred = $q.defer();

        var _assessmentId = $location.search()['assessment_id'];
        var _url = _scope.url('rest/Assessment-AssessmentPeriods.json?assessment_id=' + _assessmentId);

        $http({
            method: 'GET', // 'POST'
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            var periods = _response.data.data;

            var columnDefs = [
                {headerName: "OpenEMIS ID", field: "openemis_id"},
                {headerName: "Name", field: "name"}
            ];

            for (var key in periods) {
                if (periods.hasOwnProperty(key)) {
                    var period = periods[key];
                    columnDefs.push({headerName: period.name + " (" + period.weight + ")" , field: "period_" + period.id});
                }
            }

            columnDefs.push({headerName: "Total", field: "total"});

            deferred.resolve(columnDefs);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    function getRowData(_scope, _subject) {
        var deferred = $q.defer();

        // Always reset
        _scope.gridOptions.api.setRowData([]);

        var _url = '';

        $http({
            method: 'GET', // 'POST'
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            deferred.resolve(_response.data.data);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    return {
        getSubjects: getSubjects,
        getColumnDefs: getColumnDefs,
        getRowData: getRowData
    }
});
