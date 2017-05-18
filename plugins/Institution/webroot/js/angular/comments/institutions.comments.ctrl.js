angular
    .module('institutions.comments.ctrl', ['utils.svc', 'alert.svc', 'institutions.comments.svc'])
    .controller('InstitutionCommentsCtrl', InstitutionCommentsController);

InstitutionCommentsController.$inject = ['$scope', '$anchorScroll', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'InstitutionsCommentsSvc'];

function InstitutionCommentsController($scope, $anchorScroll, $filter, $q, UtilsSvc, AlertSvc, InstitutionsCommentsSvc) {
    var vm = this;
    $scope.action = 'view';
    vm.commentCodeOptions = null;
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
            vm.teacherCommentsRequired = reportCardData.teacher_comments_required;

            return InstitutionsCommentsSvc.getTabs(vm.reportCardId, vm.classId, vm.principalCommentsRequired, vm.homeroomTeacherCommentsRequired, vm.teacherCommentsRequired);
        }, function(error)
        {
            // No Report Card
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // getTabs
        .then(function(tabs)
        {
            vm.tabs = tabs;

            if (angular.isObject(tabs) && tabs.length > 0) {
                var tab = tabs[0];
                vm.initGrid(vm.classId, vm.educationGradeId, tab);
            }

            return InstitutionsCommentsSvc.getCommentCodeOptions();
        }, function(error)
        {
            // No Tabs
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // getCommentCodeOptions
        .then(function(response)
        {
            vm.commentCodeOptions = response.data;

            return InstitutionsCommentsSvc.getModifiedUser();
        }, function(error)
        {
            // No Comment Codes
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // set modified user
        .then(function(response) {
            vm.modifiedUser = response;

        }, function(error) {
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
            vm.onChangeColumnDefs($scope.action, vm.currentTab);
        }
    });

    function initGrid(classId, educationGradeId, tab) {
        vm.gridOptions = {
            context: {},
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

                params.data.modified_by = vm.modifiedUser;

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
                InstitutionsCommentsSvc.getRowData(vm.academicPeriodId, vm.institutionId, vm.classId, vm.educationGradeId, vm.reportCardId, vm.commentCodeOptions, tab, limit, page)
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

        InstitutionsCommentsSvc.getColumnDefs(action, tab, vm.modifiedUser, vm.comments, vm.commentCodeOptions)
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
