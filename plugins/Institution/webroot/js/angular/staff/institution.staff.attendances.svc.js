angular
    .module('institution.staff.attendances.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStaffAttendancesSvc', InstitutionStaffAttendancesSvc);

InstitutionStaffAttendancesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function InstitutionStaffAttendancesSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
   var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionStaffAttendances: 'Staff.InstitutionStaffAttendances',
        Staff: 'Institution.Staff'
    };

    var service = {
        init: init,
        translate: translate,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getDayListOptions:getDayListOptions,
        getAllStaffAttendances: getAllStaffAttendances,
        getColumnDefs: getColumnDefs,
        getAllDayColumnDefs: getAllDayColumnDefs,
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
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }

        columnDefs.push({
            headerName: "Date",
            field: "date",
            filter: "text",
            hide: true,
            menuTabs: []
        });

        columnDefs.push({
            headerName: "Name",
            field: "_matchingData.Users.name_with_id",
            filter: "text",
            menuTabs: []
        });

        columnDefs.push({
            headerName: "institution id",
            field: "institution_id",
            hide: true,
        });

        columnDefs.push({
            headerName: "Time in - Time Out",
            field: "attendance." + selectedDayDate,
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.value) && params.value !== null && angular.isDefined(params.context.action)) {
                    return getSingleDayTimeInTimeOutElement(params);
                }
            }
        });

        columnDefs.push({
            headerName: "Leaves",
            field: "attendance." + selectedDayDate,
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.value) &&params.value != null && angular.isDefined(params.context.action)) {
                    return getStaffLeaveElement(params.value);
                }
            }
        });

        columnDefs.push({
            headerName: "Comments",
            field: "attendance." + selectedDayDate + ".comment",
            menuTabs: [],
            cellClass: 'comment-flex',
            cellRenderer: function(params) {
                return getCommentElement(params);
            }
        });
        return columnDefs;
    }

    function getAllDayColumnDefs(dayList) {
        var columnDefs = [];
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }

        columnDefs.push({
            headerName: "Name",
            field: "_matchingData.Users.name_with_id",
            // filterParams: filterParams,
            pinned: direction,
            menuTabs: [],
            filter: "text"
        });
        angular.forEach(dayList, function(dayObj, dayKey) {
            if (dayObj.id != -1) {
                var dayText = dayObj.name;
                var colDef = {
                    headerName: dayText,
                    menuTabs: [],
                    field: 'attendance.' + dayObj.date,
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

    function setError(data, dataKey, error) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }

        data.save_error[dataKey] = error;
    }

    function getSingleDayTimeInTimeOutElement(params) {
        var action = params.context.action;
        var academicPeriodId = params.context.period;
        var scope = params.context.scope;
        var timeIn = params.value.time_in;
        var timeOut = params.value.time_out;
        var data = params.data;
        var rowIndex = params.rowIndex;
        var timeinPickerId = 'time-in-' + rowIndex;
        var timeoutPickerId = 'time-out-' + rowIndex;
        var time = '';
        var historyUrl = data.historyUrl;

        if (action == 'edit') {
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
                time += '<div class="time-view"><i class="fa fa-file-text-o" style="color: #72C6ED;"></i><a href= "'+ historyUrl + '"target="_blank">View History Log </a></div>';
            } else {
                time = '<i class="fa fa-minus"></i>';
            }
            return time;
        }
    }

    function getAllDayTimeInTimeOutElement(params) {
        var timeIn = params.time_in;
        var timeOut = params.time_out;
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
        if (action == 'edit') {
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
                saveStaffAttendance(params, dataKey, newValue, academicPeriodId)
                .then(
                    function(response) {
                        UtilsSvc.isAppendSpinner(false, 'institution-staff-attendances-table');
                        if(response.data.error.length == 0){
                            AlertSvc.success(scope, 'Comment successfully saved.');
                            params.data.attendance[date].comment = newValue;
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
        if (hours > 12){
            meridian = "PM";
        } else {
            meridian = "AM";
        }
        hours = (hours % 12) || 12;

        //00 does not exists in 12-hour time format hence need to convert 00 back to 12,
        //else timepicker will display wrong timing when error when user selects 12AM
        if (hours == 0) hours = 12;

        var sHours = hours.toString();
        if (sHours.length == 1) {
            sHours = "0" + sHours;
        }
        var sMinutes = minutes.toString();
        return sHours + ":" + sMinutes + " " + meridian;
    }

    function createTimeElement(params, timeKey, rowIndex)
    {
        var data = params.data;
        var academicPeriodId = params.context.period;
        var timepickerId = (timeKey == 'time_in') ? 'time-in-' : 'time-out-';
        timepickerId += rowIndex;
         var time = '';
        if (params.value[timeKey] != null && params.value[timeKey] != "") {
            time = convert12Timeformat(params.value[timeKey]);
        }
        var scope = params.context.scope;
        // div element
        var timeInputDivElement = document.createElement('div');
        timeInputDivElement.setAttribute('id', timepickerId);
        timeInputDivElement.setAttribute('class', 'input-group time');
        var timeInputElement = document.createElement('input');
        timeInputElement.setAttribute('class', 'form-control');
        var timeSpanElement = document.createElement('span');
        timeSpanElement.setAttribute('class', 'input-group-addon');
        var timeIconElement = document.createElement('i');
        timeIconElement.setAttribute('class', 'glyphicon glyphicon-time');

        if (hasError(data, timeKey)) {
            timeInputElement.setAttribute("class", "form-control form-error");
        }
        setTimeout(function(event) {
            var timepickerControl = $('#' + timepickerId).timepicker({defaultTime: time});
            $('#' + timepickerId).timepicker().on("hide.timepicker", function (e) {
                UtilsSvc.isAppendSpinner(true, 'institution-staff-attendances-table');
                var time24Hour = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                saveStaffAttendance(params.value, timeKey, time24Hour, academicPeriodId)
                .then(
                    function(response) {
                        clearError(data, timeKey);
                        if(response.data.error.length == 0){
                            AlertSvc.success(scope, 'Time record successfully saved.');
                            params.value.isNew = false;
                            params.value[timeKey] = time24Hour;
                            setError(data, timeKey, false);
                        }else{
                            setError(data, timeKey, true);
                            console.log(response.data.error);
                            AlertSvc.error(scope, response.data.error.time_out.ruleCompareTimeReverse);
                        }
                    },
                    function(error) {
                        console.log('error', error);
                        clearError(data, timeKey);
                        setError(data, timeKey, true);
                        AlertSvc.error(scope, 'There was an error when saving record');
                    }
                )
                .finally(function() {
                    UtilsSvc.isAppendSpinner(false, 'institution-staff-attendances-table');
                    console.log('attendance.' + data.date);
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

        timeInputElement.addEventListener('click', function(event) {
            $('#' + timepickerId).timepicker();
        });

        timeSpanElement.appendChild(timeIconElement);
        timeInputDivElement.appendChild(timeInputElement);
        timeInputDivElement.appendChild(timeSpanElement);
        return timeInputDivElement;
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
        if(!params.isNew) {
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        } else {
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
    }

    function hasError(data, key) {
        return (angular.isDefined(data.save_error) && angular.isDefined(data.save_error[key]) && data.save_error[key]);
    }

    function clearError(data, skipKey) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }

        angular.forEach(data.save_error, function(error, key) {
            if (key != skipKey) {
                data.save_error[key] = false;
            }
        })
    }
};