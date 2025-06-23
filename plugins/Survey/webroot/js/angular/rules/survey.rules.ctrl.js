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
    vm.populateOptions = populateOptions;
    vm.initEnabled = initEnabled;
    vm.initDependentQuestion = initDependentQuestion;
    vm.saveValue = saveValue;
    vm.canSave = false;
    vm.questions = {};
    vm.originalRules = {};

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
                angular.forEach(vm.questions, function (q) {
                    vm.originalRules[q.id] = angular.copy(q.rule);
                });
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
        if (vm.isDependentInvalid(question)) {
            question.rule.enabled = 0;
        }
    };

    vm.findQuestionById = function(id) {
        return Object.values(vm.questions).find(function(q) {
            return q.id === id;
        });
    };

    vm.isDependentInvalid = function(question) {
        return !question.dependentQuestion ||
            !question.dependentQuestion.choices ||
            question.dependentQuestion.choices.length === 0;
    };

    $scope.$watch(
        function () {
            return JSON.stringify(
                Object.keys(vm.questions).map(function (key) {
                    const q = vm.questions[key];
                    return {
                        id: q.id,
                        rule: q.rule
                    };
                })
            );
        },
        function (newVal, oldVal) {
            vm.canSave = Object.values(vm.questions).some(function (q) {
                const original = vm.originalRules[q.id];
                if (!original) return false;

                return (
                    q.rule.enabled !== original.enabled ||
                    q.rule.dependent_question_id !== original.dependent_question_id ||
                    JSON.stringify(q.rule.show_options || []) !== JSON.stringify(original.show_options || [])
                );
            });
        }
    );


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
        const saveData = [];
        const deleteData = [];

        angular.forEach(vm.questions, function (question) {
            const rule = question.rule || {};
            const surveyQuestionId = question.id;
            const surveyFormId = vm.surveyFormId;

            const isEnabled = rule.enabled == 1 &&
                rule.dependent_question_id &&
                Array.isArray(rule.show_options) &&
                rule.show_options.length > 0;

            const original = vm.originalRules[surveyQuestionId];
            const wasEnabled = original && original.enabled == 1;

            // Push to save list if valid
            if (isEnabled) {
                saveData.push({
                    survey_form_id: surveyFormId,
                    survey_question_id: surveyQuestionId,
                    enabled: rule.enabled,
                    dependent_question_id: rule.dependent_question_id,
                    show_options: JSON.stringify(rule.show_options)
                });
            }

            // Push to delete list if it *was* enabled and is now disabled/invalid
            if (wasEnabled && !isEnabled) {
                deleteData.push({
                    survey_form_id: surveyFormId,
                    survey_question_id: surveyQuestionId
                });
            }
        });

        UtilsSvc.isAppendSpinner(true, 'survey-rules-table');

        // Run saves and deletes in parallel
        $q.all([
            SurveyRulesSvc.saveData(saveData),
            SurveyRulesSvc.deleteData(deleteData)
        ])
            .then(function () {
                vm.getQuestionsFromSection(vm.surveyFormId, vm.sectionName);
                AlertSvc.success($scope, "The record has been added successfully.");
                const newHash = 'anchorTop';
                if ($location.hash() !== newHash) {
                    $location.hash(newHash);
                } else {
                    $anchorScroll();
                }
            })
            .catch(function (error) {
                console.error(error);
            })
            .finally(function () {
                UtilsSvc.isAppendSpinner(false, 'survey-rules-table');
            });
    }

}
