// ag-Grid wrapper v1.0.1
(function() {
    'use strict';
    
    angular.module('kd-angular-ag-grid', [])
        .directive('kdAgGrid', kdAgGrid);

    function kdAgGrid() {

        var directive = {
            restrict: 'A',
            replace: false,
            template: '<div ag-grid="kdAgGrid" class="sg-theme" ng-class="{\'has-tabs\': hasTabs}" kd-elem-sizes="{{elemSizeOption}}"></div>' +
                '<button ng-if="hasTooltip" id="ag-grid-tooltip-button" ng-click="setTooltip()" class="ag-hidden-tooltip" uib-tooltip="{{tooltip.text}}" tooltip-is-open="tooltip.open" tooltip-trigger="\'none\'" tooltip-placement="{{tooltip.placement}}" tooltip-append-to-body="true" tooltip-class="tooltip-blue ag-grid-uib-tooltip"></button>',
            scope: {
                kdAgGrid: '=',
                agSelectionType: '@',
                elemSizeOption: '=',
                hasTabs: '@'
            },
            controller: kdAgGridCtrl,
            link: kdAgGridLink
        };
        return directive;
    }
    /////////////////////////////////////////////////////////////////
    /// Directive Controller
    /////////////////////////////////////////////////////////////////
    kdAgGridCtrl.$inject = ['$scope'];

    function kdAgGridCtrl($scope) {

        $scope.hasTooltip = false;

        updateColumnDefinition($scope.kdAgGrid.columnDefs);

        var direction = getComputedStyle(document.body).direction;

        var gridOptionsConfig = {
            direction: direction,
            hasTooltip: $scope.hasTooltip
        };

        updateGridOptionsProperty($scope.kdAgGrid, gridOptionsConfig);

        if ($scope.agSelectionType !== undefined && $scope.agSelectionType !== "") {
            updateInputColumn($scope);
            updateGridChangeEvents($scope);

            if ($scope.agSelectionType == "checkboxAll") {
                updateFilterEvent($scope);
                updateGridReadyEvent($scope, true);
            } else {
                updateGridReadyEvent($scope, false);
            }
        }

        if ($scope.hasTooltip) {
            $scope.tooltip = getDefaultTooltip();
            $scope.tooltipEndState = false;

            $scope.setTooltip = function() {
                if (!$scope.tooltip["toCenter"]) {
                    var tooltipLeft = $scope.tooltip["dom"].getBoundingClientRect().left;
                    var gridLeft = document.querySelector(".ag-relative-grid").getBoundingClientRect().left;

                    //setTooltipProperty($scope.tooltipBtn, tooltipLeft - gridLeft + 10);
                    setTooltipProperty($scope.tooltipBtn, tooltipLeft - gridLeft);
                    setTooltipOpen($scope, true);
                } else {
                    //setTooltipProperty($scope.tooltipBtn, ((window.innerWidth - 30) / 2) - 9);
                    setTooltipProperty($scope.tooltipBtn, ((window.innerWidth - 18) / 2) - 9);
                    setTooltipOpen($scope, true, true);
                }
            }
        }

        // Grid events
        function updateGridChangeEvents(_scope) {
            _scope.kdAgGrid.onGridSizeChanged = function(params) {
                resizeGrid(_scope);
            }
        }

        function updateFilterEvent(_scope) {
            _scope.kdAgGrid.onFilterChanged = function(params) {
                checkSelectAllBox(_scope.kdAgGrid.api.getModel());
            }
        }

        function updateGridReadyEvent(_scope, _checkSelectAll) {
            _scope.kdAgGrid.onGridReady = function(params) {
                resizeGrid(_scope);
                if (_checkSelectAll) {
                    checkSelectAllBox(params.api.getModel());
                }
            }
        }

        function resizeGrid(_scope) {
            _scope.kdAgGrid.api.sizeColumnsToFit();
        }

        function updateInputColumn(_scope) {
            var inputConfig = {
                headerName: "",
                field: "checkbox",
                suppressMenu: true,
                suppressSorting: true,
                minWidth: 50,
                maxWidth: 50,
                pinned: direction == "rtl" ? "right" : "left"
            };

            switch (_scope.agSelectionType) {
                case "checkbox":
                case "radio":
                    inputConfig["cellRenderer"] = function(params) {
                        if (params.data != undefined) {
                            return initCheckbox(_scope, params, _scope.agSelectionType, false);
                        }
                    };
                    break;
                case "checkboxAll":
                    inputConfig["headerComponent"] = headerCheckboxComponent;
                    inputConfig["cellRenderer"] = function(params) {
                        if (params.data != undefined) {
                            return initCheckbox(_scope, params, "checkbox", true);
                        }
                    };
                    break;
            }

            _scope.kdAgGrid.columnDefs.unshift(inputConfig);
        }

        function updateGridOptionsProperty(_gridOptions, _config) {

            _gridOptions["enableRtl"] = _config["direction"] == "rtl";

            _gridOptions["icons"] = getIconClass();

            // do not remove the dom of the column - adding it so that tooltip listener will not be remove due to the dom
            if (_config["hasTooltip"]) {
                _gridOptions["suppressColumnVirtualisation"] = true;
            }
        }

        function updateColumnDefinition(_colDef) {
            for (var i = 0; i < _colDef.length; i++) {
                _colDef[i]["menuTabs"] = ['filterMenuTab']; //nova necessary for replacing deprecated suppressMenuMainPanel & suppressMenuColumnPanel

                //only updates the column filter params if the column filter is not suppressed
                if (_colDef[i]["suppressFilter"] == undefined || !_colDef[i]["suppressFilter"]) {
                    updateFilterParams(_colDef[i]);
                }

                //updates header with tooltip
                if (_colDef[i]["tooltip"] != undefined) {
                    $scope.hasTooltip = true;

                    var tempHeaderName = _colDef[i]["headerName"];
                    _colDef[i]["headerName"] = '<div class="ag-cell-with-tooltip">' + tempHeaderName + '</div> <i class="fa fa-info-circle fa-lg fa-right icon-blue ag-icon-tooltip" id="' + _colDef[i]["field"] + '"></i>'
                    _colDef[i]["headerClass"] = "ag-header-with-tooltip";
                }

                //headerIconRearraging(_colDef[i]); //placing of sorting icon to the right
            }
        }

        function headerIconRearraging(_col) {
            _col["headerCellTemplate"] = function() {
                var eCell = document.createElement('span');
                eCell.innerHTML =
                    '<div class="ag-cell-label-container ag-header-cell-sorted-none">' +
                    '    <span id="agMenu" class="ag-header-icon ag-header-cell-menu-button"><i class="fa fa-navicon"></i></span>' +
                    // everything inside agHeaderCellLabel gets actioned when the user clicks
                    '    <div id="agHeaderCellLabel" class="ag-header-cell-label">' +
                    '    <span ref="eSortOrder" class="ag-header-icon ag-sort-order"></span>' +
                    '      <span id="agSortAsc" class="ag-header-icon ag-sort-ascending-icon ag-hidden"><i class="fa fa-caret-down"></i></span>' +
                    '      <span id="agSortDesc" class="ag-header-icon ag-sort-descending-icon ag-hidden"><i class="fa fa-caret-up"></i></span>' +
                    '      <span id="agNoSort" class="ag-header-icon ag-sort-none-icon"><i class="fa fa-sort"></i></span>' +
                    '      <span id="agText" class="ag-header-cell-text"></span>' +
                    '    </div>' +
                    '</div>';
                //default & previous menu icon: <svg width="12" height="12"><rect y="0" width="12" height="2" class="ag-header-icon"></rect><rect y="5" width="12" height="2" class="ag-header-icon"></rect><rect y="10" width="12" height="2" class="ag-header-icon"></rect></svg>
                return eCell;
            };
        }

        function headerCheckboxComponent() {}
        // function headerTooltipComponent() {}

        /////////////////////////////////////////////////////////////////
        /// Header Checkbox Component Prototype
        /////////////////////////////////////////////////////////////////   

        headerCheckboxComponent.prototype.init = function(params) {

            var divWrapper = document.createElement("div");
            divWrapper.className = "ag-select-all-wrapper";

            var inputCheckbox = document.createElement("input");
            inputCheckbox.type = "checkbox";
            inputCheckbox.name = "select-all";
            inputCheckbox.className = "ag-select-all";

            inputCheckbox.addEventListener('click', function(event) {
                if (inputCheckbox.checked) {
                    params.api.selectAllFiltered();
                } else {
                    params.api.deselectAll();
                }

                var allCheckbox = angular.element(".ag-grid-checkbox");

                for (var i = 0; i < allCheckbox.length; i++) {
                    allCheckbox[i].checked = inputCheckbox.checked;
                }
            });

            var labelTag = document.createElement("label");
            labelTag.className = "ag-select-all-label";

            divWrapper.appendChild(inputCheckbox);
            divWrapper.appendChild(labelTag);

            this.eGui = divWrapper;

        }

        headerCheckboxComponent.prototype.getGui = function() {
            return this.eGui;
        }

        headerCheckboxComponent.prototype.destroy = function() {}
    }


    /////////////////////////////////////////////////////////////////
    /// Directive Link
    /////////////////////////////////////////////////////////////////

    function kdAgGridLink(_scope, _element, _attr) {
        if (_scope.hasTooltip) {
            _element.addClass("ag-relative-grid");

            setTimeout(function() {
                updateTooltipListener(_scope, _element);
                _scope.tooltipBtn = _element[0].querySelector("#ag-grid-tooltip-button");
            });
        }
    }

    /////////////////////////////////////////////////////////////////
    /// Header Tooltip Functions
    /////////////////////////////////////////////////////////////////   

    function getDefaultTooltip() {
        return {
            open: false,
            placement: "top",
            text: "default",
            dom: "",
            toCenter: false
        };
    }

    function updateTooltipListener(_scope, _element) {
        var allTooltipEle = _element[0].querySelectorAll('.ag-header-with-tooltip i[class*="ag-icon-tooltip"]');

        for (var i = 0; i < allTooltipEle.length; i++) {
            var item = allTooltipEle[i];

            item.addEventListener("mouseover", function(event) {
                event.stopPropagation();
                event.preventDefault();
                event.target.classList.add("ag-tooltip-hover");

                if (_scope.tooltipEndState) {
                    _scope.tooltipEndState = false;
                } else {
                    openTooltipObject(_scope, event.target);
                }
            });

            item.addEventListener("mouseout", function(event) {
                event.stopPropagation();
                event.preventDefault();
                event.target.classList.remove("ag-tooltip-hover");

                setTooltipOpen(_scope, false);
            });

            item.addEventListener("touchstart", function(event) {
                event.stopPropagation();
                event.preventDefault();
                event.target.classList.add("ag-tooltip-hover");

                _scope.tooltipEndState = true;

                openTooltipObject(_scope, event.target);
            });

            item.addEventListener("touchend", function(event) {
                event.stopPropagation();
                event.preventDefault();
                event.target.classList.remove("ag-tooltip-hover");

                setTooltipOpen(_scope, false);
            });
        }
    }

    function openTooltipObject(_scope, _item) {
        _scope.tooltip["text"] = _scope.kdAgGrid.columnApi.getColumn(_item.id).colDef["tooltip"];
        _scope.tooltip["dom"] = _item;
        _scope.tooltip["toCenter"] = false;
        _scope.tooltip["placement"] = getTooltipPlacement(_scope, _item);

        document.querySelector("#ag-grid-tooltip-button").click();
    }

    function setTooltipOpen(_scope, _toOpen, _toCenter) {
        _scope.tooltip["open"] = _toOpen;

        if (!_toOpen) {
            setTooltipProperty(_scope.tooltipBtn, 0);
            _scope.$apply();
        }

        if (angular.isDefined(_toCenter) && _toCenter) {
            setTimeout(function() {
                var uibEle = document.querySelector("body > .ag-grid-uib-tooltip .tooltip-arrow");

                if (uibEle != null) {
                    uibEle.style["opacity"] = 0;
                }
            });
        }
    }

    function setTooltipProperty(_tooltip, _left) {
        _tooltip.style.setProperty("left", _left + "px");
    }

    function getTooltipPlacement(_scope, _itemEle) {
        var placement = "top";
        var allColumns = _scope.kdAgGrid.columnApi.getAllColumns();
        var direction = getComputedStyle(document.body).direction;

        if (allColumns[allColumns.length - 1]["colDef"]["field"] == _itemEle.id) {
            placement = "top-right";
        }

        if (window.innerWidth > 450) {
            if (window.innerWidth < _itemEle.getBoundingClientRect().right + 150) {
                placement = "top-right";
            } else if (_itemEle.getBoundingClientRect().left < 150) {
                placement = "top-left";
            }
        } else {
            placement = "top";
            _scope.tooltip["toCenter"] = true;
        }

        return placement;
    }

    function getIconClass() {
        // use font awesome for menu icons
        // menu: '<i class="fa fa-bars"/>',
        return {
            filter: '<i class="fa fa-filter"/>',
            sortAscending: '<i class="fa fa-caret-down"/>',
            sortDescending: '<i class="fa fa-caret-up"/>',
            sortUnSort: '<i class="fa fa-sort"/>',
            groupExpanded: '<i class="fa fa-minus-circle"/>',
            groupContracted: '<i class="fa fa-plus-circle"/>',
            checkboxChecked: '<span class="ag-filter-custom-checked"></span>',
            checkboxUnchecked: '<span class="ag-filter-custom-unchecked"></span>'
        };
    }

    function updateFilterParams(_columnItem) {
        if (_columnItem["filterParams"] == undefined) {
            _columnItem["filterParams"] = {};
        }

        //update height of filter item list in order for virtual list item to work
        _columnItem["filterParams"]["cellHeight"] = 30;

        //update of select all mini filter selection to select only the filtered list when clicked select all
        _columnItem["filterParams"]["selectAllOnMiniFilter"] = true;

        //case insensitive
        _columnItem["filterParams"]["textFormatter"] = function(s) {
            return s.toLowerCase();
        }
    }

    /////////////////////////////////////////////////////////////////
    /// Checkbox Functions
    /////////////////////////////////////////////////////////////////

    function checkSelectAllBox(_model) {
        var selectAllCheckbox = angular.element(".ag-select-all");
        var isAllSelected = true;

        for (var i = 0; i < _model.getRowCount(); i++) {
            if (!_model.getRow(i).isSelected()) {
                isAllSelected = false;
                break;
            }
        }

        selectAllCheckbox[0].checked = isAllSelected;
    }

    function initCheckbox(_scope, _params, _inputType, _isHeaderCheckboxExist) { //param = row element
        updateInitSelection(_params);

        var divWrapper = document.createElement("div");
        divWrapper.className = "ag-" + _inputType + "-wrapper";

        var inputCheckbox = document.createElement("input");
        inputCheckbox.type = _inputType;
        inputCheckbox.name = _inputType;
        inputCheckbox.className = "ag-grid-" + _inputType;
        inputCheckbox.checked = _params.node.isSelected();

        if (_inputType === "checkbox") {
            inputCheckbox.addEventListener('click', function(event) {
                _params.node.selectThisNode(inputCheckbox.checked);

                if (_isHeaderCheckboxExist) { //checkbox false, checkbox all true
                    checkSelectAllBox(_scope.kdAgGrid.api.getModel());
                }
            });
        } else { //radio
            inputCheckbox.addEventListener('click', function(event) {
                _params.api.deselectAll();
                _params.node.selectThisNode(true);
            })
        }

        var labelTag = document.createElement("label");
        labelTag.className = "ag-cell-label";

        divWrapper.appendChild(inputCheckbox);
        divWrapper.appendChild(labelTag);

        return divWrapper;
    }

    function updateInitSelection(_params) {
        if (_params.node.data.agSelect != "undefined" && _params.node.data.agSelect) {
            _params.node.selectThisNode(true);
        }
    }
})();

