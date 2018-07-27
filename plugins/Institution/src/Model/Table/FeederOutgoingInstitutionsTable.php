<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class FeederOutgoingInstitutionsTable  extends ControllerActionTable
{
    private $institutionId = null;

    public function initialize(array $config)
    {
        $this->table('feeders_institutions');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('FeederInstitutions', ['className' => 'Institution.FeederInstitutions', 'foreignKey' => 'feeder_institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Area.Areapicker');

        $this->toggle('edit', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('area_education_id')
            ->add('feeder_institution_id', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['academic_period_id', 'institution_id']]],
                'provider' => 'table'
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $this->institutionId = $session->read('Institution.Institutions.id');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(); //to show list of academic period for selection
        $extra['selectedAcademicPeriod'] = $this->getSelectedAcademicPeriod($this->request);
        $extra['elements']['control'] = [
            'name' => 'Institution.Feeders/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriod']
            ],
            'order' => 3
        ];

        $this->field('code');
        $this->field('recipient_institution');
        $this->field('area_education');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;

        $query->contain([
            'Institutions' => [
                'fields' => [
                    'name',
                    'code'
                ]
            ], 
            'Institutions.Areas' => [
                'fields' => [
                    'name'
                ]
            ]
        ]);

        $conditions = [];
        if ($extra->offsetExists('selectedAcademicPeriod')) {
            $selectedAcademicPeriod = $extra['selectedAcademicPeriod'];
            $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        }

        if (!empty($this->institutionId)) {
            $institutionId = $this->institutionId;
            $conditions[$this->aliasField('feeder_institution_id')] = $institutionId;
        }

        if (!empty($conditions)) {
            // to overwrite the previous where conditions auto added in Institution Controller
            $query->where($conditions, [], true);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('recipient_institution');
        $this->field('area_education');

        $this->setFieldOrder([
            'code',
            'recipient_institution',
            'area_education'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Institutions' => [
                'fields' => [
                    'name',
                    'code'
                ]
            ], 
            'Institutions.Areas' => [
                'fields' => [
                    'name'
                ]
            ]
        ]);
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity->academic_period_id = $this->AcademicPeriods->getCurrent();
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('education_grade_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('area_education_id', [
            'type' => 'areapicker',
            'source_model' => 'Area.Areas',
            'displayCountry' => false,
            'null' => false,
            'onchange' => true
        ]);
        $this->field('feeder_institution_id', [
            'type' => 'select',
            'entity' => $entity,
        ]);

        $this->setFieldOrder([
            'academic_period_id',
            'area_education_id',
            'feeder_institution_id'
        ]);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        return $this->processSave($entity, $data, $extra);
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution') && $entity->institution->has('code')) {
            $value = $entity->institution->code;
        }

        return $value;
    }

    public function onGetRecipientInstitution(Event $event, Entity $entity)
    {
        return $entity->institution->name;
    }

    public function onGetAreaEducation(Event $event, Entity $entity)
    {
        return $entity->institution->area->name;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action = 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList();

            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }

        return $attr;
    }

    public function onUpdateFieldFeederInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action = 'add') {
            $institutionList = [];
            $entity = $attr['entity'];
            if ($entity->has('area_education_id')) {
                $areaEducationId = $entity->area_education_id;

                $AreasTable = TableRegistry::get('Area.Areas');
                $areaEducationEntity = $AreasTable->get($areaEducationId);

                $institutionQuery = $this->Institutions
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name',
                        'groupField' => 'group'
                    ])
                    ->select([
                        'id',
                        'code',
                        'name',
                        'group' => 'Types.name'
                    ])
                    ->contain([
                        'Types' => [
                            'fields' => [
                                'name',
                                'order'
                            ]
                        ],
                        'Areas' => [
                            'fields' => [
                                'lft',
                                'rght'
                            ]
                        ]
                    ])
                    ->where([
                        'Areas.lft >=' => $areaEducationEntity->lft,
                        'Areas.rght <=' => $areaEducationEntity->rght
                    ])
                    ->order([
                        'Types.order' => 'ASC',
                        $this->Institutions->aliasField('code') => 'ASC',
                        $this->Institutions->aliasField('name') => 'ASC'
                    ]);

                $institutionList = $institutionQuery->toArray();
            }

            if (empty($institutionList)) {
                $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];

                $attr['type'] = 'select';
                $attr['options'] = $institutionOptions;
            } else {
                $institutionOptions = ['' => '-- '.__('Select').' --'] + $institutionList;

                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['options'] = $institutionOptions;
            }
        }

        return $attr;
    }

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';
        if (isset($request->query) && array_key_exists('period', $request->query)) {
            $selectedAcademicPeriod = $request->query['period'];
        } else {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    }

    private function processSave(Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $process = function ($model, $entity) use ($data) {
            $errors = $entity->errors();

            if (empty($errors)) {
                $requestData = $data[$model->alias()];

                $newData = $requestData;
                $newData['institution_id'] = $requestData['feeder_institution_id'];
                $newData['feeder_institution_id'] = $requestData['institution_id'];

                $newEntity = $model->newEntity($newData);
                return $model->save($newEntity);
            } else {
                return false;
            }
        };

        return $process;
    }
}
