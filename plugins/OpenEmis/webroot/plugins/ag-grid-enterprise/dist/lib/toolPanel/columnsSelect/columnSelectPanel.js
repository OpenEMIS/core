// ag-grid-enterprise v13.2.0
"use strict";
var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
var main_1 = require("ag-grid/main");
var renderedGroup_1 = require("./renderedGroup");
var renderedColumn_1 = require("./renderedColumn");
var ColumnSelectPanel = (function (_super) {
    __extends(ColumnSelectPanel, _super);
    // we allow dragging in the toolPanel, but not when this component appears in the column menu
    function ColumnSelectPanel(allowDragging) {
        var _this = _super.call(this, ColumnSelectPanel.TEMPLATE) || this;
        _this.allowDragging = allowDragging;
        return _this;
    }
    ColumnSelectPanel.prototype.init = function () {
        this.addDestroyableEventListener(this.globalEventService, main_1.Events.EVENT_COLUMN_EVERYTHING_CHANGED, this.onColumnsChanged.bind(this));
        if (this.columnController.isReady()) {
            this.onColumnsChanged();
        }
    };
    ColumnSelectPanel.prototype.onColumnsChanged = function () {
        this.destroyAllRenderedElements();
        this.columnTree = this.columnController.getPrimaryColumnTree();
        this.recursivelyRenderComponents(this.columnTree, 0);
    };
    ColumnSelectPanel.prototype.destroy = function () {
        _super.prototype.destroy.call(this);
        this.destroyAllRenderedElements();
    };
    ColumnSelectPanel.prototype.destroyAllRenderedElements = function () {
        main_1.Utils.removeAllChildren(this.getHtmlElement());
        if (this.renderedItems) {
            main_1.Utils.iterateObject(this.renderedItems, function (key, renderedItem) { return renderedItem.destroy(); });
        }
        this.renderedItems = {};
    };
    ColumnSelectPanel.prototype.recursivelyRenderGroupComponent = function (columnGroup, dept) {
        // only render group if user provided the definition
        var newDept;
        if (columnGroup.getColGroupDef() && columnGroup.getColGroupDef().suppressToolPanel) {
            return;
        }
        if (!columnGroup.isPadding()) {
            var renderedGroup = new renderedGroup_1.RenderedGroup(columnGroup, dept, this.onGroupExpanded.bind(this), this.allowDragging);
            this.context.wireBean(renderedGroup);
            this.appendChild(renderedGroup.getHtmlElement());
            // we want to indent on the gui for the children
            newDept = dept + 1;
            this.renderedItems[columnGroup.getId()] = renderedGroup;
        }
        else {
            // no children, so no indent
            newDept = dept;
        }
        this.recursivelyRenderComponents(columnGroup.getChildren(), newDept);
    };
    ColumnSelectPanel.prototype.recursivelyRenderColumnComponent = function (column, dept) {
        if (column.getColDef() && column.getColDef().suppressToolPanel) {
            return;
        }
        var renderedColumn = new renderedColumn_1.RenderedColumn(column, dept, this.allowDragging);
        this.context.wireBean(renderedColumn);
        this.appendChild(renderedColumn.getHtmlElement());
        this.renderedItems[column.getId()] = renderedColumn;
    };
    ColumnSelectPanel.prototype.recursivelyRenderComponents = function (tree, dept) {
        var _this = this;
        tree.forEach(function (child) {
            if (child instanceof main_1.OriginalColumnGroup) {
                _this.recursivelyRenderGroupComponent(child, dept);
            }
            else {
                _this.recursivelyRenderColumnComponent(child, dept);
            }
        });
    };
    ColumnSelectPanel.prototype.recursivelySetVisibility = function (columnTree, visible) {
        var _this = this;
        columnTree.forEach(function (child) {
            var component = _this.renderedItems[child.getId()];
            if (component) {
                component.setVisible(visible);
            }
            if (child instanceof main_1.OriginalColumnGroup) {
                var columnGroup = child;
                var newVisible = void 0;
                if (component) {
                    var expanded = component.isExpanded();
                    newVisible = visible ? expanded : false;
                }
                else {
                    newVisible = visible;
                }
                var newChildren = columnGroup.getChildren();
                _this.recursivelySetVisibility(newChildren, newVisible);
            }
        });
    };
    ColumnSelectPanel.prototype.onGroupExpanded = function () {
        this.recursivelySetVisibility(this.columnTree, true);
    };
    ColumnSelectPanel.TEMPLATE = '<div class="ag-column-select-panel"></div>';
    __decorate([
        main_1.Autowired('columnController'),
        __metadata("design:type", main_1.ColumnController)
    ], ColumnSelectPanel.prototype, "columnController", void 0);
    __decorate([
        main_1.Autowired('eventService'),
        __metadata("design:type", main_1.EventService)
    ], ColumnSelectPanel.prototype, "globalEventService", void 0);
    __decorate([
        main_1.Autowired('context'),
        __metadata("design:type", main_1.Context)
    ], ColumnSelectPanel.prototype, "context", void 0);
    __decorate([
        main_1.PostConstruct,
        __metadata("design:type", Function),
        __metadata("design:paramtypes", []),
        __metadata("design:returntype", void 0)
    ], ColumnSelectPanel.prototype, "init", null);
    return ColumnSelectPanel;
}(main_1.Component));
exports.ColumnSelectPanel = ColumnSelectPanel;
