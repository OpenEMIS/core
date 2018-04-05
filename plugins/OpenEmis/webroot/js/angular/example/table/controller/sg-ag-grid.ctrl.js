//ag-Grid v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('sg-ag-grid.ctrl', ['agGrid'])
    .controller('AgGridCtrl', AgGridCtrl);
//alias as agc-c

AgGridCtrl.$inject = ['$scope'];

function AgGridCtrl($scope) {
    $scope.getSelected = function() {
        console.log("scope", $scope);
        console.log("selected nodes", $scope.gridOptions.api.getSelectedNodes());
    }
    
    // var bodyDir = getComputedStyle(document.body).direction;

    var columnDefs = [
        { headerName: "Make", field: "make", filter: customFilter, tooltip: "This is another tooltip " },
        { headerName: "Model", field: "model", filter: 'set' },
        { headerName: "Price", field: "price", filter: 'number' },
        // { headerName: "Country with long text until got ellipsis", field: "country", tooltip:"Country Tooltip"},
        { headerName: "Country with long text until got ellipsis", field: "country", tooltip:"Country Tooltip with long text to see if this tooltip can take in super long text and stuff Country Tooltip with long text to see if this tooltip can take in super long text and stuff Country Tooltip with long text to see if this tooltip can take in super long text and stuff"},
        { headerName: "Area", field: "area", tooltip: "Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip Area Tooltip"}
        // { headerName: "Area", field: "area", tooltip: "Area Tooltip"}
    ];

    // var columnDefs = [
    //     { headerName: "Make", field: "make", filter: customFilter },
    //     { headerName: "Model", field: "model", filter: 'set' },
    //     { headerName: "Price", field: "price", filter: 'number' },
    //     { headerName: "Country with long text until got ellipsis", field: "country" },
    //     { headerName: "Area", field: "area" }
    // ];

    var rowData = [
        { make: "Toyota", model: "Celica Celica Celica Celica Celica CelicaCelica Celica CelicaCelicaCelicaCelica Celica", price: 35000, country: "Japan", area: "Japan", agSelect:true },
        { make: "Ford", model: "Mondeo", price: 32000, country: "Malaysia", area: "Malaysia", agSelect:true },
        { make: "Porsche", model: "Boxter", price: 72000, country: "Vietnam", area: "Vietnam", agSelect:true },
        { make: "Toyota", model: "Celica1", price: 35000, country: "Singapore", area: "Singapore", agSelect:true },
        { make: "Ford", model: "Mondeo1", price: 32000, country: "Korea", area: "Korea" },
        { make: "Toyota", model: "Celica2", price: 35000, country: "America", area: "America" },
        { make: "Ford", model: "Mondeo2", price: 32000, country: "Australia", area: "Australia" },
        { make: "Porsche", model: "Boxter2", price: 72000, country: "India", area: "India" },
        { make: "Toyota", model: "Celica3", price: 35000, country: "Cambodia", area: "Cambodia" },
        { make: "Ford", model: "Mondeo3", price: 32000, country: "Malaysia", area: "Malaysia" },
        { make: "Toyota", model: "Celica4", price: 35000, country: "Thailand", area: "Thailand" },
        { make: "Ford", model: "Mondeo4", price: 32000, country: "Vietnam", area: "Vietnam" },
        { make: "Porsche", model: "Boxter5", price: 72000, country: "Singapore", area: "Singapore" },
        { make: "Toyota", model: "Celica5", price: 35000, country: "India", area: "India" },
        { make: "Ford", model: "Mondeo6", price: 32000, country: "Singapore", area: "Singapore" },
        { make: "Toyota", model: "Celica6", price: 35000, country: "Singapore", area: "Singapore" },
        { make: "Ford", model: "Mondeo7", price: 32000, country: "India", area: "India" },
        { make: "Porsche", model: "Boxter7", price: 72000, country: "Australia", area: "Australia" },
        { make: "Toyota", model: "Celica7", price: 35000, country: "Singapore", area: "Singapore" },
        { make: "Ford", model: "Mondeo8", price: 32000, country: "Singapore", area: "Singapore" },
        { make: "Toyota", model: "Celica8", price: 35000, country: "Singapore", area: "Singapore" },
        { make: "Ford", model: "Mondeo9", price: 32000, country: "India", area: "India" },
        { make: "Porsche", model: "Boxter9", price: 72000, country: "China", area: "China" },
        { make: "Toyota", model: "Celica0", price: 35000, country: "Australia", area: "Australia" },
        { make: "Ford", model: "Mondeo0", price: 32000, country: "Taiwan", area: "Taiwan" },
        { make: "Toyota", model: "Celica0", price: 35000, country: "Germany", area: "Germany" },
        { make: "Ford", model: "Mondeo11", price: 32000, country: "America", area: "America" },
        { make: "Porsche", model: "Boxter11", price: 72000, country: "America", area: "America" },
        { make: "Toyota", model: "Celica11", price: 35000, country: "America", area: "America" },
        { make: "Ford", model: "Mondeo12", price: 32000, country: "America", area: "America" },
        { make: "Porsche", model: "Boxter12", price: 72000, country: "Singapore", area: "Singapore" }
    ];

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
        //suppressMenuMainPanel: true,
        //suppressMenuColumnPanel: true,
        enableRtl: true

        // onGridReady: function() {
        //     resizeGrid();
        // },
        // onGridSizeChanged: function() {
        //     resizeGrid();
        // }
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

    customFilter.prototype.getModel = function() {
        var model = { value: this.filterText.value };
        return model;
    }

    customFilter.prototype.setModel = function(model) {
        this.eFilterText.value = model.value;
    }

    // customFilter.prototype.getApi = function() {
    //     var that = this;
    //     return {
    //         getModel: function() {
    //             var model = { value: that.filterText.value };
    //             return model;
    //         },
    //         setModel: function(model) {
    //             that.eFilterText.value = model.value;
    //         }
    //     }
    // };
    // 
}


