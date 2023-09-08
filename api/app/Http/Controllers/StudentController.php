<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StudentService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ClassAttendanceAdd;
use App\Http\Requests\StudentAbsenceAdd;
use App\Http\Requests\StaffAttendanceAdd;
use App\Http\Requests\UpdateStaffDetails;


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



    //POCOR-7547 Starts...

    public function getEducationGrades(Request $request)
    {
        try {
            $data = $this->studentService->getEducationGrades($request);
            return $this->sendSuccessResponse("Education Grade List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education Grade List Not Found');
        }
    }


    public function getClassesSubjects(Request $request, $institutionId)
    {
        try {
            $data = $this->studentService->getClassesSubjects($request, $institutionId);
            return $this->sendSuccessResponse("Class Subjects List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Subjects List Not Found');
        }
    }


    public function addClassAttendances(ClassAttendanceAdd $request)
    {
        try {
            $data = $this->studentService->addClassAttendances($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Class attendances data added.", $data);
            } elseif($data == 2) {
                return $this->sendSuccessResponse("Class attendances data updated.", $data);
            } else {
                return $this->sendErrorResponse('Failed to add class attendance details.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Attendances Not Added');
        }
    }


    public function addStudentAbsences(StudentAbsenceAdd $request)
    {
        try {
            $data = $this->studentService->addStudentAbsences($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Student absences data added.", $data);
            } elseif($data == 2) {
                return $this->sendSuccessResponse("Student absences data updated.", $data);
            } else {
                return $this->sendErrorResponse('Failed to add student absences details.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student absences Not Added');
        }
    }


    public function addStaffAttendances(StaffAttendanceAdd $request)
    {
        try {
            $data = $this->studentService->addStaffAttendances($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Staff attendances data added.");
            } elseif($data == 2) {
                return $this->sendSuccessResponse("Staff attendances data updated.");
            } else {
                return $this->sendErrorResponse('Failed to add staff attendances details.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Staff attendances Not Added');
        }
    }


    public function updateStaffDetails(UpdateStaffDetails $request)
    {
        try {
            $data = $this->studentService->updateStaffDetails($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Staff data updated.", $data);
            } elseif($data == 0){
                return $this->sendErrorResponse('Invalid user id.');
            } else {
                return $this->sendErrorResponse('Failed to update staff data details.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to update data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Staff data not updated');
        }
    }

    //POCOR-7547 Ends...
}
