angular
    .module('assessment.item.exemptions.svc', ['kd.data.svc', 'utils.svc'])
    .service('AssessmentItemExemptionsSvc', AssessmentItemExemptionsSvc);

AssessmentItemExemptionsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'UtilsSvc'];

function AssessmentItemExemptionsSvc($http, $q, $filter, KdDataSvc, UtilsSvc) {

    var service = {
        init: init,
        getExemptStudents: getExemptStudents,
        setExemptStudents: setExemptStudents,
        // getUnexemptStudents: getUnexemptStudents,
        translate: translate,
        saveStudents: saveStudents,
    };

    var models = {
        InstitutionClasses: 'Institution.InstitutionClasses',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents',
        AssessmentItemStudentExemptions: 'Institution.AssessmentItemStudentExemptions'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('AssessmentItemStudentExemptions');
        KdDataSvc.init(models);
    };

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }


    function getExemptStudents(options) {
        var success = function(response, deferred) {

            if (response.data.data) {
                deferred.resolve(response.data.data);
            } else {
                deferred.resolve([]);
            }
        };

        return InstitutionClassStudents
            .find('exemptStudents', options)
            .ajax({success: success, defer: true});
    }


    function setExemptStudents(options) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionClassStudents.find('exemptStudentsSave', options)
            .ajax({success: success, defer:true});
    }


    function saveStudents(data) {

        // AssessmentItemStudentExemptions.save(data);
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institution/Institutions/saveAssessmentItemExemptions';
        $http.post(url, {params: data})
            .then(function(response){
                deferred.resolve(response);
            }, function(error) {
                deferred.reject(error);
            });
        return deferred.promise;
    }
};
