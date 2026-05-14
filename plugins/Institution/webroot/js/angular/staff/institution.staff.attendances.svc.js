angular
    .module('institution.staff.attendances.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStaffAttendancesSvc', InstitutionStaffAttendancesSvc);

InstitutionStaffAttendancesSvc.$inject = ['$http', '$q', '$filter', '$timeout', 'KdDataSvc', 'AlertSvc', 'UtilsSvc']; //POCOR-9700

function InstitutionStaffAttendancesSvc($http, $q, $filter, $timeout, KdDataSvc, AlertSvc, UtilsSvc) { //POCOR-9700
   var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionStaffAttendances: 'Staff.InstitutionStaffAttendances',
        Staff: 'Institution.Staff',
        StaffAttendances: 'Institution.StaffAttendances',
        InstitutionShiftsTable:'Institution.InstitutionShifts',
        InstitutionShifts: 'Institution.InstitutionShifts',
       ConfigItems: 'Configuration.ConfigItems',
    };

    var translateText = {
        'original': {
            'openemis_no': 'OpenEMIS ID',
            'Name': 'Name',
            'Attendance': 'Attendance',
            'TimeIn': 'Time In',
            'TimeOut': 'Time Out',
            'Leave': 'Leave',
            'Comments': 'Comments',
        },
        'translated': {
        }
    };

    var errorElms = {};

    var service = {
        init: init,
        translate: translate,
        getTranslatedText: getTranslatedText,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getDayListOptions:getDayListOptions,
        getAllStaffAttendances: getAllStaffAttendances,
        getColumnDefs: getColumnDefs,
        getAllDayColumnDefs: getAllDayColumnDefs,
        getShiftListOptions: getShiftListOptions,
        getConfigItemValue: getConfigItemValue,
    };
    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('InstitutionStaffAttendances');
        KdDataSvc.init(models);
    }

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success: success, defer: true});
    }

    // data service
    function getTranslatedText() {
        var success = function(response, deferred) {
            var translatedObj = response.data;
            if (angular.isDefined(translatedObj)) {
                translateText = translatedObj;
            }
            deferred.resolve(angular.isDefined(translatedObj));
        };

        KdDataSvc.init({translation: 'translate'});
        return translation.translate(translateText.original, {
            success: success,
            defer: true
        });
    }

    function getAcademicPeriodOptions(institutionId) {
        var success = function(response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('There was an error when retrieving the academic periods');
            }
        };
        return AcademicPeriods
            .find('SchoolAcademicPeriod')
            .ajax({success: success, defer: true});
    }

    function getWeekListOptions(academicPeriodId) {
        var success = function(response, deferred) {
            var academicPeriodObj = response.data.data;
            if (angular.isDefined(academicPeriodObj) && academicPeriodObj.length > 0) {
                var weeks = academicPeriodObj[0].weeks;
                if (angular.isDefined(weeks) && weeks.length > 0) {
                    deferred.resolve(weeks);
                } else {
                    deferred.reject('There was an error when retrieving the week list');
                }
            } else {
                deferred.reject('There was an error when retrieving the week list');
            }
        };
        return AcademicPeriods
            .find('WeeksForPeriod', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getDayListOptions(academicPeriodId, weekId, institutionId) {
        var success = function(response, deferred) {
            var dayList = response.data.data;
            if (angular.isObject(dayList) && dayList.length > 0) {
                deferred.resolve(dayList);
            } else {
                deferred.reject('There was an error when retrieving the day list');
            }
        };
        return AcademicPeriods
            .find('daysForPeriodWeek', {
                academic_period_id: academicPeriodId,
                week_id: weekId,
                institution_id: institutionId,
                school_closed_required: true
            })
            .ajax({success: success, defer: true});
    }

    function getShiftListOptions(academicPeriodId, weekId, institutionId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionShifts.find('StaffShiftOptions',
        {institution_id: institutionId,
            academic_period_id: academicPeriodId})
                .ajax({success: success, defer: true});
        }

    function getAllStaffAttendances(params) {
        var extra = {
            institution_id: params.institution_id,
            academic_period_id: params.academic_period_id,
            week_id: params.week_id,
            week_start_day: params.week_start_day,
            week_end_day: params.week_end_day,
            day_id: params.day_id,
            shift_id: params.shift_id,
            day_date: params.day_date,
			own_attendance_view: params.own_attendance_view,
            own_attendance_edit: params.own_attendance_edit,
            other_attendance_view: params.other_attendance_view,
            other_attendance_edit: params.other_attendance_edit,
        };
        var success = function(response, deferred) {
            var allStaffAttendances = response.data.data;
            if (angular.isObject(allStaffAttendances)) {
                deferred.resolve(allStaffAttendances);
            } else {
                deferred.reject('There was an error when retrieving the staff attendances');
            }
        };
        return Staff
            .find('AllStaffAttendances', extra)
            .ajax({success: success, defer: true});
    }

    // column definitions
    function getColumnDefs(selectedDayDate) {
        // console.log("selectedDayDate");
        // console.log(selectedDayDate);
        var columnDefs = [];
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }

        columnDefs.push({
            headerName: 'OpenEMIS ID',
            field: "_matchingData.Users.openemis_no",
            pinned: direction,
            menuTabs: []
        });

        columnDefs.push({
            headerName: 'Name',
            field: "_matchingData.Users.name",
            filter: "text",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs
        });

        columnDefs.push({
            headerName: 'Time In' + " - " + 'Time Out',
            field: "attendance." + selectedDayDate,
            menuTabs: [],
            suppressSorting: true,
            cellRenderer: function(params) {
                if (angular.isDefined(params.value) && params.value !== null && angular.isDefined(params.context.action)) {
                    return getSingleDayTimeInTimeOutElement(params);
                }
            }
        });

        columnDefs.push({
            headerName: 'Leave',
            field: "attendance." + selectedDayDate,
            menuTabs: [],
            suppressSorting: true,
            cellRenderer: function(params) {
                if (angular.isDefined(params.value) &&params.value != null && angular.isDefined(params.context.action)) {
                    return getStaffLeaveElement(params.value);
                }
            }
        });

        columnDefs.push({
            headerName: 'Comments',
            field: "attendance." + selectedDayDate + ".comment",
            menuTabs: [],
            suppressSorting: true,
            cellClass: 'comment-flex',
            cellRenderer: function(params) {
                return getCommentElement(params);
            }
        });
        return columnDefs;
    }

    function getAllDayColumnDefs(dayList) {
        var columnDefs = [];
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }

        columnDefs.push({
            headerName: 'OpenEMIS ID',
            field: "_matchingData.Users.openemis_no",
            pinned: direction,
            menuTabs: []
        });

        columnDefs.push({
            headerName: 'Name',
            field: "_matchingData.Users.name",
            filter: "text",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs
        });

        angular.forEach(dayList, function(dayObj, dayKey) {
            if (dayObj.id != -1) {
                var dayText = dayObj.name;
                var colDef = {
                    headerName: dayText,
                    menuTabs: [],
                    field: 'attendance.' + dayObj.date,
                    suppressSorting: true,
                    cellRenderer: function(params) {
                        if (angular.isDefined(params.value) && params.value !== null) {
                            return getAllDayTimeInTimeOutElement(params.value);
                        } else {
                            return '<i class="fa fa-minus"></i>';
                        }
                    }
                };
                columnDefs.push(colDef);
            }
        });
        return columnDefs;
    }

    function getStaffLeaveElement(params) {
        var staffLeaves = params.leave;
        var url = params.url;
        var data = '';
        if (staffLeaves.length > 0) {
            data = '<div class="comment-text">';
            angular.forEach(staffLeaves, function(staffLeave) {
                var start_time = staffLeave.startTime;
                var end_time = staffLeave.endTime;
                var full_day = staffLeave.isFullDay;
                var leaveTypeName = staffLeave.staffLeaveTypeName;
                data += '<div class = "time-view"><font color="#CC5C5C"><i class="fa fa-calendar-check-o"></i> '+leaveTypeName + '</font></div>';
                if (!full_day){
                    data += '<div class = "time-view"><font color="#CC5C5C">' + convert12Timeformat(start_time) + ' - ' + convert12Timeformat(end_time) + '</font></div>';
                }
            });
            data += '<div class = "time-view"><i class="fa fa-file-text-o" style="color: #72C6ED;"></i><a href="'+url+ '"target="_blank">View Details</a></div>';
        } else {
            data = '<i class="fa fa-minus"></i>';
        }
        return data;
    }

    function getSingleDayTimeInTimeOutElement(params) {
        var action = params.context.action;
        var academicPeriodId = params.context.period;
        var scope = params.context.scope;
        var timeIn = params.value.time_in;
        var timeOut = params.value.time_out;
        var data = params.data;

        var staffId = params.data.staff_id;
        var rowIndex = params.rowIndex;
        var timeinPickerId = 'time-in-' + rowIndex;
        var timeoutPickerId = 'time-out-' + rowIndex;
        var time = '';
        var historyUrl = data.historyUrl;
        var successInstitutionShifts = function(response, deferred) {
          //POCOR-5885  Edit: Time in reverted to default time
        //    if(response.data.data.length > 0){

        //       params.value.time_in = response.data.data[0].startTime;
        //    }

            deferred.resolve(response.data.data);
        };

        /*var shiftsAttendance =  Staff.find('StaffShiftsAttendance', // comment in POCOR-7180
         {staff_id: staffId})
                .ajax({success: successInstitutionShifts, defer: true});*/

		var ownEdit = params.context.ownEdit;
        var otherEdit = params.context.otherEdit;
        var permissionStaffId = params.context.permissionStaffId;
        var staffId = params.data.staff_id;

        var conditionStatus = 0
        if(ownEdit == 0 && otherEdit == 1 && permissionStaffId != staffId){
            conditionStatus = 1;
        }else if(ownEdit == 1 && otherEdit == 0 && permissionStaffId == staffId){
            conditionStatus = 1;
        }else if(ownEdit == 1 && otherEdit == 1){
            conditionStatus = 1;
        }

        if (action == 'edit' && conditionStatus == 1) {
            var divElement = document.createElement('div');
            var timeInInputDivElement = createTimeElement(params, 'time_in', rowIndex);
            var timeOutInputDivElement = createTimeElement(params, 'time_out', rowIndex);
            divElement.appendChild(timeInInputDivElement);
            divElement.appendChild(timeOutInputDivElement);
            return divElement;
        } else {
            // always clear error data here.
            clearError(data, 'time_out');
            clearError(data, 'time_in');
            if (timeIn) {
                time = '<div class="time-view"><font color="#77B576"><i class="fa fa-external-link-square"></i> ' + convert12Timeformat(timeIn) + '</font></div>';
                if (timeOut) {
                    time += '<div class="time-view"><font color="#77B576"><i class=" fa fa-external-link"></i> ' + convert12Timeformat(timeOut) + '</font></div>';
                } else {
                    time += '<div class="time-view"><font color="#77B576"><i class="fa fa-external-link"></i></font></div>';
                }
                if (params.context.history) {
                    time += '<div class="time-view"><i class="fa fa-file-text-o" style="color: #72C6ED;"></i><a href= "'+ historyUrl + '"target="_blank">View History Log </a></div>';
                }
            } else {
                time = '<i class="fa fa-minus"></i>';
            }
            return time;
        }
    }

    function getAllDayTimeInTimeOutElement(params) {
        // console.log(params);
        var timeIn = params.time_in;
        // console.log(timeIn);
        var timeOut = params.time_out;
         // console.log(timeOut);
        var time = '';
        if (timeIn && timeOut){
            time = '<div class="time-view"><font color= "#77B576"><i class="fa fa-external-link-square"></i> '+ convert12Timeformat(timeIn) + '</div><div class="time-view"><i class="fa fa-external-link"></i> ' + convert12Timeformat(timeOut) +  '</font></div>';
        } else if (timeIn && !timeOut) {
            time = '<div class="time-view"><font color= "#77B576"><i class="fa fa-external-link-square"></i> '+ convert12Timeformat(timeIn) + '</font></div>';
        }
        if (angular.isDefined(params.leave) && params.leave.length != 0) {
            angular.forEach(params.leave, function(leave) {
                time += '<div class="time-view"><font color="#CC5C5C"><i class="fa fa-calendar-check-o"></i> '+ leave.staffLeaveTypeName +'</font></div>';
                if (leave.isFullDay) {
                    time += '<div class="time-view"><font color="#CC5C5C">(Full Day)</font></div>';
                } else if (leave.startTime && leave.endTime) {
                    time += '<div class="time-view"><font color="#CC5C5C">'+ convert12Timeformat(leave.startTime) + ' - '+ convert12Timeformat(leave.endTime)+'</font></div>';
                }
            });
            time = '<div class="comment-text">'+ time +'</div>';
        }
        if (time == '') {
            time = '<i class="fa fa-minus"></i>';
        }
        return time;
    }

    function getCommentElement(params) {
        var action = params.context.action;
        var divElement = '';
		var ownEdit = params.context.ownEdit;
        var otherEdit = params.context.otherEdit;
        var permissionStaffId = params.context.permissionStaffId;
        var staffId = params.data.staff_id;
        var conditionStatus = 0
        if(ownEdit == 0 && otherEdit == 1 && permissionStaffId != staffId){
            conditionStatus = 1;
        }else if(ownEdit == 1 && otherEdit == 0 && permissionStaffId == staffId){
            conditionStatus = 1;
        }else if(ownEdit == 1 && otherEdit == 1){
            conditionStatus = 1;
        }
        if (action == 'edit' && conditionStatus == 1) {
            divElement = getEditCommentElement(params);
        } else {
            divElement = getViewCommentElement(params.value);
        }
        return divElement;
    }

    function getViewCommentElement(data) {
        var comment = data;
        var html = '<i class="fa fa-minus"></i>';
        if (comment != null) {
            html = `<div class="comment-wrapper"><i class="fa kd-comment comment-flow"></i><div class="comment-text">` + comment + `</div></div>`;
        }
        return html;
    }

    function getEditCommentElement(params) {
        var dataKey = 'comment';
        var scope = params.context.scope;
        var value = params.value;
        var date = params.data.date;
        var data = params.data;
        var academicPeriodId = params.context.period;
        var eTextarea = document.createElement("textarea");
        eTextarea.setAttribute("id", dataKey);
        eTextarea.setAttribute('style','height: 100%; width:100%;');

        eTextarea.value = params.value;
        eTextarea.addEventListener('blur', function () {
            var oldValue = params.value;
            var newValue = eTextarea.value;
            if (oldValue != newValue) {
                UtilsSvc.isAppendSpinner(true, 'institution-staff-attendances-table');
                if (params.data.attendance[date].comment == null) {
                    params.data.attendance[date].isNew = true;
                }
                saveStaffAttendance(params, dataKey, newValue, academicPeriodId)
                .then(
                    function(response) {
                        UtilsSvc.isAppendSpinner(false, 'institution-staff-attendances-table');
                        if(response.data.error.length == 0){
                            AlertSvc.success(scope, 'Comment successfully saved.');
                            params.data.attendance[date].comment = newValue;
                            params.data.attendance[date].isNew = false;
                        } else {
                            AlertSvc.error(scope, 'There was an error when saving the record');
                        }
                    },
                    function(error) {
                        UtilsSvc.isAppendSpinner(false, 'institution-staff-attendances-table');
                        AlertSvc.error(scope, 'There was an error when saving the record');
                    }
                )
                .finally(function() {
                    var refreshParams = {
                        columns: [
                            'comment'
                        ],
                        force: true
                    };
                    params.api.refreshCells(refreshParams);
                });
            }
        });
        return eTextarea;
    }

    //POCOR-9700: native HTML5 <input type="time"> replaces Bootstrap 3 jQuery timepicker.
    // Removes focus-loss + lack of OS 12/24h respect + fragile hide.timepicker save event.
    // Race fix: per-row save promise chain via _savePromise; recheck-from-DB via server response.
    function createTimeElement(params, timeKey, rowIndex) {
        var data = params.data;
        var dateString = data.date;
        var academicPeriodId = params.context.period;
        var scope = params.context.scope;
        var timepickerId = (timeKey === 'time_in' ? 'time-in-' : 'time-out-') + rowIndex;
        var leave = data.attendance[dateString].leave;
        var isDisabled = (leave && leave.length > 0 && leave[0].isFullDay === 1);
        var isTimeOutField = (timeKey === 'time_out');

        var wrapperDiv = document.createElement('div');
        wrapperDiv.setAttribute('class', 'input-group time html5-timepicker' + (isDisabled ? ' disabled' : ''));

        var inputElement = document.createElement('input');
        inputElement.setAttribute('type', 'time');
        inputElement.setAttribute('step', '60');
        inputElement.setAttribute('id', timepickerId); //POCOR-9700: id moved to input so error/test selectors work natively
        inputElement.setAttribute('class', 'form-control timPikr');

        //POCOR-9700: lock the edit picker to 24h (en-GB). Demonstrated failure mode of en-US:
        // Chrome's HTML5 picker is too narrow inside the ag-Grid cell to render the AM/PM
        // marker — typing "15:00" silently converts to "03:00" with no visible PM indicator.
        // Ambiguous and unsafe. View mode still respects time_format config (AM/PM as text
        // has room there). Same call as the previous always-24h lock — keep this stable.
        inputElement.lang = 'en-GB';
        if (isDisabled) {
            inputElement.setAttribute('disabled', 'disabled');
        }
        var existing = params.value[timeKey];
        if (existing) {
            inputElement.value = existing.substring(0, 5); // HH:MM
        }
        if (hasError(data, timeKey, timepickerId)) {
            inputElement.className += ' form-error';
        }

        //POCOR-9700: no glyphicon-time button — HTML5 type=time renders its own clock indicator
        // natively (inside the input). The legacy .input-group-addon span was the jQuery
        // timepicker launcher; with the native picker it became a non-functional second clock.

        function hasTimeInSelected() {
            return params.value.time_in !== null && params.value.time_in !== undefined && params.value.time_in !== '';
        }

        function revertInput() {
            inputElement.value = params.value[timeKey] ? params.value[timeKey].substring(0, 5) : '';
        }

        function toMinutes(hms) {
            var p = hms.split(':');
            return parseInt(p[0], 10) * 60 + parseInt(p[1], 10);
        }

        function detectOutsideShiftWarning(time24Hour) {
            //POCOR-9700: returns the warning text (or null), so the save callback can fold it into a
            // single combined toast rather than letting the success message overwrite the warning.
            var shiftStart = params.context.shiftStartTime;
            var shiftEnd = params.context.shiftEndTime;
            if (!shiftStart || !shiftEnd || !time24Hour) return null;
            var GRACE_MIN = 180; // 3 hours: schools have early prep + late grading
            var tMin = toMinutes(time24Hour);
            if (timeKey === 'time_in' && tMin < toMinutes(shiftStart) - GRACE_MIN) {
                return 'Time In is more than 3 hours before the shift starts — please verify.';
            }
            if (timeKey === 'time_out' && tMin > toMinutes(shiftEnd) + GRACE_MIN) {
                return 'Time Out is more than 3 hours after the shift ends — please verify.';
            }
            return null;
        }

        inputElement.addEventListener('change', function () {
            if (isDisabled) return;
            if (isTimeOutField && !hasTimeInSelected()) {
                AlertSvc.warning(scope, 'Please select Time In first.');
                inputElement.value = '';
                return;
            }

            var rawValue = inputElement.value; // "HH:MM" or ""
            var time24Hour = rawValue ? (rawValue + ':00') : null;

            // Order validation: strict > between time_in and time_out
            var otherTime = (timeKey === 'time_out') ? params.value.time_in : params.value.time_out;
            if (time24Hour !== null && otherTime) {
                if (timeKey === 'time_out' && time24Hour <= otherTime) {
                    AlertSvc.error(scope, 'Time Out must be after Time In.');
                    revertInput();
                    setError(data, timeKey, true, {id: timepickerId, elm: inputElement});
                    return;
                }
                if (timeKey === 'time_in' && time24Hour >= otherTime) {
                    AlertSvc.error(scope, 'Time In must be before Time Out.');
                    revertInput();
                    setError(data, timeKey, true, {id: timepickerId, elm: inputElement});
                    return;
                }
            }

            var outsideShiftWarning = detectOutsideShiftWarning(time24Hour);

            UtilsSvc.isAppendSpinner(true, 'institution-staff-attendances-table');
            if (params.value[timeKey] == null) {
                params.value.isNew = true;
            }

            // POCOR-9700: write the typed value into row state immediately so a debounced save sees the latest.
            params.value[timeKey] = time24Hour;

            // POCOR-9700: debounce per row to coalesce time_in + time_out into one POST under load.
            // Schools mark hundreds of teachers within the same morning window — collapsing
            // two cell-changes into one save halves request volume AND removes the race.
            var rowState = params.data.attendance[dateString];
            if (rowState._saveTimer) {
                $timeout.cancel(rowState._saveTimer);
            }
            rowState._saveTimer = $timeout(function () {
                rowState._saveTimer = null;
                if (!rowState._savePromise) {
                    rowState._savePromise = $q.when();
                }
                rowState._savePromise = rowState._savePromise.then(function () {
                    // POCOR-9700: use the latest in-memory values so the POST includes both cells if both changed.
                    return saveStaffAttendance(params, timeKey, params.value[timeKey], academicPeriodId);
                }).then(
                function (response) {
                    clearError(data, timeKey);
                    if (!response || !response.data) {
                        AlertSvc.error(scope, 'There was an error when saving record');
                        return;
                    }
                    var errBlob = response.data.error || {};
                    var hasErr = Array.isArray(errBlob) ? errBlob.length > 0 : Object.keys(errBlob).length > 0;
                    if (hasErr) {
                        setError(data, timeKey, true, {id: timepickerId, elm: inputElement});
                        var errorMsg = 'There was an error when saving record';
                        if (typeof errBlob === 'string') {
                            errorMsg = errBlob;
                        } else if (errBlob.time_out) {
                            errorMsg = errBlob.time_out.ruleCompareTimeReverse
                                || errBlob.time_out.timeInShouldNotEmpty
                                || errorMsg;
                        }
                        AlertSvc.error(scope, errorMsg);
                        return;
                    }
                    // POCOR-9700: fold any outside-shift warning into the save toast so the user actually sees it
                    // (the AlertSvc area shows one message at a time — a later success() would otherwise overwrite a prior warning()).
                    if (outsideShiftWarning) {
                        AlertSvc.warning(scope, 'Saved. ' + outsideShiftWarning);
                    } else {
                        AlertSvc.success(scope, 'Time record successfully saved.');
                    }
                    // POCOR-9700: trust the server's persisted record, not the typed value.
                    var saved = response.data.data || {};
                    if (angular.isDefined(saved.time_in)) {
                        params.value.time_in = saved.time_in;
                    }
                    if (angular.isDefined(saved.time_out)) {
                        params.value.time_out = saved.time_out;
                    }
                    params.value.isNew = false;
                    setError(data, timeKey, false, {id: timepickerId, elm: inputElement});
                },
                function () {
                    clearError(data, timeKey);
                    setError(data, timeKey, true, {id: timepickerId, elm: inputElement});
                    AlertSvc.error(scope, 'There was an error when saving record');
                }
                ).finally(function () {
                    UtilsSvc.isAppendSpinner(false, 'institution-staff-attendances-table');
                    params.api.refreshCells({columns: ['attendance.' + dateString], force: true});
                });
            }, 600); //POCOR-9700: debounce window
        });

        wrapperDiv.appendChild(inputElement);
        return wrapperDiv;
    }

    function convert24Timeformat(hours, minutes, seconds, meridian) {
        try {
            hours = parseInt(hours, 10);
            minutes = parseInt(minutes, 10);
            seconds = parseInt(seconds, 10);
            meridian = (meridian || '').toUpperCase();

            if (isNaN(hours) || isNaN(minutes) || isNaN(seconds)) {
                throw new Error('Invalid time values');
            }

            if (meridian === "PM" && hours < 12) hours += 12;
            if (meridian === "AM" && hours === 12) hours = 0;

            const sHours = hours < 10 ? "0" + hours : hours.toString();
            const sMinutes = minutes < 10 ? "0" + minutes : minutes.toString();
            const sSeconds = seconds < 10 ? "0" + seconds : seconds.toString();

            return sHours + ":" + sMinutes + ":" + sSeconds;
        } catch (error) {
            console.error("convert24Timeformat - Invalid input:", error.message);
            return "--:--:--";
        }
    }

    //POCOR-9700: cache the system time_format. Default to 12h (most common, matches pre-9700 behaviour);
    // fetch lazily on first formatter call (config service is not ready at svc factory init time);
    // when the real value arrives, expose it on the grid context so the ctrl can refresh once.
    var _timeFormatIs12h = true;
    var _timeFormatFetched = false;
    function ensureTimeFormatLoaded() {
        if (_timeFormatFetched) return;
        _timeFormatFetched = true;
        getConfigItemValue('time_format').then(function (tf) {
            _timeFormatIs12h = /[aA]/.test(tf || '');
        }, function () { /* keep default */ });
    }

    function convert12Timeformat(time) {
        //POCOR-9700: name kept for back-compat, but now respects the system time_format config.
        // Returns 24h "HH:MM" when system is configured 24h, else 12h "HH:MM AM/PM".
        ensureTimeFormatLoaded();
        try {
            if (!time || typeof time !== "string") {
                throw new Error("Input is not a string");
            }
            const timeSplit = time.split(":");
            if (timeSplit.length < 2) {
                throw new Error("Time string is not in HH:MM[:SS] format");
            }
            let hours = parseInt(timeSplit[0], 10);
            let minutes = parseInt(timeSplit[1], 10);
            if (isNaN(hours) || isNaN(minutes)) {
                throw new Error("Time parts must be valid numbers");
            }
            const sMinutes = minutes < 10 ? "0" + minutes : minutes.toString();
            if (!_timeFormatIs12h) {
                const sHours24 = hours < 10 ? "0" + hours : hours.toString();
                return sHours24 + ":" + sMinutes;
            }
            const meridian = hours >= 12 ? "PM" : "AM";
            hours = (hours % 12) || 12;
            const sHours = hours < 10 ? "0" + hours : hours.toString();
            return sHours + ":" + sMinutes + " " + meridian;
        } catch (error) {
            console.error("convert12Timeformat - Invalid input:", error.message);
            return _timeFormatIs12h ? "--:-- --" : "--:--";
        }
    }

    function saveStaffAttendance(params, dataKey, dataValue, academicPeriodId) {
        var dateString = params.data.date;
        var $scope = params.context.$scope; // Cache the scope

        // 1. Fetch the timezone and validate asynchronously
        // We return the promise chain so the grid/caller knows when it's done
        return getConfigItemValue('time_zone').then(function(timeZone) {

            var now = new Date();
            var formatter = new Intl.DateTimeFormat('en-CA', {
                timeZone: timeZone,
                year: 'numeric', month: '2-digit', day: '2-digit'
            });

            var parts = formatter.formatToParts(now);
            var d = parts.reduce((acc, part) => {
                if (part.type !== 'literal') acc[part.type] = part.value;
                return acc;
            }, {});

            var institutionToday = new Date(d.year, d.month - 1, d.day);
            var selectedDate = new Date(dateString);
            selectedDate.setHours(0, 0, 0, 0);

            // Debugging logs - perfect for checking Tonga vs Bahamas
            console.log("Saving Attendance - TZ:", timeZone);
            console.log("Institution Today:", institutionToday.toDateString());
            console.log("Selected Date:", selectedDate.toDateString());

            // ---- Prevent saving attendance for future dates ----
            if (selectedDate > institutionToday) {
                if ($scope) {
                    AlertSvc.warning($scope, 'Future dates cannot be saved');
                } else {
                    console.error('AlertSvc failed: scope is undefined in params.context');
                }
                return $q.reject('FUTURE_DATE');
            }

            //POCOR-9700: hard block — cannot mark a time later than the current institution-tz clock when the date is today
            if (selectedDate.getTime() === institutionToday.getTime()) {
                var attemptedTime = (params.data.attendance[dateString][dataKey] === dataValue)
                    ? dataValue
                    : (dataKey === 'time_in' || dataKey === 'time_out' ? dataValue : null);
                if (attemptedTime) {
                    var nowHms = (d.hour || '00') + ':' + (d.minute || '00') + ':' + (d.second || '00');
                    if (attemptedTime > nowHms) {
                        if ($scope) {
                            AlertSvc.warning($scope, 'Cannot mark a time in the future');
                        }
                        return $q.reject('FUTURE_TIME_TODAY');
                    }
                }
            }

            // --- Rest of your data preparation logic ---
            var staffAttendanceData = {};
            var timeIn  = params.data.attendance[dateString].time_in;
            var timeOut = params.data.attendance[dateString].time_out;

             // Enforce ordering: Time Out must be strictly greater than Time In
             if (timeIn && timeOut && timeOut <= timeIn) {
                 if ($scope) {
                     AlertSvc.error($scope, 'Time Out must be after Time In.');
                 }
                 // Do not proceed to save; reject to stop the chain
                 return $q.reject('TIME_ORDER_INVALID');
             }

            staffAttendanceData = {
                staff_id: params.data.staff_id,
                institution_id: params.data.institution_id,
                academic_period_id: academicPeriodId,
                date: dateString,
                shift_id: params.context.date,
                time_in: timeIn,
                time_out: timeOut,
                comment: params.data.attendance[dateString].comment
            };

            staffAttendanceData[dataKey] = dataValue;

            if(!params.data.attendance[dateString].isNew) {
                return InstitutionStaffAttendances.edit(staffAttendanceData);
            } else {
                return InstitutionStaffAttendances.save(staffAttendanceData);
            }

        }).catch(function(err) {
            console.error('Error in saveStaffAttendance:', err);
            if ($scope) AlertSvc.error($scope, 'Error validating timezone settings');
            return false;
        });
    }

    function setError(data, dataKey, error, input) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }

        var index = Object.keys(errorElms).indexOf(input.id);
        if (error) {
            input.elm.className += ' form-error';
            input.elm.value = '';
            if (index === -1) errorElms[input.id] = input.elm;
        } else {
            input.elm.className = input.elm.className.replace(/ form-error/gi, '');
            if (index > -1) delete errorElms[input.id];
        }
    }

    function hasError(data, key, id) {
        return angular.isDefined(errorElms[id]);
    }

    function clearError(data, skipKey) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }
        angular.forEach(errorElms, function(elm, id) {
            elm.className = elm.className.replace(/ form-error/gi, '');
        });
        errorElms = {};
    }

    function getConfigItemValue(code) {
        var success = function(response, deferred) {
            var results = response.data.data;
            if (angular.isObject(results) && results.length > 0) {
                var configItemValue = (results[0].value.length > 0) ? results[0].value : results[0].default_value;
                deferred.resolve(configItemValue);
            } else {
                deferred.reject('There is no ' + code + ' configured');
            }
        };

        return ConfigItems
            .where({
                code: code
            })
            .ajax({
                success: success,
                defer: true
            });
    }
};
