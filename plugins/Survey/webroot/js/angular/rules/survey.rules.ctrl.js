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
    vm.questions = {
        1: {
            id: 1,
            name: 'Do you have age?',
            short_name: '1. Have Age',
            order: 0,
            field_type: 'DROPDOWN',
            choices: [
                { id: 801, survey_question_choice_name: 'Yes' },
                { id: 802, survey_question_choice_name: 'No' }
            ],
            rule: {
                enabled: 0,
                dependent_question_id: null,
                show_options: null
            }
        },
        2: {
            id: 2,
            name: 'What is your age?',
            short_name: '2. Your Age',
            order: 1,
            rule: {
                enabled: 1,
                dependent_question_id: 1,
                show_options: [801, 802]
            },
            dependentQuestion:
                {
                    id: 1,
                    name: 'Do you have age?',
                    short_name: '1. Do age',
                    choices: [
                        { id: 801, survey_question_choice_name: 'Yes' },
                        { id: 802, survey_question_choice_name: 'No' }
                    ]
                }

        },
        3: {
            id: 3,
            name: 'Do you have name?',
            short_name: '3. Have Name',
            order: 2,
            field_type: 'DROPDOWN',
            choices: [
                { id: 803, survey_question_choice_name: 'Ha' },
                { id: 804, survey_question_choice_name: 'Yok' }
            ],
            rule: {
                enabled: 0,
                dependent_question_id: null,
                show_options: null
            }
        },
        4: {
            id: 4,
            name: 'What is your name?',
            short_name: '4. Your name',
            order: 3,
            rule: {
                enabled: 1,
                dependent_question_id: 3,
                show_options: [803, 804]
            },
            dependentQuestion:
                {
                    id: 3,
                    name: 'Do you have name?',
                    short_name: '3. Do name',
                    choices: [
                        { id: 803, survey_question_choice_name: 'Ha' },
                        { id: 804, survey_question_choice_name: 'Yok' }
                    ]
                }
        }
    };

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
                const screenWidth = window.innerWidth;

                const truncateText = (text) => {
                    const limits = [
                        { width: 767, length: 30 },
                        { width: 1280, length: 96 },
                        { width: 1366, length: 110 },
                        { width: 1500, length: 120 },
                        { width: 1800, length: 150 },
                        { width: 1920, length: 170 }
                    ];

                    for (const limit of limits) {
                        if (screenWidth <= limit.width && text.length > limit.length) {
                            return text.substring(0, limit.length) + '...';
                        }
                    }

                    return text;
                };

                const mapRule = (question) => {
                    if (question.survey_form_id !== surveyFormId) {
                        return {
                            id: null,
                            enabled: 0,
                            dependent_question_id: undefined,
                            show_options: undefined
                        };
                    }

                    if (question.survey_rule_enabled == null) {
                        return {
                            id: null,
                            enabled: 0,
                            dependent_question_id: undefined,
                            show_options: undefined
                        };
                    }

                    let showOptions;
                    try {
                        showOptions = JSON.parse(question.show_options);
                    } catch {
                        showOptions = undefined;
                    }

                    return {
                        id: question.id || null,
                        enabled: question.survey_rule_enabled,
                        dependent_question_id: question.dependent_question,
                        show_options: showOptions
                    };
                };

                vm.surveyQuestions = (response.data || []).map((question, index) => {
                    const shortName = truncateText(question.name);
                    const number = index + 1;

                    return {
                        no: number,
                        survey_question_id: question.survey_question_id,
                        name: question.name,
                        short_name: `${number}. ${shortName}`,
                        order: question.order,
                        field_type: question.custom_field?.field_type || null,
                        rule: mapRule(question)
                    };
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
