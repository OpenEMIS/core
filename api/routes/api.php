<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BaseApi\CrudApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v4'], function () {
    Route::get(
        'clear-cache',
        function () {
            Artisan::call('route:clear');
            Artisan::call('clear-compiled');
            Artisan::call('config:cache');
            Artisan::call('cache:clear');
            return "Cache is cleared";
        }
    );
});
Route::group(['prefix' => 'v5'], function () {
    Route::get(
        'clear-cache',
        function () {
            Artisan::call('route:clear');
            Artisan::call('clear-compiled');
            Artisan::call('config:cache');
            Artisan::call('cache:clear');
            return "Cache is cleared";
        }
    );
});
Route::group(['prefix' => 'v4'], function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('login', 'Authentication\LoginController@login');
});

Route::group(['prefix' => 'v5'], function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('login', 'Authentication\LoginController@login');
});

Route::group(
    ["middleware" => "auth.jwt",'prefix' => 'v4'],
    function () {
        //POCOR-7545 starts
        Route::get('institutions/{institutionId}/students/meals', 'InstitutionController@getStudentsMealsByInstitutionId');
        //POCOR-7545 ends

        // POCOR-7547 starts
        Route::get('attendance-mark-types/education-grades', 'StudentController@getEducationGrades');
        Route::get('institutions/{institutionId}/classes/subjects', 'StudentController@getClassesSubjects');
        Route::post('institutions/classes/attendances', 'StudentController@addClassAttendances');
        Route::post('institutions/students/absences', 'StudentController@addStudentAbsences');
        Route::post('institutions/staff/attendances', 'StudentController@addStaffAttendances');
        Route::post('institutions/staff', 'StudentController@updateStaffDetails');
        // POCOR-7547 ends


        //POCOR-7651 starts
        Route::get('institutions/{institutionId}/students/absences', 'StudentController@getInstitutionStudentAbsences');
        Route::get('institutions/{institutionId}/students/{studentId}/absences', 'StudentController@getInstitutionStudentAbsencesData');
        Route::get('institutions/students', 'StudentController@getStudents');
        Route::get('institutions/{institutionId}/students', 'StudentController@getInstitutionStudents')->where('institutionId', '[0-9]+');
        Route::get('institutions/{institutionId}/students/{studentId}', 'StudentController@getInstitutionStudentData');
        Route::get('institutions/students/absences', 'StudentController@getStudentAbsences');

        //POCOR-7651 ends


        Route::get('institutions', 'InstitutionController@getInstitutionsList')->middleware('auth.jwt');

        Route::get('institutions/list', 'RegistrationController@institutionDropdown');
        Route::get('institution-types/list', 'RegistrationController@institutionTypesDropdown');

        Route::get('area-levels/list', 'RegistrationController@areaLevelsDropdown');
        Route::get('areas/list', 'RegistrationController@areasDropdown');

        //POCOR-7728 starts...
        Route::get('area-administrative-levels/list', 'RegistrationController@areaAdministrativeLevelsDropdown');
        Route::get('area-administratives/list', 'RegistrationController@areasAdministrativeDropdown');
        //POCOR-7728 ends...

        Route::get('institutions/areas/list', 'RegistrationController@administrativeAreasList');

        Route::get('institutions/subjects/staff', 'InstitutionController@getSubjectsStaffList');

        Route::get('institutions/grades', 'InstitutionController@getGradesList');
        Route::get('institutions/grades/{grade_id}/list', 'RegistrationController@getInstitutionGradesList');
        Route::get('institutions/classes', 'InstitutionController@getClassesList');
        Route::get('institutions/subjects', 'InstitutionController@getSubjectsList');
        Route::get('institutions/shifts', 'InstitutionController@getInstitutionShifts');
        Route::get('institutions/areas', 'InstitutionController@getInstitutionAreas');
        Route::get('institutions/summaries', 'InstitutionController@getSummariesList');
        Route::get('institutions/staff', 'InstitutionController@getStaffList');
        Route::get('institutions/positions', 'InstitutionController@getPositionsList');
        Route::get('institutions/room-type-summaries', 'InstitutionController@roomTypeSummaries');
        Route::get('institutions/grades/summaries', 'InstitutionController@getGradeSummariesList');
        Route::get('institutions/{id}/grades/summaries', 'InstitutionController@getInstitutionGradeSummariesList');

        // POCOR-7394 starts
        Route::get('institutions/genders', 'InstitutionController@getInstitutionGenders');
        // POCOR-7394 ends

        Route::get('institutions/{id}/grades/student-nationality-summaries', 'InstitutionController@getInstitutionGradeStudentNationalitySummariesList');


        Route::get('institutions/student-nationality-summaries', 'InstitutionController@getStudentNationalitySummariesList');
        Route::get('institutions/grades/student-nationality-summaries', 'InstitutionController@getGradesStudentNationalitySummariesList');

        Route::get('institutions/{id}', 'InstitutionController@getInstitutionData')->where('id', '[0-9]+');


        Route::get('institutions/{id}/grades', 'InstitutionController@getInstitutionGradeList');
        Route::get('institutions/{id}/grades/{grade_id:}', 'InstitutionController@getInstitutionGradeData');


        Route::get('institutions/{id}/classes', 'InstitutionController@getInstitutionClassesList');
        Route::get('institutions/{id}/classes/{classId}', 'InstitutionController@getInstitutionClassData');


        Route::get('institutions/{id}/subjects', 'InstitutionController@getInstitutionSubjectsList');
        Route::get('institutions/{id}/subjects/{subject_id}', 'InstitutionController@getInstitutionSubjectsData');


        Route::get('institutions/{id}/shifts', 'InstitutionController@getInstitutionShiftsList');
        Route::get('institutions/{id}/shifts/{shiftId}', 'InstitutionController@getInstitutionShiftsData');


        Route::get('institutions/{id}/areas', 'InstitutionController@getInstitutionAreasList');
        Route::get('institutions/{id}/areas/{areaAdministrativeId}', 'InstitutionController@getInstitutionAreasData');


        Route::get('institutions/{id}/summaries', 'InstitutionController@getInstitutionSummariesList');


        Route::get('institutions/{id}/grades/{gradeId}/summaries', 'InstitutionController@getInstitutionGradeSummariesData');


        Route::get('institutions/{id}/student-nationality-summaries', 'InstitutionController@getInstitutionStudentNationalitySummariesList');


        Route::get('institutions/{id}/grades/{gradeId}/student-nationality-summaries', 'InstitutionController@getInstitutionGradeStudentNationalitySummaries');


        Route::get('users', 'UserController@getUsersList');
         // POCOR-7394 starts
         Route::get('users/genders', 'UserController@getUsersGender');
         // POCOR-7394 ends
         Route::get('users/{userId}', 'UserController@getUsersData')->where('userId', '[0-9]+');


        Route::get('institutions/{id}/staff', 'InstitutionController@getInstitutionStaffList')->where('id', '[0-9]+');
        Route::get('institutions/{id}/staff/{staffId}', 'InstitutionController@getInstitutionStaffData')->where('id', '[0-9]+')->where('staffId', '[0-9]+');


        Route::get('institutions/{id}/positions', 'InstitutionController@getInstitutionPositionsList');
        Route::get('institutions/{id}/positions/{positionId}', 'InstitutionController@getInstitutionPositionsData');


        Route::get('locale-contents', 'InstitutionController@localeContentsList');
        Route::get('locale-contents/{id}', 'InstitutionController@localeContentsData');


        Route::get('institutions/{id}/room-type-summaries', 'InstitutionController@institutionRoomTypeSummaries');



        Route::get('systems/levels/cycles/programmes/grades/subjects', 'EducationSystemController@getAllEducationSystems');

        Route::get('systems/{system_id}/levels/cycles/programmes/grades/subjects', 'EducationSystemController@getEducationStructureSystems');

        Route::get('systems/{system_id}/levels/{level_id}/cycles/programmes/grades/subjects', 'EducationSystemController@getEducationStructureLevel');

        Route::get('systems/{system_id}/levels/{level_id}/cycles/{cycle_id}/programmes/grades/subjects', 'EducationSystemController@getEducationStructureCycle');

        Route::get('systems/{system_id}/levels/{level_id}/cycles/{cycle_id}/programmes/{programme_id}/grades/subjects', 'EducationSystemController@getEducationStructureProgramme');

        Route::get('systems/{system_id}/levels/{level_id}/cycles/{cycle_id}/programmes/{programme_id}/grades/{grade_id}/subjects', 'EducationSystemController@getEducationStructureGrade');

        Route::get('systems/{system_id}/levels/{level_id}/cycles/{cycle_id}/programmes/{programme_id}/grades/{grade_id}/subjects/{subject_id}', 'EducationSystemController@getEducationStructureSubject');


        Route::get('systems/{system_id}/levels/{level_id}/cycles/{cycle_id}/programmes/{programme_id}/grades/{grade_id}/competencies', 'EducationSystemController@getCompetencies');


        Route::get('academic-periods/list', 'RegistrationController@academicPeriodsList');
        Route::get('systems/levels/cycles/programmes/grades/list', 'RegistrationController@educationGradesList');
        Route::get('nationalities', 'RegistrationController@nationalityList');
        Route::get('identity-types/list', 'RegistrationController@identityTypeList');
        Route::get('student-custom-fields', 'RegistrationController@getStudentCustomFields');
        Route::post('otp-generate', 'RegistrationController@generateOtp');
        Route::post('otp-verify', 'RegistrationController@verifyOtp');
        Route::get('users/openemis_id/{openemis_id}', 'RegistrationController@autocompleteOpenemisNo');
        //Route::get('users/identity-types/{identity_type_id}/{identity_number}', 'RegistrationController@autocompleteIdentityNo');
        Route::get('details-by-emis/{id}', 'RegistrationController@detailsByEmis');
        Route::post('institutions/{institution_id}/student-admission', 'RegistrationController@institutionStudents');

        Route::post("storecustomfieldfile","RegistrationController@storecustomfieldfile");

        Route::get('systems/{system_id}/levels/{level_id}/cycles/{cycle_id}/programmes/{programme_id}/grades/{grade_id}/reportcards', 'EducationSystemController@reportCardLists');

        Route::post('institutions/{institutionId}/classes/{classId}/reportcardcomment', 'InstitutionController@reportCardCommentAdd');

        Route::post('institutions/{institutionId}/classes/{classId}/reportcardcomment/homeroom', 'InstitutionController@reportCardCommentHomeroomAdd');

        Route::post('institutions/{institutionId}/classes/{classId}/reportcardcomment/principal', 'InstitutionController@reportCardCommentPrincipalAdd');



        Route::get('institutions/{institutionId}/grades/{gradeId}/students/{studentId}', 'InstitutionController@getInstitutionGradeStudentdata');

        Route::post('institutions/students/competencies/results', 'InstitutionController@addCompetencyResults');

        Route::post('institutions/students/competencies/item/comments', 'InstitutionController@addCompetencyComments');

        Route::post('institutions/students/competencies/periods/comments', 'InstitutionController@addCompetencyPeriodComments');


        Route::delete('institutions/institution-classes/education-grades/class-attendance', 'InstitutionController@deleteClassAttendance');

        Route::delete('institutions/student/{studentId}/absence', 'InstitutionController@deleteStudentAttendance');


        Route::get('institutions/{institutionId}/students/{studentId}/assessment-item-results', 'InstitutionController@getStudentAssessmentItemResult');
        Route::get('area-administrative/display-address-area-level', 'InstitutionController@displayAddressAreaLevel');

        Route::get('area-administrative/display-birthplace-area-level', 'InstitutionController@displayBirthplaceAreaLevel');


        Route::post('institutions/save-student', 'UserController@saveStudentData');
        Route::post('institutions/save-staff', 'UserController@saveStaffData');
        Route::post('institutions/save-guardian', 'UserController@saveGuardianData');
        // POCOR-7394-S starts
        Route::get('institutions/getStudentAdmissionStatus', 'UserController@getStudentAdmissionStatus');//POCOR-7716
        Route::get('absence-reasons', 'InstitutionController@getAbsenceReasons');
        Route::get('absence-types', 'InstitutionController@getAbsenceTypes');
        Route::get('area-administratives', 'InstitutionController@getAreaAdministratives');
        Route::get('area-administratives/{areaadministrativeId}', 'InstitutionController@getAreaAdministrativesById');

        Route::get('institutions/localities/{localitiesId}', 'InstitutionController@getInstitutionsLocalitiesById');
        Route::get('institutions/ownerships/{ownershipId}', 'InstitutionController@getInstitutionsOwnershipsById');
        Route::get('institutions/sectors/{sectorId}', 'InstitutionController@getInstitutionSectorsById');
        Route::get('institutions/providers/{providersId}', 'InstitutionController@getInstitutionProvidersById');
        Route::get('institutions/types/{typesId}', 'InstitutionController@getInstitutionTypesById');
        Route::get('institutions/provider/{sectorId}', 'InstitutionController@getInstitutionProviderBySectorId');


        Route::get('meal-programmes', 'InstitutionController@getMealProgrammes');

        // POCOR-7394-S ends

        // POCOR-7546 starts
        Route::get('assessments/education-grades', 'AssessmentController@getEducationGradeList');
        Route::get('assessments/items', 'AssessmentController@getAssessmentItemList');
        Route::get('assessments/periods', 'AssessmentController@getAssessmentPeriodList');
        Route::get('assessments/items/grading-types', 'AssessmentController@getAssessmentItemGradingTypeList');
        Route::get('assessments/grading-options', 'AssessmentController@getAssessmentGradingOptionList');

        Route::get('behaviours/categories', 'InstitutionController@getBehaviourCategories');
        Route::get('behaviours/categories/students', 'InstitutionController@getStudentBehaviourCategories');//POCOR-8711
        Route::get('behaviours/categories/staff', 'InstitutionController@getStaffBehaviourCategories');//POCOR-8711
        Route::get('institutions/{institutionId}/students/{studentId}/behaviours', 'InstitutionController@getInstitutionStudentBehaviour');


        Route::get('institutions/{institutionId}/institution-classes/{institutionClassId}/education-grades/{educationGradeId}/students', 'InstitutionController@getInstitutionClassEducationGradeStudents');
        Route::get('institutions/{institutionId}/education-grades/{educationGradeId}/institution-subjects/students', 'InstitutionController@getInstitutionEducationSubjectStudents');

        Route::post('institutions/students/assessment-item-results', 'InstitutionController@addStudentAssessmentItemResult');
        Route::post('institutions/students/behaviours', 'InstitutionController@addStudentBehaviour');

        Route::delete('institutions/{institutionId}/students/{studentId}/behaviours/{behaviourId}', 'InstitutionController@deleteStudentBehaviour');

        // POCOR-7546 starts
        // POCOR-7368 starts
        Route::get('textbooks-statuses', 'TextbookController@getTextbookStatuses');
        Route::get('textbooks-dimensions', 'TextbookController@getTextbookDimensions');
        Route::get('textbooks-conditions', 'TextbookController@getTextbookConditions');
        Route::get('textbooks/{textbookId}', 'TextbookController@getTextbookByID');
        Route::get('institutions/{institutionId}/textbooks/{textbookId}', 'TextbookController@getInstitutionTextbookdata');

        Route::post('textbooks', 'TextbookController@addTextbooks');
        Route::post('institutions/{institutionId}/textbooks', 'TextbookController@addInstitutionTextbooks');
        // POCOR-7368 ends


        Route::group(
            ["namespace" => "Administration\Examinations\Exams"],
            function () {
                Route::get("exams/{examId}", 'ExaminationController@getExaminationDetails');
                Route::get("exams/{examId}/centres/{centreId}", 'ExaminationController@getCenterExaminationDetails');
                Route::get("exams/{examId}/centres/{centreId}/students/{studentId}", 'ExaminationController@getCenterExaminationStudentDetails');
                // start POCOR - 8076
                Route::get("exams/{examId}/centres/{centreId}/subjects", 'ExaminationController@examinationCenterExaminationSubjects');
                Route::get("exams/{examId}/centres/{centreId}/subjects/{subjectId}/students", 'ExaminationController@examinationCenterExaminationSubjectsStudents');
                Route::post("exams/student-subject-result", 'ExaminationController@examStudentSubjectResult');

                // end POCOR - 8076
            }
        );

        // POCOR-7545 starts

        Route::get('security-role-functions', 'InstitutionController@getSecurityRoleFunction');
        Route::get('security-group-users', 'InstitutionController@getSecurityGroupUsers');
        Route::get('institutions/students/meals', 'InstitutionController@getInstitutionStudentsMeals');

        Route::get('institutions/students/{studentID}/statuses', 'InstitutionController@getInstitutionStudentStatusByStudentId');

        Route::post('institutions/students', 'InstitutionController@addInstitutionStudent');
        Route::post('institutions/staff/payslips', 'InstitutionController@addInstitutionStaffPayslip');
        Route::post('institutions/students/meal-benefits', 'InstitutionController@addInstitutionStudentMealBenefits');
        Route::post('institutions/meals/distributions', 'InstitutionController@addInstitutionMealDistributions');

        Route::post('institutions', 'InstitutionController@addInstitution');
        Route::post('users', 'UserController@addUsers');

        // POCOR-7545 ends


        //POCOR - 7773
        Route::post('institutions/{institutionId}/classes/{classId}', 'InstitutionController@updateInstitutionClass');
        Route::post('institutions/{institutionId}/subject/{subjectId}', 'InstitutionController@updateInstitutionSubject');

        //POCOR - 7773 ends

        //POCOR-7754 starts
        Route::get('notices', 'WorkbenchController@getNoticesList');

        Route::get('institutions/survey/forms', 'WorkbenchController@getInstitutionStaffSurveys');
        Route::get('institutions/students/withdraw', 'WorkbenchController@getInstitutionStudentWithdraw');
        Route::get('institutions/students/admission', 'WorkbenchController@getInstitutionStudentAdmission');
        Route::get('institutions/students/transferout', 'WorkbenchController@getInstitutionStudentTransferOut');
        Route::get('institutions/students/transferin', 'WorkbenchController@getInstitutionStudentTransferIn');
        Route::get('institutions/behaviour/students', 'WorkbenchController@getInstitutionStudentBehaviour');

        Route::get('institutions/behaviour/staff', 'WorkbenchController@getInstitutionStaffBehaviour');
        Route::get('staff/career/leave', 'WorkbenchController@getInstitutionStaffLeave');
        Route::get('staff/career/appraisals', 'WorkbenchController@getStaffAppraisals');
        Route::get('institutions/staff/release', 'WorkbenchController@getStaffRelease');
        Route::get('institutions/staff/transferout', 'WorkbenchController@getStaffTransferOut');
        Route::get('institutions/staff/transferin', 'WorkbenchController@getStaffTransferIn');
        Route::get('institutions/staff/changeinassignment', 'WorkbenchController@getChangeInAssignment');
        Route::get('staff/training/needs', 'WorkbenchController@getStaffTrainingNeeds');
        Route::get('staff/professionaldevelopment/licenses', 'WorkbenchController@getStaffLicenses');

        Route::get('administration/training/courses', 'WorkbenchController@getTrainingCourses');
        Route::get('administration/training/sessions', 'WorkbenchController@getTrainingSessions');
        Route::get('administration/training/results', 'WorkbenchController@getTrainingResults');
        Route::get('institutions/visits/requests', 'WorkbenchController@getVisitRequests');
        Route::get('administration/training/applications', 'WorkbenchController@getTrainingApplications');
        Route::get('administration/scholarships/applications', 'WorkbenchController@getScholarshipApplications');
        Route::get('institutions/cases', 'WorkbenchController@getInstitutionCases');
        //Route::get('institutions/positions', 'WorkbenchController@getInstitutionPositions');
        Route::get('minidashboard', 'WorkbenchController@getMinidashboardData');
        //POCOR-7754 ends





        //POCOR-7852 starts...
        Route::get('assessments/{assessment_id}/assessmentperiods', 'AssessmentController@getAssessmentUniquePeriodList');
        Route::get('assessments/{assessment_id}', 'AssessmentController@getAssessmentData')->where('assessment_id', '[0-9]+');
        Route::get('assessments/{assessment_id}/assessmentitems', 'AssessmentController@assessmentItemsList');

        Route::get('institutions/subject/student', 'AssessmentController@getInstitutionSubjectStudent');
        //POCOR-7852 end...

        //POCOR-7865 starts...
        Route::post('schedules/timetables/lessons', 'ScheduleController@addLesson');
        Route::delete('institutions/{institutionId}/schedules/timetables/lessons/{id}', 'ScheduleController@deleteTimeTableLessonById');
        Route::get('schedules/timetables/statuses', 'ScheduleController@getTimeTableStatus');
        Route::get('schedules/timetables/{id}', 'ScheduleController@getTimeTableById');
        Route::get('schedules/timetables/{id}/lessons', 'ScheduleController@getLessonsByTimeTableId');
        Route::get('schedules/lessons/types', 'ScheduleController@getLessonType');
        Route::get('schedules/timeslots/{intervalId}', 'ScheduleController@getTimeSlotsByIntervalId');

        Route::get('weekdays', 'ScheduleController@workingDayOfWeek');
        Route::get('institutions/classes/{id}/grades', 'InstitutionController@institutionClassGrade');
        Route::get('institutions/{institutionId}/academicperiods/{academicYearId}/rooms', 'InstitutionController@institutionRooms');
        Route::get('institutions/classes/{id}/subjects', 'InstitutionController@institutionClassSubjects')->where('id', '[0-9]+');

        //POCOR-7865 end...

        //POCOR-7856 starts...
        Route::get('/institutions/classes/reportcards/subject/comments', 'ReportCardController@getReportCardStudents');
        Route::get('/institutions/classes/reportcards/subjects', 'ReportCardController@getReportCardSubjects');
        //POCOR-7856 ends...


        //POCOR-8068 starts...
        Route::get('institutions/{institutionId}/meal-programmes', 'MealController@getMealInstitutionProgrammes');
        Route::get('meal-benefit-types', 'MealController@getMealBenefits');
        Route::get('institutions/{institutionId}/meal-students', 'MealController@getMealStudents');
        Route::get('institutions/{institutionId}/meal-distributions', 'MealController@getMealDistributions');
        //POCOR-8068 end...


        //POCOR-7853 starts
        Route::get('academic-periods/{academicperiodId}', 'AttendanceController@getAcademicPeriodData');
        Route::get('academic-periods', 'AttendanceController@getAcademicPeriods');
        Route::get('academic-periods/{academicperiodId}/weeks', 'AttendanceController@getAcademicPeriodsWeeks');
        Route::get('academic-periods/{academicperiodId}/weeks/{weekId}/days', 'AttendanceController@getAcademicPeriodsWeekDays');
        Route::get('institutions/{institution_id}/staff/attendances', 'AttendanceController@getStaffAttendances');

        Route::get('institutions/{institution_id}/shift-options', 'AttendanceController@getInstitutionShiftOption');
        //POCOR-7853 end
        Route::get('institutions/{institutionId}/staff/{staffId}/attendances', 'AttendanceController@getStaffAttendancesDetails');//POCOR-8888



        //POCOR-7854 start
        Route::get('grades/{gradeId}/attendance-types', 'AttendanceController@getAttendanceTypes');
        Route::get('institutions/{institutionId}/grades/{gradeId}/classes/{classId}/subjects', 'AttendanceController@allSubjectsByClassPerAcademicPeriod');
        Route::get('institutions/{institutionId}/grades/{gradeId}/classes/{classId}/student-attendance-types', 'AttendanceController@getStudentAttendanceMarkType');
        Route::get('institutions/{institutionId}/grades/{gradeId}/classes/{classId}/student-attendances', 'AttendanceController@getStudentAttendanceList');
        Route::get('institutions/{institutionId}/grades/{gradeId}/classes/{classId}/student-attendance-marked', 'AttendanceController@getStudentAttendanceMarkedRecordList');
        //POCOR-7854 end


        //POCOR-8023 starts
        Route::get('/system-configurations', 'SystemConfigurationController@allConfigurationItems');
        Route::get('/system-configurations/{configId}', 'SystemConfigurationController@configurationItemById');
        //POCOR-8023 ends

        ///POCOR-8121 start
        Route::get('/institution-units', 'InstitutionController@units');
        Route::get('/institution-courses', 'InstitutionController@courses');
        Route::get('/institutions/{institutionId}/academic-period/{academicPeriodId}/shifts', 'InstitutionController@shifts');
        Route::get('/institutions/{institutionId}/staffs', 'InstitutionController@staffs');
        Route::get('/institutions/{institutionId}/rooms', 'InstitutionController@rooms');
        Route::get('/institutions/{institutionId}/education-grades/{educationGradeId}/institution-subjects/{institutionSubjectId}/classes', 'InstitutionController@subjectClasses');
        Route::get('/institutions/{institutionId}/classes/{classId}/unassigned-students', 'InstitutionController@unassignedStudentsInClass');
        Route::get('/institutions/{institutionId}/subjects/{subjectId}/unassigned-students', 'InstitutionController@unassignedStudentsInSubject');

        //POCOR-8121 end

        //POCOR-8104 Start...
        Route::get('user-types', 'DirectoryController@getUserTypeList');
        Route::get('users/generate-openemis-id', 'DirectoryController@getUniqueOpenemisId');
        Route::get('users/generate-password', 'DirectoryController@getAutoGeneratedPassword');
        Route::get('contact-types', 'DirectoryController@getContactTypes');
        Route::get('staff-custom-fields', 'DirectoryController@getStaffCustomFields');
        Route::get('field-options', 'DirectoryController@getFieldOptions');
        Route::get('field-option/{fieldOptionId}', 'DirectoryController@getFieldOptionData');
        Route::get('users/identity-types/{identityTypeId}/{identityNumber}', 'DirectoryController@getUserByIdentityNumber');
        Route::post('users/basic-information', 'DirectoryController@getUserByBasicInfo');
        Route::get('relationship-types', 'DirectoryController@getRelationshipTypes');
        Route::get('staff-types', 'DirectoryController@getStaffType');
        //POCOR-8104 End...

        //POCOR-8136 Starts
        Route::get('permissions', 'UserController@getUserPermissions');
        //POCOR-8136 ends


        //POCOR-8139 Starts
        Route::post('external-data-sources', 'UserController@externalDataSources');
        //POCOR-8139 ends

        //POCOR-8078 starts
        Route::get('meal-programmes/{mealProgrammeId}', 'MealController@getMealProgrammeData');
        Route::get('meal-targets', 'MealController@getMealTargets');
        Route::get('meal-implementers', 'MealController@getMealImplementers');
        Route::get('meal-nutritions', 'MealController@getMealNutritions');
        Route::get('meal-ratings', 'MealController@getMealRatings');
        Route::get('meal-statuses', 'MealController@getMealStatusTypes');
        Route::get('meal-food-types', 'MealController@getMealFoodTypes');
        //POCOR-8078 ends


        //POCOR-8197 Starts
        Route::get('institutions/{institutionId}/grade-list', 'InstitutionController@getGradesViaInstitutionId');
        //POCOR-8197 ends

        //POCOR-8194 starts
        Route::get('staff/position/grades', 'DirectoryController@getStaffPositionGrades');
        //POCOR-8194 ends


        //POCOR-8259 start...
        Route::get('themes', 'ThemeController@getAllThemes');
        Route::get('themes/{themeId}', 'ThemeController@getThemeViaId');
        //POCOR-8259 end...



        //POCOR-8100 start...
        Route::get('training-courses', 'TrainingController@getAllTrainingCourses');
        Route::get('training-courses/{courseId}', 'TrainingController@getTrainingCourseData');
        Route::get('training-providers', 'TrainingController@getTrainingProviders');
        Route::get('training-providers/{providerId}', 'TrainingController@getTrainingProvidersData');
        Route::get('training-sessions', 'TrainingController@getTrainingSessions');
        Route::get('training-sessions/{sessionId}', 'TrainingController@getTrainingSessionData');
        Route::get('training-sessions/{sessionId}/results', 'TrainingController@getTrainingSessionResults');
        Route::get('training-sessions/{sessionId}/results/{userId}', 'TrainingController@getTrainingSessionResultsViaUserId');
        //POCOR-8100 end...

        //POCOR-8260 start...
        Route::get('/institutions/classes/reportcards/comment/codes', 'ReportCardController@getReportCardCommentCodes');
        //POCOR-8260 end...


        //POCOR-8270 start...
        Route::get('/security-roles/{roleId}', 'ReportCardController@getSecurityRoleData');
        Route::get('/reportcards/{reportcardId}', 'ReportCardController@getReportCardData');
        //POCOR-8270 end...



        //POCOR-8295 start...
        Route::get('/institutions/schedule-timetables', 'ScheduleController@getScheduleTimetables');
        Route::get('/institutions/{institutionId}/schedule-timetables', 'ScheduleController@getScheduleTimetablesViaInstitutionId');
        Route::get('/institutions/schedule-timetables/{scheduleTimetableId}', 'ScheduleController@getScheduleTimetableData');
        //POCOR-8295 end...

        //POCOR-8438 start...
        Route::post('/institutions/students/meals/import', 'MealController@getStudentMealImport');
        Route::get('/institutions/students/meals/export', 'MealController@getStudentMealExport');
        Route::get('/institutions/students/meals/import/template', 'MealController@getStudentMealImportTemplate');
        //POCOR-8348 end...

        //POCOR-8292 start...
        Route::get('/assessments/{assessment_id}/periods', 'AssessmentController@getAssessmentViaAcademicTerm');
        //POCOR-8292 end...


        //POCOR-8363 start...
        Route::get('/institutions/students/attendances/export', 'AttendanceController@getStudentAttendancesExport');
        Route::get('/institutions/students/attendances/import/template', 'AttendanceController@getStudentAttendancesImportTemplate');
        Route::post('/institutions/students/attendances/import', 'AttendanceController@studentAttendancesImport');
        Route::get('/institutions/students/attendances/no-scheduled-class', 'AttendanceController@studentAttendancesNoScheduledClass');
        //POCOR-8363 end...


        //POCOR-7429 start...
        Route::get('surveys', 'SurveyController@getSurveys');
        Route::get('survey/download/xform/{surveyFormId}', 'SurveyController@downloadXform');
        Route::get('survey/checkins/xform/{surveyFormId}/{insCode}/{academicPeriodCode}', 'SurveyController@checkInsXform');
        Route::get('survey/studentlist/xform/{surveyFormId}/{insCode}/{academicPeriodCode}', 'SurveyController@getStudentListForSurvey');
        Route::post('survey/upload', 'SurveyController@uploadXform');
        //POCOR-7429 end...
        //POCOR-8397 start...
        Route::get('/academic-period/archive', 'AttendanceController@getArchiveAcademicPeriods');
        Route::get('/institutions/{institutionId}/grades/{gradeId}/classes/{classId}/student-attendance-marked/archive', 'AttendanceController@getStudentAttendanceMarkedRecordArchiveList');
        Route::get('/institutions/{institutionId}/grades/{gradeId}/classes/{classId}/student-attendance/archive', 'AttendanceController@getStudentAttendanceArchiveList');
        Route::get('/institutions/students/attendances/export/archive', 'AttendanceController@getStudentAttendanceArchiveExport');
        //POCOR-8397 end...


        //POCOR-8617 start...
        Route::post('/institutions/{institutionId}/classes/{classId}/student-report-cards/{studentId}/pdf', 'ReportCardController@studentReportCardPdfDownload');
        Route::post('/institutions/{institutionId}/classes/{classId}/student-report-cards/{studentId}/xls', 'ReportCardController@studentReportCardExcelDownload');
        //POCOR-8617 end...

        //POCOR-8519 start...
        Route::get('workbenches', 'WorkbenchController@getAllWorkbenches');
        //POCOR-8519 end...
        //POCOR-8221 start...
        Route::get('institutions/{institutionId}/students/{studentId}/student-transfer', 'StudentController@getStudentTransferData');
        Route::post('institutions/{institutionId}/student-transfer', 'StudentController@addStudentTransferData');
        //POCOR-8221 end...

        //POCOR-8616 starts...
        Route::get('schedule/timetable-overview', 'TimetableOverviewController@timetableOverview');
        Route::get('schedule/timetable-download', 'TimetableOverviewController@scheduleTimeTableExport');
        //POCOR-8616 end

        //POCOR-8666 start
        Route::get('scanned/{openemis_no}', 'ScannedController@scannedUserOpenemisNo');
        Route::post('scanned', 'ScannedController@addScannedUserData');
        Route::get('scanned', 'ScannedController@scannedUserListing');
        //POCOR-8666 end
        Route::get('scanned/data/export', 'ScannedController@institutionScannedExport');//POCOR-8793
        Route::get('scanned/user/{scannedId}', 'ScannedController@scannedUserDetails');//POCOR-8824
        Route::get('students/{openemis_no}/absences', 'StudentController@getStudentAbsencesDetails'); //POCOR-8880

        //POCOR-8619 START
        Route::post('institutions/students/assessment-item-exemption', 'AssessmentController@saveAssessmentItemExemption');
        //POCOR-8619 END

        // POCOR-8862 start
        Route::get('guardians/{openemisId}', 'UserController@getGuardianByOpenemisNo')->where('openemisNo', '[\pL0-9]+'); // POCOR-8840
        Route::get('users/username/{username}', 'UserController@getUserByUsername')->where('username', '[^\s]+'); // POCOR-8862
        // POCOR8862 end

        // POCOR-8896 start
        Route::match(['post', 'patch', 'put'], 'users/openemisId/{openemis_no}', 'UserController@updateUserByOpenemisId')
            ->where('openemis_no', '[\pL0-9]+');
        // POCOR-8896 end

        // POCOR-8912 start
        Route::get('users/email/{email}', 'UserController@getUserByEmail'); // POCOR-8912
        // POCOR-8912 end

    }
);

Route::group(["middleware" => "auth.jwt", "prefix" => "v5"], function () {
    // POCOR-8915 start
    // should be always the last, as it is all-consuming
    Route::group(
        ['namespace' => 'BaseApi', 'middleware' => 'auth.jwt'],
        function () {
            Route::match(['GET', 'POST', 'PUT', 'DELETE'], '/{any}', [CrudApiController::class, 'common'])
                ->where('any', '.*');
        });
    // POCOR-8915 end
    // for v5 apis
        //Route::match(['get', 'post', 'put', 'delete'], '{action}', [TestController::class, 'handle']);
});
