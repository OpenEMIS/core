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

class ReportCardRepository extends Controller
{

    //pocor-7856 starts
    public function getReportCardStudents($request)
    {
        try {
            $resp = [];
            $params = $request->all();
            $academicPeriodId = $params['academic_period_id'];
            $institutionId = $params['institution_id'];
            $classId = $params['institution_class_id'];
            $educationGradeId = $params['education_grade_id'];
            $reportCardId = $params['report_card_id'];
            $educationSubjectId = $params['education_subject_id'];
            $institutionSubjectId = $params['institution_subject_id'];
            $type = $params['type'];

            $lists = InstitutionClassStudents::select(
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
                )
                ->leftjoin('institution_students_report_cards', function ($j) use($reportCardId){
                $j->on('institution_students_report_cards.student_id', '=', 'institution_class_students.student_id')
                ->on('institution_students_report_cards.institution_id', '=', 'institution_class_students.institution_id')
                ->on('institution_students_report_cards.academic_period_id', '=', 'institution_class_students.academic_period_id')
                ->on('institution_students_report_cards.education_grade_id', '=', 'institution_class_students.education_grade_id')
                ->on('institution_students_report_cards.institution_class_id', '=', 'institution_class_students.institution_class_id')
                ->where('institution_students_report_cards.report_card_id', $reportCardId);
            })
            ->join('security_users', 'security_users.id', '=', 'institution_class_students.student_id')
            ->with('user', 'studentStatus')
            ->where(
                [
                    'institution_class_students.academic_period_id' => $academicPeriodId,
                    'institution_class_students.institution_id' => $institutionId,
                    'institution_class_students.institution_class_id' => $classId,
                    'institution_class_students.education_grade_id' => $educationGradeId
                ]
            )
            ->whereNotIn('student_status_id', [3])
            ->groupBy('institution_class_students.student_id')
            ->orderBy('security_users.first_name')
            ->orderBy('security_users.last_name')
            ->get()
            ->toArray();

            if ($type == 'PRINCIPAL') {
                if(count($lists) > 0){
                    foreach ($lists as $k => $l) {
                        //dd($l);
                        $reportCardId = $l['report_card_id'];
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
                }
            } elseif($type == 'HOMEROOM_TEACHER'){
                //
            }
            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }
    //pocor-7856 ends

}

