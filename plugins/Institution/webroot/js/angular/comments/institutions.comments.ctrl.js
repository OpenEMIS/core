angular
    .module('institutions.comments.ctrl', ['utils.svc', 'alert.svc', 'institutions.comments.svc'])
    .controller('InstitutionCommentsCtrl', InstitutionCommentsController);

InstitutionCommentsController.$inject = ['$scope', '$anchorScroll', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'InstitutionsCommentsSvc'];

function InstitutionCommentsController($scope, $anchorScroll, $filter, $q, UtilsSvc, AlertSvc, InstitutionsCommentsSvc) {
    var vm = this;
    $scope.action = 'view';
    vm.currentTab = {};
    vm.comments = {};

    // Functions
    vm.initGrid = initGrid;
    vm.onChangeSubject = onChangeSubject;
    vm.onChangeColumnDefs = onChangeColumnDefs;
    vm.onEditClick = onEditClick;
    vm.onBackClick = onBackClick;
    vm.onSaveClick = onSaveClick;

    // Initialisation
    angular.element(document).ready(function() {
        vm.institutionId = UtilsSvc.requestQuery('institution_id');
        vm.classId = UtilsSvc.requestQuery('institution_class_id');
        vm.reportCardId = UtilsSvc.requestQuery('report_card_id');
        InstitutionsCommentsSvc.init(angular.baseUrl);

        UtilsSvc.isAppendLoader(true);
        InstitutionsCommentsSvc.getReportCard(vm.reportCardId)
        // getReportCard
        .then(function(response)
        {
            var reportCardData = response.data;
            vm.educationGradeId = reportCardData.education_grade_id;
            vm.academicPeriodId = reportCardData.academic_period_id;

            vm.principalCommentsRequired = reportCardData.principal_comments_required;
            vm.homeroomTeacherCommentsRequired = reportCardData.homeroom_teacher_comments_required;
            vm.subjectTeacherCommentsRequired = reportCardData.subject_teacher_comments_required;

            var tabs = [];
            if (vm.principalCommentsRequired) {
                tabs.push({
                    tabName: "Principal",
                    type: "PRINCIPAL",
                    education_subject_id: 0
                });
            }
            if (vm.homeroomTeacherCommentsRequired) {
                tabs.push({
                    tabName: "Homeroom Teacher",
                    type: "HOMEROOM_TEACHER",
                    education_subject_id: 0
                });
            }
            vm.tabs = tabs;

            if (vm.subjectTeacherCommentsRequired) {
                return InstitutionsCommentsSvc.getSubjects(vm.reportCardId, vm.classId);
            }
        }, function(error)
        {
            // No Report Card
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // getSubjects
        .then(function(subjects)
        {
            if (angular.isObject(subjects) && subjects.length > 0) {
                angular.forEach(subjects, function(subjects, key)
                {
                    this.push({
                        tabName: subjects.code + " - " + subjects.name + " Teacher",
                        type: "SUBJECT_TEACHER",
                        education_subject_id: subjects.education_subject_id,
                    });
                }, vm.tabs);
            }
        }, function(error)
        {
            // No Subjects
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            if (angular.isObject(vm.tabs) && vm.tabs.length > 0) {
                var tab = vm.tabs[0];
                vm.initGrid(vm.classId, vm.educationGradeId, tab);
            } else {
                AlertSvc.warning(vm, 'You have to configure the comments required first');
            }

            UtilsSvc.isAppendLoader(false);
        });
    });

    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
            vm.onChangeColumnDefs($scope.action, vm.currentTab);
        }
    });

    function initGrid(classId, educationGradeId, tab) {
        vm.gridOptions = {
            context: {
                education_subject_id: 0,
            },
            columnDefs: [],
            rowData: [],
            headerHeight: 38,
            rowHeight: 38,
            enableColResize: false,
            enableSorting: true,
            unSortIcon: true,
            enableFilter: true,
            suppressMenuHide: true,
            suppressCellSelection: true,
            suppressMovableColumns: true,
            singleClickEdit: true,
            rowModelType: 'pagination',
            onGridSizeChanged: function(e) {
                this.api.sizeColumnsToFit();
            },
            onCellValueChanged: function(params) {
                if (angular.isUndefined(vm.comments[params.data.student_id])) {
                    vm.comments[params.data.student_id] = {};
                }

                if (angular.isUndefined(vm.comments[params.data.student_id][params.colDef.field])) {
                    vm.comments[params.data.student_id][params.colDef.field] = {};
                }

                vm.comments[params.data.student_id][params.colDef.field] = params.newValue;

                // Important: to refresh the grid after data is modified
                vm.gridOptions.api.refreshView();
            },
            onGridReady: function() {
                vm.onChangeSubject(tab);
                this.api.sizeColumnsToFit();
            }
        };
    }

    function onChangeSubject(tab) {
        AlertSvc.reset(vm);
        vm.currentTab = tab;
        console.log(vm.currentTab);
        if (vm.gridOptions != null) {
            // Always reset
            vm.gridOptions.api.setColumnDefs([]);
            vm.gridOptions.api.setRowData([]);
        }

        vm.onChangeColumnDefs($scope.action, tab);

        var limit = 10;
        var dataSource = {
            pageSize: limit,
            getRows: function (params) {
                var page = parseInt(params.startRow / limit) + 1;

                UtilsSvc.isAppendSpinner(true, 'institution-comment-table');
                InstitutionsCommentsSvc.getRowData(vm.academicPeriodId, vm.institutionId, vm.classId, vm.educationGradeId, vm.reportCardId, tab, limit, page)
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
                    UtilsSvc.isAppendSpinner(false, 'institution-comment-table');
                });
            }
        };

        vm.gridOptions.api.setDatasource(dataSource);
    }

    function onChangeColumnDefs(action, tab) {
        var deferred = $q.defer();

        InstitutionsCommentsSvc.getColumnDefs(action, tab, vm.comments)
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
            UtilsSvc.isAppendSpinner(true, 'institution-comment-table');
            InstitutionsCommentsSvc.saveRowData(vm.comments, vm.currentTab, vm.institutionId, vm.classId, vm.educationGradeId, vm.academicPeriodId, vm.reportCardId)
            .then(function(response) {
            }, function(error) {
                console.log(error);
            })
            .finally(function() {
                vm.gridOptions.api.forEachNode(function(row) {
                    InstitutionsCommentsSvc.saveTotal(row.data, row.data.student_id, row.data.institution_id, row.data.education_grade_id, academicPeriodId, examinationId, examinationCentreId, educationSubjectId, examinationItemId);
                });

                $scope.action = 'view';
                // reset comments object
                vm.comments = {};
                UtilsSvc.isAppendSpinner(false, 'institution-comment-table');
            });
        } else {
            $scope.action = 'view';
        }
    }
}
