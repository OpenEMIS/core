// ag-grid-enterprise v4.1.4
import { ColDef } from "ag-grid/main";
export declare class SetFilterModel {
    private colDef;
    private filterParams;
    private rowModel;
    private valueGetter;
    private allUniqueValues;
    private availableUniqueValues;
    private displayedValues;
    private miniFilter;
    private selectedValuesCount;
    private selectedValuesMap;
    private showingAvailableOnly;
    private usingProvidedSet;
    private doesRowPassOtherFilters;
    constructor(colDef: ColDef, rowModel: any, valueGetter: any, doesRowPassOtherFilters: any);
    refreshAfterNewRowsLoaded(keepSelection: any, isSelectAll: boolean): void;
    refreshAfterAnyFilterChanged(): void;
    private createAllUniqueValues();
    private createAvailableUniqueValues();
    private sortValues(values);
    private getUniqueValues(filterOutNotAvailable);
    setMiniFilter(newMiniFilter: any): boolean;
    getMiniFilter(): any;
    private processMiniFilter();
    getDisplayedValueCount(): number;
    getDisplayedValue(index: any): any;
    selectEverything(): void;
    isFilterActive(): boolean;
    selectNothing(): void;
    getUniqueValueCount(): number;
    getUniqueValue(index: any): any;
    unselectValue(value: any): void;
    selectValue(value: any): void;
    isValueSelected(value: any): boolean;
    isEverythingSelected(): boolean;
    isNothingSelected(): boolean;
    getModel(): any;
    setModel(model: any, isSelectAll?: boolean): void;
}
