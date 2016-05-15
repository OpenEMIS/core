angular
    .module('survey.rules.ctrl', ['utils.svc', 'alert.svc', 'survey.rules.svc'])
    .controller('SurveyRulesCtrl', SurveyRulesController);

SurveyRulesController.$inject = ['$scope', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'SurveyRulesSvc'];



function SurveyRulesController($scope, $filter, $q, UtilsSvc, AlertSvc, SurveyRulesSvc) {
    
    var vm = this;
    $scope.action = 'index';
    var filterValue = '';
    var surveyFormId = UtilsSvc.requestQuery('survey_form_id');
    vm.surveyFormId = surveyFormId;
    
    // Initialisation
    angular.element(document).ready(function() 
    {
        SurveyRulesSvc.init(angular.baseUrl);

        SurveyRulesSvc.getSurveyForm(surveyFormId)
        .then(function(response) 
        {
            var formData = response.data;
            var options = [];
            for(i = 0; i < formData.length; i++) 
            {   
                options.push({text: formData[i].name.toString(), value: formData[i].id});
            }
            
            vm.surveyFormName = options[0].text;
            vm.surveyFormId = options[0].value; 

            SurveyRulesSvc.getSection(surveyFormId)
            .then(function(sections)
            {
                var sectionData = sections.data;
                options = [];
                for(i = 0; i < sectionData.length; i++) 
                {   
                    options.push({text: sectionData[i].section.toString(), value: sectionData[i].section});
                }
                vm.surveySectionOptions = options;
                vm.sectionName = options[0].value;
                var sectionName = vm.sectionName;
                vm.getQuestionsFromSection(surveyFormId, sectionName);
            });
        }, function(error) 
        {
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        ;
    });

    vm.getQuestionsFromSection = function(surveyFormId, sectionName) 
    {
        SurveyRulesSvc.getQuestions(surveyFormId, vm.sectionName)
        .then(function(response)
        {   
            var surveyQuestions = [];
            var rules = [];
            // console.log(response.data);

            for(i = 0; i < response.data.length; i++) {
                question = response.data[i];
                var shortName = question.name;
                var number = i + 1;
                if (shortName.length > 30) {
                    shortName = number + '. ' + shortName.substring(0,29)+'...';
                }

                var rule = {
                	id: null,
                	enabled: 0,
                	dependent_question_id: undefined,
                	show_options: undefined
                };
                if (question.survey_rule_id != null) {
                    rule = {
                    	id: question.survey_rule_id,
                        enabled: question.survey_rules_enabled,
                        dependent_question_id: question.dependent_question,
                        show_options: JSON.parse(question.show_options)
                    }
                }
                surveyQuestions[i] = {
                    no: number,
                    survey_question_id: question.survey_question_id,
                    name: question.name,
                    short_name: shortName,
                    order: question.order,
                    field_type: question.custom_field.field_type,
                    rule: rule
                };
            }
            vm.surveyQuestions = surveyQuestions;
        });
    }

    vm.onChangeSection = function(sectionName) {
        vm.getQuestionsFromSection(surveyFormId, sectionName);
    }

    vm.filterByOrderAndType = function(order) {
        return function (item) {
            if (item.order < order) {
                if (item.field_type == "DROPDOWN") {
                    return true;
                }
                return false;
            }
            return false;
        }
    }

    vm.filterChoiceBySurveyQuestionId = function(surveyQuestionId) {
        return function (item) {
            if (surveyQuestionId == '' || surveyQuestionId == undefined) {
                return false;
            } else if (item.survey_question_id == surveyQuestionId) {
                return true;
            } else {
                return false;
            }
        }
    }

    vm.populateOptions = function() {
        SurveyRulesSvc.getShowIfChoices(vm.surveyFormId, vm.sectionName)
        .then(function(response)
        {
            vm.questionOptions = response.data;
        });
    }

    vm.saveValue = function() {
    	var ruleId = vm.ruleId;
    	var questionIds = vm.questionId;
    	var enabled = vm.enabled;
    	var dependentQuestions = vm.dependentQuestion;
    	var dependentOptions = vm.dependentOptions;
        var log = [];
        angular.forEach(questionIds, function(surveyQuestionId, key) {
        	if (dependentQuestions.hasOwnProperty(key)) {
        		if (dependentOptions.hasOwnProperty(key)) {
        			var enableStatus = enabled[key];
        			if (enableStatus == undefined) {
        				enableStatus = 0;
        			}
        			var dependentQuestionId = dependentQuestions[key];
        			var options = JSON.stringify(dependentOptions[key]);

        			var data = {};
        			if (ruleId[key] != null) {
        				data = {
        					id: ruleId[key],
	        				survey_form_id: vm.surveyFormId,
	        				enabled: enableStatus, 
	        				survey_question_id: surveyQuestionId, 
	        				dependent_question_id: dependentQuestionId, 
	        				show_options: options
	        			};
        			} else {
        				data = {
	        				survey_form_id: vm.surveyFormId,
	        				enabled: enableStatus, 
	        				survey_question_id: surveyQuestionId, 
	        				dependent_question_id: dependentQuestionId, 
	        				show_options: options
	        			};
        			}
        			
        			this.push(data);
        		}
        	}
		}, log);
		SurveyRulesSvc.saveData(log);
    }

}