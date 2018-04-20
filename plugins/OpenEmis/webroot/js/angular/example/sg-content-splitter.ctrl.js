//Content Splitter v.1.0.0

angular.module('OE_Styleguide')
    .controller('SgContentSplitterCtrl', ['$scope', '$window', function($scope, $window) {

        //to access the splitter elements 
        $scope.getSplitterElements = function (_response){
            $scope.splitElems = _response;
        };

        $scope.collapse = "true";
        //On click on responsive button
        $scope.showSplitContentResponsive = function() {  
            $scope.collapse = ($scope.collapse === "true")? "false": "true";
        }

        //For with Button Splitter View
        $scope.showSplitContentOverlay = function() {
            $scope.collapse = ($scope.collapse === "true")? "false": "true";
        }

        //For without Button Splitter
        $scope.removeSplitContentResponsive = function() {
            $scope.collapse = "true";
        }
    }]);