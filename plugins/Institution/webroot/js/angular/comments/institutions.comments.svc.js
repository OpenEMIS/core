angular
    .module('institutions.comments.svc', ['kd.orm.svc'])
    .service('InstitutionsCommentsSvc', InstitutionsCommentsSvc);

InstitutionsCommentsSvc.$inject = ['$filter', '$q', 'KdOrmSvc'];

function InstitutionsCommentsSvc($filter, $q, KdOrmSvc) {
    var models = {
        ReportCardTable: 'ReportCard.ReportCards',
        ReportCardSubjectsTable: 'ReportCard.ReportCardSubjects',
        InstitutionStudentsReportCardsTable: 'Institution.InstitutionStudentsReportCards',
        ClassStudentsTable: 'Institution.InstitutionClassStudents'
    };

    var service = {
        init: init,
        getReportCard: getReportCard,
        getSubjects: getSubjects,
        getColumnDefs: getColumnDefs,
        renderText: renderText,
        // renderGrades: renderGrades,
        getRowData: getRowData,
        saveRowData: saveRowData
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('ReportCardComments');
        KdOrmSvc.init(models);
    };

    function getReportCard(reportCardId) {
        return ReportCardTable
            .get(reportCardId)
            .ajax({defer: true});
    };

    function getSubjects(reportCardId, classId) {
        var success = function(response, deferred) {
            var reportCardSubjects = response.data.data;

            if (angular.isObject(reportCardSubjects) && reportCardSubjects.length > 0) {
                deferred.resolve(reportCardSubjects);
            } else {
                deferred.reject('You have to configure the subjects for comments first');
            }
        };

        return ReportCardSubjectsTable
            .select()
            .find('MatchingClassSubjects', {
                report_card_id: reportCardId,
                institution_class_id: classId
            })
            .ajax({success: success, defer: true});
    };

    function getColumnDefs(action, tab, _comments) {
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
        var isSubjectTab = (tab.type == 'SUBJECT_TEACHER') ? true : false;

        if (isSubjectTab) {
            columnDefs.push({
                headerName: "Comment Code" + headerIcons,
                field: "comment_code",
                filterParams: filterParams
            });
        }

        // columnDefs.push({
        //     headerName: "Comments" + headerIcons,
        //     field: "comments",
        //     filterParams: filterParams
        // });

        var extra = {};
        var columnDef = {
            headerName: "Comments" + headerIcons,
            field: "comments",
            filterParams: filterParams
        };
        columnDef = this.renderText(allowEdit, columnDef, extra, _comments);
        columnDefs.push(columnDef);

        // if (isMarksType) {
        //     if (subject.examination_grading_type != null) {
        //         extra = {
        //             minMark: 0,
        //             passMark: subject.examination_grading_type.pass_mark,
        //             maxMark: subject.examination_grading_type.max
        //         };
        //     }

        //     columnDef = this.renderMarks(allowEdit, columnDef, extra, _results);
        // } else {
        //     if (subject.examination_grading_type != null) {
        //         var gradingOptions = {
        //             0 : {
        //                 id: 0,
        //                 code: '',
        //                 name: '-- Select --'
        //             }
        //         };

        //         angular.forEach(subject.examination_grading_type.grading_options, function(obj, key) {
        //             gradingOptions[obj.id] = obj;
        //         });

        //         extra = {
        //             gradingOptions: gradingOptions
        //         };
        //     }

        //     columnDef = this.renderGrades(allowEdit, columnDef, extra, _results);
        // }



        // columnDefs.push({
        //     headerName: "weight",
        //     field: 'weight',
        //     hide: true
        // });

        // var visibility = isMarksType ? false : true;
        // columnDefs.push({
        //     headerName: "Total Mark",
        //     field: "total_mark",
        //     filter: "number",
        //     hide: visibility,
        //     valueGetter: function(params) {
        //         var value = params.data[params.colDef.field];

        //         if (!isNaN(parseFloat(value))) {
        //             return $filter('number')(value, 2);
        //         } else {
        //             return '';
        //         }
        //     },
        //     filterParams: filterParams
        // });

        var bodyDir = getComputedStyle(document.body).direction;
        if (bodyDir == 'rtl') {
            columnDefs.reverse();
        }

        deferred.resolve(columnDefs);
        return deferred.promise;
    };

    function renderText(allowEdit, cols, extra, _comments) {
        cols = angular.merge(cols, {
            filter: 'text'
        });

        if (allowEdit) {
            cols = angular.merge(cols, {
                editable: true,
                cellClass: 'oe-cell-highlight'
            });
        }

        return cols;
    };

    // function renderGrades(allowEdit, cols, extra, _results) {
    //     var gradingOptions = extra.gradingOptions;

    //     if (allowEdit) {
    //         cols = angular.merge(cols, {
    //             cellClass: 'oe-cell-highlight',
    //             cellRenderer: function(params) {
    //                 if (params.value.length == 0) {
    //                     params.value = 0;
    //                 }

    //                 var oldValue = params.value;
    //                 var studentId = params.data.student_id;
    //                 var institutionId = params.data.institution_id;

    //                 var eCell = document.createElement('div');
    //                 eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");

    //                 var eSelect = document.createElement("select");

    //                 var isAnswerValid = false;
    //                 angular.forEach(gradingOptions, function(obj, key) {
    //                     var eOption = document.createElement("option");
    //                     var labelText = obj.name;
    //                     if (obj.code.length > 0) {
    //                         labelText = obj.code + ' - ' + labelText;
    //                     }
    //                     eOption.setAttribute("value", key);
    //                     eOption.innerHTML = labelText;
    //                     eSelect.appendChild(eOption);
    //                     if (oldValue == obj.id) {
    //                         isAnswerValid = true;
    //                     }
    //                 });

    //                 // set selected value only when it is a valid option from gradingOptions list
    //                 if (isAnswerValid) {
    //                     eSelect.value = params.value;
    //                 }

    //                 eSelect.addEventListener('change', function () {
    //                     var newValue = eSelect.value;
    //                     params.data[params.colDef.field] = newValue;

    //                     if (angular.isUndefined(_results[studentId])) {
    //                         _results[studentId] = {};
    //                     }

    //                     if (angular.isUndefined(_results[studentId][institutionId])) {
    //                         _results[studentId][institutionId] = {gradingOptionId: ''};
    //                     }

    //                     _results[studentId][institutionId]['gradingOptionId'] = newValue;
    //                 });

    //                 eCell.appendChild(eSelect);

    //                 return eCell;
    //             },
    //             suppressMenu: true
    //         });
    //     } else {
    //         cols = angular.merge(cols, {
    //             cellRenderer: function(params) {
    //                 var cellValue = '';
    //                 if (params.value.length != 0 && params.value != 0) {
    //                     // show option code and name only when it is a valid option from gradingOptions list
    //                     if (angular.isDefined(gradingOptions[params.value])) {
    //                         cellValue = gradingOptions[params.value]['name'];
    //                         if (gradingOptions[params.value]['code'].length > 0) {
    //                             cellValue = gradingOptions[params.value]['code'] + ' - ' + cellValue;
    //                         }
    //                     }
    //                 }
    //                 // var cellValue = (params.value.length != 0 && params.value != 0) ? gradingOptions[params.value]['name'] : '';

    //                 var eCell = document.createElement('div');
    //                 var eLabel = document.createTextNode(cellValue);
    //                 eCell.appendChild(eLabel);

    //                 return eCell;
    //             },
    //             suppressMenu: true
    //         });
    //     }

    //     return cols;
    // };

    function getRowData(academicPeriodId, institutionId, institutionClassId, educationGradeId, reportCardId, tab, limit, page) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.error)) {
                deferred.reject(response.data.error);
            } else {
                var reportCardStudents = response.data.data;

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
                                openemis_id: reportCardStudent._matchingData.Students.openemis_no,
                                name: reportCardStudent._matchingData.Students.name,
                                student_id: reportCardStudent.student_id,
                                student_status: reportCardStudent._matchingData.StudentStatuses.name,
                                comments: reportCardStudent.comments,
                            };

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

        return InstitutionStudentsReportCardsTable
            .select()
            .find('Comments', {
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

    function saveRowData(comments, tab, institutionId, classId, educationGradeId, academicPeriodId, reportCardId) {
        var promises = [];

        angular.forEach(comments, function(obj, studentId) {
                var isSubjectTab = (tab.type == 'SUBJECT_TEACHER') ? true : false;

                var data = {
                    "report_card_id": reportCardId,
                    "student_id" : parseInt(studentId),
                    "institution_id" : institutionId,
                    "academic_period_id" : academicPeriodId,
                    "education_grade_id" : educationGradeId
                };

                if (isSubjectTab) {
                    data["education_subject_id"] = tab.education_subject_id;
                    console.log(data);

                } else {
                    data["institution_class_id"] = classId;

                    if (tab.type == 'PRINCIPAL') {
                        data["principal_comments"] = obj.comments;
                    } else if (tab.type == 'HOMEROOM_TEACHER') {
                        data["homeroom_teacher_comments"] = obj.comments;
                    }

                    promises.push(InstitutionStudentsReportCardsTable.save(data));
                }
        }, this);

        return $q.all(promises);
    };
}
