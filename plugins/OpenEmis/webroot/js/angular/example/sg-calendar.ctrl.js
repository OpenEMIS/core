//Calendar v.1.0.0
(function (){
    'use strict';

angular.module('OE_Styleguide')
    .controller('SgCalendarCtrl', ['$scope', function($scope, $window) {

        $scope.dropdown = [
            // {key: 'year', value: 'year'},
            {key: 'month', value: 'month'},
            {key: 'week', value: 'week'}
        ]

        $scope.events = [];

        $scope.updateImmediately = false;

        $scope.calendarType = 'month';

        $scope.calendarApi = {};

        $scope.viewDate = new Date();

        init();

        function init() {

            setTimeout(function(){
                $scope.events = CALENDAR_EVENT_CONFIG;
            }, 3000);

            $('#calPrevious').click(function(event){
                $scope.calendarApi.previousMonth();
            });

            $('#calToday').click(function(event){
                $scope.calendarApi.goDate();
            });
            
            $('#calNext').click(function(event){
                $scope.calendarApi.nextMonth();
            });
            
            $('#calGo').click(function(event){
                $scope.calendarApi.goDate('2017-01-01');
            });

        }

        $scope.onCalendarTypeChanged = function() {
            $scope.calendarApi.setCalendarType($scope.calendarType);
        }

        $scope.onEventClicked = function(_event) {
            if ( _event.note ) {
                _event.note = 'Editted ' + _event.note;
            }
            _event.badge.label = 'editted label';
            _event.badge.position = 'top';

            if ( _event.id === 1 ) {
                _event.name = 'editted ' + _event.name + ' start date';
                _event.start = '2017-12-01';
            } else if ( _event.id === 2 ) {
                _event.name = 'editted ' + _event.name + ' start date & end date';
                _event.start = '2017-12-01';
                _event.end = '2017-12-02';
            } else {
                _event.name = _event.name + ' editted';
            }
            for ( var i = 0; i < $scope.events.length; i++ ) {
                if ( $scope.events[i].id === _event.id && ($scope.updateImmediately) ) {
                    $scope.events[i] = _event;
                    break;
                }
            }
        }

        var CALENDAR_EVENT_CONFIG = 
        [
            {
                id: 1,
                name: 'Event top end date with long desc',
                note: 'long long text of comment or description of the event. this is the super super super very very long long long description. In fact, it should be the longest one',
                badge: {label: 'Yesterday & Tomorrow', position: 'top', color: 'yellow', highlight: 'grey'},
                start: yesterday(),
                end: tomorrow()
            }, {
                id: 2,
                name: 'Event top end date with long long long long long long super super super super long title',
                note: 'long long text of comment or description of the event.',
                badge: {label: 'Yesterday & Tomorrow', position: 'top', color: 'grey', highlight: 'grey'},
                start: yesterday(),
                end: tomorrow()
            }, {
                id: 3,
                name: 'Event bottom no end date',
                badge: {label: 'Yesterday', position: 'bottom', color: 'grey', highlight: 'grey'},
                start: yesterday()
            },  {
                id: 4,
                name: 'Event bottom no end date',
                badge: {label: 'Yesterday', position: 'bottom', color: 'red', highlight: 'grey'},
                start: yesterday()
            }, {
                id: 5,
                name: 'Event bottom no end date',
                badge: {label: 'Yesterday', position: 'bottom', color: 'red', highlight: 'grey'},
                start: yesterday()
            }, {
                id: 6,
                name: 'Event top end date',
                badge: {label: 'aaaaa', position: 'top', highlight: 'red'},
                start: getToday() + '-02',
                end: getToday() + '-02'
            }, {
                id: 7,
                name: 'Event top long end date',
                badge: {label: 'aaaaa', position: 'top', highlight: 'grey'},
                start: getToday() + '-02',
                end: getToday() + '-02'
            }, {
                id: 8,
                name: 'Event top long end date',
                badge: {label: '3', position: 'top', highlight: 'red'},
                start: getToday() + '-03',
                end: getToday() + '-03'
            }, {
                id: 9,
                name: 'Event top long end date',
                badge: {label: '3-5', position: 'bottom', highlight: 'white'},
                start: getToday() + '-03',
                end: getToday() + '-05'
            }, {
                id: 10,
                name: 'Event top long end date',
                badge: {label: '3-5', position: 'top', highlight: 'grey'},
                start: getToday() + '-03 ',
                end: getToday() + '-05'
            }, {
                id: 11,
                name: 'Event top long end date',
                badge: {label: '20-30', position: 'top', highlight: 'gray'},
                start: getToday() + '-20',
                end: getToday() + '-30'
            }, {
                id: 12,
                name: 'Event bottom long end date',
                badge: {label: '20-30', position: 'top', highlight: 'gray'},
                start: getToday() + '-20',
                end: getToday() + '-30'
            }, {
                id: 13,
                name: 'Event bottom end date',
                badge: {label: '20-30', position: 'bottom', highlight: 'gray'},
                start: getToday() + '-20',
                end: getToday() + '-30'
            }, {
                id: 14,
                name: 'Event bottom long end date',
                badge: {label: '20-30', position: 'bottom', highlight: 'gray'},
                start: getToday() + '-20',
                end: getToday() + '-30'
            }, {
                id: 15,
                name: 'Event bottom long end date',
                badge: {label: '30-30y', position: 'bottom'},
                start: getToday() + '-30',
                end: getToday() + '-30'
            }, {
                id: 16,
                name: 'Event top long end date',
                badge: {label: '01-01', position: 'top'},
                start: getNext() + '-01',
                end: getNext() + '-01'
            }, {
                id: 17,
                name: 'Event bottom long end date',
                badge: {label: '01-01', position: 'top'},
                start: getNext() + '-01',
                end: getNext() + '-01'
            }, {
                id: 18,
                name: 'Event bottom end date',
                badge: {label: '01-01', position: 'top'},
                start: getNext() + '-01',
                end: getNext() + '-01'
            }, {
                id: 19,
                name: 'Event bottom long end date',
                badge: {label: '01-01', position: 'top'},
                start: getNext() + '-01',
                end: getNext() + '-01'
            }, {
                id: 20,
                name: 'Event bottom long end date',
                badge: {label: '01-01', position: 'top'},
                start: getNext() + '-01',
                end: getNext() + '-01'
            }, {
                id: 21,
                name: 'Event top long end date',
                badge: {label: '02-02', position: 'bottom'},
                start: getNext() + '-02',
                end: getNext() + '-02'
            }, {
                id: 22,
                name: 'Event bottom long end date',
                badge: {label: '02-02 Holiday', position: 'bottom'},
                start: getNext() + '-02',
                end: getNext() + '-02'
            }, {
                id: 23,
                name: 'Event bottom end date',
                badge: {label: '02-02 Holiday', position: 'asdasd'},
                start: getNext() + '-02',
                end: getNext() + '-02'
            }, {
                id: 24,
                name: 'Event bottom long end date',
                badge: {label: '02-02 Holiday'},
                start: getNext() + '-02',
                end: getNext() + '-02'
            }, {
                id: 25,
                name: 'Event bottom long end date',
                badge: {label: '02-02 Holiday'},
                start: getNext() + '-02',
                end: getNext() + '-02'
            }, {
                id: 26,
                name: 'Event bottom long end date',
                badge: {label: '29-29 Holiday'},
                start: getPrevious() + '-29',
                end: getPrevious() + '-29'
            }, {
                id: 26,
                name: 'Event bottom long end date',
                badge: {label: '30-30 Holiday'},
                start: getPrevious() + '-30',
                end: getPrevious() + '-30'
            }
        ];

        function getToday() {
            return new Date().getFullYear() + '-' + setMonth(new Date().getMonth() + 1);
        }
        function getNext() {
            return new Date().getFullYear() + '-' + setMonth(new Date().getMonth() + 2);
        }
        function getPrevious() {
            return new Date().getFullYear() + '-' + setMonth(new Date().getMonth());
        }
        function yesterday() {
            return new Date().getFullYear() + '-' + setMonth(new Date().getMonth() + 1) + '-' + setDateString(new Date().getDate() - 1);
        }
        function tomorrow() {
            return new Date().getFullYear() + '-' + setMonth(new Date().getMonth() + 1) + '-' + setDateString(new Date().getDate() + 1);
        }
        function setMonth(month) {
            var m;
            if ( month === 0 ) {
                m = 12;
            } else if ( month > 12 ) {
                m = 1 + (12 - month);
            } else {
                m = month;
            }
            return setDateString(m);
        }
        function setDateString(date) {
            if ( date < 10 ) {
                return '0' + date;
            } else {
                return '' + date;
            }
        }

    }]);
})();