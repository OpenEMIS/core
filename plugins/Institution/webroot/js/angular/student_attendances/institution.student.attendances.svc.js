angular
    .module("institution.student.attendances.svc", ["kd.data.svc", "alert.svc"])
    .service(
        "InstitutionStudentAttendancesSvc",
        InstitutionStudentAttendancesSvc
    );

InstitutionStudentAttendancesSvc.$inject = [
    "$http",
    "$q",
    "$filter",
    "KdDataSvc",
    "AlertSvc",
    "UtilsSvc",
];

function InstitutionStudentAttendancesSvc(
    $http,
    $q,
    $filter,
    KdDataSvc,
    AlertSvc,
    UtilsSvc
) {
    const attendanceType = {
        NOTMARKED: {
            code: "NOTMARKED",
            icon: "fa fa-minus",
            color: "#999999",
        },
        PRESENT: {
            code: "PRESENT",
            icon: "fa fa-check",
            color: "#77B576",
        },
        LATE: {
            code: "LATE",
            icon: "fa fa-check-circle-o",
            color: "#999",
        },
        UNEXCUSED: {
            code: "UNEXCUSED",
            icon: "fa fa-circle-o",
            color: "#CC5C5C",
        },
        EXCUSED: {
            code: "EXCUSED",
            icon: "fa fa-circle-o",
            color: "#CC5C5C",
        },
        NoScheduledClicked: {
            code: "NoScheduledClicked",
            icon: "",
            color: "black",
        },
    };

    const icons = {
        REASON: "kd kd-reason",
        COMMENT: "kd kd-comment",
        PRESENT: "fa fa-minus",
    };

    const ALL_DAY_VALUE = -1;

    // POCOR-9572-GH: Умное кэширование (теперь это не монолит, а справочники)
    var cache = {
        absenceTypes: {},
        absenceReasons: {},
        academicPeriods: {},
        weeks: {},
        days: {},
        classes: {},
        grades: {},
        subjects: {},
        periods: {}
    };

    // Вспомогательная функция для генерации ключа кэша из аргументов
    function getCacheKey() {
        return Array.prototype.slice.call(arguments).join('_');
    }

    var translateText = {
        original: {
            OpenEmisId: "OpenEMIS ID",
            Name: "Name",
            Attendance: "Attendance",
            ReasonComment: "Reason / Comment",
            Monday: "Monday",
            Tuesday: "Tuesday",
            Wednesday: "Wednesday",
            Thursday: "Thursday",
            Friday: "Friday",
            Saturday: "Saturday",
            Sunday: "Sunday",
        },
        translated: {},
    };

    var controllerScope;

    var models = {
        AcademicPeriods: "AcademicPeriod.AcademicPeriods",
        StudentAttendances: "Institution.StudentAttendances",
        InstitutionClasses: "Institution.InstitutionClasses",
        InstitutionClassGrades: "Institution.InstitutionClassGrades",
        StudentAttendanceTypes: "Attendance.StudentAttendanceTypes",
        InstitutionClassSubjects: "Institution.InstitutionClassSubjects",
        AbsenceTypes: "Institution.AbsenceTypes",
        StudentAbsenceReasons: "Institution.StudentAbsenceReasons",
        StudentAbsencesPeriodDetails:
            "Institution.StudentAbsencesPeriodDetails",
        StudentAttendanceMarkTypes: "Attendance.StudentAttendanceMarkTypes",
        StudentAttendanceMarkedRecords:
            "Attendance.StudentAttendanceMarkedRecords",
    };

    var service = {
        init: init,
        translate: translate,

        getAttendanceTypeList: getAttendanceTypeList,
        getAbsenceTypeOptions: getAbsenceTypeOptions,
        getStudentAbsenceReasonOptions: getStudentAbsenceReasonOptions,
        // getAttendanceByOptions: getAttendanceByOptions, //POCOR-8874

        getTranslatedText: getTranslatedText,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getDayListOptions: getDayListOptions,
        getClassOptions: getClassOptions,
        getEducationGradeOptions: getEducationGradeOptions,
        getSubjectOptions: getSubjectOptions,
        getPeriodOptions: getPeriodOptions,
        getIsMarked: getIsMarked,
        getNoScheduledClassMarked: getNoScheduledClassMarked,
        getClassStudent: getClassStudent,

        getSingleDayColumnDefs: getSingleDayColumnDefs,
        getAllDayColumnDefs: getAllDayColumnDefs,

        saveAbsences: saveAbsences,
        savePeriodMarked: savePeriodMarked,
        editSavePeriodMarked: editSavePeriodMarked, //POCOR-6658 //POCOR-9406
        isMarkableSubjectAttendance: isMarkableSubjectAttendance,
        isMarkableAttendance: isMarkableAttendance, //POCOR-8874
    };

    return service;

    function init(baseUrl, scope) {
        controllerScope = scope;
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction("StudentAttendances");
        KdDataSvc.init(models);
    }

    function translate(data) {
        KdDataSvc.init({translation: "translate"});
        var success = function (response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success: success, defer: true});
    }

    function getAttendanceTypeList() {
        return attendanceType;
    }

    // data service
    function getTranslatedText() {
        var success = function (response, deferred) {
            var translatedObj = response.data;
            if (angular.isDefined(translatedObj)) {
                translateText = translatedObj;
            }
            deferred.resolve(angular.isDefined(translatedObj));
        };

        KdDataSvc.init({translation: "translate"});
        return translation.translate(translateText.original, {
            success: success,
            defer: true,
        });
    }

    function getAbsenceTypeOptions() {
        const key = 'all'; // Обычно список типов един для системы
        if (cache.absenceTypes[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.absenceTypes[key]);
        }

        var success = function (response, deferred) {
            var absenceType = response.data.data;
            if (angular.isObject(absenceType) && absenceType.length > 0) {
                cache.absenceTypes[key] = absenceType;
                deferred.resolve(absenceType);
            } else {
                deferred.reject("Error retrieving absence types");
            }
        };

        return AbsenceTypes.find("absenceTypeList").ajax({ success: success, defer: true });
    }

    function getStudentAbsenceReasonOptions() {
        const key = 'all';
        if (cache.absenceReasons[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.absenceReasons[key]);
        }

        var success = function (response, deferred) {
            var studentAbsenceReasons = response.data.data;
            if (angular.isObject(studentAbsenceReasons) && studentAbsenceReasons.length > 0) {
                cache.absenceReasons[key] = studentAbsenceReasons;
                deferred.resolve(studentAbsenceReasons);
            } else {
                deferred.reject("Error retrieving absence reasons");
            }
        };

        return StudentAbsenceReasons.select(["id", "name"]).ajax({ success: success, defer: true });
    }

    function getAcademicPeriodOptions(institutionId) {
        const key = getCacheKey(institutionId);
        if (cache.academicPeriods[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.academicPeriods[key]);
        }

        var success = function (response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                cache.academicPeriods[key] = periods;
                deferred.resolve(periods);
            } else {
                deferred.reject("Error retrieving academic periods");
            }
        };

        return AcademicPeriods.find("periodHasClass", { institution_id: institutionId })
            .ajax({ success: success, defer: true });
    }

    function getWeekListOptions(academicPeriodId) {
        const key = getCacheKey(academicPeriodId);
        if (cache.weeks[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.weeks[key]);
        }

        var success = function (response, deferred) {
            var academicPeriodObj = response.data.data;
            if (angular.isDefined(academicPeriodObj) && academicPeriodObj.length > 0) {
                var weeks = academicPeriodObj[0].weeks;
                if (angular.isDefined(weeks) && weeks.length > 0) {
                    cache.weeks[key] = weeks;
                    deferred.resolve(weeks);
                } else {
                    deferred.reject("Error retrieving week list");
                }
            } else {
                deferred.reject("Error retrieving week list");
            }
        };

        return AcademicPeriods.find("weeksForPeriod", { academic_period_id: academicPeriodId })
            .ajax({ success: success, defer: true });
    }

    function getDayListOptions(academicPeriodId, weekId, institutionId) {
        const key = getCacheKey(academicPeriodId, weekId, institutionId);
        if (cache.days[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.days[key]);
        }

        var success = function (response, deferred) {
            var dayList = response.data.data;
            if (angular.isObject(dayList) && dayList.length > 0) {
                cache.days[key] = dayList;
                deferred.resolve(dayList);
            } else {
                deferred.reject("Error retrieving day list");
            }
        };

        return AcademicPeriods.find("daysForPeriodWeek", {
            academic_period_id: academicPeriodId,
            week_id: weekId,
            institution_id: institutionId,
            school_closed_required: true,
        }).ajax({ success: success, defer: true });
    }

    function getClassOptions(institutionId, academicPeriodId) {
        const key = getCacheKey(institutionId, academicPeriodId);
        if (cache.classes[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.classes[key]);
        }

        var success = function (response, deferred) {
            var classList = response.data.data;
            if (angular.isObject(classList)) {
                cache.classes[key] = classList;
                deferred.resolve(classList);
            } else {
                deferred.reject("Error retrieving class list");
            }
        };

        return InstitutionClasses.find("classesByInstitutionAndAcademicPeriod", {
            institution_id: institutionId,
            academic_period_id: academicPeriodId,
        }).ajax({ success: success, defer: true });
    }

    function getEducationGradeOptions(institutionId, academicPeriodId, classId) {
        const key = getCacheKey(institutionId, academicPeriodId, classId);
        if (cache.grades[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.grades[key]);
        }

        var success = function (response, deferred) {
            var educationGradeList = response.data.data;
            if (angular.isObject(educationGradeList) && educationGradeList.length > 0) {
                cache.grades[key] = educationGradeList;
                deferred.resolve(educationGradeList);
            } else {
                deferred.reject("Error retrieving education grade list");
            }
        };

        return InstitutionClasses.find("gradesByInstitutionAndAcademicPeriodAndInstitutionClass", {
            institution_id: institutionId,
            academic_period_id: academicPeriodId,
            institution_class_id: classId,
        }).ajax({ success: success, defer: true });
    }

    function getSubjectOptions(institutionId, institutionClassId, academicPeriodId, day_id, educationGradeId) {
        const key = getCacheKey(institutionId, institutionClassId, academicPeriodId, day_id, educationGradeId);
        if (cache.subjects[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.subjects[key]);
        }

        var success = function (response, deferred) {
            var subjectList = response.data.data;
            if (angular.isObject(subjectList)) {
                cache.subjects[key] = subjectList;
                deferred.resolve(subjectList);
            } else {
                deferred.reject("Error retrieving subject list");
            }
        };

        return InstitutionClassSubjects.find("allSubjectsByClassPerAcademicPeriod", {
            institution_id: institutionId,
            institution_class_id: institutionClassId,
            academic_period_id: academicPeriodId,
            day_id: day_id,
            education_grade_id: educationGradeId,
        }).ajax({ success: success, defer: true });
    }

    function getPeriodOptions(institutionClassId, academicPeriodId, day_id, educationGradeId, weekStartDay, weekEndDay) {
        const key = getCacheKey(institutionClassId, academicPeriodId, day_id, educationGradeId, weekStartDay, weekEndDay);
        if (cache.periods[key]) {
            // console.log(`%c [CACHE] Извлечено из кеша в функции ${arguments.callee.name} с ключом: ${key}`, "color: #4CAF50; font-weight: bold;");
            return $q.resolve(cache.periods[key]);
        }
        var success = function (response, deferred) {
            var attendancePeriodList = response.data.data;
            if (angular.isObject(attendancePeriodList) && attendancePeriodList.length > 0) {
                cache.periods[key] = attendancePeriodList;
                deferred.resolve(attendancePeriodList);
            } else {
                deferred.reject("Error retrieving attendance period list");
            }
        };

        return StudentAttendanceMarkTypes.find("periodByClass", {
            institution_class_id: institutionClassId,
            academic_period_id: academicPeriodId,
            day_id: day_id,
            education_grade_id: educationGradeId,
            week_start_day: weekStartDay,
            week_end_day: weekEndDay,
        }).ajax({ success: success, defer: true });
    }

    function getClassStudent(params) {
        //POCOR-8874 start
        if (params.attendance_by == "period") {
            params.subject_id = 0;
        }
        //POCOR-8874 end
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            attendance_period_id: params.attendance_period_id,
            day_id: params.day_id,
            week_id: params.week_id,
            week_start_day: params.week_start_day,
            week_end_day: params.week_end_day,
            subject_id: params.subject_id,
            attendance_by: params.attendance_by
        };
        //POCOR-8874 start
        // console.log("getclass",extra.attendance_period_id);
        if (extra.attendance_period_id == "") {
            extra.attendance_period_id = 1;
        }
        //POCOR-8874 end
        // console.log(extra);
        if (
            extra.attendance_period_id == "" ||
            extra.institution_class_id == "" ||
            extra.academic_period_id == ""
        ) {
            return $q.reject(
                "There was an error when retrieving the class student list"
            );
        }

        var success = function (response, deferred) {
            // console.log("🔍 [Debug] Ответ от сервера:", response);

            if (!response || !response.data) {
                console.error("❌ [Error] Пустой ответ (Network OK, но данных нет)");
                deferred.reject("Empty response");
                return;
            }

            // Если бэкенд упал с ошибкой маппинга, тут часто будет null
            if (response.data.data === null) {
                console.group("❌ [Fatal] classStudents is NULL");
                console.error("Бэкенд вернул null. Проверьте логи PHP (debug.log).");
                // console.log("Содержимое response.data:", response.data);
                console.groupEnd();
                deferred.reject("Backend returned null");
                return;
            }

            var classStudents = response.data.data;

            if (angular.isArray(classStudents) || angular.isObject(classStudents)) {
                // console.log("✅ [Success] Загружено студентов:", classStudents.length || "Object received");
                deferred.resolve(classStudents);
            } else {
                console.error("❌ [Type Error] Ожидался массив, пришло:", typeof classStudents);
                deferred.reject("Invalid data type");
            }
        };

        var error = function (err, deferred) {
            console.group("🚨 [Ajax Error]");
            console.error("Статус:", err.status);
            console.error("Ошибка:", err);
            console.groupEnd();
            deferred.reject(err);
        };

        return StudentAttendances.find("classStudentsWithAbsence", extra).ajax({
            success: success,
            error: error,
            defer: true,
        });
    }

    function getIsMarked(params) {
        if (params.attendance_by == "period") {
            params.subject_id = 0;
        }
        // console.log("parms", params);
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            day_id: params.day_id,
            attendance_period_id: params.attendance_period_id,
            subject_id: params.subject_id,
        };

        if (extra.day_id == ALL_DAY_VALUE) {
            return $q.resolve(false);
        }

        var success = function (response, deferred) {
            var count = response.data.total;
            // console.log("count");
            // console.log(count);
            if (angular.isDefined(count)) {
                var isMarked = count > 0;
                deferred.resolve(isMarked);
            } else {
                deferred.reject(
                    "There was an error when retrieving the is_marked record"
                );
            }
        };

        return StudentAttendanceMarkedRecords.find(
            "periodIsMarked",
            extra
        ).ajax({success: success, defer: true});
    }

    function getNoScheduledClassMarked(params) {
        // console.log('[TEMP-LOG] getNoScheduledClassMarked: called', params); //POCOR-9652
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            day_id: params.day_id,
            attendance_period_id: params.attendance_period_id,
            subject_id: params.subject_id,
        };

        if (extra.day_id == ALL_DAY_VALUE) {
            return $q.resolve(false);
        }

        var success = function (response, deferred) {
            var count = response.data.total;
            // console.log('[TEMP-LOG] getNoScheduledClassMarked: response total=' + count); //POCOR-9652
            if (angular.isDefined(count)) {
                var isMarked = count > 0;
                // console.log('[TEMP-LOG] getNoScheduledClassMarked: isMarked=' + isMarked + ' (0=undo, 1=set)'); //POCOR-9652
                deferred.resolve(isMarked);
            } else {
                // console.log('[TEMP-LOG] getNoScheduledClassMarked: ERROR — count undefined'); //POCOR-9652
                deferred.reject(
                    "There was an error when retrieving the is_marked record"
                );
            }
        };

        return StudentAttendanceMarkedRecords.find(
            "NoScheduledClass",
            extra
        ).ajax({success: success, defer: true});
    }

    // save error
    function clearError(data, skipKey) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }

        angular.forEach(data.save_error, function (error, key) {
            if (key != skipKey) {
                data.save_error[key] = false;
            }
        });
    }

    function hasError(data, key) {
        return (
            angular.isDefined(data.save_error) &&
            angular.isDefined(data.save_error[key]) &&
            data.save_error[key]
        );
    }

    function saveAbsences(data, context) {
        // POCOR-9572: Debug logging (commented out for production)
        // console.group('💾 [SAVE] saveAbsences() ENTRY');
        // console.log('Step 1: Function called with data:', {
        //     student_id: data.student_id,
        //     absence_type_id: data.absence_type_id,
        //     student_absence_reason_id: data.student_absence_reason_id,
        //     comment: data.comment
        // });

        const isSubjectBased = context.attendance_by === "subject";

        const defaultReasonId = context.studentAbsenceReasons[0]['id'];
        const studentAbsenceData = {
            student_id: Number(data.student_id),
            institution_id: Number(data.institution_id),
            academic_period_id: Number(data.academic_period_id),
            institution_class_id: Number(data.institution_class_id),
            absence_type_id: Number(data.absence_type_id),
            student_absence_reason_id: data.student_absence_reason_id != null
                ? Number(data.student_absence_reason_id)
                : defaultReasonId,
            comment: data.comment,
            period: isSubjectBased ? 0 : Number(context.period),
            date: context.date,
            subject_id: isSubjectBased ? Number(context.subject_id) : 0,
            education_grade_id: Number(context.education_grade_id),
        };

        // console.log('Step 2: Built studentAbsenceData:', studentAbsenceData);

        const compositeKey = {
            student_id: studentAbsenceData.student_id,
            institution_id: studentAbsenceData.institution_id,
            academic_period_id: studentAbsenceData.academic_period_id,
            institution_class_id: studentAbsenceData.institution_class_id,
            date: studentAbsenceData.date,
            period: studentAbsenceData.period,
            subject_id: studentAbsenceData.subject_id,
        };

        // console.log('Step 3: Built compositeKey:', compositeKey);
        // console.log('Step 4: Calling find(first) to check if record exists...');
        // console.groupEnd();

        return StudentAbsencesPeriodDetails.find('first', compositeKey)
            .ajax({defer: true})
            .then(function (existing) {
                // console.group('💾 [SAVE] Step 5: find(first) completed');
                // console.log('Existing record response:', existing);

                const hasRecord =
                    existing &&
                    Array.isArray(existing.data) &&
                    existing.data.length > 0;

                const action = hasRecord ? 'edit' : 'save';
                // console.log('Step 6: Decision -', hasRecord ? 'UPDATE existing record' : 'CREATE new record');
                // console.log('Step 7: Calling StudentAbsencesPeriodDetails.' + action + '()...');
                // console.groupEnd();

                const operation = StudentAbsencesPeriodDetails[action](studentAbsenceData);

                return operation
                    .then(() => {
                        // console.group('💾 [SAVE] Step 8: ' + action + '() completed');
                        // console.log('Step 9: Calling find(first) again to verify save...');
                        // console.groupEnd();

                        return StudentAbsencesPeriodDetails.find('first', compositeKey)
                            .ajax({defer: true});
                    })
                    .then((verifyResult) => {
                        // console.group('💾 [SAVE] Step 10: Verification find() completed');
                        // console.log('Verification response:', verifyResult);

                        const saved =
                            Array.isArray(verifyResult.data) && verifyResult.data.length > 0
                                ? verifyResult.data[0]
                                : null;
                        const expected = studentAbsenceData;

                        // console.log('Step 11: Comparing saved vs expected:', {
                        //     saved: saved,
                        //     expected: expected
                        // });

                        // ✅ Special handling: if absence_type_id is 0/null, the record should not exist
                        if (!expected.absence_type_id || expected.absence_type_id == 0) {
                            const deleted = !saved;
                            // console.log('Step 12: PRESENT (absence_type_id=0) - Record should be deleted:', deleted);
                            // console.groupEnd();
                            return deleted
                                ? {success: true, deleted: true}
                                : {success: false, reason: "Expected record to be deleted"};
                        }

                        const matches =
                            saved &&
                            saved.absence_type_id == expected.absence_type_id &&
                            (saved.student_absence_reason_id == expected.student_absence_reason_id ||
                                (!saved.student_absence_reason_id && !expected.student_absence_reason_id)) &&
                            (saved.comment === expected.comment ||
                                (!saved.comment && !expected.comment));

                        // console.log('Step 12: Verification result -', matches ? '✅ MATCH' : '❌ MISMATCH');
                        // console.groupEnd();

                        if (matches) {
                            return {success: true, updated: hasRecord, verified: saved};
                        } else {
                            console.warn('Final DB mismatch:', saved, expected);
                            return {success: false, reason: 'DB verification mismatch'};
                        }
                    });
            })
            .catch(function (err) {
                console.error('Save or fetch failed:', err);
                return {success: false, reason: 'Save or fetch failed'};
            });
    }


    function savePeriodMarked(params, scope) {
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            date: params.day_id,
            period: params.attendance_period_id,
            subject_id: params.subject_id,
        };

        UtilsSvc.isAppendSpinner(true, "institution-student-attendances-table");
        StudentAttendanceMarkedRecords.save(extra)
            .then(
                function (response) {
                    AlertSvc.info(
                        scope,
                        "Attendances will be automatically saved."
                    );
                },
                function (error) {
                    AlertSvc.error(
                        scope,
                        "There was an error when saving the record"
                    );
                }
            )
            .finally(function () {
                UtilsSvc.isAppendSpinner(
                    false,
                    "institution-student-attendances-table"
                );
            });
    }

    function handleSaveResponse(response, data, dataKey, oldValue, oldParams) {
        clearError(data, dataKey);

        const hasError =
            !response ||
            (response.data && response.data.error && response.data.error.length > 0) ||
            response.success === false;

        if (hasError) {
            data.save_error[dataKey] = true;

            // revert old values (comment or absence_type_id, etc.)
            if (oldParams) {
                angular.forEach(oldParams, function (value, key) {
                    data[key] = value;
                });
            } else {
                data[dataKey] = oldValue;
            }
            console.error('hasError', response);
            // AlertSvc.error(scope, "There was an error when saving the record");
        } else {
            data.save_error[dataKey] = false;
            // AlertSvc.info(scope, "Attendances will be automatically saved.");
        }
    }

    /*
     * PCOOR-6658 STARTS
     * Create function for save attendance for multigrade class also.
     * author : Anubhav Jain <anubhav.jain@mail.vinove.com>
     */
    function editSavePeriodMarked(params) {
        // Force day_id into ISO format YYYY-MM-DD
        if (params.day_id instanceof Date) {
            params.day_id = params.day_id.toISOString().slice(0, 10);
        } else if (typeof params.day_id === 'string') {
            var parsedDate = new Date(params.day_id);
            if (!isNaN(parsedDate.getTime())) {
                params.day_id = parsedDate.toISOString().slice(0, 10);
            }
        }

        // POCOR-8874 start
        if (params.attendance_by == "subject") {
            params.attendance_period_id = 1;
        } else {
            params.subject_id = 0;
        }
        // POCOR-8874 ends

        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            education_grade_id: params.education_grade_id,
            academic_period_id: params.academic_period_id,
            attendance_period_id: params.attendance_period_id,
            day_id: params.day_id, // always in ISO format now
            week_id: params.week_id,
            week_start_day: params.week_start_day,
            week_end_day: params.week_end_day,
            subject_id: params.subject_id,
        };

        var success = function (response, deferred) {
            var classStudents = response;
            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject("There was an error when saving the record");
            }
        };

        return StudentAttendances.find("editSavePeriodMarked", extra)
            .ajax({ success: success, defer: true });
    }

    // column definitions
    function getAllDayColumnDefs(dayList, attendancePeriodList) {
        // console.log("dayList");
        // console.log(dayList);
        // console.log("attendancePeriodList");
        // console.log(attendancePeriodList);
        var columnDefs = [];
        var menuTabs = ["filterMenuTab"];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: "keep",
        };
        var isMobile =
            document.querySelector("html").classList.contains("mobile") ||
            navigator.userAgent.indexOf("Android") != -1 ||
            navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = "left";
        if (isMobile) {
            direction = "";
        } else if (isRtl) {
            direction = "right";
        }
        // POCOR-9572: Use flat fields from backend
        columnDefs.push({
            headerName: "OpenEMIS ID",
            field: "openemis_no",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text",
        });
        columnDefs.push({
            headerName: "Name",
            field: "student_name",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text",
        });

        angular.forEach(dayList, function (dayObj, dayKey) {
            // console.log("dayObj");
            // console.log(dayObj);
            if (dayObj.id != -1) {
                var childrenColDef = [];
                angular.forEach(
                    attendancePeriodList,
                    function (periodObj, periodKey) {
                        childrenColDef.push({
                            headerName: periodObj.id,
                            field:
                                "week_attendance." +
                                dayObj.day +
                                "." +
                                periodObj.id,
                            suppressSorting: true,
                            suppressResize: true,
                            menuTabs: [],
                            minWidth: 30,
                            headerClass: "children-period",
                            cellClass: "children-cell",
                            cellRenderer: function (params) {
                                if (angular.isDefined(params.value)) {
                                    var code = params.value;
                                    return getViewAllDayAttendanceElement(code);
                                }
                            },
                        });
                    }
                );

                var dayText = dayObj.name;

                var colDef = {
                    headerName: dayText,
                    children: childrenColDef,
                };

                columnDefs.push(colDef);
            }
        });

        return columnDefs;
    }

    function getSingleDayColumnDefs(period, noScheduledClicked) {
        var columnDefs = [];
        var menuTabs = ["filterMenuTab"];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: "keep",
        };
        var isMobile =
            document.querySelector("html").classList.contains("mobile") ||
            navigator.userAgent.indexOf("Android") != -1 ||
            navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = "left";
        if (isMobile) {
            direction = "";
        } else if (isRtl) {
            direction = "right";
        }

        columnDefs.push({
            headerName: "OpenEMIS ID",
            field: "openemis_no",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text",
        });
        columnDefs.push({
            headerName: "Name",
            field: "student_name",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text",
        });
        columnDefs.push({
            headerName: "Gender",
            field: "gender",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text",
        });

        // POCOR-9572: Use flat fields from backend
        columnDefs.push({
            headerName: "Attendance",
            field: "absence_type_id",
            suppressSorting: true,
            menuTabs: [],
            cellRenderer: function (params) {
                // POCOR-9572: Check if value is defined (including 0 which is PRESENT)
                if (angular.isDefined(params.value) || params.value === 0) {
                    var context = params.context;
                    var absenceTypeList = context.absenceTypes;
                    var isMarked = context.isMarked;
                    var isSchoolClosed = params.context.schoolClosed;
                    var mode = params.context.mode;
                    var data = params.data;

                    if (mode == "view") {
                        return getViewAttendanceElement(
                            data,
                            absenceTypeList,
                            isMarked,
                            isSchoolClosed,
                            noScheduledClicked
                        );
                    } else if (mode == "edit") {
                        var api = params.api;
                        return getEditAttendanceElement(
                            data,
                            absenceTypeList,
                            api,
                            context
                        );
                    }
                }
                return '';
            },
        });

        // POCOR-9572: Use flat fields from backend
        columnDefs.push({
            headerName: "Reason/Comment",
            field: "student_absence_reason_id",
            menuTabs: [],
            suppressSorting: true,
            cellRenderer: function (params) {
                if (angular.isDefined(params.data) && params.data.no_scheduled_class == 1) { //POCOR-9609: show dash for no_scheduled_class rows, consistent for all students
                    return '<i class="fa fa-minus"></i>';
                }
                if (angular.isDefined(params.value)) {
                    var data = params.data;
                    var context = params.context;
                    var studentAbsenceReasonList =
                        context.studentAbsenceReasons;
                    var absenceTypeList = context.absenceTypes;
                    var mode = context.mode;

                    if (
                        angular.isDefined(
                            params.data
                        )
                    ) {
                        var studentAbsenceTypeId =
                            params.data
                                .absence_type_id == null
                                ? 0
                                : params.data
                                    .absence_type_id;
                        var absenceTypeObj = absenceTypeList.find(
                            (obj) => obj.id == studentAbsenceTypeId
                        );

                        if (mode == "view") {
                            switch (absenceTypeObj.code) {
                                case attendanceType.PRESENT.code:
                                    return (
                                        '<i class="' + icons.PRESENT + '"></i>'
                                    );
                                //POCOR-7929 start
                                case attendanceType.NoScheduledClicked.code:
                                    return '<i class="kd-null btn btn-xs btn-default"></i>';
                                //POCOR-7929 end
                                case attendanceType.LATE.code:
                                case attendanceType.UNEXCUSED.code:
                                    var html = "";
                                    html += getViewCommentsElement(data);
                                    return html;
                                case attendanceType.EXCUSED.code:
                                    var html = "";
                                    html += getViewAbsenceReasonElement(
                                        data,
                                        studentAbsenceReasonList
                                    );
                                    html += getViewCommentsElement(data);
                                    return html;
                            }
                        } else if (mode == "edit") {
                            var api = params.api;
                            switch (absenceTypeObj.code) {

                                case attendanceType.PRESENT.code:
                                    return (
                                        '<i class="' + icons.PRESENT + '"></i>'
                                    );
                                case attendanceType.LATE.code:
                                case attendanceType.UNEXCUSED.code:
                                    var eCell = document.createElement("div");
                                    eCell.setAttribute(
                                        "class",
                                        "reason-wrapper"
                                    );
                                    var eTextarea = getEditCommentElement(
                                        data,
                                        context,
                                        api
                                    );
                                    eCell.appendChild(eTextarea);
                                    return eCell;
                                case attendanceType.EXCUSED.code:
                                    var eCell = document.createElement("div");
                                    eCell.setAttribute(
                                        "class",
                                        "reason-wrapper"
                                    );
                                    var eSelect = getEditAbsenceReasonElement(
                                        data,
                                        studentAbsenceReasonList,
                                        context,
                                        api
                                    );
                                    var eTextarea = getEditCommentElement(
                                        data,
                                        context,
                                        api
                                    );
                                    eCell.appendChild(eSelect);
                                    eCell.appendChild(eTextarea);
                                    return eCell;
                                default:
                                    break;
                            }
                        }
                    }
                }
            },
        });

        return columnDefs;
    }

    // cell renderer elements
    function getEditAttendanceElement(data, absenceTypeList, api, context) {
        var dataKey = "absence_type_id";
        var scope = context.scope;
        var eCell = document.createElement("div");
        eCell.setAttribute("class", "oe-select-wrapper input-select-wrapper");
        eCell.setAttribute("id", dataKey);

        if (data[dataKey] == null) {
            data[dataKey] = 0;
        }

        var selectAttendanceType = document.createElement("select");
        angular.forEach(absenceTypeList, function (obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            selectAttendanceType.appendChild(eOption);
        });

        if (hasError(data, dataKey)) {
            selectAttendanceType.setAttribute("class", "error");
        }

        selectAttendanceType.value = data[dataKey];
        selectAttendanceType.addEventListener("change", function () {
            // POCOR-9572: Debug logging (commented out for production)
            // console.group('🎯 [DROPDOWN] Attendance dropdown changed');
            const abs = data;
            const oldValue = abs[dataKey];
            abs[dataKey] = selectAttendanceType.value;
            // console.log('Old value:', oldValue, '→ New value:', abs[dataKey]);
            // console.log('Student ID:', data.student_id);
            // console.groupEnd();

            UtilsSvc.isAppendSpinner(true, "institution-student-attendances-table");

            saveAbsences(data, context)
                .then(function (response) {
                    // console.group('✅ [THEN] saveAbsences() resolved');
                    // console.log('Response received:', response);

                    handleSaveResponse(response, data, dataKey, oldValue);

                    const saved = response?.verified;
                    const wasDeleted = response?.deleted;

                    // console.log('Step 13: Processing response -', saved ? 'SAVED' : wasDeleted ? 'DELETED' : 'UNKNOWN');

                    if (saved) {
                        const absenceType = absenceTypeList.find(obj => obj.id == saved.absence_type_id);
                        // console.log('Step 14: Found absence type:', absenceType);

                        abs.absence_type_id = saved.absence_type_id;
                        abs.absence_type_code = absenceType?.code ?? null;
                        abs.student_absence_reason_id = saved.student_absence_reason_id ?? null;
                        abs.comment = saved.comment;

                        // console.log('Step 15: Updated data object:', {
                        //     absence_type_id: abs.absence_type_id,
                        //     absence_type_code: abs.absence_type_code,
                        //     student_absence_reason_id: abs.student_absence_reason_id,
                        //     comment: abs.comment
                        // });

                        var studentAbsenceReasonList =
                            context.studentAbsenceReasons;
                        // 💡 Auto-select first reason if EXCUSED and reason is null
                        if (
                            abs.absence_type_code === "EXCUSED" &&
                            !abs.student_absence_reason_id &&
                            studentAbsenceReasonList.length > 0
                        ) {
                            abs.student_absence_reason_id = studentAbsenceReasonList[0].id;
                            // console.log('Step 16: Auto-selected first reason for EXCUSED:', abs.student_absence_reason_id);
                        }

                    } else if (wasDeleted) {
                        abs.absence_type_id = 0;
                        abs.absence_type_code = "PRESENT";
                        abs.student_absence_reason_id = null;
                        abs.comment = null;
                        // console.log('Step 14: Deleted - reset to PRESENT');
                    }

                    // console.log('Step 17: THEN block completed, moving to FINALLY...');
                    // console.groupEnd();
                })
                .catch(function (error) {
                    console.error("Error saving absence:", error);
                    clearError(data, dataKey);
                    data.save_error[dataKey] = true;
                    abs[dataKey] = oldValue;

                    AlertSvc.error(scope, "There was an error when saving the record");
                })
                .finally(function () {
                    // console.group('🏁 [FINALLY] Cleanup and refresh');
                    // console.log('Step 18: Preparing to refresh...');

                    try {
                        // console.log('Step 19: Checking api...', api ? 'EXISTS' : 'UNDEFINED');

                        // POCOR-9572: Refresh cells to show updated values
                        // console.log('Step 20: Calling refreshCells()...');
                        api.refreshCells();
                        // console.log('Step 21: Cells refreshed successfully');

                        // POCOR-9572: Reset row heights so they adjust to content
                        // console.log('Step 22: Recalculating row heights...');
                        api.resetRowHeights();
                        // console.log('Step 23: Row heights updated');

                    } catch (error) {
                        console.error('ERROR during refresh:', error);
                    }

                    // console.log('Step 24: Removing spinner...');
                    UtilsSvc.isAppendSpinner(false, "institution-student-attendances-table");

                    // console.log('Step 25: ✅ SAVE PROCESS COMPLETE!');
                    // console.groupEnd();
                });
        });

        eCell.appendChild(selectAttendanceType);
        return eCell;
    }

    function setRowDatas(context, data) {
        const gridBody = document.querySelector('.ag-body-viewport');
        const previousScrollTop = gridBody ? gridBody.scrollTop : 0;

        const studentList = context.scope.$ctrl.classStudentList;

        studentList.forEach(function (dataItem) {
            const code = dataItem.absence_type_code;

            switch (code) {
                case "EXCUSED":
                    dataItem.rowHeight = 130;
                    break;
                case "UNEXCUSED":
                case "LATE":
                    dataItem.rowHeight = 80;
                    break;
                case null:
                case "PRESENT":
                default:
                    dataItem.rowHeight = 60;
            }
        });

        // Set data
        context.scope.$ctrl.gridOptions.api.setRowData(studentList);

        // Force row height recalculation
        context.scope.$ctrl.gridOptions.api.resetRowHeights();

        // Restore scroll position
        if (gridBody && previousScrollTop !== undefined) {
            gridBody.scrollTop = previousScrollTop;
        }
    }

    function getEditCommentElement(data, context, api) {
        var dataKey = "comment";
        var scope = context.scope;
        var attendanceComment = document.createElement("textarea");
        attendanceComment.setAttribute("placeholder", "Comments");
        attendanceComment.setAttribute("id", dataKey);

        if (hasError(data, dataKey)) {
            attendanceComment.setAttribute("class", "error");
        }

        attendanceComment.value = data[dataKey];
        attendanceComment.addEventListener("blur", function () {
            // POCOR-9572: Debug logging (commented out for production)
            // console.group('💬 [COMMENT] Comment field blur event');
            const oldValue = data.comment;
            data[dataKey] = attendanceComment.value;
            // console.log('Comment changed:', oldValue, '→', data[dataKey]);
            // console.log('Student ID:', data.student_id);
            // console.groupEnd();

            UtilsSvc.isAppendSpinner(true, "institution-student-attendances-table");

            saveAbsences(data, context)
                .then(function (response) {
                    // console.group('✅ [COMMENT THEN] saveAbsences() resolved');
                    // console.log('Response:', response);

                    handleSaveResponse(response, data, dataKey, oldValue);
                    const saved = response?.verified;
                    if (saved) {
                        const abs = data;

                        abs.absence_type_id = saved.absence_type_id;
                        abs.absence_type_code = saved.absence_type_code;
                        abs.student_absence_reason_id = saved.student_absence_reason_id;
                        abs.comment = saved.comment;

                        // console.log('Updated data object:', abs);
                    }
                    // console.log('THEN block completed, moving to FINALLY...');
                    // console.groupEnd();
                })
                .catch(function (error) {
                    console.error('Error saving comment:', error);
                    clearError(data, dataKey);
                    data.save_error[dataKey] = true;
                    data[dataKey] = oldValue;
                    AlertSvc.error(scope, "There was an error when saving the record");
                })
                .finally(function () {
                    // console.group('🏁 [COMMENT FINALLY] Cleanup');
                    // console.log('Refreshing cells...');

                    try {
                        // POCOR-9572: Refresh cells and recalculate row heights
                        api.refreshCells();
                        api.resetRowHeights();
                        // console.log('Cells refreshed and row heights updated');
                    } catch (error) {
                        console.error('ERROR during refresh:', error);
                    }

                    // console.log('Removing spinner...');
                    UtilsSvc.isAppendSpinner(false, "institution-student-attendances-table");
                    // console.log('✅ COMMENT SAVE COMPLETE!');
                    // console.groupEnd();
                });
        });

        return attendanceComment;
    }

    function getEditAbsenceReasonElement(
        data,
        studentAbsenceReasonList,
        context,
        api
    ) {
        var dataKey = "student_absence_reason_id";
        var scope = context.scope;
        var eSelectWrapper = document.createElement("div");
        eSelectWrapper.setAttribute(
            "class",
            "oe-select-wrapper input-select-wrapper"
        );
        eSelectWrapper.setAttribute("id", dataKey);

        var selectAbsenceReason = document.createElement("select");
        if (hasError(data, dataKey)) {
            selectAbsenceReason.setAttribute("class", "error");
        }

        if (data[dataKey] == null) {
            data[dataKey] =
                studentAbsenceReasonList[0].id;
        }

        angular.forEach(studentAbsenceReasonList, function (obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            selectAbsenceReason.appendChild(eOption);
        });

        selectAbsenceReason.value = data[dataKey];
        selectAbsenceReason.addEventListener("change", function () {
            // POCOR-9572: Debug logging (commented out for production)
            // console.group('📋 [REASON] Absence reason dropdown changed');
            const oldValue = data[dataKey];
            data[dataKey] = selectAbsenceReason.value;
            // console.log('Reason changed:', oldValue, '→', data[dataKey]);
            // console.log('Student ID:', data.student_id);
            // console.groupEnd();

            UtilsSvc.isAppendSpinner(true, "institution-student-attendances-table");

            saveAbsences(data, context)
                .then(function (response) {
                    // console.group('✅ [REASON THEN] saveAbsences() resolved');
                    // console.log('Response:', response);

                    handleSaveResponse(response, data, dataKey, oldValue);
                    const saved = response?.verified;
                    if (saved) {
                        const abs = data;

                        abs.absence_type_id = saved.absence_type_id;
                        abs.absence_type_code = saved.absence_type_code;
                        abs.student_absence_reason_id = saved.student_absence_reason_id;
                        abs.comment = saved.comment;

                        // console.log('Updated data object:', abs);
                    }
                    // console.log('THEN block completed, moving to FINALLY...');
                    // console.groupEnd();
                })
                .catch(function (error) {
                    console.error('Error saving reason:', error);
                    clearError(data, dataKey);
                    data.save_error[dataKey] = true;
                    data[dataKey] = oldValue;
                    AlertSvc.error(scope, "There was an error when saving the record");
                })
                .finally(function () {
                    // console.group('🏁 [REASON FINALLY] Cleanup');
                    // console.log('Refreshing cells...');

                    try {
                        // POCOR-9572: Refresh cells and recalculate row heights
                        api.refreshCells();
                        api.resetRowHeights();
                        // console.log('Cells refreshed and row heights updated');
                    } catch (error) {
                        console.error('ERROR during refresh:', error);
                    }

                    // console.log('Removing spinner...');
                    UtilsSvc.isAppendSpinner(false, "institution-student-attendances-table");
                    // console.log('✅ REASON SAVE COMPLETE!');
                    // console.groupEnd();
                });
        });

        eSelectWrapper.appendChild(selectAbsenceReason);
        return eSelectWrapper;
    }

    function getViewAttendanceElement(
        data,
        absenceTypeList,
        isMarked,
        isSchoolClosed,
        noScheduledClicked
    ) {
        // POCOR-9572: Check for flat field structure (no nested objects)
        if (angular.isDefined(data.absence_type_id) || data.absence_type_id === 0) {
            var html = "";
            if (isMarked) {
                var id =
                    data.absence_type_id === null
                        ? 0
                        : data.absence_type_id;
                if (noScheduledClicked || data.no_scheduled_class == 1) //POCOR-9609: use no_scheduled_class (PHP field) instead of is_NoClassScheduled (old unused field)
                    //POCOR-8333
                    //if(noScheduledClicked)
                    var absenceTypeObj = {
                        id: null,
                        code: "NoScheduledClicked",
                        name: "No Lessons",
                    };
                else
                    var absenceTypeObj = absenceTypeList.find(
                        (obj) => obj.id == id
                    );

                switch (absenceTypeObj.code) {
                    case attendanceType.PRESENT.code:
                        html =
                            '<div style="color: ' +
                            attendanceType.PRESENT.color +
                            ';"><i class="' +
                            attendanceType.PRESENT.icon +
                            '"></i> <span> ' +
                            absenceTypeObj.name +
                            " </span></div>";
                        break;
                    case attendanceType.LATE.code:
                        html =
                            '<div style="color: ' +
                            attendanceType.LATE.color +
                            ';"><i class="' +
                            attendanceType.LATE.icon +
                            '"></i> <span> ' +
                            absenceTypeObj.name +
                            " </span></div>";
                        break;
                    case attendanceType.UNEXCUSED.code:
                        html =
                            '<div style="color: ' +
                            attendanceType.UNEXCUSED.color +
                            '"><i class="' +
                            attendanceType.UNEXCUSED.icon +
                            '"></i> <span> ' +
                            absenceTypeObj.name +
                            " </span></div>";
                        break;
                    case attendanceType.EXCUSED.code:
                        html =
                            '<div style="color: ' +
                            attendanceType.EXCUSED.color +
                            '"><i class="' +
                            attendanceType.EXCUSED.icon +
                            '"></i> <span> ' +
                            absenceTypeObj.name +
                            " </span></div>";
                        break;
                    case attendanceType.NoScheduledClicked.code:
                        html =
                            '<div style="color: ' +
                            attendanceType.NoScheduledClicked.color +
                            '"> <span> ' +
                            absenceTypeObj.name +
                            " </span></div>";
                        break;
                    default:
                        break;
                }
                return html;
            } else {
                if (isSchoolClosed) {
                    // console.log('in')
                    html =
                        '<i style="color: #999999;" class="fa fa-minus"></i>';
                } else {
                    // console.log('out')
                    if (data.no_scheduled_class == 1) { //POCOR-9609: use no_scheduled_class (PHP field) instead of is_NoClassScheduled (old unused field)
                        html =
                            '<i style="color: #000000;"><span>No Lessons</span></i>';
                    } else {
                        html = '<i class="' + icons.PRESENT + '"></i>';
                    }
                }
            }
            return html;
        }
    }

    function getViewAbsenceReasonElement(data, studentAbsenceReasonList) {
        var absenceReasonId =
            data.student_absence_reason_id;
        var absenceReasonObj = studentAbsenceReasonList.find(
            (obj) => obj.id == absenceReasonId
        );
        var html = "";

        if (absenceReasonId === null) {
            html = '<i class="' + icons.PRESENT + '"></i>';
        } else {
            // console.log(absenceReasonId);
            // console.log(absenceReasonObj);
            if (absenceReasonObj && absenceReasonObj.name) {
                var reasonName = absenceReasonObj.name;
                html =
                    '<div class="absence-reason"><i class="' +
                    icons.REASON +
                    '"></i><span>' +
                    reasonName +
                    "</span></div>";
            }
        }

        return html;
    }

    function getViewCommentsElement(data) {
        var comment = data.comment;
        var html = "";
        if (comment != null) {
            html =
                '<div class="absences-comment"><i class="' +
                icons.COMMENT +
                '"></i><span>' +
                comment +
                "</span></div>";
        }
        return html;
    }

    function getViewAllDayAttendanceElement(code) {
        var html = "";
        switch (code) {
            case attendanceType.NOTMARKED.code:
                html = '<i class="' + attendanceType.NOTMARKED.icon + '"></i>';
                break;
            case attendanceType.NoScheduledClicked.code: //POCOR-7929
                html = '<i class="kd-null btn btn-xs btn-default"></i>';
                break;
            case attendanceType.PRESENT.code:
                html =
                    '<i style="color: ' +
                    attendanceType.PRESENT.color +
                    ';" class="' +
                    attendanceType.PRESENT.icon +
                    '"></i>';
                break;
            case attendanceType.LATE.code:
                html =
                    '<i style="color: ' +
                    attendanceType.LATE.color +
                    ';" class="' +
                    attendanceType.LATE.icon +
                    '"></i>';
                break;
            case attendanceType.UNEXCUSED.code:
                html =
                    '<i style="color: ' +
                    attendanceType.UNEXCUSED.color +
                    ';" class="' +
                    attendanceType.UNEXCUSED.icon +
                    '"></i>';
                break;
            case attendanceType.EXCUSED.code:
                html =
                    '<i style="color: ' +
                    attendanceType.EXCUSED.color +
                    ';" class="' +
                    attendanceType.EXCUSED.icon +
                    '"></i>';
                break;
            default:
                break;
        }
        return html;
    }

    function isMarkableSubjectAttendance(
        institutionId,
        academicPeriodId,
        selectedClass,
        selectedDay
    ) {
        var success = function (response, deferred) {
            // console.log("isMarkableSubjectAttendance");
            // console.log(response);
            if (angular.isDefined(response.data.data[0].code)) {
                var isMarkableSubjectAttendance = false;

                if (response.data.data[0].code == "SUBJECT") {
                    isMarkableSubjectAttendance = true;
                } else {
                    isMarkableSubjectAttendance = false;
                }
                deferred.resolve(isMarkableSubjectAttendance);
            } else {
                deferred.reject(
                    "There was an error when retrieving the isMarkableSubjectAttendance record"
                );
            }
        };

        var error = function (error) {
            console.error(
                "Error in retrieving isMarkableSubjectAttendance record"
            );
            console.error(error);
            // Handle the error here
        };

        return StudentAttendanceTypes.find("attendanceTypeCode", {
            institution_id: institutionId,
            academic_period_id: academicPeriodId,
            institution_class_id: selectedClass,
            day_id: selectedDay,
        }).ajax({success: success, error: error, defer: true});
    }

    function isMarkableAttendance(
        institutionId,
        academicPeriodId,
        selectedClass,
        selectedDay
    ) {
        var success = function (response, deferred) {

            // console.log(response);
            if (angular.isDefined(response.data.data[0].code)) {

                var isMarkableAttendance = false;

                // console.log("stude",response.data.data[0].StudentAttendanceMarkTypes);
                if (response.data.data[0].StudentAttendanceMarkTypes) {
                    isMarkableAttendance = true;
                } else {
                    isMarkableAttendance = false;
                }
                deferred.resolve(isMarkableAttendance);
            } else {
                deferred.reject(
                    "There was an error when retrieving the isMarkableSubjectAttendance record"
                );
            }
            // console.log("isMarkableAttendance",isMarkableAttendance);
        };

        var error = function (error) {
            console.error(
                "Error in retrieving isMarkableSubjectAttendance record"
            );
            console.error(error);
            // Handle the error here
        };

        return StudentAttendanceTypes.find("attendanceTypeCode", {
            institution_id: institutionId,
            academic_period_id: academicPeriodId,
            institution_class_id: selectedClass,
            day_id: selectedDay,
        }).ajax({success: success, error: error, defer: true});
    }
}
