// ag-grid-enterprise v13.2.0
import { MenuItemDef, Component } from "ag-grid";
export declare class MenuList extends Component {
    private context;
    private popupService;
    private static TEMPLATE;
    private static SEPARATOR_TEMPLATE;
    private activeMenuItemParams;
    private activeMenuItem;
    private timerCount;
    private removeChildFuncs;
    private subMenuParentDef;
    constructor();
    clearActiveItem(): void;
    addMenuItems(menuItems: (MenuItemDef | string)[]): void;
    addItem(menuItemDef: MenuItemDef): void;
    private mouseEnterItem(menuItemParams, menuItem);
    private removeActiveItem();
    private addHoverForChildPopup(menuItemDef, menuItemComp);
    addSeparator(): void;
    private showChildMenu(menuItemDef, menuItemComp);
    private removeChildPopup();
    destroy(): void;
}
