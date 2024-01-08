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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Session;

class InstitutionRepository extends Controller
{

    public function getInstitutions($request)
    {
        try {
            
            $params = $request->all();

            //For POCOR-7772 Start

            $permissions = checkAccess();
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            //$institutions = new Institutions();
            $institutions = Institutions::with('institutionLocalities', 'institutionOwnerships', 'institutionProviders', 'institutionSectors', 'institutionTypes', 'institutionStatus', 'institutionGender');

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $institutions = $institutions->whereIn('institutions.id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $institutions = $institutions->orderBy($col, $orderBy);
            }

            $list = $institutions->paginate($limit)->toArray();
            
            $resp = [];
            foreach($list['data'] as $d){
                if(isset($d['logo_content'])){
                    $d['logo_content'] = base64_encode($d['logo_content']);
                    //$d['logo_content'] = NULL;
                }
                $resp[] = $d;
            }
            
            $list['data'] = $resp;
            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution List Not Found');
        }
    }


    public function getInstitutionData($id)
    {
        try {
            //For POCOR-7772 Start
            $permissions = checkAccess();

            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $institution = Institutions::with('institutionLocalities', 'institutionOwnerships', 'institutionProviders', 'institutionSectors', 'institutionTypes', 'institutionStatus', 'institutionGender')->where('id', $id);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $institution = $institution->whereIn('institutions.id', $institution_Ids);
            }
            //For POCOR-7772 End
            
            $institution = $institution->first();
            return $institution;
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            $grades = EducationGrades::join('institution_grades', 'institution_grades.education_grade_id', '=', 'education_grades.id')->select('education_grades.*');

            
            //For POCOR-7772 Start
            if($institution_Ids){
                $grades = $grades->whereIn('institution_grades.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $grades = $grades->orderBy($col, $orderBy);
            }
            //$list = $grades->get();
            $list = $grades->paginate($limit);
            return $list;
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $institutionGrade = InstitutionGrades::where('institution_id', $institutionId)->with('educationGrades');

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $institutionGrade = $institutionGrade->whereIn('institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $institutionGrade = $institutionGrade->orderBy($col, $orderBy);
            }

            //$list = $institutionGrade->get();
            $list = $institutionGrade->paginate($limit);

            return $list;
            
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
            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $educationGrade = EducationGrades::join('institution_grades', 'institution_grades.education_grade_id', '=', 'education_grades.id')->select('education_grades.*')->where('education_grades.id', $gradeId);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $educationGrade = $educationGrade->whereIn('institution_grades.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $educationGrade = $educationGrade->first();
            return $educationGrade;
            
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
            $params = $request->all();
            //$classes = new InstitutionClasses();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End


            $classes = InstitutionClasses::with(
                'grades:institution_class_id,education_grade_id as grade_id', 
                'subjects:institution_class_id,institution_subject_id as subject_id',
                'students:institution_class_id,student_id',
                'secondary_teachers:institution_class_id,secondary_staff_id as staff_id'
            );

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $classes = $classes->where('academic_period_id', $academic_period_id);
            }


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $classes = $classes->whereIn('institution_classes.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $classes = $classes->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $classes->get();
            $list = $classes->paginate($limit);
            
            return $list;
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $institutionClasses = InstitutionClasses::with(
                'grades:institution_class_id,education_grade_id as grade_id', 
                'subjects:institution_class_id,institution_subject_id as subject_id',
                'students:institution_class_id,student_id',
                'secondary_teachers:institution_class_id,secondary_staff_id as staff_id'
            );

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $institutionClasses = $institutionClasses->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $institutionClasses = $institutionClasses->whereIn('institution_classes.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $institutionClasses = $institutionClasses->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $institutionClasses->where('institution_id', $institutionId)->get();
            $list = $institutionClasses->where('institution_id', $institutionId)->paginate($limit);

            return $list;
            
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

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $data = InstitutionClasses::with(
                    'grades:institution_class_id,education_grade_id as grade_id', 
                    'subjects:institution_class_id,institution_subject_id as subject_id',
                    'students:institution_class_id,student_id',
                    'secondary_teachers:institution_class_id,secondary_staff_id as staff_id'
                )->where('id', $classId);


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $data = $data->whereIn('institution_classes.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $data = $data->first();

            return $data;
            
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

            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $subjects = InstitutionSubjects::with(
                    'educationGrades:id,name', 'educationSubjects:id,name', 
                    'classes:institution_subject_id,institution_class_id as class_id', 
                    'rooms:institution_subject_id,institution_room_id as room_id',
                    'staff:institution_subject_id,staff_id',
                    'students:institution_subject_id,student_id as user_id'
                );

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $subjects = $subjects->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $subjects = $subjects->whereIn('institution_subjects.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $subjects = $subjects->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            //$list = $subjects->get();
            $list = $subjects->paginate($limit);

            return $list;

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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $subjects = InstitutionSubjects::with(
                    'educationGrades:id,name', 'educationSubjects:id,name', 
                    'classes:institution_subject_id,institution_class_id as class_id', 
                    'rooms:institution_subject_id,institution_room_id as room_id',
                    'staff:institution_subject_id,staff_id',
                    'students:institution_subject_id,student_id as user_id'
                );

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $subjects = $subjects->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $subjects = $subjects->whereIn('institution_subjects.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $subjects = $subjects->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            //$list = $subjects->where('institution_id', $institutionId)->get();
            $list = $subjects->where('institution_id', $institutionId)->paginate($limit);

            return $list;
            
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

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $subjects = InstitutionSubjects::with(
                    'educationGrades:id,name', 'educationSubjects:id,name', 
                    'classes:institution_subject_id,institution_class_id as class_id', 
                    'rooms:institution_subject_id,institution_room_id as room_id',
                    'staff:institution_subject_id,staff_id',
                    'students:institution_subject_id,student_id as user_id'
                )->where('id', $subjectId);


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $subjects = $subjects->whereIn('institution_subjects.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $subjects = $subjects->get();
            return $subjects;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $shifts = InstitutionShifts::join('institutions', 'institution_shifts.institution_id', '=', 'institutions.id')->select('institution_shifts.*');

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $shifts = $shifts->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $shifts = $shifts->whereIn('institution_shifts.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $shifts = $shifts->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $shifts->with('shiftOption:id,name')->get();
            $list = $shifts->with('shiftOption:id,name')->paginate($limit);
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $institutionShifts = InstitutionShifts::join('institutions', 'institution_shifts.institution_id', '=', 'institutions.id')->select('institution_shifts.*')->with('shiftOption:id,name');

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $institutionShifts = $institutionShifts->where('academic_period_id', $academic_period_id);
            }


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $institutionShifts = $institutionShifts->whereIn('institution_shifts.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $institutionShifts = $institutionShifts->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }


            //$list = $institutionShifts->where('institution_id', $institutionId)->get();
            $list = $institutionShifts->where('institution_id', $institutionId)->paginate($limit);
            
            return $list;

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

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $institutionShift = InstitutionShifts::join('institutions', 'institution_shifts.institution_id', '=', 'institutions.id')->select('institution_shifts.*')->with('shiftOption:id,name')->where('institution_shifts.id', $shiftId)->where('institution_id', $institutionId);


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $institutionShift = $institutionShift->whereIn('institution_shifts.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            $institutionShift = $institutionShift->first();

            return $institutionShift;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $areas = Institutions::select('id', 'area_administrative_id', 'area_id')
                ->with(
                    'areaAdministratives:id,code,name,parent_id', 
                    'areaAdministratives.areaAdministrativesChild:id,code,name,parent_id',
                    'areaEducation:id,code,name,parent_id',
                    'areaEducation.areaEducationChild:id,code,name,parent_id'
                );

            
            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $areas = $areas->whereIn('institutions.id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $areas = $areas->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $areas->get();
            $list = $areas->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();


            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $areas = Institutions::select('id', 'area_administrative_id', 'area_id')
                ->with(
                    'areaAdministratives:id,code,name,parent_id', 
                    'areaAdministratives.areaAdministrativesChild:id,code,name,parent_id',
                    'areaEducation:id,code,name,parent_id',
                    'areaEducation.areaEducationChild:id,code,name,parent_id'
                )->where('id', $institutionId);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $areas = $areas->whereIn('institutions.id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $areas = $areas->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $areas->get();
            $list = $areas->paginate($limit);
            
            return $list;
            
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
            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $data =  Institutions::select('id', 'area_administrative_id', 'area_id')
                ->with(
                    'areaAdministratives:id,code,name,parent_id', 
                    'areaAdministratives.areaAdministrativesChild:id,code,name,parent_id',
                    'areaEducation:id,code,name,parent_id',
                    'areaEducation.areaEducationChild:id,code,name,parent_id'
                )
                ->where('id', $institutionId)
                ->where('area_administrative_id', $areaAdministrativeId);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $data = $data->whereIn('institutions.id', $institution_Ids);
            }
            //For POCOR-7772 End

            $data = $data->first();

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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $summaries = new SummaryInstitutions();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $summaries = $summaries->where('academic_period_id', $academic_period_id);
            }


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $summaries = $summaries->whereIn('summary_institutions.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $summaries = $summaries->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $summaries->get();
            $list = $summaries->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $summaries = new SummaryInstitutions();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $summaries = $summaries->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $summaries = $summaries->whereIn('summary_institutions.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $summaries = $summaries->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $summaries->where('institution_id', $institutionId)->get();
            $list = $summaries->where('institution_id', $institutionId)->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $summaries = new SummaryInstitutionGrades();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $summaries = $summaries->where('academic_period_id', $academic_period_id);
            }


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $summaries = $summaries->whereIn('summary_institution_grades.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $summaries = $summaries->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $summaries->get();
            $list = $summaries->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $summaries = new SummaryInstitutionGrades();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $summaries = $summaries->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $summaries = $summaries->whereIn('summary_institution_grades.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $summaries = $summaries->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $summaries->where('institution_id', $institutionId)->get();
            $list = $summaries->where('institution_id', $institutionId)->paginate($limit);
            
            return $list;
            
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

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $gradeSummary = SummaryInstitutionGrades::where('institution_id', $institutionId)->where('grade_id', $gradeId);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $gradeSummary = $gradeSummary->whereIn('summary_institution_grades.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $gradeSummary = $gradeSummary->get();
            
            return $gradeSummary;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $nationalitySummaries = new SummaryInstitutionNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $nationalitySummaries = $nationalitySummaries->whereIn('summary_institution_nationalities.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $nationalitySummaries->get();
            $list = $nationalitySummaries->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $nationalitySummaries = new SummaryInstitutionNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $nationalitySummaries = $nationalitySummaries->whereIn('summary_institution_nationalities.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            //$list = $nationalitySummaries->where('institution_id', $institutionId)->get();
            $list = $nationalitySummaries->where('institution_id', $institutionId)->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $nationalitySummaries = new SummaryInstitutionGradeNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $nationalitySummaries = $nationalitySummaries->whereIn('summary_institution_grade_nationalities.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $nationalitySummaries->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $nationalitySummaries = new SummaryInstitutionGradeNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $nationalitySummaries = $nationalitySummaries->whereIn('summary_institution_grade_nationalities.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $nationalitySummaries->where('institution_id', $institutionId)->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $nationalitySummaries = new SummaryInstitutionGradeNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $nationalitySummaries = $nationalitySummaries->whereIn('summary_institution_grade_nationalities.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End
            

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $nationalitySummaries->where('institution_id', $institutionId)->where('grade_id', $gradeId)->paginate($limit);
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $staffs = InstitutionStaff::with('institution:id,code as institution_code', 'staffStatus:id,name as staff_status_name', 'institutionPosition:id,staff_position_title_id', 'institutionPosition.staffPositionTitle:id,name', 'staffType:id,name as staff_type_name');
            

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $staffs = $staffs->whereIn('institution_staff.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $staffs = $staffs->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $staffs->paginate($limit)->toArray();
            
            return $list;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $staffs = InstitutionStaff::with('institution:id,code as institution_code', 'staffStatus:id,name as staff_status_name', 'institutionPosition:id,staff_position_title_id', 'institutionPosition.staffPositionTitle:id,name', 'staffType:id,name as staff_type_name');
            

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $staffs = $staffs->whereIn('institution_staff.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $staffs = $staffs->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $staffs->where('institution_id', $institutionId)->paginate($limit)->toArray();
            
            return $list;
            
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

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $staffs = InstitutionStaff::with('institution:id,code as institution_code', 'staffStatus:id,name as staff_status_name', 'institutionPosition:id,staff_position_title_id', 'institutionPosition.staffPositionTitle:id,name', 'staffType:id,name as staff_type_name')
                ->where('institution_id', $institutionId)
                ->where('staff_id', $staffId);


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $staffs = $staffs->whereIn('institution_staff.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $staffs = $staffs->first();
            
            return $staffs;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $positions = InstitutionPositions::with('staffPositionTitle:id,name as staff_position_title_name', 'status:id,name as status_name');
            

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $positions = $positions->whereIn('institution_positions.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $positions = $positions->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $positions->paginate($limit)->toArray();
            
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions List Not Found');
        }
    }


    public function getInstitutionPositionsList($request, $institutionId)
    {
        try {
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $positions = InstitutionPositions::with('staffPositionTitle:id,name as staff_position_title_name', 'status:id,name as status_name');
            

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $positions = $positions->whereIn('institution_positions.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $positions = $positions->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $positions->where('institution_id', $institutionId)->paginate($limit)->toArray();
            
            return $list;
            
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
            
            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $positions = InstitutionPositions::with(
                    'staffPositionTitle:id,name as staff_position_title_name', 
                    'status:id,name as status_name'
                )
                ->where('institution_id', $institutionId)
                ->where('id', $positionId);
            

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $positions = $positions->whereIn('institution_positions.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $positions = $positions->first();
            
            return $positions;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions List Not Found');
        }
    }



    public function localeContentsList($request)
    {
        try {
            $params = $request->all();
            $positions = LocaleContentTranslations::with('localeContents:id,en', 'locales:id,name');
            
            if(isset($params['locale_name'])){
                $local_name = $params['locale_name'];
                $positions->whereHas(
                    'locales',
                    function ($q) use($local_name){
                        $q->where('name', $local_name);
                    }
                );
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $positions = $positions->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $positions->paginate($limit)->toArray();
            
            return $list;
            
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
            $locale = LocaleContentTranslations::with('localeContents:id,en', 'locales:id,name')->where('id', $localeId)->first();

            return $locale;
            
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $roomType = new SummaryInstitutionRoomTypes();
            

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $roomType = $roomType->whereIn('summary_institution_room_types.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $roomType = $roomType->orderBy($col, $orderBy);
            }

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $roomType = $roomType->where("academic_period_id", $academic_period_id);
            }

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $roomType->paginate($limit)->toArray();
            
            return $list;
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
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End


            $roomType = new SummaryInstitutionRoomTypes();
            

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $roomType = $roomType->whereIn('summary_institution_room_types.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $roomType = $roomType->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $roomType->where('institution_id', $institutionId)->paginate($limit)->toArray();
            
            return $list;
            
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
        DB::beginTransaction();
        try {
            $data = $request->all();

            $check = $this->checkIfStudentEnrolled($institutionId, $classId, $data['academic_period_id'], $data['student_id'], $data['education_grade_id']);
            
            if($check == 0){
                return 0;
            }

            $isExists = InstitutionStudentReportCardComment::where([
                'report_card_id' => $data['report_card_id'],
                'student_id' => $data['student_id'],
                'institution_id' => $institutionId,
                'academic_period_id' => $data['academic_period_id'],
                'education_grade_id' => $data['education_grade_id'],
                'education_subject_id' => $data['education_subject_id'],
            ])
            ->first();
            //dd($isExists);
            if($isExists){
                
                $updateArr['comments'] = $data['comment'];
                if(isset($data['report_card_comment_code_id'])){
                    $updateArr['report_card_comment_code_id'] = (int)$data['report_card_comment_code_id'];
                }
                $updateArr['staff_id'] = $data['staff_id'];
                $updateArr['modified_user_id'] = JWTAuth::user()->id;
                $updateArr['modified'] = Carbon::now()->toDateTimeString();
                
                $update = InstitutionStudentReportCardComment::where([
                    'report_card_id' => $data['report_card_id'],
                    'student_id' => $data['student_id'],
                    'institution_id' => $institutionId,
                    'academic_period_id' => $data['academic_period_id'],
                    'education_grade_id' => $data['education_grade_id'],
                    'education_subject_id' => $data['education_subject_id'],
                ])->update($updateArr);
            } else {
                
                $store['id'] = Str::uuid();
                $store['comments'] = $data['comment'];
                $store['academic_period_id'] = $data['academic_period_id'];
                $store['report_card_id'] = $data['report_card_id'];
                $store['student_id'] = $data['student_id'];
                $store['institution_id'] = $institutionId;
                $store['education_grade_id'] = $data['education_grade_id'];
                $store['education_subject_id'] = $data['education_subject_id'];
                if(isset($data['report_card_comment_code_id'])){
                    $store['report_card_comment_code_id'] = (int)$data['report_card_comment_code_id'];
                }
                $store['staff_id'] = $data['staff_id'];
                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();
                
                $insert = InstitutionStudentReportCardComment::insert($store);
            }

            
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }



    public function reportCardCommentHomeroomAdd($request, int $institutionId, int $classId)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            //dd($data);

            $check = $this->checkIfStudentEnrolled($institutionId, $classId, $data['academic_period_id'], $data['student_id'], $data['education_grade_id']);
            
            if($check == 0){
                return 0;
            }


            $reportCardId = $data['report_card_id'];
            $studentId = $data['student_id'];
            $academicPeriodId = $data['academic_period_id'];
            $educationGradeId = $data['education_grade_id'];

            $checkIfExists = InstitutionStudentReportCard::where(
                [
                    'report_card_id' => $reportCardId,
                    'student_id' => $studentId,
                    'academic_period_id' => $academicPeriodId,
                    'education_grade_id' => $educationGradeId,
                    'institution_id' => $institutionId,
                ]
            )->first();
            
            if($checkIfExists){
                $updateArr['homeroom_teacher_comments'] = $data['comment'];
                $updateArr['institution_class_id'] = $classId;
                $updateArr['modified'] = Carbon::now()->toDateTimeString();
                $updateArr['modified_user_id'] = JWTAuth::user()->id;

                $update = InstitutionStudentReportCard::where(
                    [
                        'report_card_id' => $reportCardId,
                        'student_id' => $studentId,
                        'academic_period_id' => $academicPeriodId,
                        'education_grade_id' => $educationGradeId,
                        'institution_id' => $institutionId,
                    ]
                )
                ->update($updateArr);
            } else {
                $store['id'] = Str::uuid();
                $store['homeroom_teacher_comments'] = $data['comment'];
                $store['status'] = 1;
                $store['academic_period_id'] = $data['academic_period_id'];
                $store['student_id'] = $data['student_id'];
                $store['institution_id'] = $institutionId;
                $store['institution_class_id'] = $classId;
                $store['education_grade_id'] = $data['education_grade_id'];
                $store['report_card_id'] = $data['report_card_id'];
                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();
                
                $insert = InstitutionStudentReportCard::insert($store);
            }

            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }


    public function checkIfStudentEnrolled($institutionId, $classId, $academicPeriodId, $studentId, $educationGradeId)
    {
        try {
            $check = InstitutionClassStudents::where('student_id', $studentId)
                    ->where('institution_class_id', $classId)
                    ->where('academic_period_id', $academicPeriodId)
                    ->where('institution_id', $institutionId)
                    ->where('education_grade_id', $educationGradeId)
                    ->where('student_status_id', 1) //For enrolled only...
                    ->first();
            
            if($check){
                return 1;
            } else {
                return 0;
            }
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
        DB::beginTransaction();
        try {
            $data = $request->all();
            

            $check = $this->checkIfStudentEnrolled($institutionId, $classId, $data['academic_period_id'], $data['student_id'], $data['education_grade_id']);
            
            if($check == 0){
                return 0;
            }


            $reportCardId = $data['report_card_id'];
            $studentId = $data['student_id'];
            $academicPeriodId = $data['academic_period_id'];
            $educationGradeId = $data['education_grade_id'];

            $checkIfExists = InstitutionStudentReportCard::where(
                [
                    'report_card_id' => $reportCardId,
                    'student_id' => $studentId,
                    'academic_period_id' => $academicPeriodId,
                    'education_grade_id' => $educationGradeId,
                    'institution_id' => $institutionId,
                ]
            )->first();
            
            if($checkIfExists){
                $updateArr['principal_comments'] = $data['comment'];
                $updateArr['institution_class_id'] = $classId;
                $updateArr['modified'] = Carbon::now()->toDateTimeString();
                $updateArr['modified_user_id'] = JWTAuth::user()->id;

                $update = InstitutionStudentReportCard::where(
                    [
                        'report_card_id' => $reportCardId,
                        'student_id' => $studentId,
                        'academic_period_id' => $academicPeriodId,
                        'education_grade_id' => $educationGradeId,
                        'institution_id' => $institutionId,
                    ]
                )
                ->update($updateArr);
            } else {
                $store['id'] = Str::uuid();
                $store['principal_comments'] = $data['comment'];
                $store['status'] = 1;
                $store['academic_period_id'] = $data['academic_period_id'];
                $store['student_id'] = $data['student_id'];
                $store['institution_id'] = $institutionId;
                $store['institution_class_id'] = $classId;
                $store['education_grade_id'] = $data['education_grade_id'];
                $store['report_card_id'] = $data['report_card_id'];
                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();
                
                $insert = InstitutionStudentReportCard::insert($store);
            }

            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollback();
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

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End


            $students = InstitutionStudent::with(
                        'institution', 
                        'studentStatus', 
                        'educationGrade', 
                        'securityUser'
                    )
                    ->with([
                        'institutionClassStudents' => function ($q) use ($institutionId, $gradeId, $studentId) {
                            $q->where('student_id', $studentId)
                                ->where('education_grade_id', $gradeId)
                                ->where('institution_id', $institutionId);
                        }
                    ])
                    ->where('institution_id', $institutionId)
                    ->where('education_grade_id', $gradeId)
                    ->where('student_id', $studentId);


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $students = $students->whereIn('institution_students.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            $list = $students->first();
            
            return $list;
            
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
        DB::beginTransaction();
        try {
            $data = $request->all();

            $check = InstitutionCompetencyResults::where([
                    'institution_id' => $data['institution_id'],
                    'student_id' => $data['student_id'],
                    'competency_template_id' => $data['competency_template_id'],
                    'competency_item_id' => $data['competency_item_id'],
                    'competency_criteria_id' => $data['competency_criteria_id'],
                    'competency_period_id' => $data['competency_period_id'],
                    'academic_period_id' => $data['academic_period_id']
                ])
                ->first();
            
            if($check){
                $updateArr = $data;
                $updateArr['modified'] = Carbon::now()->toDateTimeString();
                $updateArr['modified_user_id'] = JWTAuth::user()->id;

                $update = InstitutionCompetencyResults::where([
                        'institution_id' => $data['institution_id'],
                        'student_id' => $data['student_id'],
                        'competency_template_id' => $data['competency_template_id'],
                        'competency_item_id' => $data['competency_item_id'],
                        'competency_criteria_id' => $data['competency_criteria_id'],
                        'competency_period_id' => $data['competency_period_id'],
                        'academic_period_id' => $data['academic_period_id']
                    ])
                    ->update($updateArr);


            } else {
                $store['id'] = Str::uuid();
                $store['competency_grading_option_id'] = $data['competency_grading_option_id'];
                $store['student_id'] = $data['student_id'];
                $store['competency_template_id'] = $data['competency_template_id'];
                $store['competency_item_id'] = $data['competency_item_id'];
                $store['competency_criteria_id'] = $data['competency_criteria_id'];
                $store['competency_period_id'] = $data['competency_period_id'];
                $store['institution_id'] = $data['institution_id'];
                $store['academic_period_id'] = $data['academic_period_id'];
                $store['comments'] = $data['comments']??Null;
                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();

                $insert = InstitutionCompetencyResults::insert($store);
            }

            
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add competency result.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency result.');
        }
    }


    public function addCompetencyComments($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $check = InstitutionCompetencyItemComments::where([
                    'institution_id' => $data['institution_id'],
                    'student_id' => $data['student_id'],
                    'competency_template_id' => $data['competency_template_id'],
                    'competency_item_id' => $data['competency_item_id'],
                    'competency_period_id' => $data['competency_period_id'],
                    'academic_period_id' => $data['academic_period_id']
                ])
                ->first();


            if($check){
                $updateArr = $data;
                $updateArr['modified'] = Carbon::now()->toDateTimeString();
                $updateArr['modified_user_id'] = JWTAuth::user()->id;

                $update = InstitutionCompetencyItemComments::where([
                        'institution_id' => $data['institution_id'],
                        'student_id' => $data['student_id'],
                        'competency_template_id' => $data['competency_template_id'],
                        'competency_item_id' => $data['competency_item_id'],
                        'competency_period_id' => $data['competency_period_id'],
                        'academic_period_id' => $data['academic_period_id']
                    ])
                    ->update($updateArr);
            } else {
                $store['id'] = Str::uuid();
                $store['student_id'] = $data['student_id'];
                $store['competency_template_id'] = $data['competency_template_id'];
                $store['competency_item_id'] = $data['competency_item_id'];
                $store['competency_period_id'] = $data['competency_period_id'];
                $store['institution_id'] = $data['institution_id'];
                $store['academic_period_id'] = $data['academic_period_id'];
                $store['comments'] = $data['comments']??Null;
                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();
                
                $insert = InstitutionCompetencyItemComments::insert($store);
            }
            
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add competency result.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency result.');
        }
    }


    public function addCompetencyPeriodComments($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();


            $check = InstitutionCompetencyPeriodComments::where([
                    'institution_id' => $data['institution_id'],
                    'student_id' => $data['student_id'],
                    'competency_template_id' => $data['competency_template_id'],
                    'competency_period_id' => $data['competency_period_id'],
                    'academic_period_id' => $data['academic_period_id']
                ])
                ->first();

            if($check){
                $updateArr = $data;
                $updateArr['modified'] = Carbon::now()->toDateTimeString();
                $updateArr['modified_user_id'] = JWTAuth::user()->id;

                $update = InstitutionCompetencyPeriodComments::where([
                        'institution_id' => $data['institution_id'],
                        'student_id' => $data['student_id'],
                        'competency_template_id' => $data['competency_template_id'],
                        'competency_period_id' => $data['competency_period_id'],
                        'academic_period_id' => $data['academic_period_id']
                    ])
                    ->update($updateArr);

            } else {
                $store['id'] = Str::uuid();
                $store['student_id'] = $data['student_id'];
                $store['competency_template_id'] = $data['competency_template_id'];
                $store['competency_period_id'] = $data['competency_period_id'];
                $store['institution_id'] = $data['institution_id'];
                $store['academic_period_id'] = $data['academic_period_id'];
                $store['comments'] = $data['comments']??Null;
                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();
                
                $insert = InstitutionCompetencyPeriodComments::insert($store);
            }
            
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add competency result.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency result.');
        }
    }


    public function getStudentAssessmentItemResult($request, $institutionId, $studentId)
    {
        try {
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End
            
            $lists = AssessmentItemResults::with('assessmentGradingOption')->where('institution_id', $institutionId)->where('student_id', $studentId);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $lists = $lists->whereIn('assessment_item_results.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End
            

            $lists = $lists->get()->toArray();

            return $lists;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student assessment data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get student assessment data.');
        }
    }

    public function displayAddressAreaLevel($request)
    {
        try {
            $params = $request->all();
            $areaLevel = [];

            $configItem = ConfigItem::where('code', 'address_area_level')->first();
            if($configItem){
                $val = $configItem->value;
                $areaLevel = AreaAdministratives::where('area_administrative_level_id', $val)->orderBy('name', 'ASC')->get();
            }
            return $areaLevel;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get address area level area.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get address area level area.');
        }
    }



    public function displayBirthplaceAreaLevel($request)
    {
        try {
            $params = $request->all();
            $areaLevel = [];

            $configItem = ConfigItem::where('code', 'birthplace_area_level')->first();
            if($configItem){
                $val = $configItem->value;
                $areaLevel = AreaAdministratives::where('area_administrative_level_id', $val)->orderBy('name', 'ASC')->get();
                
            }
            return $areaLevel;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get address area level area.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get address area level area.');
        }
    }

    
    public function getSubjectsStaffList($request)
    {
        try {
            $params = $request->all();

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $resp = InstitutionSubjectStaff::with(
                        'staff', 
                        'institution', 
                        'institutionSubject',
                        'institutionSubject.classes.institutionClass',
                        'institutionSubject.students.securityUser',
                        'institutionSubject.academicPeriod',
                        'institutionSubject.educationGrades',
                        'institutionSubject.educationSubjects',
                        'institutionSubject.educationGrades.educationProgramme',
                        'institutionSubject.educationGrades.educationProgramme.educationCycle',
                        'institutionSubject.educationGrades.educationProgramme.educationCycle.educationLevel',
                        'institutionSubject.educationGrades.educationProgramme.educationCycle.educationLevel.educationSystem',
                    )
                    ->where('staff_id', $params['staff_id'])
                    ->where('institution_id', $params['institution_id']);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $resp = $resp->whereIn('institution_subject_staff.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $resp = $resp->get();
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects Staff List Not Found');
        }
    }



    
    // POCOR-7394-S starts

    public function getAbsenceReasons($request)
    {
        try {
                $params = $request->all();

                $AbsenceReasons = new AbsenceReasons();

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $AbsenceReasons = $AbsenceReasons->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $AbsenceReasons->paginate($limit)->toArray();
            return $list;
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Absence Reasons List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Absence Reasons List.');
        }
    }

    public function getAbsenceTypes($request)
    {
        try {

            $params = $request->all();
                $absenceTypes = new AbsenceTypes();

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $absenceTypes = $absenceTypes->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $absenceTypes->paginate($limit)->toArray();
            return $list;
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Absence Types List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Absence Types List.');
        }
    }

    public function getAreaAdministratives($request)
    {
        try {
          
            $params = $request->all();
            $areaAdministratives = AreaAdministratives::with('areaAdministrativeLevels');

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $areaAdministratives = $areaAdministratives->orderBy($col, $orderBy);
            }


            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $areaAdministratives->paginate($limit)->toArray();
            
            return $list;
            
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Area Administratives List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Failed to get Area Administratives List.');
        }
    }

    public function getAreaAdministrativesById($areaAdministrativeId)
    {
        try {

            $isExists = AreaAdministratives::where([
                'id' => $areaAdministrativeId,
            ])
            ->first();

            if($isExists){
                $areaAdministratives = AreaAdministratives::where('id', $areaAdministrativeId)->first();
                return $areaAdministratives;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Area Administrative.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Area Administrative.');
        }
    }

    public function getInstitutionGenders()
    {

        try {
                
            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $institutionGender = new InstitutionGender();
            

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $institutionGender = $institutionGender->join('institutions', 'institutions.institution_gender_id', '=', 'institution_genders.id')->select('institution_genders.*')->groupby('institution_genders.id')->whereIn('institutions.id', $institution_Ids);
            }
            //For POCOR-7772 End

            $institutionGender = $institutionGender->get();

            return $institutionGender;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get Institution Genders List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Institution Genders List.');
        }
    }

    public function getInstitutionsLocalitiesById($localityId)
    {
        try {

            $isExists = InstitutionLocalities::where([
                'id' => $localityId,
            ])
            ->first();

            if($isExists){
                $institutionLocalities = InstitutionLocalities::where('id', $localityId)->first();
                return $institutionLocalities;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Institution Locality.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Institution Locality.');
        }
    }

    public function getInstitutionsOwnershipsById($ownershipId)
    {
        try {

            $isExists = InstitutionOwnerships::where([
                'id' => $ownershipId,
            ])
            ->first();

            if($isExists){
                $institutionOwnerships = InstitutionOwnerships::where('id', $ownershipId)->first();
                return $institutionOwnerships;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Institution Ownership.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Institution Ownership.');
        }
    }

    public function getInstitutionSectorsById($sectorId)
    {
        try {

            $isExists = InstitutionSectors::where([
                'id' => $sectorId,
            ])
            ->first();

            if($isExists){
                $institutionSectors = InstitutionSectors::where('id', $sectorId)->first();
                return $institutionSectors;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Institution Sector.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Institution Sector.');
        }
    }

    public function getInstitutionProvidersById($providerId)
    {
        try {

            $isExists = InstitutionProviders::where([
                'id' => $providerId,
            ])
            ->first();

            if($isExists){
                $institutionProviders = InstitutionProviders::where('id', $providerId)->first();
                return $institutionProviders;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Institution Provider.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Institution Provider.');
        }
    }

    public function getInstitutionTypesById($typeId)
    {
        try {

            $isExists = InstitutionTypes::where([
                'id' => $typeId,
            ])
            ->first();

            if($isExists){
                $institutionTypes = InstitutionTypes::where('id', $typeId)->first();
                return $institutionTypes;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Institution Type.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Institution Type.');
        }
    }

    public function getInstitutionProviderBySectorId($sectorId)
    {
        try {

            $isExists = InstitutionProviders::where([
                'institution_sector_id' => $sectorId,
            ])
            ->first();

            if($isExists){
                $institutionProviders = InstitutionProviders::where('institution_sector_id', $sectorId)->get();
                return $institutionProviders;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Institution Provider By Sector ID.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Institution Provider By Sector ID.');
        }
    }

    public function getMealBenefits($request)
    {
        try {
            
                $params = $request->all();


                $limit = config('constantvalues.defaultPaginateLimit');

                if(isset($params['limit'])){
                $limit = $params['limit'];
                }

                $mealBenefits = new MealBenefits();
                if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $mealBenefits = $mealBenefits->orderBy($col, $orderBy);
                }
                $list = $mealBenefits->paginate($limit);
                return $list;
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Meal Benefits List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Meal Benefits List.');
        }
    }

    public function getMealProgrammes($request)
    {
        try {

                $params = $request->all();

                $mealProgrammes = new MealProgrammes();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $mealProgrammes->paginate($limit)->toArray();
            return $list;
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Meal Programmes List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Meal Programmes List.');
        }
    }

    // POCOR-7394-S ends

    public function deleteClassAttendance($request)
    {
        DB::beginTransaction();
        try {
            $param = $request->all();
            
            $institutionId = $param['institution_id'];
            $academicPeriodId = $param['academic_period_id'];
            $institutionClassId = $param['institution_class_id'];
            $educationGradeId = $param['education_grade_id'];
            $date = $param['date'];
            
            $delete1 = InstitutionStudentAbsenceDetails::where('institution_id', $institutionId)
                        ->where('academic_period_id', $academicPeriodId)
                        ->where('institution_class_id', $institutionClassId)
                        ->where('education_grade_id', $educationGradeId)
                        ->where('date', $date);
            if(isset($param['period'])){
                $delete1 = $delete1->where('period', $param['period']);
            }


            if(isset($param['subject_id'])){
                $delete1 = $delete1->where('subject_id', $param['subject_id']);
            }

            $check1 = $delete1->exists();
                        

            $delete2 = StudentAttendanceMarkedRecords::where('institution_id', $institutionId)
                        ->where('academic_period_id', $academicPeriodId)
                        ->where('institution_class_id', $institutionClassId)
                        ->where('education_grade_id', $educationGradeId)
                        ->where('date', $date);

            if(isset($param['period'])){
                $delete2 = $delete2->where('period', $param['period']);
            }


            if(isset($param['subject_id'])){
                $delete2 = $delete2->where('subject_id', $param['subject_id']);
            }

            $check2 = $delete2->exists();

            if(!$check1 && !$check2){
                DB::commit();
                return 2;
            }
            
            if($check1 || $check2){
                $delete1 = $delete1->delete();
                $delete2 = $delete2->delete();

                DB::commit();
                return 1;
            } else {
                DB::commit();
                return 2;
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to delete student attendance.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to delete student attendance.');
        }
    }



    public function deleteStudentAttendance($request, $studentId)
    {
        DB::beginTransaction();
        try {
            $param = $request->all();

            $institutionId = $param['institution_id'];
            $academicPeriodId = $param['academic_period_id'];
            $institutionClassId = $param['institution_class_id'];
            $educationGradeId = $param['education_grade_id'];
            $date = $param['date'];
            

            $delete1 = InstitutionStudentAbsenceDetails::where('institution_id', $institutionId)
                        ->where('student_id', $studentId)
                        ->where('academic_period_id', $academicPeriodId)
                        ->where('institution_class_id', $institutionClassId)
                        ->where('education_grade_id', $educationGradeId)
                        ->where('date', $date);

            if(isset($param['period'])){
                $delete1 = $delete1->where('period', $param['period']);
            }

            if(isset($param['subject_id'])){
                $delete1 = $delete1->where('subject_id', $param['subject_id']);
            }

            $check1 = $delete1->exists();

            if($check1){
                $delete1 = $delete1->delete();
                DB::commit();
                return 1;
            } else {
                DB::commit();
                return 2;
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to delete student attendance.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to delete student attendance.');
        }
    }

    // POCOR-7546 starts

    public function getBehaviourCategories($request)
    {
        try {
            $params = $request->all();
            $staffBehaviourCategories = new StaffBehaviourCategories();
            

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $staffBehaviourCategories = $staffBehaviourCategories->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $staffBehaviourCategories->paginate($limit)->toArray();
            
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }

    public function getInstitutionStudentBehaviour($institutionId, $studentId)
    {
        try {

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $studentBehaviours = StudentBehaviours::where('institution_id', $institutionId)->where('student_id', $studentId);


            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $studentBehaviours = $studentBehaviours->whereIn('student_behaviours.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            $studentBehaviours = $studentBehaviours->get()->toArray();

            return $studentBehaviours;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Student Behaviour from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Behaviour Not Found');
        }
    }

    public function addStudentAssessmentItemResult($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            //dd($data['assessment_grading_option_id']);
            $isExists = InstitutionClassStudents::where('institution_class_id', $data['institution_classes_id'])->where('education_grade_id', $data['education_grade_id'])->where('academic_period_id', $data['academic_period_id'])->where('student_id', $data['student_id'])->first();
            if($isExists){
                $check = AssessmentItemResults::where('student_id', $data['student_id'])
                    ->where('assessment_id', $data['assessment_id'])
                    ->where('education_subject_id', $data['education_subject_id'])
                    ->where('education_grade_id', $data['education_grade_id'])
                    ->where('academic_period_id', $data['academic_period_id'])
                    ->where('assessment_period_id', $data['assessment_period_id'])
                    ->where('institution_classes_id', $data['institution_classes_id'])
                    ->first();
                if($check){
                    $data['modified_user_id'] = JWTAuth::user()->id;
                    $data['modified'] = Carbon::now()->toDateTimeString();
                    
                    //This function removes the unnecessary columns...
                    $values = removeNonColumnFields($data, 'assessment_item_results');
                    
                    $update = AssessmentItemResults::where('student_id', $data['student_id'])
                        ->where('assessment_id', $data['assessment_id'])
                        ->where('education_subject_id', $data['education_subject_id'])
                        ->where('education_grade_id', $data['education_grade_id'])
                        ->where('academic_period_id', $data['academic_period_id'])
                        ->where('assessment_period_id', $data['assessment_period_id'])
                        ->where('institution_classes_id', $data['institution_classes_id'])
                        ->update($values);
                        $resp = 2;
                } else {
                    $store['id'] = Str::uuid();
                    $store['marks'] = $data['marks']??Null;
                    $store['assessment_grading_option_id'] = $data['assessment_grading_option_id']??Null;
                    $store['student_id'] = $data['student_id'];
                    $store['assessment_id'] = $data['assessment_id'];
                    $store['education_subject_id'] = $data['education_subject_id'];
                    $store['education_grade_id'] = $data['education_grade_id'];
                    $store['academic_period_id'] = $data['academic_period_id'];
                    $store['assessment_period_id'] = $data['assessment_period_id'];
                    $store['institution_id'] = $data['institution_id'];
                    $store['institution_classes_id'] = $data['institution_classes_id'];
                    $store['created_user_id'] = JWTAuth::user()->id;
                    $store['created'] = Carbon::now()->toDateTimeString();

                    $insert = AssessmentItemResults::insert($store);
                    $resp = 1;
                }
            } else {
                $resp = 0;
            }


            
            
            DB::commit();
            return $resp;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'The update of student assessment mark could not be completed successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('The update of student assessment mark could not be completed successfully.');
        }
    }

    public function addStudentBehaviour($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $checkAcademicPeriod = AcademicPeriod::where('id', $data['academic_period_id']??0)->first();
            if(empty($checkAcademicPeriod)){
                return 2;
            }

            $checkInstitution = Institutions::where('id', $data['institution_id'])->first();
            if(empty($checkInstitution)){
                return 3;
            }

            $checkStudent = SecurityUsers::where('id', $data['student_id'])->first();
            if(empty($checkStudent)){
                return 4;
            }

            $checkBehaviourCat = StudentBehaviourCategory::where('id', $data['student_behaviour_category_id'])->first();

            if(empty($checkBehaviourCat)){
                return 5;
            }

            $store['description'] = $data['description'];
            $store['action'] = $data['action'];
            $store['date_of_behaviour'] = $data['date_of_behaviour'];
            $store['time_of_behaviour'] = $data['time_of_behaviour']??Null;
            $store['academic_period_id'] = $data['academic_period_id']??Null;
            $store['student_id'] = $data['student_id'];
            $store['institution_id'] = $data['institution_id'];
            $store['status_id'] = $data['status_id']??Null;
            $store['student_behaviour_category_id'] = $data['student_behaviour_category_id'];
            $store['assignee_id'] = $data['assignee_id']??Null;
            $store['created_user_id'] = JWTAuth::user()->id;
            $store['created'] = Carbon::now()->toDateTimeString();
            $store['student_behaviour_classification_id'] = $data['student_behaviour_classification_id']??Null;
            
            $insert = StudentBehaviours::insert($store);
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error(
                'The update of student behaviour could not be completed successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('The update of student behaviour could not be completed successfully.');
        }
    }

    public function getInstitutionClassEducationGradeStudents($institutionId, $institutionClassId, $educationGradeId)
    {
        try {
            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $studentsId = InstitutionClasses::with([
                'students' => function ($q) use ($institutionId, $institutionClassId, $educationGradeId) {
                    $q->where('institution_id', $institutionId)
                        ->where('institution_class_id', $institutionClassId)
                        ->where('education_grade_id', $educationGradeId);
                        // ->pluck('student_id');
                }
            ])
            ->where('institution_id', $institutionId)
                    ->where('id', $institutionClassId);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $studentsId = $studentsId->whereIn('institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            $list = $studentsId->get();

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get Students List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Students List.');
        }
    }

    public function getInstitutionEducationSubjectStudents($institutionId, $educationGradeId)
    {
        try {

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $studentsId = InstitutionSubjects::with([
                'educationSubjects',
                'students' => function ($q) use ($institutionId, $educationGradeId) {
                    $q->where('institution_id', $institutionId)
                        ->where('education_grade_id', $educationGradeId);
                        // ->pluck('student_id');
                }

            ])
            ->where('institution_id', $institutionId)
            ->where('education_grade_id', $educationGradeId);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $studentsId = $studentsId->whereIn('institution_id', $institution_Ids);
            }
            //For POCOR-7772 End
            
            $list = $studentsId->get()->toArray();
            // dd(count($list));
           

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get Students List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Students List.');
        }
    }


    public function deleteStudentBehaviour($institutionId, $studentId, $behaviourId)
    {
        DB::beginTransaction();
        try {
            
            $isExists = StudentBehaviours::where([
                'institution_id' => $institutionId,
                'student_id' => $studentId,
                'id' => $behaviourId
            ])
            ->first();

            if($isExists){
                $studentBehaviours = StudentBehaviours::where([
                    'institution_id' => $institutionId,
                    'student_id' => $studentId,
                    'id' => $behaviourId
                ])->delete();
                DB::commit();
                return 1;
            }
            else{
                DB::commit();
                return 2;
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to delete student attendance.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to delete student attendance.');
        }
    }
    // POCOR-7546 ends


    // pocor-7545 starts

    public function getSecurityRoleFunction($request)
    {
        try {

            $params = $request->all();

            $securityRoleFunctions = new SecurityRoleFunctions();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $securityRoleFunctions->paginate($limit)->toArray();
            return $list;
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Security Role Function List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Security Role Function List.');
        }
    }

    public function getSecurityGroupUsers($request)
    {
        try {

            $params = $request->all();

            $securityGroupUsers = new SecurityGroupUsers();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $securityGroupUsers->paginate($limit)->toArray();
            return $list;
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Security Role Function List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Security Group Users List.');
        }
    }

    public function getInstitutionStudentsMeals($request)
    {
        try {

            $params = $request->all();

            $institutionMealStudents = new InstitutionMealStudents();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $institutionMealStudents->paginate($limit)->toArray();
            return $list;
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Institution Students Meals List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Institution Students Meals List.');
        }
    }

    public function getStudentsMealsByInstitutionId($institutionId)
    {
        try {

            $isExists = InstitutionMealStudents::where([
                'institution_id' => $institutionId,
            ])->first();
            
            if($isExists){
                $institutionMealStudents = InstitutionMealStudents::where('institution_id', $institutionId)->get();
                return $institutionMealStudents;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Students Meals List By Institution Id.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to get Students Meals List By Institution Id.');
        }
    }

    public function getInstitutionStudentStatusByStudentId($studentId)
    {
        try {

            $isExists = InstitutionStudent::where([
                'student_id' => $studentId,
            ]);

            if($isExists){
                $institutionStudent = InstitutionStudent::where('student_id', $studentId)->get();
                return $institutionStudent;
            }
            else{
                return false;
            }
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Students Status from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch Institution Students Status from DB.');
        }
    }

    public function addInstitutionStudent($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $store['id'] = Str::uuid();
            $store['student_status_id'] = $data['student_status_id'];
            $store['student_id'] = $data['student_id'];
            $store['education_grade_id'] = $data['education_grade_id'];
            $store['academic_period_id'] = $data['academic_period_id'];
            $store['start_date'] = $data['start_date'];
            $store['start_year'] = $data['start_year'];
            $store['end_date'] = $data['end_date'];
            $store['end_year'] = $data['end_year'];
            $store['institution_id'] = $data['institution_id'];
            $store['previous_institution_student_id'] = $data['previous_institution_student_id']??Null;
            $store['created_user_id'] = JWTAuth::user()->id;
            $store['created'] = Carbon::now()->toDateTimeString();

            $insert = InstitutionStudent::insert($store);
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Student is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student is not created/updated successfully.');
        }
    }

    public function addInstitutionStaffPayslip($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
                
            $checkStaff = SecurityUsers::where('id', $data['staff_id'])->first();
            if(!$checkStaff){
                return 2;
            }


            $file_name = $request->file_content->getClientOriginalName();
            $file_name = str_replace(' ', "", $file_name);
            
            $store['name'] = $data['name'];
            $store['description'] = $data['description']??Null;
            $store['file_name'] = $file_name;
            $store['file_content'] = file_get_contents($data['file_content']);
            $store['staff_id'] = $data['staff_id'];
            $store['created_user_id'] = JWTAuth::user()->id;
            $store['created'] = Carbon::now()->toDateTimeString();

            $insert = StaffPayslip::insert($store);
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Payslips is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Payslips is not created/updated successfully.');
        }
    }

    public function addInstitutionStudentMealBenefits($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            //dd($data);

            if(isset($data['id']) && $data['id'] != ""){
                $check = InstitutionMealStudents::where('id', $data['id'])->first();
                if(!$check){
                    return 2;
                }

                $id = $data['id'];
                unset($data['id']);
                $data['modified_user_id'] = JWTAuth::user()->id;
                $data['modified'] = Carbon::now()->toDateTimeString();
                $update = InstitutionMealStudents::where('id', $id)->update($data);
            } else {
                $store['student_id'] = $data['student_id'];
                $store['academic_period_id'] = $data['academic_period_id'];
                $store['institution_class_id'] = $data['institution_class_id'];
                $store['institution_id'] = $data['institution_id'];
                $store['meal_programmes_id'] = $data['meal_programmes_id'];
                $store['date'] = $data['date'];
                $store['meal_benefit_id'] = $data['meal_benefit_id'];
                $store['meal_received_id'] = $data['meal_received_id'];
                $store['paid'] = $data['paid']??Null;
                $store['comment'] = $data['comment']??Null;

                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();

                $insert = InstitutionMealStudents::insert($store);
            }

            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Meal Benefit is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Meal Benefit is not created/updated successfully.');
        }
    }

    public function addInstitutionMealDistributions($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            

            if(isset($data['id']) && $data['id'] != ""){
                $check = InstitutionMealProgrammes::where('id', $data['id'])->first();
                if(!$check){
                    return 2;
                }
                $id = $data['id'];
                unset($data['id']);
                $data['modified_user_id'] = JWTAuth::user()->id;
                $data['modified'] = Carbon::now()->toDateTimeString();
                $update = InstitutionMealProgrammes::where('id', $id)->update($data);
            } else {
                $store['academic_period_id'] = $data['academic_period_id'];
                $store['meal_programmes_id'] = $data['meal_programmes_id'];
                $store['institution_id'] = $data['institution_id']??Null;
                $store['date_received'] = $data['date_received'];
                $store['quantity_received'] = $data['quantity_received'];
                $store['delivery_status_id'] = $data['delivery_status_id'];
                $store['comment'] = $data['comment']??Null;
                $store['meal_rating_id'] = $data['meal_rating_id']??Null;

                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();

                $insert = InstitutionMealProgrammes::insert($store);
            }
            
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Meal Distribution is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Meal Distribution is not created/updated successfully.');
        }
    }

    public function addInstitution($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            if(isset($data['id']) && $data['id'] != ""){

                $check = Institutions::where('id', $data['id'])->first();
                if(!$check){
                    return 2;
                }

                $id = $data['id'];
                unset($data['id']);
                $data['modified_user_id'] = JWTAuth::user()->id;
                $data['modified'] = Carbon::now()->toDateTimeString();
                $update = Institutions::where('id', $id)->update($data);
            } else {
                
                $store['name'] = $data['name'];
                $store['alternative_name'] = $data['alternative_name'];
                $store['code'] = $data['code'];
                $store['address'] = $data['address'];
                $store['postal_code'] = $data['postal_code'];
                $store['contact_person'] = $data['contact_person'];
                $store['telephone'] = $data['telephone'];
                $store['fax'] = $data['fax'];
                $store['email'] = $data['email'];
                $store['website'] = $data['website'];
                $store['date_opened'] = $data['date_opened'];
                $store['year_opened'] = $data['year_opened'];
                $store['date_closed'] = $data['date_closed'];
                $store['year_closed'] = $data['year_closed'];
                $store['longitude'] = $data['longitude'];
                $store['latitude'] = $data['latitude'];
                
                // $store['logo_content'] = $data['logo_content']??Null;
                if(isset($data['logo_content'])){
                    $store['logo_content'] = file_get_contents($data['logo_content']);
                    $store['logo_name'] = $request->logo_content->getClientOriginalName()??NULL;
                }
                $store['shift_type'] = $data['shift_type'];
                $store['classification'] = $data['classification']??1;
                $store['area_id'] = $data['area_id'];
                $store['area_administrative_id'] = $data['area_administrative_id'];
                $store['institution_locality_id'] = $data['institution_locality_id'];
                $store['institution_type_id'] = $data['institution_type_id'];
                $store['institution_ownership_id'] = $data['institution_ownership_id'];
                $store['institution_status_id'] = $data['institution_status_id'];
                $store['institution_sector_id'] = $data['institution_sector_id'];
                $store['institution_provider_id'] = $data['institution_provider_id'];
                $store['institution_gender_id'] = $data['institution_gender_id'];
                $store['security_group_id'] = $data['security_group_id']??0;


                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();

                $insert = Institutions::insert($store);
            }

            
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Institution is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Institution is not created/updated successfully.');
        }
    }

    //pocor-7545 ends


}

