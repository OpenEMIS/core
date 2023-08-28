<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssessmentItemResultRequest;
use Illuminate\Http\Request;
use App\Services\InstitutionService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ReportCardCommentAdd;
use App\Http\Requests\ReportCardCommentHomeroomAdd;
use App\Http\Requests\CompetencyResultsAddRequest;
use App\Http\Requests\CompetencyCommentAddRequest;
use App\Http\Requests\CompetencyPeriodCommentAddRequest;
use App\Http\Requests\DeleteClassAttendanceRequest;
use App\Http\Requests\StudentBehavioursRequest;
use Exception;

class InstitutionController extends Controller
{
    protected $institutionService;

    public function __construct(
        InstitutionService $institutionService
    ) {
        $this->institutionService = $institutionService;
    }


    public function getInstitutionsList(Request $request)
    {
        try {
            $data = $this->institutionService->getInstitutions($request);
            return $this->sendSuccessResponse("Institutions List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution List Not Found');
        }
    }


    public function getInstitutionData(int $id)
    {
        try {
            $data = $this->institutionService->getInstitutionData($id);
            return $this->sendSuccessResponse("Institutions Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Data Not Found');
        }
    }


    public function getGradesList(Request $request)
    {
        try {
            $data = $this->institutionService->getGradesList($request);
            return $this->sendSuccessResponse("Grades List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grades List Not Found');
        }
    }


    public function getInstitutionGradeList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeList($request, $institutionId);
            return $this->sendSuccessResponse("Grades List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grades List Not Found');
        }
    }


    public function getInstitutionGradeData(int $institutionId, int $gradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeData($institutionId, $gradeId);
            return $this->sendSuccessResponse("Grades Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grades Data Not Found');
        }
    }



    public function getClassesList(Request $request)
    {
        try {
            $data = $this->institutionService->getClassesList($request);
            return $this->sendSuccessResponse("Classes List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Classes List Not Found');
        }
    }


    public function getInstitutionClassesList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionClassesList($request, $institutionId);
            return $this->sendSuccessResponse("Classes List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Classes List Not Found');
        }
    }


    public function getInstitutionClassData(int $institutionId, int $classId)
    {
        try {
            $data = $this->institutionService->getInstitutionClassData($institutionId, $classId);
            return $this->sendSuccessResponse("Class Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Data Not Found');
        }
    }


    public function getSubjectsList(Request $request)
    {
        try {
            $data = $this->institutionService->getSubjectsList($request);
            return $this->sendSuccessResponse("Subjects List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects List Not Found');
        }
    }


    public function getInstitutionSubjectsList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionSubjectsList($request, $institutionId);
            return $this->sendSuccessResponse("Subjects List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects List Not Found');
        }
    }


    public function getInstitutionSubjectsData(int $institutionId, int $subjectId)
    {
        try {
            $data = $this->institutionService->getInstitutionSubjectsData($institutionId, $subjectId);
            return $this->sendSuccessResponse("Subjects Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects Data Not Found');
        }
    }


    public function getInstitutionShifts(Request $request)
    {
        try {
            $data = $this->institutionService->getInstitutionShifts($request);
            return $this->sendSuccessResponse("Shifts List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Shifts List Not Found');
        }
    }


    public function getInstitutionShiftsList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionShiftsList($request, $institutionId);
            return $this->sendSuccessResponse("Shifts List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Shifts List Not Found');
        }
    }


    public function getInstitutionShiftsData(int $institutionId, int $shiftId)
    {
        try {
            $data = $this->institutionService->getInstitutionShiftsData($institutionId, $shiftId);
            return $this->sendSuccessResponse("Shifts Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Shifts Data Not Found');
        }
    }


    public function getInstitutionAreas(Request $request)
    {
        try {
            $data = $this->institutionService->getInstitutionAreas($request);
            return $this->sendSuccessResponse("Areas List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas List Not Found');
        }
    }


    public function getInstitutionAreasList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionAreasList($request, $institutionId);
            return $this->sendSuccessResponse("Areas List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas List Not Found');
        }
    }


    public function getInstitutionAreasData(int $institutionId, int $areaAdministrativeId)
    {
        try {
            $data = $this->institutionService->getInstitutionAreasData($institutionId, $areaAdministrativeId);
            return $this->sendSuccessResponse("Areas Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas Data Not Found');
        }
    }



    public function getSummariesList(Request $request)
    {
        try {
            $data = $this->institutionService->getSummariesList($request);
            return $this->sendSuccessResponse("Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Summaries List Not Found');
        }
    }


    public function getInstitutionSummariesList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionSummariesList($request, $institutionId);
            return $this->sendSuccessResponse("Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Summaries List Not Found');
        }
    }


    public function getGradeSummariesList(Request $request)
    {
        try {
            $data = $this->institutionService->getGradeSummariesList($request);
            return $this->sendSuccessResponse("Grade Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries List Not Found');
        }
    }


    public function getInstitutionGradeSummariesList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeSummariesList($request, $institutionId);
            return $this->sendSuccessResponse("Grade Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries List Not Found');
        }
    }


    public function getInstitutionGradeSummariesData(int $institutionId, int $gradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeSummariesData($institutionId, $gradeId);
            return $this->sendSuccessResponse("Grade Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries Data Not Found');
        }
    }


    public function getStudentNationalitySummariesList(Request $request)
    {
        try {
            $data = $this->institutionService->getStudentNationalitySummariesList($request);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionStudentNationalitySummariesList(Request $request, $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionStudentNationalitySummariesList($request, $institutionId);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getGradesStudentNationalitySummariesList(Request $request)
    {
        try {
            $data = $this->institutionService->getGradesStudentNationalitySummariesList($request);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionGradeStudentNationalitySummariesList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeStudentNationalitySummariesList($request, $institutionId);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionGradeStudentNationalitySummaries(Request $request, int $institutionId, int $gradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeStudentNationalitySummaries($request, $institutionId, $gradeId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getStaffList(Request $request)
    {
        try {
            $data = $this->institutionService->getStaffList($request);

            return $this->sendSuccessResponse("Institutions Staff List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Staff List Not Found');
        }
    }


    public function getInstitutionStaffList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionStaffList($request, $institutionId);
            
            return $this->sendSuccessResponse("Institutions Staff List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Staff List Not Found');
        }
    }


    public function getInstitutionStaffData(int $institutionId, int $staffId)
    {
        try {
            $data = $this->institutionService->getInstitutionStaffData($institutionId, $staffId);
            
            return $this->sendSuccessResponse("Institutions Staff Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Staff Data Not Found');
        }
    }



    public function getPositionsList(Request $request)
    {
        try {
            $data = $this->institutionService->getPositionsList($request);
            
            return $this->sendSuccessResponse("Institutions Positions List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions List Not Found');
        }
    }



    public function getInstitutionPositionsList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionPositionsList($request, $institutionId);
            
            return $this->sendSuccessResponse("Institutions Positions List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions List Not Found');
        }
    }


    public function getInstitutionPositionsData(int $institutionId, int $positionId)
    {
        try {
            $data = $this->institutionService->getInstitutionPositionsData($institutionId, $positionId);
            
            return $this->sendSuccessResponse("Institutions Positions Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions Data Not Found');
        }
    }


    public function localeContentsList(Request $request)
    {
        try {
            $data = $this->institutionService->localeContentsList($request);
            
            return $this->sendSuccessResponse("Locale Contents List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Locale Contents List Not Found');
        }
    }


    public function localeContentsData(int $localeId)
    {
        try {
            $data = $this->institutionService->localeContentsData($localeId);
            
            return $this->sendSuccessResponse("Locale Contents Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Locale Contents Data Not Found');
        }
    }


    public function roomTypeSummaries(Request $request)
    {
        try {
            $data = $this->institutionService->roomTypeSummaries($request);
            
            return $this->sendSuccessResponse("Room Type Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }


    public function institutionRoomTypeSummaries(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->institutionRoomTypeSummaries($request, $institutionId);
            
            return $this->sendSuccessResponse("Room Type Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }


    public function reportCardCommentAdd(ReportCardCommentAdd $request, int $institutionId, int $classId)
    {
        try {
            $data = $this->institutionService->reportCardCommentAdd($request, $institutionId, $classId);
            
            if($data == 0){
                return $this->sendErrorResponse("Student is not enrolled in the class.");
            }elseif ($data == 1) {
                return $this->sendSuccessResponse("Report card comment added successfully.", $data);
            } else {
                return $this->sendErrorResponse('Something went wrong.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }


    public function reportCardCommentHomeroomAdd(ReportCardCommentHomeroomAdd $request, int $institutionId, int $classId)
    {
        try {
            $data = $this->institutionService->reportCardCommentHomeroomAdd($request, $institutionId, $classId);
            
            if($data == 0){
                return $this->sendErrorResponse("Student is not enrolled in the class.");
            } else {
                return $this->sendSuccessResponse("Report card comment added successfully.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }



    public function reportCardCommentPrincipalAdd(ReportCardCommentHomeroomAdd $request, int $institutionId, int $classId)
    {
        try {
            $data = $this->institutionService->reportCardCommentPrincipalAdd($request, $institutionId, $classId);
            
            if($data == 0){
                return $this->sendErrorResponse("Student is not enrolled in the class.");
            } else {
                return $this->sendSuccessResponse("Report card comment added successfully.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }



    public function getInstitutionGradeStudentdata(int $institutionId, int $gradeId, int $studentId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeStudentdata($institutionId, $gradeId, $studentId);
            
            return $this->sendSuccessResponse("Student Details Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get student data.');
        }
    }



    public function addCompetencyResults(CompetencyResultsAddRequest $request)
    {
        try {
            $data = $this->institutionService->addCompetencyResults($request);
            
            if($data == 1){
                return $this->sendErrorResponse("Competeny result stored successfully.");
            } else {
                return $this->sendSuccessResponse("Competeny result not stored.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add competency result.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency result.');
        }
    }


    public function addCompetencyComments(CompetencyCommentAddRequest $request)
    {
        try {
            $data = $this->institutionService->addCompetencyComments($request);
            
            if($data == 1){
                return $this->sendErrorResponse("Competeny comments stored successfully.");
            } else {
                return $this->sendSuccessResponse("Competeny comments not stored.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add competency comments.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency comments.');
        }
    }



    public function addCompetencyPeriodComments(CompetencyPeriodCommentAddRequest $request)
    {
        try {
            $data = $this->institutionService->addCompetencyPeriodComments($request);
            
            if($data == 1){
                return $this->sendErrorResponse("Competeny comments stored successfully.");
            } else {
                return $this->sendSuccessResponse("Competeny comments not stored.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add competency comments.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency comments.');
        }
    }



    public function getStudentAssessmentItemResult(Request $request, $institutionId, $studentId)
    {
        try {
            $data = $this->institutionService->getStudentAssessmentItemResult($request, $institutionId, $studentId);
            
            return $this->sendSuccessResponse("Student Assessment Details Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student assessment data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get student assessment data.');
        }
    }
    
    public function displayAddressAreaLevel(Request $request)
    {
        try {
            $data = $this->institutionService->displayAddressAreaLevel($request);
            
            return $this->sendSuccessResponse("Address area level area found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get address area level area.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get address area level area.');
        }
    }


    public function displayBirthplaceAreaLevel(Request $request)
    {
        try {
            $data = $this->institutionService->displayBirthplaceAreaLevel($request);
            
            return $this->sendSuccessResponse("Birthplace area level area found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get birthplace area level area.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get birthplace area level area.');
        }
    }

    
    public function getSubjectsStaffList(Request $request)
    {
        try {
            if(!isset($request['staff_id']) || !isset($request['institution_id'])){
                return $this->sendErrorResponse('Staff id and institution id is required.');
            }
            $data = $this->institutionService->getSubjectsStaffList($request);
            return $this->sendSuccessResponse("Subjects Staff List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects Staff List Not Found');
        }
    }

    // POCOR-7394-S starts

    public function getAbsenceReasons(Request $request)
    {
        try {
            
            $data = $this->institutionService->getAbsenceReasons($request);
            return $this->sendSuccessResponse("Absence Reasons List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Absence Reasons List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Absence Reasons List Not Found');
        }
    }

    public function getAbsenceTypes(Request $request)
    {
        try {
            
            $data = $this->institutionService->getAbsenceTypes($request);
            return $this->sendSuccessResponse("Absence Types List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Absence Types List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Absence Types List Not Found');
        }
    }


    public function getAreaAdministratives(Request $request)
    {
        try {
            
            $data = $this->institutionService->getAreaAdministratives($request);
            return $this->sendSuccessResponse("Area Administratives List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Area Administratives List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administratives List Not Found');
        }
    }


    public function getAreaAdministrativesById(int $areaAdministrativeId)
    {
        try {
            
            $data = $this->institutionService->getAreaAdministrativesById($areaAdministrativeId);

            if($data){
            return $this->sendSuccessResponse("Area Administrative Found", $data);
            }
            else {
                return $this->sendErrorResponse('Area Administrative Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Area Administrative from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administrative Not Found');
        }
    }
    
    public function getInstitutionGenders()
    {
        try {
            
            $data = $this->institutionService->getInstitutionGenders();
            return $this->sendSuccessResponse("Institution Genders List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Genders List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Genders List Not Found');
        }
    }


    public function getInstitutionsLocalitiesById(int $localityId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionsLocalitiesById($localityId);

            if($data){
            return $this->sendSuccessResponse("Institution Locality Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Locality Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Locality from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Locality Not Found');
        }
    }

    public function getInstitutionsOwnershipsById(int $ownershipId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionsOwnershipsById($ownershipId);

            if($data){
            return $this->sendSuccessResponse("Institution Ownership Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Ownership Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Ownership from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Ownership Not Found');
        }
    }

    public function getInstitutionSectorsById(int $sectorId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionSectorsById($sectorId);

            if($data){
            return $this->sendSuccessResponse("Institution Sector Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Sector Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Sector from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Sector Not Found');
        }
    }

    public function getInstitutionProvidersById(int $providerId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionProvidersById($providerId);

            if($data){
            return $this->sendSuccessResponse("Institution Provider Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Provider Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Provider from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Provider Not Found');
        }
    }

    public function getInstitutionTypesById(int $typeId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionTypesById($typeId);

            if($data){
            return $this->sendSuccessResponse("Institution Type Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Type Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Type from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Type Not Found');
        }
    }

    public function getInstitutionProviderBySectorId(int $sectorId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionProviderBySectorId($sectorId);

            if($data){
            return $this->sendSuccessResponse("Institution Provider By Sector ID Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Provider By Sector ID Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Provider By Sector ID from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Provider By Sector ID Not Found');
        }
    }

    public function getMealBenefits(Request $request)
    {
        try {
            
            $data = $this->institutionService->getMealBenefits($request);
            return $this->sendSuccessResponse("Meal Benefits List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Benefits List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Benefits List Not Found');
        }
    }

    public function getMealProgrammes(Request $request)
    {
        try {
            
            $data = $this->institutionService->getMealProgrammes($request);
            return $this->sendSuccessResponse("Meal Programmes List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Programmes List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Programmes List Not Found');
        }
    }

    // POCOR-7394-S ends

    public function deleteClassAttendance(DeleteClassAttendanceRequest $request)
    {
        try {
            $data = $this->institutionService->deleteClassAttendance($request);
            if($data == 1){
                return $this->sendSuccessResponse("Student attendance deleted successfully.");
            } elseif($data == 2){
                return $this->sendSuccessResponse("Record not found for selected parameters.");
            } else {
                return $this->sendErrorResponse("Student attendance not deleted.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to delete student attendance.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to delete student attendance.');
        }
    }


    public function deleteStudentAttendance(DeleteClassAttendanceRequest $request, $studentId)
    {
        try {
            $data = $this->institutionService->deleteStudentAttendance($request, $studentId);
            if($data == 1){
                return $this->sendSuccessResponse("Student attendance deleted successfully.");
            } elseif($data == 2){
                return $this->sendSuccessResponse("Record not found for selected parameters.");
            }else {
                return $this->sendErrorResponse("Student attendance not deleted.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to delete student attendance.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to delete student attendance.');
        }
    }

    // POCOR-7546 starts

    public function getBehaviourCategories(Request $request)
    {
        try {
            
            $data = $this->institutionService->getBehaviourCategories($request);
            return $this->sendSuccessResponse("Behaviour Categories List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Behaviour Categories List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        
            return $this->sendErrorResponse('Behaviour Categories List Not Found');
        }
    }

    public function getInstitutionStudentBehaviour(int $institutionId, $studentId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionStudentBehaviour($institutionId, $studentId);

            if($data){
            return $this->sendSuccessResponse("Institution Student Behaviour Found", $data);
            }
            
            return $this->sendErrorResponse('Institution Student Behaviour Not Found');
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Student Behaviour from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Institution Student Behaviour Not Found');
        }
    }

    public function addStudentAssessmentItemResult(AssessmentItemResultRequest $request)
    {
        try {
            $data = $this->institutionService->addStudentAssessmentItemResult($request);
            
            if($data == 1){
                return $this->sendErrorResponse("Student assessment mark is added successfully.");
            } elseif($data == 2){
                return $this->sendErrorResponse("Student assessment mark is updated successfully.");
            } elseif($data == 0){
                return $this->sendErrorResponse("Invalid parameters.");
            } else {
                return $this->sendSuccessResponse("The update of student assessment mark could not be completed successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'The update of student assessment mark could not be completed successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('The update of student assessment mark could not be completed successfully.');
        }
    }

    public function addStudentBehaviour(StudentBehavioursRequest $request)
    {
        try {
            $data = $this->institutionService->addStudentBehaviour($request);
            
            if($data == 1){
                return $this->sendErrorResponse("Student Behaviour is added/updated successfully..");
            } elseif($data == 2) {
                return $this->sendErrorResponse("Invalid academic period.");
            } elseif($data == 3) {
                return $this->sendErrorResponse("Invalid institution.");
            } elseif($data == 4) {
                return $this->sendErrorResponse("Invalid student.");
            } elseif($data == 5) {
                return $this->sendErrorResponse("Invalid student behaviour category.");
            } else {
                return $this->sendSuccessResponse("The update of student behaviour could not be completed successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'The update of student behaviour could not be completed successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('The update of student behaviour could not be completed successfully.');
        }
    }


    public function getInstitutionClassEducationGradeStudents(int $institutionId, int $institutionClassId, int $educationGradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionClassEducationGradeStudents($institutionId, $institutionClassId, $educationGradeId);
            
            return $this->sendSuccessResponse("Students List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Students List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Students List.');
        }
    }

    public function getInstitutionEducationSubjectStudents(int $institutionId, int $educationGradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionEducationSubjectStudents($institutionId, $educationGradeId);
            
            return $this->sendSuccessResponse("Students List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Students List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Students List.');
        }
    }

    public function deleteStudentBehaviour(int $institutionId, int $studentId, int $behaviourId)
    {
        try {
            $data = $this->institutionService->deleteStudentBehaviour($institutionId, $studentId, $behaviourId);
            if($data == 1){
                return $this->sendSuccessResponse("Student Behaviour is deleted successfully.");
            } elseif($data == 2){
                return $this->sendSuccessResponse("Record not found for selected Id(s).");
            }else {
                return $this->sendErrorResponse("The deletion of student behaviour could not be completed successfully.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'The deletion of student behaviour could not be completed successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('The deletion of student behaviour could not be completed successfully.');
        }
    }

    // POCOR-7546 ends

}
