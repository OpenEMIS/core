angular.module('survey.rules.svc', ['kd.orm.svc'])
.service('SurveyRulesSvc', function($http, $q, $filter, KdOrmSvc) {

    var models = {
        SurveyFormsTable: 'Survey.SurveyForms',
        SurveyQuestionsTable: 'Survey.SurveyQuestions',
        SurveyFormsQuestionsTable: 'Survey.SurveyFormsQuestions',
        SurveyFormsQuestionsTable2: 'Survey.SurveyFormsQuestions',
        SurveyFormsQuestionsTable3: 'Survey.SurveyFormsQuestions',
        SurveyQuestionChoicesTable: 'Survey.SurveyQuestionChoices'
    };

    return {
        init: function(baseUrl) {
            KdOrmSvc.base(baseUrl);
            angular.forEach(models, function(model, key) {
                window[key] = KdOrmSvc.init(model);
            });
        },

        getSurveyForm: function(surveyFormId) {
            var forms = null;
            if (surveyFormId != 0) {
                forms = SurveyFormsTable
                    .select()
                    .where({id: surveyFormId})
                    .ajax({defer: true})
                    ;
            } else {
                forms = SurveyFormsTable
                    .select()
                    .ajax({defer: true})
                    ;
            }
            return forms;
        },

        getSection: function(surveyFormId) {
            // Distinct condition not yet added
            return SurveyFormsQuestionsTable
            .select(['section'])
            .where({survey_form_id: surveyFormId})
            .group(['section'])
            .order(['order'])
            .ajax({defer: true})
            ;
        },

        getQuestions: function(surveyFormId, sectionName) 
        {
            return SurveyFormsQuestionsTable2
            .select()
            .contain(['CustomFields'])
            .where({survey_form_id: surveyFormId, section: sectionName})
            .find('SurveyRules', {survey_form_id: surveyFormId})
            .order(['order'])
            .ajax({defer: true})
            ;
        },

        getDependentQuestions: function(surveyFormId, sectionName, order) 
        {
            console.log('here');
            return SurveyFormsQuestionsTable
            .select()
            .contain(['CustomFields'])
            // .find('DependentQuestions', {})
            .ajax({defer: true});
        }
        ,

        getDependent: function(surveyFormId, questionOrder) {
            return SurveyFormsQuestionsTable
            .select()
            .find('DropDownQuestions')
            .contain(['CustomFields.CustomFieldOptions'])
            .where({survey_form_id: surveyFormId})
            .ajax({success: success, defer: true})
            ;
        },

        getShowIfChoices: function(surveyFormId, section) {
            return SurveyFormsQuestionsTable3
            .find('SurveyFormChoices', {survey_form_id: surveyFormId})
            .where({survey_form_id: surveyFormId, section: section})
            .ajax({defer: true})
            ;
        }
    }
});
