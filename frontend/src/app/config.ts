interface TableColumns {
  id?: any;
  name?: any;
  status?: any;
  overall_average?: any;
  comments?: any;
}

const COLUMN_ID: any = {
  headerName: "OpenEMIS ID",
  field: "id",
  sortable: true,
  filterable: true,
  filterValue: ['Equals','Not Equals','Starts with',' Ends with','Contains','Not contains'],
  visible: true,
  width: 20,
  pinned: true
};

const COLUMN_NAME: any = {
  headerName: "Name",
  field: "name",
  sortable: true,
  filterable: true,
  visible: true,
  width: 30,
  filterValue: ['Equals','Not Equals','Starts with',' Ends with','Contains','Not contains'],
  class: "ag-name",
  // type: 'normal',
  enableValue: false,
  enablePivot: true,
  enableRowGroup: true,
  rowDrag: false,
};

const COLUMN_STATUS: any = {
  headerName: "Status",
  field: "status",
  sortable: true,
  filterable: true,
  width: 20,
  filterValue: ['Equals','Not Equals','Starts with',' Ends with','Contains','Not contains'],
  visible: true,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: true,
};

const OVERALL_AVERAGE: any = {
  headerName: "Overall Average",
  field: "overall_average",
  sortable: true,
  filterable: true,
  filterValue: ['Equals','Not Equals','Less Than','Less Than or Equal','Greater Than','Greater Than or Equal','In Range'],
  visible: true,
  width: 20,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: true
};

const COMMENTS: any = {
  headerName: "Comments",
  field: "comments",
  sortable: true,
  filterable: true,
  visible: true,
  width: 550,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: true,
  canEdit: true
};


export const TABLE_COLUMN_LIST: TableColumns = {
  id: COLUMN_ID,
  name: COLUMN_NAME,
  status: COLUMN_STATUS,
  overall_average: OVERALL_AVERAGE,
  comments: COMMENTS
};