angular
    .module('institution.staff.attendances.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStaffAttendancesSvc', InstitutionStaffAttendancesSvc);

InstitutionStaffAttendancesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function InstitutionStaffAttendancesSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
   var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionStaffAttendances: 'Staff.InstitutionStaffAttendances',
        Staff: 'Institution.Staff',
        StaffAttendances: 'Institution.StaffAttendances',
        InstitutionShiftsTable:'Institution.InstitutionShifts'
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
        getAllDayColumnDefs: getAllDayColumnDefs
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
            .find('DaysForPeriodWeek', {
                academic_period_id: academicPeriodId,
                week_id: weekId,
                institution_id: institutionId,
            })
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
            headerName: translateText.translated.openemis_no,
            field: "_matchingData.Users.openemis_no",
            pinned: direction,
            menuTabs: []
        });

        columnDefs.push({
            headerName: translateText.translated.Name,
            field: "_matchingData.Users.name",
            filter: "text",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs
        });

        columnDefs.push({
            headerName: translateText.translated.TimeIn + " - " + translateText.translated.TimeOut,
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
            headerName: translateText.translated.Leave,
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
            headerName: translateText.translated.Comments,
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
            headerName: translateText.translated.openemis_no,
            field: "_matchingData.Users.openemis_no",
            pinned: direction,
            menuTabs: []
        });

        columnDefs.push({
            headerName: translateText.translated.Name,
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
        
        var shiftsAttendance =  InstitutionShiftsTable.find('StaffShiftsAttendance', 
         {staff_id: staffId})
                .ajax({success: successInstitutionShifts, defer: true});
        
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
        console.log(params);
        var timeIn = params.time_in;
        console.log(timeIn);
        var timeOut = params.time_out;
         console.log(timeOut);
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
            if (newValue && oldValue != newValue) {
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

    function createTimeElement(params, timeKey, rowIndex)
    {
		var action = params.context.action;
        var data = params.data;
        var academicPeriodId = params.context.period;
        var timepickerId = (timeKey == 'time_in') ? 'time-in-' : 'time-out-';
        timepickerId += rowIndex;
        var time = '';
        if (params.value[timeKey] != null && params.value[timeKey] != "") {
            time = convert12Timeformat(params.value[timeKey]);
        }
        var scope = params.context.scope;
        var leave = data.attendance[data.date].leave;
        var isDisabled = (leave && leave.length > 0 && leave[0].isFullDay === 1);

        // div element
        var timeInputDivElement = document.createElement('div');
        if (!isDisabled) timeInputDivElement.setAttribute('id', timepickerId); // for pop up
        timeInputDivElement.setAttribute('class', 'input-group time');
        var timeInputElement = document.createElement('input');
        timeInputElement.setAttribute('class', 'form-control');
        if (isDisabled) timeInputElement.setAttribute('disabled', true); // for styling ui
        timeInputElement.setAttribute('readonly', 'readonly');
        var timeSpanElement = document.createElement('span');
        timeSpanElement.setAttribute('class', (isDisabled) ? 'input-group-addon disabled' : 'input-group-addon'); // for styling ui
        var timeIconElement = document.createElement('i');
        timeIconElement.setAttribute('class', 'glyphicon glyphicon-time');

        if (hasError(data, timeKey, timepickerId)) {
            timeInputElement.setAttribute("class", "form-control form-error");
        }
        setTimeout(function(event) {
            var timepickerControl = $('#' + timepickerId).timepicker({defaultTime: time, showInputs: true,minuteStep:1});
            $('#' + timepickerId).timepicker().on("hide.timepicker", function (e) {
                UtilsSvc.isAppendSpinner(true, 'institution-staff-attendances-table');
                if (params.value[timeKey] == null) {
                    params.value.isNew = true;
                }
                var time24Hour = null;
                if (timeInputElement.value.length > 0) {
                    time24Hour = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                }
                saveStaffAttendance(params, timeKey, time24Hour, academicPeriodId)
                .then(
                    function(response) {
                        clearError(data, timeKey);
                        if (Object.keys(response.data.error).length > 0 || response.data.error.length > 0) {
                            setError(data, timeKey, true, { id: timepickerId, elm: timeInputElement });
                            var errorMsg = 'There was an error when saving record';
                            if (typeof response.data.error === 'string') {
                                errorMsg = response.data.error;
                            } else if (response.data.error.time_out.ruleCompareTimeReverse) {
                                errorMsg = response.data.error.time_out.ruleCompareTimeReverse;
                            } else if (response.data.error.time_out.timeInShouldNotEmpty) {
                                errorMsg = response.data.error.time_out.timeInShouldNotEmpty;
                            }
                            
                            AlertSvc.error(scope, errorMsg);
                        } else {
                            AlertSvc.success(scope, 'Time record successfully saved.');
                            params.value.isNew = false;
                            params.value[timeKey] = time24Hour;
                            setError(data, timeKey, false, { id: timepickerId, elm: timeInputElement });
                        }
                    },
                    function(error) {
                        clearError(data, timeKey);
                        setError(data, timeKey, true, { id: timepickerId, elm: timeInputElement });
                        AlertSvc.error(scope, 'There was an error when saving record');
                    }
                )
                .finally(function() {
                    UtilsSvc.isAppendSpinner(false, 'institution-staff-attendances-table');
                    var refreshParams = {
                        columns: [
                            'attendance.' + data.date,
                        ],
                        force: true
                    };
                    params.api.refreshCells(refreshParams);
                });
            });
            $(document).on('DOMMouseScroll mousewheel scroll', function() {
                window.clearTimeout(t);
                t = setTimeout(function(event) {
                    timepickerControl.timepicker('place');
                });
            });
        }, 1);

        timeInputElement.addEventListener('select', function(event) {
            $(this).click();
        });

        timeInputElement.addEventListener('click', function(event) {
            timeInputElement.removeAttribute('readonly', 'readonly');
            $('#' + timepickerId).timepicker();
        });
        
        timeInputElement.addEventListener('keydown', function(event) {
            if(event.keyCode != 8){
                event.preventDefault();
            }
        });

        timeSpanElement.appendChild(timeIconElement);
        timeInputDivElement.appendChild(timeInputElement);
        timeInputDivElement.appendChild(timeSpanElement);
        return timeInputDivElement;
    }

    function convert24Timeformat(hours, minutes, seconds, meridian) {
        if (meridian == "PM" && hours < 12) hours = hours + 12;
        if (meridian == "AM" && hours == 12) hours = hours - 12;
        var sHours = hours.toString();
        var sMinutes = minutes.toString();
        var sSeconds = seconds.toString();
        if (hours < 10) sHours = "0" + sHours;
        if (minutes < 10) sMinutes = "0" + sMinutes;
        if (seconds < 10) sSeconds = "0" + sSeconds;
        return sHours + ":" + sMinutes + ":" + sSeconds;
    }

    function convert12Timeformat(time) {
        var timeSplit = time.split(":");
        hours = timeSplit[0];
        minutes = timeSplit[1];
        seconds = timeSplit[2];
        if (hours >= 12){
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

    function saveStaffAttendance(params, dataKey, dataValue, academicPeriodId) {
        var dateString = params.data.date;
        var staffAttendanceData = {
            staff_id: params.data.staff_id,
            institution_id: params.data.institution_id,
            academic_period_id: academicPeriodId,
            date: dateString,
            time_in: params.data.attendance[dateString].time_in,
            time_out: params.data.attendance[dateString].time_out,
            comment: params.data.attendance[dateString].comment
        };

        staffAttendanceData[dataKey] = dataValue;
        if(!params.data.attendance[dateString].isNew) {
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        } else {
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
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
};