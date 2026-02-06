<?php
namespace Institution\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use Cake\ORM\ResultSet;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Routing\Router;
use Cake\Http\ServerRequest;
class InstitutionFloorsTable extends ControllerActionTable
{
    use OptionsTrait;
    const IN_USE = 1;
    const UPDATE_DETAILS = 1;    // In Use
    const END_OF_USAGE = 2;
    const CHANGE_IN_TYPE = 3;

    private $Levels = null;
    private $levelOptions = [];
    private $floorLevel = null;

    private $canUpdateDetails = true;
    // POCOR-8037 removed academic period code
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('FloorStatuses', ['className' => 'Infrastructure.InfrastructureStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        // POCOR-8037 removed academic period code
        $this->belongsTo('FloorTypes', ['className' => 'Infrastructure.FloorTypes']);
        $this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
        $this->belongsTo('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'foreignKey' => 'institution_building_id']);
        $this->belongsTo('PreviousFloors', ['className' => 'Institution.InstitutionFloors', 'foreignKey' => 'previous_institution_floor_id']);
        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true]);

        // POCOR-8037 removed academic period code
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        // POCOR-9344 restored
        $this->addBehavior('CustomField.Record', [
            'fieldKey' => 'infrastructure_custom_field_id',
            'tableColumnKey' => null,
            'tableRowKey' => null,
            'fieldClass' => ['className' => 'Infrastructure.FloorCustomFields'],
            'formKey' => 'infrastructure_custom_form_id',
            'filterKey' => 'infrastructure_custom_filter_id',
            'formFieldClass' => ['className' => 'Infrastructure.FloorCustomFormsFields'],
            'formFilterClass' => ['className' => 'Infrastructure.FloorCustomFormsFilters'],
            'recordKey' => 'institution_floor_id',
            'fieldValueClass' => ['className' => 'Infrastructure.FloorCustomFieldValues', 'foreignKey' => 'institution_floor_id', 'dependent' => true],
            'tableCellClass' => null
        ]);
        $this->addBehavior('Institution.InfrastructureShift');

        $this->Levels = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureLevels');
        $this->CustomFieldValues = TableRegistry::getTableLocator()->get('CustomField.CustomFieldValues');
        $this->levelOptions = $this->Levels->find('list')->toArray();
        $this->accessibilityOptions = $this->getSelectOptions('InstitutionAssets.accessibility');
        $this->accessibilityTooltip = $this->getMessage('InstitutionInfrastructures.accessibilityOption');
        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InstitutionFloors'=>['id','institution_building_id']]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmptyString('name')
            ->notEmptyString('code')
            ->notEmptyString('area')
            ->notEmptyString('accessibility')
            ->notEmptyString('year_acquired')
            ->notEmptyString('infrastructure_condition_id')
            ->notEmptyString('infrastructure_ownership_id')
            ->add('code', [
                'ruleUnique' => [
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
            ->add('area', 'ruleValidateCustomLandSize', [
                'rule' => function ($value, $context) {
                    // Check if datatype is 'copy'
                    if (isset($context['data']['datatype']) && $context['data']['datatype'] == 'copy') {
                        // Skip validation when datatype is 'copy'
                        return true;
                    }

                    // Proceed with validation when datatype is not 'copy'
                    return $this->validateCustomLandSize($value, 'Maximum_institution_infrastructure_floor_size', $context);
                },
                'provider' => 'table',
                'last' => true
            ])
            ->requirePresence('new_floor_type', function ($context) {
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
            ->notEmpty('floor_type_id')
            ->notEmpty('infrastructure_ownership_id')
            ->notEmpty('infrastructure_condition_id')
            ->notEmpty('accessibility')
            ;
        ;
    }

    public function validationSavingByAssociation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator;
    }

    // POCOR-8037 removed academic period code
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //Start:POCOR-6693
        $this->field('area', ['attr' => ['label' => __('Size')]]);
        //End:POCOR-6693
        $this->Navigation->substituteCrumb(__('Institution Floors'), __('Institution Floors'));
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //Start:POCOR-7597
        if(!empty($entity['institution_building_id'])){
            $InstitutionBuildings = TableRegistry::getTableLocator()->get('Institution.InstitutionBuildings');
            $InstitutionBuilding = $InstitutionBuildings->get($entity['institution_building_id']);
        }
        if($entity['area'] > $InstitutionBuilding['area']){
            if (Router::getRequest()->getParam('action') == "CopyData") {
            } else {//POCOR_7657
            $this->Alert->warning('InstitutionFloors.sizeGreater', ['reset' => true]);
            return false;
            }
        }
        //End:POCOR-7597
        if (!$entity->isNew() && $entity->has('change_type')) {
            $editType = $entity->change_type;
            $statuses = $this->FloorStatuses->find('list', ['keyField' => 'id', 'valueField' => 'code'])->toArray();
            $functionKey = Inflector::camelize(strtolower($statuses[$editType]));
            $functionName = "process$functionKey";

            if (method_exists($this, $functionName)) {
                $this->$functionName($entity);
            }
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // logic to copy custom fields (general only) where new floor is created when change in floor type
        if ($entity->isNew()) {
            $this->processCopy($entity);
        } elseif ($entity->floor_status_id == $this->FloorStatuses->getIdByCode('END_OF_USAGE')) {
            $roomEntities = $this->InstitutionRooms
                ->find()
                ->where([
                    $this->InstitutionRooms->aliasField('institution_floor_id') => $entity->id,
                    $this->InstitutionRooms->aliasField('room_status_id') => SELF::IN_USE
                ])
                ->toArray();
            foreach ($roomEntities as $roomEntity) {
                $roomEntity->change_type = SELF::END_OF_USAGE;
                $roomEntity->end_date = $entity->end_date;
                $this->InstitutionRooms->save($roomEntity);
            }
        }
    }

    public function onGetInfrastructureLevel(EventInterface $event, Entity $entity)
    {
        return $this->levelOptions[$this->floorLevel];
    }

    public function onGetAccessibility(EventInterface $event, Entity $entity)
    {
        return $this->accessibilityOptions[$entity->accessibility];
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'institution_id') {
            return __('Owner');
        } else if ($field == 'floor_status_id'){
            return __('Floor Status');
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
        } else if ($field == 'new_floor_type'){
            return __('New Floor Type');
        } else if ($field == 'new_start_date'){
            return __('New Start Date');
        } else if ($field == 'modified'){
            return __('Modified');
        } else if ($field == 'modified_user_id'){
            return __('Modified By');
        } else if ($field == 'created'){
            return __('Created');
        } else if ($field == 'created_user_id'){
            return __('Created By');
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

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->floorLevel = $this->Levels->getFieldByCode('FLOOR', 'id');
        $this->setFieldOrder(['code', 'name', 'institution_id', 'infrastructure_level', 'floor_type_id', 'floor_status_id']);
        $this->fields['area']['visible'] = false;
        $this->fields['comment']['visible'] = false;
        $this->field('accessibility', ['visible' => false]);
        $this->field('institution_id');
        $this->field('infrastructure_level', ['after' => 'name']);
        $this->field('start_date', ['visible' => false]);
        $this->field('start_year', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('end_year', ['visible' => false]);
        $this->field('institution_building_id', ['visible' => false]);
        // POCOR-8037 removed academic period code
        $this->field('infrastructure_condition_id', ['visible' => false]);
        $this->field('previous_institution_floor_id', ['visible' => false]);

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

        $parentId = $this->getQueryString('institution_building_id');
        $parentRecord = $this->InstitutionBuildings->get($parentId, ['contain' => 'InstitutionLands'])->toArray();
        if (isset($extra['toolbarButtons']['add'])) {
            if ($parentRecord['building_status_id'] == SELF::END_OF_USAGE || $parentRecord['institution_land']['land_status_id'] == SELF::END_OF_USAGE) {
                unset($extra['toolbarButtons']['add']);
            }
        }
        if (!is_null($parentId)) {
            $query->where([$this->aliasField('institution_building_id') => $parentId]);
        } else {
            $query->where([$this->aliasField('institution_building_id IS NULL')]);
        }

        // POCOR-8037 removed academic period code
        // Floor Types
        list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true]));
        if ($selectedType != '-1') {
            $query->where([$this->aliasField('floor_type_id') => $selectedType]);
        }
        $this->controller->set(compact('typeOptions', 'selectedType'));
        // End

        // Floor Statuses
        list($statusOptions, $selectedStatus) = array_values($this->getStatusOptions([
            'conditions' => [
                'code IN' => ['IN_USE', 'END_OF_USAGE']
            ],
            'withAll' => true
        ]));
        if ($selectedStatus != '-1') {
            $query->where([$this->aliasField('floor_status_id') => $selectedStatus]);
        } else {
            // default show In Use and End Of Usage
            $query->matching('FloorStatuses', function ($q) {
                return $q->where([
                    'FloorStatuses.code IN' => ['IN_USE', 'END_OF_USAGE']
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
        $query->contain(['InstitutionBuildings', 'FloorTypes', 'InfrastructureConditions']);
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
            $inUseId = $this->FloorStatuses->getIdByCode('IN_USE');
            $endOfUsageId = $this->FloorStatuses->getIdByCode('END_OF_USAGE');

            if ($entity->floor_status_id == $inUseId) {
                $session->write($sessionKey, $this->getAlias().'.in_use.restrictEdit');
            } elseif ($entity->floor_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->getAlias().'.end_of_usage.restrictEdit');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        } else {
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                $today = new DateTime();
                $diff = date_diff($entity->start_date, $today);

                // Not allowed to change floor type in the same day
                if ($diff->days == 0) {
                    $session->write($sessionKey, $this->getAlias().'.change_in_floor_type.restrictEdit');

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

        $inUseId = $this->FloorStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->FloorStatuses->getIdByCode('END_OF_USAGE');

        if (!$isDeletable) {
            $session = $this->request->getSession();
            $sessionKey = $this->getRegistryAlias() . '.warning';
            if ($entity->floor_status_id == $inUseId) {
                $session->write($sessionKey, $this->getAlias().'.in_use.restrictDelete');
            } elseif ($entity->floor_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->getAlias().'.end_of_usage.restrictDelete');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }

        $extra['excludedModels'] = [
            $this->CustomFieldValues->getAlias(),
            $this->InstitutionRooms->getAlias()
        ];

        // POCOR-8037 removed academic period code start
        $roomQuery = $this->InstitutionRooms
            ->find()
            ->where([
                $this->InstitutionRooms->aliasField('institution_floor_id') => $entity->id,
                $this->InstitutionRooms->aliasField('room_status_id IN ') => [$inUseId, $endOfUsageId]
            ])
            ->all();

        $extra['associatedRecords'][] = [
            'model' => $this->InstitutionRooms->getAlias(),
            'count' => $roomQuery->count()
        ];
        // POCOR-8037 removed academic period code end
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

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
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

    public function onUpdateFieldFloorStatusId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } elseif ($action == 'add') {
            $inUseId = $this->FloorStatuses->getIdByCode('IN_USE');
            $attr['value'] = $inUseId;
        }

        return $attr;
    }

    // POCOR-8037 removed academic period code start
    public function onUpdateFieldCode(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $parentId = $this->getQueryString('institution_building_id');
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
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'readonly';
            }
        }

        return $attr;
    }

    public function onUpdateFieldFloorTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $classificationOptions = $this->getSelectOptions('RoomTypes.classifications');
            $floorTypeOptions = $this->FloorTypes
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->find('visible')
                    ->order([
                        $this->FloorTypes->aliasField('order') => 'ASC'
                    ])
                    ->toArray();

            $attr['options'] = $floorTypeOptions;
            $attr['onChangeReload'] = 'changeFloorType';
        } elseif ($action == 'edit') {
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::END_OF_USAGE) {
                $attr['type'] = 'hidden';
            } else {
                $entity = $attr['entity'];

                $attr['type'] = 'readonly';
                $attr['value'] = $entity->floor_type->id;
                $attr['attr']['value'] = $entity->floor_type->name;
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
                }            }
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
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldNewFloorType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                $floorTypeOptions = $this->FloorTypes
                    ->find('list')
                    ->find('visible')
                    ->where([
                        $this->FloorTypes->aliasField('id <>') => $entity->floor_type_id
                    ])
                    ->toArray();

                $attr['visible'] = true;
                $attr['options'] = $floorTypeOptions;
                $attr['select'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldNewStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {


            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                // POCOR-8037 removed academic period code

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

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'index' || $action == 'view') {
            if (!empty($this->getOwnerInstitutionId())) {
                $attr['type'] = 'select';
            }
        }

        return $attr;
    }

    public function addEditOnChangeFloorType(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->getQuery['type']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('floor_type_id', $request->getData($this->getAlias()))) {
                    $selectedType = $request->getData($this->getAlias())['floor_type_id'];
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
            'change_type', 'institution_building_id', // POCOR-8037 removed academic period code
            'institution_id', 'code', 'name', 'floor_type_id', 'area', 'floor_status_id', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id', 'previous_institution_floor_id', 'new_floor_type', 'new_start_date'
        ]);

        $this->field('change_type');
        $this->field('floor_status_id', ['type' => 'hidden']);
        $this->field('institution_building_id', ['entity' => $entity]);
        // POCOR-8037 removed academic period code
        $this->field('institution_id');
        $this->field('code');
        $this->field('name');
        $this->field('area');
        $this->field('floor_type_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);

        //POCOR-6760[start]
        if($entity->floor_status_id != self::END_OF_USAGE) {
            $this->field('end_date', ['entity' => $entity]);
        }
        //POCOR-6760[end]

        $this->field('infrastructure_condition_id', ['type' => 'select']);
        $this->field('previous_institution_floor_id', ['type' => 'hidden']);
        $this->field('new_floor_type', ['type' => 'select', 'visible' => false, 'entity' => $entity]);
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

    public function onUpdateFieldInstitutionBuildingId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['type'] = 'hidden';
        if ($action == 'add') {
            $attr['value'] = $this->getQueryString('institution_building_id');
        }
        return $attr;
    }

    public function onGetCode(EventInterface $event, Entity $entity)
    {
        $institutionId = $this->request->getParam('institutionId');
        $params = $this->getQueryString();
        $params['institution_floor_id'] = $entity->id;
        $params['institution_floor_name'] = $entity->name;
        $encodedQueryString = $this->paramsEncode($params);
        $url = [
            'plugin' => $this->controller->getPlugin(),
            'controller' => $this->controller->getName(),
            'action' => 'InstitutionRooms',
            '0' => 'index',
            '1' => $encodedQueryString,
            'institutionId' => $institutionId
        ];
        $url = array_merge($url, $this->request->getQuery());
        $paramsArr = $this->request->getParam('?'); //POCOR-8523
        $url = is_array($paramsArr) ? array_merge($url, $paramsArr) : $url; //POCOR-8523
        //$url = $this->setQueryString($url, ['institution_floor_id' => $entity->id, 'institution_floor_name' => $entity->name]);
        return $event->getSubject()->HtmlField->link($entity->code, $url);
    }

    private function getAutoGenerateCode($parentId)
    {
        $codePrefix = '';
        $lastSuffix = '00';
        $conditions = [];
        // has Parent then get the ID of the parent then followed by counter
        $parentData = $this->InstitutionBuildings->find()
            ->where([
                $this->InstitutionBuildings->aliasField($this->InstitutionBuildings->getPrimaryKey()) => $parentId
            ])
            ->first();

        $codePrefix = $parentData->code;

        // $conditions[] = $this->aliasField('code')." LIKE '" . $codePrefix . "%'";
        $lastRecord = $this->find()
            ->where([
                $this->aliasField('institution_building_id') => $parentId,
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
        $crumbs = [];
        $params = $this->getQueryString();
        // POCOR-8037 fixed crumbs start
        $institutionQueryString = $this->paramsEncode(['institution_id' => $params['institution_id']]);
        $url = $this->url('index');
        if (isset($url[1])) {
            unset($url[1]);
        }

        $landUrl = $url;
        $landUrl['action'] = 'InstitutionBuildings';
        $landUrl['0'] = 'index';
        $land_params = [
            'institution_land_id' => $params['institution_land_id'],
            'institution_land_name' => $params['institution_land_name'],
            'institution_id' => $params['institution_id'],
        ];
        $landUrl['1'] = $this->paramsEncode($land_params);

        $crumbs[] = [
            'name' => $params['institution_land_name'],
            'url' => $landUrl
        ];
        $crumbs[] = [
            'name' => $params['institution_building_name']
        ];
        $toolbarElements = ['name' => 'Institution.Infrastructure/breadcrumb',
            'data' => ['encodedQueryString' => $institutionQueryString,
            'crumbs'=>$crumbs],
            'options' => [], 'order' => 1];
        // POCOR-8037 fixed crumbs end
        return $toolbarElements;
    }

    private function addControlFilterElement()
    {
        list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true])); // POCOR-8937 fixed
        $toolbarElements = ['name' => 'Institution.Infrastructure/controls', 'data' => compact('typeOptions', 'selectedType'), 'options' => [], 'order' => 2];
        return $toolbarElements;
    }

    private function checkIfCanEditOrDelete($entity)
    {
        $isEditable = true;
        $isDeletable = true;

        $inUseId = $this->FloorStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->FloorStatuses->getIdByCode('END_OF_USAGE');

        if ($entity->floor_status_id == $inUseId) {
            $isEditable = true; // POCOR-8037 removed academic period code
            $isDeletable = true;
        } elseif ($entity->floor_status_id == $endOfUsageId) {    // If already end of usage, not allow to edit or delete
            $isEditable = false;
            $isDeletable = false;
        }

        return compact('isEditable', 'isDeletable');
    }

    // POCOR-8037 removed academic period code
    public function getTypeOptions($params = [])
    {
        $withAll = isset($params['withAll']) ? $params['withAll'] : false;

        $typeOptions = $this->FloorTypes
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->find('visible')
            ->toArray();
        if ($withAll && count($typeOptions) > 1) {
            $typeOptions = ['-1' => __('All Floor Types')] + $typeOptions;
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

        $statusOptions = $this->FloorStatuses
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

    public function onUpdateFieldArea(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function processCopy(Entity $entity)
    {
        // if is new and floor status of previous floor usage is change in floor type then copy all general custom fields
        if ($entity->has('previous_institution_floor_id') && !is_null($entity->previous_institution_floor_id)) {
            $copyFrom = $entity->previous_institution_floor_id;
            $copyTo = $entity->id;

            $previousEntity = $this->get($copyFrom);
            $changeInTypeId = $this->FloorStatuses->getIdByCode('CHANGE_IN_TYPE');

            if ($previousEntity->floor_status_id == $changeInTypeId) {
                // third parameters set to true means copy general only
                $this->copyCustomFields($copyFrom, $copyTo, true);
                $this->InstitutionRooms->updateAll([
                    'institution_floor_id' => $copyTo
                ], [
                    'institution_floor_id' => $copyFrom
                ]);
            }
        }
    }

    private function processEndOfUsage($entity)
    {
        $where = ['id' => $entity->id];
        $this->updateStatus('END_OF_USAGE', $where);
    }

    private function processChangeInType($entity)
    {
        $newStartDateObj = new Date($entity->new_start_date);
        $endDateObj = $newStartDateObj->copy();
        $endDateObj->addDay(-1);
        $newFloorTypeId = $entity->new_floor_type;

        $oldEntity = $this->find()->where(['id' => $entity->id])->first();
        $newRequestData = $oldEntity->toArray();

        // Update old entity
        $oldEntity->end_date = $endDateObj;

        $where = ['id' => $oldEntity->id];
        $this->updateStatus('CHANGE_IN_TYPE', $where);
        $this->save($oldEntity);
        // End

        // Update new entity
        $ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
        foreach ($ignoreFields as $key => $field) {
            unset($newRequestData[$field]);
        }
        $newRequestData['start_date'] = $newStartDateObj;
        $newRequestData['floor_type_id'] = $newFloorTypeId;
        $newRequestData['previous_institution_floor_id'] = $oldEntity->id;
        $newEntity = $this->newEntity($newRequestData, ['validate' => false]);
        $newEntity = $this->save($newEntity, ['checkExisting' => false]);
        // End

        $url = $this->url('edit');
        unset($url['type']);
        unset($url['edit_type']);
        $url[1] = $this->paramsEncode(['id' => $newEntity->id]);

        return $this->controller->redirect($url);
    }

    private function updateStatus($code, $primaryKey)
    {
        $statuses = $this->FloorStatuses->findCodeList();
        $status = $statuses[$code];

        $entity = $this->get($primaryKey);
        $entity->floor_status_id = $status;
        $this->save($entity);
    }

    public function findInUse(Query $query, array $options)
    {
        $institutionId = isset($options['institution_id']) ? $options['institution_id'] : null;
// POCOR-8037 removed academic period code
        $inUseId = $this->FloorStatuses->getIdByCode('IN_USE');

        $query->where([
            $this->aliasField('institution_id') => $institutionId,
            // POCOR-8037 removed academic period code
            $this->aliasField('floor_status_id') => $inUseId
        ]);

        return $query;
    }

    // POCOR-8037 removed academic period code
}
