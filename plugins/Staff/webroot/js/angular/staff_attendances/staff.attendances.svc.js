angular
    .module('staff.attendances.svc', ['kd.data.svc', 'alert.svc'])
    .service('StaffAttendancesSvc', StaffAttendancesSvc);

StaffAttendancesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc'];

function StaffAttendancesSvc($http, $q, $filter, KdDataSvc, AlertSvc) {
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
        getStaffAttendances: getStaffAttendances,
        getColumnDefs: getColumnDefs,
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
            headerName: "Date",
            field: "date",
            filter: "text",
            menuTabs: []
        });

        columnDefs.push({
            headerName: "institution id",
            field: "institution_id",
            hide: true,
        });

        columnDefs.push({
            headerName: "Time in",
            field: "time_in",
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.context.action)) {
                    var timeIn = params.data.InstitutionStaffAttendances.time_in;
                    return getTimeInElement(params);
                }
            }
        });

        columnDefs.push({
            headerName: "Time out",
            field: "time_out",
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.context.action)) {
                    var timeOut = params.data.InstitutionStaffAttendances.time_out;
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
        var timepickerId = 'time-in-' + rowIndex;
        var timeIn = params.data.InstitutionStaffAttendances.time_in;
        var scope = params.context.scope;
        if (action == 'edit') {
            if (timeIn == null) {
                timeIn = '';
            } else {
                timeIn = convert12Timeformat(timeIn);
            }
            var divElement = document.createElement('div');
            divElement.setAttribute('class', 'input');
            var inputDivElement = document.createElement('div');
            inputDivElement.setAttribute('id', timepickerId);
            inputDivElement.setAttribute('class', 'input-group time');
            var inputElement = document.createElement('input');
            inputElement.setAttribute('class', 'form-control');
            var spanElement = document.createElement('span');
            spanElement.setAttribute('class', 'input-group-addon');
            var iconElement = document.createElement('i');
            iconElement.setAttribute('class', 'glyphicon glyphicon-time');

            setTimeout(function(event) {
                var timepickerControl = $('#' + timepickerId).timepicker({defaultTime: timeIn});
                $('#' + timepickerId).timepicker().on("hide.timepicker", function (e) {
                    var time_in = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                    saveStaffAttendanceTimeIn(data, params, time_in)
                    .then(
                        function(response) {
                            if (response.data.error.length == 0) {
                                AlertSvc.success(scope, 'Time in record successfully saved.');
                                data.isNew = false;
                                data.InstitutionStaffAttendances.time_in = time_in;
                            } else {
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
                $('#' + timepickerId).timepicker();
            });

            spanElement.appendChild(iconElement);
            inputDivElement.appendChild(inputElement);
            inputDivElement.appendChild(spanElement);
            divElement.appendChild(inputDivElement);

            return divElement;
        } else {
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
        var timepickerId = 'time-out-' + rowIndex;
        var timeOut = params.data.InstitutionStaffAttendances.time_out;
        var scope = params.context.scope;
        if (action == 'edit') {
            if (timeOut == null) {
                timeOut = '';
            } else {
                timeOut = convert12Timeformat(timeOut);
            }
            var divElement = document.createElement('div');
            divElement.setAttribute('class', 'input');
            var inputDivElement = document.createElement('div');
            inputDivElement.setAttribute('id', timepickerId);
            inputDivElement.setAttribute('class', 'input-group time');
            var inputElement = document.createElement('input');
            inputElement.setAttribute('class', 'form-control');
            var spanElement = document.createElement('span');
            spanElement.setAttribute('class', 'input-group-addon');
            var iconElement = document.createElement('i');
            iconElement.setAttribute('class', 'glyphicon glyphicon-time');

            setTimeout(function(event) {
                var timepickerControl = $('#' + timepickerId).timepicker({defaultTime: timeOut});
                    $('#' + timepickerId).timepicker().on("hide.timepicker", function (e) {
                    var time_out = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                    saveStaffAttendanceTimeOut(data, params, time_out)
                    .then(
                        function(response) {
                            if (response.data.error.length == 0) {
                                AlertSvc.success(scope, 'Time Out record successfully saved.');
                                data.isNew = false;
                                data.InstitutionStaffAttendances.time_out = time_out;
                            } else {
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
                        timepickerControl.timepicker('place');
                    });
                });
            }, 1);

            inputElement.addEventListener('click', function(event) {
                $('#' + timepickerId).timepicker();
            });

            spanElement.appendChild(iconElement);
            inputDivElement.appendChild(inputElement);
            inputDivElement.appendChild(spanElement);
            divElement.appendChild(inputDivElement);

            return divElement;
        } else {
            if (timeOut) {
                timeOut = '<div class = "time-view"><i class="fa fa-external-link"></i>'+convert12Timeformat(timeOut) + '</div>';
            } else {
                timeOut = '<i class="fa fa-minus"></i>';
            }
            return timeOut;
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
        if (hours > 12) {
            meridian = "PM";
        } else {
            meridian = "AM";
        }
        if (hours > 12) hours = hours - 12;

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

    function saveStaffAttendanceTimeIn(data, context, time) {
        var isNew = data.isNew;
        var timeIn = data.InstitutionStaffAttendances.time_in;
        var staffAttendanceData = {
            staff_id: data.staff_id,
            institution_id: data.institution_id,
            academic_period_id: context.context.period,
            date: data.InstitutionStaffAttendances.date,
            time_in: time,
            time_out: data.InstitutionStaffAttendances.time_out
        };
        if (!data.isNew && timeIn != null) {
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        } else {
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
    }

    function saveStaffAttendanceTimeOut(data, context, time) {
        var isNew = data.isNew;
        var timeOut = data.InstitutionStaffAttendances.time_out;
        var staffAttendanceData = {
            staff_id: data.staff_id,
            institution_id: data.institution_id,
            academic_period_id: context.context.period,
            date: data.InstitutionStaffAttendances.date,
            time_in: data.InstitutionStaffAttendances.time_in,
            time_out: time,
        };
        if (!data.isNew && timeOut != null) {
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        } else {
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
    }

};