/////////////////////////////////////////////////////////////////
/// Header Tooltip Component Prototype
/////////////////////////////////////////////////////////////////   

// headerTooltipComponent.prototype.init = function(params) {
//  console.log("init!", params);

//  var divWrapper = document.createElement('div');
//  divWrapper.className = "ag-header-cell-sorted-none";

//  divWrapper.appendChild(this.createEMenu());
//  divWrapper.appendChild(this.createELabel(params));

//  this.eGui = divWrapper;
// }

// headerTooltipComponent.prototype.getGui = function() {
//  return this.eGui;
// }

// headerTooltipComponent.prototype.destroy = function() {

// }

// headerTooltipComponent.prototype.createRect = function(_posY) {
//  var rectEle = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
//  rectEle.setAttribute('y', _posY);
//  rectEle.setAttribute('width', 12);
//  rectEle.setAttribute('height', 2);
//  rectEle.className = "ag-header-icon";

//  return rectEle;
// }

// headerTooltipComponent.prototype.createEMenu = function() {
//  var menuWrapper = document.createElement('span');
//  menuWrapper.setAttribute('ref', 'eMenu');
//  menuWrapper.className = "ag-header-icon ag-header-cell-menu-button";
//  menuWrapper.style.transition = "opacity 0.2s, border 0.2s";

