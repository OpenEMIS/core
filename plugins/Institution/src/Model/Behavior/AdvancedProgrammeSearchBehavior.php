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
        if (isset($advancedSearchHasMany['education_programmes'])) {
            $search = $advancedSearchHasMany['education_programmes'];
        } else {
            $search = '';
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
                ->where([
                    'EducationGrades.education_programme_id = ' . $search
                ])
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
    }

    public function getProgrammesOptions()
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $programmeOptions = [];

        $query = $InstitutionGrades
                ->find('all')
                ->select([
                    'id' => 'EducationProgrammes.id',
                    'name' => 'EducationProgrammes.name'
                ])
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
                ->group('EducationProgrammes.id')
                ->toArray();

        foreach ($query as $key => $value) {
            $programmeOptions[$value->id] = $value->name;
        }

        return $programmeOptions;
    }
}
