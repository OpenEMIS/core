angular.module('institution.result.toolbar.controller', [])
.controller('ToolbarCtrl', function($scope) {
    $scope.$parent.editMode = false;

    angular.element(document).ready(function () {
    });

    $scope.onEditClick = function() {
        $scope.$parent.editMode = true;
    };

    $scope.onSaveClick = function() {
        $scope.$parent.editMode = false;
    };
});
