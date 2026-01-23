angular
    .module('survey.rules.svc', ['kd.orm.svc'])
    .service('SurveyRulesSvc', SurveyRulesSvc);

SurveyRulesSvc.$inject = ['$q', 'KdOrmSvc'];

function SurveyRulesSvc($q, KdOrmSvc) {

    var models = {
        SurveyFormsTable: 'Survey.SurveyForms',
        SurveyQuestionsTable: 'Survey.SurveyQuestions',
        SurveyFormsQuestionsTable: 'Survey.SurveyFormsQuestions',
        SurveyQuestionChoicesTable: 'Survey.SurveyQuestionChoices',
        SurveyRulesTable: 'Survey.SurveyRules'
    };

    var service = {
        init: init,
        getSurveyForms: getSurveyForms,
        getSections: getSections,
        getQuestions: getQuestions,
        getShowIfChoices: getShowIfChoices,
        saveData: saveData,
        deleteData: deleteData
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('Rules');
        KdOrmSvc.init(models);
    };

    function getSurveyForms() {
        return SurveyFormsTable
            .select()
            .find('HavingDropdownQuestions')
            .ajax({defer: true})
            ;
    };

    function getSections(surveyFormId) {
        return SurveyFormsQuestionsTable
            .select(['section'])
            .where({survey_form_id: surveyFormId})
            .group(['section'])
            //.order(['order']) //POCOR-8465
            .ajax({defer: true})
            ;
    };

    function getQuestions(surveyFormId, sectionName) {
        let options = {survey_form_id: surveyFormId, section: sectionName};
        // console.log(options);
        return SurveyFormsQuestionsTable
            .select()
            .find('ForSurveyRules', options) // POCOR-9147
            //.order(['order']) //POCOR-8465
            .ajax({defer: true})
            ;
    };

    function getShowIfChoices(surveyFormId, section, dependentQuestionId) {
        let options = {survey_form_id: surveyFormId,
            section: section,
            survey_question_id: dependentQuestionId};
        return SurveyFormsQuestionsTable
            .select()
            .find('SurveyFormChoices', options)
            .where(options)
            .ajax({defer: true})
            ;
    };

    function saveData(ruleData) {
        var promises = [];
        angular.forEach(ruleData, function(rule, key) {
            // console.log(rule);
            promises.push(SurveyRulesTable.save(rule));
        }, this);
        return $q.all(promises);
    };
    function deleteData(ruleData) {
        var promises = [];
        angular.forEach(ruleData, function(rule, key) {
            // console.log(rule);
            rule.dependent_question_id = 0;
            rule.enabled = 0;
            rule.show_options = "";
            promises.push(SurveyRulesTable.save(rule));
        }, this);
        return $q.all(promises);
    };
}
