angular.module('institution.result.toolbar.controller', ['institution.result.service'])
.controller('ToolbarCtrl', function($scope, ResultService) {
    angular.element(document).ready(function () {
        // default is view mode
        $scope.$parent.editMode = false;
    });

    $scope.onEditClick = function() {
        $scope.$parent.editMode = true;
    };

    $scope.onBackClick = function() {
        $scope.$parent.editMode = false;
    };

    $scope.onSaveClick = function() {
        $scope.$parent.editMode = false;
    };
});
