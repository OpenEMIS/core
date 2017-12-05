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
var ag_grid_1 = require("ag-grid");
var menuItemComponent_1 = require("./menuItemComponent");
var MenuList = (function (_super) {
    __extends(MenuList, _super);
    function MenuList() {
        var _this = _super.call(this, MenuList.TEMPLATE) || this;
        _this.timerCount = 0;
        _this.removeChildFuncs = [];
        return _this;
        // console.log('MenuList->constructor() ' + this.instance);
    }
    MenuList.prototype.clearActiveItem = function () {
        this.removeActiveItem();
        this.removeChildPopup();
    };
    MenuList.prototype.addMenuItems = function (menuItems) {
        var _this = this;
        if (ag_grid_1.Utils.missing(menuItems)) {
            return;
        }
        menuItems.forEach(function (menuItemOrString) {
            if (menuItemOrString === 'separator') {
                _this.addSeparator();
            }
            else if (typeof menuItemOrString === 'string') {
                console.log("ag-Grid: unrecognised menu item " + menuItemOrString);
            }
            else {
                var menuItem = menuItemOrString;
                _this.addItem(menuItem);
            }
        });
    };
    MenuList.prototype.addItem = function (menuItemDef) {
        var _this = this;
        var cMenuItem = new menuItemComponent_1.MenuItemComponent(menuItemDef);
        this.context.wireBean(cMenuItem);
        this.getHtmlElement().appendChild(cMenuItem.getHtmlElement());
        this.addDestroyFunc(function () { return cMenuItem.destroy(); });
        cMenuItem.addEventListener(menuItemComponent_1.MenuItemComponent.EVENT_ITEM_SELECTED, function (event) {
            if (menuItemDef.subMenu) {
                _this.showChildMenu(menuItemDef, cMenuItem);
            }
            else {
                _this.dispatchEvent(event);
            }
        });
        cMenuItem.addGuiEventListener('mouseenter', this.mouseEnterItem.bind(this, menuItemDef, cMenuItem));
        cMenuItem.addGuiEventListener('mouseleave', function () { return _this.timerCount++; });
    };
    MenuList.prototype.mouseEnterItem = function (menuItemParams, menuItem) {
        if (menuItemParams.disabled) {
            return;
        }
        if (this.activeMenuItemParams !== menuItemParams) {
            this.removeChildPopup();
        }
        this.removeActiveItem();
        this.activeMenuItemParams = menuItemParams;
        this.activeMenuItem = menuItem;
        ag_grid_1.Utils.addCssClass(this.activeMenuItem.getHtmlElement(), 'ag-menu-option-active');
        if (menuItemParams.subMenu) {
            this.addHoverForChildPopup(menuItemParams, menuItem);
        }
    };
    MenuList.prototype.removeActiveItem = function () {
        if (this.activeMenuItem) {
            ag_grid_1.Utils.removeCssClass(this.activeMenuItem.getHtmlElement(), 'ag-menu-option-active');
            this.activeMenuItem = null;
            this.activeMenuItemParams = null;
        }
    };
    MenuList.prototype.addHoverForChildPopup = function (menuItemDef, menuItemComp) {
        var _this = this;
        var timerCountCopy = this.timerCount;
        setTimeout(function () {
            var shouldShow = timerCountCopy === _this.timerCount;
            var showingThisMenu = _this.subMenuParentDef === menuItemDef;
            if (shouldShow && !showingThisMenu) {
                _this.showChildMenu(menuItemDef, menuItemComp);
            }
        }, 500);
    };
    MenuList.prototype.addSeparator = function () {
        this.getHtmlElement().appendChild(ag_grid_1.Utils.loadTemplate(MenuList.SEPARATOR_TEMPLATE));
    };
    MenuList.prototype.showChildMenu = function (menuItemDef, menuItemComp) {
        var _this = this;
        this.removeChildPopup();
        var childMenu = new MenuList();
        this.context.wireBean(childMenu);
        childMenu.addMenuItems(menuItemDef.subMenu);
        var ePopup = ag_grid_1.Utils.loadTemplate('<div class="ag-menu"></div>');
        ePopup.appendChild(childMenu.getHtmlElement());
        var hidePopupFunc = this.popupService.addAsModalPopup(ePopup, true);
        this.popupService.positionPopupForMenu({
            eventSource: menuItemComp.getGui(),
            ePopup: ePopup
        });
        this.subMenuParentDef = menuItemDef;
        var selectedListener = function (event) {
            _this.dispatchEvent(event);
        };
        childMenu.addEventListener(menuItemComponent_1.MenuItemComponent.EVENT_ITEM_SELECTED, selectedListener);
        this.removeChildFuncs.push(function () {
            childMenu.clearActiveItem();
            childMenu.destroy();
            _this.subMenuParentDef = null;
            childMenu.removeEventListener(menuItemComponent_1.MenuItemComponent.EVENT_ITEM_SELECTED, selectedListener);
            hidePopupFunc();
        });
    };
    MenuList.prototype.removeChildPopup = function () {
        this.removeChildFuncs.forEach(function (func) { return func(); });
        this.removeChildFuncs = [];
    };
    MenuList.prototype.destroy = function () {
        // console.log('MenuList->destroy() ' + this.instance);
        this.removeChildPopup();
        _super.prototype.destroy.call(this);
    };
    // private instance = Math.random();
    MenuList.TEMPLATE = '<div class="ag-menu-list"></div>';
    MenuList.SEPARATOR_TEMPLATE = '<div class="ag-menu-separator">' +
        '  <span class="ag-menu-separator-cell"></span>' +
        '  <span class="ag-menu-separator-cell"></span>' +
        '  <span class="ag-menu-separator-cell"></span>' +
        '  <span class="ag-menu-separator-cell"></span>' +
        '</div>';
    __decorate([
        ag_grid_1.Autowired('context'),
        __metadata("design:type", ag_grid_1.Context)
    ], MenuList.prototype, "context", void 0);
    __decorate([
        ag_grid_1.Autowired('popupService'),
        __metadata("design:type", ag_grid_1.PopupService)
    ], MenuList.prototype, "popupService", void 0);
    return MenuList;
}(ag_grid_1.Component));
exports.MenuList = MenuList;
