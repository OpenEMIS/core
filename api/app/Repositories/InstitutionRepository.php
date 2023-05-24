<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InstitutionRepository extends Controller
{

    public function getInstitutions($request)
    {
        try {
            $params = $request->all();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            $institutions = new Institutions();
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $institutions = $institutions->orderBy($col, $orderBy);
            }
            //$list = $institutions->paginate($limit)->toArray();
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
            $institution = Institutions::where('id', $id)->first();
            
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


            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            $grades = new EducationGrades();
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

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $institutionGrade = InstitutionGrades::where('institution_id', $institutionId)->with('educationGrades');

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
            $educationGrade = EducationGrades::where('id', $gradeId)->first();
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

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $classes = $classes->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $institutionClasses = $institutionClasses->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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
            $data = InstitutionClasses::with(
                    'grades:institution_class_id,education_grade_id as grade_id', 
                    'subjects:institution_class_id,institution_subject_id as subject_id',
                    'students:institution_class_id,student_id',
                    'secondary_teachers:institution_class_id,secondary_staff_id as staff_id'
                )->where('id', $classId)
                ->first();

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

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $subjects = $subjects->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $subjects = $subjects->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $subjects = InstitutionSubjects::with(
                    'educationGrades:id,name', 'educationSubjects:id,name', 
                    'classes:institution_subject_id,institution_class_id as class_id', 
                    'rooms:institution_subject_id,institution_room_id as room_id',
                    'staff:institution_subject_id,staff_id',
                    'students:institution_subject_id,student_id as user_id'
                )->where('id', $subjectId)->get();


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
            $shifts = new InstitutionShifts();

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $shifts = $shifts->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $shifts = $shifts->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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
            $institutionShifts = InstitutionShifts::with('shiftOption:id,name');

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $institutionShifts = $institutionShifts->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $institutionShifts = $institutionShifts->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $institutionShift = InstitutionShifts::with('shiftOption:id,name')->where('id', $shiftId)->where('institution_id', $institutionId)->first();

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

            $areas = Institutions::select('id', 'area_administrative_id', 'area_id')
                ->with(
                    'areaAdministratives:id,code,name,parent_id', 
                    'areaAdministratives.areaAdministrativesChild:id,code,name,parent_id',
                    'areaEducation:id,code,name,parent_id',
                    'areaEducation.areaEducationChild:id,code,name,parent_id'
                );

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $areas = $areas->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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

            $areas = Institutions::select('id', 'area_administrative_id', 'area_id')
                ->with(
                    'areaAdministratives:id,code,name,parent_id', 
                    'areaAdministratives.areaAdministrativesChild:id,code,name,parent_id',
                    'areaEducation:id,code,name,parent_id',
                    'areaEducation.areaEducationChild:id,code,name,parent_id'
                )->where('id', $institutionId);

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $areas = $areas->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $data =  Institutions::select('id', 'area_administrative_id', 'area_id')
                ->with(
                    'areaAdministratives:id,code,name,parent_id', 
                    'areaAdministratives.areaAdministrativesChild:id,code,name,parent_id',
                    'areaEducation:id,code,name,parent_id',
                    'areaEducation.areaEducationChild:id,code,name,parent_id'
                )
                ->where('id', $institutionId)
                ->where('area_administrative_id', $areaAdministrativeId)
                ->first();

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
            $summaries = new SummaryInstitutions();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $summaries = $summaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $summaries = $summaries->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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
            $summaries = new SummaryInstitutions();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $summaries = $summaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $summaries = $summaries->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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
            $summaries = new SummaryInstitutionGrades();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $summaries = $summaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $summaries = $summaries->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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
            $summaries = new SummaryInstitutionGrades();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $summaries = $summaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $summaries = $summaries->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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
            $gradeSummary = SummaryInstitutionGrades::where('institution_id', $institutionId)->where('grade_id', $gradeId)->get();
            
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
            $nationalitySummaries = new SummaryInstitutionNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }

            $limit = config('constants.defaultPaginateLimit');

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
            $nationalitySummaries = new SummaryInstitutionNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $nationalitySummaries = new SummaryInstitutionGradeNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $nationalitySummaries = new SummaryInstitutionGradeNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $nationalitySummaries = new SummaryInstitutionGradeNationalities();
            
            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $nationalitySummaries = $nationalitySummaries->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $nationalitySummaries = $nationalitySummaries->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $staffs = InstitutionStaff::with('institution:id,code as institution_code', 'staffStatus:id,name as staff_status_name', 'institutionPosition:id,staff_position_title_id', 'institutionPosition.staffPositionTitle:id,name', 'staffType:id,name as staff_type_name');
            

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $staffs = $staffs->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $staffs = InstitutionStaff::with('institution:id,code as institution_code', 'staffStatus:id,name as staff_status_name', 'institutionPosition:id,staff_position_title_id', 'institutionPosition.staffPositionTitle:id,name', 'staffType:id,name as staff_type_name');
            

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $staffs = $staffs->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $staffs = InstitutionStaff::with('institution:id,code as institution_code', 'staffStatus:id,name as staff_status_name', 'institutionPosition:id,staff_position_title_id', 'institutionPosition.staffPositionTitle:id,name', 'staffType:id,name as staff_type_name')
                ->where('institution_id', $institutionId)
                ->where('staff_id', $staffId)
                ->first();
            
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
            $positions = InstitutionPositions::with('staffPositionTitle:id,name as staff_position_title_name', 'status:id,name as status_name');
            

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $positions = $positions->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $positions = InstitutionPositions::with('staffPositionTitle:id,name as staff_position_title_name', 'status:id,name as status_name');
            

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $positions = $positions->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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
            $positions = InstitutionPositions::with(
                    'staffPositionTitle:id,name as staff_position_title_name', 
                    'status:id,name as status_name'
                )
                ->where('institution_id', $institutionId)
                ->where('id', $positionId)
                ->first();
            
            
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


            $limit = config('constants.defaultPaginateLimit');

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
            $roomType = new SummaryInstitutionRoomTypes();
            

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $roomType = $roomType->orderBy($col, $orderBy);
            }

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];
                $roomType = $roomType->where("academic_period_id", $academic_period_id);
            }

            $limit = config('constants.defaultPaginateLimit');

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
            $roomType = new SummaryInstitutionRoomTypes();
            

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $roomType = $roomType->orderBy($col, $orderBy);
            }


            $limit = config('constants.defaultPaginateLimit');

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

            if($isExists){
                
                $updateArr['comments'] = $data['comment'];
                if($data['report_card_comment_code_id']){
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
                if($data['report_card_comment_code_id']){
                    $store['report_card_comment_code_id'] = (int)$data['report_card_comment_code_id'];
                }
                $store['staff_id'] = $data['staff_id'];
                $store['created_user_id'] = JWTAuth::user()->id;
                $store['created'] = Carbon::now()->toDateTimeString();
                
                $insert = InstitutionStudentReportCardComment::insert($store);
            }

            

            return 1;
            
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


            return true;
            
        } catch (\Exception $e) {
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


            return true;
            
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

            $store['id'] = Str::uuid();
            $store['competency_grading_option_id'] = $data['competency_grading_option_id'];
            $store['student_id'] = $data['student_id'];
            $store['competency_template_id'] = $data['competency_template_id'];
            $store['competency_item_id'] = $data['competency_item_id'];
            $store['competency_criteria_id'] = $data['competency_criteria_id'];
            $store['competency_period_id'] = $data['competency_period_id'];
            $store['institution_id'] = $data['institution_id'];
            $store['academic_period_id'] = $data['academic_period_id'];
            $store['created_user_id'] = JWTAuth::user()->id;
            $store['created'] = Carbon::now()->toDateTimeString();

            $insert = InstitutionCompetencyResults::insert($store);
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
}

