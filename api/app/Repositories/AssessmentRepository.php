<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\AssessmentGradingOptions;
use App\Models\AssessmentGradingTypes;
use App\Models\AssessmentItem;
use App\Models\AssessmentPeriod;
use App\Models\Assessments;
use App\Models\InstitutionSubjectStaff;
use App\Models\InstitutionSubjectStudents;
use App\Models\InstitutionSubjects;
use App\Models\AssessmentItemStudentExemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use DB;

class AssessmentRepository extends Controller
{


    public function getEducationGradeList($request)
    {
        try {

            $params = $request->all();

            // $assessments = Assessments::get();
            // return $assessments;
            $assessments = new Assessments();
            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            $list = $assessments->paginate($limit);
            
            return $list;
            
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Education Grade List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Education Grade List.');
        }
    }

    public function getAssessmentItemList($request)
    {
        try {

            $params = $request->all();
            //POCOR-8705 starts
            $education_grade_id = $params['education_grade_id']??0;
            $academic_period_id = $params['academic_period_id']??0;

            $assessmentItem = AssessmentItem::with('educationSubjects');
            if(!empty($education_grade_id) && !empty($academic_period_id)){
                $assessmentsData = Assessments::where('education_grade_id', $education_grade_id)
                        ->where('academic_period_id', $academic_period_id)
                        ->first();
               
                if ($assessmentsData) {
                    $assessmentItem->where('assessment_id', $assessmentsData->id);
                }
            }//POCOR-8705 ends
            
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentItem = $assessmentItem->orderBy($col, $orderBy);
            }
            

            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $assessmentItem->paginate($limit)->toArray();
            } else {
                $list['data'] = $assessmentItem->get()->toArray();
            }
            //For POCOR-8215/8216 end...
            
