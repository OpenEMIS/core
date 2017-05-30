angular.module('commonEvents',[])
.directive('stopPropagation',function(){
	return {
		restrict: 'A',
	    link: function (scope, element, attr) {
	        element.on('click', function (e) {
	            e.stopPropagation();
	        });
	    }
	}
})