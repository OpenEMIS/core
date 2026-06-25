angular
    .module('assessment.item.exemptions.svc', ['kd.data.svc'])
    .service('AssessmentItemExemptionsSvc', AssessmentItemExemptionsSvc);

AssessmentItemExemptionsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function AssessmentItemExemptionsSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getExemptStudents: getExemptStudents,
        setExemptStudents: setExemptStudents,
        getStudentStatus: getStudentStatus,
        // getUnexemptStudents: getUnexemptStudents,
        translate: translate,
        saveStudents: saveStudents,
    };

    var models = {
        InstitutionClasses: 'Institution.InstitutionClasses',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents',
        StudentStatuses: 'Student.StudentStatuses',
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

    //POCOR-9114 -- code changes for multiple assessment periods -- START
    function getExemptStudents(options) {
        if (Array.isArray(options.assessment_period_id)) {
            // Create a custom combined field
            options.assessment_period_combo = options.assessment_period_id.join('_');

            // Retain only the first value in the original field
            options.assessment_period_id = options.assessment_period_id[0];
        }

        var success = function(response, deferred) {
            // console.log('response', response); //POCOR-9197
            if (response.data && response.data.data) {
                deferred.resolve(response.data.data);
            } else {
                deferred.resolve([]);
            }
        };

        return InstitutionClassStudents
            .find('exemptStudents', options)
            .ajax({ success: success, defer: true });
    }
    //POCOR-9114 -- code changes for multiple assessment periods -- END

    function getExemptStudentsOld(options) {
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
    
    //POCOR-9428 start
    function getStudentStatus() {
        var success = function(response, deferred) {
            if (response && response.data && response.data.data && Array.isArray(response.data.data)) {
                // Allowed status 
                var allowedStatuses = ['CURRENT', 'TRANSFERRED', 'GRADUATED', 'PROMOTED'];
                var filteredData = response.data.data.filter(function(item) {
                    return allowedStatuses.includes(item.code);
                });
               // console.log('Filtered Data:', filteredData);
                deferred.resolve({ data: { data: filteredData } });
            } else {
                deferred.resolve({ data: { data: [] } });
            }
        };
        return StudentStatuses
            .find('statusList')
            .ajax({ success: success, defer: true });
    } //POCOR-9428 end

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
