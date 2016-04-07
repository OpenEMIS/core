angular.module('assessmentAdminModule', [])
    .directive('caOnChangeTargetElementCallback', function() {
        return {
            restrict: 'A',
            controller: 'caModuleCtrl',
            controllerAs: 'targetCtrl',
            bindToController: {
            	onChangeTargets: '='
            },
            link: function(scope, elem, attr, targetCtrl) {

            	scope.gradingTypes = {};

			    scope.$on('onChangeCompleteCallback', function (event, target, sourceScope, sourceAttr) {
			    	if (target=='assessment_items') {
        	            var target
			            var customAttr = {
				            'caOnChangeElement': true,
							'caOnChangeSourceUrl': '/restful/assessment-assessmentgradingtypes.json?_finder=visible,list',
							'caOnChangeTarget': 'assessment_grading_type_id'
						}
		                targetCtrl.registerOnChangeTargets(attr.caId);

	                    targetCtrl.changeOptions(sourceScope, '', customAttr);
	                    // console.log(targetCtrl.onChangeTargets['assessment_grading_type_id']);
			    	} else if (target=='assessment_grading_type_id') {
			    		scope.gradingTypes = targetCtrl.onChangeTargets['assessment_grading_type_id'];
	                    // console.log(targetCtrl.onChangeTargets['assessment_grading_type_id']);
			    	}

			    });

            }
        };
    })
    ;
