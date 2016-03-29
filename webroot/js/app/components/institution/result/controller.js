angular.module('institution.result.controller', ['institution.result.service'])
.controller('ResultController', function($scope, ResultService) {
    $scope.gridOptions = null;

    angular.element(document).ready(function () {
        ResultService.getSubjects($scope).then(function successCallback(_subjects) {
            $scope.subjects = _subjects;

            ResultService.getColumnDefs($scope).then(function successCallback(_columnDef) {
                ResultService.initGrid($scope, _columnDef, _subjects);
            });
        });
    });

    $scope.$watch('$parent.editMode', function(newValue, oldValue) {
        $scope.editMode = newValue;
        // To-do: switch to edit mode
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
