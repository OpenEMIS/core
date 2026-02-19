<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ports all 73 onExcelTemplateInitialise* methods from
 * plugins/CustomExcel/src/Model/Table/ReportCardsTable.php
 *
 * Each getter returns plain arrays (or null/empty on missing data) that are
 * consumed by ReportCardGeneratorService via the $extra['vars'] array.
 */
class ReportCardVariableService
{
    // State shared across getters within one generate() call
    private ?string $reportCardStartDate = null;
    private ?string $reportCardEndDate   = null;
    private ?int    $reportCardEducationGradeId = null;
    private array   $totalResults        = [];
    private array   $extra               = [];

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    public function getAll(array $params): array
    {
        // Reset per-call state
        $this->reportCardStartDate      = null;
        $this->reportCardEndDate        = null;
        $this->reportCardEducationGradeId = null;
        $this->totalResults             = [];
        $this->extra                    = [];

        // ReportCards must run first to populate shared dates
        $vars = [];

        $vars['ReportCards']                          = $this->getReportCards($params);
        $vars['InstitutionStudentsReportCards']        = $this->getInstitutionStudentsReportCards($params);
        $vars['FirstGuardian']                        = $this->getFirstGuardian($params);
        $vars['Extracurriculars']                     = $this->getExtracurriculars($params);
        $vars['Awards']                               = $this->getAwards($params);
        $vars['Admissions']                           = $this->getAdmissions($params);
        $vars['InstitutionStudentsReportCardsComments'] = $this->getInstitutionStudentsReportCardsComments($params);
        $vars['Institutions']                         = $this->getInstitutions($params);
        $vars['Principal']                            = $this->getPrincipal($params);
        $vars['DeputyPrincipal']                      = $this->getDeputyPrincipal($params);
        $vars['InstitutionClasses']                   = $this->getInstitutionClasses($params);
        $vars['InstitutionSubjectStudents']           = $this->getInstitutionSubjectStudents($params);
        $vars['InstitutionSubjectStudentsWithName']   = $this->getInstitutionSubjectStudentsWithName($params);
        $vars['StudentBehaviours']                    = $this->getStudentBehaviours($params);
        $vars['InstitutionStudentAbsencesOldOne']     = $this->getInstitutionStudentAbsencesOldOne($params);
        $vars['InstitutionStudentAbsences']           = $this->getInstitutionStudentAbsences($params);
        $vars['CompetencyTemplates']                  = $this->getCompetencyTemplates($params);
        $vars['AttendanceAge']                        = $this->getAttendanceAge($params);
        $vars['CompetencyPeriodsByTemplate']          = $this->getCompetencyPeriodsByTemplate($params);
        $vars['CompetencyPeriods']                    = $this->getCompetencyPeriods($params);
        $vars['CompetencyItems']                      = $this->getCompetencyItems($params);
        $vars['CompetencyCriterias']                  = $this->getCompetencyCriterias($params);
        $vars['StudentCompetencyPeriodComments']      = $this->getStudentCompetencyPeriodComments($params);
        $vars['StudentCompetencyItemComments']        = $this->getStudentCompetencyItemComments($params);
        $vars['CompetencyCriteriasWithResults']       = $this->getCompetencyCriteriasWithResults($params);
        $vars['StudentCompetencyResults']             = $this->getStudentCompetencyResults($params);
        $vars['Assessments']                          = $this->getAssessments($params);
        $vars['AssessmentPeriods']                    = $this->getAssessmentPeriods($params);
        $vars['AssessmentItems']                      = $this->getAssessmentItems($params);
        $vars['SubjectTeacher']                       = $this->getSubjectTeacher($params);
        $vars['AssessmentItemsStudentSubjects']       = $this->getAssessmentItemsStudentSubjects($params);
        $vars['AssessmentItemsWithResults']           = $this->getAssessmentItemsWithResults($params);
        $vars['AssessmentItemResults']                = $this->getAssessmentItemResults($params);
        $vars['OutcomeTemplates']                     = $this->getOutcomeTemplates($params);
        $vars['OutcomePeriods']                       = $this->getOutcomePeriods($params);
        $vars['OutcomeSubjects']                      = $this->getOutcomeSubjects($params);
        $vars['StudentOutcomeSubjectComments']        = $this->getStudentOutcomeSubjectComments($params);
        $vars['OutcomeCriterias']                     = $this->getOutcomeCriterias($params);
        $vars['StudentOutcomeResults']                = $this->getStudentOutcomeResults($params);
        $vars['GroupAssessmentItemResults']           = $this->getGroupAssessmentItemResults($params);
        $vars['GroupAssessmentPeriods']               = $this->getGroupAssessmentPeriods($params);
        $vars['AssessmentTermResults']                = $this->getAssessmentTermResults($params);
        $vars['NextClassSubjects']                    = $this->getNextClassSubjects($params);
        $vars['StudentNextYearClass']                 = $this->getStudentNextYearClass($params);
        $vars['StudentIdentities']                    = $this->getStudentIdentities($params);
        $vars['InstitutionStudentsReportCardGpaOld']  = $this->getInstitutionStudentsReportCardGpaOld($params);
        $vars['InstitutionStudentsReportCardGpa']     = $this->getInstitutionStudentsReportCardGpa($params);
        $vars['InstitutionStudentGradeGpaOld']        = $this->getInstitutionStudentGradeGpaOld($params);
        $vars['InstitutionStudentGradeGpa']           = $this->getInstitutionStudentGradeGpa($params);
        $vars['ClassAndLevelRanking']                 = $this->getClassAndLevelRanking($params);

        return array_filter($vars, fn($v) => $v !== null && $v !== []);
    }

    // -------------------------------------------------------------------------
    // 1. ReportCards
    // -------------------------------------------------------------------------

    public function getReportCards(array $params): ?array
    {
        if (!isset($params['report_card_id'])) {
            return null;
        }

        $row = DB::table('report_cards as rc')
            ->leftJoin('academic_periods as ap', 'ap.id', '=', 'rc.academic_period_id')
            ->leftJoin('education_grades as eg', 'eg.id', '=', 'rc.education_grade_id')
            ->leftJoin('education_programmes as ep', 'ep.id', '=', 'eg.education_programme_id')
            ->where('rc.id', $params['report_card_id'])
            ->select([
                'rc.*',
                'ap.name as academic_period_name',
                'eg.name as education_grade_name',
                'eg.code as education_grade_code',
                'ep.name as education_programme_name',
            ])
            ->first();

        if (!$row) {
            return null;
        }

        $data = (array) $row;

        // Store shared state for other getters
        $this->reportCardStartDate = $data['start_date'] ?? null;
        $this->reportCardEndDate   = $data['end_date']   ?? null;
        $this->reportCardEducationGradeId = $data['education_grade_id'] ?? null;

        return $data;
    }

