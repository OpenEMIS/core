angular
    .module('student.results.ctrl', ['utils.svc', 'alert.svc', 'student.results.svc'])
    .controller('StudentResultsCtrl', StudentResultsController);

StudentResultsController.$inject = ['$scope', '$location', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'StudentResultsSvc'];

function StudentResultsController($scope, $location, $filter, $q, UtilsSvc, AlertSvc, StudentResultsSvc) {
	var vm = this;

    // Variables
    vm.gridOptions = {};

    // Functions
    vm.initGrid = initGrid;
    vm.setGrid = setGrid;
    vm.resetColumnDefs = resetColumnDefs;

    // Initialisation
    angular.element(document).ready(function() {
        StudentResultsSvc.init(angular.baseUrl);

        UtilsSvc.isAppendLoader(true);
        StudentResultsSvc.getAssessmentGradingTypes()
        // getAssessmentGradingTypes
        .then(function(assessmentGradingTypes) {
            return StudentResultsSvc.getAcademicPeriods();
        }, function(error) {
            // No Assessment Grading Types
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        // getAcademicPeriods
        .then(function(academicPeriods) {
            vm.sections = [];

            angular.forEach(academicPeriods, function(academicPeriod, key) {
                var academicPeriodId = academicPeriod.id;
                var academicPeriodName = academicPeriod.name;
                var academicPeriodOrder = academicPeriod.order;

                StudentResultsSvc.getStudentResults(academicPeriodId)
                .then(function(response) {
                    if (angular.isDefined(response[academicPeriodId])) {
                        angular.forEach(response[academicPeriodId], function(assessmentObj, assessmentId) {
                            var assessmentName = StudentResultsSvc.getAssessment(assessmentId).code_name;
                            var sectionId = academicPeriodId+'_'+assessmentId;
                            var sectionName = academicPeriodName + ' | ' + assessmentName;

                            vm.sections.push({
                                id: sectionId,
                                name: sectionName,
                                order: academicPeriodOrder,
                                visible: true
                            });

                            var assessmentResults = response[academicPeriodId][assessmentId];
                            vm.initGrid(sectionId, academicPeriodId, assessmentId, assessmentResults);
                        });
                    }
                }, function(error) {
                    console.log(error);
                })
                .finally(function() {
                    if (vm.sections.length == 0) {
                        var errorMessage = 'No Results';
                        console.log(errorMessage);
                        AlertSvc.warning($scope, errorMessage);
                    } else {
                        AlertSvc.reset($scope);
                    }
                    UtilsSvc.isAppendLoader(false);
                });
            });
        }, function(error) {
            // No Academic Periods
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        .finally(function() {
            // move turn off loader after getting response from student results
            // UtilsSvc.isAppendLoader(false);
        });
    });

    function initGrid(sectionId, academicPeriodId, assessmentId, assessmentResults) {
        vm.gridOptions[sectionId] = {
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
            onGridReady: function() {
                vm.setGrid(sectionId, academicPeriodId, assessmentId, assessmentResults);
            }
        };
    }

    function setGrid(sectionId, academicPeriodId, assessmentId, assessmentResults) {
        UtilsSvc.isAppendSpinner(true, 'student-result-table_'+sectionId);
        StudentResultsSvc.getAssessmentPeriods(assessmentId)
        // getAssessmentPeriods
        .then(function(assessmentPeriods) {
            if (vm.resetColumnDefs(sectionId, assessmentPeriods)) {
                return StudentResultsSvc.getRowData(assessmentResults);
            }
        }, function(error) {
            // Assessment Periods is not configured
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        // getRowData
        .then(function(rows) {
            vm.gridOptions[sectionId].api.setRowData(rows);
        }, function(error) {
            // No Results
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        .finally(function(){
            UtilsSvc.isAppendSpinner(false, 'student-result-table_'+sectionId);
        });
    }

    function resetColumnDefs(sectionId, assessmentPeriods) {
        var response = StudentResultsSvc.getColumnDefs(assessmentPeriods);

        if (angular.isDefined(response.error)) {
            console.log(response.error);
            return false;
        } else {
            var columnDefs = response.data;
            if (vm.gridOptions[sectionId] != null) {
                vm.gridOptions[sectionId].api.setColumnDefs(columnDefs);
            }

            return true;
        }
    }
}
