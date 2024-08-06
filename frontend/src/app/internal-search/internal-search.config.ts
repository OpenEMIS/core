interface TableColumns {
    id?: any;
    name?: any;
    gender?: any;
    dob?: any;
    nationality?: any;
    identityType?: any;
    identityNumber?: any;
    accountType?: any;
}

const COLUMN_ID: any = {
    headerName: "OpenEMIS ID",
    field: "id",
    sortable: false,
    filterable: false,
    visible: true,
};

const COLUMN_NAME: any = {
    headerName: "Name",
    field: "name",
    sortable: false,
    filterable: false,
    visible: true,
    class: "ag-name",
    enableValue: false,
    enablePivot: false,
    enableRowGroup: false,
    rowDrag: false,
};

const COLUMN_GENDER: any = {
    headerName: "Gender",
    field: "gender",
    sortable: false,
    filterable: false,
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
};

const COLUMN_DOB: any = {
    headerName: "Date Of Birth",
    field: "dob",
    sortable: false,
    filterable: false,
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
    canEdit: true,
};

const COLUMN_NATIONALITY: any = {
    headerName: "Nationality",
    field: "nationality",
    sortable: false,
    filterable: false,
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
    canEdit: true,
};

const COLUMN_IDENTITY_TYPE: any = {
    headerName: "Identity Type",
    field: "identityType",
    sortable: false,
    filterable: false,
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
};

const COLUMN_IDENTITY_NUMBER: any = {
    headerName: "Identity Number",
    field: "identityNumber",
    sortable: false,
    filterable: false,
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
}

const COLUMN_ACCOUNT_TYPE: any = {
    headerName: "Account Type",
    field: "accountType",
    sortable: false,
    filterable: false,
    visible: true,
    enableValue: false,
    enablePivot: true,
    enableRowGroup: true,
}

export const TABLE_COLUMN_LIST: TableColumns = {
    id: COLUMN_ID,
    name: COLUMN_NAME,
    gender: COLUMN_GENDER,
    dob: COLUMN_DOB,
    nationality: COLUMN_NATIONALITY,
    identityType: COLUMN_IDENTITY_TYPE,
    accountType: COLUMN_ACCOUNT_TYPE,
    identityNumber: COLUMN_IDENTITY_NUMBER
};