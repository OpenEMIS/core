<?php

namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
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
        // POCOR-8219 redone
        $where = [];
        if (isset($advancedSearchHasMany['education_systems'])) {
            $education_system_id = self::extractNumericPart($advancedSearchHasMany['education_systems']);
            if ($education_system_id > 0) {
                $where[] = ['EducationSystems.id = ' . $education_system_id];
            }
        }
        if (isset($advancedSearchHasMany['education_programmes'])) {
            $education_program_id = self::extractNumericPart($advancedSearchHasMany['education_programmes']);
            if ($education_program_id > 0) {
                $where[] = ['EducationProgrammes.id = ' . $education_program_id];
            }
        }
        if (isset($advancedSearchHasMany['education_levels'])) {
            $education_level_id = self::extractNumericPart($advancedSearchHasMany['education_levels']);
            if ($education_level_id > 0) {
                $where[] = ['EducationLevels.id = ' . $education_level_id];
            }
        }

        if (!empty($where)) {
            $query->find('all')
                ->join([
                    'InstitutionGrades' => [
                        'table' => 'institution_grades',
                        'conditions' => [
                            'InstitutionGrades.institution_id = ' . $this->_table->aliasField('id')
                        ]
                    ],
                    'EducationGrades' => [
                        'table' => 'education_grades',
                        'conditions' => [
                            'EducationGrades.id = InstitutionGrades.education_grade_id'
                        ]
                    ],
                    'EducationProgrammes' => [
                        'table' => 'education_programmes',
                        'conditions' => [
                            'EducationProgrammes.id = EducationGrades.education_programme_id'
                        ]
                    ],
                    'EducationCycles' => [
                        'table' => 'education_cycles',
                        'conditions' => [
                            'EducationCycles.id = EducationProgrammes.education_cycle_id'
                        ]
                    ],
                    'EducationLevels' => [
                        'table' => 'education_levels',
                        'conditions' => [
                            'EducationLevels.id = EducationCycles.education_level_id'
                        ]
                    ],
                    'EducationSystems' => [
                        'table' => 'education_systems',
                        'conditions' => [
                            'EducationSystems.id = EducationLevels.education_system_id'
                        ]
                    ]
                ])
                ->where($where)
                ->group([
                    $this->_table->aliasField('id'),
                    'EducationGrades.education_programme_id'
                ]);

        }
        return $query;
    }

    private function extractNumericPart($variable)
    {
        // POCOR-8219
        // Check if the variable contains a colon
        if (strpos($variable, ':') !== false) {
            // Split the string by the colon (:)
            $parts = explode(':', $variable);

            // Extract the numeric part and convert it to an integer
            $number = intval($parts[1]);

            return $number;
        } else {
            // If the variable doesn't contain a colon, return null or false
            return intval($variable); // or return false;
        }
    }

    public function onSetupFormField(Event $event, ArrayObject $searchables, $advanceSearchModelData)
    {
        $searchables['education_programmes'] = [
            'label' => __('Education Programme'),
            'type' => 'select',
            'options' => $this->getProgrammesOptions(),
            'selected' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['education_programmes'])) ? $advanceSearchModelData['hasMany']['education_programmes'] : '',
        ];
        // POCOR-8219 start
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
        // POCOR-8219 end
    }

    public function getProgrammesOptions()
    {
        // POCOR-8219 redone

        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
//        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
//        $academicPeriodId = $AcademicPeriod->getCurrent();
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
                        'EducationGrades.id = ' . $InstitutionGrades->aliasField('education_grade_id')
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
