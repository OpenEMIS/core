angular
    .module('staff.attendances.svc', ['kd.data.svc', 'alert.svc'])
    .service('StaffAttendancesSvc', StaffAttendancesSvc);

StaffAttendancesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function StaffAttendancesSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
    var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionStaffAttendances: 'Staff.InstitutionStaffAttendances',
        Staff: 'Institution.Staff'
    };

    var translateText = {
        'original': {
            'Date': 'Date',
            'TimeIn': 'Time In',
            'TimeOut': 'Time Out'
        },
        'translated': {
        }
    };

    var service = {
        init: init,
        translate: translate,
        getTranslatedText: getTranslatedText,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getStaffAttendances: getStaffAttendances,
        getColumnDefs: getColumnDefs,
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

    function getColumnDefs(staffAttendances) {
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
            headerName: translateText.translated.Date,
            field: "date",
            filter: "text",
            menuTabs: []
        });

        columnDefs.push({
            headerName: translateText.translated.TimeIn,
            field: "time_in",
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.context.action)) {
                    return getTimeInElement(params);
                }
            }
        });

        columnDefs.push({
            headerName: translateText.translated.TimeOut,
            field: "time_out",
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.context.action)) {
                    return getTimeOutElement(params);
                }
            }
        });
        return columnDefs;
    }

    function getTimeInElement(params) {
        var action = params.context.action;
        var data = params.data;
        var rowIndex = params.rowIndex;
        var timeIn = params.data.InstitutionStaffAttendances.time_in;
        if (action == 'edit') {
            var divElement = document.createElement('div');
            var timeInInputDivElement = createTimeElement(params, 'time_in', rowIndex);
            divElement.appendChild(timeInInputDivElement);
            return divElement;
        } else {
            clearError(data, 'time_in');
            if (timeIn) {
                timeIn = '<div class = "time-view"><i class="fa fa-external-link-square"></i>'+convert12Timeformat(timeIn)+'</div>';
            } else {
                timeIn = '<i class="fa fa-minus"></i>';
            }
            return timeIn;
        }
    }

    function getTimeOutElement(params) {
        var action = params.context.action;
        var data = params.data;
        var rowIndex = params.rowIndex;
        var timeOut = params.data.InstitutionStaffAttendances.time_out;
        if (action == 'edit') {
            var divElement = document.createElement('div');
            var timeOutInputDivElement = createTimeElement(params, 'time_out', rowIndex);
            divElement.appendChild(timeOutInputDivElement);
            return divElement;
        } else {
            clearError(data, 'time_out');
            if (timeOut) {
                timeOut = '<div class = "time-view"><i class="fa fa-external-link"></i>'+convert12Timeformat(timeOut) + '</div>';
            } else {
                timeOut = '<i class="fa fa-minus"></i>';
            }
            return timeOut;
        }
    }

    function createTimeElement(params, timeKey, rowIndex)
    {
        var scope = params.context.scope;
        var data = params.data;
        var academicPeriodId = params.context.period;
        var timepickerId = (timeKey == 'time_in') ? 'time-in-' : 'time-out-';
        timepickerId += rowIndex;
        var time = '';
        if (data.InstitutionStaffAttendances[timeKey] != null && data.InstitutionStaffAttendances[timeKey] != "") {
            time = convert12Timeformat(data.InstitutionStaffAttendances[timeKey]);
        }
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
                if (data.InstitutionStaffAttendances[timeKey] == null) {
                    data.isNew = true;
                }
                var time24Hour = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                saveStaffAttendance(data, timeKey, time24Hour, academicPeriodId)
                .then(
                    function(response) {
                        clearError(data, timeKey);
                        if(response.data.error.length == 0){
                            AlertSvc.success(scope, 'Time record successfully saved.');
                            data.isNew = false;
                            data.InstitutionStaffAttendances[timeKey] = time24Hour;
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

    function saveStaffAttendance(data, dataKey, dataValue, academicPeriodId) {
        var dateString = data.InstitutionStaffAttendances.date;
        var staffAttendanceData = {
            staff_id: data.staff_id,
            institution_id: data.institution_id,
            academic_period_id: academicPeriodId,
            date: dateString,
            time_in: data.InstitutionStaffAttendances.time_in,
            time_out: data.InstitutionStaffAttendances.time_out,
        };

        staffAttendanceData[dataKey] = dataValue;
        if(!data.isNew) {
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        } else {
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
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

    function setError(data, dataKey, error) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }
        data.save_error[dataKey] = error;
    }
};