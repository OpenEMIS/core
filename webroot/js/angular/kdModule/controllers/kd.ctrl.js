//Controller Action Angular Functions v.1.0.1
angular.module('kd.ctrl', ['kd.common.svc'])
    .controller('kdCtrl', function(kdCommonSvc, $scope) {

        kdCommonSvc.initController($scope);

    })
    ;
