<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\InstitutionRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class InstitutionService extends Controller
{

    protected $institutionRepository;

    public function __construct(
    InstitutionRepository $institutionRepository) {
        $this->institutionRepository = $institutionRepository;
    }

    
    public function getInstitutions($request)
    {
        try {
            $data = $this->institutionRepository->getInstitutions($request);
            
            return $data; 
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
            $data = $this->institutionRepository->getInstitutionData($id);
            
            return $data; 
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Data Not Found');
        }
    }


    public function getGradesList($request)
    {
        try {
            $data = $this->institutionRepository->getGradesList($request);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grades List Not Found');
        }
    }


    public function getInstitutionGradeList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionGradeList($request, $institutionId);
            return $data;
            
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
            $data = $this->institutionRepository->getInstitutionGradeData($institutionId, $gradeId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grades Data Not Found');
        }
    }


    public function getClassesList($request)
    {
        try {
            $data = $this->institutionRepository->getClassesList($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Classes List Not Found');
        }
    }


    public function getInstitutionClassesList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionClassesList($request, $institutionId);
            return $data;
            
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
            $data = $this->institutionRepository->getInstitutionClassData($institutionId, $classId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Data Not Found');
        }
    }


    public function getSubjectsList($request)
    {
        try {
            $data = $this->institutionRepository->getSubjectsList($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects List Not Found');
        }
    }


    public function getInstitutionSubjectsList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionSubjectsList($request, $institutionId);
            return $data;
            
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
            $data = $this->institutionRepository->getInstitutionSubjectsData($institutionId, $subjectId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects Data Not Found');
        }
    }


    public function getInstitutionShifts($request)
    {
        try {
            $data = $this->institutionRepository->getInstitutionShifts($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Shifts List Not Found');
        }
    }


    public function getInstitutionShiftsList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionShiftsList($request, $institutionId);
            return $data;
            
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
            $data = $this->institutionRepository->getInstitutionShiftsData($institutionId, $shiftId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Shifts Data Not Found');
        }
    }


    public function getInstitutionAreas($request)
    {
        try {
            $data = $this->institutionRepository->getInstitutionAreas($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas List Not Found');
        }
    }


    public function getInstitutionAreasList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionAreasList($request, $institutionId);
            return $data;
            
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
            $data = $this->institutionRepository->getInstitutionAreasData($institutionId, $areaAdministrativeId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas Data Not Found');
        }
    }


    public function getSummariesList($request)
    {
        try {
            $data = $this->institutionRepository->getSummariesList($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Summaries List Not Found');
        }
    }


    public function getInstitutionSummariesList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionSummariesList($request, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Summaries List Not Found');
        }
    }


    public function getGradeSummariesList($request)
    {
        try {
            $data = $this->institutionRepository->getGradeSummariesList($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries List Not Found');
        }
    }


    public function getInstitutionGradeSummariesList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionGradeSummariesList($request, $institutionId);
            return $data;
            
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
            $data = $this->institutionRepository->getInstitutionGradeSummariesData($institutionId, $gradeId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries Data Not Found');
        }
    }


    public function getStudentNationalitySummariesList($request)
    {
        try {
            $data = $this->institutionRepository->getStudentNationalitySummariesList($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionStudentNationalitySummariesList($request, $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionStudentNationalitySummariesList($request, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getGradesStudentNationalitySummariesList($request)
    {
        try {
            $data = $this->institutionRepository->getGradesStudentNationalitySummariesList($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionGradeStudentNationalitySummariesList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionGradeStudentNationalitySummariesList($request, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionGradeStudentNationalitySummaries($request, int $institutionId, int $gradeId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionGradeStudentNationalitySummaries($request, $institutionId, $gradeId);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getStaffList($request)
    {
        try {
            $data = $this->institutionRepository->getStaffList($request);
            
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['fte'] = $d['FTE'];
                    $list[$k]['start_date'] = $d['start_date'];
                    $list[$k]['start_year'] = $d['start_year'];
                    $list[$k]['end_date'] = $d['end_date'];
                    $list[$k]['end_year'] = $d['end_year'];
                    $list[$k]['staff_id'] = $d['staff_id'];
                    $list[$k]['staff_type_id'] = $d['staff_type_id'];
                    $list[$k]['staff_type_name'] = $d['staff_type']['staff_type_name']??"";
                    $list[$k]['staff_status_id'] = $d['staff_status_id'];
                    $list[$k]['staff_status_name'] = $d['staff_status']['staff_status_name']??"";
                    $list[$k]['institution_id'] = $d['institution_id'];
                    $list[$k]['institution_position_id'] = $d['institution_position_id'];
                    $list[$k]['security_group_user_id'] = $d['security_group_user_id'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                    $list[$k]['institution_code'] = $d['institution']['institution_code']??"";
                    $list[$k]['staff_status_name'] = $d['staff_status']['staff_status_name']??"";
                    $list[$k]['institution_position_name'] = $d['institution_position']['staff_position_title']['name']??"";
                }
            }
            

            $data['data'] = $list;

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Staff List Not Found');
        }
    }


    public function getInstitutionStaffList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionStaffList($request, $institutionId);
            
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['fte'] = $d['FTE'];
                    $list[$k]['start_date'] = $d['start_date'];
                    $list[$k]['start_year'] = $d['start_year'];
                    $list[$k]['end_date'] = $d['end_date'];
                    $list[$k]['end_year'] = $d['end_year'];
                    $list[$k]['staff_id'] = $d['staff_id'];
                    $list[$k]['staff_type_id'] = $d['staff_type_id'];
                    $list[$k]['staff_type_name'] = $d['staff_type']['staff_type_name']??"";
                    $list[$k]['staff_status_id'] = $d['staff_status_id'];
                    $list[$k]['staff_status_name'] = $d['staff_status']['staff_status_name']??"";
                    $list[$k]['institution_id'] = $d['institution_id'];
                    $list[$k]['institution_position_id'] = $d['institution_position_id'];
                    $list[$k]['security_group_user_id'] = $d['security_group_user_id'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                    $list[$k]['institution_code'] = $d['institution']['institution_code']??"";
                    $list[$k]['staff_status_name'] = $d['staff_status']['staff_status_name']??"";
                    $list[$k]['institution_position_name'] = $d['institution_position']['staff_position_title']['name']??"";
                }
            }
            

            $data['data'] = $list;

            return $data;
            
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
            $data = $this->institutionRepository->getInstitutionStaffData($institutionId, $staffId);
            
            $list = [];
            if($data){
                $list['id'] = $data['id'];
                $list['fte'] = $data['FTE'];
                $list['start_date'] = $data['start_date'];
                $list['start_year'] = $data['start_year'];
                $list['end_date'] = $data['end_date'];
                $list['end_year'] = $data['end_year'];
                $list['staff_id'] = $data['staff_id'];
                $list['staff_type_id'] = $data['staff_type_id'];
                $list['staff_type_name'] = $data['staffType']['staff_type_name']??"";
                $list['staff_status_id'] = $data['staff_status_id'];
                $list['staff_status_name'] = $data['staffStatus']['staff_status_name']??"";
                $list['institution_id'] = $data['institution_id'];
                $list['institution_position_id'] = $data['institution_position_id'];
                $list['security_group_user_id'] = $data['security_group_user_id'];
                $list['modified_user_id'] = $data['modified_user_id'];
                $list['modified'] = $data['modified'];
                $list['created_user_id'] = $data['created_user_id'];
                $list['created'] = $data['created'];
                $list['institution_code'] = $data['institution']['institution_code']??"";
                $list['staff_status_name'] = $data['staffStatus']['staff_status_name']??"";
                $list['institution_position_name'] = $data['institutionPosition']['staffPositionTitle']['name'];
            }

            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Staff Data Not Found');
        }
    }



    public function getPositionsList($request)
    {
        try {
            $data = $this->institutionRepository->getPositionsList($request);
            //dd($data);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['position_id'] = $d['id'];
                    $list[$k]['status_id'] = $d['status_id'];
                    $list[$k]['status_name'] = $d['status']['status_name'];
                    $list[$k]['position_no'] = $d['position_no'];
                    $list[$k]['staff_position_title_id'] = $d['staff_position_title_id'];
                    $list[$k]['staff_position_title_name'] = $d['staff_position_title']['staff_position_title_name'];
                    /*$list[$k]['staff_position_grade_id'] = $d['staff_position_grade_id'];
                    $list[$k]['staff_position_grade_name'] = $d['staff_position_grades']['staff_position_grade_name'];*/
                    $list[$k]['institution_id'] = $d['institution_id'];
                    $list[$k]['assignee_id'] = $d['assignee_id'];
                    //$list[$k]['is_homeroom'] = $d['is_homeroom'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }

            $data['data'] = $list;

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions List Not Found');
        }
    }
    

    public function getInstitutionPositionsList($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionPositionsList($request, $institutionId);
            
            //dd($data);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['position_id'] = $d['id'];
                    $list[$k]['status_id'] = $d['status_id'];
                    $list[$k]['status_name'] = $d['status']['status_name'];
                    $list[$k]['position_no'] = $d['position_no'];
                    $list[$k]['staff_position_title_id'] = $d['staff_position_title_id'];
                    $list[$k]['staff_position_title_name'] = $d['staff_position_title']['staff_position_title_name'];
                    /*$list[$k]['staff_position_grade_id'] = $d['staff_position_grade_id'];
                    $list[$k]['staff_position_grade_name'] = $d['staff_position_grades']['staff_position_grade_name'];*/
                    $list[$k]['institution_id'] = $d['institution_id'];
                    $list[$k]['assignee_id'] = $d['assignee_id'];
                    //$list[$k]['is_homeroom'] = $d['is_homeroom'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }

            $data['data'] = $list;

            return $data;
            
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
            $data = $this->institutionRepository->getInstitutionPositionsData($institutionId, $positionId);
            
            $list = [];
            if($data){
                $list['position_id'] = $data['id'];
                $list['status_id'] = $data['status_id'];
                $list['status_name'] = $data['status']['status_name'];
                $list['position_no'] = $data['position_no'];
                $list['staff_position_title_id'] = $data['staff_position_title_id'];
                $list['staff_position_title_name'] = $data['staffPositionTitle']['staff_position_title_name']??"";
                /*$list['staff_position_grade_id'] = $data['staff_position_grade_id'];
                $list['staff_position_grade_name'] = $data['staffPositionGrades']['staff_position_grade_name']??"";*/
                $list['institution_id'] = $data['institution_id'];
                $list['assignee_id'] = $data['assignee_id'];
                $list['is_homeroom'] = $data['is_homeroom'];
                $list['modified_user_id'] = $data['modified_user_id'];
                $list['modified'] = $data['modified'];
                $list['created_user_id'] = $data['created_user_id'];
                $list['created'] = $data['created'];
            }

            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions Data Not Found');
        }
    }


    public function localeContentsList($request)
    {
        try {
            $data = $this->institutionRepository->localeContentsList($request);
            
            //dd($data);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['translation'] = $d['translation'];
                    $list[$k]['locale_content_id'] = $d['locale_content_id'];
                    $list[$k]['locale_content_name'] = $d['locale_contents']['en']??"";
                    $list[$k]['locale_id'] = $d['locale_id'];
                    $list[$k]['locale_name'] = $d['locales']['name']??"";
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }

            $data['data'] = $list;

            return $data;
            
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
            $data = $this->institutionRepository->localeContentsData($localeId);
            
            //dd($data);
            $list = [];
            if($data){
                $list['id'] = $data['id'];
                $list['translation'] = $data['translation'];
                $list['locale_content_id'] = $data['locale_content_id'];
                $list['locale_content_name'] = $data['locale_contents']['en']??"";
                $list['locale_id'] = $data['locale_id'];
                $list['locale_name'] = $data['locales']['name']??"";
                $list['modified_user_id'] = $data['modified_user_id'];
                $list['modified'] = $data['modified'];
                $list['created_user_id'] = $data['created_user_id'];
                $list['created'] = $data['created'];
            }

            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Locale Contents Data Not Found');
        }
    }



    public function roomTypeSummaries($request)
    {
        try {
            $data = $this->institutionRepository->roomTypeSummaries($request);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }


    public function institutionRoomTypeSummaries($request, int $institutionId)
    {
        try {
            $data = $this->institutionRepository->institutionRoomTypeSummaries($request, $institutionId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }


    public function reportCardCommentAdd($request, int $institutionId, int $classId)
    {
        try {
            $data = $this->institutionRepository->reportCardCommentAdd($request, $institutionId, $classId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }



    public function reportCardCommentHomeroomAdd($request, int $institutionId, int $classId)
    {
        try {
            $data = $this->institutionRepository->reportCardCommentHomeroomAdd($request, $institutionId, $classId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }



    public function reportCardCommentPrincipalAdd($request, int $institutionId, int $classId)
    {
        try {
            $data = $this->institutionRepository->reportCardCommentPrincipalAdd($request, $institutionId, $classId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }



    public function getInstitutionGradeStudentdata($institutionId, $gradeId, $studentId)
    {
        try {
            $data = $this->institutionRepository->getInstitutionGradeStudentdata($institutionId, $gradeId, $studentId);
            
            $resp = [];
            if($data){
                $resp['academic_period_id'] = $data['academic_period_id'];
                $resp['institution_id'] = $data['institution_id'];
                $resp['education_grade_id'] = $data['education_grade_id'];
                $resp['student_status_id'] = $data['student_status_id'];
                $resp['student_id'] = $data['student_id'];
                $resp['username'] = $data['securityUser']['username'];
                $resp['openemis_no'] = $data['securityUser']['openemis_no'];
                $resp['first_name'] = $data['securityUser']['first_name'];
                $resp['last_name'] = $data['securityUser']['last_name'];
                $resp['gender_id'] = $data['securityUser']['gender_id'];
                $resp['date_of_birth'] = $data['securityUser']['date_of_birth'];
                $resp['start_year'] = $data['start_year'];
                $resp['start_date'] = $data['start_date'];
                $resp['end_year'] = $data['end_year'];
                $resp['end_date'] = $data['end_date'];
            }
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get student data.');
        }
    }



    public function addCompetencyResults($request)
    {
        try {
            $data = $this->institutionRepository->addCompetencyResults($request);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add competency result.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency result.');
        }
    }


    public function addCompetencyComments($request)
    {
        try {
            $data = $this->institutionRepository->addCompetencyComments($request);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add competency comments.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency comments.');
        }
    }



    public function addCompetencyPeriodComments($request)
    {
        try {
            $data = $this->institutionRepository->addCompetencyPeriodComments($request);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add competency comments.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency comments.');
        }
    }


    public function getStudentAssessmentItemResult($request, $institutionId, $studentId)
    {
        try {
            $lists = $this->institutionRepository->getStudentAssessmentItemResult($request, $institutionId, $studentId);
            $resp = [];

            if(count($lists) > 0){
                foreach($lists as $k => $l){
                    $resp[$k]['academic_period_id'] = $l['academic_period_id'];
                    $resp[$k]['assessment_grading_option_id'] = $l['assessment_grading_option_id'];
                    $resp[$k]['assessment_id'] = $l['assessment_id'];
                    $resp[$k]['assessment_period_id'] = $l['assessment_period_id'];
                    $resp[$k]['education_grade_id'] = $l['education_grade_id'];
                    $resp[$k]['education_subject_id'] = $l['education_subject_id'];
                    $resp[$k]['institution_id'] = $l['institution_id'];
                    $resp[$k]['marks'] = $l['marks'];
                    $resp[$k]['student_id'] = $l['student_id'];
                }
            }

            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student assessment data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get student assessment data.');
        }
    }

    
    public function getSubjectsStaffList($request)
    {
        try {
            $data = $this->institutionRepository->getSubjectsStaffList($request);
            
            //dd($data);

            $resp = [];
            if($data){
                foreach($data as $k => $d){
                    
                    $resp[$k]['education_systems_name'] = $d['institutionSubject']['educationGrades']['educationProgramme']['educationCycle']['educationLevel']['educationSystem']['name'];

                    $resp[$k]['education_levels_name'] = $d['institutionSubject']['educationGrades']['educationProgramme']['educationCycle']['educationLevel']['name'];

                    $resp[$k]['education_cycles_name'] = $d['institutionSubject']['educationGrades']['educationProgramme']['educationCycle']['name'];

                    $resp[$k]['education_programmes_code'] = $d['institutionSubject']['educationGrades']['educationProgramme']['code'];

                    $resp[$k]['education_programmes_name'] = $d['institutionSubject']['educationGrades']['educationProgramme']['name'];

                    $resp[$k]['education_grades_code'] = $d['institutionSubject']['educationGrades']['code'];
                    $resp[$k]['education_grades_name'] = $d['institutionSubject']['educationGrades']['name'];
                    $resp[$k]['education_subjects_code'] = $d['institutionSubject']['educationSubjects']['code'];
                    $resp[$k]['education_subjects_name'] = $d['institutionSubject']['educationSubjects']['name'];
                    $resp[$k]['institutions_id'] = $d['institution']['id'];
                    $resp[$k]['institutions_code'] = $d['institution']['code'];
                    $resp[$k]['institutions_name'] = $d['institution']['name'];

                    $resp[$k]['institution_classes_name'] = $d['institutionSubject']['classes'][0]['institutionClass']['name']??"";

                    $resp[$k]['academic_periods_code'] = $d['institutionSubject']['academicPeriod']['code'];
                    $resp[$k]['academic_periods_name'] = $d['institutionSubject']['academicPeriod']['name'];
                    $resp[$k]['institution_subjects_id'] = $d['institutionSubject']['id'];
                    $resp[$k]['institution_subjects_name'] = $d['institutionSubject']['name'];

                    $resp[$k]['security_users_openemis_no_subject_teachers'] = $d['staff']['openemis_no'];

                    $openEmisNo = [];

                    if(count($d['institutionSubject']['students']) > 0){
                        $students = $d['institutionSubject']['students'];

                        foreach($students as $s){
                            $openEmisNo[] = $s['securityUser']['openemis_no'];
                        }
                    }

                    $resp[$k]['security_users_openemis_no_students'] = $openEmisNo;
                }
                
            }
            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects Staff List Not Found');
        }
    }

}
