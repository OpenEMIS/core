angular
    .module('survey.rules.ctrl', ['utils.svc', 'alert.svc', 'survey.rules.svc', 'angular.chosen'])
    .controller('SurveyRulesCtrl', SurveyRulesController);

SurveyRulesController.$inject = ['$scope', '$anchorScroll', '$location', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'SurveyRulesSvc'];

function SurveyRulesController($scope, $anchorScroll, $location, $filter, $q, UtilsSvc, AlertSvc, SurveyRulesSvc) {

    var vm = this;
    $scope.action = 'index';
    var filterValue = '';
    var surveyFormId = UtilsSvc.requestQuery('survey_form_id');
    var sectionId = UtilsSvc.requestQuery('section_id');
    vm.surveyFormId = surveyFormId;
    vm.sectionId = sectionId;

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
    vm.canSave = false;
    vm.questions = {};

    // Initialisation
    angular.element(document).ready(function() {
        SurveyRulesSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        SurveyRulesSvc.getSurveyForms()
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

    function getSurveySections() {
        const surveyFormId = vm.surveyFormId;
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
            if (options.length > 0) {
                if (!isNaN(sectionId) && sectionId != 0) {
                    vm.sectionName = options[sectionId - 1].value;
                } else {
                    vm.sectionName = options[0].value;
                }
                vm.getQuestionsFromSection();
            }
        });
    }

    function getQuestionsFromSection() {
        const surveyFormId = vm.surveyFormId;
        const sectionName = vm.sectionName;
        UtilsSvc.isAppendSpinner(true, 'survey-rules-table');

        SurveyRulesSvc.getQuestions(surveyFormId, sectionName)
            .then(function (response) {
                const questions = response.data || [];

                // Build a map indexed by survey_question_id for fast lookup
                const questionsById = {};
                questions.forEach((q) => {
                    questionsById[q.survey_question_id] = q;
                });

                const formatted = {};
                questions.forEach((question, index) => {
                    const number = index + 1;

                    const shortName = `${number}. ${question.name}`;
                    const fieldType = question.custom_field?.field_type || null;

                    const choices = (question.custom_field?.custom_field_options || []).map(opt => ({
                        id: opt.id,
                        survey_question_choice_name: opt.name
                    }));

                    const ruleData = question.survey_rule || {};
                    let showOptions = [];
                    try {
                        showOptions = JSON.parse(ruleData.show_options || '[]');
                    } catch (e) {
                        showOptions = [];
                    }

                    const rule = {
                        id: ruleData.id || null,
                        enabled: ruleData.enabled || 0,
                        dependent_question_id: ruleData.dependent_question_id || null,
                        show_options: showOptions
                    };

                    const item = {
                        id: question.survey_question_id,
                        name: question.name,
                        short_name: shortName,
                        order: question.order,
                        field_type: fieldType,
                        choices: choices,
                        rule: rule
                    };

                    // If there's a dependent_question_id, attach dependentQuestion
                    if (rule.dependent_question_id) {
                        const dep = questionsById[rule.dependent_question_id];
                        if (dep) {
                            item.dependentQuestion = {
                                id: dep.survey_question_id,
                                name: dep.name,
                                short_name: `${number}. ${dep.name}`,
                                choices: (dep.custom_field?.custom_field_options || []).map(opt => ({
                                    id: opt.id,
                                    survey_question_choice_name: opt.name
                                }))
                            };
                        }
                    }

                    formatted[number] = item;
                });

                vm.questions = formatted;
            })
            .catch(console.error)
            .finally(() => {
                UtilsSvc.isAppendSpinner(false, 'survey-rules-table');
            });
    }


    function onChangeSection() {
        var sectionName = vm.sectionName;
        console.log(sectionName);
        vm.getQuestionsFromSection();
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
    vm.getDependentQuestions = function(currentOrder) {
        return Object.values(vm.questions).filter(function(item) {
            return item.order < currentOrder && item.field_type === 'DROPDOWN';
        });
    };
    vm.updateDependentQuestion = function(question) {
        const selected = vm.findQuestionById(question.rule.dependent_question_id);
        if (question.rule.dependent_question_id) {
            question.dependentQuestion = angular.copy(selected);
        } else {
            question.dependentQuestion = null;
        }
    };

    vm.findQuestionById = function(id) {
        return Object.values(vm.questions).find(function(q) {
            return q.id === id;
        });
    };
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
        if (dependentQuestionId !== undefined && !isNaN(dependentQuestionId)) {
            SurveyRulesSvc.getShowIfChoices(vm.surveyFormId, vm.sectionName, dependentQuestionId)
                .then(function(response)
            {
                console.log(response);
                vm.questionOptions = response.data;
            });
        } else {
            vm.questionOptions = [];        }
    }
    // function populateOptions(dependentQuestionId) {
    //     console.log(dependentQuestionId);
    //     SurveyRulesSvc.getShowIfChoices(vm.surveyFormId, vm.sectionName, dependentQuestionId)
    //     .then(function(response)
    //     {
    //         console.log(response);
    //         vm.questionOptions = response.data;
    //     });
    // }

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
