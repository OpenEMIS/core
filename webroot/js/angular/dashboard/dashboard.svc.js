angular
    .module('dashboard.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('DashboardSvc', DashboardSvc);

DashboardSvc.$inject = ['$q', '$filter', 'KdOrmSvc'];

function DashboardSvc($q, $filter, KdOrmSvc) {
    var properties = {
        notices: {},
        workbenchItems: {}
    };

    var models = {
        // TransferApprovalsTable: 'Institution.TransferApprovals',
        // StudentAdmissionTable: 'Institution.StudentAdmission',
        // StudentDropoutTable: 'Institution.StudentDropout',
        // StaffTransferApprovalsTable: 'Institution.StaffTransferApprovals',
        // StaffTransferRequestsTable: 'Institution.StaffTransferRequests',
        StaffLeaveTable: 'Institution.StaffLeave',
        InstitutionSurveysTable: 'Institution.InstitutionSurveys',
        InstitutionPositionsTable: 'Institution.InstitutionPositions',
        StaffPositionProfilesTable: 'Institution.StaffPositionProfiles'
    };

    var service = {
        init: init,
        getNotices: getNotices,
        getWorkbenchItems: getWorkbenchItems,
        getWorkbenchItemsCount: getWorkbenchItemsCount,
        getWorkbenchTitleByName: getWorkbenchTitleByName,
        getWorkbenchColumnDefs: getWorkbenchColumnDefs,
        getWorkbenchRowData: getWorkbenchRowData
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.init({NoticesTable: 'Notices'});
        KdOrmSvc.init(models);
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
        angular.forEach(models, function(model, key) {
            var registryAlias = model.split(".");
            var modelName = registryAlias.length > 1 ? registryAlias[1] : registryAlias[0];

            var modelObj = {
                code: key,
                name: getWorkbenchTitleByName(modelName),
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

    function getWorkbenchTitleByName(modelName) {
        var title = modelName.replace(/([A-Z])/g, ' $1').trim();
        return title;
    };

    function getWorkbenchColumnDefs() {
        var columnDefs = [
            {headerName: "Status", field: "status"},
            {
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
                width: 400
            },
            {
                headerName: "Institution", field: "institution",
                width: 250
            },
            {headerName: "Received Date", field: "received_date"},
            {headerName: "Requester", field: "requester"}
        ];

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
