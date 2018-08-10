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
            field: "start_time",
            menuTabs: [],
            cellRenderer: function(params) {
                console.log('params', params);
                if (angular.isDefined(params.context.action)) {
                    // var action = params.context.action;
                    // var data = params.data;
                    // var rowIndex = params.rowIndex;
                    var startTime = params.data.InstitutionStaffAttendances.start_time;
                    return getStartTimeElement(params);
                }
            }
        });

        columnDefs.push({
            headerName: "Time out",
            field: "end_time",
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.context.action)) {
                    // var action = params.context.action;
                    // var data = params.data;
                    // var rowIndex = params.rowIndex;
                    var endTime = params.data.InstitutionStaffAttendances.end_time;
                    return getEndTimeElement(params);
                }
            }
        });

        return columnDefs;
    }

    function getStartTimeElement(params) {
        var action = params.context.action;
        var data = params.data;
        var rowIndex = params.rowIndex;
        var timepickerId = 'time-in-' + rowIndex;
        var startTime = params.data.InstitutionStaffAttendances.start_time;
        var scope = params.context.scope;
        if(action == 'edit') {
            if(startTime == null){
                startTime = 'current';
            }else{
                startTime = convert12Timeformat(startTime);
                console.log(startTime);
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
                var timepickerControl = $('#' + timepickerId).timepicker({defaultTime: startTime});
                $('#' + timepickerId).timepicker().on("hide.timepicker", function (e) {
                    console.log('saving start time.........');
                    console.log(e.time);
                    // convertTimeformat(e.time.hours, e.time.minutes, e.time.seconds);
                    var start_time = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                    console.log(start_time);
                    data.InstitutionStaffAttendances.start_time = start_time;
                    saveStaffAttendanceTimeIn(data, params, start_time)
                    .then(
                        function(response) {
                            console.log('SAVING SUCCESS LEY CHECK DB');
                            // console.log(response.data.error.length);
                            if(response.data.error.length == 0){
                                AlertSvc.success(scope, 'Time in record successfully saved.');
                                data.isNew = false;
                            }else{
                                 AlertSvc.error(scope, 'There was an error when saving record');
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
                                'start_time',
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
            if(startTime == null){
                startTime = '-';
            }

            return startTime;
        }
    }

    function getEndTimeElement(params) {
        var action = params.context.action;
        var data = params.data;
        var rowIndex = params.rowIndex;
        var timepickerId = 'time-out-' + rowIndex;
        var endTime = params.data.InstitutionStaffAttendances.end_time;
        var scope = params.context.scope;
        if(action == 'edit') {
            if(endTime == null){
                endTime = 'current';
            }else{
                endTime = convert12Timeformat(endTime);
                // console.log(endTime);
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
                var timepickerControl = $('#' + timepickerId).timepicker({defaultTime: endTime});
                    $('#' + timepickerId).timepicker().on("hide.timepicker", function (e) {
                    console.log('saving end-time.........');
                    // console.log(e.time);
                    // convertTimeformat(e.time.hours, e.time.minutes, e.time.seconds);
                    var end_time = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                    console.log(end_time);
                    saveStaffAttendanceTimeOut(data, params, end_time)
                    .then(
                        function(response) {
                            console.log('SAVING SUCCESS LEY CHECK DB');
                            console.log(response.data.error.length);
                            if(response.data.error.length == 0){
                                AlertSvc.success(scope, 'Time in record successfully saved.');
                                data.isNew = false;
                            }else{
                                 AlertSvc.error(scope, 'There was an error when saving record');
                            }
                        },
                        function(error) {
                            console.log(error);
                            AlertSvc.error(scope, 'There was an error when saving record');
                        }
                    )
                    .finally(function() {
                        api.refreshCells();
                    });
                });
                $(document).on('DOMMouseScroll mousewheel scroll', function() {
                    console.log('scroll1');
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
            if(endTime == null){
                endTime = '-';
            }
            return endTime;
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
        console.log(timeSplit);
        if (hours > 12){ 
            meridian = "PM";
        } else {
            meridian = "AM";
        }
        if (hours >= 12) hours = hours - 12;
        
        var sHours = hours.toString();
        var sMinutes = minutes.toString();
        var sSeconds = seconds.toString();
        return sHours + ":" + sMinutes + ":" + sSeconds + " " +meridian;
    }

    function saveStaffAttendanceTimeIn(data, context, time) {
        var isNew = data.isNew;
        var staffAttendanceData = {
            staff_id: data.staff_id,
            institution_id: data.institution_id,
            academic_period_id: context.context.period,
            date: data.InstitutionStaffAttendances.date,
            start_time: time,
        };
        if(data.isNew){
            console.log('is new entity');
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }else{
            console.log('is not new entity');
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        }
        // return InstitutionStaffAttendances.save(staffAttendanceData);
    }

    function saveStaffAttendanceTimeOut(data, context, time) {
        var staffAttendanceData = {
            staff_id: data.staff_id,
            institution_id: data.institution_id,
            academic_period_id: context.context.period,
            date: data.InstitutionStaffAttendances.date,
            end_time: time,
        };
        return InstitutionStaffAttendances.save(staffAttendanceData);
    } 

};