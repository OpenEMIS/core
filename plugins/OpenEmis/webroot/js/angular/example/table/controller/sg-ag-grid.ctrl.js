//ag-Grid v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('sg-ag-grid.ctrl', ['agGrid'])
    .controller('AgGridCtrl', AgGridCtrl);
//alias as agc-c

AgGridCtrl.$inject = ['$scope'];

function AgGridCtrl($scope) {
    // var agd = this;

    var bodyDir = getComputedStyle(document.body).direction;

    if (bodyDir == 'ltr') {
        var columnDefs = [
            { headerName: "Make", field: "make", filter: customFilter },
            { headerName: "Model", field: "model", filter: 'set' },
            { headerName: "Price", field: "price", filter: 'number' },
            { headerName: "Country", field: "country" },
            { headerName: "Area", field: "area" }
        ];

        var rowData = [
            { make: "Toyota", model: "Celica", price: 35000, country: "Japan", area: "Japan" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "Malaysia", area: "Malaysia" },
            { make: "Porsche", model: "Boxter", price: 72000, country: "Vietnam", area: "Vietnam" },
            { make: "Toyota", model: "Celica", price: 35000, country: "Singapore", area: "Singapore" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "Korea", area: "Korea" },
            { make: "Toyota", model: "Celica", price: 35000, country: "America", area: "America" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "Australia", area: "Australia" },
            { make: "Porsche", model: "Boxter", price: 72000, country: "India", area: "India" },
            { make: "Toyota", model: "Celica", price: 35000, country: "Cambodia", area: "Cambodia" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "Malaysia", area: "Malaysia" },
            { make: "Toyota", model: "Celica", price: 35000, country: "Thailand", area: "Thailand" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "Vietnam", area: "Vietnam" },
            { make: "Porsche", model: "Boxter", price: 72000, country: "Singapore", area: "Singapore" },
            { make: "Toyota", model: "Celica", price: 35000, country: "India", area: "India" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "Singapore", area: "Singapore" },
            { make: "Toyota", model: "Celica", price: 35000, country: "Singapore", area: "Singapore" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "India", area: "India" },
            { make: "Porsche", model: "Boxter", price: 72000, country: "Australia", area: "Australia" },
            { make: "Toyota", model: "Celica", price: 35000, country: "Singapore", area: "Singapore" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "Singapore", area: "Singapore" },
            { make: "Toyota", model: "Celica", price: 35000, country: "Singapore", area: "Singapore" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "India", area: "India" },
            { make: "Porsche", model: "Boxter", price: 72000, country: "China", area: "China" },
            { make: "Toyota", model: "Celica", price: 35000, country: "Australia", area: "Australia" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "Taiwan", area: "Taiwan" },
            { make: "Toyota", model: "Celica", price: 35000, country: "Germany", area: "Germany" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "America", area: "America" },
            { make: "Porsche", model: "Boxter", price: 72000, country: "America", area: "America" },
            { make: "Toyota", model: "Celica", price: 35000, country: "America", area: "America" },
            { make: "Ford", model: "Mondeo", price: 32000, country: "America", area: "America" },
            { make: "Porsche", model: "Boxter", price: 72000, country: "Singapore", area: "Singapore" }
        ];
    } else {
        var columnDefs = [
            { headerName: "Area", field: "area" },
            { headerName: "Country", field: "country" },
            { headerName: "Price", field: "price", filter: 'number' },
            { headerName: "Model", field: "model", filter: 'set' },
            { headerName: "Make", field: "make", filter: customFilter }
        ];

        var rowData = [
            { area: "Singapore", country: "Singapore", price: 72000, model: "Boxter", make: "Porsche" },
            { area: "America", country: "America", price: 32000, model: "Mondeo", make: "Ford"  },
            { area: "America", country: "America", price: 35000, model: "Celica", make: "Toyota" },
            { area: "America", country: "America", price: 72000, model: "Boxter", make: "Porsche" },
            { area: "America", country: "America", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Germany", country: "Germany", price: 35000, model: "Celica", make: "Toyota" },
            { area: "Taiwan", country: "Taiwan", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Taiwan", country: "Taiwan", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Australia", country: "Australia", price: 35000, model: "Celica", make: "Toyota" },
            { area: "China", country: "China", price: 72000, model: "Boxter", make: "Porsche" },
            { area: "India", country: "India", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Singapore", country: "Singapore", price: 35000, model: "Celica", make: "Toyota" },
            { area: "Singapore", country: "Singapore", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Singapore", country: "Singapore", price: 35000, model: "Celica", make: "Toyota" },
            { area: "Australia", country: "Australia", price: 72000, model: "Boxter", make: "Porsche" },
            { area: "India", country: "India", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Singapore", country: "Singapore", price: 35000, model: "Celica", make: "Toyota"  },
            { area: "Singapore", country: "Singapore", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "India", country: "India", price: 35000, model: "Celica",make: "Toyota" },
            { area: "Singapore", country: "Singapore", price: 72000, model: "Boxter", make: "Porsche" },
            { area: "Vietnam", country: "Vietnam", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Thailand", country: "Thailand", price: 35000, model: "Celica", make: "Toyota" },
            { area: "Malaysia", country: "Malaysia", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Cambodia", country: "Cambodia", price: 35000, model: "Celica", make: "Toyota" },
            { area: "India", country: "India", price: 72000, model: "Boxter", make: "Porsche" },
            { area: "Australia", country: "Australia", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "America", country: "America", price: 35000, model: "Celica", make: "Toyota" },
            { area: "Korea", country: "Korea", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Singapore", country: "Singapore", price: 35000, model: "Celica", make: "Toyota" },
            { area: "Vietnam", country: "Vietnam", price: 72000, model: "Boxter", make: "Porsche" },
            { area: "Malaysia", country: "Malaysia", price: 32000, model: "Mondeo", make: "Ford" },
            { area: "Japan", country: "Japan", price: 35000, model: "Celica", make: "Toyota" }
        ];
    }

    $scope.gridOptions = {
        columnDefs: columnDefs,
        rowData: rowData,
        headerHeight: 38,
        rowHeight: 38,
        minColWidth: 200,
        enableColResize: false,
        enableSorting: true,
        unSortIcon: true,
        suppressMenuHide: true,
        enableFilter: true,
        suppressCellSelection: true,
        suppressMovableColumns: true,
        suppressContextMenu: true,
        suppressMenuMainPanel: true,
        suppressMenuColumnPanel: true,
        onGridReady: function() {
            resizeGrid();
        },
        onGridSizeChanged: function() {
            resizeGrid();
        },
        // override all the defaults with font awesome
        icons: {
            // use font awesome for menu icons
            // menu: '<i class="fa fa-bars"/>',
            filter: '<i class="fa fa-filter"/>',
            sortAscending: '<i class="fa fa-caret-down"/>',
            sortDescending: '<i class="fa fa-caret-up"/>',
            sortUnSort: '<i class="fa fa-sort"/>',
            groupExpanded: '<i class="fa fa-minus-circle"/>',
            groupContracted: '<i class="fa fa-plus-circle"/>'
        }
    };

    function resizeGrid() {
        $scope.gridOptions.api.sizeColumnsToFit();
    }

    //Custom Filter 
    function customFilter() {}

    customFilter.prototype.init = function(params) {
        this.valueGetter = params.valueGetter;
        this.filterText = null;
        this.setupGui(params);
    };

    // not called by ag-Grid, just for us to help setup
    customFilter.prototype.setupGui = function(params) {
        this.gui = document.createElement('div');
        this.gui.innerHTML =
            '<div class="custom-filter">' +
            '<label>Custom Filter:</label>' +
            '<div><input class="custom-search" type="text" id="filterText" placeholder="Search"/></div>' +
            '<div>This filter does partial word search on multiple words, eg "mich phel" still brings back Michael Phelps.</div>' +
            '</div>';

        this.eFilterText = this.gui.querySelector('#filterText');
        this.eFilterText.addEventListener("changed", listener);
        this.eFilterText.addEventListener("paste", listener);
        this.eFilterText.addEventListener("input", listener);
        // IE doesn't fire changed for special keys (eg delete, backspace), so need to
        // listen for this further ones
        this.eFilterText.addEventListener("keydown", listener);
        this.eFilterText.addEventListener("keyup", listener);

        var that = this;

        function listener(event) {
            that.filterText = event.target.value;
            params.filterChangedCallback();
        }
    };

    customFilter.prototype.getGui = function() {
        return this.gui;
    };

    customFilter.prototype.doesFilterPass = function(params) {
        // make sure each word passes separately, ie search for firstname, lastname
        var passed = true;
        var valueGetter = this.valueGetter;
        this.filterText.toLowerCase().split(" ").forEach(function(filterWord) {
            var value = valueGetter(params);
            if (value.toString().toLowerCase().indexOf(filterWord) < 0) {
                passed = false;
            }
        });

        return passed;
    };

    customFilter.prototype.isFilterActive = function() {
        return this.filterText !== null && this.filterText !== undefined && this.filterText !== '';
    };

    customFilter.prototype.getApi = function() {
        var that = this;
        return {
            getModel: function() {
                var model = { value: that.filterText.value };
                return model;
            },
            setModel: function(model) {
                that.eFilterText.value = model.value;
            }
        }
    };
}
