angular
    .module('survey.rules.ctrl', ['utils.svc', 'alert.svc', 'survey.rules.svc'])
    .controller('SurveyRulesCtrl', SurveyRulesController);

SurveyRulesController.$inject = ['$scope', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'SurveyRulesSvc'];

function SurveyRulesController($scope, $filter, $q, UtilsSvc, AlertSvc, SurveyRulesSvc) {
    
    var vm = this;
    $scope.action = 'index';
    var filterValue = '';
    var surveyFormId = UtilsSvc.requestQuery('survey_form_id');
    
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
                    order: question.order
                };
            }
            vm.surveyQuestions = surveyQuestions;
        });
    }

    // Updating of the filter for the survey form
    vm.update = function (selectedItem) 
    {
        filterValue = selectedItem;
        console.log(filterValue);
    }

    // busy waiting to watch the action of the page
    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
        }
    });

    vm.onChangeSection = function(sectionName) {
        vm.getQuestionsFromSection(surveyFormId, sectionName);
    }

    vm.getDependentQuestions = function(question) {
        console.log('here');
        var deferred = $q.defer();
        var questionOrder = question.order;
        var sectionName = question.section;
        var surveyFormId = question.survey_form_id;
        SurveyRulesSvc.getDependentQuestions(surveyFormId, sectionName, questionOrder)
        .then(function(response) {
            deferred.resolve(response.data);
        }, function(error) {
            deferred.error(error);
        });
        return deferred.promise;
    }

    vm.onChangeQuestion = function(questionId) {
        // AlertSvc.reset($scope);
        // vm.sectionName = sectionName;
        // SurveyRulesSvc.getQuestions(vm.surveyFormId, sectionName)
        // .then(function(response)
        // {
        //     console.log(questions);
        // });
        console.log(questionId);
        // console.log(sectionName);
        // UtilsSvc.isAppendSpinner(true, 'survey-rules-table');
        // if () {

        // }
    }
}