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
        InstitutionStudentsReportCardsTable: 'Institution.InstitutionStudentsReportCards',
        InstitutionStudentsReportCardsCommentsTable: 'Institution.InstitutionStudentsReportCardsComments',
        InstitutionClassesTable: 'Institution.InstitutionClasses',
        InstitutionClassStudentsTable: 'Institution.InstitutionClassStudents',
        StaffUserTable: 'Institution.StaffUser',
        StaffTable: 'Institution.Staff',
        InstitutionSubjectStaffTable: 'Institution.InstitutionSubjectStaff'
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
        saveRowData: saveRowData
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
            .find('PrincipalEditPermissions', {
                institution_id: institutionId,
                staff_id: currentUserId
            });

        var homeroomTeacherPermission = InstitutionClassesTable
            .select()
            .where({
                id: classId,
                staff_id: currentUserId
            });

        var teacherPermission = InstitutionSubjectStaffTable
            .select()
            .find('TeacherEditPermissions', {
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

        this.getEditPermissions(reportCardId, institutionId, classId, currentUserId)
        .then(function(response)
        {
            var isSuperAdmin = response[0];
            var principalPermission = response[1].data;
            var homeroomTeacherPermission = response[2].data;
            var teacherPermission = response[3].data;

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

            if (teacherCommentsRequired) {
                getSubjects(reportCardId, classId)
                .then(function(response)
                {
                    subjects = response.data;
                    if (angular.isObject(subjects) && subjects.length > 0) {
                        angular.forEach(subjects, function(subject, key)
                        {
                            editable = (angular.isObject(teacherPermission) && teacherPermission.hasOwnProperty(subject.education_subject_id)) || isSuperAdmin;
                            this.push({
                                tabName: subject.name + " Teacher",
                                type: roles.TEACHER,
                                education_subject_id: subject.education_subject_id,
                                editable: editable
                            });
                        }, tabs);
                    }
                }, function(error)
                {
                    // No Subjects
                    console.log(error);
                });
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

    function getSubjects(reportCardId, classId) {
        return ReportCardSubjectsTable
            .select()
            .find('MatchingClassSubjects', {
                report_card_id: reportCardId,
                institution_class_id: classId
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

    function getColumnDefs(action, tab, currentUserName, _comments, commentCodeOptions) {
        var deferred = $q.defer();

        var filterParams = {
            cellHeight: 30
        };

        var columnDefs = [];

        columnDefs.push({
            headerName: "OpenEMIS ID",
            field: "openemis_id",
            filterParams: filterParams
        });
        columnDefs.push({
            headerName: "Name",
            field: "name",
            sort: 'asc',
            filterParams: filterParams
        });
        columnDefs.push({
            headerName: "Status",
            field: "student_status",
            filterParams: filterParams
        });
        columnDefs.push({
            headerName: "student id",
            field: "student_id",
            hide: true,
            filterParams: filterParams
        });
        columnDefs.push({
            headerName: "institution id",
            field: "institution_id",
            hide: true,
            filterParams: filterParams
        });
        columnDefs.push({
            headerName: "education grade id",
            field: "education_grade_id",
            hide: true,
            filterParams: filterParams
        });

        var allowEdit = action == 'edit';
        var headerIcons = allowEdit ? " <span class='divider'></span>  <i class='fa fa-pencil-square-o fa-lg header-icon'></i>" : '';
        var isSubjectTab = (tab.type == roles.TEACHER) ? true : false;

        var extra = {};
        if (isSubjectTab) {
            var selectOptions = {
                0 : {
                    id: 0,
                    name: '-- Select --'
                }
            };
            angular.forEach(commentCodeOptions, function(obj, key) {
                selectOptions[obj.id] = {
                    id: obj.id,
                    name: obj.name
                }
            });

            extra = {
                selectOptions: selectOptions,
                currentUserName: currentUserName,
                editPermission: tab.editable
            };
            var columnDef = {
                headerName: "Comment Code" + headerIcons,
                field: "comment_code",
                filterParams: filterParams
            };
            columnDef = this.renderSelect(allowEdit, columnDef, extra, _comments);
            columnDefs.push(columnDef);
        }

        extra = {editPermission: tab.editable};
        var columnDef = {
            headerName: "Comments" + headerIcons,
            field: "comments",
            filterParams: filterParams
        };
        columnDef = this.renderText(allowEdit, columnDef, extra, _comments);
        columnDefs.push(columnDef);

        if (isSubjectTab) {
            columnDefs.push({
                headerName: "Modified By",
                field: "modified_by",
                filterParams: filterParams
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
                cellClass: 'oe-cell-highlight'
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
                    if (params.value.length == 0) {
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
                        eOption.setAttribute("value", key);
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
                        params.data.modified_by = currentUserName;

                        if (angular.isUndefined(_comments[studentId])) {
                            _comments[studentId] = {};
                        }

                        if (angular.isUndefined(_comments[params.data.student_id][params.colDef.field])) {
                            _comments[params.data.student_id][params.colDef.field] = {};
                        }

                        _comments[params.data.student_id][params.colDef.field] = newValue;
                    });

                    eCell.appendChild(eSelect);

                    return eCell;
                },
                suppressMenu: true
            });
        } else {
            cols = angular.merge(cols, {
                cellRenderer: function(params) {
                    var cellValue = '';
                    if (params.value.length != 0 && params.value != 0) {
                        // show option code and name only when it is a valid option from options list
                        if (angular.isDefined(options[params.value])) {
                            cellValue = options[params.value]['name'];
                        }
                    }

                    var eCell = document.createElement('div');
                    var eLabel = document.createTextNode(cellValue);
                    eCell.appendChild(eLabel);

                    return eCell;
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
                                modified_by: ''
                            };

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
            .find('ReportCardComments', {
                academic_period_id: academicPeriodId,
                institution_id: institutionId,
                institution_class_id: institutionClassId,
                education_grade_id: educationGradeId,
                report_card_id: reportCardId,
                type: tab.type,
                education_subject_id: tab.education_subject_id
            })
            .limit(limit)
            .page(page)
            .ajax({success: success, defer: true});
    };

    function checkStudentReportCardExists(studentId, institutionId, classId, educationGradeId, academicPeriodId, reportCardId) {
        return InstitutionStudentsReportCardsTable
            .select()
            .where({
                report_card_id: reportCardId,
                student_id : parseInt(studentId),
                institution_id : institutionId,
                academic_period_id : academicPeriodId,
                education_grade_id : educationGradeId,
                institution_class_id: classId
            })
            .ajax({defer: true});
    };

    function saveRowData(comments, tab, institutionId, classId, educationGradeId, academicPeriodId, reportCardId, currentUserId) {
        var promises = [];

        angular.forEach(comments, function(obj, studentId) {
                var isSubjectTab = (tab.type == roles.TEACHER) ? true : false;

                var data = {
                    report_card_id: reportCardId,
                    student_id: parseInt(studentId),
                    institution_id: institutionId,
                    academic_period_id: academicPeriodId,
                    education_grade_id: educationGradeId,
                    institution_class_id: classId
                };

                if (isSubjectTab) {
                    commentsData = data;
                    commentsData["comments"] = obj.comments;
                    commentsData["report_card_comment_code_id"] = obj.comment_code;
                    commentsData["education_subject_id"] = tab.education_subject_id;
                    commentsData["staff_id"] = currentUserId;

                    // check if main student report card record exists
                    this.checkStudentReportCardExists(studentId, institutionId, classId, educationGradeId, academicPeriodId, reportCardId)
                    .then(function(response) {
                        var studentReportcard = response.data;

                        if (studentReportcard.length == 0) {
                            // save to both tables
                            promises.push(InstitutionStudentsReportCardsTable.save(data));
                            promises.push(InstitutionStudentsReportCardsCommentsTable.save(commentsData));

                        } else {
                            // save only to comments table
                            promises.push(InstitutionStudentsReportCardsCommentsTable.save(commentsData));
                        }

                    }, function(error) {
                        console.log(error);
                    });

                } else {
                    if (tab.type == roles.PRINCIPAL) {
                        data["principal_comments"] = obj.comments;
                    } else if (tab.type == roles.HOMEROOM_TEACHER) {
                        data["homeroom_teacher_comments"] = obj.comments;
                    }

                    promises.push(InstitutionStudentsReportCardsTable.save(data));
                }
        }, this);

        return $q.all(promises);
    };
}
