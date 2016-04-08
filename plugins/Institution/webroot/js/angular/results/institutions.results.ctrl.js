angular.module('institutions.results.ctrl', ['alert.svc', 'institutions.results.svc'])
.controller('ResultCtrl', function($scope, AlertSvc, ResultSvc) {
    $scope.message = null;
    $scope.gridOptions = null;

    angular.element(document).ready(function () {
        $scope.action = 'view';

        // initValues
        ResultSvc.initValues($scope);

        // getAssessment
        ResultSvc.getAssessment($scope).then(function(assessment) {
            $scope.assessment = assessment;
            // getSubjects
            ResultSvc.getSubjects($scope.assessment_id).then(function(subjects) {
                $scope.subjects = subjects;
                // getColumnDefs
                ResultSvc.getColumnDefs($scope).then(function(columnDefs) {
                    $scope.columnDefs = columnDefs;
                    ResultSvc.initGrid($scope);
                }, function(error) {
                    // No Assessment Periods
                    console.log(error);
                    AlertSvc.warning($scope, error);
                });
            }, function(error) {
                // No Assessment Items
                console.log(error);
                AlertSvc.warning($scope, error);
            });
        }, function(error) {
            // No Assessment
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    });

    // $scope.$watch('$parent.action', function(newValue, oldValue) {
    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
            ResultSvc.switchAction($scope);
        }
    });

    $scope.resizeColumns = function() {
        $scope.gridOptions.api.refreshView();
        $scope.gridOptions.api.sizeColumnsToFit();
    };

    $scope.reloadRowData = function(subject) {
        AlertSvc.reset($scope);
        $scope.subject = subject;

        // getRowData
        ResultSvc.isAppendSpinner(true, 'institution-result-table');
        ResultSvc.getRowData($scope).then(function(rowData) {
            ResultSvc.isAppendSpinner(false, 'institution-result-table');
            $scope.gridOptions.api.setRowData(rowData);
        }, function(error) {
            ResultSvc.isAppendSpinner(false, 'institution-result-table');
            // No Students
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    };

    $scope.setRowData = function(data) {
        ResultSvc.setRowData(data, $scope);
    };

    $scope.cellValueChanged = function(params) {
        ResultSvc.cellValueChanged(params, $scope);
    };

    $scope.onEditClick = function() {
        $scope.action = 'edit';
    };

    $scope.onBackClick = function() {
        $scope.action = 'view';
    };

    $scope.onSaveClick = function() {
        ResultSvc.isAppendSpinner(true, 'institution-result-table');
        ResultSvc.saveRowData($scope).then(function(_results) {
        }, function(_errors) {
        }).finally(function() {
            ResultSvc.isAppendSpinner(false, 'institution-result-table');
            $scope.action = 'view';
        });
    };
});
