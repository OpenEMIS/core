angular.module('sgtreecontroller')
    .controller('SgTreeCtrl', function($scope, $window) {
        $scope.outputFlag = false;
        $scope.inputModelText = [];

        $scope.setTreeInputModelText = function (jsonInput) {
            $scope.inputModelText = JSON.parse(jsonInput);
        }
    });