            return $list;

            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Item List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Item List.');
        }
    }

    public function getAssessmentPeriodList($request)
    {
        try {

            $params = $request->all();
            $assessmentPeriod = AssessmentPeriod::with('assessments');
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentPeriod = $assessmentPeriod->orderBy($col, $orderBy);
            }
            

            

            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $assessmentPeriod->paginate($limit)->toArray();
            } else {
                $list['data'] = $assessmentPeriod->get()->toArray();
            }
            //For POCOR-8215/8216 end...
            
            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Period List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Period List.');
        }
    }

    public function getAssessmentItemGradingTypeList($request)
    {
        try {

            $params = $request->all();
            $assessmentGradingTypes = AssessmentGradingTypes::with('assessmentGradingOptions');
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentGradingTypes = $assessmentGradingTypes->orderBy($col, $orderBy);
            }

            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $assessmentGradingTypes->paginate($limit)->toArray();
            } else {
                $list['data'] = $assessmentGradingTypes->get()->toArray();
            }
            //For POCOR-8215/8216 end...
            
            return $list;

            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Item Grading Type List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Item Grading Type List.');
        }
    }

    public function getAssessmentGradingOptionList($request)
    {
        try {

            $params = $request->all();

            $assessmentGradingOptions = new AssessmentGradingOptions();

            //For POCOR-8215/8216 start...

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentGradingOptions = $assessmentGradingOptions->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $assessmentGradingOptions->paginate($limit)->toArray();
            } else {
                $list['data'] = $assessmentGradingOptions->get()->toArray();
            }
            //For POCOR-8215/8216 end...
            
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Grading Option List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Grading Option List.');
        }
    }



    public function getAssessmentUniquePeriodList($request, $assessmentId)
    {
        try {
            $params = $request->all();
            $assessmentPeriods = AssessmentPeriod::where('assessment_id', $assessmentId)
                    ->groupby('academic_term');

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentPeriods = $assessmentPeriods->orderBy($col, $orderBy);
            }
            

            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $assessmentPeriods->paginate($limit)->toArray();
            } else {
                $list['data'] = $assessmentPeriods->get()->toArray();
            }
            //For POCOR-8215/8216 end...

            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Terms List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Assessment Terms List Not Found');
        }
    }


    public function getAssessmentData($request, $assessmentId)
    {
        try {
            
            $data = Assessments::where('id', $assessmentId)->first();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Deatils from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Assessment Deatils Not Found');
        }
    }


    public function assessmentItemsList($request, $assessmentId)
    {
        try {
            
            $params = $request->all();

            $userId = JWTAuth::user()->id;
            $institution_id = $params['institution_id']??0;
            $academic_period_id = $params['academic_period_id']??0;
            $class_id = $params['class_id']??0;

            $list = [];
            $assessmentItem = AssessmentItem::where('assessment_id', $assessmentId)
                    ->join('education_subjects', 'education_subjects.id', '=', 'assessment_items.education_subject_id')
                    ->join('assessments', 'assessments.id', '=', 'assessment_items.assessment_id')
                    ->join('institution_subjects', 'institution_subjects.education_subject_id', '=', 'education_subjects.id')
                    ->join('institution_class_subjects', 'institution_class_subjects.institution_subject_id', '=', 'institution_subjects.id')
                    ->where('institution_subjects.institution_id', $institution_id)
                    ->where('institution_subjects.academic_period_id', $academic_period_id)
                    ->where('institution_class_subjects.institution_class_id', $class_id)
                    ->select(
                        'assessment_items.*', 
                        'institution_subjects.id as institution_subject_id', 
                        'institution_subjects.education_subject_id', 
                        'institution_subjects.name as institution_subject_name', 
                        'education_subjects.code as education_subject_code',
                        'education_subjects.name as education_subject_name'
                    )
                    ->orderBy('education_subjects.order')
                    ->orderBy('education_subjects.code')
                    ->orderBy('education_subjects.name');


            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $assessmentItem->paginate($limit)->toArray();
            } else {
                $list['data'] = $assessmentItem->get()->toArray();
            }
            //For POCOR-8215/8216 end...
                    
            
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch assessment item list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Assessment item list not found');
        }
    }


    public function isEditableBySubjectStaff($subjectId)
    {
        try {
            $user = JWTAuth::user();
            $list = InstitutionSubjectStaff::where('staff_id', $user->id)->where('institution_subject_id', $subjectId)->get()->toArray();

            if(count($list)){
                $is_editable = 1;
            } else {
                $is_editable = 0;
            }

            return $is_editable;
        } catch (\Exception $e) {
            return 0;
        }
    }


    public function getInstitutionSubjectStudent($request)
    {
        try {
            $params = $request->all();

            /*$limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }*/

            $institution_id = $params['institution_id']??0;
            $institution_class_id = $params['institution_class_id']??0;
            $assessment_id = $params['assessment_id']??0;
            $academic_period_id = $params['academic_period_id']??0;
            $institution_subject_id = $params['institution_subject_id']??0;
            $education_grade_id = $params['education_grade_id']??0;
            $education_subject_id = $params['education_subject_id']??0;
            $archive = $params['archive']??0;

            $education_subject_id = $this->getEducationSubjectId($institution_subject_id, $education_subject_id);
            
            if($archive != 0){
                $archive = true;
            }
            
            $where = [
                'institution_subject_students.education_subject_id' => $education_subject_id,
                'institution_subject_students.institution_class_id' => $institution_class_id,
                'institution_subject_students.institution_id' => $institution_id,
                'institution_subject_students.education_grade_id' => $education_grade_id,
                'institution_subject_students.academic_period_id' => $academic_period_id,
            ];
            
            $institutionSubjectStudents = InstitutionSubjectStudents::select(
                    'institution_subject_students.total_mark',
                    'institution_subject_students.academic_period_id',
                    'institution_subject_students.education_grade_id',
                    'institution_subject_students.education_subject_id',
                    'institution_subject_students.student_status_id',
                    'assessment_periods.assessment_id',
                    'assessment_periods.id as assessment_period_id',
                    'assessment_periods.academic_term',
                    'student_statuses.name as student_status_name',
                    'student_statuses.name as the_student_status',
                    'student_statuses.code as student_status_code',
                    'security_users.id as student_id',
                    'security_users.first_name',
                    'security_users.middle_name',
                    'security_users.third_name',
                    'security_users.last_name',
                    'security_users.preferred_name',
                    'security_users.openemis_no as the_student_code',
                    'assessment_item_results.id as mark_id',
                    'assessment_item_results.marks as mark',
                    'assessment_item_results.assessment_grading_option_id',

                )
                ->addSelect(DB::raw('CONCAT(security_users.first_name, " ", security_users.last_name) AS the_student_name'))
                ->join('assessment_periods', function($j) use($assessment_id){
                    $j->where('assessment_periods.assessment_id', $assessment_id);
                })
                ->join('student_statuses', 'student_statuses.id', '=', 'institution_subject_students.student_status_id')
                ->join('security_users', 'security_users.id', '=', 'institution_subject_students.student_id')
                ->leftjoin('assessment_item_results', function($j) use($education_subject_id){
                    $j->on('assessment_item_results.student_id', '=', 'institution_subject_students.student_id')
                        ->on('assessment_item_results.assessment_period_id', '=', 'assessment_periods.id')
                        ->where('assessment_item_results.education_subject_id', $education_subject_id);
                })
                ->where($where);

            if($archive == 0 || $archive == false){
                $institutionSubjectStudents = $institutionSubjectStudents->whereNotIn('student_statuses.code', ['TRANSFERRED', 'WITHDRAWN', 'REPEATED']);
            }
            //$list = $list->paginate($limit)->toArray();
            


            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $institutionSubjectStudents = $institutionSubjectStudents->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $institutionSubjectStudents->paginate($limit)->toArray();
            } else {
                $list['data'] = $institutionSubjectStudents->get()->toArray();
            }
            //For POCOR-8215/8216 end...


            //For POCOR-8275 Start...
            $array = '{"class_id":"'.$institution_class_id.'","assessment_id":"'.$assessment_id.'","institution_id":'.$institution_id.',"academic_period_id":'.$academic_period_id.'}';
            $encodedArray = base64_encode($array);
            $encodedArray = rtrim($encodedArray, "=");
            
            $list['urls'] = [
                'reportCardGenerate' => 'Institution/Institutions/reportCardGenerate/add?queryString='.$encodedArray.'.cake_session_id',
                'pdf' => 'CustomExcels/exportPDF/AssessmentResults?queryString='.$encodedArray.'.cake_session_id',
                'excel' => 'Institution/Institutions/resultsExport?queryString='.$encodedArray.'.cake_session_id'
            ];
            //For POCOR-8275 End...

            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch institution subject student list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Institution subject student list not found');
        }
    }


    public function getEducationSubjectId($institution_subject_id, $education_subject_id)
    {
        try {
            if($institution_subject_id){
                if($education_subject_id == 0){
                    $institutionSubject = InstitutionSubjects::where('id', $institution_subject_id)->first();
                    $education_subject_id = $institutionSubject->education_subject_id??0;

                }
            }
            return $education_subject_id;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getEducationSubjectId.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return 0;
        }
    }


    //POCOR-8292 start...
    public function getAssessmentViaAcademicTerm($params, $assessmentId)
    {
        try {
            $assessmentPeriods = AssessmentPeriod::where('assessment_id', $assessmentId);

            if(isset($params['academic_term'])){
                $assessmentPeriods = $assessmentPeriods->where('academic_term', $params['academic_term']);
            }


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentPeriods = $assessmentPeriods->orderBy($col, $orderBy);
            }
            

            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $assessmentPeriods->paginate($limit)->toArray();
            } else {
                $list['data'] = $assessmentPeriods->get()->toArray();
            }
            //For POCOR-8215/8216 end...

            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch assessment periods list from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Failed to fetch assessment periods list from DB.');
        }
    }
    //POCOR-8292 end...

    //POCOR-8619 [START]
    public function saveAssessmentItemExemption($request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $currentTimestamp = Carbon::now()->toDateTimeString();
            $userId = JWTAuth::user()->id;

            $checkExemptedStudent = AssessmentItemStudentExemption::where('student_id',$params['student_id'])
            ->where('assessment_id',$params['assessment_id'])
            ->where('education_subject_id',$params['education_subject_id'])
            ->where('institution_class_id',$params['institution_class_id'])
            ->where('education_grade_id',$params['education_grade_id'])
            ->where('assessment_period_id',$params['assessment_period_id'])->first();
            if(empty($checkExemptedStudent)){
                $data = [
                    'assessment_id' => $params['assessment_id'],
                    'education_subject_id' => $params['education_subject_id'] ?? NULL,
                    'student_id' => $params['student_id'] ?? NULL,
                    'institution_class_id' => $params['institution_class_id'] ?? NULL,
                    'education_grade_id' => $params['education_grade_id'] ?? NULL,
                    'assessment_period_id' => $params['assessment_period_id'] ?? NULL,
                    'modified_user_id' => $userId,
                    'modified' => $currentTimestamp,
                    'created_user_id' => $userId,
                    'created' => $currentTimestamp,
                ];
    
                // Insert the data
                AssessmentItemStudentExemption::create($data);
    
                DB::commit();
                return 1;
            }else{
                return 2;
            }
        } catch (\Exception $e) {
            DB::rollback();

            Log::error(
                'Failed to save Exempted User Data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Failed to save Exempted User Data in DB.');
        }
    }
    //POCOR-8619 [END]

}


        
