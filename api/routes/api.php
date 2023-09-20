<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', 'Authentication\LoginController@login');


Route::group(
    ["middleware" => "auth.jwt"],
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
        Route::get('institutions/{institutionId}/students', 'StudentController@getInstitutionStudents');
        Route::get('institutions/{institutionId}/students/{studentId}', 'StudentController@getInstitutionStudentData');
        Route::get('institutions/students/absences', 'StudentController@getStudentAbsences');
        
        //POCOR-7651 ends


        Route::get('institutions', 'InstitutionController@getInstitutionsList')->middleware('auth.jwt');

        Route::get('institutions/list', 'RegistrationController@institutionDropdown');
        Route::get('institution-types/list', 'RegistrationController@institutionTypesDropdown');

        Route::get('area-levels/list', 'RegistrationController@areaLevelsDropdown');
        Route::get('areas/list', 'RegistrationController@areasDropdown');

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

        Route::get('institutions/{id}', 'InstitutionController@getInstitutionData');


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
         Route::get('users/{userId}', 'UserController@getUsersData');


        Route::get('institutions/{id}/staff', 'InstitutionController@getInstitutionStaffList');
        Route::get('institutions/{id}/staff/{staffId}', 'InstitutionController@getInstitutionStaffData');


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
        Route::get('nationalities/list', 'RegistrationController@nationalityList');
        Route::get('identity-types/list', 'RegistrationController@identityTypeList');
        Route::get('student-custom-fields', 'RegistrationController@getStudentCustomFields');
        Route::post('otp-generate', 'RegistrationController@generateOtp');
        Route::post('otp-verify', 'RegistrationController@verifyOtp');
        Route::get('users/openemis_id/{openemis_id}', 'RegistrationController@autocompleteOpenemisNo');
        Route::get('users/identity-types/{identity_type_id}/{identity_number}', 'RegistrationController@autocompleteIdentityNo');
        Route::get('details-by-emis/{id}', 'RegistrationController@detailsByEmis');
        Route::post('institutions/{institution_id}/student-admission', 'RegistrationController@institutionStudents');


        Route::get('systems/{system_id}/levels/{level_id}/cycles/{cycle_id}/programmes/{programme_id}/grades/{grade_id}/reportcards', 'EducationSystemController@reportCardLists');

        Route::post('institutions/{institutionId}/classes/{classId}/reportcardcomment', 'InstitutionController@reportCardCommentAdd');

        Route::post('institutions/{institutionId}/classes/{classId}/reportcardcomment/homeroom', 'InstitutionController@reportCardCommentHomeroomAdd');

        Route::post('institutions/{institutionId}/classes/{classId}/reportcardcomment/principal', 'InstitutionController@reportCardCommentPrincipalAdd');



        Route::get('institutions/{institutionId}/grades/{gradeId}/students/{studentId}', 'InstitutionController@getInstitutionGradeStudentdata');
        
        Route::post('institutions/students/competencies/results', 'InstitutionController@addCompetencyResults');

        Route::post('institutions/students/competencies/comments', 'InstitutionController@addCompetencyComments');

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

        Route::get('meal-benefits', 'InstitutionController@getMealBenefits');
        Route::get('meal-programmes', 'InstitutionController@getMealProgrammes');

        // POCOR-7394-S ends

        // POCOR-7546 starts
        Route::get('assessments/education-grades', 'AssessmentController@getEducationGradeList');
        Route::get('assessments/items', 'AssessmentController@getAssessmentItemList');
        Route::get('assessments/periods', 'AssessmentController@getAssessmentPeriodList');
        Route::get('assessments/items/grading-types', 'AssessmentController@getAssessmentItemGradingTypeList');
        Route::get('assessments/grading-options', 'AssessmentController@getAssessmentGradingOptionList');

        Route::get('behaviours/categories', 'InstitutionController@getBehaviourCategories');
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

    }
);



