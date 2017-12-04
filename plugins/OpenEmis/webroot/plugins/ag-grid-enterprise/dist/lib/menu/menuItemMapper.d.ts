// ag-grid-enterprise v13.2.0
import { MenuItemDef, Column } from 'ag-grid';
export declare class MenuItemMapper {
    private gridOptionsWrapper;
    private columnController;
    private gridApi;
    private clipboardService;
    private aggFuncService;
    mapWithStockItems(originalList: (MenuItemDef | string)[], column: Column): (MenuItemDef | string)[];
    private getStockMenuItem(key, column);
    private createAggregationSubMenu(column);
}