    // -------------------------------------------------------------------------
    // 2. InstitutionStudentsReportCards
    // -------------------------------------------------------------------------

    public function getInstitutionStudentsReportCards(array $params): ?array
    {
        if (!isset($params['report_card_id'], $params['student_id'], $params['institution_id'], $params['academic_period_id'])) {
            return null;
        }

        $educationGradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        $row = DB::table('institution_students_report_cards as isrc')
            ->join('security_users as su', 'su.id', '=', 'isrc.student_id')
            ->leftJoin('genders as g', 'g.id', '=', 'su.gender_id')
            ->leftJoin('nationalities as n', 'n.id', '=', 'su.nationality_id')
            ->leftJoin('areas as aa', 'aa.id', '=', 'su.address_area_id')
            ->leftJoin('areas as ba', 'ba.id', '=', 'su.birthplace_area_id')
            ->leftJoin('education_grades as eg', 'eg.id', '=', 'isrc.education_grade_id')
            ->where([
                'isrc.report_card_id'     => $params['report_card_id'],
                'isrc.student_id'         => $params['student_id'],
                'isrc.institution_id'     => $params['institution_id'],
                'isrc.academic_period_id' => $params['academic_period_id'],
            ])
            ->when($educationGradeId, fn($q) => $q->where('isrc.education_grade_id', $educationGradeId))
            ->select([
                'isrc.*',
                'su.openemis_no',
                'su.first_name',
                'su.middle_name',
                'su.third_name',
                'su.last_name',
                'su.preferred_name',
                'su.email',
                'su.address',
                'su.postal_code',
                'su.date_of_birth',
                'su.gender_id',
                'su.nationality_id',
                'su.identity_type_id',
                'su.identity_number',
                'g.name as gender_name',
                'n.name as nationality_name',
                'aa.name as address_area_name',
                'ba.name as birthplace_area_name',
                'eg.name as education_grade_name',
                'eg.code as education_grade_code',
            ])
            ->first();

        if (!$row) {
            return null;
        }

        $data = (array) $row;

        // Compute age
        if (!empty($data['date_of_birth'])) {
            try {
                $dob  = new \DateTime($data['date_of_birth']);
                $now  = new \DateTime();
                $data['age'] = $dob->diff($now)->y . ' Year';
            } catch (\Throwable $e) {
                $data['age'] = null;
            }
        }

        // Body mass
        if ($this->reportCardStartDate && $this->reportCardEndDate) {
            $bodyMass = DB::table('user_body_masses')
                ->where('security_user_id', $params['student_id'])
                ->where('date', '>=', $this->reportCardStartDate)
                ->where('date', '<=', $this->reportCardEndDate)
                ->orderByDesc('date')
                ->orderByDesc('created')
                ->first();

            if ($bodyMass) {
                $data['height']           = $bodyMass->height;
                $data['weight']           = $bodyMass->weight;
                $data['body_mass_index']  = $bodyMass->body_mass_index;
            }
        }

        // GPA from report card date range
        if ($this->reportCardStartDate && $this->reportCardEndDate) {
            $gpaRow = DB::table('institution_students_gpa as isg')
                ->join('education_grades_gpa as egg', function ($join) use ($params) {
                    $join->on('egg.id', '=', 'isg.education_grades_gpa_id')
                        ->where('egg.academic_period_id', $params['academic_period_id'])
                        ->where('egg.education_grade_id', $params['education_grade_id'] ?? 0);
                })
                ->where([
                    'isg.student_id'         => $params['student_id'],
                    'isg.education_grade_id' => $params['education_grade_id'] ?? 0,
                    'isg.academic_period_id' => $params['academic_period_id'],
                    'isg.institution_id'     => $params['institution_id'],
                ])
                ->where('egg.start_date', '>=', $this->reportCardStartDate)
                ->where('egg.end_date', '<=', $this->reportCardEndDate)
                ->select('isg.gpa')
                ->first();

            $data['gpa'] = $gpaRow ? $gpaRow->gpa : null;
        }

        $data['generated_date'] = date('m-d-Y');

        // Wrap student fields
        $data['student'] = [
            'openemis_no'   => $data['openemis_no']   ?? null,
            'first_name'    => $data['first_name']    ?? null,
            'middle_name'   => $data['middle_name']   ?? null,
            'last_name'     => $data['last_name']     ?? null,
            'preferred_name' => $data['preferred_name'] ?? null,
            'email'         => $data['email']         ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender'        => ['name' => $data['gender_name'] ?? null],
            'nationality'   => ['name' => $data['nationality_name'] ?? null],
        ];

        return $data;
    }

    // -------------------------------------------------------------------------
    // 3. FirstGuardian
    // -------------------------------------------------------------------------

    public function getFirstGuardian(array $params): ?array
    {
        if (!isset($params['student_id'])) {
            return null;
        }

        $guardian = DB::table('student_guardians as sg')
            ->join('security_users as su', 'su.id', '=', 'sg.guardian_id')
            ->leftJoin('guardian_relations as gr', 'gr.id', '=', 'sg.guardian_relation_id')
            ->where('sg.student_id', $params['student_id'])
            ->select([
                'sg.id',
                'sg.student_id',
                'sg.guardian_id',
                'sg.guardian_relation_id',
                'su.openemis_no',
                'su.first_name',
                'su.last_name',
                'su.preferred_name',
                'su.email',
                'su.address',
                'su.postal_code',
                'gr.name as guardian_relation_name',
            ])
            ->first();

        if (!$guardian) {
            return null;
        }

        $data = (array) $guardian;

        // Fetch first contact
        $contact = DB::table('user_contacts as uc')
            ->join('contact_types as ct', 'ct.id', '=', 'uc.contact_type_id')
            ->leftJoin('contact_options as co', 'co.id', '=', 'ct.contact_option_id')
            ->where('uc.security_user_id', $guardian->guardian_id)
            ->select(['uc.*', 'ct.name as contact_type_name', 'co.name as contact_option_name'])
            ->first();

        $data['contact'] = $contact ? (array) $contact : [];
        return $data;
    }

    // -------------------------------------------------------------------------
    // 4. Extracurriculars
    // -------------------------------------------------------------------------

