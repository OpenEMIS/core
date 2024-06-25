<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\AttendanceRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class AttendanceService extends Controller
{

    protected $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository) 
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    
    public function getAcademicPeriods($request)
    {
        try {
            $data = $this->attendanceRepository->getAcademicPeriods($request);
            $resp = [];
            $resp = $data['list'];
            
            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }



    public function getStaffAttendances($request, $institutionId)
    {
        try {
            $data = $this->attendanceRepository->getStaffAttendances($request, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances List Not Found');
        }
    }


    public function getInstitutionShiftOption($request, $institutionId)
    {
        try {
            $data = $this->attendanceRepository->getInstitutionShiftOption($request, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Shift Options from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Institution Shift Options Not Found.');
        }
    }


    public function getAcademicPeriodsWeeks($request, $academicPeriodId)
    {
        try {
            $data = $this->attendanceRepository->getAcademicPeriodsWeeks($request, $academicPeriodId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }



    public function getAcademicPeriodsWeekDays($request, $academicPeriodId, $weekId)
    {
        try {
            $data = $this->attendanceRepository->getAcademicPeriodsWeekDays($request, $academicPeriodId, $weekId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }


    public function getAcademicPeriodData($academicPeriodId)
    {
        try {
            $data = $this->attendanceRepository->getAcademicPeriodData($academicPeriodId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods Data Not Found');
        }
    }



    //For POCOR-7854 Start...
    public function getAttendanceTypes($params, $gradeId)
    {
        try {
            $data = $this->attendanceRepository->getAttendanceTypes($params, $gradeId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Attendance Types from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Attendance Types Not Found');
        }
    }



    public function allSubjectsByClassPerAcademicPeriod($params, $institutionId, $gradeId, $classId)
    {
        try {
            $data = $this->attendanceRepository->allSubjectsByClassPerAcademicPeriod($params, $institutionId, $gradeId, $classId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Subjects List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Subjects List Not Found');
        }
    }



    public function getStudentAttendanceMarkType($params, $institutionId, $gradeId, $classId)
    {
        try {
            $data = $this->attendanceRepository->getStudentAttendanceMarkType($params, $institutionId, $gradeId, $classId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance Mark Type from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance Mark Type Not Found');
        }
    }



    public function getStudentAttendanceList($params, $institutionId, $gradeId, $classId)
    {
        try {
            $data = $this->attendanceRepository->getStudentAttendanceList($params, $institutionId, $gradeId, $classId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance List Not Found');
        }
    }


    public function getStudentAttendanceMarkedRecordList($params, $institutionId, $gradeId, $classId)
    {
        try {
            $data = $this->attendanceRepository->getStudentAttendanceMarkedRecordList($params, $institutionId, $gradeId, $classId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance List Not Found');
        }
    }

    //For POCOR-7854 End...


    //For POCOR-8363 Starts...
    public function getStudentAttendancesExport($params)
    {
        try {
            $data = $this->attendanceRepository->getStudentAttendancesExport($params);
            
            $resp = [];
            if(isset($data['list'])){
                foreach ($data['list'] as $key => $d) {
                    $resp[$key]['Openemis ID'] = $d['user']['openemis_no'];
                    $resp[$key]['Name'] = $d['user']['full_name'];
                    if($d['institution_student_absences']['absence_type_name'] != ""){
                        $resp[$key]['Attendance'] = $d['institution_student_absences']['absence_type_name'];
                    } else {
                        $resp[$key]['Attendance'] = "Present";
                    }
                    
                    $resp[$key]['Date'] = $d['institution_student_absences']['date'];
                    $resp[$key]['Student Statuses'] = "";
                    $resp[$key]['Class'] = $d['institution_class_name'];
                    $resp[$key]['Absent Reasons'] = $d['institution_student_absences']['student_absence_reason_name'];
                    $resp[$key]['Comment'] = $d['institution_student_absences']['comment'];
                }
            }
            
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export students attendances from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to export students attendances from DB.');
        }
    }


    public function getStudentAttendancesImportTemplate($params)
    {
        try {
            $data = $this->attendanceRepository->getStudentAttendancesImportTemplate($params);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch students attendances import template data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch students attendances import template data from DB.');
        }
    }


    public function studentAttendancesImport($params)
    {
        try {
            $data = $this->attendanceRepository->studentAttendancesImport($params);

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to import students attendances in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to import students attendances in DB.');
        }
    }
    //For POCOR-8363 Ends...

}