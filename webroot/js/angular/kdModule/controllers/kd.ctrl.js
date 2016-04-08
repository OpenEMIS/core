//Controller Action Angular Functions v.1.0.1
angular.module('kd.ctrl', ['kd.common.svc'])
    .controller('kdCtrl', function(kdCommonSvc, $scope) {

        var kdCtrl = this;

        // pr($scope.baseUrl);
        kdCommonSvc.baseUrl = $scope.baseUrl;
        kdCommonSvc.ctrl = 'kdCtrl';
        $scope['onChangeTargets'] = {};
        
        kdCtrl.registerOnChangeTargets = function(caId) {
            $scope.onChangeTargets[caId] = [];
        };

        kdCtrl.changeOptions = function(id, attr) {
            var dataType = attr.kdOnChangeElement;
            var target = attr.kdOnChangeTarget;
            var targetUrl = attr.kdOnChangeSourceUrl + id;
            var response = kdCommonSvc.ajax({url:targetUrl});
            response  
                .then(function(data) {
   
                    targetOptions = [];
                    if (dataType=='data') {
                        targetOptions = data.data;
                    } else {
                        for (var id in data.data) {
                            targetOptions.push({"id":id, "name":data.data[id]});
                        }
                    }
                    $scope.onChangeTargets[target] = targetOptions;
                    // return true;
                }, function(error) {
                    console.log('Failure...', error);
                });
        };

        kdCtrl.alert = function(scope, elem, attr) {
            alert('showing off');
        };

        kdCtrl.addRow = function(scope, elem, attr) {
            var target = attr.caOnClickTarget;
            var targetUrl = attr.caOnClickSourceUrl;
            var response = kdCommonSvc.ajax({url:targetUrl});
            response  
                .then(function(data) {
                    scope.$root.$broadcast('onClickComplete', 'addRow', target, data.data);                
                }, function(error) {
                    console.log('Failure...', error);
                });
        };
    })
    ;
