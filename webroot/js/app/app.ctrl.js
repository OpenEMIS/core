angular.module('app.ctrl', ['app.svc', 'utils.svc'])
	.controller('AppCtrl', function($rootScope, $scope) {
    	$scope.getSplitterElements = function (_response){
    		$scope.splitElems = _response;
    	};
  		$scope.splitterDragCallback = function (_response){
  			console.log("From AppCtrl >> splitterDragCallback = "+ _response);

  			$.each($('.highchart'), function(key, group) {
                $(group).highcharts().reflow();
            });
  		}
    });

