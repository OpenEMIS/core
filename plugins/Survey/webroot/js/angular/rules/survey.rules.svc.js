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
        getSurveyForm: getSurveyForm,
        getSection: getSection,
        getQuestions: getQuestions,
        getShowIfChoices: getShowIfChoices,
        saveData: saveData
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('Rules');
        KdOrmSvc.init(models);
    };

    function getSurveyForm() {
        return SurveyFormsTable
            .select()
            .ajax({defer: true})
            ;
    };

    function getSection(surveyFormId) {
        return SurveyFormsQuestionsTable
            .select(['section'])
            .where({survey_form_id: surveyFormId})
            .group(['section'])
            .order(['order'])
            .ajax({defer: true})
            ;
    };

    function getQuestions(surveyFormId, sectionName) {
        return SurveyFormsQuestionsTable
            .select()
            .contain(['CustomFields'])
            .where({survey_form_id: surveyFormId, section: sectionName})
            .find('SurveyRules', {survey_form_id: surveyFormId})
            .order(['order'])
            .ajax({defer: true})
            ;
    };

    function getShowIfChoices(surveyFormId, section) {
        return SurveyFormsQuestionsTable
            .select()
            .find('SurveyFormChoices', {survey_form_id: surveyFormId})
            .where({survey_form_id: surveyFormId, section: section})
            .ajax({defer: true})
            ;
    };

    function saveData(ruleData) {
        var promises = [];
        angular.forEach(ruleData, function(rule, key) {
            promises.push(SurveyRulesTable.save(rule));
        }, this);
        return $q.all(promises);
    };
}
