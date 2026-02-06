interface TableColumns {
    id?: any;
    name?: any;
    status?: any;
    period1?: any;
    period2?: any;
    period3?: any;
    totalMark?: any;
  }
  
  const COLUMN_ID: any = {
    headerName: "OpenEMIS ID",
    field: "id",
    sortable: true,
    filterable: false,
    filterValue: ['Equals','Not Equals','Starts with',' Ends with','Contains','Not contains'],
    visible: true,
    width: 40,
  };
  
  const COLUMN_NAME: any = {
    headerName: "Name",
    field: "name",
    sortable: true,
    filterable: false,
    visible: true,
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
    filterable: false,
    filterValue: ['Equals','Not Equals','Starts with',' Ends with','Contains','Not contains'],
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
  };
  
  const COLUMN_PERIOD1: any = {
    headerName: "Assessment Period 1 | 100",
    field: "period1",
    sortable: true,
    filterable: false,
    filterValue: ['Equals','Not Equals','Less Than','Less Than or Equal','Greater Than','Greater Than or Equal','In Range'],
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
    canEdit: true
  };
  
  const COLUMN_PERIOD2: any = {
    headerName: "Assessment Period 2 | 100",
    field: "period2",
    sortable: true,
    filterable: false,
    filterValue: ['Equals','Not Equals','Less Than','Less Than or Equal','Greater Than','Greater Than or Equal','In Range'],
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
    canEdit: true
  };

  const COLUMN_PERIOD3: any = {
    headerName: "Assessment Period 3 | 100",
    field: "period3",
    sortable: true,
    filterable: false,
    filterValue: ['Equals','Not Equals','Less Than','Less Than or Equal','Greater Than','Greater Than or Equal','In Range'],
    visible: true,
    enableValue: false,
    enablePivot: false,
    enableRowGroup: true,
    canEdit: true
  };
  
  const COLUMN_TOTALMARK: any = {
    headerName: "Total Mark",
    field: "totalMark",
    sortable: true,
    filterable: false,
    filterValue: ['In Range','Greater Than','Less Than'],
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
    width: 40,
  };
  
  
  export const TABLE_COLUMN_LIST: TableColumns = {
    id: COLUMN_ID,
    name: COLUMN_NAME,
    status: COLUMN_STATUS,
    period1: COLUMN_PERIOD1,
    period2: COLUMN_PERIOD2,
    period3: COLUMN_PERIOD3,
    totalMark: COLUMN_TOTALMARK
  };