//  // var svgRectEle = document.createElement('svg');
//  var svgRectEle = document.createElementNS("http://www.w3.org/2000/svg", 'svg');
//  svgRectEle.setAttribute('width', 12);
//  svgRectEle.setAttribute('height', 12);

//  var rectPosY = [0, 5, 10];

//  for (var i in rectPosY) {
//      var rectEle = this.createRect(rectPosY[i]);
//      svgRectEle.appendChild(rectEle);
//  }

//  menuWrapper.appendChild(svgRectEle);

//  $compile(menuWrapper);
//  return menuWrapper;
// }

// headerTooltipComponent.prototype.createLabelSpan = function(_params, _refName, _className, _iconClass, _innerText) {
//  var spanEle = document.createElement('span');
//  spanEle.setAttribute('ref', _refName);
//  spanEle.className = _className;

//  if (_iconClass != undefined) {
//      var iconEle = document.createElement('i');
//      iconEle.className = _iconClass;
//      spanEle.appendChild(iconEle);
//  }

//  if (_innerText != undefined) {
//      spanEle.innerHTML = _innerText;

//      spanEle.addEventListener("mouseover", function(event) {
//          console.log("mouse in!", event);
//          $scope.tooltip.open = true;
//          $scope.tooltip.placement = "top-right";
//          $scope.tooltip.text = _params.column.colDef.tooltip;

