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
        Route::get('institutions/grades', 'InstitutionController@getGradesList');
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
    }
);


