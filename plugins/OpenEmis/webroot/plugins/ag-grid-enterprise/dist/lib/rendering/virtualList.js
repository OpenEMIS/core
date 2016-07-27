// ag-grid-enterprise v4.1.4
var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var main_1 = require('ag-grid/main');
var VirtualList = (function (_super) {
    __extends(VirtualList, _super);
    function VirtualList() {
        _super.call(this, null);
        this.rowsInBodyContainer = {};
        this.rowHeight = 20;
    }
    VirtualList.prototype.init = function () {
        this.setTemplate(VirtualList.TEMPLATE);
        this.eListContainer = this.queryForHtmlElement(".ag-virtual-list-container");
        this.addScrollListener();
    };
    VirtualList.prototype.ensureIndexVisible = function (index) {
        var lastRow = this.model.getRowCount();
        if (typeof index !== 'number' || index < 0 || index >= lastRow) {
            console.warn('invalid row index for ensureIndexVisible: ' + index);
            return;
        }
        // var nodeAtIndex = this.rowModel.getRow(index);
        var rowTopPixel = index * this.rowHeight;
        var rowBottomPixel = rowTopPixel + this.rowHeight;
        var viewportTopPixel = this.getGui().scrollTop;
        var viewportHeight = this.getGui().offsetHeight;
        var viewportBottomPixel = viewportTopPixel + viewportHeight;
        var viewportScrolledPastRow = viewportTopPixel > rowTopPixel;
        var viewportScrolledBeforeRow = viewportBottomPixel < rowBottomPixel;
        if (viewportScrolledPastRow) {
            // if row is before, scroll up with row at top
            this.getGui().scrollTop = rowTopPixel;
        }
        else if (viewportScrolledBeforeRow) {
            // if row is below, scroll down with row at bottom
            var newScrollPosition = rowBottomPixel - viewportHeight;
            this.getGui().scrollTop = newScrollPosition;
        }
    };
    VirtualList.prototype.setComponentCreator = function (componentCreator) {
        this.componentCreator = componentCreator;
    };
    VirtualList.prototype.getRowHeight = function () {
        return this.rowHeight;
    };
    VirtualList.prototype.getScrollTop = function () {
        return this.getGui().scrollTop;
    };
    VirtualList.prototype.setRowHeight = function (rowHeight) {
        this.rowHeight = rowHeight;
        this.refresh();
    };
    VirtualList.prototype.refresh = function () {
        if (main_1.Utils.missing(this.model)) {
            return;
        }
        this.eListContainer.style.height = (this.model.getRowCount() * this.rowHeight) + "px";
        this.clearVirtualRows();
        this.drawVirtualRows();
    };
    VirtualList.prototype.clearVirtualRows = function () {
        var rowsToRemove = Object.keys(this.rowsInBodyContainer);
        this.removeVirtualRows(rowsToRemove);
    };
    VirtualList.prototype.drawVirtualRows = function () {
        var topPixel = this.getGui().scrollTop;
        var bottomPixel = topPixel + this.getGui().offsetHeight;
        var firstRow = Math.floor(topPixel / this.rowHeight);
        var lastRow = Math.floor(bottomPixel / this.rowHeight);
        this.ensureRowsRendered(firstRow, lastRow);
    };
    VirtualList.prototype.ensureRowsRendered = function (start, finish) {
        // at the end, this array will contain the items we need to remove
        var rowsToRemove = Object.keys(this.rowsInBodyContainer);
        // add in new rows
        for (var rowIndex = start; rowIndex <= finish; rowIndex++) {
            // see if item already there, and if yes, take it out of the 'to remove' array
            if (rowsToRemove.indexOf(rowIndex.toString()) >= 0) {
                rowsToRemove.splice(rowsToRemove.indexOf(rowIndex.toString()), 1);
                continue;
            }
            // check this row actually exists (in case overflow buffer window exceeds real data)
            if (this.model.getRowCount() > rowIndex) {
                var value = this.model.getRow(rowIndex);
                this.insertRow(value, rowIndex);
            }
        }
        // at this point, everything in our 'rowsToRemove' . . .
        this.removeVirtualRows(rowsToRemove);
    };
    // takes array of row id's
    VirtualList.prototype.removeVirtualRows = function (rowsToRemove) {
        var _this = this;
        rowsToRemove.forEach(function (index) {
            var component = _this.rowsInBodyContainer[index];
            _this.eListContainer.removeChild(component.eDiv);
            if (component.rowComponent.destroy) {
                component.rowComponent.destroy();
            }
            delete _this.rowsInBodyContainer[index];
        });
    };
    VirtualList.prototype.insertRow = function (value, rowIndex) {
        var eDiv = document.createElement('div');
        main_1.Utils.addCssClass(eDiv, 'ag-virtual-list-item');
        eDiv.style.top = (this.rowHeight * rowIndex) + "px";
        var rowComponent = this.componentCreator(value);
        eDiv.appendChild(rowComponent.getGui());
        this.eListContainer.appendChild(eDiv);
        this.rowsInBodyContainer[rowIndex] = {
            rowComponent: rowComponent,
            eDiv: eDiv
        };
    };
    VirtualList.prototype.addScrollListener = function () {
        var _this = this;
        this.addGuiEventListener('scroll', function () {
            _this.drawVirtualRows();
        });
    };
    VirtualList.prototype.setModel = function (model) {
        this.model = model;
    };
    VirtualList.TEMPLATE = '<div class="ag-virtual-list-viewport">' +
        '<div class="ag-virtual-list-container">' +
        '</div>' +
        '</div>';
    __decorate([
        main_1.Autowired('context'), 
        __metadata('design:type', main_1.Context)
    ], VirtualList.prototype, "context", void 0);
    __decorate([
        main_1.PostConstruct, 
        __metadata('design:type', Function), 
        __metadata('design:paramtypes', []), 
        __metadata('design:returntype', void 0)
    ], VirtualList.prototype, "init", null);
    return VirtualList;
})(main_1.Component);
exports.VirtualList = VirtualList;
