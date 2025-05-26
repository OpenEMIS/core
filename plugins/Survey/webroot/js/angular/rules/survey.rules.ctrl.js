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
    vm.getSurveySections = getSurveySections;
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
            vm.getSurveySections(vm.surveyFormId);
        }, function(error)
        {
            console.error(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendLoader(false);
        })
        ;
    });

    function getSurveySections(surveyFormId) {
        SurveyRulesSvc.getSections(surveyFormId).then(function (response) {
            const sections = response.data || [];

            // Build dropdown options
            const options = sections.map(section => {
                const text = section.section?.toString() || "No Section";
                return {
                    text: text,
                    value: section.section
                };
            });

            vm.surveySectionOptions = options;

            // Use first section if available
            if (options.length > 0) {
                const firstSectionValue = options[0].value;
                vm.sectionName = firstSectionValue;
                vm.getQuestionsFromSection(surveyFormId, firstSectionValue);
            }
        });
    }

    function getQuestionsFromSection(surveyFormId, sectionName) {
        UtilsSvc.isAppendSpinner(true, 'survey-rules-table');

        SurveyRulesSvc.getQuestions(surveyFormId, sectionName)
            .then(function (response) {
                console.log(response)
                const screenLimits = {
                    mobile: 767,
                    tablet: 1280,
                    laptop: 1366,
                    mac: 1500,
                    desktop: 1800,
                    largeDesktop: 1920
                };

                const screenWidth = window.innerWidth;
                const truncateText = (text) => {
                    if (screenWidth <= screenLimits.mobile && text.length > 30) {
                        return text.substring(0, 29) + '...';
                    } else if (screenWidth <= screenLimits.tablet && text.length > 96) {
                        return text.substring(0, 96) + '...';
                    } else if (screenWidth <= screenLimits.laptop && text.length > 110) {
                        return text.substring(0, 110) + '...';
                    } else if (screenWidth <= screenLimits.mac && text.length > 120) {
                        return text.substring(0, 120) + '...';
                    } else if (screenWidth <= screenLimits.desktop && text.length > 150) {
                        return text.substring(0, 150) + '...';
                    } else if (screenWidth <= screenLimits.largeDesktop && text.length > 170) {
                        return text.substring(0, 170) + '...';
                    }
                    return text;
                };

                const questions = response.data.map((question, index) => {
                    const shortName = truncateText(question.name);
                    const questionNumber = index + 1;
                    // console.log(question);
                    const rule = (question.survey_form_id !== surveyFormId)
                        ? {
                            id: null,
                            enabled: 0,
                            dependent_question_id: undefined,
                            show_options: undefined
                        }
                        : (question.survey_rule_enabled != null
                                ? {
                                    id: question.id || null, // optional: if there's an `id` field for the rule
                                    enabled: question.survey_rule_enabled,
                                    dependent_question_id: question.dependent_question,

                                    show_options: (() => { // POCOR-9147
                                        console.log(question);
                                        try {
                                            return JSON.parse(question.show_options);
                                        } catch {
                                            return undefined;
                                        }
                                    })()
                                }
                                : {
                                    id: null,
                                    enabled: 0,
                                    dependent_question_id: undefined,
                                    show_options: undefined
                                }
                        );
                    // console.log(rule);

                    return {
                        no: questionNumber,
                        survey_question_id: question.survey_question_id,
                        name: question.name,
                        short_name: `${questionNumber}. ${shortName}`,
                        order: question.order,
                        field_type: question.custom_field.field_type,
                        rule: rule
                    };
                });

                vm.surveyQuestions = questions;
            })
            .catch(function (error) {
                console.error(error);
            })
            .finally(function () {
                UtilsSvc.isAppendSpinner(false, 'survey-rules-table');
            });
    }

    function onChangeSection(sectionName) {
        vm.sectionName = sectionName;
        // console.log(sectionName);
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

    function populateOptions(dependentQuestionId) {
        SurveyRulesSvc.getShowIfChoices(vm.surveyFormId, vm.sectionName, dependentQuestionId)
        .then(function(response)
        {
            console.log(response);
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
        // console.log(vm);
    	var questionIds = vm.questionId;
    	var enabled = vm.enabled;
    	var dependentQuestions = vm.dependentQuestion;
    	var dependentOptions = vm.dependentOptions;
        var data = [];
        angular.forEach(questionIds, function (surveyQuestionId, key) {
            if (dependentQuestions.hasOwnProperty(key)) {
                if (dependentOptions.hasOwnProperty(key)) {
                    const optionsArray = dependentOptions[key];
                    if (Array.isArray(optionsArray) && optionsArray.length > 0) {
                        var dependentQuestionId = dependentQuestions[key];
                        var options = JSON.stringify(dependentOptions[key]);
                        var data = {
                            survey_form_id: vm.surveyFormId,
                            enabled: enabled[key],
                            survey_question_id: surveyQuestionId,
                            dependent_question_id: dependentQuestionId,
                            show_options: options
                        };
                        this.push(data);
                    }
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
            console.error(error);
        })
        .finally(function() {
            UtilsSvc.isAppendSpinner(false, 'survey-rules-table');
        });

    }

}
