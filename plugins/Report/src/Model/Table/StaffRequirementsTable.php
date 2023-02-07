<?php

namespace Report\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\ConnectionManager;
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

        $academicPeriodId       = $requestData->academic_period_id;
        $areaLevelId            = $requestData->area_level_id;
        $areaId                 = $requestData->area_education_id;
        $institutionId          = $requestData->institution_id;
        $studentPerTeacherRatio = $requestData->student_per_teacher_ratio;
        $upperTolerance         = $requestData->upper_tolerance;
        $lowerTolerance         = $requestData->lower_tolerance;

        $academicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        // get institution_staff data
        $staffTable = TableRegistry::get('Institution.InstitutionStaff');
        $staffTableData = $staffTable->find()->group([$staffTable->aliasField('staff_id')]);

        // get data institution subject staff
        $institutionSubjectStaffTable = TableRegistry::get('Institution.InstitutionSubjectStaff');
        $institutionSubjectsTable = TableRegistry::get('Institution.InstitutionSubjects');
        $educationStagesTable = TableRegistry::get('EducationGrades.EducationStages');
        $educationGrades = TableRegistry::get('Education.EducationGrades');

        $institution = TableRegistry::get('Institutions');
        $area = TableRegistry::get('areas');
        $areaLevels = TableRegistry::get('area_levels');
        $educationSubjects = TableRegistry::get('education_subjects');

        $institutionSubjectStudents = TableRegistry::get('institution_subject_students');

        $operationalAcademicPeriods = $this->studentSubject();

        /*$periodEntity = $academicPeriods->get($academicPeriodId);
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
        }*/



        $oapt2 = $this->find()
            ->select([
                'current_academic_periods_id' => '@current_year_id := academic_periods.id',
                'curent_start_date' => '@current_start_year := academic_periods.start_date',
            ])
            ->from(['operational_academic_periods' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods.academic_period_id'
            ])
            ->where(['academic_periods.current = 1']);

        //$previous_current_join111
        $previous_current_join111 = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_1.academic_period_id',
                'start_year' => 'academic_periods.start_date',
            ])
            ->from(['operational_academic_periods_1' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_1.academic_period_id'
            ])
            ->leftJoin(['t' => $oapt2], [
                't.current_academic_periods_id = @current_year_id'
            ])
            ->where(['academic_periods.start_date < @current_start_year']);

        //subq_t_1
        $subq_t_1 = $this->find()
            ->select([
                'operational_academic_periods_1.academic_period_id',
                'previous_start_year' => '@previous_start_year := MAX(academic_periods.start_date)',
            ])
            ->from(['operational_academic_periods_1' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_1.academic_period_id'
            ])
            ->leftJoin(['t' => $oapt2], [
                't.current_academic_periods_id = @current_year_id'
            ])
            ->where(['academic_periods.start_date < @current_start_year']);

        $t_1 = $this->find()
            ->select([
                'previous_academic_period_id' => '@previous_year_id := previous_current_join.academic_period_id',
                'previous_start_year' => '@previous_start_year',
            ])
            ->from(['subq' => $subq_t_1])
            ->innerJoin(['previous_current_join' => $previous_current_join111], [
                'previous_current_join.start_year = @previous_start_year'
            ]);

        //previous_current_join_2
        $previousCurrentJoin2 = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_2.academic_period_id',
                'start_date' => 'academic_periods.start_date',
            ])
            ->from(['operational_academic_periods_2' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_2.academic_period_id'
            ])
            ->leftJoin(['t_1' => $t_1], [
                't_1.previous_academic_period_id = @previous_year_id'
            ])
            ->where(['academic_periods.start_date < @previous_start_year']);

        // $t_cap t21
        $t_cap = $this->find()
            ->select([
                'current_academic_periods_id' => '@current_year_id := academic_periods.id',
                'curent_start_date' => '@current_start_year := academic_periods.start_date',
            ])
            ->from(['operational_academic_periods' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods.academic_period_id'
            ])
            ->where(['academic_periods.current = 1']);

        // previous_current_join1
        $previous_current_join = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_1.academic_period_id',
                'start_year' => 'academic_periods.start_date',
            ])
            ->from(['operational_academic_periods_1' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_1.academic_period_id'
            ])
            ->leftJoin(['t' => $t_cap], [
                't.current_academic_periods_id = @current_year_id'
            ])
            ->where(['academic_periods.start_date < @current_start_year']);

        $t111 = $this->find()
            ->select([
                'current_academic_periods_id' => '@current_year_id := academic_periods.id',
                'curent_start_date' => '@current_start_year := academic_periods.start_date',
            ])
            ->from(['operational_academic_periods' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods.academic_period_id'
            ])
            ->where(['academic_periods.current = 1']);

        $subq = $this->find()
            ->select([
                'operational_academic_periods_1.academic_period_id',
                'previous_start_year' => '@previous_start_year := MAX(academic_periods.start_date)',
            ])
            ->from(['operational_academic_periods_1' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_1.academic_period_id'
            ])
            ->leftJoin(['t' => $t111], [
                't.current_academic_periods_id = @current_year_id'
            ])
            ->where(['academic_periods.start_date < @current_start_year']);

        $t_1 = $this->find()
            ->select([
                'previous_academic_period_id' => '@previous_year_id := previous_current_join.academic_period_id',
                'previous_start_year' => '@previous_start_year',
            ])
            ->from(['subq' => $subq])
            ->innerJoin(['previous_current_join' => $previous_current_join], [
                'previous_current_join.start_year = @previous_start_year'
            ]);

        $subq2 = $this->find()
            ->select([
                'operational_academic_periods_2.academic_period_id',
                'previous_previous_start_year' => '@previous_previous_start_year := MAX(academic_periods.start_date)'
            ])
            ->from(['operational_academic_periods_2' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_2.academic_period_id'
            ])
            ->leftJoin(['t_1' => $t_1], [
                't_1.previous_academic_period_id = @previous_year_id'
            ])
            ->where(['academic_periods.start_date < @previous_start_year']);

        $t_2 = $this->find()
            ->select([
                'previous_previous_year_id' => '@previous_previous_year_id := previous_current_join_2.academic_period_id',
                'previous_previous_start_year' => '@previous_previous_start_year',
            ])
            ->from(['subq2' => $subq2])
            ->innerJoin(['previous_current_join_2' => $previousCurrentJoin2], [
                'previous_current_join_2.start_date = @previous_previous_start_year'
            ]);

        // subq3_2times
        $subq3 = $this->find()
            ->select([
                'operational_academic_periods_3.academic_period_id',
                'previous_start_year' => '@previous_previous_previous_start_year := MAX(academic_periods.start_date)'
            ])
            ->from(['operational_academic_periods_3' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_3.academic_period_id'
            ])
            ->leftJoin(['t_2' => $t_2], [
                't_2.previous_previous_year_id = @previous_previous_year_id'
            ])
            ->where(['academic_periods.start_date < @previous_previous_start_year']);

        //left_join_t_2
        //previous_current_join_3
        $previousCurrentJoin3 = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_3.academic_period_id',
                'start_date' => 'academic_periods.start_date',
            ])
            ->from(['operational_academic_periods_3' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_3.academic_period_id'
            ])
            ->leftJoin(['t_2' => $t_2], [
                't_2.previous_previous_year_id = @previous_previous_year_id'
            ])
            ->where(['academic_periods.start_date < @previous_previous_start_year']);

        // subq4 t_3 t_3_2times
        $t3 = $this->find()
            ->select([
                'previous_previous_previous_year_id'    => '@previous_previous_previous_year_id := previous_current_join_3.academic_period_id',
                'previous_previous_previous_start_year' => '@previous_previous_previous_start_year'
            ])
            ->from(['subq3' => $subq3])
            ->innerJoin(['previous_current_join_3' => $previousCurrentJoin3], [
                'previous_current_join_3.start_date = @previous_previous_previous_start_year'
            ]);

        //previous_current_join_4_11
        $previousCurrentJoin4 = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_4.academic_period_id',
                'start_date' => 'academic_periods.start_date',
            ])
            ->from(['operational_academic_periods_4' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = operational_academic_periods_4.academic_period_id'
            ])
            ->leftJoin(['t_3' => $t3], [
                't_3.previous_previous_previous_year_id = @previous_previous_previous_year_id'
            ])
            ->where(['academic_periods.start_date < @previous_previous_previous_start_year']);

        // subq4_11 in $dynamicAcademicPeriods
        $subq4 = $this->find()
            ->select([
                'academic_period_id' => 'operational_academic_periods_4.academic_period_id',
                'previous_previous_previous_previous_start_year' => '@previous_previous_previous_previous_start_year := MAX(academic_periods.start_date)',
            ])
            ->from(['operational_academic_periods_4' => $operationalAcademicPeriods])
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
               'academic_periods.id = operational_academic_periods_4.academic_period_id'
            ])
            ->leftJoin(['t_3' => $t3], [
                't_3.previous_previous_previous_year_id = @previous_previous_previous_year_id'
            ])
            ->where(['academic_periods.start_date < @previous_previous_previous_start_year']);

        // dynamic academic periods
        $dynamicAcademicPeriods = $this->find()
            ->select([
                'previous_previous_previous_previous_year_id'   => '@previous_previous_previous_previous_year_id := previous_current_join_4.academic_period_id',
                'previous_previous_previous_year_id'            => '@previous_previous_previous_year_id',
                'previous_previous_year_id'                     => '@previous_previous_year_id',
                'previous_year_id'                              => '@previous_year_id',
                'current_year_id'                               => '@current_year_id',
            ])
            ->from(['subq4' => $subq4])
            ->innerJoin(['previous_current_join_4' => $previousCurrentJoin4], [
                'previous_current_join_4.start_date = @previous_previous_previous_previous_start_year'
            ]);

        // institute student subjects -- student_subjects
        $studentSubjects = $institutionSubjectStudents->find()
            ->select([
                'id'                    => $institutionSubjectStudents->aliasField('id'),
                'academic_period_id'    => $institutionSubjectStudents->aliasField('academic_period_id'),
                'student_id'            => $institutionSubjectStudents->aliasField('student_id'),
                'education_subject_id'  => $institutionSubjectStudents->aliasField('education_subject_id'),
                'education_grade_id'    => $institutionSubjectStudents->aliasField('education_grade_id'),
                'institution_id'        => $institutionSubjectStudents->aliasField('institution_id'),
            ])
            /*->innerJoin([$academicPeriods->alias() => $academicPeriods->table()], [
                $academicPeriods->aliasField('id') . ' = ' . $institutionSubjectStudents->aliasField('academic_period_id')
            ])*/
            ->innerJoin(['academic_periods' => $academicPeriods->table()], [
                'academic_periods.id = institution_subject_students.academic_period_id'
            ])
            ->leftJoin(['dynamic_academic_periods' => $dynamicAcademicPeriods], [
                'academic_periods.id = @current_year_id'
            ])
            ->where(['IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_subject_students.student_status_id = 1, institution_subject_students.student_status_id IN (1, 7, 6, 8))'])
            ->group([
                $institutionSubjectStudents->aliasField('student_id'),
                $institutionSubjectStudents->aliasField('education_subject_id'),
                $institutionSubjectStudents->aliasField('academic_period_id'),
            ]);

        // student_query
        $studentQuery = $this->find()
            ->select([
                'institutions_id'       => $institution->aliasField('id'),
                'education_stages_id'   => 'education_stages.id',
                'education_subjects_id' => $educationSubjects->aliasField('id'),
                'area_level_layer_four'     => "IFNULL(area_level_layer_four.name, '')",
                'area_layer_four_code'      => "IFNULL(area_layer_four.code, '')",
                'area_layer_four_name'      => "IFNULL(area_layer_four.name, '')",
                'area_level_layer_three'    => "IFNULL(area_level_layer_three.name, '')",
                'area_layer_three_code'     => "IFNULL(area_layer_three.code, '')",
                'area_layer_three_name'     => "IFNULL(area_layer_three.name, '')",
                'area_level_layer_two'      => "IFNULL(area_level_layer_two.name, '')",
                'area_layer_two_code'       => "IFNULL(area_layer_two.code, '')",
                'area_layer_two_name'       => "IFNULL(area_layer_two.name, '')",
                'area_level_layer_one'      => "IFNULL(area_level_layer_one.name, '')",
                'area_layer_one_code'   => 'area_layer_one.code',
                'area_layer_one_name'   => 'area_layer_one.name',
                'institutions_code' => $institution->aliasField('code'),
                'institutions_name' => $institution->aliasField('name'),
                'education_grades_code' => 'education_grades.code',
                'education_grades_name' => 'education_grades.name',
                //'calculation_type' => "",
                'education_subjects_code'   => 'education_subjects.code',
                'education_subjects_name'   => 'education_subjects.name',
                'Students_per_Teacher'  => '@Students_per_Teacher := 30',
                'Lower_Tolerance'       => '@Lower_Tolerance := 0.5',
                'Upper_Tolerance'       => '@Upper_Tolerance := 1.5',
                '2018_students' => 'SUM(CASE WHEN student_subjects.academic_period_id = @previous_previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2019_students' => 'SUM(CASE WHEN student_subjects.academic_period_id = @previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2020_students' => 'SUM(CASE WHEN student_subjects.academic_period_id = @previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2021_students' => 'SUM(CASE WHEN student_subjects.academic_period_id = @previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2022_students' => 'SUM(CASE WHEN student_subjects.academic_period_id = @current_year_id THEN 1 ELSE 0.0000000000001 END)',
            ])
            ->from(['student_subjects' => $studentSubjects])
            ->innerJoin([$institution->alias() => $institution->table()], [
                $institution->aliasField('id') . ' = student_subjects.institution_id'
            ])
            ->innerJoin(['area_layer_one' => $area->table()], [
                'area_layer_one.id = ' . $institution->aliasField('area_id')
            ])
            ->innerJoin(['area_level_layer_one' => $areaLevels->table()], [
                'area_level_layer_one.id = area_layer_one.area_level_id'
            ])
            ->leftJoin(['area_layer_two' => $area->table()], [
                'area_layer_two.id = area_layer_one.parent_id'
            ])
            ->leftJoin(['area_level_layer_two' => $areaLevels->table()], [
                'area_level_layer_two.id = area_layer_two.area_level_id'
            ])
            ->leftJoin(['area_layer_three' => $area->table()], [
                'area_layer_three.id = area_layer_two.parent_id'
            ])
            ->leftJoin(['area_level_layer_three' => $areaLevels->table()], [
                'area_level_layer_three.id = area_layer_three.area_level_id'
            ])
            ->leftJoin(['area_layer_four' => $area->table()], [
                'area_layer_four.id = area_layer_three.parent_id'
            ])
            ->leftJoin(['area_level_layer_four' => $areaLevels->table()], [
                'area_level_layer_four.id = area_layer_four.area_level_id'
            ])
            ->innerJoin(['education_grades' => $educationGrades->table()], [
                'education_grades.id = student_subjects.education_grade_id'
            ])
            ->innerJoin(['education_stages' => $educationStagesTable->table()], [
                'education_stages.id = education_grades.education_stage_id'
            ])
            ->innerJoin(['education_subjects' => $educationSubjects->table()], [
                'education_subjects.id = student_subjects.education_subject_id'
            ])
            ->group([
                'area_layer_one.id',
                'area_layer_two.id',
                'area_layer_three.id',
                'area_layer_four.id',
                $institution->aliasField('id'),
                'education_stages.id',
                'education_subjects.id',
            ]);


        //  staff_query
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
                'institution_id' => 'staff_subjects.institution_id',
                'education_stages_id' => 'staff_subjects.education_stages_id',
                'education_subject_id' => 'staff_subjects.education_subject_id',
                '2018_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2019_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2020_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2021_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @previous_year_id THEN 1 ELSE 0.0000000000001 END)',
                '2022_staff' => 'SUM(CASE WHEN staff_subjects.academic_period_id = @current_year_id THEN 1 ELSE 0.0000000000001 END)',
            ])
            ->from(['staff_subjects' => $institutionSubjectStaffTableData])
            ->group(['staff_subjects.education_subject_id', 'staff_subjects.education_stages_id', 'staff_subjects.institution_id']);

        // main query student and staff
        //$mainQuery = $this->find()
        $query
            ->select([
                'area_level1'    => 'student_query.area_level_layer_four',
                'area_code1'     => 'student_query.area_layer_four_code',
                'area_name1'     => 'student_query.area_layer_four_name',
                'area_level2'    => 'student_query.area_level_layer_three',
                'area_code2'     => 'student_query.area_layer_three_code',
                'area_name2'     => 'student_query.area_layer_three_name',
                'area_level3'    => 'student_query.area_level_layer_two',
                'area_code3'     => 'student_query.area_layer_two_code',
                'area_name3'     => 'student_query.area_layer_two_name',
                'area_level4'    => 'student_query.area_level_layer_one',
                'area_code4'     => 'student_query.area_layer_one_code',
                'area_name4'     => 'student_query.area_layer_one_name',
                'institution_code'                  => 'student_query.institutions_code',
                'institution_name'                  => 'student_query.institutions_name',
                'education_grade_code'              => 'student_query.education_grades_code',
                'education_grade_name'              => 'student_query.education_grades_name',
                'education_subject_code'            => 'student_query.education_subjects_code',
                'education_subjects_name'           => 'student_query.education_subjects_name',
                'students_per_teacher_benchmark'    => 'student_query.Students_per_Teacher',
                'historical_t4_students'         => 'ROUND(IF(student_query.2018_students < 1, 0, student_query.2018_students), 0)',
                'historical_t3_students'         => 'ROUND(IF(student_query.2019_students < 1, 0, student_query.2019_students), 0)',
                'historical_t2_students'         => 'ROUND(IF(student_query.2020_students < 1, 0, student_query.2020_students), 0)',
                'historical_t1_students'         => 'ROUND(IF(student_query.2021_students < 1, 0, student_query.2021_students), 0)',
                'current_t_year_students'         => 'ROUND(IF(student_query.2022_students < 1, 0, student_query.2022_students), 0)',
                'projected_t1_students'      => '@2023_students := IFNULL(ROUND(2022_students * ((IF(2022_students/2021_students > @Upper_Tolerance, @Upper_Tolerance, IF(2022_students/2021_students < @Lower_Tolerance, @Lower_Tolerance, 2022_students/2021_students)) + IF(2021_students/2020_students > @Upper_Tolerance, @Upper_Tolerance, IF(2021_students/2020_students < @Lower_Tolerance, @Lower_Tolerance, 2021_students/2020_students)) + IF(2020_students/2019_students > @Upper_Tolerance, @Upper_Tolerance, IF(2020_students/2019_students < @Lower_Tolerance, @Lower_Tolerance, 2020_students/2019_students)) + IF(2019_students/2018_students > @Upper_Tolerance, @Upper_Tolerance, IF(2019_students/2018_students < @Lower_Tolerance, @Lower_Tolerance, 2019_students/2018_students)))/4), 0), 0)',
                'projected_t2_students'      => '@2024_students := IFNULL(ROUND(@2023_students * ((IF(@2023_students/2022_students > @Upper_Tolerance, @Upper_Tolerance, IF(@2023_students/2022_students < @Lower_Tolerance, @Lower_Tolerance, @2023_students/2022_students)) + IF(2022_students/2021_students > @Upper_Tolerance, @Upper_Tolerance, IF(2022_students/2021_students < @Lower_Tolerance, @Lower_Tolerance, 2022_students/2021_students)) + IF(2021_students/2020_students > @Upper_Tolerance, @Upper_Tolerance, IF(2021_students/2020_students < @Lower_Tolerance, @Lower_Tolerance, 2021_students/2020_students)) + IF(2020_students/2019_students > @Upper_Tolerance, @Upper_Tolerance, IF(2020_students/2019_students < @Lower_Tolerance, @Lower_Tolerance, 2020_students/2019_students)))/4), 0), 0)',
                'projected_t3_students'      => '@2025_students := IFNULL(ROUND(@2024_students * ((IF(@2024_students/@2023_students > @Upper_Tolerance, @Upper_Tolerance, IF(@2024_students/@2023_students < @Lower_Tolerance, @Lower_Tolerance, @2024_students/@2023_students)) + IF(@2023_students/2022_students > @Upper_Tolerance, @Upper_Tolerance, IF(@2023_students/2022_students < @Lower_Tolerance, @Lower_Tolerance, @2023_students/2022_students)) + IF(2022_students/2021_students > @Upper_Tolerance, @Upper_Tolerance, IF(2022_students/2021_students < @Lower_Tolerance, @Lower_Tolerance, 2022_students/2021_students)) + IF(2021_students/2020_students > @Upper_Tolerance, @Upper_Tolerance, IF(2021_students/2020_students < @Lower_Tolerance, @Lower_Tolerance, 2021_students/2020_students)))/4), 0), 0)',
                'historical_t4_staff'        => 'ROUND(IF(IFNULL(student_query.2018_students, 0) < 1, 0, IFNULL(student_query.2018_students, 0)), 0)',
                'historical_t3_staff'        => 'ROUND(IF(IFNULL(student_query.2019_students, 0) < 1, 0, IFNULL(student_query.2019_students, 0)), 0)',
                'historical_t2_staff'        => 'ROUND(IF(IFNULL(student_query.2020_students, 0) < 1, 0, IFNULL(student_query.2020_students, 0)), 0)',
                'historical_t1_staff'        => 'ROUND(IF(IFNULL(student_query.2021_students, 0) < 1, 0, IFNULL(student_query.2021_students, 0)), 0)',
                'current_t_year_staff'        => 'ROUND(IF(IFNULL(student_query.2022_students, 0) < 1, 0, IFNULL(student_query.2022_students, 0)), 0)',
                'projected_t1_staff'     => '@2023_staff := IFNULL(ROUND(2022_staff * ((IF(2022_staff/2021_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2022_staff/2021_staff < @Lower_Tolerance, @Lower_Tolerance, 2022_staff/2021_staff)) + IF(2021_staff/2020_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2021_staff/2020_staff < @Lower_Tolerance, @Lower_Tolerance, 2021_staff/2020_staff)) + IF(2020_staff/2019_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2020_staff/2019_staff < @Lower_Tolerance, @Lower_Tolerance, 2020_staff/2019_staff)) + IF(2019_staff/2018_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2019_staff/2018_staff < @Lower_Tolerance, @Lower_Tolerance, 2019_staff/2018_staff)))/4), 0), 0)',
                'projected_t2_staff'     => '@2024_staff := IFNULL(ROUND(@2023_staff * ((IF(@2023_staff/2022_staff > @Upper_Tolerance, @Upper_Tolerance, IF(@2023_staff/2022_staff < @Lower_Tolerance, @Lower_Tolerance, @2023_staff/2022_staff)) + IF(2022_staff/2021_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2022_staff/2021_staff < @Lower_Tolerance, @Lower_Tolerance, 2022_staff/2021_staff)) + IF(2021_staff/2020_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2021_staff/2020_staff < @Lower_Tolerance, @Lower_Tolerance, 2021_staff/2020_staff)) + IF(2020_staff/2019_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2020_staff/2019_staff < @Lower_Tolerance, @Lower_Tolerance, 2020_staff/2019_staff)))/4), 0), 0)',
                'projected_t3_staff'     => '@2025_staff := IFNULL(ROUND(@2024_staff * ((IF(@2024_staff/@2023_staff > @Upper_Tolerance, @Upper_Tolerance, IF(@2024_staff/@2023_staff < @Lower_Tolerance, @Lower_Tolerance, @2024_staff/@2023_staff)) + IF(@2023_staff/2022_staff > @Upper_Tolerance, @Upper_Tolerance, IF(@2023_staff/2022_staff < @Lower_Tolerance, @Lower_Tolerance, @2023_staff/2022_staff)) + IF(2022_staff/2021_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2022_staff/2021_staff < @Lower_Tolerance, @Lower_Tolerance, 2022_staff/2021_staff)) + IF(2021_staff/2020_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2021_staff/2020_staff < @Lower_Tolerance, @Lower_Tolerance, 2021_staff/2020_staff)))/4), 0), 0)',
                'required_staff_t1'    => "IF(@2023_staff = 0 AND @2023_students = 0, '0', IF(@2023_staff = 0 AND @2023_students != 0, CONCAT('+', CEILING(@2023_students/@Students_per_Teacher)), IF(@2023_students / @2023_staff = @Students_per_Teacher, '0', IF(@2023_students / @2023_staff > @Students_per_Teacher AND (@2023_staff - @2023_students/@Students_per_Teacher) < 0, CONCAT('+', CEILING((@2023_staff - @2023_students/@Students_per_Teacher)*(-1))), IF(@2023_students / @2023_staff > @Students_per_Teacher, CONCAT('+', CEILING(@2023_staff - @2023_students/@Students_per_Teacher)), IF(FLOOR(@2023_staff - @2023_students/@Students_per_Teacher) = 0, '0', CONCAT('-', FLOOR(@2023_staff - @2023_students/@Students_per_Teacher))))))))",
                'required_staff_t2'    => "IF(@2024_staff = 0 AND @2024_students = 0, '0', IF(@2024_staff = 0 AND @2024_students != 0, CONCAT('+', CEILING(@2024_students/@Students_per_Teacher)), IF(@2024_students / @2024_staff = @Students_per_Teacher, '0', IF(@2024_students / @2024_staff > @Students_per_Teacher AND (@2024_staff - @2024_students/@Students_per_Teacher) < 0, CONCAT('+', CEILING((@2024_staff - @2024_students/@Students_per_Teacher)*(-1))), IF(@2024_students / @2024_staff > @Students_per_Teacher, CONCAT('+', CEILING(@2024_staff - @2024_students/@Students_per_Teacher)), IF(FLOOR(@2024_staff - @2024_students/@Students_per_Teacher) = 0, '0', CONCAT('-', FLOOR(@2024_staff - @2024_students/@Students_per_Teacher))))))))",
                'required_staff_t3'    => "IF(@2025_staff = 0 AND @2025_students = 0, '0', IF(@2025_staff = 0 AND @2025_students != 0, CONCAT('+', CEILING(@2025_students/@Students_per_Teacher)), IF(@2025_students / @2025_staff = @Students_per_Teacher, '0', IF(@2025_students / @2025_staff > @Students_per_Teacher AND (@2025_staff - @2025_students/@Students_per_Teacher) < 0, CONCAT('+', CEILING((@2025_staff - @2025_students/@Students_per_Teacher)*(-1))), IF(@2025_students / @2025_staff > @Students_per_Teacher, CONCAT('+', CEILING(@2025_staff - @2025_students/@Students_per_Teacher)), IF(FLOOR(@2025_staff - @2025_students/@Students_per_Teacher) = 0, '0', CONCAT('-', FLOOR(@2025_staff - @2025_students/@Students_per_Teacher))))))))",
            ])
            ->from(['student_query' => $studentQuery])
            ->leftJoin(['staff_query' => $staffSubjectsTbl], [
                'staff_query.institution_id = student_query.institutions_id',
                'staff_query.education_stages_id = student_query.education_stages_id',
                'staff_query.education_subject_id = student_query.education_subjects_id'
            ]);


        //$connection = ConnectionManager::get('default');
        //$results = $connection->execute($mainQuery)->fetchAll('assoc');

        echo '<pre>';
        print_r($query->sql()); exit;


        /*$query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                //$row['userIdentityTypes'] =[];

                echo '<pre>'; print_r($row); exit;
                return $row;
            });
        });*/

    }

    private function studentSubject()
    {
        /*SELECT institution_students.academic_period_id FROM institution_students
            GROUP BY institution_students.academic_period_id*/

        // get student subject
        $institutionStudents = TableRegistry::get('institution_students');
        return $institutionStudents->find()
            ->select(['academic_period_id' => $institutionStudents->aliasField('academic_period_id')])
            ->group([$institutionStudents->aliasField('academic_period_id')]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        /*$newFields[] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];*/

        $newFields[] = [
            'key' => 'area_level1',
            'field' => 'area_level1',
            'type' => 'string',
            'label' => __('Area Level')
        ];

        $newFields[] = [
            'key' => 'area_code1',
            'field' => 'area_code1',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'area_name1',
            'field' => 'area_name1',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'area_level2',
            'field' => 'area_level2',
            'type' => 'string',
            'label' => __('Area Level')
        ];

        $newFields[] = [
            'key' => 'area_name2',
            'field' => 'area_name2',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'area_level3',
            'field' => 'area_level3',
            'type' => 'string',
            'label' => __('Area Level')
        ];


        $newFields[] = [
            'key' => 'area_code3',
            'field' => 'area_code3',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'area_name3',
            'field' => 'area_name3',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'area_level4',
            'field' => 'area_level4',
            'type' => 'string',
            'label' => __('Area Level')
        ];

        $newFields[] = [
            'key' => 'area_code4',
            'field' => 'area_code4',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'area_name4',
            'field' => 'area_name4',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'education_grade_code',
            'field' => 'education_grade_code',
            'type' => 'string',
            'label' => __('Education Grade Code')
        ];

        $newFields[] = [
            'key' => 'education_grade_name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade Name')
        ];

        $newFields[] = [
            'key' => 'education_subject_code',
            'field' => 'education_subject_code',
            'type' => 'string',
            'label' => __('Education Subject Code')
        ];

        $newFields[] = [
            'key' => 'education_Subjects_name',
            'field' => 'education_Subjects_name',
            'type' => 'string',
            'label' => __('Education Subject Name')
        ];

        $newFields[] = [
            'key' => 'students_per_teacher_benchmark',
            'field' => 'students_per_teacher_benchmark',
            'type' => 'string',
            'label' => __('Students Per Teacher Benchmark')
        ];

        $newFields[] = [
            'key' => 'historical_t4_students',
            'field' => 'historical_t4_students',
            'type' => 'string',
            'label' => __('Historical (T-4) Students')
        ];

        $newFields[] = [
            'key' => 'historical_t3_students',
            'field' => 'historical_t3_students',
            'type' => 'string',
            'label' => __('Historical (T-3) Students')
        ];

        $newFields[] = [
            'key' => 'historical_t2_students',
            'field' => 'historical_t2_students',
            'type' => 'string',
            'label' => __('Historical (T-2) Students')
        ];

        $newFields[] = [
            'key' => 'historical_t1_students',
            'field' => 'historical_t1_students',
            'type' => 'string',
            'label' => __('Historical (T-1) Students')
        ];

        $newFields[] = [
            'key' => 'projected_t1_students',
            'field' => 'projected_t1_students',
            'type' => 'string',
            'label' => __('Projected (T+1) Students')
        ];

        $newFields[] = [
            'key' => 'projected_t2_students',
            'field' => 'projected_t2_students',
            'type' => 'string',
            'label' => __('Projected (T+2) Students')
        ];

        $newFields[] = [
            'key' => 'projected_t3_students',
            'field' => 'projected_t3_students',
            'type' => 'string',
            'label' => __('Projected (T+3) Students')
        ];

        $newFields[] = [
            'key' => 'historical_t4_staff',
            'field' => 'historical_t4_staff',
            'type' => 'string',
            'label' => __('Historical (T-4) Staff')
        ];

        $newFields[] = [
            'key' => 'historical_t3_staff',
            'field' => 'historical_t3_staff',
            'type' => 'string',
            'label' => __('Historical (T-3) Staff')
        ];

        $newFields[] = [
            'key' => 'historical_t2_staff',
            'field' => 'historical_t2_staff',
            'type' => 'string',
            'label' => __('Historical (T-2) Staff')
        ];

        $newFields[] = [
            'key' => 'historical_t1_staff',
            'field' => 'historical_t1_staff',
            'type' => 'string',
            'label' => __('Historical (T-1) Staff')
        ];

        $newFields[] = [
            'key' => 'current_t_year_staff',
            'field' => 'current_t_year_staff',
            'type' => 'string',
            'label' => __('Current (T) Year Staff')
        ];

        $newFields[] = [
            'key' => 'projected_t1_staff',
            'field' => 'projected_t1_staff',
            'type' => 'string',
            'label' => __('Projected (T+1) Staff')
        ];

        $newFields[] = [
            'key' => 'projected_t2_staff',
            'field' => 'projected_t2_staff',
            'type' => 'string',
            'label' => __('Projected (T+2) Staff')
        ];

        $newFields[] = [
            'key' => 'projected_t3_staff',
            'field' => 'projected_t3_staff',
            'type' => 'string',
            'label' => __('Projected (T+3) Staff')
        ];

        $newFields[] = [
            'key' => 'required_staff_t1',
            'field' => 'required_staff_t1',
            'type' => 'string',
            'label' => __('Required Staff T+1')
        ];

        $newFields[] = [
            'key' => 'required_staff_t2',
            'field' => 'required_staff_t2',
            'type' => 'string',
            'label' => __('Required Staff T+2')
        ];

        $newFields[] = [
            'key' => 'required_staff_t3',
            'field' => 'required_staff_t3',
            'type' => 'string',
            'label' => __('Required Staff T+3')
        ];

        $fields->exchangeArray($newFields);
    }
}
