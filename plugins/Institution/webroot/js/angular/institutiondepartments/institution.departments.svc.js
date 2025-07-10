angular
    .module('institution.departments.svc', ['kd.data.svc'])
    .service('InstitutionDepartmentsSvc', InstitutionDepartmentsSvc);

InstitutionDepartmentsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionDepartmentsSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getDepartmentDetails: getDepartmentDetails,
        // getUnassignedStaff: getUnassignedStaff,
        translate: translate,
        // getManagerOptions: getTeacherOptions,
        // saveDepartment: saveDepartment,
        // updateDepartment: updateDepartment,
        // getConfigItemValue: getConfigItemValue
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
            console.log(response);
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

    // function getUnassignedStaff(institutionDepartmentId, institutionId, academicPeriodId) {
    //     var success = function(response, deferred) {
    //         deferred.resolve(response.data.data);
    //     };
    //     return Users.find('InstitutionStudentsNotInDepartment', {
    //         academic_period_id: academicPeriodId,
    //         institution_id: institutionId,
    //         institution_department_id: institutionDepartmentId
    //     }).ajax({
    //         success: success,
    //         defer: true
    //     });
    // }
    //
    // function getTeacherOptions(institutionId, academicPeriodId) {
    //     var success = function(response, deferred) {
    //         deferred.resolve(response.data.data);
    //     };
    //     return InstitutionStaff.find('classStaffOptions', {
    //         institution_id: institutionId,
    //         academic_period_id: academicPeriodId
    //     }).ajax({
    //         success: success,
    //         defer: true
    //     });
    // }
    //
    // function getConfigItemValue(code) {
    //     var success = function(response, deferred) {
    //         var results = response.data.data;
    //         if (angular.isObject(results) && results.length > 0) {
    //             var configItemValue = (results[0].value.length > 0) ? results[0].value : results[0].default_value;
    //             deferred.resolve(configItemValue);
    //         } else {
    //             deferred.reject('There is no ' + code + ' configured');
    //         }
    //     };
    //
    //     return ConfigItemsTable
    //         .where({
    //             code: code
    //         })
    //         .ajax({
    //             success: success,
    //             defer: true
    //         });
    // };
    //
    // function saveDepartment(data) {
    //     InstitutionDepartments.reset();
    //     return InstitutionDepartments.save(data);
    // }
    //
    // function updateDepartment(data) {
    //     InstitutionDepartments.reset();
    //     return InstitutionDepartments.edit(data);
    // }
    //
    // // for add page
    // function getAcademicPeriodOptions(institutionId) {
    //     // console.log("Institution ID " + institutionId);
    //     var success = function(response, deferred) {
    //         var periods = response.data.data;
    //         if (angular.isObject(periods) && periods.length > 0) {
    //             deferred.resolve(periods);
    //         } else {
    //             deferred.reject('There was an error when retrieving the academic periods');
    //         }
    //     };
    //
    //     return AcademicPeriods
    //         .find('schoolAcademicPeriod' //POCOR-7988
    //             //     , {
    //             //     institution_id: institutionId
    //             // }
    //         )
    //         .ajax({
    //             success: success,
    //             defer: true
    //         });
    // }
};
