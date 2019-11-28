<?php
namespace Institution\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use Cake\ORM\ResultSet;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class InstitutionRoomsTable extends ControllerActionTable
{
    use OptionsTrait;
    const IN_USE = 1;
    const UPDATE_DETAILS = 1;    // In Use
    const END_OF_USAGE = 2;
    const CHANGE_IN_TYPE = 3;

    private $Levels = null;
    private $levelOptions = [];
    private $roomLevel = null;

    private $canUpdateDetails = true;
    private $currentAcademicPeriod = null;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('RoomStatuses', ['className' => 'Infrastructure.InfrastructureStatuses', 'foreignKey' => 'room_status_id']);
        $this->belongsTo('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'foreignKey' => 'institution_floor_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('RoomTypes', ['className' => 'Infrastructure.RoomTypes']);
        $this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
        $this->belongsTo('PreviousRooms', ['className' => 'Institution.InstitutionRooms', 'foreignKey' => 'previous_institution_room_id']);

        $this->belongsToMany('Subjects', [
            'className' => 'Institution.InstitutionSubjects',
            'joinTable' => 'institution_subjects_rooms',
            'foreignKey' => 'institution_room_id',
            'targetForeignKey' => 'institution_subject_id',
            'through' => 'Institution.InstitutionSubjectsRooms',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->addBehavior('CustomField.Record', [
            'fieldKey' => 'infrastructure_custom_field_id',
            'tableColumnKey' => null,
            'tableRowKey' => null,
            'fieldClass' => ['className' => 'Infrastructure.RoomCustomFields'],
            'formKey' => 'infrastructure_custom_form_id',
            'filterKey' => 'infrastructure_custom_filter_id',
            'formFieldClass' => ['className' => 'Infrastructure.RoomCustomFormsFields'],
            'formFilterClass' => ['className' => 'Infrastructure.RoomCustomFormsFilters'],
            'recordKey' => 'institution_room_id',
            'fieldValueClass' => ['className' => 'Infrastructure.RoomCustomFieldValues', 'foreignKey' => 'institution_room_id', 'dependent' => true],
            'tableCellClass' => null
        ]);
        $this->addBehavior('Institution.InfrastructureShift');

        $this->Levels = TableRegistry::get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $this->Levels->find('list')->toArray();
        $this->accessibilityOptions = $this->getSelectOptions('InstitutionAssets.accessibility');
        $this->accessibilityTooltip = $this->getMessage('InstitutionInfrastructures.accessibilityOption');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'SubjectStudents' => ['index'],
            'ScheduleTimetable' => ['index']
        ]);
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('code', [
                'ruleUnique' => [
                    'rule' => ['validateUnique', ['scope' => ['start_date', 'institution_id', 'academic_period_id']]],
                    'provider' => 'table'
                ]
            ])
            ->add('start_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ]
            ])
            ->add('end_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'start_date', true]
                ]
            ])
            ->add('new_start_date', [
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'start_date', false]
                ]
            ])
            ->requirePresence('new_room_type', function ($context) {
                if (array_key_exists('change_type', $context['data'])) {
                    $selectedEditType = $context['data']['change_type'];
                    if ($selectedEditType == self::CHANGE_IN_TYPE) {
                        return true;
                    }
                }

                return false;
            })
            ->requirePresence('new_start_date', function ($context) {
                if (array_key_exists('change_type', $context['data'])) {
                    $selectedEditType = $context['data']['change_type'];
                    if ($selectedEditType == self::CHANGE_IN_TYPE) {
                        return true;
                    }
                }

                return false;
            })
            ->notEmpty('room_type_id');        
    }

    public function validationSavingByAssociation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator;
    }

    public function findSubjectRoomOptions(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $institutionSubjectId = $options['institution_subject_id'];
        $classSubjectsTable = TableRegistry::get('Institution.InstitutionClassSubjects');
        $institution = $classSubjectsTable->find()->contain('InstitutionClasses.InstitutionShifts')->where([$classSubjectsTable->aliasField('institution_subject_id') => $institutionSubjectId])->first();
        return $query
            ->find('inUse', ['institution_id' => $institution->institution_class->institution_shift->institution_id, 'academic_period_id' => $academicPeriodId])
            ->contain(['RoomTypes'])
            ->where(['RoomTypes.classification' => 1]) // classification 1 is equal to Classroom, 0 is Non_Classroom
            ->order(['RoomTypes.order', $this->aliasField('code'), $this->aliasField('name')])
            ->formatResults(function ($results) {
                $returnArr = [];
                foreach ($results as $result) {
                    $returnArr[] = ['group' => $result->room_type->name, 'id' => $result->id, 'name' => $result->code_name];
                }
                return $returnArr;
            });
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.AcademicPeriods.afterSave'] = 'academicPeriodAfterSave';
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->has('change_type')) {
            $editType = $entity->change_type;
            $statuses = $this->RoomStatuses->find('list', ['keyField' => 'id', 'valueField' => 'code'])->toArray();
            $functionKey = Inflector::camelize(strtolower($statuses[$editType]));
            $functionName = "process$functionKey";

            if (method_exists($this, $functionName)) {
                $event->stopPropagation();
                $this->$functionName($entity);
            }
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // logic to copy custom fields (general only) where new room is created when change in room type
        $this->processCopy($entity);
    }

    public function onGetInfrastructureLevel(Event $event, Entity $entity)
    {
        return $this->levelOptions[$this->roomLevel];
    }

    public function onGetAccessibility(Event $event, Entity $entity)
    {
        return $this->accessibilityOptions[$entity->accessibility];
    }

    public function onGetSubjects(Event $event, Entity $entity)
    {
        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');

        if ($entity->has('subjects')) {
            $resultArray = [];

            foreach ($entity->subjects as $key => $obj) {
                $records = $InstitutionClassSubjects->find()
                    ->where([$InstitutionClassSubjects->aliasField('institution_subject_id') => $obj->id])
                    ->first();

                $className = $InstitutionClasses->get($records->institution_class_id)->name;

                $resultArray[] = $className . ' - ' . $obj->name;
            }

            if (!empty($resultArray)) {
                return implode(', ', $resultArray);
            }
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'institution_id') {
            return __('Owner');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        // unset edit_type so that will always default to Update Details
        foreach ($buttons as $action => $attr) {
            if (array_key_exists('url', $attr) && array_key_exists('edit_type', $attr['url'])) {
                unset($buttons[$action]['url']['edit_type']);
            }
        }

        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->Navigation->substituteCrumb(__('Institution Rooms'), __('Institution Rooms'));
    }

    public function onUpdateFieldInstitutionFloorId(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'hidden';
        if ($action == 'add') {
            $attr['value'] = $this->getQueryString('institution_floor_id');
        }
        return $attr;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->roomLevel = $this->Levels->getFieldByCode('ROOM', 'id');
        $this->setFieldOrder(['code', 'name', 'institution_id', 'infrastructure_level', 'room_type_id', 'room_status_id']);
        $this->field('institution_id');
        $this->field('infrastructure_level', ['after' => 'name']);
        $this->fields['institution_floor_id']['visible'] = false;
        $this->field('accessibility', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('start_date', ['visible' => false]);
        $this->field('start_year', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('end_year', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('infrastructure_condition_id', ['visible' => false]);
        $this->field('previous_institution_room_id', ['visible' => false]);

        $extra['elements']['toolbarElements'] = $this->addBreadcrumbElement();
        $extra['elements']['control'] = $this->addControlFilterElement();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // get the list of owner institution id
        $ownerInstitutionIds = $this->getOwnerInstitutionId();

        if (!empty($ownerInstitutionIds)) {
            $conditions = [];
            $conditions[$this->aliasField('institution_id IN ')] = $ownerInstitutionIds;
            $query->where($conditions, [], true);
        }

        // $floorRecord = $this->InstitutionFloors

        $parentId = $this->getQueryString('institution_floor_id');
        $parentRecord = $this->InstitutionFloors->get($parentId, ['contain' => 'InstitutionBuildings.InstitutionLands'])->toArray();
        if (isset($extra['toolbarButtons']['add'])) {
            if ($parentRecord['floor_status_id'] == SELF::END_OF_USAGE || $parentRecord['institution_building']['building_status_id'] == SELF::END_OF_USAGE
                || $parentRecord['institution_building']['institution_land']['land_status_id'] == SELF::END_OF_USAGE) {
                unset($extra['toolbarButtons']['add']);
            }
        }
        if (!is_null($parentId)) {
            $query->where([$this->aliasField('institution_floor_id') => $parentId]);
        } else {
            $query->where([$this->aliasField('institution_floor_id IS NULL')]);
        }

        // Academic Period
        list($periodOptions, $selectedPeriod) = array_values($this->getPeriodOptions());
        $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        // End

        // Room Types
        list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true]));
        if ($selectedType != '-1') {
            $query->where([$this->aliasField('room_type_id') => $selectedType]);
        }
        $this->controller->set(compact('typeOptions', 'selectedType'));
        // End

        // Room Statuses
        list($statusOptions, $selectedStatus) = array_values($this->getStatusOptions([
            'conditions' => [
                'code IN' => ['IN_USE', 'END_OF_USAGE']
            ],
            'withAll' => true
        ]));
        if ($selectedStatus != '-1') {
            $query->where([$this->aliasField('room_status_id') => $selectedStatus]);
        } else {
            // default show In Use and End Of Usage
            $query->matching('RoomStatuses', function ($q) {
                return $q->where([
                    'RoomStatuses.code IN' => ['IN_USE', 'END_OF_USAGE']
                ]);
            });
        }
        $this->controller->set(compact('statusOptions', 'selectedStatus'));
        // End

        $options['order'] = [
            $this->aliasField('code') => 'asc',
            $this->aliasField('name') => 'asc'
        ];
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $session = $this->request->session();

        $sessionKey = $this->registryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $this->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AcademicPeriods', 'RoomTypes', 'InfrastructureConditions', 'Subjects']);
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();

        $sessionKey = $this->registryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $this->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function editAfterQuery(Event $event, Entity $entity, ArrayObject $extra)
    {
        list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

        $session = $this->request->session();
        $sessionKey = $this->registryAlias() . '.warning';
        if (!$isEditable) {
            $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
            $endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

            if ($entity->room_status_id == $inUseId) {
                $session->write($sessionKey, $this->alias().'.in_use.restrictEdit');
            } elseif ($entity->room_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->alias().'.end_of_usage.restrictEdit');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        } else {
            $selectedEditType = $this->request->query('edit_type');
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                $today = new DateTime();
                $diff = date_diff($entity->start_date, $today);

                // Not allowed to change room type in the same day
                if ($diff->days == 0) {
                    $session->write($sessionKey, $this->alias().'.change_in_room_type.restrictEdit');

                    $url = $this->url('edit');
                    $url['edit_type'] = self::UPDATE_DETAILS;
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                }
            }
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

        $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

        if (!$isDeletable) {
            $session = $this->request->session();
            $sessionKey = $this->registryAlias() . '.warning';
            if ($entity->room_status_id == $inUseId) {
                $session->write($sessionKey, $this->alias().'.in_use.restrictDelete');
            } elseif ($entity->room_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->alias().'.end_of_usage.restrictDelete');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }

        $extra['excludedModels'] = [$this->CustomFieldValues->alias()];

        // check if the same room is copy from / copy to other academic period, then not allow user to delete
        $resultQuery = $this->find();
        $results = $resultQuery
            ->select([
                'academic_period_name' => 'AcademicPeriods.name',
                'count' => $resultQuery->func()->count($this->aliasField('id'))
            ])
            ->contain(['AcademicPeriods'])
            ->where([
                $this->aliasField('code') => $entity->code,
                $this->aliasField('room_status_id') => $inUseId,
                $this->aliasField('id <> ') => $entity->id
            ])
            ->group($this->aliasField('academic_period_id'))
            ->order([$this->aliasField('start_date')])
            ->all();

        if (!$results->isEmpty()) {
            $extra['excludedModels'][] = $this->Subjects->alias();

            foreach ($results as $obj) {
                $title = $this->alias() . ' - ' . $obj->academic_period_name;
                $extra['associatedRecords'][] = [
                    'model' => $title,
                    'count' => $obj->count
                ];
            }
        }
        // end
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarElements = $this->addBreadcrumbElement();
        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $toolbarElements = $this->addBreadcrumbElement();
        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $selectedEditType = $this->request->query('edit_type');
        if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_TYPE) {
            foreach ($this->fields as $field => $attr) {
                if ($this->startsWith($field, 'custom_') || $this->startsWith($field, 'section_')) {
                    $this->fields[$field]['visible'] = false;
                }
            }
        }
    }

    public function onUpdateFieldChangeType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view' || $action == 'add') {
            $attr['visible'] = false;
        } elseif ($action == 'edit') {
            $editTypeOptions = $this->getSelectOptions('InstitutionInfrastructure.change_types');
            $selectedEditType = $this->queryString('edit_type', $editTypeOptions);
            $this->advancedSelectOptions($editTypeOptions, $selectedEditType);
            $this->controller->set(compact('editTypeOptions'));

            if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_TYPE) {
                $this->canUpdateDetails = false;
            }

            $attr['type'] = 'element';
            $attr['element'] = 'Institution.Infrastructure/change_type';

            $this->controller->set(compact('editTypeOptions'));
        }

        return $attr;
    }

    public function onUpdateFieldRoomStatusId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } elseif ($action == 'add') {
            $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
            $attr['value'] = $inUseId;
        }

        return $attr;
    }

    // public function onUpdateFieldInstitutionInfrastructureId(Event $event, array $attr, $action, Request $request)
    // {
    //     if ($action == 'view') {
    //         $entity = $attr['entity'];

    //         $attr['type'] = 'hidden';
    //         $parentId = $entity->institution_infrastructure_id;
    //         if (!empty($parentId)) {
    //             $list = $this->Parents->findPath(['for' => $parentId, 'withLevels' => true]);
    //         } else {
    //             $list = [];
    //         }

    //         $field = 'institution_infrastructure_id';
    //         $after = $field;
    //         foreach ($list as $key => $infrastructure) {
    //             $this->field($field.$key, [
    //                 'type' => 'readonly',
    //                 'attr' => ['label' => $infrastructure->_matchingData['Levels']->name],
    //                 'value' => $infrastructure->code_name,
    //                 'after' => $after
    //             ]);
    //             $after = $field.$key;
    //         }
    //     } elseif ($action == 'add' || $action == 'edit') {
    //         $parentId = $this->request->query('parent');

    //         if (is_null($parentId)) {
    //             $attr['type'] = 'hidden';
    //             $attr['value'] = null;
    //         } else {
    //             $attr['type'] = 'readonly';
    //             $attr['value'] = $parentId;
    //             $attr['attr']['value'] = $this->Parents->getParentPath($parentId);
    //         }
    //     }

    //     return $attr;
    // }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();
            $this->currentAcademicPeriod = $this->AcademicPeriods->get($currentAcademicPeriodId);

            $attr['type'] = 'readonly';
            $attr['value'] = $currentAcademicPeriodId;
            $attr['attr']['value'] = $this->currentAcademicPeriod->name;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];
            $this->currentAcademicPeriod = $entity->academic_period;

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period->id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'index' || $action == 'view') {
            if (!empty($this->getOwnerInstitutionId())) {
                $attr['type'] = 'select';
            }
        }

        return $attr;
    }

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $parentId = $this->getQueryString('institution_floor_id');
            $autoGenerateCode = $this->getAutoGenerateCode($parentId);

            $attr['attr']['default'] = $autoGenerateCode;
            $attr['type'] = 'readonly';
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function onUpdateFieldName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $selectedEditType = $request->query('edit_type');
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'readonly';
            }
        }

        return $attr;
    }

    public function onUpdateFieldRoomTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $classificationOptions = $this->getSelectOptions('RoomTypes.classifications');
            $roomTypeOptions = $this->RoomTypes
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name',
                        'groupField' => function ($roomType) use ($classificationOptions) {
                            return $classificationOptions[$roomType->classification];
                        }
                    ])
                    ->find('visible')
                    ->order([
                        $this->RoomTypes->aliasField('classification') => 'ASC',
                        $this->RoomTypes->aliasField('order') => 'ASC'
                    ])
                    ->toArray();

            $attr['options'] = $roomTypeOptions;
            $attr['onChangeReload'] = 'changeRoomType';
        } elseif ($action == 'edit') {
            $selectedEditType = $request->query('edit_type');
            if ($selectedEditType == self::END_OF_USAGE) {
                $attr['type'] = 'hidden';
            } else {
                $entity = $attr['entity'];

                $attr['type'] = 'readonly';
                $attr['value'] = $entity->room_type->id;
                $attr['attr']['value'] = $entity->room_type->name;
            }
        }

        return $attr;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $startDate = $this->currentAcademicPeriod->start_date->format('d-m-Y');
            /* restrict Start Date from start until end of academic period
            $endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');
            */
            // temporary restrict until today until have better solution
            $today = new DateTime();
            $endDate = $today->format('d-m-Y');

            $attr['date_options']['startDate'] = $startDate;
            $attr['date_options']['endDate'] = $endDate;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->start_date->format('Y-m-d');
            $attr['attr']['value'] = $this->formatDate($entity->start_date);
        }

        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['visible'] = false;
        } elseif ($action == 'add') {
            $endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

            $attr['type'] = 'hidden';
            $attr['value'] = $endDate;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $selectedEditType = $request->query('edit_type');
            if ($selectedEditType == self::END_OF_USAGE) {
                /* restrict End Date from start date until end of academic period
                $startDate = $entity->start_date->format('d-m-Y');
                $endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

                $attr['date_options']['startDate'] = $startDate;
                $attr['date_options']['endDate'] = $endDate;
                */

                // temporary restrict to today until have better solution
                $today = new DateTime();

                $attr['type'] = 'readonly';
                $attr['value'] = $today->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($today);
            } else {
                $attr['type'] = 'hidden';
                $attr['value'] = $entity->end_date->format('Y-m-d');
            }
        }

        return $attr;
    }

    public function onUpdateFieldAccessibility(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit' || $action == 'add') {
            $attr['options'] = $this->accessibilityOptions;
            $attr['type'] = 'select';
            return $attr;
        }
    }

    public function onUpdateFieldInfrastructureConditionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $selectedEditType = $request->query('edit_type');
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldSubjects(Event $event, array $attr, $action, Request $request)
    {
        // POCOR-3849 Subjects field will only be shown if the room belongs to a room type of Classroom classification
        $entity = $attr['entity'];

        $visibility = false;
        if ($entity->has('room_type_id')) {
            $classificationTypeId = $this->RoomTypes->getClassificationTypes($entity->room_type_id);

            if ($classificationTypeId == 1) { // Classroom
                $visibility = true;
            }
        }

        $attr['visible'] = $visibility;
        // end POCOR-3849

        if ($action == 'add' || $action == 'edit') {
            $session = $request->session();

            if ($session->check('Institution.Institutions.id') && !is_null($this->currentAcademicPeriod)) {
                $institutionId = $session->read('Institution.Institutions.id');
                $academicPeriodId = $this->currentAcademicPeriod->id;

                $attr['options'] = $this->getSubjectOptions(['institution_id' => $institutionId, 'academic_period_id' => $academicPeriodId]);
            }

            if (!$this->canUpdateDetails) {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldNewRoomType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $selectedEditType = $request->query('edit_type');
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                $classificationOptions = $this->getSelectOptions('RoomTypes.classifications');
                $roomTypeOptions = $this->RoomTypes
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name',
                        'groupField' => function ($roomType) use ($classificationOptions) {
                            return $classificationOptions[$roomType->classification];
                        }
                    ])
                    ->find('visible')
                    ->where([
                        $this->RoomTypes->aliasField('id <>') => $entity->room_type_id
                    ])
                    ->toArray();

                $attr['visible'] = true;
                $attr['options'] = $roomTypeOptions;
                $attr['select'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldNewStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $selectedEditType = $request->query('edit_type');
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                /* restrict End Date from start date until end of academic period
                $startDateObj = $entity->start_date->copy();
                $startDateObj->addDay();

                $startDate = $startDateObj->format('d-m-Y');
                $endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

                $attr['visible'] = true;
                $attr['null'] = false;	// for asterisk to appear
                $attr['date_options']['startDate'] = $startDate;
                $attr['date_options']['endDate'] = $endDate;
                */

                // temporary restrict to today until have better solution
                $today = new DateTime();

                $attr['visible'] = true;
                $attr['null'] = false;    // for asterisk to appear
                $attr['type'] = 'readonly';
                $attr['value'] = $today->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($today);
            }
        }

        return $attr;
    }

    public function addEditOnChangeRoomType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['type']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('room_type_id', $request->data[$this->alias()])) {
                    $selectedType = $request->data[$this->alias()]['room_type_id'];
                    $request->query['type'] = $selectedType;
                }

                if (array_key_exists('custom_field_values', $request->data[$this->alias()])) {
                    unset($request->data[$this->alias()]['custom_field_values']);
                }
            }
        }
    }

    private function setupFields(Entity $entity)
    {
        $this->setFieldOrder([
            'change_type', 'academic_period_id', 'institution_id', 'code', 'name', 'room_type_id', 'room_status_id', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id', 'previous_institution_room_id', 'new_room_type', 'new_start_date'
        ]);

        $this->field('change_type');
        $this->field('room_status_id', ['type' => 'hidden']);
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('institution_id');
        $this->field('institution_floor_id');
        $this->field('code');
        $this->field('name');
        $this->field('room_type_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);
        $this->field('previous_institution_room_id', ['type' => 'hidden']);
        $this->field('infrastructure_condition_id', ['type' => 'select']);
        $this->field('subjects', [
            'type' => 'chosenSelect',
            'fieldNameKey' => 'subjects',
            'fieldName' => $this->alias() . '.subjects._ids',
            'placeholder' => $this->getMessage($this->aliasField('select_subject')),
            'valueWhenEmpty' => '<span>&lt;'.__('No Subject Allocated').'&gt;</span>',
            'entity' => $entity
        ]);
        $this->field('new_room_type', ['type' => 'select', 'visible' => false, 'entity' => $entity]);
        $this->field('new_start_date', ['type' => 'date', 'visible' => false, 'entity' => $entity]);

        $this->field('accessibility', [
            'type' => 'select',
            'attr' => [
                'label' => [
                    'text' => __('Accessibility') . ' <i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="bottom" uib-tooltip="' . __($this->accessibilityTooltip) . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                    'escape' => false,
                    'class' => 'tooltip-desc'
                ]
            ]
        ]);
    }

    private function getAutoGenerateCode($parentId)
    {
        $codePrefix = '';
        $lastSuffix = '00';
        $conditions = [];
        // has Parent then get the ID of the parent then followed by counter
        $parentData = $this->InstitutionFloors->find()
            ->where([
                $this->InstitutionFloors->aliasField($this->InstitutionFloors->primaryKey()) => $parentId
            ])
            ->first();

        $codePrefix = $parentData->code;

        // $conditions[] = $this->aliasField('code')." LIKE '" . $codePrefix . "%'";
        $lastRecord = $this->find()
            ->where([
                $this->aliasField('institution_floor_id') => $parentId,
                $this->aliasField('code')." LIKE '" . $codePrefix . "%'"
            ])
            ->order($this->aliasField('code DESC'))
            ->first();

        if (!empty($lastRecord)) {
            $lastSuffix = str_replace($codePrefix, "", $lastRecord->code);
        }

        $codeSuffix = intval($lastSuffix) + 1;

        // if 1 character prepend '0'
        $codeSuffix = (strlen($codeSuffix) == 1) ? '0'.$codeSuffix : $codeSuffix;
        $autoGenerateCode = $codePrefix . $codeSuffix;

        return $autoGenerateCode;
    }

    private function addBreadcrumbElement()
    {
        $entity = $this->InstitutionFloors->get($this->getQueryString('institution_floor_id'), ['contain' => ['InstitutionBuildings.InstitutionLands']]);
        $url = $this->url('index');
        if (isset($url[1])) {
            unset($url[1]);
        }
        $buildingUrl = $url;
        $buildingUrl['action'] = 'InstitutionBuildings';
        $buildingUrl = $this->setQueryString($buildingUrl, [
            'institution_land_id' => $entity->institution_building->institution_land->id,
            'institution_land_name' => $entity->institution_building->institution_land->code
        ]);

        $floorUrl = $url;
        $floorUrl['action'] = 'InstitutionFloors';
        $floorUrl = $this->setQueryString($floorUrl, [
            'institution_building_id' => $entity->institution_building->id,
            'institution_building_name' => $entity->institution_building->name
        ]);

        $crumbs[] = [
            'name' => $entity->institution_building->institution_land->code,
            'url' => $buildingUrl
        ];
        $crumbs[] = [
            'name' => $entity->institution_building->name,
            'url' => $floorUrl
        ];
        $crumbs[] = [
            'name' => $this->getQueryString('institution_floor_name')
        ];
        $toolbarElements = ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => compact('crumbs'), 'options' => [], 'order' => 1];

        return $toolbarElements;
    }

    private function addControlFilterElement()
    {
        $toolbarElements = ['name' => 'Institution.Infrastructure/controls', 'data' => compact('typeOptions', 'selectedType'), 'options' => [], 'order' => 2];

        return $toolbarElements;
    }

    private function checkIfCanEditOrDelete($entity)
    {
        $isEditable = true;
        $isDeletable = true;

        $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

        if ($entity->room_status_id == $inUseId) {
        // If is in use, not allow to delete if the rooms is appear in other academic period
            $count = $this
                ->find()
                ->where([
                    $this->aliasField('previous_institution_room_id') => $entity->id
                ])
                ->count();

            if ($count > 0) {
                $isEditable = false;
            }
        } elseif ($entity->room_status_id == $endOfUsageId) {    // If already end of usage, not allow to edit or delete
            $isEditable = false;
            $isDeletable = false;
        }

        return compact('isEditable', 'isDeletable');
    }

    private function updateRoomStatus($code, $conditions)
    {
        $roomStatuses = $this->RoomStatuses->findCodeList();
        $status = $roomStatuses[$code];

        $entity = $this->find()->where([$conditions])->first();
        $entity->room_status_id = $status;
        $this->save($entity);
    }

    private function processEndOfUsage($entity)
    {
        $where = ['id' => $entity->id];
        $this->updateRoomStatus('END_OF_USAGE', $where);
    }

    private function processChangeInType($entity)
    {
        $newStartDateObj = new Date($entity->new_start_date);
        $endDateObj = $newStartDateObj->copy();
        $endDateObj->addDay(-1);
        $newRoomTypeId = $entity->new_room_type;

        $oldEntity = $this->find()->where(['id' => $entity->id])->first();
        $newRequestData = $oldEntity->toArray();

        // Update old entity
        $oldEntity->end_date = $endDateObj;

        $where = ['id' => $oldEntity->id];
        $this->updateRoomStatus('CHANGE_IN_TYPE', $where);
        $this->save($oldEntity);
        // End

        // Update new entity
        $ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
        foreach ($ignoreFields as $key => $field) {
            unset($newRequestData[$field]);
        }
        $newRequestData['start_date'] = $newStartDateObj;
        $newRequestData['room_type_id'] = $newRoomTypeId;
        $newRequestData['previous_institution_room_id'] = $oldEntity->id;
        $newEntity = $this->newEntity($newRequestData, ['validate' => false]);
        $newEntity = $this->save($newEntity, ['checkExisting' => false]);
        // End

        $url = $this->url('edit');
        unset($url['type']);
        unset($url['edit_type']);
        $url[1] = $this->paramsEncode(['id' => $newEntity->id]);
        return $this->controller->redirect($url);
    }

    public function getPeriodOptions($params = [])
    {
        $periodOptions = $this->AcademicPeriods->getYearList();
        if (is_null($this->request->query('period_id'))) {
            $this->request->query['period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $selectedPeriod = $this->queryString('period_id', $periodOptions);
        $this->advancedSelectOptions($periodOptions, $selectedPeriod);

        return compact('periodOptions', 'selectedPeriod');
    }

    public function getTypeOptions($params = [])
    {
        $withAll = array_key_exists('withAll', $params) ? $params['withAll'] : false;

        $typeOptions = $this->RoomTypes
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->find('visible')
            ->toArray();
        if ($withAll && count($typeOptions) > 1) {
            $typeOptions = ['-1' => __('All Room Types')] + $typeOptions;
        }
        $selectedType = $this->queryString('type', $typeOptions);
        $this->advancedSelectOptions($typeOptions, $selectedType);

        return compact('typeOptions', 'selectedType');
    }

    public function getStatusOptions($params = [])
    {
        $conditions = array_key_exists('conditions', $params) ? $params['conditions'] : [];
        $withAll = array_key_exists('withAll', $params) ? $params['withAll'] : false;

        $statusOptions = $this->RoomStatuses
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->where($conditions)
            ->toArray();
        if ($withAll && count($statusOptions) > 1) {
            $statusOptions = ['-1' => __('All Statuses')] + $statusOptions;
        }
        $selectedStatus = $this->queryString('status', $statusOptions);
        $this->advancedSelectOptions($statusOptions, $selectedStatus);

        return compact('statusOptions', 'selectedStatus');
    }

    public function getSubjectOptions($params = [])
    {
        $institutionId = array_key_exists('institution_id', $params) ? $params['institution_id'] : null;
        $academicPeriodId = array_key_exists('academic_period_id', $params) ? $params['academic_period_id'] : null;

        $options = [];

        $Classes = $this->Subjects->Classes;
        $classOptions = $Classes
            ->find()
            ->contain(['Subjects'])
            ->where([
                $Classes->aliasField('institution_id') => $institutionId,
                $Classes->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->order([$Classes->aliasField('name') => 'ASC'])
            ->toArray();

        foreach ($classOptions as $classKey => $class) {
            $className = $class->name;
            if ($class->has('subjects')) {
                foreach ($class->subjects as $subjectKey => $subject) {
                    $options[$subject->id] = $className . ' - ' . $subject->name;
                }
            }
        }

        return $options;
    }

    public function processCopy(Entity $entity)
    {
        // if is new and room status of previous room usage is change in room type then copy all general custom fields
        if ($entity->isNew()) {
            if ($entity->has('previous_institution_room_id') && !is_null($entity->previous_institution_room_id)) {
                $copyFrom = $entity->previous_institution_room_id;
                $copyTo = $entity->id;

                $previousEntity = $this->get($copyFrom);
                $changeInRoomTypeId = $this->RoomStatuses->getIdByCode('CHANGE_IN_TYPE');

                if ($previousEntity->room_status_id == $changeInRoomTypeId) {
                    // third parameters set to true means copy general only
                    $this->copyCustomFields($copyFrom, $copyTo, true);
                }
            }
        }
    }

    public function findInUse(Query $query, array $options)
    {
        $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : null;
        $academicPeriodId = array_key_exists('academic_period_id', $options) ? $options['academic_period_id'] : null;
        $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');

        $query->where([
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('room_status_id') => $inUseId
        ]);

        return $query;
    }

    public function academicPeriodAfterSave(Event $event, Entity $academicPeriodEntity)
    {
        $academicPeriodId = $academicPeriodEntity->id;

        if (!$academicPeriodEntity->isNew()) {
            $newStartDate = $academicPeriodEntity->start_date;
            $newEndDate = $academicPeriodEntity->end_date;
            $originalArray = $academicPeriodEntity->extractOriginal(['start_date', 'end_date']);
            $originalStartDate = $originalArray['start_date'];
            $originalEndDate = $originalArray['end_date'];

            if ($newStartDate >= $originalStartDate) {
                // if new start date is later than original start date, update start date
                $this->query()
                    ->update()
                    ->set(['start_date' => $newStartDate])
                    ->where([
                        'academic_period_id' => $academicPeriodId,
                        'start_date' . ' <= ' => $newStartDate->format('Y-m-d')
                    ])
                    ->execute();
            }

            if ($newEndDate <= $originalEndDate) {
                // if new end date is earlier than original end date, update end date
                $this->query()
                    ->update()
                    ->set(['end_date' => $newEndDate])
                    ->where([
                        'academic_period_id' => $academicPeriodId,
                        'end_date' . ' >= ' => $newEndDate->format('Y-m-d')
                    ])
                    ->execute();
            }
        }
    }
}
