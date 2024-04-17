<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AdvancedProgrammeSearchBehavior extends Behavior
{
    protected $_defaultConfig = [
        'associatedKey' => '',
    ];

    public function initialize(array $config)
    {
        $associatedKey = $this->config('associatedKey');
        if (empty($associatedKey)) {
            $this->config('associatedKey', $this->_table->aliasField('id'));
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'AdvanceSearch.onSetupFormField' => 'onSetupFormField',
            'AdvanceSearch.onBuildQuery' => 'onBuildQuery',
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onBuildQuery(Event $event, Query $query, $advancedSearchHasMany)
    {
//        echo "<pre>"; print_r($query);
//        die;
//        echo "<pre>"; print_r($advancedSearchHasMany);
//        die;
        $where = [];

        if (isset($advancedSearchHasMany['education_programmes'])) {
            $where[] = ['EducationProgrammes.id = ' . $advancedSearchHasMany['education_programmes']];
        }
        if (isset($advancedSearchHasMany['education_levels'])) {
            $where[] = ['EducationLevels.id = ' . $advancedSearchHasMany['education_levels']];
        }
        if (isset($advancedSearchHasMany['education_systems'])) {
            $where[] = ['EducationSystems.id = ' . $advancedSearchHasMany['education_systems']];
        }

        if (!empty($search)) {
            $query->find('all')
                ->join([
                    'InstitutionGrades' => [
                        'table' => 'institution_grades',
                        'conditions' => [
                            'InstitutionGrades.institution_id = '.$this->_table->aliasField('id')
                        ]
                    ],
                    'EducationGrades' => [
                        'table' => 'education_grades',
                        'conditions' => [
                            'EducationGrades.id = InstitutionGrades.education_grade_id'
                        ]
                    ]
                ])
                ->where($where)
                ->group([
                    $this->_table->aliasField('id'),
                    'EducationGrades.education_programme_id'
                ]);
                //pr($query);die;
        }
        return $query;
    }

    public function onSetupFormField(Event $event, ArrayObject $searchables, $advanceSearchModelData)
    {
        $searchables['education_programmes'] = [
            'label' => __('Education Programme'),
            'type' => 'select',
            'options' => $this->getProgrammesOptions(),
            'selected' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['education_programmes'])) ? $advanceSearchModelData['hasMany']['education_programmes'] : '',
        ];
        $searchables['education_systems'] = [
            'label' => __('Education Systems'),
            'type' => 'select',
            'options' => $this->getProgrammesOptions(),
            'selected' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['education_systems'])) ? $advanceSearchModelData['hasMany']['education_systems'] : '',
        ];
        $searchables['education_levels'] = [
            'label' => __('Education Levels'),
            'type' => 'select',
            'options' => $this->getProgrammesOptions(),
            'selected' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['education_levels'])) ? $advanceSearchModelData['hasMany']['education_levels'] : '',
        ];
    }

    public function getProgrammesOptions()
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $AcademicPeriod->getCurrent();
        $programmeOptions = [];

        $query = $InstitutionGrades
                ->find('all')
                ->select([
                    'education_program_id' => 'EducationProgrammes.id',
                    'education_program_name' => 'EducationProgrammes.name',
                    'education_level_id' => 'EducationLevels.id',
                    'education_level_name' => 'EducationLevels.name',
                    'education_system_id' => 'EducationSystems.id',
                    'education_system_name' => 'EducationSystems.name',
                    'academic_period_name' => 'AcademicPeriods.name',
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems.AcademicPeriods'])//POCOR-6803
                ->join([
                    'EducationGrades' => [
                        'table' => 'education_grades',
                        'conditions' => [
                            'EducationGrades.id = '.$InstitutionGrades->aliasField('education_grade_id')
                        ]
                    ],
                    'EducationProgrammes' => [
                        'table' => 'education_programmes',
                        'conditions' => [
                        'EducationProgrammes.id = EducationGrades.education_programme_id'
                        ]
                    ]
                ])
//                ->where(['EducationSystems.academic_period_id' => $academicPeriodId]) //POCOR-6803
                ->group('EducationProgrammes.id')
                ->order([
                    'EducationLevels.order' => 'ASC',
                    'EducationCycles.order' => 'ASC',
                    'EducationProgrammes.order' => 'ASC',
                    'EducationGrades.order' => 'ASC'
                ]) //POCOR-8165 - Update order by fields for sorting
                ->toArray();

        foreach ($query as $key => $value) {
            $value['education_system_name'] = __($value['education_system_name']) . ': ' . $value['academic_period_name'];
            $value['education_level_name'] = __($value['education_level_name']) . ': ' . $value['academic_period_name'];
            $value['education_program_name'] = __($value['education_program_name'] . ': ' . $value['academic_period_name']);
            $programmeOptions[$key] = $value;
        }

        return $programmeOptions;
    }
}
