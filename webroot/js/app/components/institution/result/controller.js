angular.module('institution.result.controller', ['institution.result.service'])
.controller('ResultController', function($scope, ResultService) {
    $scope.gridOptions = null;

    angular.element(document).ready(function () {
        // initValues
        ResultService.initValues($scope);

        // getAssessment
        ResultService.getAssessment($scope).then(function successCallback(_assessment) {
            $scope.assessment = _assessment;
            // getSubjects
            ResultService.getSubjects($scope).then(function successCallback(_subjects) {
                $scope.subjects = _subjects;
                // getColumnDefs
                ResultService.getColumnDefs($scope).then(function successCallback(_columnDefs) {
                    $scope._columnDefs = _columnDefs;
                    ResultService.initGrid($scope);
                });
            });
        });
    });

    $scope.$watch('$parent.editMode', function(newValue, oldValue) {
        $scope.editMode = newValue;
        // To-do: switch to edit mode
        ResultService.switchMode($scope);
    });

    $scope.reloadData = function(_subject) {
        $scope.subject = _subject;
        // getRowData
        ResultService.isAppendSpinner(true, 'institution-result-table');
        ResultService.getRowData($scope).then(function successCallback(_rowData) {
            ResultService.isAppendSpinner(false, 'institution-result-table');
            $scope.gridOptions.api.setRowData(_rowData);
        }, function errorCallback(_error) {
            ResultService.isAppendSpinner(false, 'institution-result-table');
            deferred.reject(_error);
        }, function progressCallback(_response) {
            ResultService.isAppendSpinner(false, 'institution-result-table');
        });
    };
});
