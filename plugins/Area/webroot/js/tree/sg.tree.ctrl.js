angular.module('sg.tree.ctrl', ['kd-angular-tree-dropdown', 'sg.tree.svc'])
    .controller('SgTreeCtrl', SgTreeController);

SgTreeController.$inject = ['$scope', '$window', 'SgTreeSvc'];

function SgTreeController($scope, $window, SgTreeSvc) {

    $scope.outputFlag = false;
    var Controller = this;

    $scope.outputModelText = [];
    Controller.outputValue = null;
    Controller.displayCountry = 0;
    Controller.loaded = false;
    Controller.triggerLoad = triggerLoad;
    Controller.triggerOnChange = false;
    $scope.textConfig = {
        multipleSelection: '%tree_no_of_item items selected'
    };

    angular.element(document).ready(function () {
        SgTreeSvc.init(angular.baseUrl);
        var userId = JSON.parse(Controller.userId);
        var authArea = [];
        var counter = 0;
        SgTreeSvc.getRecords(Controller.model, userId, Controller.displayCountry, Controller.outputValue, true)
            .then(function(response) {
                if (angular.isDefined(response[1]) && angular.isDefined(response[1].name)) {
                    $scope.textConfig['noSelection'] = response[1].name;
                }
                return SgTreeSvc.translate($scope.textConfig);
            }, function(error){
                console.log(error)
            })
            .then(function(res) {
                $scope.textConfig = res;
                console.log('document ready res', res);
            }, function (error) {
                console.log(error);
            });
        console.log('document ready');
    });

    function triggerLoad(refreshList) {
        // run ajax call to get parentData. Then pass it to refreshList(_response) callback function.
        // eg: assign parentData to _pData.
        if (!Controller.loaded) {
            Controller.loaded = true;
            var userId = JSON.parse(Controller.userId);
            SgTreeSvc.getRecords(Controller.model, userId, Controller.displayCountry, Controller.outputValue)
            .then(function(response) {
                refreshList(response);
                return SgTreeSvc.translate($scope.textConfig);
            }, function(error){
                console.log(error)
            });
        }
    }

     $scope.$watch('outputModelText', function (newValue, oldValue) {
        if (typeof newValue !== 'undefined' && newValue.length > 0) {
            Controller.outputValue = newValue[0].id;
            if (Controller.triggerOnChange) {
                setTimeout(function() {
                    if (oldValue.length != 0 && Controller.outputValue != null && Controller.outputValue != oldValue[0].id) {
                        $('#reload').val('changeAreaEducation').click();
                        return false;
                    }
                }, 1);
            }
        }
    });
}
