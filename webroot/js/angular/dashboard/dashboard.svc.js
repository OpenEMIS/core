angular
    .module('dashboard.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('DashboardSvc', DashboardSvc);

DashboardSvc.$inject = ['$q', '$filter', 'KdOrmSvc'];

function DashboardSvc($q, $filter, KdOrmSvc) {
    const workbenchItemTypes = {
        FIXED: ['status', 'request_title', 'institution', 'received_date'],
        SCHOOL_BASED: ['status', 'request_title', 'institution', 'received_date'],
        NON_SCHOOL_BASED: ['status', 'request_title', 'received_date']
    };

    var properties = {
        notices: {},
        workbenchItems: {}
    };

    var configModels = {
        // FIXED Workflow
        StudentTransferApprovalTable: {
            cols: workbenchItemTypes.FIXED,
            model: 'Institution.TransferApprovals'
        },
        StudentAdmissionTable: {
            cols: workbenchItemTypes.FIXED,
            model: 'Institution.StudentAdmission'
        },
        StudentDropoutTable: {
            cols: workbenchItemTypes.FIXED,
            model: 'Institution.StudentDropout'
        },
        StaffTransferApprovalTable: {
            cols: workbenchItemTypes.FIXED,
            model: 'Institution.StaffTransferApprovals'
        },
        StaffTransferRequestTable: {
            cols: workbenchItemTypes.FIXED,
            model: 'Institution.StaffTransferRequests'
        },
        // SCHOOL_BASED Workflow
        StaffLeaveTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StaffLeave'
        },
        InstitutionSurveysTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.InstitutionSurveys'
        },
        InstitutionPositionsTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.InstitutionPositions'
        },
        ChangeInAssignmentTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StaffPositionProfiles'
        },
        // NON_SCHOOL_BASED Workflow
        TrainingCoursesTable: {
            cols: workbenchItemTypes.NON_SCHOOL_BASED,
            model: 'Training.TrainingCourses'
        },
        TrainingSessionsTable: {
            cols: workbenchItemTypes.NON_SCHOOL_BASED,
            model: 'Training.TrainingSessions'
        },
        TrainingSessionResultsTable: {
            cols: workbenchItemTypes.NON_SCHOOL_BASED,
            model: 'Training.TrainingSessionResults'
        },
        StaffTrainingNeedsTable: {
            cols: workbenchItemTypes.NON_SCHOOL_BASED,
            model: 'Staff.TrainingNeeds'
        },
        StaffTrainingApplicationsTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Training.TrainingApplications'
        }
    };

    var service = {
        init: init,
        extractModels: extractModels,
        getNotices: getNotices,
        getWorkbenchItems: getWorkbenchItems,
        getWorkbenchItemsCount: getWorkbenchItemsCount,
        getWorkbenchTitle: getWorkbenchTitle,
        getWorkbenchColumnDefs: getWorkbenchColumnDefs,
        getWorkbenchRowData: getWorkbenchRowData
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('Dashboard');
        KdOrmSvc.init({NoticesTable: 'Notices'});

        var models = this.extractModels();
        KdOrmSvc.init(this.extractModels());
    };

    function extractModels() {
        var models = {};

        angular.forEach(configModels, function(obj, key) {
            models[key] = obj.model;
        });

        return models;
    };

    function getNotices() {
        var success = function(response, deferred) {
            var notices = response.data.data;

            if (angular.isObject(notices) && notices.length > 0) {
                var order = 1;
                angular.forEach(notices, function(notice, key) {
                    notice['message'] = $filter('date')(notice.created, 'medium') + ': ' + notice.message;
                    notice['order'] = order;
                    properties.notices[notice.id] = notice;
                    order++;
                });
                deferred.resolve(properties.notices);
            } else {
                deferred.reject('No Notices');
            }
        };

        return NoticesTable
            .order(['created desc'])
            .ajax({success: success, defer: true});
    };

    function getWorkbenchItems() {
        var order = 1;
        angular.forEach(configModels, function(obj, key) {
            var modelObj = {
                code: key,
                name: getWorkbenchTitle(key),
                cols: obj.cols,
                total: 0,
                order: order++
            };

            properties.workbenchItems[modelObj.order] = modelObj;
        });

        return properties.workbenchItems;
    };

    function getWorkbenchItemsCount() {
        var promises = [];

        angular.forEach(properties.workbenchItems, function(workbenchItem, key) {
            promises.push(getWorkbenchRowData(workbenchItem, 1, 1));
        });

        return $q.all(promises);
    };

    function getWorkbenchTitle(modelKey) {
        var title = modelKey.replace(/Table$/, '').replace(/([A-Z])/g, ' $1').trim();
        return title;
    };

    function getWorkbenchColumnDefs(cols) {
        var columnDefs = [];

        if (cols.indexOf('status') !== -1) {
            columnDefs.push({
                headerName: "Status",
                field: "status",
                width: 150,
                suppressSizeToFit: true
            });
        }

        if (cols.indexOf('request_title') !== -1) {
            columnDefs.push({
                headerName: "Request Title",
                field: "request_title",
                cellRenderer: function(params) {
                    var urlParams = params.data.url;
                    var url = [urlParams.controller, urlParams.action].join('/');
                    
                    var queryParams = [];
                    angular.forEach(urlParams, function(obj, key) {
                        if (key == 'plugin' || key == 'controller' || key == 'action') {
                            // do nothing
                        } else {
                            if (!isNaN(parseFloat(key))) {
                                url += '/' + obj;
                            } else {
                                // query string
                                this.push(key + '=' + obj);
                            }
                        }
                    }, queryParams);

                    if (queryParams.length > 0) {
                        url += '?' + queryParams.join('&');
                    }

                    var eCell = document.createElement('a');
                    eCell.setAttribute("href", url);
                    eCell.setAttribute("target", "_blank");
                    eCell.innerHTML = params.value;

                    return eCell;
                },
                // width: 500
            });
        }

        if (cols.indexOf('institution') !== -1) {
            columnDefs.push({
                headerName: "Institution",
                field: "institution",
                width: 250,
                suppressSizeToFit: true
            });
        }

        if (cols.indexOf('received_date') !== -1) {
            columnDefs.push({
                headerName: "Received Date",
                field: "received_date",
                width: 150,
                suppressSizeToFit: true
            });
        }

        if (cols.indexOf('requester') !== -1) {
            columnDefs.push({headerName: "Requester", field: "requester"});
        }

        var bodyDir = getComputedStyle(document.body).direction;
        if (bodyDir == 'rtl') {
            columnDefs.reverse();
        }

        return columnDefs;
    };

    function getWorkbenchRowData(model, limit, page) {
        var success = function(response, deferred) {
            deferred.resolve(response);
        };

        return window[model.code]
            .find('workbench')
            .limit(limit)
            .page(page)
            .ajax({success: success, defer: true});
    };
}
