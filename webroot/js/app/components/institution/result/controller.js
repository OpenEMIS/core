angular.module('institution.result.controller', ['institution.result.service'])
.controller('ResultController', function($scope, ResultService) {
    $scope.gridOptions = null;

    angular.element(document).ready(function () {
        ResultService.getSubjects($scope).then(function successCallback(_subjects) {
            $scope.subjects = _subjects;

            ResultService.getColumnDefs($scope).then(function successCallback(_columnDef) {
                $scope.gridOptions = {
                    columnDefs: _columnDef,
                    rowData: [],
                    headerHeight: 38,
                    rowHeight: 38,
                    onReady: function() {
                        $scope.gridOptions.api.sizeColumnsToFit();

                        if (_subjects.length > 0) {
                            $scope.reloadData(_subjects[0]);
                        }
                    }
                };
            });
        });
    });

    $scope.reloadData = function(_subject) {
        ResultService.getRowData($scope, _subject).then(function successCallback(_rowData) {
            $scope.gridOptions.api.setRowData(_rowData);
        }, function errorCallback(_error) {
            deferred.reject(_error);
        }, function progressCallback(_response) {

        });
    };
});
