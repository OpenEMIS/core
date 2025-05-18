import { ValueGetterParams } from "ag-grid-community";
import { IMiniDashboardConfig } from "openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface";

interface TableColumns {
  openEmisId?: any;
  personName?: any;
  student_attendance?: any;
  student_attendance_select?: any;
  student_attendance_select_new?: any;
  reasonOrComment?: any;
  reasonOrComment_select?: any;
  reasonOrComment_select_new?: any;
  monday?: any;
  tuesday?: any;
  wednesday?: any;
  thursday?: any;
  friday?: any;
}

const COLUMN_OPENEMISID: any = {
  headerName: "OpenEMIS ID",
  field: "user.openemis_no",
  sortable: true,
  filterable: true,
  filterValue: [],
  class: "ag-school-column",
  pinned: 'left',
  canEdit: false
}

const COLUMN_PERSONNAME: any = {
  headerName: "Name",
  field: "user.full_name",
  sortable: true,
  filterable: true,
  filterValue: [],
  class: "ag-school-column",
  canEdit: false,
  pinned: 'left',
  menuTabs: ['filterMenuTab']
}

const MONDAY: any = {
  headerName: "Monday",
  field: "monday",
  sortable: false,
  filterable: false,
  filterValue: [],
  children: [
    {
      headerName: '1',
      field: "M1",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
    {
      headerName: '2',
      field: "M2",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
  ],
  class: "ag-school-column",
  canEdit: false,
  pinned: 'left',
  menuTabs: ['filterMenuTab']
}

const TUESDAY: any = {
  headerName: "Tuesday",
  field: "tuesday",
  sortable: false,
  filterable: false,
  filterValue: [],
  children: [
    {
      headerName: '1',
      field: "T1",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
    {
      headerName: '2',
      field: "T2",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
  ],
  class: "ag-school-column",
  canEdit: false,
  pinned: 'left',
  menuTabs: ['filterMenuTab']
}

const WEDNESDAY: any = {
  headerName: "Wednesday",
  field: "wednesday",
  sortable: false,
  filterable: false,
  filterValue: [],
  children: [
    {
      headerName: '1',
      field: "W1",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
    {
      headerName: '2',
      field: "W2",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
  ],
  class: "ag-school-column",
  canEdit: false,
  pinned: 'left',
  menuTabs: ['filterMenuTab']
}

const THURSDAY: any = {
  headerName: "Thursday",
  field: "thursday",
  sortable: false,
  filterable: false,
  filterValue: [],
  children: [
    {
      headerName: '1',
      field: "TH1",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
    {
      headerName: '2',
      field: "TH2",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
  ],
  class: "ag-school-column",
  canEdit: false,
  pinned: 'left',
  menuTabs: ['filterMenuTab']
}

const FRIDAY: any = {
  headerName: "Friday",
  field: "friday",
  sortable: false,
  filterable: false,
  filterValue: [],
  children: [
    {
      headerName: '1',
      field: "F1",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
    {
      headerName: '2',
      field: "F2",
      sortable: false,
      filter: false,
      filterValue: [],
      width: 90,
      headerClass: 'center-header',
      canEdit: false,
      menuTabs: []
    },
  ],
  class: "ag-school-column",
  canEdit: false,
  pinned: 'left',
  menuTabs: ['filterMenuTab']
}

const COLUMN_STUDENTATTENDANCE: any = {
  headerName: "Attendance",
  field: "student_attendance",
  sortable: false,
  filterable: false,
  class: "ag-name",
  editable: false,
  valueGetter: (params: ValueGetterParams) => {
    // console.log(params.data)
  }
}

const COLUMN_REASONORCOMMENT: any = {
  headerName: "Reason / Comment",
  field: "reasonOrComment",
  sortable: false,
  filterable: false,
  filterValue: [
    "First Primary School",
    "Second Primary School",
    "Last Primary School",
  ],
  class: "ag-name",
  canEdit: true
}

const COLUMN_INPUT_ATTENDANCE_DROPDOWN: any = {
  headerName: "Attendance",
  field: "student_attendance_select",
  type: "input",
  config: {
    input: {
      controlType: "dropdown",
      key: "student_attendance_select",
      visible: true,
      options: [
        {
          key: "",
          value: "Present",
        },
        {
          key: 1,
          value: "Absence - Excused",
        },
        {
          key: 2,
          value: "Absence - Unexcused",
        },
        {
          key: 3,
          value: "Late",
        },
      ],
    },
  },
  event: true,
  valueGetter: (params: ValueGetterParams) => {
    console.log(params.data)
  }
}
const COLUMN_INPUT_ATTENDANCE_DROPDOWN_NEW: any = {
  headerName: 'Attendance',
  field: 'institution_student_absences.absence_type_id',
  suppressSorting: true,
  menuTabs: [],
  cellRenderer: (params) => {
    // console.log(params)
    if (params.hasOwnProperty('value')) {
      let context = params.context;
      let absenceTypeList = [
        {
          "id": 0,
          "name": "Present",
          "code": "PRESENT"
        },
        {
          "id": 1,
          "name": "Absence - Excused",
          "code": "EXCUSED"
        },
        {
          "id": 2,
          "name": "Absence - Unexcused",
          "code": "UNEXCUSED"
        },
        {
          "id": 3,
          "name": "Late",
          "code": "LATE"
        },
        {
          "id": 99,
          "name": "No Lessons",
          "code": "NOLESSONS"
        }
      ]
      let isMarked = params.context.isMarked ? params.context.isMarked : false;
      let isSchoolClosed = params.context.schoolClosed ? params.context.schoolClosed : true
      let mode = params.context.mode ? params.context.mode : 'view';
      // let mode = params.data.mode == 'view' ? 'view' : 'edit';
      let data = params.data;
      let noScheduledClicked = false

      // alert(`mode:${mode}`)

      if (mode == 'view') {
        return getViewAttendanceElement(data, absenceTypeList, true, isSchoolClosed, noScheduledClicked)
      } else if (mode == 'edit') {
        let api = params.api
        return getEditAttendanceElement(data, absenceTypeList, api, context)
      }
    }
  }
}

const COLUMN_INPUT_REASON_OR_COMMENT_NEW: any = {
  headerName: 'Reason / Comment',
  field: 'institution_student_absences.student_absence_reason_id',
  menuTabs: [],
  suppressSorting: true,
  cellRenderer: (params) => {
    if (params.hasOwnProperty('value')) {
      // console.log('reason params', params)
      let data = params.data;
      let context = params.context;
      let studentAbsenceReasonList = context.studentAbsenceReasons;
      let absenceTypeList = context.absenceTypes;
      let mode = context.mode;
      if (data.hasOwnProperty('institution_student_absences')) {
        let studentAbsenceTypeId = data.institution_student_absences.absence_type_id == null ? 0 : data.institution_student_absences.absence_type_id;
        let absenceTypeObj = absenceTypeList?.find(obj => obj.id == studentAbsenceTypeId);
        let html = '';
        if (mode == 'view') {
          switch (absenceTypeObj?.code) {
            case attendanceType.PRESENT.code:
              // return '<i class=">' + icons.PRESENT + '"></i>';
              return '<div><i class="' + icons.PRESENT + '"></i></div>';
            case attendanceType.LATE.code:
            case attendanceType.UNEXCUSED.code:
              html += getViewCommentsElement(data);
              return html;
            case attendanceType.EXCUSED.code:
              html += getViewAbsenceReasonElement(data, studentAbsenceReasonList);
              html += getViewCommentsElement(data);
              return html;

          }
        } else if (mode == 'edit') {
          let api = params.api
          switch (absenceTypeObj?.code) {
            case attendanceType.PRESENT.code:
              return '<div><i class="' + icons.PRESENT + '"></i></div>';
            case attendanceType.LATE.code:
            case attendanceType.UNEXCUSED.code:
              let eCell = document.createElement('div');
              eCell.setAttribute("class", "reason-wrapper");
              let eTextarea = getEditCommentElement(data, context, api);
              eCell.appendChild(eTextarea);
              return eCell;
            case attendanceType.EXCUSED.code:
              let sCell = document.createElement('div');
              sCell.setAttribute("class", "reason-wrapper");
              let eSelect = getEditAbsenceReasonElement(data, studentAbsenceReasonList, context, api);
              let eTextareaNew = getEditCommentElement(data, context, api);
              sCell.appendChild(eSelect);
              sCell.appendChild(eTextareaNew);
              return sCell;
            default:
              break;
          }
        }
      }
    }
  }
}

const COLUMN_INPUT_REASON_OR_COMMENT: any = {
  headerName: "Reason / Comment",
  field: "reasonOrComment_select",
  type: "input",
  config: {
    input: [{
      controlType: "dropdown",
      key: "reasonOrComment_select",
      visible: true,
      options: [
        {
          key: 1,
          value: "Illness",
        },
        {
          key: 2,
          value: "Emergency",
        },
        {
          key: 3,
          value: "Weather",
        },
        {
          key: 4,
          value: "Family matter",
        },
        {
          key: 5,
          value: "Death",
        }
      ],
    }, {
      controlType: "textarea",
      key: "inputTextarea",
      visible: true,
      placeholder: "Enter comment"
    }],

  },
  canEdit: true,
}

const attendanceType = {
  'NOTMARKED': {
    code: 'NOTMARKED',
    icon: 'fa fa-minus',
    color: '#999999'
  },
  'PRESENT': {
    code: 'PRESENT',
    icon: 'fa fa-check',
    color: '#77B576'
  },
  'LATE': {
    code: 'LATE',
    icon: 'fa fa-check-circle-o',
    color: '#999'
  },
  'UNEXCUSED': {
    code: 'UNEXCUSED',
    icon: 'fa fa-circle-o',
    color: '#CC5C5C'
  },
  'EXCUSED': {
    code: 'EXCUSED',
    icon: 'fa fa-circle-o',
    color: '#CC5C5C'
  },
  'NoScheduledClicked': {
    code: 'NoScheduledClicked',
    icon: '',
    color: 'black',
  },
  'NOLESSONS': {
    code: 'NOLESSONS',
    icon: '',
    color: 'black'
  }
};


export const TABLE_COLUMN_LIST: TableColumns = {
  openEmisId: COLUMN_OPENEMISID,
  personName: COLUMN_PERSONNAME,
  student_attendance: COLUMN_STUDENTATTENDANCE,
  student_attendance_select: COLUMN_INPUT_ATTENDANCE_DROPDOWN,
  student_attendance_select_new: COLUMN_INPUT_ATTENDANCE_DROPDOWN_NEW,
  reasonOrComment: COLUMN_REASONORCOMMENT,
  reasonOrComment_select: COLUMN_INPUT_REASON_OR_COMMENT,
  reasonOrComment_select_new: COLUMN_INPUT_REASON_OR_COMMENT_NEW,
  monday: MONDAY,
  tuesday: TUESDAY,
  wednesday: WEDNESDAY,
  thursday: THURSDAY,
  friday: FRIDAY
};

export const MINI_DASHBOARD_CONFIG: IMiniDashboardConfig = {
  closeButtonDisabled: false,
  // rtl: true,
};

function getViewAttendanceElement(data, absenceTypeList, isMarked, isSchoolClosed, noScheduledClicked) {
  if (data.institution_student_absences) {
    let html = '';
    if (isMarked) {
      let absenceTypeObj: any = {}
      let id = (data.institution_student_absences.absence_type_id == 0 || data.institution_student_absences.absence_type_id == null) ? 0 : data.institution_student_absences.absence_type_id;
      if (noScheduledClicked) {
        absenceTypeObj = {
          id: null,
          code: 'NoScheduledClicked',
          name: 'No Lessons'
        };
      } else {
        absenceTypeObj = absenceTypeList?.find(obj => obj.id == id);
      }
      switch (absenceTypeObj.code) {
        case attendanceType.PRESENT.code:
          html = '<div style="color: ' + attendanceType.PRESENT.color + ';"><i class="' + attendanceType.PRESENT.icon + '"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
          break;
        case attendanceType.LATE.code:
          html = '<div style="color: ' + attendanceType.LATE.color + ';"><i class="' + attendanceType.LATE.icon + '"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
          break;
        case attendanceType.UNEXCUSED.code:
          html = '<div style="color: ' + attendanceType.UNEXCUSED.color + '"><i class="' + attendanceType.UNEXCUSED.icon + '"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
          break;
        case attendanceType.EXCUSED.code:
          html = '<div style="color: ' + attendanceType.EXCUSED.color + '"><i class="' + attendanceType.EXCUSED.icon + '"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
          break;
        case attendanceType.NoScheduledClicked.code:
          html = '<div style="color: ' + attendanceType.NoScheduledClicked.color + '"> <span> ' + absenceTypeObj.name + ' </span></div>';
          break;
        case attendanceType.NOLESSONS.code:
          html = '<div style="color: ' + attendanceType.NoScheduledClicked.color + '"> <span> ' + absenceTypeObj.name + ' </span></div>';
          break;
        default:
          break;
      }
      return html
    } else {
      if (isSchoolClosed) {
        html = '<i style="color: #999999;" class="fa fa-minus"></i>';
      } else {
        if (data.is_NoClassScheduled == 1) {
          html = '<i style="color: #000000;"><span>No Lessons</span></i>';
        } else {
          // html = '<i class="' + icons.PRESENT + '"></i>';
        }
      }
    }
    return html
  }
}

function getEditAttendanceElement(data, absenceTypeList, api, context) {
  absenceTypeList.pop();
  let dataKey = 'absence_type_id';
  let eCell = document.createElement('div');
  eCell.setAttribute("class", "oe-select-wrapper input-select-wrapper");
  eCell.setAttribute("id", dataKey);

  if (data.institution_student_absences[dataKey] == null || data.institution_student_absences[dataKey] == 99) {
    data.institution_student_absences[dataKey] = 0;
  }

  let eSelect = document.createElement("select");
  absenceTypeList.forEach((obj, index) => {
    let eOption = document.createElement("option");
    let labelText = obj.name;
    eOption.setAttribute('value', obj.id);
    eOption.innerHTML = labelText;
    eSelect.appendChild(eOption);
  })

  eSelect.value = data.institution_student_absences[dataKey];
  eSelect.addEventListener('change', () => {
    setTimeout(() => {
      setRowDatas(context, data, api)
    }, 500);
    let oldValue = data.institution_student_absences[dataKey];
    let newValue = eSelect.value;
    let absenceTypeObj = absenceTypeList.find(obj => obj.id == newValue);

    if (newValue != oldValue) {
      let oldParams: any = {
        absence_type_id: oldValue
      }

      switch (absenceTypeObj.code) {
        case attendanceType.PRESENT.code:
          oldParams.student_absence_reason_id = data.institution_student_absences.student_absence_reason_id;
          oldParams.comment = data.institution_student_absences.comment;

          data.institution_student_absences.student_absence_reason_id = null;
          data.institution_student_absences.comment = null;
          data.institution_student_absences.absence_type_id = null;
          break;
        case attendanceType.LATE.code:
        case attendanceType.UNEXCUSED.code:
          oldParams.student_absence_reason_id = data.institution_student_absences.student_absence_reason_id;
          oldParams.comment = data.institution_student_absences.comment;

          data.institution_student_absences.student_absence_reason_id = null;
          data.institution_student_absences.comment = null;
          break;
        case attendanceType.EXCUSED.code:
          oldParams.comment = data.institution_student_absences.comment;

          data.institution_student_absences.comment = null;
          break;
      }
      oldValue = newValue;
      data.institution_student_absences.absence_type_id = newValue;
      data.institution_student_absences.absence_type_code = absenceTypeObj.code;

      let refreshParams = {
        columns: ['institution_student_absences.student_absence_reason_id'],
        force: true
      }
      api.refreshCells(refreshParams);
    }
  })
  eCell.appendChild(eSelect);
  return eCell
}

function getEditCommentElement(data, context, api) {
  let dataKey = 'comment';
  // let scope = context.scope;
  let eTextarea = document.createElement("textarea");
  eTextarea.style.width = '160px'; // Set the width as needed
  eTextarea.style.height = '80px'; // Set the height as needed
  eTextarea.style.resize = 'none';
  eTextarea.setAttribute("placeholder", "Comments");
  eTextarea.setAttribute("id", dataKey);

  eTextarea.value = data.institution_student_absences[dataKey];
  eTextarea.addEventListener('blur', () => {
    let oldValue = data.institution_student_absences.comment;
    data.institution_student_absences[dataKey] = eTextarea.value;
  })

  return eTextarea
}

function getViewCommentsElement(data) {
  let comment = data.institution_student_absences.comment;
  let html = '';
  if (comment != null) {
    html = '<div class="absences-comment"><i class="' + icons.COMMENT + '"></i><span style="margin-left: 8px;">' + comment + '</span></div>';
  }
  return html
}

function getViewAbsenceReasonElement(data, studentAbsenceReasonList) {
  let absenceReasonId = data.institution_student_absences.student_absence_reason_id;
  let absenceReasonObj = studentAbsenceReasonList.find(obj => obj.id == absenceReasonId);
  let html = '';

  if (absenceReasonId === null) {
    html = '<div><i class="' + icons.PRESENT + '"></i></div>';
  } else {
    html = '<div class="absence-reason"><i class="' + icons.REASON + '"></i><span style="margin-left: 8px;">' + absenceReasonObj.name + '</span></div>';
  }

  return html
}

function setRowDatas(context, data, api) {
  let studentList = context.scope.data

  studentList.forEach((dataItem, index) => {
    if (dataItem.institution_student_absences.absence_type_code == null || dataItem.institution_student_absences.absence_type_code == "PRESENT") {
      dataItem.rowHeight = 60;
    } else {
      dataItem.rowHeight = 120;
    }
  })
  // api.setRowData(studentList)
}

function getEditAbsenceReasonElement(data, studentAbsenceReasonList, context, api) {
  let dataKey = 'student_absence_reason_id';
  // let scope = context.scope;
  let eSelectWrapper = document.createElement('div');
  eSelectWrapper.setAttribute("class", "oe-select-wrapper input-select-wrapper");
  eSelectWrapper.setAttribute("id", dataKey);
  let eSelect = document.createElement("select");
  if (data.institution_student_absences[dataKey] == null) {
    data.institution_student_absences[dataKey] = studentAbsenceReasonList[0].id;
  }

  studentAbsenceReasonList.forEach((obj, key) => {
    let eOption = document.createElement("option");
    let labelText = obj.name;
    eOption.setAttribute("value", obj.id);
    eOption.innerHTML = labelText;
    eSelect.appendChild(eOption);
  })

  eSelect.value = data.institution_student_absences[dataKey];
  eSelect.addEventListener('change', () => {
    let oldValue = data.institution_student_absences[dataKey];
    data.institution_student_absences[dataKey] = eSelect.value;
  })
  eSelectWrapper.appendChild(eSelect);
  return eSelectWrapper;
}

const icons = {
  'REASON': 'kd kd-reason',
  'COMMENT': 'kd kd-comment',
  'PRESENT': 'fa fa-minus',
};
