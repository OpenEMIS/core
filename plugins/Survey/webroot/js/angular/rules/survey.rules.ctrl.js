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
                if (shortName.length > 30) {
                    shortName = shortName.substring(0,29)+'...';
                }
                surveyQuestions[i] = {
                    no: i+1,
                    survey_question_id: question.survey_question_id,
                    name: question.name,
                    short_name: shortName,
                    order: question.order,
                    field_type: question.custom_field.field_type
                };
                if (question.survey_rules_enabled != null) {
                    rules[question.survey_question_id] = {
                        enabled: question.survey_rules_enabled,
                        dependent_question_id: question.dependent_question,
                        show_options: question.show_options
                    }
                }
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
            console.log(response.data);
            vm.questionOptions = response.data;
            console.log(response.data);
        });
    }

    vm.saveValue = function() {
        
    }

}