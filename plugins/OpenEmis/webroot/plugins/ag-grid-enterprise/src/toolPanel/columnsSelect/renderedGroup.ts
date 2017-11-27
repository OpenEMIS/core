import {
    AgCheckbox,
    Autowired,
    Column,
    ColumnController,
    Component,
    Context,
    CssClassApplier,
    DragAndDropService,
    DragSource,
    DragSourceType,
    Events,
    EventService,
    GridOptionsWrapper,
    GridPanel,
    OriginalColumnGroup,
    PostConstruct,
    QuerySelector,
    TouchListener,
    Utils
} from "ag-grid/main";

export class RenderedGroup extends Component {

    private static TEMPLATE =
        '<div class="ag-column-select-column-group">' +
        '  <span id="eIndent" class="ag-column-select-indent"></span>' +
        '  <span id="eColumnGroupIcons" class="ag-column-group-icons">' +
        '    <span id="eGroupOpenedIcon" class="ag-column-group-closed-icon"></span>' +
        '    <span id="eGroupClosedIcon" class="ag-column-group-opened-icon"></span>' +
        '  </span>' +
        '  <span id="eCheckboxAndText">' +
        '    <ag-checkbox class="ag-column-select-checkbox"></ag-checkbox>' +
        '    <span id="eText" class="ag-column-select-column-group-label"></span>' +
        '  </span>' +
        '</div>';

    @Autowired('gridOptionsWrapper') private gridOptionsWrapper: GridOptionsWrapper;
    @Autowired('columnController') private columnController: ColumnController;
    @Autowired('gridPanel') private gridPanel: GridPanel;
    @Autowired('context') private context: Context;
    @Autowired('dragAndDropService') private dragAndDropService: DragAndDropService;
    @Autowired('eventService') private eventService: EventService;

    @QuerySelector('.ag-column-select-checkbox') private cbSelect: AgCheckbox;

    private columnGroup: OriginalColumnGroup;
    private expanded = true;
    private columnDept: number;

    private eGroupClosedIcon: HTMLElement;
    private eGroupOpenedIcon: HTMLElement;

    private expandedCallback: ()=>void;

    private allowDragging: boolean;

    private displayName: string;

    private processingColumnStateChange = false;

    constructor(columnGroup: OriginalColumnGroup, columnDept: number, expandedCallback: ()=>void, allowDragging: boolean) {
        super();
        this.columnGroup = columnGroup;
        this.columnDept = columnDept;
        this.expandedCallback = expandedCallback;
        this.allowDragging = allowDragging;
    }

    @PostConstruct
    public init(): void {
        this.setTemplate(RenderedGroup.TEMPLATE);

        this.instantiate(this.context);

        let eText = this.queryForHtmlElement('#eText');

        this.displayName = this.columnGroup.getColGroupDef() ? this.columnGroup.getColGroupDef().headerName : null;
        if (Utils.missing(this.displayName)) {
            this.displayName = '>>'
        }

        eText.innerHTML = this.displayName;
        this.setupExpandContract();

        let eIndent = this.queryForHtmlElement('#eIndent');
        eIndent.style.width = (this.columnDept * 10) + 'px';

        this.addDestroyableEventListener(eText, 'click', this.onClick.bind(this) );
        this.addDestroyableEventListener(this.eventService, Events.EVENT_COLUMN_PIVOT_MODE_CHANGED, this.onColumnStateChanged.bind(this) );
        this.addDestroyableEventListener(this.cbSelect, AgCheckbox.EVENT_CHANGED, this.onCheckboxChanged.bind(this));

        let eCheckboxAndText = this.queryForHtmlElement('#eCheckboxAndText');
        let touchListener = new TouchListener(eCheckboxAndText);
        this.addDestroyableEventListener(touchListener, TouchListener.EVENT_TAP, this.onClick.bind(this) );
        this.addDestroyFunc( touchListener.destroy.bind(touchListener) );

        this.setOpenClosedIcons();

        if (this.allowDragging) {
            this.addDragSource();
        }

        this.onColumnStateChanged();
        this.addVisibilityListenersToAllChildren();

        CssClassApplier.addToolPanelClassesFromColDef(this.columnGroup.getColGroupDef(), this.getHtmlElement(), this.gridOptionsWrapper, null, this.columnGroup);
    }

    private addVisibilityListenersToAllChildren(): void {
        this.columnGroup.getLeafColumns().forEach( column => {
            this.addDestroyableEventListener(column, Column.EVENT_VISIBLE_CHANGED, this.onColumnStateChanged.bind(this));
            this.addDestroyableEventListener(column, Column.EVENT_VALUE_CHANGED, this.onColumnStateChanged.bind(this) );
            this.addDestroyableEventListener(column, Column.EVENT_PIVOT_CHANGED, this.onColumnStateChanged.bind(this) );
            this.addDestroyableEventListener(column, Column.EVENT_ROW_GROUP_CHANGED, this.onColumnStateChanged.bind(this) );
        });
    }

    private addDragSource(): void {
        let dragSource: DragSource = {
            type: DragSourceType.ToolPanel,
            eElement: this.getHtmlElement(),
            dragItemName: this.displayName,
            dragItemCallback: () => this.createDragItem()
        };
        this.dragAndDropService.addDragSource(dragSource, true);
        this.addDestroyFunc( ()=> this.dragAndDropService.removeDragSource(dragSource) );
    }

