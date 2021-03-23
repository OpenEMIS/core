var dashBoardApp = angular
    .module('dashboard.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'dashboard.svc'])
    .controller('DashboardCtrl', DashboardController);

dashBoardApp.filter('removeEmded', function() {
    return function(text) {
        var str = text.replace(/\{.*?\}/g, "");
        return str;
    };
});

dashBoardApp.filter('getUrl', function() {
    return function(input) {
        var youtubeUrl = input.split('|');
        youtubeUrl = youtubeUrl[1].split('}');
        return youtubeUrl[0].replace('/watch?v=', '/embed/');
    }
});

DashboardController.$inject = ['$scope', '$location', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DashboardSvc', '$sce'];

function DashboardController($scope, $location, $filter, $q, UtilsSvc, AlertSvc, AggridLocaleSvc, DashboardSvc, $sce) {
    var vm = this;
    $scope.trustedUrl = function(input) {
        return $sce.trustAsResourceUrl(input);
    }

    // Variables
    vm.collapse = "true";
    vm.profile = [];
    vm.notices = [];
    vm.workbenchItems = [];
    vm.workbenchTitle = "";
    vm.target = '';
    vm.gridOptions = {};
    vm.percentage = '';

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
    }

    function initNotices() {
        UtilsSvc.isAppendSpinner(true, 'dashboard-notices-table');
        DashboardSvc.getNotices()
            .then(function(notices) {
                for (var key in notices) {
                    vm.notices.push(notices[key]);
                }

                // vm.notices = notices;
                if (vm.notices.length == 0) {
                    vm.notices = null;
                }
            }, function(error) {
                // No Notices
                console.log(error);
                vm.notices = null;
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
                    if (angular.isDefined(response[index].data.total) && response[index].data.total > 0) {
                        workbenchItem['total'] = response[index].data.total;
                        hasWorkbenchData = true;
                        vm.workbenchItems.push(workbenchItem);
                    }
                    index++;
                });

                if (hasWorkbenchData == false) {
                    vm.workbenchItems = false;
                }
                return vm.workbenchItems;
            }, function(error) {
                // No Workbench Data
                console.log(error);
                vm.workbenchItems = false;
                AlertSvc.warning($scope, error);
            })
            .then(function(items) {
                var textToTranslate = [];
                angular.forEach(items, function(item, key) {
                    textToTranslate.push(item.name);
                });
                var defer = $q.defer();
                DashboardSvc.translate(textToTranslate)
                    .then(function(res) {
                        angular.forEach(res, function(value, key) {
                            items[key]['name'] = value;
                        });
                        defer.resolve(items);
                    }, function(error) {
                        defer.resolve(items);
                    });
                return defer.promise;
            }, function(error) {
                console.log(error);
            })
            .then(function(workbenchItemsTranslated) {
                vm.workbenchItems = workbenchItemsTranslated;
            }, function(error) {
                console.log(error);
            })
            .finally(function() {
                UtilsSvc.isAppendSpinner(false, 'dashboard-workbench-item-table');
            });

        vm.target = 'workbench';
        vm.initGrid(vm.target);
    }

    function initGrid(target) {
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function(localeText) {
                vm.gridOptions[target] = {
                    columnDefs: [],
                    rowData: [],
                    headerHeight: 38,
                    rowHeight: 38,
                    // minColWidth: 200,
                    enableColResize: true,
                    enableSorting: true,
                    unSortIcon: true,
                    enableFilter: true,
                    suppressMenuHide: true,
                    suppressMovableColumns: true,
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    suppressCellSelection: true,
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                   
                };
            });
    }

    function onChangeModel(model) {
        vm.showSplitContentResponsive();
        vm.workbenchTitle = DashboardSvc.getWorkbenchTitle(model.code);

        // reset to empty
        vm.gridOptions[vm.target].api.setColumnDefs([]);

        var columnDefs = DashboardSvc.getWorkbenchColumnDefs(model.cols);
        var textToTranslate = [];
        angular.forEach(columnDefs, function(value, key) {
            textToTranslate.push(value.headerName);
        });
        textToTranslate.push(vm.workbenchTitle);
        DashboardSvc.translate(textToTranslate)
            .then(function(res) {
                // console.log('res', res);
                var maxCount = res.length - 1;
                angular.forEach(res, function(value, key) {
                    if (key < maxCount) {
                        columnDefs[key]['headerName'] = value;
                    } else {
                        vm.workbenchTitle = value;
                    }
                });
                vm.gridOptions[vm.target].api.setColumnDefs(columnDefs);
                vm.gridOptions[vm.target].api.sizeColumnsToFit();
            }, function(error) {
                console.log(error);
            });
            var limit = 0;
            var page = 0;
            var rows;
            UtilsSvc.isAppendSpinner(true, 'dashboard-workbench-table');
                DashboardSvc.getWorkbenchRowData(model, limit, page)
                    .then(function(response) {
                        // console.log(response)
                        var lastRowIndex = response.data.total;

                        if (lastRowIndex > 0) {
                           rows = response.data.data;
                             vm.gridOptions[vm.target].api.setRowData(rows)
                            //  console.log('row end');
                           
                        } 
                        
                    }, function(error) {
                        console.log(error);
                    })
                    .finally(function() {
                        UtilsSvc.isAppendSpinner(false, 'dashboard-workbench-table');
                    });
                  

        // var limit = 10;
        // var dataSource = {
        //     pageSize: limit,
        //     getRows: function(params) {
        //         var page = parseInt(params.startRow / limit) + 1;

        //         UtilsSvc.isAppendSpinner(true, 'dashboard-workbench-table');
        //         DashboardSvc.getWorkbenchRowData(model, limit, page)
        //             .then(function(response) {
        //                 var lastRowIndex = response.data.total;

        //                 if (lastRowIndex > 0) {
        //                     var rows = response.data.data;
        //                     params.successCallback(rows, lastRowIndex);
        //                 } else {
        //                     params.failCallback();
        //                 }
        //             }, function(error) {
        //                 console.log(error);
        //             })
        //             .finally(function() {
        //                 UtilsSvc.isAppendSpinner(false, 'dashboard-workbench-table');
        //             });
        //     }
        // };

        // vm.gridOptions[vm.target].api.setRowData(dataSource);
    }
}