angular
    .module('student.examination_results.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'student.examination_results.svc'])
    .controller('StudentExaminationResultsCtrl', StudentExaminationResultsController);

StudentExaminationResultsController.$inject = ['$scope', '$location', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'StudentExaminationResultsSvc'];

function StudentExaminationResultsController($scope, $location, $filter, $q, UtilsSvc, AlertSvc, AggridLocaleSvc, StudentExaminationResultsSvc) {
	var vm = this;

    // Variables
    vm.gridOptions = {};

    // Functions
    vm.initGrid = initGrid;
    vm.setGrid = setGrid;
    vm.resetColumnDefs = resetColumnDefs;

    // Initialisation
    angular.element(document).ready(function() {
        StudentExaminationResultsSvc.init(angular.baseUrl);

        UtilsSvc.isAppendLoader(true);
        StudentExaminationResultsSvc.getExaminationGradingTypes()
        // getExaminationGradingTypes
        .then(function(examinationGradingTypes) {
            return StudentExaminationResultsSvc.getAcademicPeriods();
        }, function(error) {
            // No Examination Grading Types
            console.log(error);
            AlertSvc.warning($scope, error);
            UtilsSvc.isAppendLoader(false);
        })
        // getAcademicPeriods
        .then(function(academicPeriods) {
            vm.sections = [];

            angular.forEach(academicPeriods, function(academicPeriod, key) {
                var academicPeriodId = academicPeriod.id;
                var academicPeriodName = academicPeriod.name;
                var academicPeriodOrder = academicPeriod.order;

                StudentExaminationResultsSvc.getStudentExaminationResults(academicPeriodId)
                .then(function(response) {
                    if (angular.isDefined(response[academicPeriodId])) {
                        angular.forEach(response[academicPeriodId], function(examinationObj, examinationId) {
                            var examinationName = StudentExaminationResultsSvc.getExamination(examinationId).code_name;
                            var sectionId = academicPeriodId+'_'+examinationId;
                            var sectionName = academicPeriodName + ' | ' + examinationName;

                            vm.sections.push({
                                id: sectionId,
                                name: sectionName,
                                order: academicPeriodOrder,
                                visible: true
                            });

                            var examinationResults = response[academicPeriodId][examinationId];
                            vm.initGrid(sectionId, academicPeriodId, examinationId, examinationResults);
                        });
                    }
                }, function(error) {
                    console.log(error);
                })
                .finally(function() {
                    if (vm.sections.length == 0) {
                        var errorMessage = 'No Examination Results';
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
            UtilsSvc.isAppendLoader(false);
        })
        .finally(function() {
            // move turn off loader after getting response from student results
            // UtilsSvc.isAppendLoader(false);
        });
    });

    function initGrid(sectionId, academicPeriodId, examinationId, examinationResults) {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
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
                suppressMovableColumns: true,
                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                localeText: localeText,
                onGridReady: function() {
                    vm.setGrid(sectionId, academicPeriodId, examinationId, examinationResults);
                }
            };
        }, function (error) {
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
                suppressMovableColumns: true,
                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                onGridReady: function() {
                    vm.setGrid(sectionId, academicPeriodId, examinationId, examinationResults);
                }
            };
        });
    }

    function setGrid(sectionId, academicPeriodId, examinationId, examinationResults) {
        // initialize columns header
        vm.resetColumnDefs(sectionId);

        UtilsSvc.isAppendSpinner(true, 'student-examination-result-table_'+sectionId);
        StudentExaminationResultsSvc.getRowData(examinationResults)
        // getRowData
        .then(function(rows) {
            vm.gridOptions[sectionId].api.setRowData(rows);
        }, function(error) {
            // No Results
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        .finally(function(){
            UtilsSvc.isAppendSpinner(false, 'student-examination-result-table_'+sectionId);
        });
    }

    function resetColumnDefs(sectionId) {
        var response = StudentExaminationResultsSvc.getColumnDefs();

        if (angular.isDefined(response.error)) {
            console.log(response.error);
            return false;
        } else {
            var columnDefs = response.data;

            var textToTranslate = [];
            angular.forEach(columnDefs, function(value, key) {
                textToTranslate.push(value.headerName);
            });
            StudentExaminationResultsSvc.translate(textToTranslate)
            .then(function(res){
                angular.forEach(res, function(value, key) {
                    columnDefs[key]['headerName'] = value;
                });
                if (vm.gridOptions[sectionId] != null) {
                    vm.gridOptions[sectionId].api.setColumnDefs(columnDefs);
                    vm.gridOptions[sectionId].api.sizeColumnsToFit();
                }
            }, function(error){
                console.log(error);
            });


            return true;
        }
    }
}
