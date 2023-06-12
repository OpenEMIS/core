angular
    .module('dashboard.svc', ['kd.data.svc', 'kd.session.svc'])
    .service('DashboardSvc', DashboardSvc);

DashboardSvc.$inject = ['$q', '$filter', 'KdDataSvc'];

function DashboardSvc($q, $filter, KdDataSvc) {
    const workbenchItemTypes = {
        FIXED: ['status', 'request_title', 'institution', 'received_date'],
        SCHOOL_BASED: ['status', 'request_title', 'institution', 'received_date'],
        NON_SCHOOL_BASED: ['status', 'request_title', 'received_date']
    };

    var properties = {
        notices: {},
        workbenchItems: {}
    };

    var includedPlugins = ['Directory', 'Scholarship'];

    var configModels = {
        // SCHOOL_BASED Workflow
        StudentWithdrawTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StudentWithdraw'
        },
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
        VisitRequestsTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Quality.VisitRequests'
        },
        CasesTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Cases.InstitutionCases'
        },
        StaffTrainingApplicationsTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Training.TrainingApplications'
        },
        StaffTransferIn: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StaffTransferIn'
        },
        StaffTransferOut: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StaffTransferOut'
        },
        StaffRelease: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StaffRelease'
        },
        StaffReleaseIn: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StaffReleaseIn'
        },
        StudentAdmissionTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StudentAdmission'
        },
        StudentTransferInTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StudentTransferIn'
        },
        StudentTransferOutTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StudentTransferOut'
        },
        StaffAppraisalsTable: {
            cols: workbenchItemTypes.SCHOOL_BASED,
            model: 'Institution.StaffAppraisals'
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
        StaffLicensesTable: {
            cols: workbenchItemTypes.NON_SCHOOL_BASED,
            model: 'Staff.Licenses'
        },
        ScholarshipApplicationsTable: {
            cols: workbenchItemTypes.NON_SCHOOL_BASED,
            model: 'Scholarship.Applications'
        }
    };

    var service = {
        init: init,
        extractModels: extractModels,
        getProfileCompletness: getProfileCompletness,
        getNotices: getNotices,
        getWorkbenchItems: getWorkbenchItems,
        getWorkbenchItemsCount: getWorkbenchItemsCount,
        getWorkbenchTitle: getWorkbenchTitle,
        getWorkbenchColumnDefs: getWorkbenchColumnDefs,
        getWorkbenchRowData: getWorkbenchRowData,
        translate: translate
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('Dashboard');
        KdDataSvc.init({
            NoticesTable: 'Notices'
        });

        var models = this.extractModels();
        KdDataSvc.init(this.extractModels());
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

    function extractModels() {
        var models = {};

        angular.forEach(configModels, function(obj, key) {
            models[key] = obj.model;
        });

        return models;
    };

    function getProfileCompletness() {
        var success = function(response, deferred) {
            var notices = response.data.data;

            if (angular.isObject(notices) && notices.length > 0) {
                var order = 0;
                angular.forEach(notices, function(notice, key) {
                    notice['message'] = notice.created + ': ' + notice.message;
                    notice['order'] = order;
                    properties.notices[notice.order] = notice;
                    order++;
                });

                deferred.resolve(properties.notices);
            } else {
                deferred.reject('No Notices');
            }
        };

        return NoticesTable
            .order(['-created'])
            .ajax({
                success: success,
                defer: true
            });
    }

    function getNotices() {
        var success = function(response, deferred) {
            var notices = response.data.data;

            if (angular.isObject(notices) && notices.length > 0) {
                var order = 0;
                angular.forEach(notices, function(notice, key) {
                    notice['message'] = notice.created + ': ' + notice.message;
                    notice['order'] = order;
                    properties.notices[notice.order] = notice;
                    order++;
                });

                deferred.resolve(properties.notices);
            } else {
                deferred.reject('No Notices');
            }
        };

        return NoticesTable
            .order(['-created'])
            .ajax({
                success: success,
                defer: true
            });
    };

    function getWorkbenchItems() {
        var order = 1;
        var workbenchItems = []; // convert object to Array - Required by Angular
        angular.forEach(configModels, function(obj, key) {
            var modelObj = {
                code: key,
                name: getWorkbenchTitle(key),
                cols: obj.cols,
                total: 0,
                order: order++
            };

            properties.workbenchItems[modelObj.order] = modelObj;
            workbenchItems.push(modelObj);
        });


        return workbenchItems;
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
        // var menuTabs = [];
        var columnDefs = [];
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        if (cols.indexOf('status') !== -1) {
            columnDefs.push({
                headerName: "Status",
                field: "status",
                width: 150,
                filterParams: filterParams,
                menuTabs: menuTabs,
                suppressSizeToFit: true,
                filter: 'text',
                
            });
        }

        if (cols.indexOf('request_title') !== -1) {
            columnDefs.push({
                headerName: "Request Title",
                menuTabs: menuTabs,
                field: "request_title",
                filterParams: filterParams,
                filter: 'text',
                cellRenderer: function(params) {
                    // console.log(params)
                    if (typeof params.data !== 'undefined') {
                        var urlParams = params.data.url;

                        if (includedPlugins.indexOf(urlParams.plugin) !== -1) {
                            var url = [urlParams.plugin, urlParams.controller, urlParams.action].join('/');
                        } else {
                            var url = [urlParams.controller, urlParams.action].join('/');
                        }

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
                    }
                    // width: 500
                }
            });
        }

        if (cols.indexOf('institution') !== -1) {
            columnDefs.push({
                headerName: "Institution",
                field: "institution",
                filter: 'set',
                menuTabs: menuTabs,
                filterParams: filterParams,
                width: 250,
                suppressSizeToFit: true
            });
        }


        if (cols.indexOf('received_date') !== -1) {
            columnDefs.push({
                headerName: "Received Date",
                filter: 'set',
                floatingFilter: true,
                // valueFormatter: dateFormatter,
                // comparator: dateComparator,
                // inRangeInclusive:true,	
                menuTabs: menuTabs,
                field: "received_date",
                filterParams: filterParams,
                 width: 150,
                suppressSizeToFit: true
            });
        }

        if (cols.indexOf('requester') !== -1) {
            columnDefs.push({
                headerName: "Requester",
                field: "requester",
                filter: 'text',
                menuTabs: menuTabs
            });
        }

        var bodyDir = getComputedStyle(document.body).direction;
        if (bodyDir == 'rtl') {
            columnDefs.reverse();
        }

        return columnDefs;
    };

    function getWorkbenchRowData(model, limit, page) {
        // console.log('model', model);
        var success = function(response, deferred) {
            deferred.resolve(response);
        };

        return window[model.code]
            .find('workbench')
            .limit(limit)
            .page(page)
            .ajax({
                success: success,
                defer: true
            });
    };
}