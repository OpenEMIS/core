interface TableColumns {
    staffOpenEmisId?: any;
    staffName?: any;
    inAndOutTime?: any;
    leave?: any;
    comment?: any;
}

const COLUMN_STAFF_OPENEMISID: any = {
    headerName: "OpenEMIS ID",
    field: "_matchingData.User.openemis_no",
    sortable: true,
    filterable: false,
    class: "ag-school-column",
    pinned: 'left'
}

const COLUMN_STAFFNAME: any = {
    headerName: "Name",
    field: "_matchingData.User.full_name",
    sortable: true,
    filterable: true,
    pinned: 'left',
    filterValue: [
        "First Primary School",
        "Second Primary School",
        "Last Primary School",
    ],
    class: "ag-school-column",
    canEdit: false,
    menuTabs: ['filterMenuTab']
}

const COLUMN_INANDOUTTIME: any = {
    headerName: "Time In - Time Out",
    field: 'attendance.2023-12-11',
    menuTabs: [],
    suppressSorting: true,
    cellRenderer: (params) => {
        if (params.hasOwnProperty('value')) {
            return getSingleDayTimeInTimeOutElement(params)
        }
    }
}

const COLUMN_STAFF_LEAVE: any = {
    headerName: 'Leave',
    field: 'attendance.2023-12-11',
    menuTabs: [],
    suppressSorting: true,
    cellRenderer: (params) => {
        if (params.hasOwnProperty('value')) {
            return getStaffLeaveElement(params)
        }
    }
}

const COLUMN_STAFF_COMMENT: any = {
    headerName: "Comments",
    field: 'attendance.2023-12-11.comment',
    menuTabs: [],
    suppressSorting: true,
    cellRenderer: (params) => {
        if (params.hasOwnProperty('value')) {
            return getCommentElement(params);
        }
    }
}

export const TABLE_COLUMN_LIST: TableColumns = {
    staffOpenEmisId: COLUMN_STAFF_OPENEMISID,
    staffName: COLUMN_STAFFNAME,
    inAndOutTime: COLUMN_INANDOUTTIME,
    leave: COLUMN_STAFF_LEAVE,
    comment: COLUMN_STAFF_COMMENT,
};

function getSingleDayTimeInTimeOutElement(params) {
    let action = params?.context?.action;
    let timeIn = params?.value?.time_in;
    let timeOut = params?.value?.time_out;
    let data = params.data;
    let date = params?.data?.date;

    let staff_id = params.data.staff_id;
    let rowIndex = params.rowIndex;
    let timeinPickerId = 'time-in-' + rowIndex;
    let timeoutPickerId = 'time-out-' + rowIndex;
    let time = '';
    let historyUrl = data.historyUrl;

    let ownEdit = params.context.ownEdit;
    let otherEdit = params.context.otherEdit;
    let permissionStaffId = params.context.permissionStaffId;
    let staffId = params.data.staff_id;

    if(timeIn == null){
        timeIn = params?.data?.attendance[date].time_in;
    }
    if(timeOut == null){
        timeOut = params?.data?.attendance[date].time_out;
    }
    let conditionStatus = 0
    if (ownEdit == 0 && otherEdit == 1 && permissionStaffId != staffId) {
        conditionStatus = 1;
    } else if (ownEdit == 1 && otherEdit == 0 && permissionStaffId == staffId) {
        conditionStatus = 1;
    } else if (ownEdit == 1 && otherEdit == 1) {
        conditionStatus = 1;
    }

    if (action == 'edit' && conditionStatus == 1) {
        let divElement = document.createElement('div');
        let timeInInputDivElement = createTimeElement(params, 'time_in', rowIndex);
        let timeOutInputDivElement = createTimeElement(params, 'time_out', rowIndex);
        divElement.appendChild(timeInInputDivElement);
        let breakLineElement = document.createElement('br')
        divElement.appendChild(breakLineElement);
        divElement.appendChild(timeOutInputDivElement);
        return divElement;
    } else {
        if (timeIn) {
            time = '<div class="time-view"><span style="color: #77B576;"><i class="fa fa-external-link-square" style="margin-right: 8px;"></i>' + convert12Timeformat(timeIn) + '</span></div>'
            if (timeOut) {
                time += '<div class="time-view"><span style="color: #77B576;"><i class="fa fa-external-link" style="margin-right: 8px;"></i>' + convert12Timeformat(timeOut) + '</span></div>'
            } else {
                time += '<div class="time-view"><span style="color: #77B576;"><i class="fa fa-external-link"></i></span></div>'
            }
        } else {
            time = '<div><i class="fa fa-minus"></i></div>'
        }
        return time
    }
}

function convert12Timeformat(time) {
    if(time == '00:00:00'){
        return '-'
    }
    var timeSplit = time.split(":");
    let hours = timeSplit[0];
    let minutes = timeSplit[1];
    let meridian = ''
    if (hours >= 12) {
        meridian = "PM";
    } else {
        meridian = "AM";
    }
    //00 does not exists in 12-hour time format hence need to convert 00 back to 12,
    //else timepicker will display wrong timing when error when user selects 12AM
    hours = (hours % 12) || 12;
    var sHours = hours.toString();
    if (sHours.length == 1) {
        sHours = "0" + sHours;
    }
    var sMinutes = minutes.toString();
    return sHours + ":" + sMinutes + " " + meridian;
}

function getStaffLeaveElement(params) {
    let staffLeaves = params?.value?.leave;
    let url = params?.value?.url;
    let data = '';

    if (staffLeaves?.length > 0) {

    } else {
        data = '<div><i class="fa fa-minus"></i></div>'
    }
    return data
}

