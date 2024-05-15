<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\AbsenceReasons;
use App\Models\AbsenceTypes;
use App\Models\Institutions;
use App\Models\InstitutionGrades;
use App\Models\EducationGrades;
use App\Models\InstitutionClasses;
use App\Models\InstitutionSubjects;
use App\Models\EducationSubjects;
use App\Models\InstitutionShifts;
use App\Models\AreaAdministratives;
use App\Models\SummaryInstitutions;
use App\Models\SummaryInstitutionGrades;
use App\Models\SummaryInstitutionNationalities;
use App\Models\SummaryInstitutionGradeNationalities;
use App\Models\InstitutionStaff;
use App\Models\StaffStatuses;
use App\Models\InstitutionPositions;
use App\Models\LocaleContentTranslations;
use App\Models\SummaryInstitutionRoomTypes;
use App\Models\ReportCard;
use App\Models\InstitutionStudentReportCardComment;
use App\Models\InstitutionStudentReportCard;
use App\Models\InstitutionClassStudents;
use App\Models\InstitutionStudent;
use App\Models\InstitutionCompetencyResults;
use App\Models\InstitutionCompetencyItemComments;
use App\Models\InstitutionCompetencyPeriodComments;
use App\Models\StaffTypes;
use App\Models\AssessmentItemResults;
use App\Models\ConfigItem;
use App\Models\InstitutionGender;
use App\Models\InstitutionLocalities;
use App\Models\InstitutionOwnerships;
use App\Models\InstitutionProviders;
use App\Models\InstitutionSectors;
use App\Models\InstitutionSubjectStaff;
use App\Models\AcademicPeriod;
use App\Models\StudentStatuses;
use App\Models\Nationalities;
use App\Models\Workflows;
use App\Models\InstitutionStudentTransfers;
use App\Models\SecurityUsers;
use App\Models\UserNationalities;
use App\Models\IdentityTypes;
use App\Models\UserIdentities;
use App\Models\StaffPositionTitles;
use App\Models\SecurityRoles;
use App\Models\InstitutionStudentAdmission;
use App\Models\InstitutionClassSubjects;
use App\Models\InstitutionSubjectStudents;
use App\Models\StudentCustomFieldValues;
use App\Models\InstitutionTypes;
use App\Models\MealBenefits;
use App\Models\MealProgrammes;
use App\Models\StudentAttendanceMarkedRecords;
use App\Models\InstitutionStudentAbsences;
use App\Models\InstitutionStudentAbsenceDays;
use App\Models\InstitutionStudentAbsenceDetails;
use App\Models\StaffBehaviourCategories;
use App\Models\StudentBehaviours;
use App\Models\StudentBehaviourCategory;
use App\Models\InstitutionMealProgrammes;
use App\Models\InstitutionMealStudents;
use App\Models\StaffPayslip;
use App\Models\SecurityGroupUsers;
use App\Models\SecurityRoleFunctions;
use App\Models\ReportCardSubject;
use App\Models\Assessments;
use App\Models\ReportCardCommentCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Session;

class ReportCardRepository extends Controller
{

