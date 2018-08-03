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
        saveStaffAttendance:saveStaffAttendance,
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

        if(action == 'edit') {
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
                //here maybe can set the blur event
                var timepickerControl = $('#' + timepickerId).timepicker({formatTime:'H i s'});
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

            // inputElement.addEventListener('blur', function(event) {
            //     console.log('blur event');
            //     // save logic
            //     saveStaffAttendance(data)
            //     .then(
            //         function(response) {
            //             console.log('SAVING SUCCESS LEY CHECK DB');
            //         },
            //         function(error) {
            //             console.log(error);
            //         }
            //     )
            //     .finally(function() {
            //         api.refreshCells();
            //     });
            // });

            spanElement.appendChild(iconElement);
            inputDivElement.appendChild(inputElement);
            inputDivElement.appendChild(spanElement);
            divElement.appendChild(inputDivElement);

            return divElement;
        } else {
            return "-";

        }
    }

    function getEndTimeElement(params) {
        var action = params.context.action;
        var data = params.data;
        var rowIndex = params.rowIndex;
        var timepickerId = 'time-out-' + rowIndex;

        if(action == 'edit') {
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
                var timepickerControl = $('#' + timepickerId).timepicker({"defaultTime":"07:00 AM"});
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

            inputElement.addEventListener('blur', function(event) {
                console.log('blur event');

                // save logic
            });

            spanElement.appendChild(iconElement);
            inputDivElement.appendChild(inputElement);
            inputDivElement.appendChild(spanElement);
            divElement.appendChild(inputDivElement);

            return divElement;
        } else {
            return "-";

        }
    }

    function saveStaffAttendance(data, context) {
        var staffAttendanceData = {
            staff_id: data.staff_id,
            institution_id: data.institution_id,
            academic_period_id: data.academic_period_id,
            date: data.date,
            start_time: data.start_time,
            end_time: data.end_time,
        };
        return InstitutionStaffAttendances.save(staffAttendanceData);
    } 

};