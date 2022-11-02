import {
    Component,
    Autowired,
    ColumnController,
    EventService,
    Context,
    PostConstruct,
    Events,
    OriginalColumnGroup,
    Column,
    Utils as _,
    OriginalColumnGroupChild
} from "ag-grid/main";
import {RenderedItem} from "./renderedItem";
import {RenderedGroup} from "./renderedGroup";
import {RenderedColumn} from "./renderedColumn";

export class ColumnSelectPanel extends Component {

    @Autowired('columnController') private columnController: ColumnController;
    @Autowired('eventService') private globalEventService: EventService;
    @Autowired('context') private context: Context;

    private static TEMPLATE = '<div class="ag-column-select-panel"></div>';

    private renderedItems: {[key: string]: RenderedItem};

    private columnTree: OriginalColumnGroupChild[];

    private allowDragging: boolean;

    constructor(allowDragging: boolean) {
        super(ColumnSelectPanel.TEMPLATE);
        this.allowDragging = allowDragging;
    }

    @PostConstruct
    public init(): void {
        this.addDestroyableEventListener(this.globalEventService, Events.EVENT_COLUMN_EVERYTHING_CHANGED, this.onColumnsChanged.bind(this));
        if (this.columnController.isReady()) {
            this.onColumnsChanged();
        }
    }

    public onColumnsChanged(): void {
        this.destroyAllRenderedElements();
        this.columnTree = this.columnController.getOriginalColumnTree();
        this.recursivelyRenderComponents(this.columnTree, 0);
    }

    public destroy(): void {
        super.destroy();
        this.destroyAllRenderedElements();
    }

    private destroyAllRenderedElements(): void {
        _.removeAllChildren(this.getGui());
        if (this.renderedItems) {
            _.iterateObject(this.renderedItems, (key: string, renderedItem: RenderedItem) => renderedItem.destroy() );
        }
        this.renderedItems = {};
    }

    private recursivelyRenderGroupComponent(columnGroup: OriginalColumnGroup, dept: number): void {
        // only render group if user provided the definition
        var newDept: number;

        if (columnGroup.getColGroupDef()) {
            var renderedGroup = new RenderedGroup(columnGroup, dept, this.onGroupExpanded.bind(this));
            this.context.wireBean(renderedGroup);
            this.appendChild(renderedGroup.getGui());
            // we want to indent on the gui for the children
            newDept = dept + 1;

            this.renderedItems[columnGroup.getId()] = renderedGroup;
        } else {
            // no children, so no indent
            newDept = dept;
        }

        this.recursivelyRenderComponents(columnGroup.getChildren(), newDept);
    }

    private recursivelyRenderColumnComponent(column: Column, dept: number): void {
        var renderedColumn = new RenderedColumn(column, dept, this.allowDragging);
        this.context.wireBean(renderedColumn);
        this.appendChild(renderedColumn.getGui());

        this.renderedItems[column.getId()] = renderedColumn;
    }

    private recursivelyRenderComponents(tree: any[], dept: number): void {
        tree.forEach( child => {
            if (child instanceof OriginalColumnGroup) {
                this.recursivelyRenderGroupComponent(<OriginalColumnGroup> child, dept);
            } else {
                this.recursivelyRenderColumnComponent(<Column> child, dept);
            }
        });
    }

    private recursivelySetVisibility(columnTree: any[], visible: boolean): void {

        columnTree.forEach( child => {

            var component = this.renderedItems[child.getId()];
            if (component) {
                component.setVisible(visible);
            }

            if (child instanceof OriginalColumnGroup) {
                var columnGroup = <OriginalColumnGroup> child;

                var newVisible: boolean;
                if (component) {
                    var expanded = (<RenderedGroup>component).isExpanded();
                    newVisible = visible ? expanded : false;
                } else {
                    newVisible = visible;
                }

                var newChildren = columnGroup.getChildren();
                this.recursivelySetVisibility(newChildren, newVisible);

            }

        });
    }

    public onGroupExpanded(): void {
        this.recursivelySetVisibility(this.columnTree, true);
    }
}
