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

                // targetCtrl.registerOnChangeTargets(attr.caId);
		        // console.log('caOnChangeTargetElementCallback');
			    scope.$on('onChangeCompleteCallback', function (event, target, sourceScope, sourceElem, sourceAttr, ctrl) {
			    	if (target=='assessment_items') {
			    		// console.log(ctrl.onChangeTargets);
			        	// console.log('onChangeComplete', target, scope, sourceElem, sourceAttr, ctrl);
			        	// ctrl.alert(sourceScope, sourceElem, sourceAttr);
        	            var target
			            var customAttr = {
				            'caOnChangeElement': true,
							'caOnChangeSourceUrl': '/restful/assessment-assessmentgradingtypes.json?_finder=visible,list',
							'caOnChangeTarget': 'assessment_grading_type_id'
						}
		                targetCtrl.registerOnChangeTargets(attr.caId);
	                    targetCtrl.changeOptions(sourceScope, '', customAttr);
	                    console.log(targetCtrl.onChangeTargets['assessment_grading_type_id']);
	                    console.log(ctrl.onChangeTargets['assessment_grading_type_id']);
			    	}
			    });

            }
        };
    })
    ;
