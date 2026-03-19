<?php
namespace Institution\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use Cake\ORM\ResultSet;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Routing\Router;
use Cake\Http\ServerRequest;
class InstitutionBuildingsTable extends ControllerActionTable
{
    use OptionsTrait;
    const IN_USE = 1;
    const UPDATE_DETAILS = 1;    // In Use
    const END_OF_USAGE = 2;
    const CHANGE_IN_TYPE = 3;

    private $Levels = null;
    private $levelOptions = [];
    private $buildingLevel = null;

    private $canUpdateDetails = true;
    // POCOR-8037 removed academic period code

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('BuildingStatuses', ['className' => 'Infrastructure.InfrastructureStatuses']);
        // POCOR-8037 removed academic period code
        $this->belongsTo('BuildingTypes', ['className' => 'Infrastructure.BuildingTypes']);
        $this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'institution_land_id']);
        $this->belongsTo('PreviousBuildings', ['className' => 'Institution.InstitutionBuildings', 'foreignKey' => 'previous_institution_building_id']);
        $this->belongsTo('InfrastructureOwnership', ['className' => 'FieldOption.InfrastructureOwnerships']);
        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'dependent' => true]);

        // POCOR-8037 removed academic period code
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        // POCOR-9344 restored
        $this->addBehavior('CustomField.Record', [
            'fieldKey' => 'infrastructure_custom_field_id',
            'tableColumnKey' => null,
            'tableRowKey' => null,
            'fieldClass' => ['className' => 'Infrastructure.BuildingCustomFields'],
            'formKey' => 'infrastructure_custom_form_id',
            'filterKey' => 'infrastructure_custom_filter_id',
            'formFieldClass' => ['className' => 'Infrastructure.BuildingCustomFormsFields'],
            'formFilterClass' => ['className' => 'Infrastructure.BuildingCustomFormsFilters'],
            'recordKey' => 'institution_building_id',
            'fieldValueClass' => ['className' => 'Infrastructure.BuildingCustomFieldValues', 'foreignKey' => 'institution_building_id', 'dependent' => true],
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
            'appliedAction' => ['InstitutionBuildings'=>['id']]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmptyString('name') // POCOR-8037
            ->notEmptyString('code') // POCOR-8037
            ->notEmptyString('area') // POCOR-8037
            ->notEmptyString('accessibility') // POCOR-8037
            ->notEmptyString('year_acquired') // POCOR-8037
            ->notEmptyString('infrastructure_condition_id') // POCOR-8037
            ->notEmptyString('infrastructure_ownership_id') // POCOR-8037
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
                    'rule' => ['compareDateReverse', 'start_date', true],
                    'on' => function ($context) { //POCOR-8241 -- Date validation when start_date is not empty
                        return !empty($context['data']['start_date']);
                    }
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
                    return $this->validateCustomLandSize($value, 'Maximum_institution_infrastructure_building_size', $context);
                },
                'provider' => 'table',
                'last' => true
            ])
            ->requirePresence('new_building_type', function ($context) {
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
            ->notEmpty('building_type_id')
            ->notEmpty('infrastructure_ownership_id')
            ->notEmpty('infrastructure_condition_id')
            ->notEmpty('accessibility')
            ;
        ;
    }

    public function validationSavingByAssociation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator;
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
// POCOR-8037 removed academic period code
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //Start:POCOR-6693
        $this->field('area', ['attr' => ['label' => __('Size')]]);
        //End:POCOR-6693
        $this->Navigation->substituteCrumb(__('Institution Buildings'), __('Institution Buildings'));
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //Start:POCOR-7597
        if(!empty($entity['institution_land_id'])){
            $InstitutionLands = TableRegistry::getTableLocator()->get('Institution.InstitutionLands');
            $InstitutionLand = $InstitutionLands->get($entity['institution_land_id']);
        }
        if($entity['area'] >= $InstitutionLand['area']){

            if(Router::getRequest()->getParam('action') == "CopyData"){}
            else{//POCOR_7657
            $this->Alert->warning('InstitutionBuildings.sizeGreater', ['reset' => true]);
            return false;
            }
        }
        //End:POCOR-7597
        if (!$entity->isNew() && $entity->has('change_type')) {
            $editType = $entity->change_type;
            $statuses = $this->BuildingStatuses->find('list', ['keyField' => 'id', 'valueField' => 'code'])->toArray();
            $functionKey = Inflector::camelize(strtolower($statuses[$editType]));
            $functionName = "process$functionKey";
            if (method_exists($this, $functionName)) {
                $this->$functionName($entity);
            }
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // logic to copy custom fields (general only) where new building is created when change in building type
        if ($entity->isNew()) {
            $this->processCopy($entity);
        } elseif ($entity->building_status_id == $this->BuildingStatuses->getIdByCode('END_OF_USAGE')) {
            $floorEntities = $this->InstitutionFloors
                ->find()
                ->where([
                    $this->InstitutionFloors->aliasField('institution_building_id') => $entity->id,
                    $this->InstitutionFloors->aliasField('floor_status_id') => SELF::IN_USE
                ])
                ->toArray();
            foreach ($floorEntities as $floorEntity) {
                $floorEntity->change_type = SELF::END_OF_USAGE;
                $floorEntity->end_date = $entity->end_date;
                $this->InstitutionFloors->save($floorEntity);
            }
        }
    }

    public function onGetInfrastructureLevel(EventInterface $event, Entity $entity)
    {
        return $this->levelOptions[$this->buildingLevel];
    }

    public function onGetAccessibility(EventInterface $event, Entity $entity)
    {
        return $this->accessibilityOptions[$entity->accessibility];
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'institution_id') {
            return __('Owner');
        } else if($field == 'start_date'){
            return __('Start Date');
        } else if($field == 'comment'){
            return __('Comment');
        } else if($field == 'infrastructure_level'){
            return __('Infrastructure Level');
        } else if($field == 'building_status_id'){
            return __('Building Status');
        } else if($field == 'modified'){
            return __('Modified');
        } else if($field == 'modified_user_id'){
            return __('Modified By');
        } else if($field == 'created'){
            return __('Created');
        } else if($field == 'created_user_id'){
            return __('Created By');
        } else if($field == 'end_date'){
            return __('End Date');
        } else if($field == 'new_building_type'){
            return __('New Building Type');
        } else if($field == 'new_start_date'){
            return __('New Start Date');
        } else if($field == 'accessibility'){
            return __('Accessibility');
        } elseif ($field == 'to_be_deleted') {
            return __('To be Deleted ');
        } elseif ($field == 'associated_records') {
            return __('Associated Records');
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
        // POCOR-8037 fix remove button
        $queryString = $buttons['remove']['url']['1'];
        $params = $this->paramsDecode($queryString);
        $params['id'] = $entity->id;
        $queryString = $this->paramsEncode($params);
        $buttons['remove']['url']['1'] = $queryString;
        // POCOR-8037 end
        return $buttons;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->buildingLevel = $this->Levels->getFieldByCode('BUILDING', 'id');
        $this->setFieldOrder(['code', 'name', 'institution_id', 'infrastructure_level', 'building_type_id', 'building_status_id']);
        $this->fields['area']['visible'] = false;
        $this->fields['comment']['visible'] = false;
        $this->fields['infrastructure_ownership_id']['visible'] = false;
        $this->fields['year_acquired']['visible'] = false;
        $this->fields['year_disposed']['visible'] = false;
        $this->field('accessibility', ['visible' => false]);
        $this->field('institution_id');
        $this->field('infrastructure_level', ['after' => 'name']);
        $this->field('start_date', ['visible' => false]);
        $this->field('start_year', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('end_year', ['visible' => false]);
        $this->field('institution_land_id', ['visible' => false]);
        // POCOR-8037 removed academic period code
        $this->field('infrastructure_condition_id', ['visible' => false]);
        $this->field('previous_institution_building_id', ['visible' => false]);

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

        $parentId = $this->getQueryString('institution_land_id');
        $parentRecord = $this->InstitutionLands->get($parentId)->toArray();
        if (isset($extra['toolbarButtons']['add'])) {
            if ($parentRecord['land_status_id'] == SELF::END_OF_USAGE) {
                unset($extra['toolbarButtons']['add']);
            }
        }
        if (!is_null($parentId)) {
            $query->where([$this->aliasField('institution_land_id') => $parentId]);
        } else {
            $query->where([$this->aliasField('institution_land_id IS NULL')]);
        }

// POCOR-8037 removed academic period code
        // Building Types
        list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true]));
        if ($selectedType && $selectedType != '-1') { // POCOR-9074
            $query->where([$this->aliasField('building_type_id') => $selectedType]);
        }
        $this->controller->set(compact('typeOptions', 'selectedType'));
        // End

        // Building Statuses
        list($statusOptions, $selectedStatus) = array_values($this->getStatusOptions([
            'conditions' => [
                'code IN' => ['IN_USE', 'END_OF_USAGE']
            ],
            'withAll' => true
        ]));
        if ($selectedStatus != '-1') {
            $query->where([$this->aliasField('building_status_id') => $selectedStatus]);
        } else {
            // default show In Use and End Of Usage
            $query->matching('BuildingStatuses', function ($q) {
                return $q->where([
                    'BuildingStatuses.code IN' => ['IN_USE', 'END_OF_USAGE']
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
        $query->contain([
// POCOR-8037 removed academic period code
            'InstitutionLands', 'BuildingTypes', 'InfrastructureConditions']);
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
            $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');
            $endOfUsageId = $this->BuildingStatuses->getIdByCode('END_OF_USAGE');

            if ($entity->building_status_id == $inUseId) {
                $session->write($sessionKey, $this->getAlias().'.in_use.restrictEdit');
            } elseif ($entity->building_status_id == $endOfUsageId) {
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

                // Not allowed to change building type in the same day
                if ($diff->days == 0) {
                    $session->write($sessionKey, $this->getAlias().'.change_in_building_type.restrictEdit');

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

        $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->BuildingStatuses->getIdByCode('END_OF_USAGE');

        if (!$isDeletable) {
            $session = $this->request->getSession();
            $sessionKey = $this->getRegistryAlias() . '.warning';
            if ($entity->building_status_id == $inUseId) {
                $session->write($sessionKey, $this->getAlias().'.in_use.restrictDelete');
            } elseif ($entity->building_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->getAlias().'.end_of_usage.restrictDelete');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }

        $extra['excludedModels'] = [
            $this->CustomFieldValues->getAlias(),
            $this->InstitutionFloors->getAlias()
        ];

        // POCOR-8037 removed academic period code

        $floorQuery = $this->InstitutionFloors
            ->find()
            ->where([
                $this->InstitutionFloors->aliasField('institution_building_id') => $entity->id,
                $this->InstitutionFloors->aliasField('floor_status_id IN ') => [$inUseId, $endOfUsageId]
            ])
            ->all();

        $extra['associatedRecords'][] = [
            'model' => $this->InstitutionFloors->getAlias(),
            'count' => $floorQuery->count()
        ];
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

    public function onUpdateFieldBuildingStatusId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } elseif ($action == 'add') {
            $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');
            $attr['value'] = $inUseId;
        }

        return $attr;
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

    // POCOR-8037 removed academic period code

    public function onUpdateFieldCode(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $parentId = $this->getQueryString('institution_land_id');
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

    public function onUpdateFieldBuildingTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $classificationOptions = $this->getSelectOptions('RoomTypes.classifications');
            $buildingTypeOptions = $this->BuildingTypes
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->find('visible')
                    ->order([
                        $this->BuildingTypes->aliasField('order') => 'ASC'
                    ])
                    ->toArray();

            $attr['options'] = $buildingTypeOptions;
            $attr['onChangeReload'] = 'changeBuildingType';
        } elseif ($action == 'edit') {
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::END_OF_USAGE) {
                $attr['type'] = 'hidden';
            } else {
                $entity = $attr['entity'];

                $attr['type'] = 'readonly';
                $attr['value'] = $entity->building_type->id;
                $attr['attr']['value'] = $entity->building_type->name;
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

    public function onUpdateFieldYearAcquired(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->getYearOptionsByConfig();
            $attr['type'] = 'select';
        } elseif ($action == 'edit') {
            $attr['options'] = $this->getYearOptionsByConfig();
            $attr['type'] = 'select';
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldYearDisposed(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->getYearOptionsByConfig();
            $attr['type'] = 'select';
        } elseif ($action == 'edit') {
            $attr['options'] = $this->getYearOptionsByConfig();
            $attr['type'] = 'select';
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

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

    public function onUpdateFieldInfrastructureOwnershipId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldNewBuildingType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $selectedEditType = $this->request->getAttribute('params')['?']['edit_type'];
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                $buildingTypeOptions = $this->BuildingTypes
                    ->find('list')
                    ->find('visible')
                    ->where([
                        $this->BuildingTypes->aliasField('id <>') => $entity->building_type_id
                    ])
                    ->toArray();

                $attr['visible'] = true;
                $attr['options'] = $buildingTypeOptions;
                $attr['select'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldNewStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

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

    public function addEditOnChangeBuildingType(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->getQuery['type']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('building_type_id', $request->getData($this->getAlias()))) {
                    $selectedType = $request->getData($this->getAlias())['building_type_id'];
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
            // POCOR-8037 removed academic period code
            'change_type', 'institution_land_id',  'institution_id', 'code', 'name', 'building_type_id', 'area', 'building_status_id', 'infrastructure_ownership_id', 'year_acquired', 'year_disposed', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id', 'previous_institution_building_id', 'new_building_type', 'new_start_date'
        ]);

        $this->field('change_type');
        $this->field('building_status_id', ['type' => 'hidden']);
        $this->field('institution_land_id', ['entity' => $entity]);
        // POCOR-8037 removed academic period code
        $this->field('institution_id');
        $this->field('code');
        $this->field('name');
        $this->field('area');
        $this->field('year_acquired');
        $this->field('year_disposed');
        $this->field('building_type_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);

        //POCOR-6760[start]
        if($entity->building_status_id != self::END_OF_USAGE) {
            $this->field('end_date', ['entity' => $entity]);
        }
        //POCOR-6760[end]

        $this->field('infrastructure_condition_id', ['type' => 'select']);
        $this->field('infrastructure_ownership_id', ['type' => 'select']);
        $this->field('previous_institution_building_id', ['type' => 'hidden']);
        $this->field('new_building_type', ['type' => 'select', 'visible' => false, 'entity' => $entity]);
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

    public function onUpdateFieldInstitutionLandId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['type'] = 'hidden';
        if ($action == 'add') {
            $attr['value'] = $this->getQueryString('institution_land_id');
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

    public function onGetCode(EventInterface $event, Entity $entity)
    {
        $institutionId = $this->request->getParam('institutionId');
        $params = $this->getQueryString();
        if(!isset($params['institution_id'])){ // POCOR-8037 fixed
            $params['institution_id'] = $institutionId;
        }
//        dd($entity);
        $params['institution_building_id'] = $entity->id;
        $params['institution_building_name'] = $entity->name;

        $encodedQueryString = $this->paramsEncode($params);
        $url = [
            'plugin' => $this->controller->getPlugin(),
            'controller' => $this->controller->getName(),
            'action' => 'InstitutionFloors',
            '0' => 'index',
            '1' => $encodedQueryString // POCOR-8037 fixed
        ];
        return $event->getSubject()->HtmlField->link($entity->code, $url);
    }

    private function getAutoGenerateCode($parentId)
    {
        $codePrefix = '';
        $lastSuffix = '00';
        $conditions = [];
        // has Parent then get the ID of the parent then followed by counter
        $parentData = $this->InstitutionLands->find()
            ->where([
                $this->InstitutionLands->aliasField($this->InstitutionLands->getPrimaryKey()) => $parentId
            ])
            ->first();

        $codePrefix = $parentData->code;

        // $conditions[] = $this->aliasField('code')." LIKE '" . $codePrefix . "%'";
        $lastRecord = $this->find()
            ->where([
                $this->aliasField('institution_land_id') => $parentId,
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
        $encodedQueryString = $this->paramsEncode($params);
        $crumbs[] = [
            // POCOR-8037 fixed crumbs
            'name' => $params['institution_land_name'],
            'url' => [
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'action' => 'InstitutionLands',
                '0' => 'index',
                '1' => $encodedQueryString
            ]
        ];
        $toolbarElements = ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => ['encodedQueryString' => $encodedQueryString, 'crumbs'=>$crumbs], 'options' => [], 'order' => 1];

        return $toolbarElements;
    }

    private function addControlFilterElement()
    {
        list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true])); // POCOR-8037
        $toolbarElements = ['name' => 'Institution.Infrastructure/controls', 'data' => compact('typeOptions', 'selectedType'), 'options' => [], 'order' => 2];
        return $toolbarElements;
    }

    private function checkIfCanEditOrDelete($entity)
    {
        $isEditable = true;
        $isDeletable = true;

        $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->BuildingStatuses->getIdByCode('END_OF_USAGE');

        if ($entity->building_status_id == $endOfUsageId) { // POCOR-8037 removed academic period code
            $isEditable = false;
            $isDeletable = false;
        }
        if ($entity->building_status_id == $inUseId) { // POCOR-8037 removed academic period code
            $isEditable = true;
            $isDeletable = true;
        }

        return compact('isEditable', 'isDeletable');
    }

    // POCOR-8037 removed academic period code

    public function getTypeOptions($params = [])
    {
        $withAll = isset($params['withAll']) ? $params['withAll'] : false;

        $typeOptions = $this->BuildingTypes
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->find('visible')
            ->toArray();
        if ($withAll && count($typeOptions) > 1) {
            $typeOptions = ['-1' => __('All Building Types')] + $typeOptions;
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

        $statusOptions = $this->BuildingStatuses
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

    public function processCopy(Entity $entity)
    {
        // if is new and building status of previous building usage is change in building type then copy all general custom fields
        if ($entity->has('previous_institution_building_id') && !is_null($entity->previous_institution_building_id)) {
            $copyFrom = $entity->previous_institution_building_id;
            $copyTo = $entity->id;

            $previousEntity = $this->get($copyFrom);
            $changeInTypeId = $this->BuildingStatuses->getIdByCode('CHANGE_IN_TYPE');
            if ($previousEntity->building_status_id == $changeInTypeId) {
                // third parameters set to true means copy general only
                $this->copyCustomFields($copyFrom, $copyTo, true);
                $this->InstitutionFloors->updateAll([
                    'institution_building_id' => $copyTo
                ], [
                    'institution_building_id' => $copyFrom
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
        $newBuildingTypeId = $entity->new_building_type;

        $oldEntity = $this->find()->where(['id' => $entity->id])->first();
        $newRequestData = $oldEntity->toArray();

        // Update old entity
        $oldEntity->end_date = $endDateObj;

        $where = ['id' => $oldEntity->id];
        $this->updateStatus('CHANGE_IN_TYPE', $where);
        // End

        // Update new entity
        $ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
        foreach ($ignoreFields as $key => $field) {
            unset($newRequestData[$field]);
        }
        $newRequestData['start_date'] = $newStartDateObj;
        $newRequestData['building_type_id'] = $newBuildingTypeId;
        $newRequestData['previous_institution_building_id'] = $oldEntity->id;
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
        $statuses = $this->BuildingStatuses->findCodeList();
        $status = $statuses[$code];

        $entity = $this->get($primaryKey);
        $entity->building_status_id = $status;
        $this->save($entity);
    }

    public function findInUse(Query $query, array $options)
    {
        $institutionId = isset($options['institution_id']) ? $options['institution_id'] : null;
// POCOR-8037 removed academic period code
        $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');

        $query->where([
            $this->aliasField('institution_id') => $institutionId,
            // POCOR-8037 removed academic period code
            $this->aliasField('building_status_id') => $inUseId
        ]);

        return $query;
    }

// POCOR-8037 removed academic period code
}
