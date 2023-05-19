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
        Route::get('institutions', 'InstitutionController@getInstitutionsList')->middleware('auth.jwt');

        Route::get('institutions/list', 'RegistrationController@institutionDropdown');
        Route::get('institutions/areas/list', 'RegistrationController@administrativeAreasList');


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
    }
);



