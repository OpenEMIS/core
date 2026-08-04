<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\AttendanceRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
            
            return $data;
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

    //POCOR-8630 start
    /**
     * @return array<string, int>|false
     */
    public function validateStaffAttendancePermissions(int $institutionId, array $params): array|false
    {
        return $this->attendanceRepository->validateStaffAttendancePermissions($institutionId, $params);
    }


    public function getStaffAttendancesArchive($request)
    {
        try {
            return $this->attendanceRepository->getStaffAttendancesArchive($request);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances Archive List from DB',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances Archive List Not Found');
        }
    }
    //POCOR-8630 end

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
            if(isset($data['data'])){
                foreach ($data['data'] as $key => $d) {
                    $resp[$key]['Openemis ID'] = $d['user']['openemis_no'];
                    $resp[$key]['Name'] = $d['user']['full_name'];
                    if($d['institution_student_absences']['absence_type_name'] != ""){
                        $resp[$key]['Attendance'] = $d['institution_student_absences']['absence_type_name'];
                    } else {
                        $resp[$key]['Attendance'] = "Present";
                    }
                    
                    $resp[$key]['Date'] = Null;
                    if($d['institution_student_absences']['date']){
                        $date1 = date('d/m/Y', strtotime($d['institution_student_absences']['date']));

                        $resp[$key]['Date'] = $date1;
                    }
                    $resp[$key]['Student Statuses'] = "";
                    $resp[$key]['Class'] = $d['institution_class_name'];
                    $resp[$key]['Absent Reasons'] = $d['institution_student_absences']['student_absence_reason_name'];
                    $resp[$key]['Comment'] = $d['institution_student_absences']['comment'];
                    $resp[$key]['Modified User'] = Null;
                    if($d['modified_user']){
                        $resp[$key]['Modified User'] = $d['modified_user']['full_name'];
                    }

                    $resp[$key]['Modified'] = Null;
                    if($d['modified_date']){
                        $date2 = date('Y-m-d', strtotime($d['modified_date']));
                        $formattedDate = Carbon::createFromFormat('Y-m-d', $date2)->format('F d, Y');

                        $resp[$key]['Modified'] = $formattedDate;
                    }

                    $resp[$key]['Created User'] = Null;
                    if($d['created_user']){
                        $resp[$key]['Created User'] = $d['created_user']['full_name'];
                    }

                    $resp[$key]['Created'] = Null;
                    if($d['created_date']){
                        $date3 = date('Y-m-d', strtotime($d['created_date']));
                        $formattedDate = Carbon::createFromFormat('Y-m-d', $date3)->format('F d, Y');
                        $resp[$key]['Created'] = $formattedDate;
                    }
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


    /*public function getStudentAttendancesImportTemplate($params)
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
    }*/


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


    public function studentAttendancesNoScheduledClass($params)
    {
        try {
            $data = $this->attendanceRepository->studentAttendancesNoScheduledClass($params);
            
            return $data;

            
        } catch (\Exception $e) {
            Log::error(
                'Failed to set Student attendance for no-schedules class.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to set Student attendance for no-schedules class.');
        }
    }
    //For POCOR-8363 Ends...



    //For POCOR-8397 Starts...
    public function getArchiveAcademicPeriods($params)
    {
        try {
            $data = $this->attendanceRepository->getArchiveAcademicPeriods($params);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get archive academic periods.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to get archive academic periods.');
        }
    }


    public function getStudentAttendanceMarkedRecordArchiveList($params, $institutionId, $gradeId, $classId)
    {
        try {
            $data = $this->attendanceRepository->getStudentAttendanceMarkedRecordArchiveList($params, $institutionId, $gradeId, $classId);
            
            $resp = [];

            if(count($data['data']) > 0){
                foreach ($data['data'] as $k => $d) {
                    $resp[$k]['institution_id'] = $d['institution_id'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['institution_class_id'] = $d['institution_class_id'];
                    $resp[$k]['education_grade_id'] = $d['education_grade_id'];
                    $resp[$k]['date'] = date('F d, Y', strtotime($d['date']));
                    $resp[$k]['period'] = $d['period'];
                    $resp[$k]['subject_id'] = $d['subject_id'];
                    $resp[$k]['no_scheduled_class'] = $d['no_scheduled_class'];
                }
            }

            $data['data'] = $resp;
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student attendance marked archive.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to get student attendance marked archive.');
        }
    }


    public function getStudentAttendanceArchiveList($params, $institutionId, $gradeId, $classId)
    {
        try {
            $data = $this->attendanceRepository->getStudentAttendanceArchiveList($params, $institutionId, $gradeId, $classId);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student attendance archive list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to get student attendance archive list.');
        }
    }


    public function getStudentAttendanceArchiveExport($params)
    {
        try {
            $data = $this->attendanceRepository->getStudentAttendanceArchiveExport($params);
            
            $resp = [];

            foreach($data as $k => $d){
                $resp[$k]['Student'] = $d['first_name']. ' '.$d['last_name'];
                $resp[$k]['Academic Period'] = $d['academic_period_name'];
                $resp[$k]['Institution Class'] = $d['class_name'];
                $resp[$k]['Education Grade'] = $d['education_grade_name'];
                $resp[$k]['Date'] = date('F d, Y', strtotime($d['date']));
                $resp[$k]['Period'] = $d['period'];
                $resp[$k]['Comment'] = $d['comment'];
                $resp[$k]['Absence Type'] = $d['absence_type_name'];
                $resp[$k]['Student Absence Reason'] = $d['student_absence_reason_name'];
                $resp[$k]['Subject'] = $d['institution_subject_name'];
            }
            
            return $resp;

        } catch (\Exception $e) {
            Log::error(
                'Failed to export students attendances archive from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to export students attendances archive from DB.');
        }
    }
    //For POCOR-8397 Ends...
    
    //For POCOR-8396 Start...
    public function getDataForSheet($params)
    {
        try {
            $data = $this->attendanceRepository->getDataForSheet($params);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed in getDataForSheet.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed in getDataForSheet.');
        }
    }
    //For POCOR-8396 End...

    public function getStaffAttendancesDetails($request, $institutionId, $staffId)
    {
        try {
            $data = $this->attendanceRepository->getStaffAttendancesDetails($request, $institutionId, $staffId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances Details from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances Details Not Found');
        }
    }

    //POCOR-8630 STARTS
    public function getStaffAttendancesImportTemplateData(int $institutionId): array
    {
        try {
            return $this->attendanceRepository->getStaffAttendancesImportTemplateData($institutionId);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch staff attendances import template data from DB.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            throw $e;
        }
    }


    public function staffAttendancesImport(array $params)
    {
        try {
            return $this->attendanceRepository->staffAttendancesImport($params);
        } catch (\Exception $e) {
            Log::error(
                'Failed to import staff attendances in DB.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to import staff attendances in DB.');
        }
    }


    public function getStaffAttendancesExport(array $params): array
    {
        try {
            return $this->attendanceRepository->getStaffAttendancesExport($params);
        } catch (\Exception $e) {
            Log::error(
                'Failed to export staff attendances from DB.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            throw $e;
        }
    }

    //POCOR-8630 ENDS

}