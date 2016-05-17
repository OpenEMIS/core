angular.module('relevancy.rules.svc', ['kd.orm.svc'])
.service('RelevancyRulesSvc', function($http, $q, $filter, KdOrmSvc) {

    var models = {
        SurveyRulesTable: 'Survey.SurveyRules'
    };

    return {
        init: function(baseUrl) {
            KdOrmSvc.base(baseUrl);
            angular.forEach(models, function(model, key) {
                window[key] = KdOrmSvc.init(model);
            });
        }
    }
});
