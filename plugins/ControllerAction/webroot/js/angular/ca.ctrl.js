//Controller Action Angular Functions v.1.0.1
angular.module('ca.ctrl', ['ca.common.svc'])
    .controller('caCtrl', function(caCommonSvc, $scope, $q, $location) {

        // $locationProvider.html5mode({ enabled: true, requireBase: false });
        $location.$locationProvider.html5mode({ enabled: true, requireBase: false });
        var absUrl = $location.absUrl();
        var url = $location.url();
        var host = $location.host();
        var path = $location.path();
        console.log("absUrl: "+ absUrl);
        console.log("url: "+ url);
        console.log("host: "+ host);
        console.log("path: "+ path);

        var ctrl = this;

        ctrl.onChangeTargets = {};

        // ctrl.init = function() {
        //     var ctrlMethods = ['changeOptions', 'func2'];
        //     caCommonSvc.init($scope, ctrlMethods);
        // }

        ctrl.registerOnChangeTargets = function(caId) {
            $scope.onChangeTargets[caId] = [];
        };

        ctrl.changeOptions = function(id, attr) {
            var dataType = attr.caOnChangeElement;
            var target = attr.caOnChangeTarget;
            var targetUrl = attr.caOnChangeSourceUrl + id;
            var response = caCommonSvc.ajax({url:targetUrl});
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
                    ctrl.onChangeTargets[target] = targetOptions;
                    return true;
                }, function(error) {
                    console.log('Failure...', error);
                });
        };

        ctrl.alert = function(scope, elem, attr) {
            alert('showing off');
        }

        ctrl.addRow = function(scope, elem, attr) {
            var target = attr.caOnClickTarget;
            var targetUrl = attr.caOnClickSourceUrl;
            var response = caCommonSvc.ajax({url:targetUrl});
            response  
                .then(function(data) {
                    scope.$root.$broadcast('onClickComplete', 'addRow', target, data.data);                
                }, function(error) {
                    console.log('Failure...', error);
                });
        };

        // $scope.onReadyFunction = caCommonSvc.onReadyFunction;

    })
    ;
