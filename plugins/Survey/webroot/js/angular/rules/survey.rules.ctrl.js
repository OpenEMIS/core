angular
    .module('survey.rules.ctrl', ['utils.svc', 'alert.svc', 'survey.rules.svc', 'angular.chosen'])
    .controller('SurveyRulesCtrl', SurveyRulesController);

SurveyRulesController.$inject = ['$scope', '$anchorScroll', '$location', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'SurveyRulesSvc'];

function SurveyRulesController($scope, $anchorScroll, $location, $filter, $q, UtilsSvc, AlertSvc, SurveyRulesSvc) {

    var vm = this;
    $scope.action = 'index';
    var filterValue = '';
    var surveyFormId = UtilsSvc.requestQuery('survey_form_id');
    vm.surveyFormId = surveyFormId;

    // Functions
    vm.getSurveySection = getSurveySection;
    vm.getQuestionsFromSection = getQuestionsFromSection;
    vm.onChangeSection = onChangeSection;
    vm.filterByOrderAndType = filterByOrderAndType;
    vm.filterChoiceBySurveyQuestionId = filterChoiceBySurveyQuestionId;
    vm.populateOptions = populateOptions;
    vm.initEnabled = initEnabled;
    vm.initDependentQuestion = initDependentQuestion;
    vm.saveValue = saveValue;

    // Initialisation
    angular.element(document).ready(function() {
        SurveyRulesSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        SurveyRulesSvc.getSurveyForm(0)
        .then(function(response)
        {
            var formData = response.data;
            var options = [];
            for(i = 0; i < formData.length; i++)
            {
                options.push({text: formData[i].name.toString(), value: formData[i].id});
            }

            vm.surveyFormOptions = options;
            if (!isNaN(surveyFormId) && surveyFormId !=0) {
                vm.surveyFormId = surveyFormId;
            } else {
                vm.surveyFormId = options[0].value;
            }
            vm.getSurveySection(vm.surveyFormId);
        }, function(error)
        {
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendLoader(false);
        })
        ;
    });

    function getSurveySection(surveyFormId) {
        SurveyRulesSvc.getSection(surveyFormId)
        .then(function(sections) {
            var sectionData = sections.data;
            var options = [];
            for(i = 0; i < sectionData.length; i++)
            {
                if (sectionData[i].section.toString() == "") {
                    options.push({text: "No Section", value: sectionData[i].section});
                } else {
                    options.push({text: sectionData[i].section.toString(), value: sectionData[i].section});
                }
            }
            vm.surveySectionOptions = options;
            vm.sectionName = options[0].value;
            var sectionName = vm.sectionName;
            vm.getQuestionsFromSection(surveyFormId, sectionName);
        });
    }

    function getQuestionsFromSection(surveyFormId, sectionName) {
        UtilsSvc.isAppendSpinner(true, 'survey-rules-table');
        SurveyRulesSvc.getQuestions(surveyFormId, sectionName)
        .then(function(response)
        {
            var surveyQuestions = [];
            var rules = [];
            const mobSc=767, tabletSc=1280, laptopSc=1366, macSc=1500, desktopSc=1800, largerDesktopSc=1920;
            // console.log(response.data);
            
                for(i = 0; i < response.data.length; i++) {
                    question = response.data[i];
                    var shortName = question.name;
                    var number = i + 1;
                   
                    /* to fix text length in dropdown POCOR-3331*/
                    if ((window.innerWidth <= mobSc) && (shortName.length > 30)) {
                        shortName = shortName.substring(0, 29) + '...';
                    } else if (shortName.length > 96 && window.innerWidth <= tabletSc) {
                        shortName = shortName.substring(0, 96) + '...';
                    } else if ((shortName.length > 110) && (window.innerWidth <= laptopSc)) {
                        shortName = shortName.substring(0, 110) + '...';
                    } else if ((shortName.length > 120)&& (window.innerWidth <= macSc)) {
                        shortName = shortName.substring(0, 120) + '...';
                    } else if ((shortName.length > 150) && (window.innerWidth <= desktopSc)) {
                        shortName = shortName.substring(0, 150) + '...';
                    } else if ((shortName.length > 170)  && (window.innerWidth <= largerDesktopSc)) {
                        shortName = shortName.substring(0, 170) + '...';
                    }

                    var rule = {
                        enabled: 0,
                        dependent_question_id: undefined,
                        show_options: undefined
                    };
                    if (question.survey_rule_enabled != null) {
                        rule = {
                            enabled: question.survey_rule_enabled,
                            dependent_question_id: question.dependent_question,
                            show_options: JSON.parse(question.show_options)
                        }
                    }
                    surveyQuestions[i] = {
                        no: number,
                        survey_question_id: question.survey_question_id,
                        name: question.name,
                        short_name: number + '. ' + shortName,
                        order: question.order,
                        field_type: question.custom_field.field_type,
                        rule: rule
                    };
                }
            
            vm.surveyQuestions = surveyQuestions;
        }, function(error) {
            console.log(error);
        })
        .finally(function(){
            UtilsSvc.isAppendSpinner(false, 'survey-rules-table');
        });
    }

    function onChangeSection(sectionName) {
        vm.getQuestionsFromSection(vm.surveyFormId, sectionName);
    }

    function filterByOrderAndType(order) {
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

    function filterChoiceBySurveyQuestionId(surveyQuestionId) {
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

    function populateOptions() {
        SurveyRulesSvc.getShowIfChoices(vm.surveyFormId, vm.sectionName)
        .then(function(response)
        {
            vm.questionOptions = response.data;
        });
    }

    function initEnabled(question) {
        var no = question.no;
        vm.enabled[no] = parseInt(question.rule.enabled);
    }

    function initDependentQuestion(question) {
        var no = question.no;
        vm.dependentQuestion[no] = parseInt(question.rule.dependent_question_id);
    }

    function saveValue() {
    	var questionIds = vm.questionId;
    	var enabled = vm.enabled;
    	var dependentQuestions = vm.dependentQuestion;
    	var dependentOptions = vm.dependentOptions;
        var data = [];
        angular.forEach(questionIds, function(surveyQuestionId, key) {
        	if (dependentQuestions.hasOwnProperty(key)) {
        		if (dependentOptions.hasOwnProperty(key)) {
        			var enableStatus = enabled[key];
        			if (enableStatus == undefined || enableStatus == 0) {
        				enableStatus = "0";
        			}
        			var dependentQuestionId = dependentQuestions[key];
        			var options = JSON.stringify(dependentOptions[key]);

        			var data = {
        				survey_form_id: vm.surveyFormId,
        				enabled: enableStatus,
        				survey_question_id: surveyQuestionId,
        				dependent_question_id: dependentQuestionId,
        				show_options: options
        			};
        			this.push(data);
        		}
        	}
		}, data);
        UtilsSvc.isAppendSpinner(true, 'survey-rules-table');
		SurveyRulesSvc.saveData(data)
        .then(function (response){
            vm.getQuestionsFromSection(vm.surveyFormId, vm.sectionName);
            AlertSvc.success($scope, "The record has been added successfully.");
            var newHash = 'anchorTop';
            if ($location.hash() !== newHash) {
              $location.hash(newHash);
            } else {
              $anchorScroll();
            }
        }, function(error){
            console.log(error);
        })
        .finally(function() {
            UtilsSvc.isAppendSpinner(false, 'survey-rules-table');
        });

    }

}