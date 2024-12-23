interface TableColumns {
    dateTime?: any;
    openEmisId?: any;
    personName?: any;
    access?: any;
    location?: any;
}

const COLUMN_DATETIME: any = {
    headerName: "Date Time",
    field: "dateTime",
    sortable: true,
    filterable: false,
    filterValue: [],
    class: "ag-school-column",
    canEdit: false
}

const COLUMN_OPENEMISID: any = {
    headerName: "OpenEMIS ID",
    field: "openemis_no",
    sortable: true,
    filterable: false,
    filterValue: [],
    class: "ag-school-column",
    canEdit: false
}

const COLUMN_PERSONNAME: any = {
    headerName: "Name",
    field: "name",
    sortable: true,
    filterable: false,
    filterValue: [],
    class: "ag-school-column",
    canEdit: false
}

const COLUMN_ACCESS: any = {
    headerName: "Access",
    field: "access",
    sortable: true,
    filterable: false,
    filterValue: [],
    class: "ag-school-column",
    canEdit: false
}

const COLUMN_LOCATION: any = {
    headerName: "Location",
    field: "location",
    sortable: true,
    filterable: false,
    filterValue: [],
    class: "ag-school-column",
    canEdit: false
}

export const TABLE_COLUMN_LIST: TableColumns = {
    dateTime: COLUMN_DATETIME,
    openEmisId: COLUMN_OPENEMISID,
    personName: COLUMN_PERSONNAME,
    access: COLUMN_ACCESS,
    location: COLUMN_LOCATION
};