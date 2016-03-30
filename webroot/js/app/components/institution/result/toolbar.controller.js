angular.module('institution.result.toolbar.controller', ['institution.result.service'])
.controller('ToolbarCtrl', function($scope, ResultService) {
    $scope.$parent.editMode = false;

    angular.element(document).ready(function () {
    });

    $scope.onEditClick = function() {
        $scope.$parent.editMode = true;
    };

    $scope.onSaveClick = function() {
        $scope.$parent.editMode = false;
        // ResultService.saveData($scope);
    };
});
