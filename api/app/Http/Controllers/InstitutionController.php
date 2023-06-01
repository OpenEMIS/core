<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InstitutionService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ReportCardCommentAdd;
use App\Http\Requests\ReportCardCommentHomeroomAdd;
use App\Http\Requests\CompetencyResultsAddRequest;
use App\Http\Requests\CompetencyCommentAddRequest;
use App\Http\Requests\CompetencyPeriodCommentAddRequest;

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
}