//          console.log("scope", $scope);
//          console.log("params", _params);
//      });

//      spanEle.addEventListener("mouseout", function(event) {
//          console.log("mouse out!", event);
//          $scope.tooltip.open = false;
//      })
//  }

//  return spanEle;
// }

// headerTooltipComponent.prototype.updateDOMSortIcons = function(_labelWrapper, _currentWrapper, _shownOrder) {
//  /// Order - None -> Asc -> Desc

//  // _labelWrapper
// }

// headerTooltipComponent.prototype.createELabel = function(params) {
//  var labelWrapper = document.createElement('div');
//  labelWrapper.setAttribute('ref', 'eLabel');
//  labelWrapper.className = "ag-header-cell-label";

//  // labelWrapper.addEventListener('click', function(event) {
//  //  var currentRefElement = labelWrapper.querySelector('span[class*="ag-sort"]:not([class*="ag-hidden"])');
//  //  // console.log("class", currentRefElement.getAttribute('ref'));

//  //  switch(currentRefElement) {
//  //      case "eSortNone":
//  //          this.updateDOMSortIcons(labelWrapper, currentRefElement, 'asc');
//  //          break;
//  //      case "eSortAsc": 
//  //          this.updateDOMSortIcons(labelWrapper, currentRefElement, 'desc');
//  //          break;
//  //      case "eSortDesc": 
//  //          this.updateDOMSortIcons(labelWrapper, currentRefElement, 'none');
//  //          break;
//  //  }
//  // });  

//  var spanArray = [
//      /*{
//          ref: "eSortOrder",
//          class: "ag-header-icon ag-sort-order ag-hidden",
//          icon: undefined,
//          innerText: undefined 
//      },*/ {
//          ref: "eSortAsc",
//          class: "ag-header-icon ag-sort-ascending-icon ag-hidden",
//          icon: "fa fa-caret-down",
//          innerText: undefined 
//      }, {
//          ref: "eSortDesc",
//          class: "ag-header-icon ag-sort-descending-icon ag-hidden",
//          icon: "fa fa-caret-up",
//          innerText: undefined 
//      }, {
//          ref: "eSortNone",
//          class: "ag-header-icon ag-sort-none-icon",
//          icon: "fa fa-sort",
//          innerText: undefined 
//      }, {
//          ref: "eText",
//          class: "ag-header-cell-text",
//          icon: undefined,
//          innerText: params.column.colDef.headerName
//      }
//  ];

//  for (var item in spanArray) {
//      labelWrapper.appendChild(this.createLabelSpan(params, spanArray[item].ref, spanArray[item].class, spanArray[item].icon, spanArray[item].innerText));
//  }

//  return labelWrapper;
// }
