<?php
namespace Institution\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use Cake\ORM\ResultSet;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Routing\Router;
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
    // POCOR-8037 removed academic period code
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('RoomStatuses', ['className' => 'Infrastructure.InfrastructureStatuses', 'foreignKey' => 'room_status_id']);
        $this->belongsTo('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'foreignKey' => 'institution_floor_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        // POCOR-8037 removed academic period code
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

        // POCOR-8037 removed academic period code
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        //comment cakephp4
        // POCOR-9344 restored
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

        $this->Levels = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $this->Levels->find('list')->toArray();
        $this->accessibilityOptions = $this->getSelectOptions('InstitutionAssets.accessibility');
        $this->accessibilityTooltip = $this->getMessage('InstitutionInfrastructures.accessibilityOption');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'SubjectStudents' => ['index'],
            'ScheduleTimetable' => ['index']
        ]);
        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InstitutionRooms'=>['id','institution_floor_id']]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmptyString('code')
            ->notEmptyString('name')
            ->notEmptyString('area')
            ->notEmptyString('accessibility')
            ->notEmptyString('year_acquired')
            ->notEmptyString('infrastructure_condition_id')
            ->notEmptyString('infrastructure_ownership_id')
            ->add('code', [
                'ruleUnique' => [
                    //POCOR-8060 - start_date can be empty
                    'rule' => ['validateUnique', ['scope' => ['institution_id']]], // POCOR-8037 removed academic period code
                    'provider' => 'table'
                ]
            ])
            // POCOR-8037 removed academic period code
            ->add('end_date', [
            // POCOR-8037 removed academic period code
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
            ->add('area', 'ruleValidateCustomLandSize', [
                'rule' => function ($value, $context) {
                    // Check if datatype is 'copy'
                    if (isset($context['data']['datatype']) && $context['data']['datatype'] == 'copy') {
                        // Skip validation when datatype is 'copy'
                        return true;
                    }

                    // Proceed with validation when datatype is not 'copy'
                    return $this->validateCustomLandSize($value, 'Maximum_institution_infrastructure_room_size', $context);
                },
                'provider' => 'table',
                'last' => true
            ])
            ->requirePresence('new_start_date', function ($context) {
                if (array_key_exists('change_type', $context['data'])) {
                    $selectedEditType = $context['data']['change_type'];
                    if ($selectedEditType == self::CHANGE_IN_TYPE) {
                        return true;
                    }
                }

                return false;
            })
            ->notEmpty('room_type_id')
            ->notEmpty('infrastructure_ownership_id')
            ->notEmpty('infrastructure_condition_id')
            ->notEmpty('accessibility');
    }

    public function validationSavingByAssociation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator;
    }

    public function findSubjectRoomOptions(Query $query, array $options)
    {
        // POCOR-8037 removed academic period code
        $institutionSubjectId = $options['institution_subject_id'];
        $classSubjectsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $institution = $classSubjectsTable->find()->contain('InstitutionClasses.InstitutionShifts')->where([$classSubjectsTable->aliasField('institution_subject_id') => $institutionSubjectId])->first();
        return $query
            ->find('inUse', ['institution_id' => $institution->institution_class->institution_shift->institution_id,
                ]) // POCOR-8037 removed academic period code
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

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        // POCOR-8037 removed academic period code
        $events['ControllerAction.Model.add.beforeAction'] = 'addDeleteBeforeAction';
        return $events;
    }

    // POCOR-8060::start
    private function setLastDateForStartDate(&$data)
    {
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if ($data['start_date'] > $data['end_date']) {
                if ($data['change_type'] == self::END_OF_USAGE) {
                    $data['start_date'] = $data['end_date'];
                } else {
                    $data['end_date'] = $data['start_date'];
                }
            }
        }
    }

    private function setLastDateForEmptyStartDate(&$data)
    {
        if (!($data['start_date']) && isset($data['end_date'])) {
            $data['end_date'] = null;
        }
    }
    // POCOR-8060::end
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        self::setLastDateForStartDate($data);
        self::setLastDateForEmptyStartDate($data);
    }


    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //Start:POCOR-7597
        if(!empty($entity['institution_floor_id'])){
            $InstitutionFloors = TableRegistry::getTableLocator()->get('Institution.InstitutionFloors');
            $InstitutionFloor = $InstitutionFloors->get($entity['institution_floor_id']);
        }
        if($entity['area'] >= $InstitutionFloor['area']){
            if (Router::getRequest()->getParam('action') == "CopyData") {
            }//POCOR_7657
            else {
            $this->Alert->warning('InstitutionRooms.sizeGreater', ['reset' => true]);
            return false;
            }
        }
        //End:POCOR-7597
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

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // logic to copy custom fields (general only) where new room is created when change in room type
        $this->processCopy($entity);
    }

    public function onGetInfrastructureLevel(EventInterface $event, Entity $entity)
    {
        return $this->levelOptions[$this->roomLevel];
    }

    public function onGetAccessibility(EventInterface $event, Entity $entity)
    {
        return $this->accessibilityOptions[$entity->accessibility];
    }

    public function onGetSubjects(EventInterface $event, Entity $entity)
    {
        $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        // POCOR-8037 added academic period code for subjects start
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

        if ($entity->has('subjects')) {
            $resultArray = [];

            foreach ($entity->subjects as $key => $obj) {
                $records = $InstitutionClassSubjects->find()
                    ->where([$InstitutionClassSubjects->aliasField('institution_subject_id') => $obj->id])
                    ->first();
                $className = $InstitutionClasses->get($records->institution_class_id)->name;
                $academicPeriodName = $AcademicPeriods->get($obj->academic_period_id)->name;
                $resultArray[] = $academicPeriodName . ' - ' . $className . ' - ' . $obj->name;
            }
            // POCOR-8037 added academic period code for subjects end

            if (!empty($resultArray)) {
                return implode(', ', $resultArray);
            }
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'institution_id') {
            return __('Owner');
        } else if ($field == 'room_status_id'){
            return __('Room Status');
        } else if($field == 'start_date'){
            return __('Start Date');
        } else if($field == 'end_date'){
            return __('End Date');
        } else if($field == 'comment'){
            return __('Comment');
        } elseif ($field == 'to_be_deleted') {
            return __('To be Deleted ');
        } elseif ($field == 'associated_records') {
            return __('Associated Records');
        } else if ($field == 'modified'){
            return __('Modified');
        } else if ($field == 'modified_user_id'){
            return __('Modified By');
        } else if ($field == 'created'){
            return __('Created');
        } else if ($field == 'created_user_id'){
            return __('Created By');
        } else if ($field == 'new_room_type'){
            return __('New Room Type');
        } else if ($field == 'new_start_date'){
            return __('New Start Date');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        // unset edit_type so that will always default to Update Details
        foreach ($buttons as $action => $attr) {
            if (isset($attr['url']) && array_key_exists('edit_type', $attr['url'])) {
                unset($buttons[$action]['url']['edit_type']);
            }
        }
        $queryString = $buttons['remove']['url']['1'];
        $params = $this->paramsDecode($queryString);
        $params['id'] = $entity->id;
        $queryString = $this->paramsEncode($params);
        $buttons['remove']['url']['1'] = $queryString;

        return $buttons;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //Start:POCOR-6693
        $this->field('area', ['attr' => ['label' => __('Size')]]);
        //End:POCOR-6693
        $this->Navigation->substituteCrumb(__('Institution Rooms'), __('Institution Rooms'));
    }

    public function onUpdateFieldInstitutionFloorId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['type'] = 'hidden';
        if ($action == 'add') {
            $attr['value'] = $this->getQueryString('institution_floor_id');
        }
        return $attr;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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
        // POCOR-8037 removed academic period code
        $this->field('infrastructure_condition_id', ['visible' => false]);
        $this->field('area', ['visible' => false]);
        $this->field('previous_institution_room_id', ['visible' => false]);

        $extra['elements']['toolbarElements'] = $this->addBreadcrumbElement();
        $extra['elements']['control'] = $this->addControlFilterElement();
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
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

        // POCOR-8037 removed academic period code
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

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $session = $this->request->getSession();

        $sessionKey = $this->getRegistryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $this->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        // POCOR-8037 removed academic period code
        $query->contain(['RoomTypes', 'InfrastructureConditions', 'Subjects']);
    }

    public function editBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $session = $this->request->getSession();

        $sessionKey = $this->getRegistryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $this->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function editAfterQuery(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

        $session = $this->request->getSession();
        $sessionKey = $this->getRegistryAlias() . '.warning';
        if (!$isEditable) {
            $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
            $endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

            if ($entity->room_status_id == $inUseId) {
                $session->write($sessionKey, $this->getAlias().'.in_use.restrictEdit');
            } elseif ($entity->room_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->getAlias().'.end_of_usage.restrictEdit');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        } else {
            //$selectedEditType = $this->request->getQuery('edit_type');
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                $today = new DateTime();
                $diff = date_diff($entity->start_date, $today);

                // Not allowed to change room type in the same day
                if ($diff->days == 0) {
                    $session->write($sessionKey, $this->getAlias().'.change_in_room_type.restrictEdit');

                    $url = $this->url('edit');
                    $url['edit_type'] = self::UPDATE_DETAILS;
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                }
            }
        }
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

        $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

        if (!$isDeletable) {
            $session = $this->request->getSession();
            $sessionKey = $this->getRegistryAlias() . '.warning';
            if ($entity->room_status_id == $inUseId) {
                $session->write($sessionKey, $this->getAlias().'.in_use.restrictDelete');
            } elseif ($entity->room_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->getAlias().'.end_of_usage.restrictDelete');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }

        //$extra['excludedModels'] = [$this->CustomFieldValues->getAlias()];//POCOR-7485

    // POCOR-8037 removed academic period code
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $toolbarElements = $this->addBreadcrumbElement();
        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $toolbarElements = $this->addBreadcrumbElement();
        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(EventInterface $event, Entity $entity)
    {
        //$selectedEditType = $this->request->getQuery('edit_type');
        $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
        if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_TYPE) {
            foreach ($this->fields as $field => $attr) {
                if ($this->startsWith($field, 'custom_') || $this->startsWith($field, 'section_')) {
                    $this->fields[$field]['visible'] = false;
                }
            }
        }
    }

    public function onUpdateFieldChangeType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view' || $action == 'add') {
            $attr['visible'] = false;
        } elseif ($action == 'edit') {
            $editTypeOptions = $this->getSelectOptions('InstitutionInfrastructure.change_types');
            //$selectedEditType = $this->setQueryString('edit_type', $editTypeOptions);
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
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

    public function onUpdateFieldRoomStatusId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } elseif ($action == 'add') {
            $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
            $attr['value'] = $inUseId;
        }

        return $attr;
    }

    // public function onUpdateFieldInstitutionInfrastructureId(EventInterface $event, array $attr, $action, Request $request)
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

    // POCOR-8037 removed academic period code
    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'index' || $action == 'view') {
            if (!empty($this->getOwnerInstitutionId())) {
                $attr['type'] = 'select';
            }
        }

        return $attr;
    }

    public function onUpdateFieldCode(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldName(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            //$selectedEditType = $request->getQuery('edit_type');
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'readonly';
            }
        }

        return $attr;
    }

    public function onUpdateFieldRoomTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
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
            //$selectedEditType = $request->getQuery('edit_type');
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
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

    public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $today = new DateTime();
        // POCOR-8037 removed academic period code
        $startDate = $today->format('d-m-Y');
        $attr['date_options']['startDate'] = $startDate;

        return $attr;
    }

    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // POCOR-8037 removed academic period code start
        if ($action == 'view') {
            $attr['visible'] = true;
        } elseif ($action == 'add' || $action == 'edit') {

            $entity = $attr['entity'];

            //$selectedEditType = $request->getQuery('edit_type');
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::END_OF_USAGE) {

                // temporary restrict to today until have better solution
                $today = new DateTime();

                $attr['type'] = 'readonly';
                $attr['value'] = $today->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($today);
            } else {
                if (!empty($start_date)) {
                    $attr['date_options']['startDate'] = $start_date->format('d-m-Y');
                }
            }
        }
        // POCOR-8037 removed academic period code end

        return $attr;
    }

    public function onUpdateFieldAccessibility(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit' || $action == 'add') {
            $attr['options'] = $this->accessibilityOptions;
            $attr['type'] = 'select';
            return $attr;
        }
    }

    public function onUpdateFieldInfrastructureConditionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            //$selectedEditType = $request->getQuery('edit_type');
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldSubjects(EventInterface $event, array $attr, $action, ServerRequest $request)
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

        if ($visibility && ($action == 'add' || $action == 'edit')) { // POCOR-8037 removed academic period code

            $institutionId = $this->getInstitutionID();

            $attr['options'] = $this->getSubjectOptions(['institution_id' => $institutionId]); // POCOR-8037 removed academic period code
        }

        if (!$this->canUpdateDetails) {
            $attr['visible'] = false;
        }


        return $attr;
    }

    public function onUpdateFieldNewRoomType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            //$selectedEditType = $request->getQuery('edit_type');
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
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

    public function onUpdateFieldNewStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {

            // POCOR-8037 removed academic period code
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                // POCOR-8037 removed academic period code

                // temporary restrict to today until have better solution
                $today = new DateTime();

                $attr['visible'] = true;
                $attr['null'] = false;    // for asterisk to appear
                //$attr['type'] = 'readonly'; //POCOR-8004
                $attr['value'] = $today->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($today);
            }
        }

        return $attr;
    }

    public function addEditOnChangeRoomType(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->getQuery['type']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('room_type_id', $request->getData($this->getAlias()))) {
                    $selectedType = $request->getData($this->getAlias())['room_type_id'];
                    //$request->getQuery['type'] = $selectedType;
                    $this->request = $this->request->withQueryParams(['type' => $selectedType]);
                }

                if (array_key_exists('custom_field_values', $request->getData($this->getAlias()))) {
                    unset($request->getData($this->getAlias())['custom_field_values']);
                }
            }
        }
    }

    private function setupFields(Entity $entity)
    {
        $this->setFieldOrder([
            'change_type', // POCOR-8037 removed academic period code
            'institution_id',
            'code', 'name', 'room_type_id', 'room_status_id', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id', 'previous_institution_room_id','area', 'new_room_type', 'new_start_date'
        ]);

        $this->field('change_type');
        $this->field('room_status_id', ['type' => 'hidden']);
        // POCOR-8037 removed academic period code
        $this->field('institution_id');
        $this->field('institution_floor_id');
        $this->field('code');
        $this->field('name');
        $this->field('room_type_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);

        //POCOR-6760[start]
        if($entity->room_status_id != self::END_OF_USAGE) {
            $this->field('end_date', ['entity' => $entity]);
        }
        //POCOR-6760[end]

        $this->field('previous_institution_room_id', ['type' => 'hidden']);
        $this->field('infrastructure_condition_id', ['type' => 'select']);
        $this->field('subjects', [
            'type' => 'chosenSelect',
            'fieldNameKey' => 'subjects',
            'fieldName' => $this->getAlias() . '.subjects._ids',
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
                $this->InstitutionFloors->aliasField($this->InstitutionFloors->getPrimaryKey()) => $parentId
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
        $params = $this->getQueryString();
        // POCOR-8037 fixed urls for crumbs start
        $encodedQueryString = $this->paramsEncode(['institution_id' => $params['institution_id']]);
        $url = $this->url('index');
        if (isset($url[1])) {
            unset($url[1]);
        }

        $buildingUrl = $url;
        $buildingUrl['action'] = 'InstitutionBuildings';
        $buildingUrl[1] = $this->paramsEncode([
            'institution_land_id' => $params['institution_land_id'],
            'institution_land_name' => $params['institution_land_name'],
            'institution_id' => $params['institution_id']
        ]);

        $floorUrl = $url;
        $floorUrl['action'] = 'InstitutionFloors';
        $floorUrl[1] = $this->paramsEncode([
            'institution_land_id' => $params['institution_land_id'],
            'institution_land_name' => $params['institution_land_name'],
            'institution_id' => $params['institution_id'],
            'institution_building_id' => $params['institution_building_id'],
            'institution_building_name' => $params['institution_building_name'],
        ]);

        $crumbs[] = [
            'name' => $params['institution_land_name'],
            'url' => $buildingUrl
        ];
        $crumbs[] = [
            'name' => $params['institution_building_name'],
            'url' => $floorUrl
        ];
        $crumbs[] = [
            'name' => $params['institution_floor_name']
        ];
        // POCOR-8037 fixed urls for crumbs end
        $toolbarElements = ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => ['encodedQueryString' => $encodedQueryString, 'crumbs'=>$crumbs], 'options' => [], 'order' => 1];

        return $toolbarElements;
    }

    private function addControlFilterElement()
    {
        // POCOR-8037 fixed
        list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true]));
        $toolbarElements = ['name' => 'Institution.Infrastructure/controls', 'data' => compact('typeOptions', 'selectedType'), 'options' => [], 'order' => 2];

        return $toolbarElements;
    }

    private function checkIfCanEditOrDelete($entity)
    {
        $isEditable = true;
        $isDeletable = true;

        $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->RoomStatuses->getIdByCode('END_OF_USAGE');

        if ($entity->room_status_id == $endOfUsageId) {    // POCOR-8037 removed academic period code
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

    // POCOR-8037 removed academic period code
    public function getTypeOptions($params = [])
    {
        $withAll = isset($params['withAll']) ? $params['withAll'] : false;

        $typeOptions = $this->RoomTypes
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->find('visible')
            ->toArray();
        if ($withAll && count($typeOptions) > 1) {
            $typeOptions = ['-1' => __('All Room Types')] + $typeOptions;
        }
        if (!is_null($this->request->getAttribute('params')['?']['type'])) {
            $type = $this->request->getAttribute('params')['?']['type'];
            $this->request = $this->request->withQueryParams(['type' => $type]);
        }
        $selectedType = $this->queryString('type', $typeOptions);
        $this->advancedSelectOptions($typeOptions, $selectedType);

        return compact('typeOptions', 'selectedType');
    }

    public function getStatusOptions($params = [])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $withAll = isset($params['withAll']) ? $params['withAll'] : false;

        $statusOptions = $this->RoomStatuses
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->where($conditions)
            ->toArray();
        if ($withAll && count($statusOptions) > 1) {
            $statusOptions = ['-1' => __('All Statuses')] + $statusOptions;
        }
        if (!is_null($this->request->getAttribute('params')['?']['status'])) {
            $status = $this->request->getAttribute('params')['?']['status'];
            $this->request = $this->request->withQueryParams(['status' => $status]);
        }
        $selectedStatus = $this->queryString('status', $statusOptions);
        $this->advancedSelectOptions($statusOptions, $selectedStatus);

        return compact('statusOptions', 'selectedStatus');
    }

    public function getSubjectOptions($params = [])
    {
        $institutionId = isset($params['institution_id']) ? $params['institution_id'] : null;

        $options = [];

        $Classes = $this->Subjects->Classes;
        // POCOR-8037 added academic period code start
        $where = [
            $Classes->aliasField('institution_id') => $institutionId,
        ];

        $classOptions = $Classes
            ->find()
            ->contain(['Subjects', 'AcademicPeriods'])
            ->where($where)
            ->order(['AcademicPeriods.code' => 'ASC', $Classes->aliasField('name') => 'ASC'])
            ->toArray();

        foreach ($classOptions as $classKey => $class) {
            $className = $class->name;
            if ($class->has('subjects')) {
                foreach ($class->subjects as $subjectKey => $subject) {

                    $options[$subject->id] = $class->academic_period->name . ' - ' . $className . ' - ' . $subject->name;
                }
            }
        }
        // POCOR-8037 added academic period end
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
        $institutionId = isset($options['institution_id']) ? $options['institution_id'] : null;
        // POCOR-8037 removed academic period code
        $inUseId = $this->RoomStatuses->getIdByCode('IN_USE');

        $query->where([
            $this->aliasField('institution_id') => $institutionId, // POCOR-8037 removed academic period code
            $this->aliasField('room_status_id') => $inUseId
        ]);

        return $query;
    }

    // POCOR-8037 removed academic period code
    public function addDeleteBeforeAction(EventInterface $event, ArrayObject $extra)
    {

        $model = $this;
        $url = $model->url('index');
        $institutionID = $this->getInstitutionID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        //$queryString['id'] = $institutionID;
        $queryString = $model->getQueryString();


        unset($queryString['id']);

        $queryString['institution_id'] = $institutionID;
        $url[1] = $model->paramsEncode($queryString);
        $extra['redirect'] = $url;
    }
}
