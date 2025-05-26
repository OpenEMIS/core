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
        getSections: getSections,
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
        return SurveyFormsQuestionsTable
            .select()
            .contain(['CustomFields'])
            .where({survey_form_id: surveyFormId, section: sectionName})
            .find('SurveyRules', {survey_form_id: surveyFormId, section: sectionName}) // POCOR-9147
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
            promises.push(SurveyRulesTable.save(rule));
        }, this);
        return $q.all(promises);
    };
}
