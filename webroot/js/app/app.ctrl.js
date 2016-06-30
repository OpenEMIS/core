angular.module('app.ctrl', ['app.svc', 'utils.svc'])
    .controller('AppCtrl', function($rootScope, $scope) {
        $scope.getSplitterElements = function(_response) {
            $scope.splitElems = _response;
        };

        $rootScope.$on('onSplitterResize', function() {
            $.each($('.highchart'), function(key, group) {
                $(group).highcharts().reflow();
            });
            console.log("on spliter resize");
        });
    });