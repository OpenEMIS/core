angular
    .module('institutions.comments.svc', ['kd.data.svc', 'kd.session.svc'])
    .service('InstitutionsCommentsSvc', InstitutionsCommentsSvc);

InstitutionsCommentsSvc.$inject = ['$filter', '$q', 'KdDataSvc', 'KdSessionSvc'];

function InstitutionsCommentsSvc($filter, $q, KdDataSvc, KdSessionSvc) {
    const roles = {PRINCIPAL: 'PRINCIPAL', HOMEROOM_TEACHER: 'HOMEROOM_TEACHER', TEACHER: 'TEACHER'};

    var models = {
        ReportCardTable: 'ReportCard.ReportCards',
        ReportCardSubjectsTable: 'ReportCard.ReportCardSubjects',
        ReportCardCommentCodesTable: 'ReportCard.ReportCardCommentCodes',
        InstitutionSubjectsTable: 'Institution.InstitutionSubjects',
        InstitutionStudentsReportCardsTable: 'Institution.InstitutionStudentsReportCards',
        InstitutionStudentsReportCardsCommentsTable: 'Institution.InstitutionStudentsReportCardsComments',
        InstitutionClassStudentsTable: 'Institution.InstitutionClassStudents',
        StaffUserTable: 'Institution.StaffUser',
        StaffTable: 'Institution.Staff',
        HomeroomStaffTable: 'Institution.Staff',
    };

    var service = {
        init: init,
        getReportCard: getReportCard,
        getEditPermissions: getEditPermissions,
        getTabs: getTabs,
        getSubjects: getSubjects,
        getCommentCodeOptions: getCommentCodeOptions,
        getCurrentUser: getCurrentUser,
        getColumnDefs: getColumnDefs,
        renderText: renderText,
        renderSelect: renderSelect,
        getRowData: getRowData,
        checkStudentReportCardExists: checkStudentReportCardExists,
        saveSingleRecordData: saveSingleRecordData
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('ReportCardComments');
        KdDataSvc.init(models);
        KdSessionSvc.base(baseUrl);
    };

    function getReportCard(reportCardId) {
        return ReportCardTable
            .get(reportCardId)
            .ajax({defer: true});
    };

    function getEditPermissions(reportCardId, institutionId, classId, currentUserId) {
        var promises = [];

        var principalPermission = StaffTable
            .select()
            .find('principalEditPermissions', {
                institution_id: institutionId,
                staff_id: currentUserId
            });

        var homeroomTeacherPermission = HomeroomStaffTable
            .select()
            .find('homeroomEditPermissions', {
                institution_id: institutionId,
                institution_class_id: classId,
                staff_id: currentUserId
            });

        var teacherPermission = InstitutionSubjectsTable
            .select()
            .find('teacherEditPermissions', {
                report_card_id: reportCardId,
                institution_id: institutionId,
                institution_class_id: classId,
                staff_id: currentUserId
            });

        promises.push(KdSessionSvc.read('Auth.User.super_admin'));
        promises.push(principalPermission.ajax({defer: true}));
        promises.push(homeroomTeacherPermission.ajax({defer: true}));
        promises.push(teacherPermission.ajax({defer: true}));

        return $q.all(promises);
    };

    function getTabs(reportCardId, classId, institutionId, currentUserId, principalCommentsRequired, homeroomTeacherCommentsRequired, teacherCommentsRequired) {
        var deferred = $q.defer();
        var tabs = [];

        var isSuperAdmin = {};
        var principalPermission = {};
        var homeroomTeacherPermission = {};
        var teacherPermission = {};

        this.getEditPermissions(reportCardId, institutionId, classId, currentUserId)
        .then(function(response)
        {
            isSuperAdmin = response[0];
            principalPermission = response[1].data;
            homeroomTeacherPermission = response[2].data;
            teacherPermission = response[3].data;

            if (principalCommentsRequired) {
                editable = (angular.isObject(principalPermission) && principalPermission.length > 0) || isSuperAdmin;
                tabs.push({
                    tabName: "Principal",
                    type: roles.PRINCIPAL,
                    education_subject_id: 0,
                    editable: editable
                });
            }

            if (homeroomTeacherCommentsRequired) {
                editable = (angular.isObject(homeroomTeacherPermission) && homeroomTeacherPermission.length > 0) || isSuperAdmin;
                tabs.push({
                    tabName: "Homeroom Teacher",
                    type: roles.HOMEROOM_TEACHER,
                    education_subject_id: 0,
                    editable: editable
                });
            }

            return getSubjects(reportCardId, classId,principalPermission);
        }, function(error)
        {
            console.log(error);
        })
        .then(function(response)
        {
            if (teacherCommentsRequired) {
                subjects = response.data;
                if (angular.isObject(subjects) && subjects.length > 0) {
                    angular.forEach(subjects, function(subject, key)
                    {
                        editable = (angular.isObject(teacherPermission) && teacherPermission.hasOwnProperty(subject.education_subject_id)) || isSuperAdmin;
                        this.push({
                            tabName: subject.name + " Teacher",
                            type: roles.TEACHER,
                            id: subject.id,
                            education_subject_id: subject.education_subject_id,
                            editable: editable
                        });
                    }, tabs);
                }
            }
        }, function(error)
        {
            console.log(error);
        })
        .finally(function()
        {
            if (tabs.length > 0) {
                deferred.resolve(tabs);
            } else {
                deferred.reject('You have to configure the comments required first');
            }
        });

        return deferred.promise;
    };

    function getSubjects(reportCardId, classId,principalPermission) {
        return ReportCardSubjectsTable
            .select()
            .find('matchingClassSubjects', {
                report_card_id: reportCardId,
                institution_class_id: classId,
                type:principalPermission.length
            })
            .ajax({defer: true});
    };

    function getCommentCodeOptions() {
        return ReportCardCommentCodesTable
            .select(['id', 'name'])
            .where({visible: 1})
            .order(['order'])
            .ajax({defer: true});
    };

    function getCurrentUser() {
        var deferred = $q.defer();

        KdSessionSvc.read('Auth.User.id')
        .then(function(response) {
            var staffId = response;
            return StaffUserTable
                .get(staffId)
                .ajax({defer: true});

        }, function(error) {
            console.log(error);
            deferred.reject(error);
        })
        // get staff data
        .then(function(response) {
            staffData = response.data;
            deferred.resolve(staffData);

        }, function(error) {
            console.log(error);
            deferred.reject(error);
        });

        return deferred.promise;
    };

    function getColumnDefs(action, tab, currentUserName, _comments, commentCodeOptions, _commentTextEditor) {
        var deferred = $q.defer();

        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30
        };

        var columnDefs = [];

        columnDefs.push({
            headerName: "OpenEMIS ID",
            field: "openemis_id",
            filterParams: filterParams,
            filter: 'text',
            menuTabs: menuTabs,
            suppressMenu: true,
            cellStyle: {
                lineHeight: '45px'
            },
            maxWidth: 125
        });
        columnDefs.push({
            headerName: "Name",
            field: "name",
            sort: 'asc',
            filterParams: filterParams,
            filter: 'text',
            menuTabs: menuTabs,
            suppressMenu: true,
            cellStyle: {
                lineHeight: '45px'
            },
            minWidth: 100,
            maxWidth: 250
        });
        columnDefs.push({
            headerName: "Status",
            field: "student_status",
            filterParams: filterParams,
            filter: 'text',
            menuTabs: menuTabs,
            suppressMenu: true,
            cellStyle: {
                lineHeight: '45px'
            },
            maxWidth: 100
        });
        columnDefs.push({
            headerName: "student id",
            field: "student_id",
            hide: true,
            filterParams: filterParams,
            cellStyle: {
                lineHeight: '45px'
            },
        });

        var allowEdit = action == 'edit';
        var headerIcons = allowEdit ? " <span class='divider'></span>  <i class='fa fa-pencil-square-o fa-lg header-icon'></i>" : '';
        var isSubjectTab = (tab.type == roles.TEACHER) ? true : false;

        var extra = {};

        if (isSubjectTab) {
            var columnDef = {
                headerName: "Total Mark",
                field: "total_mark",
                menuTabs: menuTabs,
                suppressMenu: true,
                valueGetter: function(params) {
                    var marks = '';
                    if (angular.isDefined(params.data) && angular.isDefined(params.data[params.colDef.field])) {
                        var value = params.data[params.colDef.field];
                        if (!isNaN(parseFloat(value))) {
                            marks =  $filter('number')(params.data[params.colDef.field], 2);
                        }
                    } 
                    return marks;
                },
                cellStyle: {
                    lineHeight: '45px'
                },
                maxWidth: 140
            };
            columnDefs.push(columnDef);
        }

        // comment code column
        if (isSubjectTab) {
            var selectOptions = {
                0 : {
                    id: 0,
                    name: '-- Select --'
                }
            };
            for (var i = 0; i < commentCodeOptions.length; i++) {
                selectOptions[i+1] = commentCodeOptions[i];
            }

            extra = {
                selectOptions: selectOptions,
                currentUserName: currentUserName,
                editPermission: tab.editable,
                tab: tab
            };
            var columnDef = {
                headerName: "Comment Code" + headerIcons,
                field: "comment_code",
                filterParams: filterParams,
                filter: 'text',
                menuTabs: menuTabs,
                suppressMenu: true,
                cellStyle: {
                    whiteSpace: 'normal !important',
                    overflowY: 'auto',
                    lineHeight: '45px'
                },
                maxWidth: 350
            };
            columnDef = this.renderSelect(allowEdit, columnDef, extra, _comments);
            columnDefs.push(columnDef);
        }

        if (!isSubjectTab) {
            var columnDef = {
                headerName: "Overall Average",
                field: "average_mark",
                menuTabs: menuTabs,
                suppressMenu: true,
                valueGetter: function(params) {
                    var marks = '';
                    if (angular.isDefined(params.data) && angular.isDefined(params.data[params.colDef.field])) {
                        var value = params.data[params.colDef.field];
                        if (!isNaN(parseFloat(value))) {
                            marks =  $filter('number')(params.data[params.colDef.field], 2);
                        } 
                    } 
                    return marks;
                },
                cellStyle: {
                    lineHeight: '45px'
                },
                maxWidth: 140
            };
            columnDefs.push(columnDef);
        }

        // comment column
        extra = {editPermission: tab.editable};
        var columnDef = {
            headerName: "Comments" + headerIcons,
            field: "comments",
            filterParams: filterParams,
            filter: 'text',
            menuTabs: menuTabs,
            suppressMenu: true,
            cellStyle: {
                whiteSpace: 'normal !important',
                overflowY: 'auto'
            },
            autoHeight: true,
            cellEditor: _commentTextEditor
        };
        columnDef = this.renderText(allowEdit, columnDef, extra, _comments);
        columnDefs.push(columnDef);

        // modified by column
        if (isSubjectTab) {
            columnDefs.push({
                headerName: "Modified By",
                field: "modified_by",
                filterParams: filterParams,
                filter: 'text',
                menuTabs: menuTabs,
                suppressMenu: true,
                cellStyle: {
                    lineHeight: '45px'
                },
                minWidth: 100,
                maxWidth: 250
            });
        }

        var bodyDir = getComputedStyle(document.body).direction;
        if (bodyDir == 'rtl') {
            columnDefs.reverse();
        }

        deferred.resolve(columnDefs);
        return deferred.promise;
    };

    function renderText(allowEdit, cols, extra, _comments) {
        var editPermission = extra.editPermission;

        cols = angular.merge(cols, {
            filter: 'text'
        });

        if (allowEdit && editPermission) {
            cols = angular.merge(cols, {
                editable: true,
                cellClass: 'oe-cell-highlight',
            });
        }

        return cols;
    };

    function renderSelect(allowEdit, cols, extra, _comments) {
        var options = extra.selectOptions;
        var currentUserName = extra.currentUserName;
        var editPermission = extra.editPermission;

        if (allowEdit && editPermission) {
            cols = angular.merge(cols, {
                cellClass: 'oe-cell-highlight',
                cellRenderer: function(params) {
                    if (angular.isDefined(params.data)) {
                        if (params.value.length == 0) {
                            // set to default select
                            params.value = 0;
                        }

                        var oldValue = params.value;
                        var studentId = params.data.student_id;

                        var eCell = document.createElement('div');
                        eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");

                        var eSelect = document.createElement("select");

                        var isAnswerValid = false;
                        angular.forEach(options, function(obj, key) {
                            var eOption = document.createElement("option");
                            var labelText = obj.name;
                            eOption.setAttribute("value", obj.id);
                            eOption.innerHTML = labelText;
                            eSelect.appendChild(eOption);
                            if (oldValue == obj.id) {
                                isAnswerValid = true;
                            }
                        });

                        // set selected value only when it is a valid option from gradingOptions list
                        if (isAnswerValid) {
                            eSelect.value = params.value;
                        }

                        eSelect.addEventListener('change', function () {
                            var newValue = eSelect.value;
                            params.data[params.colDef.field] = newValue;

                            // set last modified user name
                            params.data.modified_by = currentUserName;

                            if (angular.isUndefined(_comments[studentId])) {
                                _comments[studentId] = {};
                            }

                            if (angular.isUndefined(_comments[params.data.student_id][params.colDef.field])) {
                                _comments[params.data.student_id][params.colDef.field] = {};
                            }

                            _comments[params.data.student_id][params.colDef.field] = newValue;

                            saveSingleRecordData(params, extra.tab)
                            .then(function(response) {
                            }, function(error) {
                                console.log(error);
                            });

                            // Important: to refresh the grid after data is modified
                            params.api.refreshView();
                        });

                        eCell.appendChild(eSelect);

                        return eCell;
                    }
                },
                suppressMenu: true
            });
        } else {
            cols = angular.merge(cols, {
                cellRenderer: function(params) {
                    if (angular.isDefined(params.data)) {
                        var cellValue = '';
                        if (params.value.length != 0 && params.value != 0) {
                            // show option name only when it is a valid option from options list
                            angular.forEach(options, function(obj, key) {
                                if (params.value == obj.id) {
                                    cellValue = options[key]['name'];
                                }
                            });
                        }

                        var eCell = document.createElement('div');
                        var eLabel = document.createTextNode(cellValue);
                        eCell.appendChild(eLabel);

                        return eCell;
                    }
                },
                suppressMenu: true
            });
        }

        return cols;
    };

    function getRowData(academicPeriodId, institutionId, institutionClassId, educationGradeId, reportCardId, commentCodeOptions, tab, limit, page) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.error)) {
                deferred.reject(response.data.error);
            } else {
                var reportCardStudents = response.data.data;
                var isSubjectTab = (tab.type == roles.TEACHER) ? true : false;

                if (angular.isObject(reportCardStudents) && reportCardStudents.length > 0) {
                    var studentId = null;
                    var currentStudentId = null;
                    var studentsData = {};
                    var rowData = [];

                    angular.forEach(reportCardStudents, function(reportCardStudent, key) {
                        currentStudentId = parseInt(reportCardStudent.student_id);

                        if (studentId != currentStudentId) {
                            if (studentId != null) {
                                this.push(studentsData);
                            }

                            studentsData = {
                                openemis_id: reportCardStudent._matchingData.Users.openemis_no,
                                name: reportCardStudent._matchingData.Users.name,
                                student_id: reportCardStudent.student_id,
                                student_status: reportCardStudent.student_status.name,
                                comments: '',
                                comment_code: '',
                                modified_by: '',
                                total_mark: '',
                                average_mark: ''
                            };

                            if (reportCardStudent.total_mark != null) {
                                studentsData['total_mark'] = reportCardStudent.total_mark;
                            }

                            if (reportCardStudent.average_mark != null) {
                                studentsData['average_mark'] = reportCardStudent.average_mark;
                            }

                            if (reportCardStudent.comments != null) {
                                studentsData['comments'] = reportCardStudent.comments;
                            }

                            if (isSubjectTab) {
                                if (reportCardStudent.comment_code != null) {
                                    studentsData['comment_code'] = reportCardStudent.comment_code;
                                }

                                if (reportCardStudent.Staff.first_name != null && reportCardStudent.Staff.last_name != null) {
                                    var staffName = reportCardStudent.Staff.first_name + ' ' + reportCardStudent.Staff.last_name;
                                    studentsData['modified_by'] = staffName;
                                }
                            }
                            studentId = currentStudentId;
                        }

                    }, rowData);

                    if (studentsData.hasOwnProperty('student_id')) {
                        rowData.push(studentsData);
                    }

                    response.data.data = rowData;
                    deferred.resolve(response);
                } else {
                    deferred.resolve(response);
                }
            }
        };

        return InstitutionClassStudentsTable
            .select()
            .find('reportCardComments', {
                academic_period_id: academicPeriodId,
                institution_id: institutionId,
                institution_class_id: institutionClassId,
                education_grade_id: educationGradeId,
                report_card_id: reportCardId,
                type: tab.type,
                education_subject_id: tab.education_subject_id,
                institution_subject_id: tab.id
            })
            .limit(limit)
            .page(page)
            .ajax({success: success, defer: true});
    };

    function checkStudentReportCardExists(data) {
        return InstitutionStudentsReportCardsTable
            .select()
            .where({
                report_card_id: data.report_card_id,
                student_id : data.student_id,
                institution_id : data.institution_id,
                academic_period_id : data.academic_period_id,
                education_grade_id : data.education_grade_id,
                institution_class_id: data.institution_class_id
            })
            .ajax({defer: true});
    };

    function saveSingleRecordData(params, tab) {
        var promises = [];
        var isSubjectTab = (tab.type == roles.TEACHER) ? true : false;

        var studentReportCardData = {
            report_card_id: params.context.report_card_id,
            student_id: params.data.student_id,
            institution_id: params.context.institution_id,
            academic_period_id: params.context.academic_period_id,
            education_grade_id: params.context.education_grade_id,
            institution_class_id: params.context.class_id
        };

        if (isSubjectTab) {
            var comments = null;
            var commentCode = null;

            if (params.data.comments.length > 0) {
                comments = params.data.comments;
            }

            if (params.data.comment_code.length > 0 && params.data.comment_code != 0) {
                commentCode = params.data.comment_code;
            }

            var subjectCommentsData = Object.assign({}, studentReportCardData);
            subjectCommentsData["comments"] = comments;
            subjectCommentsData["report_card_comment_code_id"] = commentCode;
            subjectCommentsData["education_subject_id"] = tab.education_subject_id;
            subjectCommentsData["staff_id"] = params.context.current_user_id;

            // check if main student report card record exists
            checkStudentReportCardExists(studentReportCardData)
            .then(function(response) {
                var studentReportcard = response.data;

                if (studentReportcard.length == 0) {
                    // save to both tables
                    promises.push(InstitutionStudentsReportCardsTable.save(studentReportCardData));
                    promises.push(InstitutionStudentsReportCardsCommentsTable.save(subjectCommentsData));

                } else {
                    // save only to comments table
                    promises.push(InstitutionStudentsReportCardsCommentsTable.save(subjectCommentsData));
                }

            }, function(error) {
                console.log(error);
            });

        } else {
            if (tab.type == roles.PRINCIPAL) {
                studentReportCardData["principal_comments"] = params.data.comments;
            } else if (tab.type == roles.HOMEROOM_TEACHER) {
                studentReportCardData["homeroom_teacher_comments"] = params.data.comments;
            }

            promises.push(InstitutionStudentsReportCardsTable.save(studentReportCardData));
        }

        return $q.all(promises);
    };
}
