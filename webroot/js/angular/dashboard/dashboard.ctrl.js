angular
    .module('dashboard.ctrl', ['utils.svc', 'alert.svc', 'dashboard.svc'])
    .controller('DashboardCtrl', DashboardController);

DashboardController.$inject = ['$scope', '$location', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'DashboardSvc'];

function DashboardController($scope, $location, $filter, $q, UtilsSvc, AlertSvc, DashboardSvc) {
	var vm = this;

    // Variables
    vm.collapse = "true";
    vm.notices = [];
    vm.workbenchItems = [];
    vm.workbenchTitle = "";
    vm.target = '';
    vm.gridOptions = {};

    // Functions
    vm.showSplitContentResponsive = showSplitContentResponsive;
    vm.removeSplitContentResponsive = removeSplitContentResponsive;
    vm.initNotices = initNotices;
    vm.initWorkbenchItems = initWorkbenchItems;
    vm.initGrid = initGrid;
    vm.onChangeModel = onChangeModel;

    // Initialisation
    angular.element(document).ready(function() {
        DashboardSvc.init(angular.baseUrl);

        vm.initNotices();
        vm.initWorkbenchItems();
    });

    function showSplitContentResponsive() {
        vm.collapse = "false";
    }

    function removeSplitContentResponsive() {
        vm.collapse = "true";

        vm.workbenchTitle = '';
        vm.gridOptions[vm.target].api.setRowData([]);
    }

    function initNotices() {
        UtilsSvc.isAppendSpinner(true, 'dashboard-notices-table');
        DashboardSvc.getNotices()
        .then(function(notices) {
            vm.notices = notices;
            if (vm.notices.length == 0) {
                vm.notices = false;
            }
        }, function(error) {
            // No Notices
            console.log(error);
            vm.notices = false;
            AlertSvc.warning($scope, error);
        })
        .finally(function() {
            UtilsSvc.isAppendSpinner(false, 'dashboard-notices-table');
        });
    }

    function initWorkbenchItems() {
        var workbenchItems = DashboardSvc.getWorkbenchItems();

        UtilsSvc.isAppendSpinner(true, 'dashboard-workbench-item-table');
        DashboardSvc.getWorkbenchItemsCount()
        .then(function(response) {
            var hasWorkbenchData = false;
            var index = 0;

            angular.forEach(workbenchItems, function(workbenchItem, key) {
                if(angular.isDefined(response[index].data.total) && response[index].data.total > 0) {
                    workbenchItem['total'] = response[index].data.total;
                    hasWorkbenchData = true;
                    vm.workbenchItems.push(workbenchItem);
                }
                index++;
            });

            if (hasWorkbenchData == false) {
                vm.workbenchItems = false;
            }
        }, function(error) {
            // No Workbench Data
            console.log(error);
            vm.workbenchItems = false;
            AlertSvc.warning($scope, error);
        })
        .finally(function() {
            UtilsSvc.isAppendSpinner(false, 'dashboard-workbench-item-table');
        });

        vm.target = 'workbench';
        vm.initGrid(vm.target);
    }

    function initGrid(target) {
        vm.gridOptions[target] = {
            columnDefs: [],
            rowData: [],
            headerHeight: 38,
            rowHeight: 38,
            minColWidth: 200,
            enableColResize: true,
            enableSorting: true,
            unSortIcon: true,
            enableFilter: true,
            suppressMenuHide: true,
            suppressCellSelection: true,
            suppressMovableColumns: true,
            rowModelType: 'pagination'
        };
    }

    function onChangeModel(model) {
        vm.showSplitContentResponsive();
        vm.workbenchTitle = DashboardSvc.getWorkbenchTitle(model.code);

        // reset to empty
        vm.gridOptions[vm.target].api.setColumnDefs([]);
        vm.gridOptions[vm.target].api.setRowData([]);

        var columnDefs = DashboardSvc.getWorkbenchColumnDefs(model.cols);
        vm.gridOptions[vm.target].api.setColumnDefs(columnDefs);
        vm.gridOptions[vm.target].api.sizeColumnsToFit();

        var limit = 10;
        var dataSource = {
            pageSize: limit,
            getRows: function (params) {
                var page = parseInt(params.startRow / limit) + 1;

                UtilsSvc.isAppendSpinner(true, 'dashboard-workbench-table');
                DashboardSvc.getWorkbenchRowData(model, limit, page)
                .then(function(response) {
                    var lastRowIndex = response.data.total;

                    if (lastRowIndex > 0) {
                        var rows = response.data.data;
                        params.successCallback(rows, lastRowIndex);
                    } else {
                        params.failCallback();
                    }
                }, function(error) {
                    console.log(error);
                })
                .finally(function() {
                    UtilsSvc.isAppendSpinner(false, 'dashboard-workbench-table');
                });
            }
        };

        vm.gridOptions[vm.target].api.setDatasource(dataSource);
    }
}
