angular
    .module('institution.staff.attendances.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStaffAttendancesSvc', InstitutionStaffAttendancesSvc);

InstitutionStaffAttendancesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc'];

function InstitutionStaffAttendancesSvc($http, $q, $filter, KdDataSvc, AlertSvc) {
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
        getStaffAttendances: getStaffAttendances,
        getAllStaffAttendances: getAllStaffAttendances,
        // getAllDayAllStaffAttendances: getAllDayAllStaffAttendances,
        getColumnDefs: getColumnDefs,
        getAllDayColumnDefs: getAllDayColumnDefs,
        saveStaffAttendanceTimeIn:saveStaffAttendanceTimeIn,
        saveStaffAttendanceTimeOut:saveStaffAttendanceTimeOut,
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
                deferred.reject('ERROR');
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
                    deferred.reject('ERROR ONE');
                }
            } else {
                deferred.reject('ERROR TWO');
            }
        };

        return AcademicPeriods
            .find('WeeksForPeriod', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getDayListOptions(academicPeriodId, weekId) {
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
                week_id: weekId
            })
            .ajax({success: success, defer: true});
    }

    function getStaffAttendances(params) {
        var extra = {
            staff_id : params.staff_id,
            institution_id: params.institution_id,
            academic_period_id: params.academic_period_id,
            week_id: params.week_id,
            week_start_day: params.week_start_day,
            week_end_day: params.week_end_day,
        };

        var success = function(response, deferred) {
            var staffAttendances = response.data.data;

            if (angular.isObject(staffAttendances)) {
                deferred.resolve(staffAttendances);
            } else {
                deferred.reject('ERROR');
            }
        };

        return Staff
            .find('StaffAttendances', extra)
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
            var staffAttendances = response.data.data;
            if (angular.isObject(staffAttendances)) {
                deferred.resolve(staffAttendances);
            } else {
                deferred.reject('ERROR');
            }
        };
        return Staff
            .find('AllDayAllStaffAttendances', extra)
            .ajax({success: success, defer: true});
    }

    function getColumnDefs(selectedDate) {
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
            field: "attendance." + selectedDate,
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.value) && params.value !== null && angular.isDefined(params.context.action)) {
                    // var action = params.context.action;
                    // var data = params.data;
                    // var rowIndex = params.rowIndex;
                    // var timeIn = params.data.InstitutionStaffAttendances.time_in;
                    return getTimeInTimeOutElement(params);
                    // return params.value.time_in + ' - ' + params.value.time_out;
                }
            }
        });

        columnDefs.push({
            headerName: "Leaves",
            field: "attendance." + selectedDate,
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.value) &&params.value != null && angular.isDefined(params.context.action)) {
                    return getStaffLeaveElement(params.value);
                }
            }
        });
        // comments for attendance
        columnDefs.push({
            headerName: "Comments",
            field: "attendance." + selectedDate + ".comment",
            menuTabs: [],
            cellRenderer: function(params) {
                // if (angular.isDefined(params.value) && params.value != null && angular.isDefined(params.context.action)) {
                return getCommentElement(params);
                // }
            }
        });

        return columnDefs;
    }

    // column definitions
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
                var dayText = dayObj.shortName;
                var colDef = {
                    headerName: dayText,
                    menuTabs: [],
                    field: 'attendance.' + dayObj.date,
                    cellRenderer: function(params) {
                        if (angular.isDefined(params.value) && params.value !== null) {
                            return getTimeInTimeOutElementTwo(params.value);
                            // return params.value.time_in + ' - ' + params.value.time_out;
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
            angular.forEach(staffLeaves, function(staffLeave) {
                // var statusId = staffLeave.status_id;
                // var leaveTypeId = staffLeave.staff_leave_type_id;
                var url = "hello";
                var start_time = staffLeave.start_time;
                var end_time = staffLeave.end_time;
                var full_day = staffLeave.full_day;
                // var leaveStatusName = staffLeave._matchingData.Statuses.name;
                var leaveTypeName = staffLeave.staffLeaveTypeName;
                data += '<font color="#CC5C5C"><i class="fa-calendar-check-o"></i> '+leaveTypeName + '</font><br>';

                // if (!full_day){
                //     data += start_time + '<br>' + end_time + '<br>';
                // }
                // data += 'end of an index <br>';
            });
            data += '<i class="fa fa-file-text" style=" color: #FFFFFF; background-color:  #6699CC; border: 1px solid #6699CC;"></i><a href= "'+url+ '"target="_blank"> View Details</a>';
        } else {
            data = '<i class="fa fa-minus"></i>';
;
        }
        return data;
    }

    function getTimeInTimeOutElement(params) {
        var action = params.context.action;
        var academicPeriodId = params.context.period;
        var data = params.data;
        var rowIndex = params.rowIndex;
        var timeinPickerId = 'time-in-' + rowIndex;
        var timeoutPickerId = 'time-out-' + rowIndex;
        var timeIn = params.value.time_in;
        var timeOut = params.value.time_out;
        var scope = params.context.scope;
        if(action == 'edit') {
            if(timeIn == null || timeIn == ""){
                timeIn = '';
            }else{
                timeIn = convert12Timeformat(timeIn);
            }
            if(timeOut == null || timeOut == ""){
                timeOut = '';
            }else{
                timeOut = convert12Timeformat(timeOut);
            }
            // time in element
            var divElement = document.createElement('div');
            divElement.setAttribute('class', 'input');
            var inputDivElement = document.createElement('div');
            inputDivElement.setAttribute('id', timeinPickerId);
            inputDivElement.setAttribute('class', 'input-group time');
            var inputElement = document.createElement('input');
            inputElement.setAttribute('class', 'form-control');
            var spanElement = document.createElement('span');
            spanElement.setAttribute('class', 'input-group-addon');
            var iconElement = document.createElement('i');
            iconElement.setAttribute('class', 'glyphicon glyphicon-time');

            setTimeout(function(event) {
                var timepickerControl = $('#' + timeinPickerId).timepicker({defaultTime: timeIn});
                $('#' + timeinPickerId).timepicker().on("hide.timepicker", function (e) {
                    var time_in = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                    saveStaffAttendanceTimeIn(params.data, params.value, time_in, academicPeriodId)
                    .then(
                        function(response) {
                            // console.log(response.data.error.length);
                            if(response.data.error.length == 0){
                                AlertSvc.success(scope, 'Time in record successfully saved.');
                                params.value.isNew = false;
                                params.value.time_in = time_in;
                            }else{
                                 AlertSvc.error(scope, response.data.error.time_in.ruleCompareTime);
                            }
                        },
                        function(error) {
                            console.log(error);
                            AlertSvc.error(scope, 'There was an error when saving record');
                        }
                    )
                    .finally(function() {
                        var refreshParams = {
                            columns: [
                                'time_in',
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

            inputElement.addEventListener('click', function(event) {
                $('#' + timeinPickerId).timepicker();
            });

            spanElement.appendChild(iconElement);
            inputDivElement.appendChild(inputElement);
            inputDivElement.appendChild(spanElement);
            divElement.appendChild(inputDivElement);
            //end of time in element

            // time out element
            var inputDivElement2 = document.createElement('div');
            inputDivElement2.setAttribute('id', timeoutPickerId);
            inputDivElement2.setAttribute('class', 'input-group time');
            var inputElement2 = document.createElement('input');
            inputElement2.setAttribute('class', 'form-control');
            var spanElement2 = document.createElement('span');
            spanElement2.setAttribute('class', 'input-group-addon');
            var iconElement2 = document.createElement('i');
            iconElement2.setAttribute('class', 'glyphicon glyphicon-time');

            setTimeout(function(event) {
                var timepickerControl2 = $('#' + timeoutPickerId).timepicker({defaultTime: timeOut});
                $('#' + timeoutPickerId).timepicker().on("hide.timepicker", function (e) {
                    // console.log('saving start time.........');
                    // console.log(e.time);
                    // convertTimeformat(e.time.hours, e.time.minutes, e.time.seconds);
                    var time_out = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                    // console.log(time_in);
                    // data.InstitutionStaffAttendances.start_time = start_time;
                    saveStaffAttendanceTimeOut(params.data, params.value, time_out, academicPeriodId)
                    .then(
                        function(response) {
                            // console.log(response.data.error.length);
                            if(response.data.error.length == 0){
                                AlertSvc.success(scope, 'Time out record successfully saved.');
                                params.value.isNew = false;
                                params.value.time_out = time_out;
                            }else{
                                 AlertSvc.error(scope, response.data.error.time_out.ruleCompareTime);
                            }
                        },
                        function(error) {
                            console.log(error);
                            AlertSvc.error(scope, 'There was an error when saving record');
                        }
                    )
                    .finally(function() {
                        var refreshParams = {
                            columns: [
                                'time_out',
                            ],
                            force: true
                        };
                        params.api.refreshCells(refreshParams);
                    });
                });
                $(document).on('DOMMouseScroll mousewheel scroll', function() {
                    window.clearTimeout(t);
                    t = setTimeout(function(event) {
                        timepickerControl2.timepicker('place');
                    });
                });
            }, 1);

            inputElement2.addEventListener('click', function(event) {
                $('#' + timeoutPickerId).timepicker();
            });

            spanElement2.appendChild(iconElement2);
            inputDivElement2.appendChild(inputElement2);
            inputDivElement2.appendChild(spanElement2);
            divElement.appendChild(inputDivElement2);
            //end of time out element
            return divElement;
        } else {
            var time = '<i class="fa fa-minus"></i>';
            var historyUrl = data.historyUrl;
            if(timeIn && timeOut){
                time = '<font color= "#77B576"><i class="fa-external-link-square"></i> '+ convert12Timeformat(timeIn) + '<br><i class="fa-external-link"></i> ' + convert12Timeformat(timeOut) +'</font><br><i class="fa fa-file-text" style=" color: #FFFFFF; background-color:  #6699CC; border: 1px solid #6699CC;"></i><a href= "'+ historyUrl + '"target="_blank"> View History Log</a>';
            } else if (timeIn && !timeOut) {
                time = '<font color= "#77B576"><i class="fa-external-link"></i> '+ convert12Timeformat(timeIn) + '</font>';
            }
            return time;
        }
    }

    function getTimeInTimeOutElementTwo(params) {
        var timeIn = params.time_in;
        var timeOut = params.time_out;
        var time = '';
        if (timeIn && timeOut){
            time = '<font color= "#77B576"><i class="fa-external-link"></i> '+ convert12Timeformat(timeIn) + '<br><i class="fa-external-link"></i> ' + convert12Timeformat(timeOut) +  '</font>';
        } else if (timeIn && !timeOut) {
            time = '<font color= "#77B576"><i class="fa-external-link"></i> '+ convert12Timeformat(timeIn) + '</font>';
        }
        if (angular.isDefined(params.leave) && params.leave.length != 0) {
            angular.forEach(params.leave, function(leave) {
                time += '<br><font color="#CC5C5C"> <i class="fa-calendar-check-o"></i> '+ leave.staffLeaveTypeName +'</font>';
                if (leave.isFullDay) {
                    time += '<br><font color="#CC5C5C">(Full Day)</font><br>';
                } else if (leave.startTime && leave.endTime) {
                    time += '<br><font color="#CC5C5C">'+ convert12Timeformat(leave.startTime) + ' - '+ convert12Timeformat(leave.endTime)+'</font><br>';
                }
            });
        }
        if (time == '') {
            time = '<i class="fa fa-minus"></i>';
        }
        return time;
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
        if (hours > 12) hours = hours - 12;
        // if (hours < 10) hours = "0" + hours;

        //00 does not exists in 12-hour time format hence need to convert 00 back to 12,
        //else timepicker will display wrong timing when error when user selects 12AM
        if (hours == 0) hours = 12;

        var sHours = hours.toString();
        if (sHours.length == 1) {
            sHours = "0" + sHours;
        }
        var sMinutes = minutes.toString();
        // var sSeconds = seconds.toString();
        return sHours + ":" + sMinutes + " " + meridian;
    }

    function saveStaffAttendanceTimeIn(data, params, time, academicPeriodId) {
        var isNew = params.isNew;
        var timeIn = params.time_in;
        var staffAttendanceData = {
            staff_id: data.staff_id,
            institution_id: data.institution_id,
            academic_period_id: academicPeriodId,
            date: params.dateStr,
            time_in: time,
            time_out: params.time_out
        };
        if(!data.isNew && timeIn != null){
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        }else{
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
    }

    function saveStaffAttendanceTimeOut(data, params, time, academicPeriodId) {
        var isNew = params.isNew;
        var timeOut = params.time_out;
        var staffAttendanceData = {
            staff_id: data.staff_id,
            institution_id: data.institution_id,
            academic_period_id: academicPeriodId,
            date: params.dateStr,
            time_in: params.time_in,
            time_out: time,
        };
        if(!data.isNew && timeOut != null){
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        }else{
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
    }

    function saveStaffAttendanceComment(params, comment) {
        var dateKey = params.data.date;
        var value = params.data.attendance[dateKey];
        var isNew = value.isNew;
        var timeOut = value.time_out;
        var staffAttendanceData = {
            staff_id: params.data.staff_id,
            institution_id: params.data.institution_id,
            academic_period_id: params.context.period,
            date: value.dateStr,
            time_in: value.time_in,
            time_out: value.time_out,
            comment: comment
        };
        console.log(staffAttendanceData);
        if(!value.isNew && timeOut != null){
            console.log('updating entity');
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        }else{
            console.log('saving new eentity');
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
    }

    function getCommentElement(params) {
        console.log('Rendering before action');
        var action = params.context.action;
        var divElement = '';
        if (action == 'edit') {
            console.log('Getting Comment Element');
            divElement = getEditCommentElement(params);
        } else {
            divElement = getViewCommentElement(params.value);
        }
        return divElement;
    }

    function getViewCommentElement(data) {
        // console.log(data);
        var comment = data;
        var html = '<i class="fa fa-minus"></i>';
        if (comment != null) {
            // the icon dunch wanna come out :((
            html = '<i class="fa kd-comment"></i>' + comment;
        }
        return html;
    }

    function getEditCommentElement(params) {
        var dataKey = 'comment';
        var scope = params.context.scope;
        var value = params.value;
        var date = params.data.date;
        var eTextarea = document.createElement("textarea");
        eTextarea.setAttribute("placeholder", "");
        eTextarea.setAttribute("id", dataKey);

        // if (hasError(data, dataKey)) {
        //     eTextarea.setAttribute("class", "error");
        // }

        eTextarea.value = params.value;
        eTextarea.addEventListener('blur', function () {
            var oldValue = params.value;
            var newValue = eTextarea.value;
            // console.log(newValue);
            // UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
            saveStaffAttendanceComment(params, newValue, date)
            .then(
                function(response) {
                    // console.log(response);
                    if(response.data.error.length == 0){
                        console.log(newValue);
                        AlertSvc.success(scope, 'Comment successfully saved.');
                        console.log(params);
                        params.data.attendance[date].comment = newValue;
                    } else {
                        AlertSvc.error(scope, 'There was an error when saving the record');
                    }
                },
                function(error) {
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
                console.log('refreshing');
                params.api.refreshCells(refreshParams);
                // UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
            });
        });
        return eTextarea;
    }
};