angular
    .module('institutions.comments.ctrl', ['utils.svc', 'alert.svc', 'institutions.comments.svc'])
    .controller('InstitutionCommentsCtrl', InstitutionCommentsController);

InstitutionCommentsController.$inject = ['$scope', '$anchorScroll', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'InstitutionsCommentsSvc'];

function InstitutionCommentsController($scope, $anchorScroll, $filter, $q, UtilsSvc, AlertSvc, InstitutionsCommentsSvc) {
    var vm = this;
    $scope.action = 'view';
    vm.commentCodeOptions = {};
    vm.currentTab = {};
    vm.comments = {};
    vm.currentUserName = null;
    vm.currentUserId = null;

    // Functions
    vm.initGrid = initGrid;
    vm.onChangeSubject = onChangeSubject;
    vm.onChangeColumnDefs = onChangeColumnDefs;
    vm.onEditClick = onEditClick;
    vm.onBackClick = onBackClick;

    // Initialisation
    angular.element(document).ready(function() {
        InstitutionsCommentsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);

        InstitutionsCommentsSvc.getReportCard($scope.reportCardId)
        // getReportCard
        .then(function(response)
        {
            var reportCardData = response.data;
            vm.educationGradeId = reportCardData.education_grade_id;
            vm.academicPeriodId = reportCardData.academic_period_id;

            vm.principalCommentsRequired = reportCardData.principal_comments_required;
            vm.homeroomTeacherCommentsRequired = reportCardData.homeroom_teacher_comments_required;
            vm.teacherCommentsRequired = reportCardData.teacher_comments_required;

            return InstitutionsCommentsSvc.getCurrentUser();
        }, function(error)
        {
            // No Report Card
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // getCurrentUser
        .then(function(response)
        {
            userData = response;

            // get data of current user
            if (angular.isObject(userData)) {
                vm.currentUserName = userData.first_name + ' ' + userData.last_name;
                vm.currentUserId = userData.id;
            }

            return InstitutionsCommentsSvc.getTabs($scope.reportCardId, $scope.classId, $scope.institutionId, vm.currentUserId, vm.principalCommentsRequired, vm.homeroomTeacherCommentsRequired, vm.teacherCommentsRequired);
        }, function(error)
        {
            // No current user
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // getTabs
        .then(function(tabs)
        {
            vm.tabs = tabs;

            if (angular.isObject(tabs) && tabs.length > 0) {
                var tab = tabs[0];
                vm.initGrid(tab);
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
        }, function(error)
        {
            // No Comment Codes
            console.log(error);
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

    function initGrid(tab) {
        vm.gridOptions = {
            context: {
                institution_id: $scope.institutionId,
                class_id: $scope.classId,
                report_card_id: $scope.reportCardId,
                academic_period_id: vm.academicPeriodId,
                education_grade_id: vm.educationGradeId,
                current_user_id: vm.currentUserId
            },
            columnDefs: [],
            rowData: [],
            headerHeight: 38,
            rowHeight: 38,
            enableColResize: false,
            enableSorting: false,
            unSortIcon: true,
            enableFilter: false,
            suppressMenuHide: true,
            suppressMovableColumns: true,
            singleClickEdit: true,
            rowModelType: 'infinite',
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
            onGridSizeChanged: function(e) {
                this.api.sizeColumnsToFit();
            },
            onCellValueChanged: function(params) {
                if (params.newValue != params.oldValue) {
                    if (angular.isUndefined(vm.comments[params.data.student_id])) {
                        vm.comments[params.data.student_id] = {};
                    }

                    if (angular.isUndefined(vm.comments[params.data.student_id][params.colDef.field])) {
                        vm.comments[params.data.student_id][params.colDef.field] = {};
                    }

                    vm.comments[params.data.student_id][params.colDef.field] = params.newValue;

                    // set last modified user name
                    params.data.modified_by = vm.currentUserName;

                    InstitutionsCommentsSvc.saveSingleRecordData(params, vm.currentTab)
                    .then(function(response) {
                    }, function(error) {
                        console.log(error);
                    });

                    // Important: to refresh the grid after data is modified
                    vm.gridOptions.api.refreshView();
                }
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
                InstitutionsCommentsSvc.getRowData(vm.academicPeriodId, $scope.institutionId, $scope.classId, vm.educationGradeId, $scope.reportCardId, vm.commentCodeOptions, tab, limit, page)
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

        InstitutionsCommentsSvc.getColumnDefs(action, tab, vm.currentUserName, vm.comments, vm.commentCodeOptions)
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
        AlertSvc.info(vm, 'Student comment will be saved after the comment has been entered.');
    }

    function onBackClick() {
        $scope.action = 'view';
    }
}
