<?php

namespace Report\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class StaffRequirementsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institutions');

        parent::initialize($config);

        $this->addBehavior('Excel', [ 'excludes' => [], 'pages' => ['index'], ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        echo '<pre>';

        $academicPeriodId       = $requestData->academic_period_id;
        $areaLevelId            = $requestData->area_level_id;
        $areaId                 = $requestData->area_education_id;
        $institutionId          = $requestData->institution_id;
        $studentPerTeacherRatio = $requestData->student_per_teacher_ratio;
        $upperTolerance         = $requestData->upper_tolerance;
        $lowerTolerance         = $requestData->lower_tolerance;

        $academicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $academicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');

        $conditions = [];
        if ($academicPeriodId) {
            if ($this->aliasField('end_date')) {
                $conditions = [
                    $this->aliasField('start_date') . ' >=' => $startDate,
                    $this->aliasField('start_date') . ' <=' => $endDate
                ];
            } else {
                $conditions = [
                    $this->aliasField('start_date') . ' >=' => $startDate,
                    $this->aliasField('end_date') . ' <=' => $endDate
                ];
            }
        }

        if ($institutionId) {
            $conditions['Institutions.id'] = $institutionId;
        }

        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }

        // student query

        // get student subject
        $institutionStudents = TableRegistry::get('institution_students');
        $institutionStudentsData = $institutionStudents->find()
            ->select(['academic_period_id' => $institutionStudents->aliasField('academic_period_id')])
            ->group([$institutionStudents->aliasField('academic_period_id')]);

        // operational_academic_periods
        $operationalAcademicPeriods = $this->find()
            ->select([
                'current_academic_periods_id' => '@current_year_id := '. $academicPeriods->aliasField('id'),
                'curent_start_date' => '@current_start_year := '. $academicPeriods->aliasField('start_date')
            ])
            ->from(['operational_academic_periods' => $institutionStudentsData])
            ->innerJoin([$academicPeriods->alias() => $academicPeriods->table()], [
                $academicPeriods->aliasField('id') . ' = operational_academic_periods.academic_period_id'
            ])
            ->where([$academicPeriods->aliasField('current') => 1]);

        // operational_academic_periods_previous
        $operationalAcademicPeriodsPrevious = $this->find()
            ->select([
                'previous_academic_period_id' => '@previous_year_id := previous_current_join.academic_period_id',
                'curent_start_date' => '@current_start_year := '. $academicPeriods->aliasField('start_date')
            ])
            ->from(['operational_academic_periods' => $institutionStudentsData])
            ->innerJoin([$academicPeriods->alias() => $academicPeriods->table()], [
                $academicPeriods->aliasField('id') . ' = operational_academic_periods.academic_period_id'
            ])
            ->where([$academicPeriods->aliasField('current') => 1]);

        // operational_academic_periods_1
        $operationalAcademicPeriods1 = $this->find()
            ->select([
                'operational_academic_periods_1.academic_period_id',
                'previous_start_year' => '@previous_start_year := MAX('. $academicPeriods->aliasField('start_date') .')'
            ])
            ->from(['operational_academic_periods_1' => $institutionStudentsData])
            ->innerJoin([$academicPeriods->alias() => $academicPeriods->table()], [
                $academicPeriods->aliasField('id') . ' = operational_academic_periods_1.academic_period_id'
            ])
            ->leftJoin(['t' => $operationalAcademicPeriods], [
                't.current_academic_periods_id = @current_year_id'
            ])
            ->where([$academicPeriods->aliasField('start_date < @current_start_year')]);

        // operational_academic_periods_1 inner
        $operationalAcademicPeriodsInner = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_1.academic_period_id',
                'start_year' => $academicPeriods->aliasField('start_date')
            ])
            ->from(['operational_academic_periods_1' => $institutionStudentsData])
            ->innerJoin([$academicPeriods->alias() => $academicPeriods->table()], [
                $academicPeriods->aliasField('id') . ' = operational_academic_periods_1.academic_period_id'
            ])
            ->leftJoin(['t' => $operationalAcademicPeriods], [
                't.current_academic_periods_id = @current_year_id'
            ])
            ->where([$academicPeriods->aliasField('start_date < @current_start_year')]);

        // operational_academic_periods_2 inner
        $operationalAcademicPeriodsInner2 = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_2.academic_period_id',
                'start_year' => $academicPeriods->aliasField('start_date')
            ])
            ->from(['operational_academic_periods_2' => $institutionStudentsData])
            ->innerJoin([$academicPeriods->alias() => $academicPeriods->table()], [
                $academicPeriods->aliasField('id') . ' = operational_academic_periods_2.academic_period_id'
            ])
            ->leftJoin(['t_1' => $operationalAcademicPeriods], [
                't_1.previous_academic_period_id = @previous_year_id'
            ])
            ->where([$academicPeriods->aliasField('start_date < @previous_start_year')]);

        $previousCurrentJoin = $this->find()
            ->select([
                'previous_academic_period_id' => '@previous_year_id := previous_current_join.academic_period_id',
                'previous_start_year' => '@previous_start_year'
            ])
            ->from(['subq' => $operationalAcademicPeriods1])
            ->innerJoin(['previous_current_join' => $operationalAcademicPeriodsInner], [
                'previous_current_join.start_year = @previous_start_year'
            ]);

        // operational_academic_periods_2
        $operationalAcademicPeriods2 = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_2.academic_period_id',
                'previous_previous_start_year' => '@previous_previous_start_year := MAX('. $academicPeriods->aliasField('start_date') .')'
            ])
            ->from(['operational_academic_periods_2' => $institutionStudentsData])
            ->innerJoin([$academicPeriods->alias() => $academicPeriods->table()], [
                $academicPeriods->aliasField('id') . ' = operational_academic_periods_2.academic_period_id'
            ])
            ->leftJoin(['t_1' => $previousCurrentJoin], [
                't_1.previous_academic_period_id = @previous_year_id'
            ])
            ->where([$academicPeriods->aliasField('start_date < @previous_start_year')]);

        // previous_current_join_2
        $previousCurrentJoin2 = $this->find()
            ->select([
                'previous_previous_year_id' => '@previous_previous_year_id := previous_current_join_2.academic_period_id',
                'previous_previous_start_year' => '@previous_previous_start_year'
            ])
            ->from(['subq2' => $operationalAcademicPeriods2])
            ->innerJoin(['previous_current_join_2' => $operationalAcademicPeriodsInner], [
                'previous_current_join_2.start_year = @previous_previous_start_year'
            ]);


        
        // get institution_staff data
        $staffTable = TableRegistry::get('Institution.InstitutionStaff');
        $staffTableData = $staffTable->find()->group([$staffTable->aliasField('staff_id')]);

        // get data institution subject staff
        $institutionSubjectStaffTable = TableRegistry::get('Institution.InstitutionSubjectStaff');
        $institutionSubjectsTable = TableRegistry::get('Institution.InstitutionSubjects');
        $educationStagesTable = TableRegistry::get('EducationGrades.EducationStages');
        $educationGrades = TableRegistry::get('Education.EducationGrades');

        $institutionSubjectStaffTableData = $institutionSubjectStaffTable->find()
            ->select([
                'staff_id' => $institutionSubjectStaffTable->aliasField('staff_id'),
                'education_subject_id' => $institutionSubjectsTable->aliasField('education_subject_id'),
                'education_stages_id' => $educationStagesTable->aliasField('id'),
                'institution_id' => $institutionSubjectStaffTable->aliasField('institution_id'),
                'academic_period_id' => $institutionSubjectsTable->aliasField('academic_period_id'),
            ])
            ->innerJoin([$institutionSubjectsTable->alias() => $institutionSubjectsTable->table()], [
                $institutionSubjectsTable->aliasField('id') . ' = ' . $institutionSubjectStaffTable->aliasField('institution_subject_id')
            ])
            ->innerJoin(['inst_staff' => $staffTableData], [
                'inst_staff.InstitutionStaff__staff_id = ' . $institutionSubjectStaffTable->aliasField('staff_id')
            ])
            ->innerJoin([$educationGrades->alias() => $educationGrades->table()], [
                $educationGrades->aliasField('id') . ' = ' . $institutionSubjectsTable->aliasField('education_grade_id')
            ])
            ->innerJoin([$educationStagesTable->alias() => $educationStagesTable->table()], [
                $educationStagesTable->aliasField('id') . ' = ' . $educationGrades->aliasField('education_stage_id')
            ])
            ->group([
                $institutionSubjectStaffTable->aliasField('staff_id'),
                $institutionSubjectsTable->aliasField('education_subject_id'),
                $educationStagesTable->aliasField('id'),
                $institutionSubjectStaffTable->aliasField('institution_id'),
                $institutionSubjectsTable->aliasField('academic_period_id'),
            ]);

        // staff_subjects
        $staffSubjectsTbl = $this->find()
            ->select([
                'staff_subjects.institution_id',
                'staff_subjects.education_stages_id',
                'staff_subjects.education_subject_id',
                '2018_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2019_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2020_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2021_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2022_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @current_year_id THEN 1 ELSE 0.0000000000001 END)',
            ])
            ->from(['staff_subjects' => $institutionSubjectStaffTableData])
            ->group(['staff_subjects.education_subject_id', 'staff_subjects.education_stages_id', 'staff_subjects.institution_id']);

        echo $staffSubjectsTbl->sql(); exit;
            /*->innerJoin(['DuplicateStudents' => $duplicateStudentSubquery], [
                'DuplicateStudents.first_name = ' . $this->aliasField('first_name'),
                'DuplicateStudents.last_name = ' . $this->aliasField('last_name'),
                'DuplicateStudents.gender_id = ' . $this->aliasField('gender_id'),
                'DuplicateStudents.date_of_birth = ' . $this->aliasField('date_of_birth')
            ]);*/

        /*$instStaff = $staffTable->find()->select([
            'institution_position_id' => $staffTable->aliasField('institution_position_id'),
            'status_id' => $institutionPositionsTable->aliasField('status_id')
        ])->innerJoin([$institutionPositionsTable->alias() => $institutionPositionsTable->table()], [
            $institutionPositionsTable->aliasField('id = ') . $staffTable->aliasField('institution_position_id'),
        ]);*/


        exit;

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {

                return $row;
            });
        });
    }
}
