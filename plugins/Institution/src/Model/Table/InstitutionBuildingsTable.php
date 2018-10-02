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
    private $currentAcademicPeriod = null;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('BuildingStatuses', ['className' => 'Infrastructure.InfrastructureStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('BuildingTypes', ['className' => 'Infrastructure.BuildingTypes']);
        $this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
        $this->belongsTo('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'institution_land_id']);
        $this->belongsTo('PreviousBuildings', ['className' => 'Institution.InstitutionBuildings', 'foreignKey' => 'previous_institution_building_id']);
        $this->belongsTo('InfrastructureOwnership', ['className' => 'FieldOption.InfrastructureOwnerships']);
        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'dependent' => true]);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
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

        $this->Levels = TableRegistry::get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $this->Levels->find('list')->toArray();
        $this->accessibilityOptions = $this->getSelectOptions('InstitutionAssets.accessibility');
        $this->accessibilityTooltip = $this->getMessage('InstitutionInfrastructures.accessibilityOption');
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
            ->notEmpty('building_type_id');
        ;
    }

    public function validationSavingByAssociation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.AcademicPeriods.afterSave'] = 'academicPeriodAfterSave';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->Navigation->substituteCrumb(__('Institution Buildings'), __('Institution Buildings'));
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
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

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
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

    public function onGetInfrastructureLevel(Event $event, Entity $entity)
    {
        return $this->levelOptions[$this->buildingLevel];
    }

    public function onGetAccessibility(Event $event, Entity $entity)
    {
        return $this->accessibilityOptions[$entity->accessibility];
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
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
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('infrastructure_condition_id', ['visible' => false]);
        $this->field('previous_institution_building_id', ['visible' => false]);

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

        // Academic Period
        list($periodOptions, $selectedPeriod) = array_values($this->getPeriodOptions());
        $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        // End

        // Building Types
        list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true]));
        if ($selectedType != '-1') {
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
        $query->contain(['AcademicPeriods', 'InstitutionLands', 'BuildingTypes', 'InfrastructureConditions']);
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
            $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');
            $endOfUsageId = $this->BuildingStatuses->getIdByCode('END_OF_USAGE');

            if ($entity->building_status_id == $inUseId) {
                $session->write($sessionKey, $this->alias().'.in_use.restrictEdit');
            } elseif ($entity->building_status_id == $endOfUsageId) {
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

                // Not allowed to change building type in the same day
                if ($diff->days == 0) {
                    $session->write($sessionKey, $this->alias().'.change_in_building_type.restrictEdit');

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

        $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->BuildingStatuses->getIdByCode('END_OF_USAGE');

        if (!$isDeletable) {
            $session = $this->request->session();
            $sessionKey = $this->registryAlias() . '.warning';
            if ($entity->building_status_id == $inUseId) {
                $session->write($sessionKey, $this->alias().'.in_use.restrictDelete');
            } elseif ($entity->building_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->alias().'.end_of_usage.restrictDelete');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }

        $extra['excludedModels'] = [
            $this->CustomFieldValues->alias(),
            $this->InstitutionFloors->alias()
        ];

        // check if the same building is copy from / copy to other academic period, then not allow user to delete
        $resultQuery = $this->find();
        $results = $resultQuery
            ->select([
                'academic_period_name' => 'AcademicPeriods.name',
                'count' => $resultQuery->func()->count($this->aliasField('id'))
            ])
            ->contain(['AcademicPeriods'])
            ->where([
                $this->aliasField('code') => $entity->code,
                $this->aliasField('building_status_id') => $inUseId,
                $this->aliasField('id <> ') => $entity->id
            ])
            ->group($this->aliasField('academic_period_id'))
            ->order([$this->aliasField('start_date')])
            ->all();

        if (!$results->isEmpty()) {
            $extra['excludedModels'][] = $this->InstitutionFloors->alias();

            foreach ($results as $obj) {
                $title = $this->alias() . ' - ' . $obj->academic_period_name;
                $extra['associatedRecords'][] = [
                    'model' => $title,
                    'count' => $obj->count
                ];
            }
        } else {
            $floorQuery = $this->InstitutionFloors
                ->find()
                ->where([
                    $this->InstitutionFloors->aliasField('institution_building_id') => $entity->id,
                    $this->InstitutionFloors->aliasField('floor_status_id IN ') => [$inUseId, $endOfUsageId]
                ])
                ->all();

            $extra['associatedRecords'][] = [
                'model' => $this->InstitutionFloors->alias(),
                'count' => $floorQuery->count()
            ];
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

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
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

    public function onUpdateFieldBuildingStatusId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } elseif ($action == 'add') {
            $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');
            $attr['value'] = $inUseId;
        }

        return $attr;
    }

    public function onUpdateFieldArea(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $selectedEditType = $request->query('edit_type');
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

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

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldBuildingTypeId(Event $event, array $attr, $action, Request $request)
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
            $selectedEditType = $request->query('edit_type');
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

    public function onUpdateFieldYearAcquired(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->getYearOptionsByConfig();
            $attr['type'] = 'select';
        } elseif ($action == 'edit') {
            $attr['options'] = $this->getYearOptionsByConfig();
            $attr['type'] = 'select';
            $selectedEditType = $request->query('edit_type');
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldYearDisposed(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->getYearOptionsByConfig();
            $attr['type'] = 'select';
        } elseif ($action == 'edit') {
            $attr['options'] = $this->getYearOptionsByConfig();
            $attr['type'] = 'select';
            $selectedEditType = $request->query('edit_type');
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
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

    public function onUpdateFieldInfrastructureOwnershipId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $selectedEditType = $request->query('edit_type');
            if (!$this->canUpdateDetails) {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldNewBuildingType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $selectedEditType = $request->query('edit_type');
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

    public function addEditOnChangeBuildingType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['type']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('building_type_id', $request->data[$this->alias()])) {
                    $selectedType = $request->data[$this->alias()]['building_type_id'];
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
            'change_type', 'institution_land_id', 'academic_period_id', 'institution_id', 'code', 'name', 'building_type_id', 'area', 'building_status_id', 'infrastructure_ownership_id', 'year_acquired', 'year_disposed', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id', 'previous_institution_building_id', 'new_building_type', 'new_start_date'
        ]);

        $this->field('change_type');
        $this->field('building_status_id', ['type' => 'hidden']);
        $this->field('institution_land_id', ['entity' => $entity]);
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('institution_id');
        $this->field('code');
        $this->field('name');
        $this->field('area');
        $this->field('year_acquired');
        $this->field('year_disposed');
        $this->field('building_type_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);
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

    public function onUpdateFieldInstitutionLandId(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'hidden';
        if ($action == 'add') {
            $attr['value'] = $this->getQueryString('institution_land_id');
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

    public function onGetCode(Event $event, Entity $entity)
    {
        $institutionId = $this->request->param('institutionId');
        $url = [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => 'InstitutionFloors',
            'institutionId' => $institutionId,
            'index'
        ];
        $url = array_merge($url, $this->request->query);
        $url = $this->setQueryString($url, ['institution_building_id' => $entity->id, 'institution_building_name' => $entity->name]);
        return $event->subject()->HtmlField->link($entity->code, $url);
    }

    private function getAutoGenerateCode($parentId)
    {
        $codePrefix = '';
        $lastSuffix = '00';
        $conditions = [];
        // has Parent then get the ID of the parent then followed by counter
        $parentData = $this->InstitutionLands->find()
            ->where([
                $this->InstitutionLands->aliasField($this->InstitutionLands->primaryKey()) => $parentId
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
        $crumbs[] = [
            'name' => $this->getQueryString('institution_land_name')
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

        $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->BuildingStatuses->getIdByCode('END_OF_USAGE');

        if ($entity->building_status_id == $inUseId) {
            // If is in use, not allow to delete if the buildings is appear in other academic period
            $count = $this
                ->find()
                ->where([
                    $this->aliasField('previous_institution_building_id') => $entity->id
                ])
                ->count();

            if ($count > 0) {
                $isEditable = false;
            }
        } elseif ($entity->building_status_id == $endOfUsageId) {    // If already end of usage, not allow to edit or delete
            $isEditable = false;
            $isDeletable = false;
        }

        return compact('isEditable', 'isDeletable');
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

        $typeOptions = $this->BuildingTypes
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->find('visible')
            ->toArray();
        if ($withAll && count($typeOptions) > 1) {
            $typeOptions = ['-1' => __('All Building Types')] + $typeOptions;
        }
        $selectedType = $this->queryString('type', $typeOptions);
        $this->advancedSelectOptions($typeOptions, $selectedType);

        return compact('typeOptions', 'selectedType');
    }

    public function getStatusOptions($params = [])
    {
        $conditions = array_key_exists('conditions', $params) ? $params['conditions'] : [];
        $withAll = array_key_exists('withAll', $params) ? $params['withAll'] : false;

        $statusOptions = $this->BuildingStatuses
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
        $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : null;
        $academicPeriodId = array_key_exists('academic_period_id', $options) ? $options['academic_period_id'] : null;
        $inUseId = $this->BuildingStatuses->getIdByCode('IN_USE');

        $query->where([
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('building_status_id') => $inUseId
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