    public function getExtracurriculars(array $params): array
    {
        if (!isset($params['student_id']) || !$this->reportCardStartDate || !$this->reportCardEndDate) {
            return [];
        }

        return DB::table('student_extracurriculars')
            ->where('security_user_id', $params['student_id'])
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('end_date')
                        ->where('start_date', '<=', $this->reportCardStartDate)
                        ->where('end_date', '>=', $this->reportCardStartDate);
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('end_date')
                        ->where('start_date', '<=', $this->reportCardEndDate)
                        ->where('end_date', '>=', $this->reportCardEndDate);
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('end_date')
                        ->where('start_date', '>=', $this->reportCardStartDate)
                        ->where('end_date', '<=', $this->reportCardEndDate);
                });
            })
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 5. Awards
    // -------------------------------------------------------------------------

    public function getAwards(array $params): array
    {
        if (!isset($params['student_id']) || !$this->reportCardStartDate || !$this->reportCardEndDate) {
            return [];
        }

        return DB::table('user_awards')
            ->where('security_user_id', $params['student_id'])
            ->where('issue_date', '>=', $this->reportCardStartDate)
            ->where('issue_date', '<=', $this->reportCardEndDate)
            ->selectRaw('*, DATE_FORMAT(issue_date, \'%m/%d/%y\') as award_date')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 6. Admissions
    // -------------------------------------------------------------------------

    public function getAdmissions(array $params): ?array
    {
        if (!isset($params['student_id'], $params['academic_period_id'], $params['institution_id'])) {
            return null;
        }
        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        $row = DB::table('institution_students')
            ->where([
                'student_id'         => $params['student_id'],
                'academic_period_id' => $params['academic_period_id'],
                'institution_id'     => $params['institution_id'],
            ])
            ->when($gradeId, fn($q) => $q->where('education_grade_id', $gradeId))
            ->selectRaw('*, DATE_FORMAT(start_date, \'%m/%d/%y\') as admission_date')
            ->first();

        return $row ? (array) $row : null;
    }

    // -------------------------------------------------------------------------
    // 7. InstitutionStudentsReportCardsComments
    // -------------------------------------------------------------------------

    public function getInstitutionStudentsReportCardsComments(array $params): array
    {
        if (!isset($params['report_card_id'], $params['student_id'], $params['institution_id'], $params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        $subjects = DB::table('institution_subject_students as iss')
            ->join('education_subjects as es', 'es.id', '=', 'iss.education_subject_id')
            ->join('institution_subjects as ins', function ($join) use ($params) {
                $join->on('ins.institution_id', '=', 'iss.institution_id')
                    ->on('ins.education_subject_id', '=', 'iss.education_subject_id')
                    ->on('ins.academic_period_id', '=', 'iss.academic_period_id');
            })
            ->where([
                'iss.student_id'           => $params['student_id'],
                'iss.institution_class_id' => $params['institution_class_id'],
                'iss.institution_id'       => $params['institution_id'],
                'iss.academic_period_id'   => $params['academic_period_id'],
            ])
            ->when($gradeId, fn($q) => $q->where('iss.education_grade_id', $gradeId))
            ->select(['iss.*', 'es.name as education_subject_name', 'ins.name as institution_subject_name'])
            ->get();

        if ($subjects->isEmpty()) {
            return [];
        }

        $results = [];
        foreach ($subjects as $subject) {
            $comment = DB::table('institution_students_report_cards_comments as c')
                ->leftJoin('security_users as su', 'su.id', '=', 'c.modified_user_id')
                ->where([
                    'c.report_card_id'           => $params['report_card_id'],
                    'c.student_id'               => $params['student_id'],
                    'c.institution_id'           => $params['institution_id'],
                    'c.academic_period_id'       => $params['academic_period_id'],
                    'c.education_subject_id'     => $subject->education_subject_id,
                ])
                ->select(['c.*', 'su.first_name as modified_user_first_name', 'su.last_name as modified_user_last_name'])
                ->first();

            $row = array_merge((array) $subject, [
                'comment' => $comment ? (array) $comment : null,
            ]);
            $results[] = $row;
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // 8. Institutions
    // -------------------------------------------------------------------------

    public function getInstitutions(array $params): ?array
    {
        if (!isset($params['institution_id'])) {
            return null;
        }

        $row = DB::table('institutions as i')
            ->leftJoin('institution_providers as ip', 'ip.id', '=', 'i.institution_provider_id')
            ->leftJoin('areas as a', 'a.id', '=', 'i.area_id')
            ->leftJoin('area_administratives as aad', 'aad.id', '=', 'i.area_administrative_id')
            ->where('i.id', $params['institution_id'])
            ->select([
                'i.*',
                'ip.name as provider_name',
                'a.name as area_name',
                'aad.name as area_administrative_name',
            ])
            ->first();

        if (!$row) {
            return null;
        }

        $data = (array) $row;
        $data['fax']          = '';
        $data['address_area'] = $data['area_administrative_name'] ?? '';

        return $data;
    }

    // -------------------------------------------------------------------------
    // 9. Principal
    // -------------------------------------------------------------------------

    public function getPrincipal(array $params): ?array
    {
        if (!isset($params['institution_id'])) {
            return null;
        }

        return $this->getSecurityStaffByRoleName($params['institution_id'], 'Principal');
    }

    // -------------------------------------------------------------------------
    // 10. DeputyPrincipal
    // -------------------------------------------------------------------------

    public function getDeputyPrincipal(array $params): ?array
    {
        if (!isset($params['institution_id'])) {
            return null;
        }

        return $this->getSecurityStaffByRoleName($params['institution_id'], 'Deputy Principal');
    }

    // -------------------------------------------------------------------------
    // 11. InstitutionClasses
    // -------------------------------------------------------------------------

    public function getInstitutionClasses(array $params): ?array
    {
        if (!isset($params['institution_class_id'])) {
            return null;
        }

        $row = DB::table('institution_classes as ic')
            ->leftJoin('security_users as su', 'su.id', '=', 'ic.staff_id')
            ->leftJoin('genders as g', 'g.id', '=', 'su.gender_id')
            ->where('ic.id', $params['institution_class_id'])
            ->select([
                'ic.*',
                'su.first_name as staff_first_name',
                'su.last_name as staff_last_name',
                'su.preferred_name as staff_preferred_name',
                'su.openemis_no as staff_openemis_no',
                'su.email as staff_email',
                'su.gender_id as staff_gender_id',
                'g.name as staff_gender_name',
            ])
            ->first();

        if (!$row) {
            return null;
        }

        $data = (array) $row;

        // Homeroom teacher name
        $data['homeroom'] = trim(($data['staff_first_name'] ?? '') . ' ' . ($data['staff_last_name'] ?? ''));
        $data['staff']    = [
            'first_name'    => $data['staff_first_name'] ?? null,
            'last_name'     => $data['staff_last_name']  ?? null,
            'preferred_name' => $data['staff_preferred_name'] ?? null,
            'openemis_no'   => $data['staff_openemis_no']   ?? null,
            'email'         => $data['staff_email']         ?? null,
            'gender_id'     => ($data['staff_gender_id'] == 1) ? 'Male' : 'Female',
            'gender'        => ['name' => $data['staff_gender_name'] ?? null],
        ];

        // Secondary staff
        $secondary = DB::table('institution_classes_secondary_staff as icss')
            ->join('security_users as su', 'su.id', '=', 'icss.secondary_staff_id')
            ->where('icss.institution_class_id', $params['institution_class_id'])
            ->select(['su.first_name', 'su.last_name', 'su.preferred_name'])
            ->first();

        if ($secondary) {
            $data['secondary'] = trim($secondary->first_name . ' ' . $secondary->last_name);
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // 12. InstitutionSubjectStudents
    // -------------------------------------------------------------------------

    public function getInstitutionSubjectStudents(array $params): array
    {
        if (!isset($params['student_id'], $params['institution_class_id'], $params['institution_id'], $params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        $rows = DB::table('institution_subject_students as iss')
            ->where([
                'iss.student_id'           => $params['student_id'],
                'iss.institution_class_id' => $params['institution_class_id'],
                'iss.institution_id'       => $params['institution_id'],
                'iss.academic_period_id'   => $params['academic_period_id'],
            ])
            ->when($gradeId, fn($q) => $q->where('iss.education_grade_id', $gradeId))
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        foreach ($rows as &$row) {
            $sid = $row['student_id'];
            $sub = $row['education_subject_id'];
            $row['total_mark'] = $this->totalResults[$sid][$sub] ?? null;
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // 13. InstitutionSubjectStudentsWithName
    // -------------------------------------------------------------------------

    public function getInstitutionSubjectStudentsWithName(array $params): array
    {
        if (!isset($params['student_id'], $params['institution_class_id'], $params['institution_id'], $params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        $rows = DB::table('institution_subject_students as iss')
            ->join('education_subjects as es', 'es.id', '=', 'iss.education_subject_id')
            ->join('institution_subjects as ins', function ($join) use ($params) {
                $join->on('ins.education_subject_id', '=', 'iss.education_subject_id')
                    ->on('ins.institution_id', '=', 'iss.institution_id')
                    ->on('ins.academic_period_id', '=', 'iss.academic_period_id');
            })
            ->where([
                'iss.student_id'           => $params['student_id'],
                'iss.institution_class_id' => $params['institution_class_id'],
                'iss.institution_id'       => $params['institution_id'],
                'iss.academic_period_id'   => $params['academic_period_id'],
            ])
            ->when($gradeId, fn($q) => $q->where('iss.education_grade_id', $gradeId))
            ->select(['iss.*', 'es.name as education_subject_name', 'ins.name as institution_subject_name'])
            ->get();

        $result = [];
        $i = 1;
        foreach ($rows as $row) {
            $result[] = [
                'education_subject_id' => $row->education_subject_id,
                'id'                   => $row->id . $i,
                'name'                 => $row->institution_subject_name,
                'subjectName'          => $row->education_subject_name,
            ];
            $i++;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // 14. StudentBehaviours
    // -------------------------------------------------------------------------

    public function getStudentBehaviours(array $params): array
    {
        if (!isset($params['student_id'], $params['institution_id']) || !$this->reportCardStartDate || !$this->reportCardEndDate) {
            return [];
        }

        return DB::table('student_behaviours as sb')
            ->leftJoin('student_behaviour_categories as sbc', 'sbc.id', '=', 'sb.student_behaviour_category_id')
            ->where('sb.student_id', $params['student_id'])
            ->where('sb.institution_id', $params['institution_id'])
            ->where('sb.date_of_behaviour', '>=', $this->reportCardStartDate)
            ->where('sb.date_of_behaviour', '<=', $this->reportCardEndDate)
            ->select(['sb.*', 'sbc.name as category_name'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 15. InstitutionStudentAbsencesOldOne (legacy)
    // -------------------------------------------------------------------------

    public function getInstitutionStudentAbsencesOldOne(array $params): array
    {
        if (!isset($params['institution_class_id'], $params['institution_id'], $params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        $absenceTypes = DB::table('absence_types')
            ->pluck('code', 'id')
            ->toArray();

        $results = [];
        foreach ($absenceTypes as $id => $code) {
            $results[$code]['number_of_days'] = 0;
        }
        $results['TOTAL_ABSENCE']['number_of_days'] = 0;

        $absences = DB::table('institution_student_absences')
            ->where([
                'institution_id'     => $params['institution_id'],
                'student_id'         => $params['student_id'],
                'academic_period_id' => $params['academic_period_id'],
            ])
            ->get();

        foreach ($absences as $absence) {
            $code = $absenceTypes[$absence->absence_type_id] ?? null;
            if (!$code) {
                continue;
            }
            if (in_array($code, ['EXCUSED', 'UNEXCUSED'])) {
                $results['TOTAL_ABSENCE']['number_of_days']++;
            }
            if (isset($results[$code])) {
                $results[$code]['number_of_days']++;
            }
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // 16. InstitutionStudentAbsences
    // -------------------------------------------------------------------------

    public function getInstitutionStudentAbsences(array $params): array
    {
        if (!isset($params['institution_class_id'], $params['institution_id'], $params['student_id'], $params['academic_period_id'])
            || !$this->reportCardStartDate || !$this->reportCardEndDate) {
            return [];
        }

        $startDate = $this->reportCardStartDate;
        $endDate   = $this->reportCardEndDate;

        $absenceTypes = DB::table('absence_types')->pluck('code', 'id')->toArray();

        $results = [];
        foreach ($absenceTypes as $id => $code) {
            $results[$code]['number_of_days'] = 0;
        }
        $results['TOTAL_ABSENCE']['number_of_days'] = 0;

        $configOption = DB::table('config_items')
            ->where('code', 'calculate_daily_attendance')
            ->value('value') ?? 1;

        // Count distinct absence days per type using raw SQL
        $absenceCounts = DB::select("
            SELECT
                absence_type_id,
                COUNT(DISTINCT DATE(date)) as days
            FROM institution_student_absence_details
            WHERE institution_id = ?
              AND student_id = ?
              AND academic_period_id = ?
              AND education_grade_id = ?
              AND institution_class_id = ?
              AND date >= ?
              AND date <= ?
            GROUP BY absence_type_id
        ", [
            $params['institution_id'],
            $params['student_id'],
            $params['academic_period_id'],
            $params['education_grade_id'],
            $params['institution_class_id'],
            $startDate,
            $endDate,
        ]);

        foreach ($absenceCounts as $row) {
            $code = $absenceTypes[$row->absence_type_id] ?? null;
            if (!$code) {
                continue;
            }
            if (isset($results[$code])) {
                $results[$code]['number_of_days'] = (int) $row->days;
            }
            if (in_array($code, ['EXCUSED', 'UNEXCUSED'])) {
                $results['TOTAL_ABSENCE']['number_of_days'] += (int) $row->days;
            }
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // 17. CompetencyTemplates
    // -------------------------------------------------------------------------

    public function getCompetencyTemplates(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        return DB::table('competency_templates')
            ->where('academic_period_id', $params['academic_period_id'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 18. AttendanceAge
    // -------------------------------------------------------------------------

    public function getAttendanceAge(array $params): array
    {
        // Returns age range for attendance
        return [];
    }

    // -------------------------------------------------------------------------
    // 19. CompetencyPeriodsByTemplate
    // -------------------------------------------------------------------------

    public function getCompetencyPeriodsByTemplate(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        return DB::table('competency_periods as cp')
            ->join('competency_templates as ct', 'ct.id', '=', 'cp.competency_template_id')
            ->where('ct.academic_period_id', $params['academic_period_id'])
            ->select(['cp.*', 'ct.name as template_name'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 20. CompetencyPeriods
    // -------------------------------------------------------------------------

    public function getCompetencyPeriods(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        return DB::table('competency_periods')
            ->where('academic_period_id', $params['academic_period_id'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 21. CompetencyItems
    // -------------------------------------------------------------------------

    public function getCompetencyItems(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        return DB::table('competency_items as ci')
            ->join('competency_periods as cp', 'cp.id', '=', 'ci.competency_period_id')
            ->join('competency_templates as ct', 'ct.id', '=', 'cp.competency_template_id')
            ->where('ct.academic_period_id', $params['academic_period_id'])
            ->select(['ci.*'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 22. CompetencyCriterias
    // -------------------------------------------------------------------------

    public function getCompetencyCriterias(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        return DB::table('competency_criterias as cc')
            ->join('competency_items as ci', 'ci.id', '=', 'cc.competency_item_id')
            ->join('competency_periods as cp', 'cp.id', '=', 'ci.competency_period_id')
            ->join('competency_templates as ct', 'ct.id', '=', 'cp.competency_template_id')
            ->where('ct.academic_period_id', $params['academic_period_id'])
            ->select(['cc.*'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 23. StudentCompetencyPeriodComments
    // -------------------------------------------------------------------------

    public function getStudentCompetencyPeriodComments(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        return DB::table('student_competency_period_comments')
            ->where([
                'student_id'         => $params['student_id'],
                'academic_period_id' => $params['academic_period_id'],
            ])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 24. StudentCompetencyItemComments
    // -------------------------------------------------------------------------

    public function getStudentCompetencyItemComments(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        return DB::table('student_competency_item_comments')
            ->where([
                'student_id'         => $params['student_id'],
                'academic_period_id' => $params['academic_period_id'],
            ])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 25. CompetencyCriteriasWithResults
    // -------------------------------------------------------------------------

    public function getCompetencyCriteriasWithResults(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        return DB::table('competency_criterias as cc')
            ->join('competency_items as ci', 'ci.id', '=', 'cc.competency_item_id')
            ->join('competency_periods as cp', 'cp.id', '=', 'ci.competency_period_id')
            ->join('competency_templates as ct', 'ct.id', '=', 'cp.competency_template_id')
            ->leftJoin('student_competency_results as scr', function ($join) use ($params) {
                $join->on('scr.competency_criteria_id', '=', 'cc.id')
                    ->where('scr.student_id', $params['student_id'])
                    ->where('scr.academic_period_id', $params['academic_period_id']);
            })
            ->where('ct.academic_period_id', $params['academic_period_id'])
            ->select(['cc.*', 'scr.competency_grading_option_id', 'scr.marks'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 26. StudentCompetencyResults
    // -------------------------------------------------------------------------

    public function getStudentCompetencyResults(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        return DB::table('student_competency_results')
            ->where([
                'student_id'         => $params['student_id'],
                'academic_period_id' => $params['academic_period_id'],
            ])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 27. Assessments
    // -------------------------------------------------------------------------

    public function getAssessments(array $params): ?array
    {
        if (!isset($params['academic_period_id'], $params['education_grade_id'])) {
            return null;
        }

        $row = DB::table('assessments')
            ->where('academic_period_id', $params['academic_period_id'])
            ->where('education_grade_id', $params['education_grade_id'] ?? ($this->reportCardEducationGradeId ?? 0))
            ->first();

        return $row ? (array) $row : null;
    }

    // -------------------------------------------------------------------------
    // 28. AssessmentPeriods
    // -------------------------------------------------------------------------

    public function getAssessmentPeriods(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        $rows = DB::table('assessment_periods as ap')
            ->join('assessments as a', 'a.id', '=', 'ap.assessment_id')
            ->where('a.academic_period_id', $params['academic_period_id'])
            ->when($gradeId, fn($q) => $q->where('a.education_grade_id', $gradeId))
            ->select(['ap.*', 'a.id as assessment_id', 'a.code as assessment_code'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        return $rows;
    }

    // -------------------------------------------------------------------------
    // 29. AssessmentItems
    // -------------------------------------------------------------------------

    public function getAssessmentItems(array $params): array
    {
        if (!isset($params['academic_period_id'], $params['institution_class_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        return DB::table('assessment_items as ai')
            ->join('assessments as a', 'a.id', '=', 'ai.assessment_id')
            ->join('education_subjects as es', 'es.id', '=', 'ai.education_subject_id')
            ->where('a.academic_period_id', $params['academic_period_id'])
            ->when($gradeId, fn($q) => $q->where('a.education_grade_id', $gradeId))
            ->select(['ai.*', 'es.name as education_subject_name', 'es.code as education_subject_code'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 30. SubjectTeacher
    // -------------------------------------------------------------------------

    public function getSubjectTeacher(array $params): array
    {
        if (!isset($params['institution_id'], $params['academic_period_id'])) {
            return [];
        }

        return DB::table('institution_subjects as ins')
            ->join('institution_subject_staff as iss', 'iss.institution_subject_id', '=', 'ins.id')
            ->join('security_users as su', 'su.id', '=', 'iss.staff_id')
            ->where([
                'ins.institution_id'     => $params['institution_id'],
                'ins.academic_period_id' => $params['academic_period_id'],
            ])
            ->select([
                'ins.education_subject_id',
                'ins.id as institution_subject_id',
                'ins.name as institution_subject_name',
                'su.first_name',
                'su.last_name',
                'su.openemis_no',
            ])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 31. AssessmentItemsStudentSubjects
    // -------------------------------------------------------------------------

    public function getAssessmentItemsStudentSubjects(array $params): array
    {
        if (!isset($params['student_id'], $params['institution_class_id'], $params['institution_id'], $params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        return DB::table('assessment_items as ai')
            ->join('assessments as a', 'a.id', '=', 'ai.assessment_id')
            ->join('institution_subject_students as iss', function ($join) use ($params, $gradeId) {
                $join->on('iss.education_subject_id', '=', 'ai.education_subject_id')
                    ->where('iss.student_id', $params['student_id'])
                    ->where('iss.institution_class_id', $params['institution_class_id'])
                    ->where('iss.institution_id', $params['institution_id'])
                    ->where('iss.academic_period_id', $params['academic_period_id'])
                    ->when($gradeId, fn($j) => $j->where('iss.education_grade_id', $gradeId));
            })
            ->join('education_subjects as es', 'es.id', '=', 'ai.education_subject_id')
            ->where('a.academic_period_id', $params['academic_period_id'])
            ->when($gradeId, fn($q) => $q->where('a.education_grade_id', $gradeId))
            ->select(['ai.*', 'es.name as education_subject_name'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 32. AssessmentItemsWithResults
    // -------------------------------------------------------------------------

    public function getAssessmentItemsWithResults(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        return DB::table('assessment_items as ai')
            ->join('assessments as a', 'a.id', '=', 'ai.assessment_id')
            ->leftJoin('assessment_item_results as air', function ($join) use ($params) {
                $join->on('air.assessment_item_id', '=', 'ai.id')
                    ->where('air.student_id', $params['student_id']);
            })
            ->join('education_subjects as es', 'es.id', '=', 'ai.education_subject_id')
            ->where('a.academic_period_id', $params['academic_period_id'])
            ->when($gradeId, fn($q) => $q->where('a.education_grade_id', $gradeId))
            ->select(['ai.*', 'air.marks', 'air.assessment_grading_option_id', 'es.name as education_subject_name'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 33. AssessmentItemResults (POCOR-9143)
    // -------------------------------------------------------------------------

    public function getAssessmentItemResults(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        $results = DB::table('assessment_item_results as air')
            ->join('assessment_items as ai', 'ai.id', '=', 'air.assessment_item_id')
            ->join('assessments as a', 'a.id', '=', 'ai.assessment_id')
            ->join('assessment_periods as ap', 'ap.id', '=', 'air.assessment_period_id')
            ->join('education_subjects as es', 'es.id', '=', 'ai.education_subject_id')
            ->leftJoin('assessment_grading_options as ago', 'ago.id', '=', 'air.assessment_grading_option_id')
            ->where('air.student_id', $params['student_id'])
            ->where('a.academic_period_id', $params['academic_period_id'])
            ->when($gradeId, fn($q) => $q->where('a.education_grade_id', $gradeId))
            ->select([
                'air.*',
                'ap.code as assessment_period_code',
                'ap.name as assessment_period_name',
                'ap.weight as assessment_period_weight',
                'es.id as education_subject_id',
                'es.name as education_subject_name',
                'ago.code as grading_option_code',
                'ago.name as grading_option_name',
            ])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        // Build total_results for InstitutionSubjectStudents to use
        foreach ($results as $result) {
            $sid = $result['student_id'];
            $sub = $result['education_subject_id'];
            if (!isset($this->totalResults[$sid][$sub])) {
                $this->totalResults[$sid][$sub] = 0;
            }
            $this->totalResults[$sid][$sub] += (float) ($result['marks'] ?? 0);
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // 34. OutcomeTemplates
    // -------------------------------------------------------------------------

    public function getOutcomeTemplates(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        return DB::table('outcome_templates')
            ->where('academic_period_id', $params['academic_period_id'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 35. OutcomePeriods
    // -------------------------------------------------------------------------

    public function getOutcomePeriods(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        return DB::table('outcome_periods as op')
            ->join('outcome_templates as ot', 'ot.id', '=', 'op.outcome_template_id')
            ->where('ot.academic_period_id', $params['academic_period_id'])
            ->select(['op.*'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 36. OutcomeSubjects
    // -------------------------------------------------------------------------

    public function getOutcomeSubjects(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        return DB::table('outcome_subjects as os')
            ->join('outcome_templates as ot', 'ot.id', '=', 'os.outcome_template_id')
            ->join('education_subjects as es', 'es.id', '=', 'os.education_subject_id')
            ->where('ot.academic_period_id', $params['academic_period_id'])
            ->select(['os.*', 'es.name as education_subject_name'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 37. StudentOutcomeSubjectComments
    // -------------------------------------------------------------------------

    public function getStudentOutcomeSubjectComments(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        return DB::table('student_outcome_subject_comments')
            ->where([
                'student_id'         => $params['student_id'],
                'academic_period_id' => $params['academic_period_id'],
            ])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 38. OutcomeCriterias
    // -------------------------------------------------------------------------

    public function getOutcomeCriterias(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        return DB::table('outcome_criterias as oc')
            ->join('outcome_subjects as os', 'os.id', '=', 'oc.outcome_subject_id')
            ->join('outcome_templates as ot', 'ot.id', '=', 'os.outcome_template_id')
            ->where('ot.academic_period_id', $params['academic_period_id'])
            ->select(['oc.*'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 39. StudentOutcomeResults
    // -------------------------------------------------------------------------

    public function getStudentOutcomeResults(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        return DB::table('student_outcome_results')
            ->where([
                'student_id'         => $params['student_id'],
                'academic_period_id' => $params['academic_period_id'],
            ])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 40. GroupAssessmentItemResults
    // -------------------------------------------------------------------------

    public function getGroupAssessmentItemResults(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        return DB::select("
            SELECT
                es.id AS education_subject_id,
                es.name AS education_subject_name,
                ap.name AS assessment_period_name,
                ap.code AS assessment_period_code,
                SUM(air.marks * ap.weight) / SUM(ap.weight) AS weighted_average
            FROM assessment_item_results air
            JOIN assessment_items ai ON ai.id = air.assessment_item_id
            JOIN assessments a ON a.id = ai.assessment_id
            JOIN assessment_periods ap ON ap.id = air.assessment_period_id
            JOIN education_subjects es ON es.id = ai.education_subject_id
            WHERE air.student_id = ?
              AND a.academic_period_id = ?
            " . ($gradeId ? " AND a.education_grade_id = " . (int) $gradeId : "") . "
            GROUP BY es.id, es.name, ap.id, ap.name, ap.code
        ", [
            $params['student_id'],
            $params['academic_period_id'],
        ]);
    }

    // -------------------------------------------------------------------------
    // 41. GroupAssessmentPeriods
    // -------------------------------------------------------------------------

    public function getGroupAssessmentPeriods(array $params): array
    {
        if (!isset($params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        $rows = DB::table('assessment_periods as ap')
            ->join('assessments as a', 'a.id', '=', 'ap.assessment_id')
            ->where('a.academic_period_id', $params['academic_period_id'])
            ->when($gradeId, fn($q) => $q->where('a.education_grade_id', $gradeId))
            ->select(['ap.*'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $rows[] = ['name' => 'Average', 'code' => 'AVERAGE', 'id' => null];
        return $rows;
    }

    // -------------------------------------------------------------------------
    // 42. AssessmentTermResults
    // -------------------------------------------------------------------------

    public function getAssessmentTermResults(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        return DB::select("
            SELECT
                ap.academic_term,
                es.id AS education_subject_id,
                es.name AS education_subject_name,
                SUM(air.marks * ap.weight) / SUM(ap.weight) AS term_average,
                SUM(air.marks) AS term_total
            FROM assessment_item_results air
            JOIN assessment_items ai ON ai.id = air.assessment_item_id
            JOIN assessments a ON a.id = ai.assessment_id
            JOIN assessment_periods ap ON ap.id = air.assessment_period_id
            JOIN education_subjects es ON es.id = ai.education_subject_id
            WHERE air.student_id = ?
              AND a.academic_period_id = ?
            " . ($gradeId ? " AND a.education_grade_id = " . (int) $gradeId : "") . "
            GROUP BY ap.academic_term, es.id, es.name
        ", [
            $params['student_id'],
            $params['academic_period_id'],
        ]);
    }

    // -------------------------------------------------------------------------
    // 43. NextClassSubjects
    // -------------------------------------------------------------------------

    public function getNextClassSubjects(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'])) {
            return [];
        }

        $nextPeriodId = $this->getNextAcademicPeriodId($params['academic_period_id']);
        if (!$nextPeriodId) {
            return [];
        }

        return DB::table('institution_subject_students')
            ->join('education_subjects as es', 'es.id', '=', 'institution_subject_students.education_subject_id')
            ->where([
                'institution_subject_students.student_id'         => $params['student_id'],
                'institution_subject_students.academic_period_id' => $nextPeriodId,
            ])
            ->select(['institution_subject_students.*', 'es.name as education_subject_name'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 44. StudentNextYearClass
    // -------------------------------------------------------------------------

    public function getStudentNextYearClass(array $params): ?array
    {
        if (!isset($params['student_id'], $params['institution_class_id'])) {
            return null;
        }

        $row = DB::table('institution_classes as ic')
            ->join('institution_classes as nic', 'nic.id', '=', 'ic.next_institution_class_id')
            ->join('institution_class_students as ics', function ($join) use ($params) {
                $join->on('ics.institution_class_id', '=', 'nic.id')
                    ->where('ics.student_id', $params['student_id']);
            })
            ->where('ic.id', $params['institution_class_id'])
            ->select(['nic.name as next_class_name', 'nic.id as next_class_id'])
            ->first();

        return $row ? (array) $row : null;
    }

    // -------------------------------------------------------------------------
    // 45. StudentIdentities
    // -------------------------------------------------------------------------

    public function getStudentIdentities(array $params): array
    {
        if (!isset($params['student_id'])) {
            return [];
        }

        return DB::table('user_identities as ui')
            ->join('identity_types as it', 'it.id', '=', 'ui.identity_type_id')
            ->where('ui.security_user_id', $params['student_id'])
            ->select(['ui.*', 'it.name as identity_type_name'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 46. InstitutionStudentsReportCardGpaOld (legacy)
    // -------------------------------------------------------------------------

    public function getInstitutionStudentsReportCardGpaOld(array $params): ?array
    {
        if (!isset($params['student_id'], $params['education_grade_id'], $params['academic_period_id'], $params['institution_id'])) {
            return null;
        }

        $row = DB::table('institution_students_gpa')
            ->where([
                'student_id'               => $params['student_id'],
                'education_grade_id'       => $params['education_grade_id'],
                'academic_period_id'       => $params['academic_period_id'],
                'institution_id'           => $params['institution_id'],
            ])
            ->whereNotNull('education_grades_gpa_id')
            ->select('gpa')
            ->first();

        return $row ? (array) $row : null;
    }

    // -------------------------------------------------------------------------
    // 47. InstitutionStudentsReportCardGpa (POCOR-9196)
    // -------------------------------------------------------------------------

    public function getInstitutionStudentsReportCardGpa(array $params): ?array
    {
        if (!isset($params['student_id'], $params['education_grade_id'], $params['academic_period_id'], $params['institution_id'])
            || !$this->reportCardStartDate || !$this->reportCardEndDate) {
            return null;
        }

        $row = DB::table('institution_students_gpa as isg')
            ->join('education_grades_gpa as egg', function ($join) use ($params) {
                $join->on('egg.id', '=', 'isg.education_grades_gpa_id')
                    ->where('egg.academic_period_id', $params['academic_period_id'])
                    ->where('egg.education_grade_id', $params['education_grade_id'])
                    ->where('egg.start_date', '>=', $this->reportCardStartDate)
                    ->where('egg.end_date', '<=', $this->reportCardEndDate);
            })
            ->where([
                'isg.student_id'         => $params['student_id'],
                'isg.education_grade_id' => $params['education_grade_id'],
                'isg.academic_period_id' => $params['academic_period_id'],
                'isg.institution_id'     => $params['institution_id'],
            ])
            ->select('isg.gpa')
            ->first();

        return $row ? (array) $row : null;
    }

    // -------------------------------------------------------------------------
    // 48. InstitutionStudentGradeGpaOld (legacy)
    // -------------------------------------------------------------------------

    public function getInstitutionStudentGradeGpaOld(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'], $params['institution_id'])) {
            return [];
        }

        return DB::table('institution_students_gpa as isg')
            ->join('education_grades as eg', 'eg.id', '=', 'isg.education_grade_id')
            ->leftJoin('education_grades_gpa as egg', 'egg.id', '=', 'isg.education_grades_gpa_id')
            ->where([
                'isg.student_id'         => $params['student_id'],
                'isg.academic_period_id' => $params['academic_period_id'],
                'isg.institution_id'     => $params['institution_id'],
            ])
            ->whereNotNull('isg.education_grades_gpa_id')
            ->select(['isg.*', 'eg.name as education_grade_name', 'egg.name as gpa_grade_name'])
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // 49. InstitutionStudentGradeGpa (POCOR-9144)
    // -------------------------------------------------------------------------

    public function getInstitutionStudentGradeGpa(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'], $params['institution_id'])) {
            return [];
        }

        return DB::select("
            SELECT
                isg.*,
                eg.name AS education_grade_name,
                egg.name AS gpa_grade_name,
                gs.name AS grading_scale_name
            FROM institution_students_gpa isg
            JOIN education_grades eg ON eg.id = isg.education_grade_id
            LEFT JOIN (
                SELECT id, education_grade_id, academic_period_id, MAX(start_date) AS max_start_date
                FROM education_grades_gpa
                GROUP BY education_grade_id, academic_period_id
            ) latest_egg ON latest_egg.education_grade_id = isg.education_grade_id
                         AND latest_egg.academic_period_id = isg.academic_period_id
            LEFT JOIN education_grades_gpa egg ON egg.id = isg.education_grades_gpa_id
            LEFT JOIN grading_scales gs ON gs.id = egg.grading_scale_id
            WHERE isg.student_id = ?
              AND isg.academic_period_id = ?
              AND isg.institution_id = ?
        ", [
            $params['student_id'],
            $params['academic_period_id'],
            $params['institution_id'],
        ]);
    }

    // -------------------------------------------------------------------------
    // 50. ClassAndLevelRanking (POCOR-9232)
    // -------------------------------------------------------------------------

    public function getClassAndLevelRanking(array $params): array
    {
        if (!isset($params['student_id'], $params['academic_period_id'], $params['institution_id'], $params['institution_class_id'])) {
            return [];
        }

        $gradeId = $this->reportCardEducationGradeId ?? $params['education_grade_id'] ?? null;

        try {
            return DB::select("
                SELECT
                    ranked.*
                FROM (
                    SELECT
                        air.student_id,
                        ic.name AS class_name,
                        eg.name AS grade_name,
                        i.name AS institution_name,
                        es.name AS subject_name,
                        SUM(air.marks) AS total_marks,
                        RANK() OVER (
                            PARTITION BY ic.name, es.name
                            ORDER BY SUM(air.marks) DESC
                        ) AS class_ranking,
                        RANK() OVER (
                            PARTITION BY i.name, eg.name, es.name
                            ORDER BY SUM(air.marks) DESC
                        ) AS level_ranking
                    FROM assessment_item_results air
                    JOIN assessment_items ai ON ai.id = air.assessment_item_id
                    JOIN assessments a ON a.id = ai.assessment_id
                    JOIN education_subjects es ON es.id = ai.education_subject_id
                    JOIN institution_class_students ics ON ics.student_id = air.student_id
                        AND ics.academic_period_id = a.academic_period_id
                    JOIN institution_classes ic ON ic.id = ics.institution_class_id
                    JOIN institutions i ON i.id = ic.institution_id
                    JOIN education_grades eg ON eg.id = a.education_grade_id
                    WHERE a.academic_period_id = ?
                      AND ic.institution_id = ?
                    " . ($gradeId ? " AND a.education_grade_id = " . (int) $gradeId : "") . "
                    GROUP BY air.student_id, ic.name, eg.name, i.name, es.name
                ) ranked
                WHERE ranked.student_id = ?
            ", [
                $params['academic_period_id'],
                $params['institution_id'],
                $params['student_id'],
            ]);
        } catch (\Throwable $e) {
            Log::warning("ClassAndLevelRanking failed: " . $e->getMessage());
            return [];
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getSecurityStaffByRoleName(int $institutionId, string $roleName): ?array
    {
        // Get security group IDs for this institution
        $securityGroupIds = DB::table('institution_security_group_users')
            ->where('institution_id', $institutionId)
            ->distinct()
            ->pluck('security_group_id')
            ->toArray();

        if (empty($securityGroupIds)) {
            return null;
        }

        // Find security role ID by name
        $roleId = DB::table('security_roles')
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($roleName) . '%'])
            ->value('id');

        if (!$roleId) {
            return null;
        }

        // Find staff position titles with this role
        $positionTitleIds = DB::table('staff_position_titles')
            ->whereRaw("FIND_IN_SET(?, security_role_ids)", [$roleId])
            ->pluck('id')
            ->toArray();

        if (empty($positionTitleIds)) {
            return null;
        }

        // Find staff member
        $row = DB::table('institution_staff as is')
            ->join('security_users as su', 'su.id', '=', 'is.staff_id')
            ->join('security_group_users as sgu', function ($join) use ($securityGroupIds) {
                $join->on('sgu.security_user_id', '=', 'is.staff_id')
                    ->whereIn('sgu.security_group_id', $securityGroupIds);
            })
            ->join('institution_positions as ip', function ($join) use ($positionTitleIds) {
                $join->on('ip.id', '=', 'is.institution_position_id')
                    ->whereIn('ip.staff_position_title_id', $positionTitleIds);
            })
            ->leftJoin('genders as g', 'g.id', '=', 'su.gender_id')
            ->where('is.institution_id', $institutionId)
            ->select([
                'su.first_name',
                'su.last_name',
                'su.preferred_name',
                'su.openemis_no',
                'g.name as gender_name',
            ])
            ->first();

        if (!$row) {
            return null;
        }

        $data = (array) $row;
        $data['user'] = [
            'name'           => trim($row->first_name . ' ' . $row->last_name),
            'preferred_name' => $row->preferred_name ?: '-',
        ];
        $data['gender'] = $data['gender_name'] ?? '-';
        $data['principal']        = $data['user']['name'];
        $data['principal_gender'] = $data['gender'];

        return $data;
    }

    private function getNextAcademicPeriodId(int $currentPeriodId): ?int
    {
        $current = DB::table('academic_periods')->where('id', $currentPeriodId)->first();
        if (!$current) {
            return null;
        }

        $next = DB::table('academic_periods')
            ->where('start_date', '>', $current->end_date)
            ->orderBy('start_date')
            ->value('id');

        return $next ? (int) $next : null;
    }
}
