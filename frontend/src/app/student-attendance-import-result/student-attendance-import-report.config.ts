interface TableColumns {
    rowNumber?: any;
    date?: any;
    student_attendance_type_code?: any;
    period?: any;
    institution_subject_name?: any;
    openEMIS_id	?: any;
    absence_type_code?: any;
    student_absence_reason_code?: any;
    comment?: any;
}

const textCheck: any = {
    "error-data-style": (params: any) => {
        if (params && params.value) {
            if(isNaN(params.value)){
                return (params.value.includes("Invalid") || params.value.includes("required") || params.value.includes("required."))
            }
        }
    }
}

const textCheckId: any = {
    "error-data-style": (params: any) => {
        typeof(params.value) != 'number'
    }
}

const COLUMN_ROWNUMBER: any = {
    headerName: "Row Number",
    field: "row_number",
    sortable: false,
    width: 90,
    filterable: false,
    class: "ag-school-column",
    canEdit: false
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

const COLUMN_STUDENT_ATTENDANCE_TYPE_CODE: any = {
    headerName: "Student Attendance Type Code",
    field: "student_attendance_type_code",
    sortable: false,
    filterable: false,
    width: 280,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheck,
}

const COLUMN_PERIOD: any = {
    headerName: "Period",
    field: "period",
    sortable: false,
    filterable: false,
    width: 150,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheck,
}

const COLUMN_INSTITUTION_SUBJECT_NAME: any = {
    headerName: "Institution Subject Name",
    field: "institution_subject_name",
    sortable: false,
    filterable: false,
    width: 270,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheck,
}

const COLUMN_OPENEMIS_ID: any = {
    headerName: "Openemis ID",
    field: "openemis_id",
    sortable: false,
    filterable: false,
    width: 200,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheckId
}

const COLUMN_ABSENCE_TYPE_CODE: any = {
    headerName: "Absence Type Code",
    field: "absence_type_code",
    sortable: false,
    filterable: false,
    width: 180,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheck
}

const COLUMN_STUDENT_ABSENCE_REASON_CODE: any = {
    headerName: "Student Absence Reason Code",
    field: "student_absence_reason_code",
    sortable: false,
    filterable: false,
    class: "ag-school-column",
    canEdit: false,
    cellClassRules: textCheckId
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
    student_attendance_type_code: COLUMN_STUDENT_ATTENDANCE_TYPE_CODE,
    period: COLUMN_PERIOD,
    institution_subject_name: COLUMN_INSTITUTION_SUBJECT_NAME,
    openEMIS_id: COLUMN_OPENEMIS_ID,
    absence_type_code: COLUMN_ABSENCE_TYPE_CODE,
    student_absence_reason_code: COLUMN_STUDENT_ABSENCE_REASON_CODE,
    comment: COLUMN_COMMENT
};

