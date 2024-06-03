interface WorkBenchListColumn {
  id?: any;
  Title?: any;
  Name?: any;
  Institution?: any;
  ReceivedDate?: any;
}

export const WORKBENCHTABLECOLUMN: WorkBenchListColumn = {
  id: {
    headerName: 'ID',
    field: 'id',
    visible: false
  },
  Name: {
    headerName: 'Status',
    field: 'status',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  Title: {
    headerName: 'Request Title',
    field: 'request_title',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  Institution: {
    headerName: 'Institution',
    field: 'institution',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  ReceivedDate: {
    headerName: 'Received Date',
    field: 'received_date',
    sortable: true,
    filterable: true,
    filterValue: []
  }
};



