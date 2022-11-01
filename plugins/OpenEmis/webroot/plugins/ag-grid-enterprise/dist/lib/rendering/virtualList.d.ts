// ag-grid-enterprise v13.2.0
import { Component } from "ag-grid/main";
export interface VirtualListModel {
    getRowCount(): number;
    getRow(index: number): any;
}
export declare class VirtualList extends Component {
    private static TEMPLATE;
    private model;
    private eListContainer;
    private rowsInBodyContainer;
    private componentCreator;
    private rowHeight;
    private environment;
    constructor();
    private init();
    ensureIndexVisible(index: number): void;
    setComponentCreator(componentCreator: (value: any) => Component): void;
    getRowHeight(): number;
    getScrollTop(): number;
    setRowHeight(rowHeight: number): void;
    refresh(): void;
    private clearVirtualRows();
    private drawVirtualRows();
    private ensureRowsRendered(start, finish);
    private removeVirtualRows(rowsToRemove);
    private insertRow(value, rowIndex);
    private addScrollListener();
    setModel(model: VirtualListModel): void;
}
