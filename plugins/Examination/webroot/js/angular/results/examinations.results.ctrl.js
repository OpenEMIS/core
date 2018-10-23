angular
    .module('examinations.results.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'examinations.results.svc'])
    .controller('ExaminationsResultsCtrl', ExaminationsResultsController);

ExaminationsResultsController.$inject = ['$scope', '$anchorScroll', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'ExaminationsResultsSvc'];

function ExaminationsResultsController($scope, $anchorScroll, $filter, $q, UtilsSvc, AlertSvc, AggridLocaleSvc,ExaminationsResultsSvc) {
    var vm = this;
    $scope.action = 'view';
    vm.noOptions = [{text: 'No options', value: 0}];
    vm.education_subject = {};
    vm.results = {};

    // Functions
    vm.initGrid = initGrid;
    vm.onChangeSubject = onChangeSubject;
    vm.onChangeColumnDefs = onChangeColumnDefs;
    vm.onEditClick = onEditClick;
    vm.onBackClick = onBackClick;
    vm.onSaveClick = onSaveClick;

    // Initialisation
    angular.element(document).ready(function() {
        vm.academicPeriodId = UtilsSvc.requestQuery('academic_period_id');
        vm.examinationId = UtilsSvc.requestQuery('examination_id');
        vm.examinationCentreId = UtilsSvc.requestQuery('examination_centre_id');
        ExaminationsResultsSvc.init(angular.baseUrl);

        UtilsSvc.isAppendLoader(true);
        ExaminationsResultsSvc.getExaminationCentreExaminations(vm.examinationCentreId, vm.examinationId)
        // getExaminationCentre
        .then(function(response)
        {
            var data = response.data[0];
            var examinationCentreData = data.examination_centre;
            var academicPeriodData = data.academic_period;
            var examinationData = data.examination;

            vm.academicPeriodOptions = [{text: academicPeriodData.name, value: academicPeriodData.id}];
            vm.examinationOptions = [{text: examinationData.code_name, value: examinationData.id}];
            vm.examinationCentreOptions = [{text: examinationCentreData.code_name, value: examinationCentreData.id}];
            return ExaminationsResultsSvc.getSubjects(vm.examinationId, vm.examinationCentreId);
        }, function(error)
        {
            // No Examination Centre
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // getSubjects
        .then(function(subjects)
        {
            vm.subjects = subjects;
            if (angular.isObject(subjects) && subjects.length > 0) {
                var subject = subjects[0];

                vm.initGrid(vm.academicPeriodId, vm.examinationId, vm.examinationCentreId, subject);
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
    });

    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
            vm.onChangeColumnDefs($scope.action, vm.education_subject);
        }
    });

    function initGrid(academicPeriodId, examinationId, examinationCentreId, subject) {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            vm.gridOptions = {
                context: {
                    academic_period_id: academicPeriodId,
                    examination_id: examinationId,
                    examination_centre_id: examinationCentreId,
                    education_subject_id: 0,
                    examination_item_id: 0
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                enableColResize: false,
                enableSorting: false,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                rowModelType: 'infinite',
                enableServerSideFilter: true,
                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                pagination: true,
                paginationPageSize: 10,
                maxBlocksInCache: 1,
                cacheBlockSize: 10,
                localeText: localeText,
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                onCellValueChanged: function(params) {
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
                },
                onGridReady: function() {
                    vm.onChangeSubject(academicPeriodId, examinationId, subject);
                    this.api.sizeColumnsToFit();
                }
            };
        }, function(error){
            vm.gridOptions = {
                context: {
                    academic_period_id: academicPeriodId,
                    examination_id: examinationId,
                    examination_centre_id: 0,
                    education_subject_id: 0,
                    examination_item_id: 0
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                enableColResize: false,
                enableSorting: false,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                rowModelType: 'infinite',
                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                enableServerSideFilter: true,
                ensureDomOrder: true,
                pagination: true,
                paginationPageSize: 10,
                maxBlocksInCache: 1,
                cacheBlockSize: 10,
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                onCellValueChanged: function(params) {
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
                },
                onGridReady: function() {
                    vm.onChangeSubject(academicPeriodId, examinationId, subject);
                    this.api.sizeColumnsToFit();
                }
            };
        });
    }

    function onChangeSubject(academicPeriodId, examinationId, subject) {
        AlertSvc.reset(vm);
        vm.education_subject = subject;

        if (vm.gridOptions != null) {
            // update value in context
            vm.gridOptions.context.examination_centre_id = vm.examinationCentreId;
            vm.gridOptions.context.education_subject_id = subject.education_subject_id;
            vm.gridOptions.context.examination_item_id = subject.id;
            // Always reset
            vm.gridOptions.api.setColumnDefs([]);
            vm.gridOptions.api.setRowData([]);
        }

        vm.onChangeColumnDefs($scope.action, subject);

        var limit = 10;
        var dataSource = {
            pageSize: limit,
            getRows: function (params) {
                var filterModel = params.filterModel;
                var page = parseInt(params.startRow / limit) + 1;

                UtilsSvc.isAppendSpinner(true, 'examination-result-table');
                ExaminationsResultsSvc.getRowData(academicPeriodId, examinationId, vm.examinationCentreId, subject, limit, page, filterModel)
                .then(function(response) {
                    var lastRowIndex = response.data.total;

                    if (lastRowIndex > 0) {
                        var rows = response.data.data;
                        params.successCallback(rows, lastRowIndex);
                    } else {
                        // No Students
                        params.failCallback();
                    }
                }, function(error) {
                    console.log(error);
                })
                .finally(function() {
                    UtilsSvc.isAppendSpinner(false, 'examination-result-table');
                });
            }
        };

        vm.gridOptions.api.setDatasource(dataSource);
    }

    function onChangeColumnDefs(action, subject) {
        var deferred = $q.defer();

        ExaminationsResultsSvc.getColumnDefs(action, subject, vm.results)
        .then(function(res){
            var textToTranslate = [];
            var defer = $q.defer();
            angular.forEach(res, function(value, key) {
                textToTranslate.push(value.headerName);
            });
            ExaminationsResultsSvc.translate(textToTranslate)
            .then(function(respond){
                angular.forEach(respond, function(value, key) {
                    res[key]['headerName'] = value;
                });
                defer.resolve(res);
            }, function(error){
                defer.resolve(res);
            });
            return defer.promise;
        }, function(error){
            console.log(error);
        })
        .then(function(cols)
        {
            if (vm.gridOptions != null) {
                vm.gridOptions.api.setColumnDefs(cols);
                vm.gridOptions.api.sizeColumnsToFit();
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
            var examinationItemId = vm.gridOptions.context.examination_item_id;

            UtilsSvc.isAppendSpinner(true, 'examination-result-table');
            ExaminationsResultsSvc.saveRowData(vm.results, vm.education_subject, academicPeriodId, examinationId, examinationCentreId, educationSubjectId, examinationItemId)
            .then(function(response) {
            }, function(error) {
                console.log(error);
            })
            .finally(function() {
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
