angular.module('institution.result.controller', ['institution.result.service'])
.controller('ResultCtrl', function($scope, AlertSvc, ResultSvc) {
    $scope.gridOptions = null;

    angular.element(document).ready(function () {
        // initValues
        ResultSvc.initValues($scope);

        // getAssessment
        ResultSvc.getAssessment($scope).then(function(assessment) {
            $scope.assessment = assessment;
            // getSubjects
            ResultSvc.getSubjects($scope).then(function(subjects) {
                $scope.subjects = subjects;
                // getColumnDefs
                ResultSvc.getColumnDefs($scope).then(function(columnDefs) {
                    $scope.columnDefs = columnDefs;
                    ResultSvc.initGrid($scope);
                }, function(error) {
                    // No Assessment Periods
                    console.log(error);
                    AlertSvc.warning(error);
                });
            }, function(error) {
                // No Assessment Items
                console.log(error);
                AlertSvc.warning(error);
            });
        }, function(error) {
            // No Assessment
            console.log(error);
            AlertSvc.warning(error);
        });
    });

    $scope.$watch('$parent.action', function(newValue, oldValue) {
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
        AlertSvc.reset();
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
            AlertSvc.warning(error);
        });
    };

    $scope.setRowData = function(data) {
        ResultSvc.setRowData(data, $scope);
    };

    $scope.cellValueChanged = function(params) {
        ResultSvc.cellValueChanged(params, $scope);
    };
});
