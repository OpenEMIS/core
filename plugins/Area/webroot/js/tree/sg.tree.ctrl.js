angular.module('sg.tree.ctrl', ['kd-angular-tree-dropdown', 'sg.tree.svc'])
    .controller('SgTreeCtrl', SgTreeController);

SgTreeController.$inject = ['$scope', '$window', 'SgTreeSvc'];

function SgTreeController($scope, $window, SgTreeSvc) {

    $scope.outputFlag = false;
    var Controller = this;

    Controller.inputModelText = [];
    $scope.outputModelText = [];
    Controller.outputValue = null;

    angular.element(document).ready(function () {
        SgTreeSvc.init(angular.baseUrl);
        if (Controller.outputValue != null) {
            // $scope.outputModelText.push(Controller.outputValue);
        }
        var authorisedArea = JSON.parse(Controller.authorisedArea);
        var authArea = [];
        var counter = 0;
        SgTreeSvc.getRecords(Controller.model, authorisedArea)
        .then(function(response) {
            Controller.inputModelText = response;
        }, function(error){

        });
    });

    $scope.$watch('outputModelText', function (newValue) {
        if (typeof newValue !== 'undefined' && newValue.length > 0) {
            Controller.outputValue = newValue[0].id;
        }
    });
}