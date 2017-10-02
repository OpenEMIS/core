angular.module('sg.tree.ctrl', ['kd-angular-tree-dropdown', 'sg.tree.svc'])
    .controller('SgTreeCtrl', SgTreeController);

SgTreeController.$inject = ['$scope', '$window', 'SgTreeSvc'];

function SgTreeController($scope, $window, SgTreeSvc) {

    $scope.outputFlag = false;
    var Controller = this;

    Controller.inputModelText = [];
    $scope.outputModelText = [];
    Controller.outputValue = null;
    Controller.displayCountry = 0;
    Controller.loaded = false;
    Controller.triggerLoad = triggerLoad;
    $scope.textConfig = {
        noSelection: 'Loading...',
        multipleSelection: '%tree_no_of_item items selected'
    };

    angular.element(document).ready(function () {
        SgTreeSvc.init(angular.baseUrl);
        var userId = JSON.parse(Controller.userId);
        var authArea = [];
        var counter = 0;
        SgTreeSvc.getRecords(Controller.model, userId, Controller.displayCountry, Controller.outputValue, true)
            .then(function(response) {
                Controller.inputModelText = response;
                return SgTreeSvc.translate($scope.textConfig);
            }, function(error){
                console.log(error)
            })
            .then(function(res) {
                $scope.textConfig = res;
            }, function (error) {
                console.log(error);
            });
    });

    $scope.$on("clickEvent", function(event, args) {
        event.stopPropagation();
        triggerLoad();
    });

    function triggerLoad() {
        if (!Controller.loaded) {
            Controller.inputModelText = [];
            Controller.loaded = true;
            var userId = JSON.parse(Controller.userId);
            var authArea = [];
            var counter = 0;
            SgTreeSvc.getRecords(Controller.model, userId, Controller.displayCountry, Controller.outputValue)
            .then(function(response) {
                Controller.inputModelText = response;
                return SgTreeSvc.translate($scope.textConfig);
            }, function(error){
                console.log(error)
            })
            .then(function(res) {
                $scope.textConfig = res;
            }, function (error) {
                console.log(error);

            });
        }
    }

    $scope.$watch('outputModelText', function (newValue) {
        if (typeof newValue !== 'undefined' && newValue.length > 0) {
            Controller.outputValue = newValue[0].id;
        }
    });
}