    //pocor-7856 starts
    public function getReportCardStudents($request)
    {
        try {
            $resp = [];
            $params = $request->all();

            $academicPeriodId = $params['academic_period_id']??0;
            $institutionId = $params['institution_id']??0;
            $classId = $params['institution_class_id']??0;
            $educationGradeId = $params['education_grade_id']??0;
            $reportCardId = $params['report_card_id']??0;
            $educationSubjectId = $params['education_subject_id']??0;
            $institutionSubjectId = $params['institution_subject_id']??0;
            $type = $params['type'];

            $page = $params['page']??NULL;
            $limit = $params['limit']??NULL;
            if(isset($page) && ($page == 0)){
                $page = 1;
            }

            if(isset($limit) && ($limit == 0)){
                $limit = 10;
            }


            if(isset($page) && isset($limit)){
                $skip = ($page - 1) * $limit;
                $take = $limit;
            }
            
            

            /*dd("params: ",$params, "limit: ".$limit, "page: ".$page, "skip: ".$skip, "take: ".$take);*/

            $lists = InstitutionClassStudents::select([
                    'institution_class_students.student_id',
                    'institution_class_students.student_status_id',
                    'security_users.openemis_no',
                    'security_users.first_name',
                    'security_users.middle_name',
                    'security_users.third_name',
                    'security_users.last_name',
                    'security_users.preferred_name',
                    'institution_students_report_cards.principal_comments',
                    'institution_students_report_cards.homeroom_teacher_comments',
                    'institution_students_report_cards.report_card_id'
                ])
                ->leftjoin('institution_students_report_cards', function ($j) use($reportCardId){
                $j->on('institution_students_report_cards.student_id', '=', 'institution_class_students.student_id')
                ->on('institution_students_report_cards.institution_id', '=', 'institution_class_students.institution_id')
                ->on('institution_students_report_cards.academic_period_id', '=', 'institution_class_students.academic_period_id')
                ->on('institution_students_report_cards.education_grade_id', '=', 'institution_class_students.education_grade_id')
                ->on('institution_students_report_cards.institution_class_id', '=', 'institution_class_students.institution_class_id')
                ->where('institution_students_report_cards.report_card_id', $reportCardId);
            })
            ->join('security_users', 'security_users.id', '=', 'institution_class_students.student_id')
            ->with('user:id,first_name,middle_name,third_name,last_name,openemis_no,preferred_name', 'studentStatus')
            ->where(
                [
                    'institution_class_students.academic_period_id' => $academicPeriodId,
                    'institution_class_students.institution_id' => $institutionId,
                    'institution_class_students.institution_class_id' => $classId,
                    'institution_class_students.education_grade_id' => $educationGradeId
                ]
            )
            ->whereNotIn('institution_class_students.student_status_id', [3])
            ->groupBy('institution_class_students.student_id')
            ->orderBy('security_users.first_name')
            ->orderBy('security_users.last_name');
            //->get()
            //->toArray();
            //->toSql();
            
            if ($type == 'PRINCIPAL') {
                $totalRecords = $lists->get()->count();
                
                if(isset($skip) && isset($take)){
                    $lists = $lists->skip($skip)->take($take)->get()->toArray();
                } else {
                    $lists = $lists->get()->toArray();
                }
                

                if(count($lists) > 0){
                    foreach ($lists as $k => $l) {
                        
                        $reportCardId = $l['report_card_id']??$reportCardId;
                        $studentId = $l['student_id'];

                        $resp[$k]['student_id'] = $l['student_id'];
                        $resp[$k]['student_user_id'] = $l['student_id'];
                        $resp[$k]['student_openemis_no'] = $l['openemis_no'];
                        $resp[$k]['student_gender'] = "";
                        $resp[$k]['comments'] = $l['principal_comments'];
                        $resp[$k]['student_status'] = $l['student_status'];
                        $resp[$k]['InstitutionStudentsReportCards']['report_card_id'] = $reportCardId;
                        $resp[$k]['reportCardStartDate'] = Null;
                        $resp[$k]['reportCardEndDate'] = Null;
                        

                        // Get the report card start/end date
                        $reportCardEntity = ReportCard::select('id', 'start_date', 'end_date')->where('id', $l['report_card_id'])->first();

                        if($reportCardEntity){
                            $resp[$k]['reportCardStartDate'] = $reportCardEntity->start_date;
                            $resp[$k]['reportCardEndDate'] = $reportCardEntity->end_date;
                        }


                        // To get the report card template subjects
                        $reportCardSubjectsEntity = ReportCardSubject::select('education_subject_id')->where('report_card_id', $reportCardId)->get()->toArray();


                        // Check if the student belongs to any subject
                        $subjectStudentsEntities = InstitutionSubjectStudents::select('student_id', 'education_subject_id')
                            ->where([
                                'student_id' => $studentId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                            ])
                            ->groupBy('education_subject_id')
                            ->get()
                            ->toArray();

                        $assessmentResults = Assessments::where([
                                'academic_period_id' => $academicPeriodId,
                                'education_grade_id' => $educationGradeId
                            ])
                            ->first();

                        $assessment_id = 0;
                        if(!empty($assessmentResults)){
                            $assessment_id = $assessmentResults->id;
                        }


                        // If subjectStudentsEntities is not empty mean the student have a subject

                        if (!empty($subjectStudentsEntities)) {
                            $total_mark = 0;
                            $subjectTaken = 0;

                            foreach($subjectStudentsEntities as $studentEntity) {

                                // Getting all the subject marks based on report card start/end date
                                $assessmentItemResultsEntities = AssessmentItemResults::select(
                                        'student_id',
                                        'marks',
                                        'education_subject_id',
                                        'education_grade_id',
                                        'academic_period_id',
                                        'institution_id',
                                        'institution_classes_id',
                                        'assessment_periods.weight as weightage'
                                    )
                                    ->leftjoin('assessment_periods', 'assessment_periods.id', '=', 'assessment_item_results.assessment_period_id')
                                    ->with('assessmentPeriod')
                                    ->where([
                                        'student_id' => $studentEntity['student_id'],
                                        'education_subject_id' => $studentEntity['education_subject_id'],
                                        'assessment_item_results.assessment_id' => $assessment_id,
                                        'institution_classes_id' => $classId,
                                    ])
                                    ->whereNotNull('marks')
                                    ->get()
                                    ->toArray();
                                

                                $studentSubArray = [];

                                foreach($assessmentItemResultsEntities as $entity){

                                    foreach ($reportCardSubjectsEntity as $reportCardSubjectEntity) {
                                        if($entity['education_subject_id'] === $reportCardSubjectEntity['education_subject_id']) {
                                            $total_mark += $entity['marks'] * $entity['weightage'];
                                            // Plus one to the subject so that we can keep track how many subject does this student is taking within the report card template.

                                            if((!in_array($entity['education_subject_id'], $studentSubArray))){
                                                $studentSubArray [] = $entity['education_subject_id'];
                                                $subjectTaken++;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $resp[$k]['subjectTaken'] = NULL;
                        $resp[$k]['total_mark'] = NULL;
                        $resp[$k]['average_mark'] = NULL;


                        $resp[$k]['subjectTaken'] = $subjectTaken;
                        $resp[$k]['total_mark'] = $total_mark;

                        if ($subjectTaken == 0) {
                            $subjectTaken = 1;
                        }

                        $resp[$k]['average_mark'] = number_format($total_mark / $subjectTaken, 2);

                        $resp[$k]['_matchingData']['Users'] = $l['user'];
                    }

                    $list['data'] = $resp;
                    $list['total'] = $totalRecords;
                    
                }
            } elseif($type == 'HOMEROOM_TEACHER'){
                $totalRecords = $lists->get()->count();
                
                if(isset($skip) && isset($take)){
                    $lists = $lists->skip($skip)->take($take)->get()->toArray();
                } else {
                    $lists = $lists->get()->toArray();
                }

                if(count($lists) > 0){
                    foreach ($lists as $k => $l) {
                        $reportCardId = $l['report_card_id']??$reportCardId;
                        $studentId = $l['student_id'];

                        $resp[$k]['student_id'] = $l['student_id'];
                        $resp[$k]['student_user_id'] = $l['student_id'];
                        $resp[$k]['student_openemis_no'] = $l['openemis_no'];
                        $resp[$k]['student_gender'] = "";
                        $resp[$k]['comments'] = $l['homeroom_teacher_comments'];
                        $resp[$k]['student_status'] = $l['student_status'];
                        $resp[$k]['InstitutionStudentsReportCards']['report_card_id'] = $reportCardId;
                        $resp[$k]['reportCardStartDate'] = Null;
                        $resp[$k]['reportCardEndDate'] = Null;

                        // Get the report card start/end date
                        $reportCardEntity = ReportCard::select('id', 'start_date', 'end_date')->where('id', $l['report_card_id'])->first();

                        if($reportCardEntity){
                            $resp[$k]['reportCardStartDate'] = $reportCardEntity->start_date;
                            $resp[$k]['reportCardEndDate'] = $reportCardEntity->end_date;
                        }

                        
                        // To get the report card template subjects
                        $reportCardSubjectsEntity = ReportCardSubject::select('education_subject_id')->where('report_card_id', $reportCardId)->get()->toArray();


                        // Check if the student belongs to any subject
                        $subjectStudentsEntities = InstitutionSubjectStudents::select('student_id', 'education_subject_id')
                            ->where([
                                'student_id' => $studentId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                            ])
                            ->groupBy('education_subject_id')
                            ->get()
                            ->toArray();


                        $assessmentResults = Assessments::where([
                                'academic_period_id' => $academicPeriodId,
                                'education_grade_id' => $educationGradeId
                            ])
                            ->first();

                        $assessment_id = 0;
                        if(!empty($assessmentResults)){
                            $assessment_id = $assessmentResults->id;
                        }

                        // If subjectStudentsEntities is not empty mean the student have a subject

                        if (!empty($subjectStudentsEntities)) {
                            $total_mark = 0;
                            $subjectTaken = 0;

                            foreach($subjectStudentsEntities as $studentEntity) {
                                // Getting all the subject marks based on report card start/end date
                                $assessmentItemResultsEntities = AssessmentItemResults::select(
                                        'student_id',
                                        'marks',
                                        'education_subject_id',
                                        'education_grade_id',
                                        'academic_period_id',
                                        'institution_id',
                                        'institution_classes_id',
                                        'assessment_periods.weight as weightage'
                                    )
                                    ->leftjoin('assessment_periods', 'assessment_periods.id', '=', 'assessment_item_results.assessment_period_id')
                                    ->with('assessmentPeriod')
                                    ->where([
                                        'student_id' => $studentEntity['student_id'],
                                        'education_subject_id' => $studentEntity['education_subject_id'],
                                        'assessment_item_results.assessment_id' => $assessment_id,
                                        'institution_classes_id' => $classId,
                                    ])
                                    ->whereNotNull('marks')
                                    ->get()
                                    ->toArray();

                                $studentSubArray = [];
                                
                                foreach($assessmentItemResultsEntities as $entity){

                                    foreach ($reportCardSubjectsEntity as $reportCardSubjectEntity) {
                                        
                                        if($entity['education_subject_id'] === $reportCardSubjectEntity['education_subject_id']) {
                                            $total_mark += $entity['marks'] * $entity['weightage'];
                                            // Plus one to the subject so that we can keep track how many subject does this student is taking within the report card template.

                                            if((!in_array($entity['education_subject_id'], $studentSubArray))){
                                                $studentSubArray [] = $entity['education_subject_id'];
                                                $subjectTaken++;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $resp[$k]['subjectTaken'] = NULL;
                        $resp[$k]['total_mark'] = NULL;
                        $resp[$k]['average_mark'] = NULL;


                        $resp[$k]['subjectTaken'] = $subjectTaken;
                        $resp[$k]['total_mark'] = $total_mark;

                        if ($subjectTaken == 0) {
                            $subjectTaken = 1;
                        }
                        
                        $resp[$k]['average_mark'] = number_format($total_mark / $subjectTaken, 2);

                        $resp[$k]['_matchingData']['Users'] = $l['user'];

                    }

                    $list['data'] = $resp;
                    $list['total'] = $totalRecords;
                    //$resp['total'] = $totalRecords;
                }
            } elseif($type == 'TEACHER'){
                $lists = $lists->addSelect([
                        'institution_students_report_cards_comments.comments',
                        'institution_students_report_cards_comments.report_card_comment_code_id as comment_code',
                        'institution_subject_students.total_mark as total_mark',
                        'staff.first_name as staff_first_name',
                        'staff.last_name as staff_last_name'
                    ])
                    ->leftjoin('institution_students_report_cards_comments', function($j) use($educationSubjectId){
                    $j->on('institution_students_report_cards.report_card_id', '=', 'institution_students_report_cards_comments.report_card_id')
                        ->on('institution_students_report_cards.student_id', '=', 'institution_students_report_cards_comments.student_id')
                        ->on('institution_students_report_cards_comments.institution_id', '=', 'institution_students_report_cards.institution_id')
                        ->on('institution_students_report_cards_comments.academic_period_id', '=', 'institution_students_report_cards.academic_period_id')
                        ->on('institution_students_report_cards_comments.education_grade_id', '=', 'institution_students_report_cards.education_grade_id')
                        ->where('institution_students_report_cards_comments.education_subject_id', $educationSubjectId);
                })
                ->leftjoin('security_users as staff', 'staff.id', '=', 'institution_students_report_cards_comments.staff_id')
                ->leftjoin('institution_subject_students', function($j){
                    $j->on('institution_subject_students.student_id', '=', 'institution_class_students.student_id')
                        ->on('institution_class_students.institution_class_id', '=', 'institution_subject_students.institution_class_id');
                })
                ->where('institution_subject_students.institution_subject_id', $institutionSubjectId);
                

                $totalRecords = $lists->get()->count();
                
                if(isset($skip) && isset($take)){
                    $lists = $lists->skip($skip)->take($take)->get()->toArray();
                } else {
                    $lists = $lists->get()->toArray();
                }
                
                if(count($lists) > 0){
                    foreach ($lists as $k => $l) {
                        
                        $studentId = $l['student_id'];
                        $reportCardId = $l['report_card_id']??$reportCardId;

                        $resp[$k]['student_id'] = $l['student_id'];
                        //$resp[$k]['student_user_id'] = $l['student_id'];
                        $resp[$k]['student_openemis_no'] = $l['openemis_no'];
                        $resp[$k]['student_gender'] = "";
                        $resp[$k]['comments'] = $l['comments'];
                        $resp[$k]['comments_code'] = $l['comment_code'];
                        $resp[$k]['student_status'] = $l['student_status'];
                        $resp[$k]['student_status_name'] = $l['student_status']['name'];
                        $resp[$k]['InstitutionStudentsReportCards']['report_card_id'] = $reportCardId;
                        $resp[$k]['Staff']['first_name'] = $l['staff_first_name'];
                        $resp[$k]['Staff']['last_name'] = $l['staff_last_name'];
                        $resp[$k]['reportCardStartDate'] = Null;
                        $resp[$k]['reportCardEndDate'] = Null;

                        // Get the report card start/end date
                        $reportCardEntity = ReportCard::select('id', 'start_date', 'end_date')->where('id', $l['report_card_id'])->first();

                        if($reportCardEntity){
                            $resp[$k]['reportCardStartDate'] = $reportCardEntity->start_date;
                            $resp[$k]['reportCardEndDate'] = $reportCardEntity->end_date;
                        }


                        // Check if the student belongs to any subject
                        $subjectStudentsEntities = InstitutionSubjectStudents::select('student_id', 'education_subject_id', 'institution_subject_id')
                            ->where([
                                'student_id' => $studentId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                                'institution_subject_id' => $institutionSubjectId
                            ])
                            ->groupBy('institution_subject_id')
                            ->first()
                            ->toArray();
                        

                        // If subjectStudentsEntities is not empty mean the student have a subject
                        if (!empty($subjectStudentsEntities)) {
                            $studentEntity = $subjectStudentsEntities;

                            $assessmentResults = Assessments::where('academic_period_id', $academicPeriodId)->where('education_grade_id', $educationGradeId)->first();

                            $assessment_id = 0;
                            if(!empty($assessmentResults)){
                                $assessment_id = $assessmentResults->id;
                            }

                            // Getting all the subject marks based on report card start/end date
                            $assessmentItemResultsEntities = AssessmentItemResults::select(
                                    'student_id',
                                    'marks',
                                    'education_subject_id',
                                    'education_grade_id',
                                    'academic_period_id',
                                    'institution_id',
                                    'institution_classes_id',
                                    'assessment_periods.weight as weightage'
                                )
                                ->leftjoin('assessment_periods', 'assessment_periods.id', '=', 'assessment_item_results.assessment_period_id')
                                ->with('assessmentPeriod')
                                ->where([
                                    'student_id' => $studentEntity['student_id'],
                                    'education_subject_id' => $studentEntity['education_subject_id'],
                                    'assessment_item_results.assessment_id' => $assessment_id,
                                    'institution_classes_id' => $classId,
                                ])
                                ->whereNotNull('marks')
                                ->get()
                                ->toArray();


                            $total_mark = 0;

                            if (!empty($assessmentItemResultsEntities)) {
                                foreach ($assessmentItemResultsEntities as $entity) {
                                    $total_mark += $entity['marks'] * $entity['weightage'];
                                }

                                $resp[$k]['total_mark'] = $total_mark;
                            }else {
                                $resp[$k]['total_mark'] = '';

                            }
                        }

                        $resp[$k]['_matchingData']['Users'] = $l['user'];
                    }

                    //$resp['total'] = $totalRecords;

                    $list['data'] = $resp;
                    $list['total'] = $totalRecords;
                }

            }
            
            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }



    public function getReportCardSubjects($request)
    {
        try {
            $params = $request->all();
            $checkType = $params['type']??0;
            $staffType = $params['staffType']??0;
            $reportCardId = $params['report_card_id']??0;
            $classId = $params['institution_class_id']??0;

            $user = JWTAuth::user();
            $staffId = $user->id;
            $resp = [];
            $list = ReportCardSubject::select(
                        'report_card_subjects.education_subject_id',
                        'education_subjects.code',
                        'institution_subjects.name',
                        'institution_subjects.id',
                        'education_subjects.order',
                        'institution_subject_staff.staff_id'
                    )
                    ->join('education_subjects', 'education_subjects.id', '=', 'report_card_subjects.education_subject_id')
                    ->join('institution_subjects', 'institution_subjects.education_subject_id', '=', 'report_card_subjects.education_subject_id')
                    ->leftjoin('institution_subject_staff', 'institution_subject_staff.institution_subject_id', '=', 'institution_subjects.id')
                    ->join('institution_class_subjects', function ($j) use($classId) {
                        $j->on('institution_class_subjects.institution_subject_id', '=', 'institution_subjects.id')
                            ->where('institution_class_subjects.institution_class_id', '=', $classId)
                            ->where('institution_class_subjects.status', '>', 0);
                    })
                    ->leftJoin('institution_classes', 'institution_classes.id', '=', 'institution_class_subjects.institution_class_id')
                    ->where('report_card_subjects.report_card_id', '=', $reportCardId);

            if($user->super_admin != 1){
                $list = $list->orWhere('staff_id', $staffId);
            }

            $list = $list->groupBy('institution_subjects.name')
                    ->orderBy('education_subjects.order')
                    ->get()
                    ->toArray();

            return $list;
                

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }
    //pocor-7856 ends


    //For pocor-8260 start...
    public function getReportCardCommentCodes($params)
    {
        try {
            //$limit = config('constantvalues.defaultPaginateLimit');

            

            $list = ReportCardCommentCode::where('visible', 1)->orderBy('order');

            if(isset($params['limit'])){
                $limit = $params['limit'];

                $list = $list->paginate($limit);
            } else {
                $list = $list->get();
            }

            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }
    //For pocor-8260 end...

    //For pocor-8270 start...
    public function getSecurityRoleData($params, $roleId)
    {
        try {
            $data = SecurityRoles::where('id', $roleId)->first();

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch data.');
        }
    }


    public function getReportCardData($params, $reportCardId)
    {
        try {
            $data = ReportCard::where('id', $reportCardId)->first();

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch data.');
        }
    }
    //For pocor-8270 end...

}

