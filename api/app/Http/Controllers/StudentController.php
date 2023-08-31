<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StudentService;
use Illuminate\Support\Facades\Log;


class StudentController extends Controller
{
    protected $studentService;

    public function __construct(
        StudentService $studentService
    ) {
        $this->studentService = $studentService;
    }


    public function getStudents(Request $request)
    {
        try {
            $data = $this->studentService->getStudents($request);
            return $this->sendSuccessResponse("Institutions Students List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students List Not Found');
        }
    }


    public function getInstitutionStudents(Request $request, $institutionId)
    {
        try {
            $data = $this->studentService->getInstitutionStudents($request, $institutionId);
            return $this->sendSuccessResponse("Institutions Students List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students List Not Found');
        }
    }


    public function getInstitutionStudentData(Request $request, $institutionId, $studentId)
    {
        try {
            $data = $this->studentService->getInstitutionStudentData($request, $institutionId, $studentId);
            return $this->sendSuccessResponse("Institutions Student Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Data Not Found');
        }
    }


    public function getStudentAbsences(Request $request)
    {
        try {
            $data = $this->studentService->getStudentAbsences($request);
            return $this->sendSuccessResponse("Institutions Student Absences List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences List Not Found');
        }
    }



    public function getInstitutionStudentAbsences(Request $request, $institutionId)
    {
        try {
            $data = $this->studentService->getInstitutionStudentAbsences($request, $institutionId);
            return $this->sendSuccessResponse("Institutions Student Absences List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences List Not Found');
        }
    }


    public function getInstitutionStudentAbsencesData(Request $request, $institutionId, $studentId)
    {
        try {
            $data = $this->studentService->getInstitutionStudentAbsencesData($request, $institutionId, $studentId);
            return $this->sendSuccessResponse("Institutions Student Absences Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences Data Not Found');
        }
    }
}
