angular
    .module('student.results.ctrl', ['utils.svc', 'alert.svc', 'student.results.svc'])
    .controller('StudentResultsCtrl', StudentResultsController);

StudentResultsController.$inject = ['$scope', '$location', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'StudentResultsSvc'];

function StudentResultsController($scope, $location, $filter, $q, UtilsSvc, AlertSvc, StudentResultsSvc) {
	var vm = this;
    $scope.gridOptions = null;

    // Functions
    vm.onChangePeriod = onChangePeriod;
    vm.onChangeAssessment = onChangeAssessment;
    vm.initGrid = initGrid;
    vm.resetGrid = resetGrid;
    vm.resetColumnDefs = resetColumnDefs;

    // Initialisation
    angular.element(document).ready(function() {
        StudentResultsSvc.init(angular.baseUrl);
        vm.initGrid();

        UtilsSvc.isAppendLoader(true);
        StudentResultsSvc.getAcademicPeriods()
        .then(function(academicPeriods) {
            vm.periodOptions = academicPeriods;
        }, function(error) {
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    });

    function onChangePeriod(periodId) {
        vm.resetGrid();

        UtilsSvc.isAppendLoader(true);
        StudentResultsSvc.getAssessments(periodId)
        .then(function(assessments) {
            vm.assessmentOptions = assessments;
        }, function(error) {
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        })
        ;
    }

    function onChangeAssessment(assessmentId) {
        vm.resetGrid();

        UtilsSvc.isAppendSpinner(true, 'student-result-table');
        StudentResultsSvc.getAssessmentPeriods(assessmentId)
        // getAssessmentPeriods
        .then(function(assessmentPeriods) {
            if (vm.resetColumnDefs(assessmentPeriods)) {
                return StudentResultsSvc.getRowData();
            }
        }, function(error) {
            console.log(error);
        })
        // getRowData
        .then(function(rows) {
            $scope.gridOptions.api.setRowData(rows);
        }, function(error) {
            // No Results
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendSpinner(false, 'student-result-table');
        });
    }

    function initGrid() {
        $scope.gridOptions = {
            columnDefs: [],
            rowData: [],
            headerHeight: 38,
            rowHeight: 38,
            minColWidth: 200,
            enableColResize: false,
            enableSorting: true,
            unSortIcon: true,
            enableFilter: true,
            suppressMenuHide: true,
            suppressCellSelection: true,
            suppressMovableColumns: true,
            singleClickEdit: true,
            onGridReady: function() {
            }
        };
    }

    function resetGrid() {
        $scope.gridOptions.api.setColumnDefs([]);
        $scope.gridOptions.api.setRowData([]);
    }

    function resetColumnDefs(assessmentPeriods) {
        var response = StudentResultsSvc.getColumnDefs(assessmentPeriods);

        if (angular.isDefined(response.error)) {
            console.log(response.error);

            return false;
        } else {
            if ($scope.gridOptions != null) {
                $scope.gridOptions.api.setColumnDefs(response.data);
            }

            return true;
        }
    }
}