    private createDragItem() {
        let visibleState: { [key: string]: boolean } = {};
        this.columnGroup.getLeafColumns().forEach(col => {
            visibleState[col.getId()] = col.isVisible();
        });

        return {
            columns: this.columnGroup.getLeafColumns(),
            visibleState: visibleState
        };
    }

    private setupExpandContract(): void {
        this.eGroupClosedIcon = this.queryForHtmlElement('#eGroupClosedIcon');
        this.eGroupOpenedIcon = this.queryForHtmlElement('#eGroupOpenedIcon');

        this.eGroupClosedIcon.appendChild(Utils.createIcon('columnSelectClosed', this.gridOptionsWrapper, null));
        this.eGroupOpenedIcon.appendChild(Utils.createIcon('columnSelectOpen', this.gridOptionsWrapper, null));

        this.addDestroyableEventListener(this.eGroupClosedIcon, 'click', this.onExpandOrContractClicked.bind(this));
        this.addDestroyableEventListener(this.eGroupOpenedIcon, 'click', this.onExpandOrContractClicked.bind(this));

        let eColumnGroupIcons = this.queryForHtmlElement('#eColumnGroupIcons');
        let touchListener = new TouchListener(eColumnGroupIcons);
        this.addDestroyableEventListener(touchListener, TouchListener.EVENT_TAP, this.onExpandOrContractClicked.bind(this));
        this.addDestroyFunc( touchListener.destroy.bind(touchListener) );
    }

    private onClick(): void {
        this.cbSelect.setSelected(!this.cbSelect.isSelected());
    }

    private onCheckboxChanged(): void {
        if (this.processingColumnStateChange) { return; }

        let childColumns = this.columnGroup.getLeafColumns();
        let selected = this.cbSelect.isSelected();

        if (this.columnController.isPivotMode()) {
            if (selected) {
                this.actionCheckedReduce(childColumns);
            } else {
                this.actionUnCheckedReduce(childColumns)
            }
        } else {
            this.columnController.setColumnsVisible(childColumns, selected);
        }
    }

    private actionUnCheckedReduce(columns: Column[]): void {

        let columnsToUnPivot: Column[] = [];
        let columnsToUnValue: Column[] = [];
        let columnsToUnGroup: Column[] = [];

        columns.forEach( column => {
            if (column.isPivotActive()) {
                columnsToUnPivot.push(column);
            }
            if (column.isRowGroupActive()) {
                columnsToUnGroup.push(column);
            }
            if (column.isValueActive()) {
                columnsToUnValue.push(column);
            }
        });

        if (columnsToUnPivot.length>0) {
            this.columnController.removePivotColumns(columnsToUnPivot);
        }
        if (columnsToUnGroup.length>0) {
            this.columnController.removeRowGroupColumns(columnsToUnGroup);
        }
        if (columnsToUnValue.length>0) {
            this.columnController.removeValueColumns(columnsToUnValue);
        }
    }

    private actionCheckedReduce(columns: Column[]): void {

        let columnsToAggregate: Column[] = [];
        let columnsToGroup: Column[] = [];
        let columnsToPivot: Column[] = [];

        columns.forEach( column => {
            // don't change any column that's already got a function active
            if (column.isAnyFunctionActive()) { return; }

            if (column.isAllowValue()) {
                columnsToAggregate.push(column);
            } else if (column.isAllowRowGroup()) {
                columnsToGroup.push(column);
            } else if (column.isAllowRowGroup()) {
                columnsToPivot.push(column);
            }

        });

        if (columnsToAggregate.length>0) {
            this.columnController.addValueColumns(columnsToAggregate);
        }
        if (columnsToGroup.length>0) {
            this.columnController.addRowGroupColumns(columnsToGroup);
        }
        if (columnsToPivot.length>0) {
            this.columnController.addPivotColumns(columnsToPivot);
        }

    }

    private onColumnStateChanged(): void {
        let columnsReduced = this.columnController.isPivotMode();

        let visibleChildCount = 0;
        let hiddenChildCount = 0;

        this.columnGroup.getLeafColumns().forEach( (column: Column) => {
            if (this.isColumnVisible(column, columnsReduced)) {
                visibleChildCount++;
            } else {
                hiddenChildCount++;
            }
        });

        let selectedValue: boolean;
        if (visibleChildCount>0 && hiddenChildCount>0) {
            selectedValue = null;
        } else if (visibleChildCount > 0) {
            selectedValue = true;
        } else {
            selectedValue = false;
        }

        this.processingColumnStateChange = true;
        this.cbSelect.setSelected(selectedValue);
        this.processingColumnStateChange = false;
    }

    private isColumnVisible(column: Column, columnsReduced: boolean): boolean {
        if (columnsReduced) {
            let pivoted = column.isPivotActive();
            let grouped = column.isRowGroupActive();
            let aggregated = column.isValueActive();
            return pivoted || grouped || aggregated;
        } else {
            return column.isVisible();
        }
    }

    private onExpandOrContractClicked(): void {
        this.expanded = !this.expanded;
        this.setOpenClosedIcons();
        this.expandedCallback();
    }

    private setOpenClosedIcons(): void {
        let folderOpen = this.expanded;
        Utils.setVisible(this.eGroupClosedIcon, !folderOpen);
        Utils.setVisible(this.eGroupOpenedIcon, folderOpen);
    }

    public isExpanded(): boolean {
        return this.expanded;
    }
}
