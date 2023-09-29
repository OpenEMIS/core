angular
    .module('institutionadd.associations.svc', ['kd.data.svc'])
    .service('InstitutionAssociationsSvc', InstitutionAssociationsSvc);

InstitutionAssociationsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionAssociationsSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getAssociationDetails: getAssociationDetails,
        getUnassignedStudent: getUnassignedStudent,
        translate: translate,
        getTeacherOptions: getTeacherOptions,
        saveAssociation: saveAssociation,
        updateAssociation: updateAssociation,
        getConfigItemValue: getConfigItemValue,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
    };

    var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionStaff: 'Institution.Staff',
        InstitutionStudent: 'Institution.Student',
        AssociationStudent: 'Student.InstitutionAssociationStudent',
        Student: 'Institution.StudentUser',
        InstitutionAssociations: 'Institution.InstitutionAssociations',
        InstitutionShifts: 'Institution.InstitutionShifts',
        Users: 'User.Users',
        ConfigItemsTable: 'Configuration.ConfigItems'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('AssociationStudent');
        KdDataSvc.init(models);
    };

    function translate(data) {
        KdDataSvc.init({
            translation: 'translate'
        });
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {
            success: success,
            defer: true
        });
    }

    function getAssociationDetails(associationId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionAssociations
            .get(associationId)
            .find('associationDetails')
            .ajax({
                success: success,
                defer: true
            });
    }

    function getUnassignedStudent(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return Users.find('InstitutionStudentsNotInAssociation', {
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        }).ajax({
            success: success,
            defer: true
        });
    }

    function getTeacherOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionStaff.find('classStaffOptions', {
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        }).ajax({
            success: success,
            defer: true
        });
    }

    function getConfigItemValue(code) {
        var success = function(response, deferred) {
            var results = response.data.data;
            if (angular.isObject(results) && results.length > 0) {
                var configItemValue = (results[0].value.length > 0) ? results[0].value : results[0].default_value;
                deferred.resolve(configItemValue);
            } else {
                deferred.reject('There is no ' + code + ' configured');
            }
        };

        return ConfigItemsTable
            .where({
                code: code
            })
            .ajax({
                success: success,
                defer: true
            });
    };

    function saveAssociation(data) {
        InstitutionAssociations.reset();
        console.log(data)
        return InstitutionAssociations.save(data);
    }

    function updateAssociation(data) {
        InstitutionAssociations.reset();
        return InstitutionAssociations.edit(data);
    }

    // for add page 
    function getAcademicPeriodOptions(institutionId) {
        var success = function(response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('There was an error when retrieving the academic periods');
            }
        };
        return AcademicPeriods
            .find('periodHasClass', {
                institution_id: institutionId
            })
            .ajax({
                success: success,
                defer: true
            });
    }
};