function getCommentElement(params) {
    let action = params.context.action;
    let divElement: any;
    let ownEdit = params.context.ownEdit;
    let otherEdit = params.context.otherEdit;
    let permissionStaffId = params.context.permissionStaffId;
    let staffId = params.data.staff_id;
    let conditionStatus = 0;

    if (ownEdit == 0 && otherEdit == 1 && permissionStaffId != staffId) {
        conditionStatus = 1;
    } else if (ownEdit == 1 && otherEdit == 0 && permissionStaffId == staffId) {
        conditionStatus = 1;
    } else if (ownEdit == 1 && otherEdit == 1) {
        conditionStatus = 1;
    }

    if (action == 'edit' && conditionStatus == 1) {
        divElement = getEditCommentElementStaff(params);
    } else {
        divElement = getViewCommentElementStaff(params);
    }
    return divElement;
}

function getViewCommentElementStaff(params) {
    let date = params?.data?.date;
    let comment = params.data.attendance[date]?.comment
    let html = '<div><i class="fa fa-minus"></i></div>'
    if (comment != null) {
        html = `<div class="comment-wrapper"><i class="fa kd-comment comment-flow"></i><span class="comment-text" style="margin-left: 8px;">` + comment + `</span></div>`;
    }
    return html
}

function getEditCommentElementStaff(params) {
    let dataKey = 'comment';
    let date = params.data.date;
    let data = params.data;
    let academicPeriodId = params.context.period;
    let eTextarea = document.createElement("textarea");
    eTextarea.setAttribute("id", dataKey);
    eTextarea.setAttribute('style', 'height: 100%; width:100%;');

    eTextarea.value = data.attendance[date].comment === null ? '' : data.attendance[date].comment;
    eTextarea.addEventListener('blur', function () {
        let oldValue = data.attendance[date].comment;
        let newValue = eTextarea.value;
        if (oldValue != newValue) {
            data.attendance[date].comment = newValue;
        }
    })
    // TODO
    // let refreshParams = {
    //     columns: [
    //         'comment'
    //     ],
    //     force: true
    // };
    // params.api.refreshCells(refreshParams);
    return eTextarea
}

function createTimeElement(params, timeKey, rowIndex) {
    let action = params.context.action;
    let data = params.data;
    let academicPeriodId = params.context.period;
    let timepickerId = (timeKey == 'time_in') ? 'time-in-' : 'time-out-';
    timepickerId += rowIndex;
    let time = '';
    
    let dateStr = params?.data?.date //added by my to autofill time
    // if (params.value[timeKey] != null && params.value[timeKey] != "") {
    //     // time = convert12Timeformat(params.value[timeKey]);
    // }
    let scope = params.context.scope;
    let leave = data.attendance[data.date].leave;
    // let isDisabled = (leave && leave.length > 0 && leave[0].isFullDay === 1);
    let isDisabled = false;

    let timeInputDivElement = document.createElement('div');
    if (!isDisabled) timeInputDivElement.setAttribute('id', timepickerId);

    timeInputDivElement.setAttribute('class', 'input-group time timepicker');

    let timeInputElement = document.createElement('input');
    timeInputElement.setAttribute('class', 'form-control timPikr');
    timeInputElement.setAttribute('type', 'time');
    timeInputElement.setAttribute('style', 'width: 100px;')

    if (isDisabled) timeInputElement.setAttribute('disabled', 'true');
    timeInputElement.setAttribute('readonly', 'readonly');
    if (data.attendance[dateStr][timeKey] && data.attendance[dateStr][timeKey].length > 0) {
        timeInputElement.setAttribute('value', data.attendance[dateStr][timeKey].toString())
    }
    let timeSpanElement = document.createElement('span');
    timeSpanElement.setAttribute('class', (isDisabled) ? 'input-group-addon disabled' : 'input-group-addon');
    timeSpanElement.setAttribute('style', 'background-color: #689ccc; width: 10px');
    // timeSpanElement.setAttribute('class', 'btn btn-primary');

    let timeIconElement = document.createElement('i');
    timeIconElement.setAttribute('class', 'glyphicon glyphicon-time');

    timeInputElement.addEventListener('click', () => {
        timeInputElement.removeAttribute('readonly');
        //  $('.timepicker').each(()=>{
        //     let element = $(this);
        //     if (element.attr('id') != timepickerId) {
        //         // console.log("I am here inside")
        //     }
        //  })
    })

    timeInputElement.addEventListener('blur', () => {
        if (timeInputElement.value.length > 0) {
            saveStaffAttendance(params, timeKey, timeInputElement.value, academicPeriodId);
        } else {
            saveStaffAttendance(params, timeKey, null, academicPeriodId);
        }
        timeInputElement.setAttribute('readonly', 'readonly');
    })

    timeSpanElement.appendChild(timeIconElement);
    timeInputDivElement.appendChild(timeInputElement);
    timeInputDivElement.appendChild(timeSpanElement);
    return timeInputDivElement;

}

function saveStaffAttendance(params, timeKey, enteredTime, academicPeriodId) {
    let dateString = params.data.date;
    if (!params.data.attendance[dateString].isNew) {
        params.data.attendance[dateString][timeKey] = enteredTime
    } else {
        params.data.attendance[dateString][timeKey] = enteredTime
    }
}

export interface IMiniDashboardConfig {
    closeButtonDisabled?: boolean;
    rtl?: boolean;
}

export const MINI_DASHBOARD_CONFIG: IMiniDashboardConfig = {
    closeButtonDisabled: false,
    rtl: true,
};
