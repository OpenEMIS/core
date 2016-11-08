angular
    .module('examinations.results.ctrl', ['utils.svc', 'alert.svc', 'examinations.results.svc'])
    .controller('ExaminationsResultsCtrl', ExaminationsResultsController);

ExaminationsResultsController.$inject = ['$scope', '$anchorScroll', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'ExaminationsResultsSvc'];

function ExaminationsResultsController($scope, $anchorScroll, $filter, $q, UtilsSvc, AlertSvc, ExaminationsResultsSvc) {
    var vm = this;
    $scope.action = 'view';
    vm.noOptions = [{text: 'No options', value: 0}];
    vm.education_subject = {};
    vm.results = {};

    // Functions
    vm.onChangeAcademicPeriod = onChangeAcademicPeriod;
    vm.onChangeExamination = onChangeExamination;
    vm.onChangeExaminationCentre = onChangeExaminationCentre;
    vm.initGrid = initGrid;
    vm.onChangeSubject = onChangeSubject;
    vm.onChangeColumnDefs = onChangeColumnDefs;
    vm.onEditClick = onEditClick;
    vm.onBackClick = onBackClick;
    vm.onSaveClick = onSaveClick;

    // Initialisation
    angular.element(document).ready(function() {
        ExaminationsResultsSvc.init(angular.baseUrl);

        vm.academicPeriodOptions = vm.noOptions;
        vm.academicPeriodId = 0;
        vm.examinationOptions = vm.noOptions;
        vm.examinationId = 0;
        vm.examinationCentreOptions = vm.noOptions;
        vm.examinationCentreId = 0;

        UtilsSvc.isAppendLoader(true);
        ExaminationsResultsSvc.getAcademicPeriods()
        .then(function(response)
        {
            var academicPeriods = response.data;
            if (angular.isObject(academicPeriods) && academicPeriods.length > 0) 
            {
                var options = [];
                angular.forEach(academicPeriods, function(academicPeriod, i)
                {
                    this.push({text: academicPeriods[i].name.toString(), value: academicPeriods[i].id});
                }, options);

                vm.academicPeriodOptions = options;
                vm.academicPeriodId = options[0].value;

                vm.onChangeAcademicPeriod(vm.academicPeriodId);
            } else {
                vm.academicPeriodOptions = vm.noOptions;
                vm.academicPeriodId = 0;
            }
        }, function(error)
        {
            // No Academic Periods
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendLoader(false);
        });
    });

    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
            vm.onChangeColumnDefs($scope.action, vm.education_subject);
        }
    });

    function onChangeAcademicPeriod(academicPeriodId) {
        UtilsSvc.isAppendLoader(true);
        ExaminationsResultsSvc.getExaminations(academicPeriodId)
        .then(function(response)
        {
            var examinations = response.data;
            if (angular.isObject(examinations) && examinations.length > 0) 
            {
                var options = [];
                options.push({text: '-- Select Examination --', value: '-1'});
                angular.forEach(examinations, function(academicPeriod, i)
                {
                    options.push({text: examinations[i].name.toString(), value: examinations[i].id});
                }, options);

                vm.examinationOptions = options;
                vm.examinationId = options[0].value;
            } else {
                vm.examinationOptions = vm.noOptions;
                vm.examinationId = 0;
            }
        }, function(error)
        {
            // No Examinations
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendLoader(false);
        });
    }

    function onChangeExamination(academicPeriodId, examinationId) {
        UtilsSvc.isAppendLoader(true);
        ExaminationsResultsSvc.getExaminationCentres(academicPeriodId, examinationId)
        // getExaminationCentres
        .then(function(response)
        {
            var examinationCentres = response.data;
            if (angular.isObject(examinationCentres) && examinationCentres.length > 0) 
            {
                var options = [];
                options.push({text: '-- Select Examination Centre --', value: '-1'});
                for(i = 0; i < examinationCentres.length; i++) 
                {   
                    options.push({text: examinationCentres[i].name.toString(), value: examinationCentres[i].id});
                }

                vm.examinationCentreOptions = options;
                vm.examinationCentreId = options[0].value;

                return ExaminationsResultsSvc.getSubjects(examinationId);
            } else {
                vm.examinationCentreOptions = vm.noOptions;
                vm.examinationCentreId = 0;
            }
        }, function(error)
        {
            // No Examination Centres
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // getSubjects
        .then(function(subjects)
        {
            vm.subjects = subjects;
            if (angular.isObject(subjects) && subjects.length > 0) {
                var subject = subjects[0];

                vm.initGrid(academicPeriodId, examinationId, subject);
            }
        }, function(error)
        {
            // No Examination Centres
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendLoader(false);
        });
    }

    function onChangeExaminationCentre(academicPeriodId, examinationId, examinationCentreId) {
        vm.onChangeSubject(academicPeriodId, examinationId, vm.education_subject);
    }

    function initGrid(academicPeriodId, examinationId, subject) {
        vm.gridOptions = {
            context: {
                academic_period_id: academicPeriodId,
                examination_id: examinationId,
                examination_centre_id: 0,
                education_subject_id: 0
            },
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
            onCellValueChanged: function(params) {
                if (params.newValue != params.oldValue) {
                    var institutionId = params.data.institution_id;

                    if (angular.isUndefined(vm.results[params.data.student_id])) {
                        vm.results[params.data.student_id] = {};
                    }

                    if (angular.isUndefined(vm.results[params.data.student_id][institutionId])) {
                        vm.results[params.data.student_id][institutionId] = {marks: ''};
                    }

                    vm.results[params.data.student_id][institutionId]['marks'] = params.newValue;

                    params.data.total_mark = ExaminationsResultsSvc.calculateTotal(params.data);
                    // Important: to refresh the grid after data is modified
                    vm.gridOptions.api.refreshView();
                }
            },
            onGridReady: function() {
                vm.onChangeSubject(academicPeriodId, examinationId, subject);
            }
        };
    }

    function onChangeSubject(academicPeriodId, examinationId, subject) {
        AlertSvc.reset(vm);
        vm.education_subject = subject;

        if (vm.gridOptions != null) {
            // update value in context
            vm.gridOptions.context.examination_centre_id = vm.examinationCentreId;
            vm.gridOptions.context.education_subject_id = subject.id;
            // Always reset
            vm.gridOptions.api.setRowData([]);
        }

        UtilsSvc.isAppendSpinner(true, 'examination-result-table');
        vm.onChangeColumnDefs($scope.action, subject)
        .then(function(response)
        {
            if (response) {
                return ExaminationsResultsSvc.getRowData(academicPeriodId, examinationId, vm.examinationCentreId, subject);
            }
        })
        // getRowData
        .then(function(rows) {
            vm.gridOptions.api.setRowData(rows);
        }, function(error) {
            // No Students
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function() {
            UtilsSvc.isAppendSpinner(false, 'examination-result-table');
        });
    }

    function onChangeColumnDefs(action, subject) {
        var deferred = $q.defer();

        ExaminationsResultsSvc.getColumnDefs(action, subject, vm.results)
        .then(function(cols)
        {
            if (vm.gridOptions != null) {
                vm.gridOptions.api.setColumnDefs(cols);
                if (Object.keys(cols).length < 15) {
                    vm.gridOptions.api.sizeColumnsToFit();
                }
            }

            deferred.resolve(cols);
        }, function(error) {
            // No Columns
            console.log(error);
            AlertSvc.warning(vm, error);

            deferred.reject(error);
        });

        return deferred.promise;
    }

    function onEditClick() {
        $scope.action = 'edit';
    }

    function onBackClick() {
        $scope.action = 'view';
    }

    function onSaveClick() {
        if (vm.gridOptions != null) {
            var academicPeriodId = vm.gridOptions.context.academic_period_id;
            var examinationId = vm.gridOptions.context.examination_id;
            var examinationCentreId = vm.gridOptions.context.examination_centre_id;
            var educationSubjectId = vm.gridOptions.context.education_subject_id;

            UtilsSvc.isAppendSpinner(true, 'examination-result-table');
            ExaminationsResultsSvc.saveRowData(vm.results, vm.education_subject, academicPeriodId, examinationId, examinationCentreId, educationSubjectId)
            .then(function(response) {
            }, function(error) {
                console.log(error);
            })
            .finally(function() {
                vm.gridOptions.api.forEachNode(function(row) {
                    ExaminationsResultsSvc.saveTotal(row.data, row.data.student_id, row.data.institution_id, row.data.education_grade_id, academicPeriodId, examinationId, examinationCentreId, educationSubjectId);
                });

                $scope.action = 'view';
                // reset results object
                vm.results = {};
                UtilsSvc.isAppendSpinner(false, 'examination-result-table');
            });
        } else {
            $scope.action = 'view';
        }
    }
}
