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

    vm.pageSizeDropdown = null;

    // Functions
    vm.initGrid = initGrid;
    vm.onChangeSubject = onChangeSubject;
    vm.onChangeColumnDefs = onChangeColumnDefs;
    vm.onEditClick = onEditClick;
    vm.onBackClick = onBackClick;
    vm.addPageSizeDropdown = addPageSizeDropdown;

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
            rowHeight: 60,
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
            paginationPageSize: 5,
            maxBlocksInCache: 1,
            cacheBlockSize: 10,
            onGridSizeChanged: function(e) {
                this.api.sizeColumnsToFit();
            },
            onCellValueChanged: function(params) {
                if (params.newValue != params.oldValue) {

                    var newVal = params.newValue;
                    var format = /[ `/'"=%]/;
                    if(format.test(newVal.charAt(0))) {
                        AlertSvc.warning(vm, 'Special character not allow at first character of text');
                        return false
                    } else {
                        AlertSvc.info(vm, 'Student comment will be saved after the comment has been entered.');
                    } 

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
                vm.addPageSizeDropdown();
            }
        };
    }

    function onChangeSubject(tab, limit = 10) {
        if (vm.currentTab !== tab) {
            vm.gridOptions.api.paginationSetPageSize(Number(limit));
            if (vm.pageSizeDropdown !== null) vm.pageSizeDropdown.value = 10;
        }

        AlertSvc.reset(vm);
        vm.currentTab = tab;

        if (vm.gridOptions != null) {
            // Always reset
            vm.gridOptions.api.setColumnDefs([]);
            vm.gridOptions.api.setRowData([]);
        }

        vm.onChangeColumnDefs($scope.action, tab);

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

        InstitutionsCommentsSvc.getColumnDefs(action, tab, vm.currentUserName, vm.comments, vm.commentCodeOptions, CommentTextEditor)
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


    // function to act as a class
    function CommentTextEditor () {}

    // gets called once before the renderer is used
    CommentTextEditor.prototype.init = function(params) {
        // create the cell
        this.eInput = document.createElement('textarea');
        this.eInput.setAttribute("id", 'comment');
        this.eInput.value = params.value;
    };

    // gets called once when grid ready to insert the element
    CommentTextEditor.prototype.getGui = function() {
        return this.eInput;
    };

    // returns the new value after editing
    CommentTextEditor.prototype.getValue = function() {
        return this.eInput.value;
    };

    // if true, then this editor will appear in a popup
    CommentTextEditor.prototype.isPopup = function() {
        // and we could leave this method out also, false is the default
        return false;
    };
    
    function addPageSizeDropdown() {
        var wrapper = document.createElement('div');
        wrapper.setAttribute('class', 'display-limit');
        wrapper.setAttribute('style', 'margin: 0');

        var displayLabel = document.createElement('span');
        displayLabel.innerHTML = 'Display';
        displayLabel.setAttribute('style', 'margin: 0');
        wrapper.appendChild(displayLabel);

        var recordLabel = document.createElement('span');
        recordLabel.innerHTML = 'records';

        var dropdown = createDropdownElement();
        wrapper.appendChild(dropdown);

        wrapper.appendChild(recordLabel);

        var paginationBar = document.querySelector('.sg-theme div[ref="south"] .ag-paging-panel');
        paginationBar.appendChild(wrapper);
    }

    function createDropdownElement() {
        var dropdownContainer = document.createElement('div');
        dropdownContainer.setAttribute('class', 'input-select-wrapper');
        dropdownContainer.setAttribute('style', 'margin: 0 5px');

        vm.pageSizeDropdown = document.createElement('select');
        vm.pageSizeDropdown.setAttribute('id', 'page-size');
        vm.pageSizeDropdown.setAttribute('style', 'background-color: #FFFFFF');
        vm.pageSizeDropdown.onchange = onPageSizeChanged;
        var dropdown = document.createElement('select');
        dropdown.setAttribute('id', 'page-size');
        dropdown.setAttribute('style', 'background-color: #FFFFFF');
        dropdown.onchange = onPageSizeChanged;

        //Create and append the options
        for (var i = 10; i <= 20; i += 10) {
            var option = document.createElement("option");
            option.value = i;
            option.text = i;
            vm.pageSizeDropdown.appendChild(option);
        }

        dropdownContainer.appendChild(vm.pageSizeDropdown);
        return dropdownContainer;
    }

    function onPageSizeChanged() {
        var limit = this.value;
        vm.gridOptions.api.gridOptionsWrapper.setProperty('cacheBlockSize', limit);
        vm.gridOptions.api.gridOptionsWrapper.setProperty('paginationPageSize', Number(limit/2));
        vm.gridOptions.api.infinitePageRowModel.resetCache();
        vm.gridOptions.api.paginationSetPageSize(Number(limit));
        vm.count = 0;
        vm.onChangeSubject(vm.currentTab, limit);
        // var value = document.getElementById('page-size').value;
    }
}
