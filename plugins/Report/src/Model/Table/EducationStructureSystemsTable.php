<?php
//POCOR-9835 Starts
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\ORM\Query;

/**
 * Reports > Education > Systems
 * One row per education grade and subject of the selected academic period's education systems.
 * Grades without subjects are still listed.
 */
class EducationStructureSystemsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('education_grades');
        parent::initialize($config);

        $this->addBehavior('Excel', ['autoFields' => false]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => __('Systems'),
            'table' => $this,
            'query' => $this->find(),
        ];
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id ?? null;
        if (empty($academicPeriodId)) {
            // a null value in where() throws in CakePHP 5 - return an empty report instead
            $query->where(['1 = 0']);
            return;
        }

        $query
            ->select([
                'academic_period_code' => 'AcademicPeriods.code',
                'academic_period_name' => 'AcademicPeriods.name',
                'education_system_name' => 'EducationSystems.name',
                'education_level_name' => 'EducationLevels.name',
                'education_level_isced' => 'EducationLevelIsced.name',
                'education_cycle_name' => 'EducationCycles.name',
                'admission_age' => $this->aliasField('admission_age'), //POCOR-9835: one row per grade, so use the grade's own admission age
                'education_programme_code' => 'EducationProgrammes.code',
                'education_programme_name' => 'EducationProgrammes.name',
                'education_field_of_study' => 'EducationFieldOfStudies.name',
                'education_programme_orientation' => 'EducationProgrammeOrientations.name',
                'education_certification' => 'EducationCertifications.name',
                'education_grade_code' => $this->aliasField('code'),
                'education_grade_name' => $this->aliasField('name'),
                'education_stage_code' => 'EducationStages.code',
                'education_stage_name' => 'EducationStages.name',
                'education_subject_code' => 'EducationSubjects.code',
                'education_subject_name' => 'EducationSubjects.name',
            ])
            //POCOR-9835: only the visible (active) part of the structure - hidden systems/levels/cycles/programmes/grades are left out
            ->innerJoin(['EducationProgrammes' => 'education_programmes'], ['EducationProgrammes.id = ' . $this->aliasField('education_programme_id'), 'EducationProgrammes.visible' => 1])
            ->innerJoin(['EducationCycles' => 'education_cycles'], ['EducationCycles.id = EducationProgrammes.education_cycle_id', 'EducationCycles.visible' => 1])
            ->innerJoin(['EducationLevels' => 'education_levels'], ['EducationLevels.id = EducationCycles.education_level_id', 'EducationLevels.visible' => 1])
            ->innerJoin(['EducationSystems' => 'education_systems'], ['EducationSystems.id = EducationLevels.education_system_id', 'EducationSystems.visible' => 1])
            ->innerJoin(['AcademicPeriods' => 'academic_periods'], ['AcademicPeriods.id = EducationSystems.academic_period_id'])
            ->leftJoin(['EducationLevelIsced' => 'education_level_isced'], ['EducationLevelIsced.id = EducationLevels.education_level_isced_id'])
            ->leftJoin(['EducationStages' => 'education_stages'], ['EducationStages.id = ' . $this->aliasField('education_stage_id')])
            ->leftJoin(['EducationCertifications' => 'education_certifications'], ['EducationCertifications.id = EducationProgrammes.education_certification_id'])
            ->leftJoin(['EducationFieldOfStudies' => 'education_field_of_studies'], ['EducationFieldOfStudies.id = EducationProgrammes.education_field_of_study_id'])
            //POCOR-9835: LEFT, not INNER - the field of study above is optional, an INNER join here dropped those programmes' grades
            ->leftJoin(['EducationProgrammeOrientations' => 'education_programme_orientations'], ['EducationProgrammeOrientations.id = EducationFieldOfStudies.education_programme_orientation_id'])
            //POCOR-9835: visible filter in the join condition so grades without (visible) subjects are still listed
            ->leftJoin(['EducationGradesSubjects' => 'education_grades_subjects'], ['EducationGradesSubjects.education_grade_id = ' . $this->aliasField('id'), 'EducationGradesSubjects.visible' => 1])
            ->leftJoin(['EducationSubjects' => 'education_subjects'], ['EducationSubjects.id = EducationGradesSubjects.education_subject_id', 'EducationSubjects.visible' => 1])
            ->where([
                'EducationSystems.academic_period_id' => $academicPeriodId,
                $this->aliasField('visible') => 1,
            ])
            ->order([
                'EducationSystems.order' => 'ASC',
                'EducationLevels.order' => 'ASC',
                'EducationCycles.order' => 'ASC',
                'EducationProgrammes.order' => 'ASC',
                $this->aliasField('order') => 'ASC',
                'EducationSubjects.order' => 'ASC',
            ]);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $columns = [
            'academic_period_code' => __('Academic Period Code'),
            'academic_period_name' => __('Academic Period Name'),
            'education_system_name' => __('Education System Name'),
            'education_level_name' => __('Education Level Name'),
            'education_level_isced' => __('ISCED'),
            'education_cycle_name' => __('Education Cycle Name'),
            'admission_age' => __('Admission Age'),
            'education_programme_code' => __('Education Programme Code'),
            'education_programme_name' => __('Education Programme Name'),
            'education_field_of_study' => __('Education Field of Study'),
            'education_programme_orientation' => __('Programme Orientation'),
            'education_certification' => __('Education Certification'),
            'education_grade_code' => __('Education Grade Code'),
            'education_grade_name' => __('Education Grade Name'),
            'education_stage_code' => __('Education Stages Code'),
            'education_stage_name' => __('Education Stages Name'),
            'education_subject_code' => __('Education Subject Code'),
            'education_subject_name' => __('Education Subject Name'),
        ];

        $newFields = [];
        foreach ($columns as $key => $label) {
            $newFields[] = [
                'key' => $key,
                'field' => $key,
                'type' => 'string',
                'label' => $label
            ];
        }
        $fields->exchangeArray($newFields);
    }
}
//POCOR-9835 Ends
