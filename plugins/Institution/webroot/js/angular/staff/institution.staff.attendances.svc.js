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
            .find('AllStaffAttendances', extra)
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
            field: "time_in",
            menuTabs: [],
            cellRenderer: function(params) {
                // console.log('params', params);
                if (angular.isDefined(params.context.action)) {
                    // var action = params.context.action;
                    // var data = params.data;
                    // var rowIndex = params.rowIndex;
                    var timeIn = params.data.InstitutionStaffAttendances.time_in;
                    return getTimeInElement(params);
                }
            }
        });

        columnDefs.push({
            headerName: "Leaves",
            field: "StaffLeave",
            menuTabs: [],
            cellRenderer: function(params) {
                // console.log('params', params);
                if (angular.isDefined(params.context.action)) {
                    // var action = params.context.action;
                    // var data = params.data;
                    // var rowIndex = params.rowIndex;
                    var StaffLeave = params.data.StaffLeave;
                    return getStaffLeaveElement(params);
                }
            }
        });
        // comments for attendance
        columnDefs.push({
            headerName: "Comments",
            field: "",
            menuTabs: [],
            filter: "text",
            // cellRenderer: function(params) {
            //     console.log('params', params);
            //     if (angular.isDefined(params.context.action)) {
            //         // var action = params.context.action;
            //         // var data = params.data;
            //         // var rowIndex = params.rowIndex;
            //         var StaffLeave = params.data.StaffLeave;
            //         return getStaffLeaveElement(params);
            //     }
            // }
        });
        // columnDefs.push({
        //     headerName: "Time out",
        //     field: "time_out",
        //     menuTabs: [],
        //     cellRenderer: function(params) {
        //         if (angular.isDefined(params.context.action)) {
        //             // var action = params.context.action;
        //             // var data = params.data;
        //             // var rowIndex = params.rowIndex;
        //             var timeOut = params.data.InstitutionStaffAttendances.time_out;
        //             return getTimeOutElement(params);
        //         }
        //     }
        // });

        return columnDefs;
    }

    function getStaffLeaveElement(params) {
        var staffLeaves = params.data.StaffLeave;
        var data = '';
        var leaveIndexURL = params.data.url;
        if (staffLeaves.length > 0) {
            angular.forEach(staffLeaves, function(staffLeave) {
                // console.log(staffLeave);
                var statusId = staffLeave.status_id;
                var leaveTypeId = staffLeave.staff_leave_type_id;
                var start_time = staffLeave.start_time;
                var end_time = staffLeave.end_time;
                var full_day = staffLeave.full_day;
                var leaveStatusName = staffLeave._matchingData.Statuses.name;
                var leaveTypeName = staffLeave._matchingData.StaffLeaveTypes.name;
                data += '<i class="fa kd-attendance"></i> <font color="#CC5C5C">'+leaveTypeName + '</font><br>';

                if (!full_day){
                    data += start_time + '<br>' + end_time + '<br>';
                }
                // data += 'end of an index <br>';
            });
            data += '<i class="fa fa-file-text" style=" color: #FFFFFF; background-color:  #6699CC; border: 1px solid #6699CC;"></i><a href= "'+leaveIndexURL+ '"target="_blank"> View Details</a>';
        } else {
            // console.log('none');
            data = '-';
        }
        return data;
    }

    function getTimeInElement(params) {
        var action = params.context.action;
        var data = params.data;
        var rowIndex = params.rowIndex;
        var timepickerId = 'time-in-' + rowIndex;
        var timeIn = params.data.InstitutionStaffAttendances.time_in;
        var timeOut = params.data.InstitutionStaffAttendances.time_out;
        var scope = params.context.scope;
        if(action == 'edit') {
            if(timeIn == null){
                timeIn = 'current';
            }else{
                timeIn = convert12Timeformat(timeIn);
                // console.log(timeIn);
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
                    // console.log('saving start time.........');
                    // console.log(e.time);
                    // convertTimeformat(e.time.hours, e.time.minutes, e.time.seconds);
                    var time_in = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
                    // console.log(time_in);
                    // data.InstitutionStaffAttendances.start_time = start_time;
                    saveStaffAttendanceTimeIn(data, params, time_in)
                    .then(
                        function(response) {
                            // console.log(response.data.error.length);
                            if(response.data.error.length == 0){
                                AlertSvc.success(scope, 'Time in record successfully saved.');
                                data.isNew = false;
                                data.InstitutionStaffAttendances.time_in = time_in;
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
                $('#' + timepickerId).timepicker();
            });

            spanElement.appendChild(iconElement);
            inputDivElement.appendChild(inputElement);
            inputDivElement.appendChild(spanElement);
            divElement.appendChild(inputDivElement);

            return divElement;
        } else {
            var time = '';
            if(timeIn == null){
                time = '-';
            }else{
                time = convert12Timeformat(timeIn) + '<br>' + convert12Timeformat(timeOut);
            }

            return time;
        }
    }

    // function getTimeOutElement(params) {
    //     var action = params.context.action;
    //     var data = params.data;
    //     var rowIndex = params.rowIndex;
    //     var timepickerId = 'time-out-' + rowIndex;
    //     var timeOut = params.data.InstitutionStaffAttendances.time_out;
    //     var scope = params.context.scope;
    //     if(action == 'edit') {
    //         if(timeOut == null){
    //             timeOut = 'current';
    //         }else{
    //             timeOut = convert12Timeformat(timeOut);
    //             // console.log(endTime);
    //         }
    //         var divElement = document.createElement('div');
    //         divElement.setAttribute('class', 'input');
    //         var inputDivElement = document.createElement('div');
    //         inputDivElement.setAttribute('id', timepickerId);
    //         inputDivElement.setAttribute('class', 'input-group time');
    //         var inputElement = document.createElement('input');
    //         inputElement.setAttribute('class', 'form-control');
    //         var spanElement = document.createElement('span');
    //         spanElement.setAttribute('class', 'input-group-addon');
    //         var iconElement = document.createElement('i');
    //         iconElement.setAttribute('class', 'glyphicon glyphicon-time');

    //         setTimeout(function(event) {
    //             var timepickerControl = $('#' + timepickerId).timepicker({defaultTime: timeOut});
    //                 $('#' + timepickerId).timepicker().on("hide.timepicker", function (e) {
    //                 console.log('saving end-time.........');
    //                 // console.log(e.time);
    //                 // convertTimeformat(e.time.hours, e.time.minutes, e.time.seconds);
    //                 var time_out = convert24Timeformat(e.time.hours, e.time.minutes, e.time.seconds, e.time.meridian);
    //                 console.log(time_out);
    //                 saveStaffAttendanceTimeOut(data, params, time_out)
    //                 .then(
    //                     function(response) {
    //                         if(response.data.error.length == 0){
    //                             AlertSvc.success(scope, 'Time Out record successfully saved.');
    //                             data.isNew = false;
    //                             data.InstitutionStaffAttendances.time_out = time_out;
    //                         }else{
    //                              AlertSvc.error(scope, response.data.error.time_in.ruleCompareTime);
    //                         }
    //                     },
    //                     function(error) {
    //                         console.log(error);
    //                         AlertSvc.error(scope, 'There was an error when saving record');
    //                     }
    //                 )
    //                 .finally(function() {
    //                     var refreshParams = {
    //                         columns: [
    //                             'time_out',
    //                         ],
    //                         force: true
    //                     };
    //                     params.api.refreshCells(refreshParams);
    //                 });
    //             });
    //             $(document).on('DOMMouseScroll mousewheel scroll', function() {
    //                 console.log('scroll1');
    //                 window.clearTimeout(t);
    //                 t = setTimeout(function(event) {
    //                     timepickerControl.timepicker('place');
    //                 });
    //             });
    //         }, 1);


    //         inputElement.addEventListener('click', function(event) {
    //             $('#' + timepickerId).timepicker();
    //         });

    //         spanElement.appendChild(iconElement);
    //         inputDivElement.appendChild(inputElement);
    //         inputDivElement.appendChild(spanElement);
    //         divElement.appendChild(inputDivElement);

    //         return divElement;
    //     } else {
    //         if(timeOut == null){
    //             timeOut = '-';
    //         }else{
    //             timeOut = convert12Timeformat(timeOut);
    //         }
    //         return timeOut;
    //     }
    // }

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
        // console.log(time);
        var timeSplit = time.split(":");
        hours = timeSplit[0];
        minutes = timeSplit[1];
        seconds = timeSplit[2];
        // console.log(timeSplit);
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
        var sMinutes = minutes.toString();
        // var sSeconds = seconds.toString();
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
        if(!data.isNew && timeIn != null){
            // console.log('is not new entity');
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        }else{
            // console.log('is new entity');
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
        if(!data.isNew && timeOut != null){
            // console.log('is not new entity');
            return InstitutionStaffAttendances.edit(staffAttendanceData);
        }else{
            // console.log('is new entity');
            return InstitutionStaffAttendances.save(staffAttendanceData);
        }
    } 

};