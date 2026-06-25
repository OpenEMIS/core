angular
    .module('institution.departments.svc', ['kd.data.svc'])
    .service('InstitutionDepartmentsSvc', InstitutionDepartmentsSvc);

InstitutionDepartmentsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionDepartmentsSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getDepartmentDetails: getDepartmentDetails,
        getUnassignedStaff: getUnassignedStaff,
        translate: translate,
        getManagerOptions: getManagerOptions,
        updateDepartment: updateDepartment,
    };

    var models = {
        InstitutionStaff: 'Institution.Staff',
        DepartmentStaff: 'Institution.DepartmentStaff',
        InstitutionDepartments: 'Institution.InstitutionDepartments',
        Users: 'User.Users',
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('DepartmentStaff');
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

    function getDepartmentDetails(departmentId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionDepartments
            .get(departmentId)
            .find('DepartmentDetails')
            .ajax({
                success: success,
                defer: true
            });
    }

    function getUnassignedStaff(institutionId, departmentId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionStaff.find('StaffForDepartment', {
            institution_id: institutionId,
            department_id: departmentId,
            target: 'unassigned'
        }).ajax({
            success: success,
            defer: true
        });
    }

    function getManagerOptions(departmentId, institutionId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionStaff.find('StaffForDepartment', {
            institution_id: institutionId,
            department_id: departmentId,
            target: 'manager'
        }).ajax({
            success: success,
            defer: true
        });
    }
    //

    function updateDepartment(data) {
        InstitutionDepartments.reset();
        return InstitutionDepartments.edit(data);
    }

};
