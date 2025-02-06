interface TableColumns {
    rowNumber?: any;
    date?: any;
    openemis_id?: any;
    meal_programme_code?: any;
    meal_received_code?: any;
    meal_benefit_name?: any;
    comment?: any;
}

const COLUMN_ROWNUMBER: any = {
    headerName: "Row Number",
    field: "row_number",
    sortable: false,
    width: 100,
    filterable: false,
    class: "ag-school-column",
    canEdit: false
}

const textCheck: any = {
    "error-data-style": (params: any) => {
        console.log(params,"params");
        
        if (params && params.value) {
            return (params.value.includes("Invalid") || params.value.includes("required"))
        }
    }
}

const COLUMN_DATE: any = {
    headerName: "Date ( DD/MM/YYYY )",
    field: "date",
    sortable: false,
    filterable: false,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheck,
}

const textCheckId: any = {
    "error-data-style": (params: any) => {
        typeof(params.value) != 'number'
    }
}

const COLUMN_OPENEMISID: any = {
    headerName: "Openemis Id",
    field: "openemis_id",
    sortable: false,
    filterable: false,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheckId,
}

const COLUMN_MEAL_PROGRAMME_CODE: any = {
    headerName: "Meal Programme Code",
    field: "meal_programme_code",
    sortable: false,
    filterable: false,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheck,
}

const COLUMN_MEAL_RECEIVED_CODE: any = {
    headerName: "Meal Received Code",
    field: "meal_received_code",
    sortable: false,
    filterable: false,
    width: 240,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheck,
}

const COLUMN_MEAL_BENEFIT_NAME: any = {
    headerName: "Meal Benefit Name",
    field: "meal_benefit_name",
    sortable: false,
    filterable: false,
    width: 240,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: {
        'error-data-style': (params: any) => {
            return typeof params.value !== 'number';
        }
    }
}

const COLUMN_COMMENT: any = {
    headerName: "Comment",
    field: "comment",
    sortable: false,
    filterable: false,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheck
}

export const TABLE_COLUMN_LIST: TableColumns = {
    rowNumber: COLUMN_ROWNUMBER,
    date: COLUMN_DATE,
    openemis_id: COLUMN_OPENEMISID,
    meal_programme_code: COLUMN_MEAL_PROGRAMME_CODE,
    meal_received_code: COLUMN_MEAL_RECEIVED_CODE,
    meal_benefit_name: COLUMN_MEAL_BENEFIT_NAME,
    comment: COLUMN_COMMENT,
};

