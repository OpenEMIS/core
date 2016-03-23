angular.module('institution.result.controller', ['institution.result.service'])
.controller('ResultController', function($scope, ResultService) {
    $scope.gridOptions = null;

    angular.element(document).ready(function () {
        ResultService.getResults($scope).then(function successCallback(_response) {
            var columnDefs = [];

            var rowData = [];

            $scope.gridOptions = {
                columnDefs: columnDefs,
                rowData: rowData,
                headerHeight: 38,
                rowHeight: 38,
                onReady: function() {
                    $scope.gridOptions.api.sizeColumnsToFit();
                }
            };
        }, function errorCallback(_response) {

        }, function progressCallback(_response) {

        });
    });
});
