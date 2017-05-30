//Multi Select v.1.0.0

angular.module('kd-angular-multi-select', [])
    .directive('kdMultiSelect', kdMultiSelect);

function kdMultiSelect() {

    var directive = {
        restrict: 'E',
        replace: true,
        transclude: true,
        template: "<div class='table-wrapper'>\
                        <div class='table-top'>\
                            <div class='search-table-top'>\
                                <i class='fa fa-search'></i>\
                                <input placeholder='Search Unassign Students' name='focus' type='text' ng-model='gridOptionsTop.quickFilterText'>\
                                <button type='button' class='close-icon' ng-click='resetTopRow()' ng-hide='!gridOptionsTop.quickFilterText.length'></button>\
                            </div>\
                            <div ag-grid='gridOptionsTop' class='sg-theme' kd-elem-sizes kd-height='260px'></div>\
                        </div>\
                        <div class='table-action-btn'>\
                            <button class='btn btn-default' id='assignBtn' ng-disabled='tableActionBtns.assign' ng-click='onAssign()'>\
                            <i class='fa fa-angle-double-down fa-lg'></i> Assign\
                            </button><button class='btn btn-default' id='unassignBtn' ng-disabled='tableActionBtns.unassign' ng-click='onUnassign()'>\
                            <i class='fa fa-angle-double-up fa-lg'></i> Unassign\
                            </button>\
                        </div>\
                        <div class='table-bottom'>\
                            <div class='search-table-bottom'>\
                                <i class='fa fa-search'></i>\
                                <input placeholder='Search Assign Students' name='focus' type='text' ng-model='gridOptionsBottom.quickFilterText'>\
                                <button type='button' class='close-icon' ng-click='resetBottomRow()' ng-hide='!gridOptionsBottom.quickFilterText.length'></button>\
                            </div>\
                            <div ag-grid='gridOptionsBottom' class='sg-theme' kd-elem-sizes kd-height='240px'></div>\
                        </div>\
                    </div>",
        scope: {
            gridOptionsTop: "=",
            gridOptionsBottom: "="
        },
        controller: kdMultiSelectCtrl,
        link: kdMultiSelectLink
    };
    return directive;


    function kdMultiSelectCtrl($scope) {
        var defaultGridOptions = getDefaultGridOpt();
        //Setting up the top ag-grid options/config
        $scope.gridOptionsTop = angular.extend({}, defaultGridOptions, $scope.gridOptionsTop);
        $scope.gridOptionsTop['gridPosition'] = 'top';
        $scope.gridOptionsTop.floatingTopRowData = getHeaderCheckbox($scope.gridOptionsTop);

        //Setting up the top ag-grid options/config
        $scope.gridOptionsBottom = angular.extend({}, defaultGridOptions, $scope.gridOptionsBottom);
        $scope.gridOptionsBottom['gridPosition'] = 'bottom';
        $scope.gridOptionsBottom.headerHeight = 0;
        $scope.gridOptionsBottom.floatingTopRowData = getHeaderCheckbox($scope.gridOptionsBottom);

        //setting up the master and slave relationship
        $scope.gridOptionsTop.slaveGrids.push($scope.gridOptionsBottom);
        $scope.gridOptionsBottom.slaveGrids.push($scope.gridOptionsTop);


        function getDefaultGridOpt() {
            return {
                primaryKey: 'id',
                headerHeight: 38,
                rowHeight: 38,
                rowSelection: 'multiple',
                suppressRowClickSelection: true,
                enableColResize: false,
                unSortIcon: true,
                enableSorting: true,
                suppressMenuHide: true,
                enableFilter: true,
                suppressCellSelection: true,
                suppressMovableColumns: true,
                suppressContextMenu: true,
                slaveGrids: [],
                onModelUpdated: onModelUpdated,
                onGridReady: onGridReady,
                onSelectionChanged: onSelectionChanged,
                getRowClass: function(params) {
                    if (params.data.hasOwnProperty('isNewRow')) {
                        delete params.data.isNewRow;
                        return 'new-row-highlight';
                    }
                },
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                icons: {
                    // use font awesome for menu icons
                    menu: '<i class="fa fa-bars"/>',
                    filter: '<i class="fa fa-filter"/>',
                    sortAscending: '<i class="fa fa-caret-down"/>',
                    sortDescending: '<i class="fa fa-caret-up"/>',
                    sortUnSort: '<i class="fa fa-sort"/>',
                    groupExpanded: '<i class="fa fa-minus-circle"/>',
                    groupContracted: '<i class="fa fa-plus-circle"/>'
                },
                overlayNoRowsTemplate: '<span class="ag-custom-overlay"><i class="fa fa-info-circle fa-lg margin-right-10"></i>No student record found</span>'
            }
        }

        function getHeaderCheckbox(_scopeObj) {
            var studentType = (_scopeObj.gridPosition == 'top') ? "Unassigned" : "Assigned";

            return [{ checkbox: '<span class="ag-cell-wrapper"><input type="checkbox" name="name" class="ag-select-all-' + _scopeObj.gridPosition + '"><span class="ag-cell-value"></span></span> <span class="checkbox-text">Select All - <span class="ag-row-count"></span> ' + studentType + ' Students</span>' }];
        }

        function onModelUpdated() {
            updateAgGridRowCount(this);
        }

        function onGridReady() {
            this.api.sizeColumnsToFit();
        }

        function onSelectionChanged(e) {
            var rows = this.api.getSelectedNodes();
            var btnsState = {};
            if (this.gridPosition == 'top') {
                btnsState = {
                    assign: (rows.length > 0) ? false : true,
                    unassign: true
                };
            } else {
                btnsState = {
                    assign: true,
                    unassign: (rows.length > 0) ? false : true
                };
            }
            $scope.$apply(function() {
                $scope.tableActionBtns = btnsState;
            });
        }
    }

    function kdMultiSelectLink(_scope, _element, _attrs) {
        var isMobile = (document.querySelector('html.mobile') !== null) ? true : false;
        var searchTopWrapper = angular.element(document.querySelector('.search-table-top'));
        var searchBottomWrapper = angular.element(document.querySelector('.search-table-bottom'));
        var clickTopSearch = angular.element(document.querySelector('.search-table-top .fa-search'));
        var clickBottomSearch = angular.element(document.querySelector('.search-table-bottom .fa-search'));


        angular.element(document).ready(function() {
            var sortTop = _scope.gridOptionsTop.api.getSortModel();
            initMultiSelect(_scope, '.ag-select-all-top');
            initMultiSelect(_scope, '.ag-select-all-bottom');

            initSearchCollapse();
            setupMobileView();

            _scope.tableActionBtns = {
                assign: true,
                unassign: true
            }

            /*** ======== Start of (After Row is Regenerated) ======== ***/

            _scope.gridOptionsTop['onModelUpdated'] = function() {
                initMultiSelect(_scope, '.ag-select-all-top');
                updateAgGridRowCount(this);

                sortTop = _scope.gridOptionsTop.api.getSortModel();
                _scope.gridOptionsBottom.api.setSortModel(sortTop);
            }

            _scope.gridOptionsBottom['onModelUpdated'] = function() {
                initMultiSelect(_scope, '.ag-select-all-bottom');
                updateAgGridRowCount(this);
            }

            /*** ======== End of (After Row is Regenerated) ======== ***/


            /*** ======== Start of (Clear Search) ======== ***/

            _scope.resetTopRow = function() {
                _scope.gridOptionsTop.quickFilterText = '';

                setupSearch("isNotMobile");
            }

            _scope.resetBottomRow = function() {
                _scope.gridOptionsBottom.quickFilterText = '';

                setupSearch("isNotMobile");
            }

            /*** ======== End of (Clear Search) ======== ***/


            /*** ======== Start of (Assign & Unassign Buttons) ======== ***/
            _scope.onAssign = function() {
                processAssignUnassign(_scope.gridOptionsTop, _scope.gridOptionsBottom);
            }

            _scope.onUnassign = function() {
                processAssignUnassign(_scope.gridOptionsBottom, _scope.gridOptionsTop);
            }

            /*** ======== End of (Assign & Unassign Buttons) ======== ***/

        });


        /*** ======== Start of (Resize Window Browser) ======== ***/

        angular.element(window).bind('resize', function(_event) {
            _scope.gridOptionsTop.api.sizeColumnsToFit();
            setupMobileView();
        });

        /*** ======== End of (Resize Window Browser) ======== ***/

        function processAssignUnassign(_obj1, _obj2) {
            var key = _obj1.primaryKey;
            var newRows = null;
            var rows = _obj1.api.getSelectedNodes();
            var offset = 0;

            for (var i = 0; i < rows.length; i++) {
                rows[i].data['isNewRow'] = true;

                _obj2.rowData.push(rows[i].data);
                _obj1.rowData.splice(rows[i].id - offset, 1);
                offset++;
            }

            _obj1.api.setRowData(_obj1.rowData);
            _obj2.api.setRowData(_obj2.rowData);

            //Match the id and the index row
            _obj2.api.forEachNodeAfterFilterAndSort(function(_node, _index) {
                if (key != null) {
                    if (rows[0].data[key] == _node.data[key]) {
                        newRows = _index;
                    }
                }
            });

            //scroll to the bottom
            if (newRows != null) _obj2.api.ensureIndexVisible(newRows);

            _scope.tableActionBtns = {
                assign: true,
                unassign: true
            }
        }

        /*** ======== Start of (Collapse & Expand Search) ======== ***/

        function initSearchCollapse() {
            if (window.innerWidth <= 1024) {
                searchTopWrapper.addClass('collapse-search').remove('expand-search');
                searchBottomWrapper.addClass('collapse-search').remove('expand-search');
            }
        }

        function setupMobileView() {
            if (window.innerWidth <= 1024) {
                onClickSearchButton();

                if (!isMobile) setupSearch("isNotMobile");

            } else {
                setupSearch('restoreWebview');
                clickTopSearch.unbind('click');
                clickBottomSearch.unbind('click');
            }
        }

        function onClickSearchButton() {
            clickTopSearch.bind('click', function() {
                searchTopWrapper.removeClass('collapse-search').addClass('expand-search');
            });

            clickBottomSearch.bind('click', function() {
                searchBottomWrapper.removeClass('collapse-search').addClass('expand-search');
            });
        }

        function setupSearch(_cmd) {
            // _cmd = angular.isUndefined(_cmd)? ""
            var options = [{
                isSearchPresent: _scope.gridOptionsTop.api.isQuickFilterPresent(),
                elem: searchTopWrapper
            }, {
                isSearchPresent: _scope.gridOptionsBottom.api.isQuickFilterPresent(),
                elem: searchBottomWrapper
            }];
            for (var i = 0; i < options.length; i++) {
                if (angular.isDefined(_cmd)) {
                    if (_cmd == "restoreWebview") {
                        options[i].elem.removeClass('collapse-search').removeClass('expand-search');
                    } else if (_cmd == "isNotMobile") {
                        if (window.innerWidth <= 1024) {
                            if (!options[i].isSearchPresent) {
                                options[i].elem.removeClass('expand-search').addClass('collapse-search');
                            } else {
                                options[i].elem.addClass('expand-search').removeClass('collapse-search');
                            }
                        }
                    }
                }
            }
        }

        /*** ======== End of (Collapse & Expand Search) ======== ***/
    }

    function updateAgGridRowCount(_agGridOptions) {
        angular.element(document).ready(function() {
            var elem = angular.element(document.querySelector('.table-' + _agGridOptions.gridPosition + ' .ag-row-count'));
            var rowCount = _agGridOptions.api.getModel().getRowCount();

            elem.html(rowCount);
        });
    }

    /*** ======== Checkbox ======== ***/

    function initMultiSelect(_scope, _gridClass) {
        var elementSelectAllCheckbox = angular.element(_gridClass);
        var isTopElem = (_gridClass.indexOf('top') > -1) ? true : false;

        initSelectAllCheckbox(_scope, elementSelectAllCheckbox, isTopElem);
        initInnerCheckbox(_scope, elementSelectAllCheckbox, isTopElem);
    }

    function initSelectAllCheckbox(_scope, _elementSelectAllCheckbox, _isTopElem) {
        _elementSelectAllCheckbox.bind('click', function(e) {
            var gridOptions = (_isTopElem) ? _scope.gridOptionsTop : _scope.gridOptionsBottom;
            var quickFilterIsPresent = gridOptions.api.isQuickFilterPresent();

            if (_elementSelectAllCheckbox[0].checked) {
                if (quickFilterIsPresent) {
                    gridOptions.api.forEachNodeAfterFilter(function(node) {
                        node.setSelected(true);
                    });
                } else {
                    gridOptions.api.selectAll();
                }
            } else {
                gridOptions.api.deselectAll();
            }

            updateOptionsStatus(_scope, _isTopElem);

        });
    }

    function initInnerCheckbox(_scope, _elementSelectAllCheckbox, _isTopElem) {
        var elementInnerCheckbox = angular.element((_isTopElem) ? '.table-top .ag-selection-checkbox' : '.table-bottom .ag-selection-checkbox');

        elementInnerCheckbox.bind('click', function(e) {
            var totalSelectedCheckbox = 0;
            for (var i in elementInnerCheckbox) {
                if (elementInnerCheckbox[i].checked) {
                    totalSelectedCheckbox++;
                }
            }

            _elementSelectAllCheckbox[0].checked = (elementInnerCheckbox.length == totalSelectedCheckbox) ? true : false;

            updateOptionsStatus(_scope, _isTopElem);
        });
    }

    /*** ======== End of (Checkbox) ======== ***/

    /*** ======== Start of (Update Assign & Unassign Buttons) ======== ***/

    function updateOptionsStatus(_scope, _isTopElem) {
        var topRows = _scope.gridOptionsTop.api.getSelectedRows();
        var bottomRows = _scope.gridOptionsBottom.api.getSelectedRows();

        if (_isTopElem) {
            if (bottomRows.length > 0) {
                _scope.gridOptionsBottom.api.deselectAll();

                var _elementSelectAllBottomCheckbox = angular.element('.ag-select-all-bottom');
                _elementSelectAllBottomCheckbox[0].checked = false;
            }

            var btnsState = {
                assign: (topRows.length > 0) ? false : true,
                unassign: true
            };

        } else {
            if (topRows.length > 0) {
                _scope.gridOptionsTop.api.deselectAll();

                var _elementSelectAllTopCheckbox = angular.element('.ag-select-all-top');
                _elementSelectAllTopCheckbox[0].checked = false;
            }

            var btnsState = {
                assign: true,
                unassign: (bottomRows.length > 0) ? false : true
            };
        }

        _scope.$apply(function() {
            _scope.tableActionBtns = btnsState;
        });
    }

    /*** ======== End of (Update Assign & Unassign Buttons) ======== ***/
}
