<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;
use DateTime;

class LandsTable extends ControllerActionTable
{
    use OptionsTrait;
    const IN_USE = 1;
    const UPDATE_DETAILS = 1;// In Use
    const END_OF_USAGE = 2;
    const CHANGE_IN_TYPE = 3;

    private $Levels = null;
    private $levelOptions = [];
    private $landLevel = null;

    private $canUpdateDetails = true;
    private $currentAcademicPeriod = null;
    private $_dynamicFieldName = 'custom_field_data';

    public function initialize(array $config)
    {
        $this->table('institution_lands');
        parent::initialize($config);

        $this->belongsTo('LandStatuses', ['className' => 'Infrastructure.InfrastructureStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('LandTypes', ['className' => 'Infrastructure.LandTypes']);
        $this->belongsTo('InfrastructureOwnership', ['className' => 'FieldOption.InfrastructureOwnerships']);
        $this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
        $this->belongsTo('PreviousLands', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'previous_institution_land_id']);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'dependent' => true]);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        // $this->addBehavior('CustomField.Record', [
        //     'fieldKey' => 'infrastructure_custom_field_id',
        //     'tableColumnKey' => null,
        //     'tableRowKey' => null,
        //     'fieldClass' => ['className' => 'Infrastructure.LandCustomFields'],
        //     'formKey' => 'infrastructure_custom_form_id',
        //     'filterKey' => 'infrastructure_custom_filter_id',
        //     'formFieldClass' => ['className' => 'Infrastructure.LandCustomFormsFields'],
        //     'formFilterClass' => ['className' => 'Infrastructure.LandCustomFormsFilters'],
        //     'recordKey' => 'institution_land_id',
        //     'fieldValueClass' => ['className' => 'Infrastructure.LandCustomFieldValues', 'foreignKey' => 'institution_land_id', 'dependent' => true],
        //     'tableCellClass' => null
        // ]);
        $this->addBehavior('Institution.InfrastructureShift');

        $this->Levels = TableRegistry::get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $this->Levels->find('list')->toArray();
        $this->accessibilityOptions = $this->getSelectOptions('InstitutionAssets.accessibility');
        $this->accessibilityTooltip = $this->getMessage('InstitutionInfrastructures.accessibilityOption');
        $this->effectiveDateTooltip = $this->getMessage('InstitutionInfrastructures.effectiveDate');
        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Excel', [
            'excludes' => ['start_year', 'end_year', 'previous_institution_student_id'],
            'pages' => ['index'],
            'autoFields' => false
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('name')
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
            ->requirePresence('new_land_type', function ($context) {
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
            ->notEmpty('land_type_id');
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
        $this->Navigation->substituteCrumb(__('Institution Lands'), __('Institution Lands'));
        $this->field('name', ['visible' => false]);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $data['name'] = $data['code'];
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->has('change_type')) {
            $editType = $entity->change_type;
            $statuses = $this->LandStatuses->find('list', ['keyField' => 'id', 'valueField' => 'code'])->toArray();
            $functionKey = Inflector::camelize(strtolower($statuses[$editType]));
            $functionName = "process$functionKey";

            if (method_exists($this, $functionName)) {
                $this->$functionName($entity);
            }
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // logic to copy custom fields (general only) where new land is created when change in land type
        if ($entity->isNew()) {
            $this->processCopy($entity);
        } elseif ($entity->land_status_id == $this->LandStatuses->getIdByCode('END_OF_USAGE')) {
            $buildingEntities = $this->InstitutionBuildings
                ->find()
                ->where([
                    $this->InstitutionBuildings->aliasField('institution_land_id') => $entity->id,
                    $this->InstitutionBuildings->aliasField('building_status_id') => SELF::IN_USE
                ])
                ->toArray();
            foreach ($buildingEntities as $buildingEntity) {
                $buildingEntity->change_type = SELF::END_OF_USAGE;
                $buildingEntity->end_date = $entity->end_date;
                $this->InstitutionBuildings->save($buildingEntity);
            }
        }
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        $institutionId = $this->request->param('institutionId');
        $url = [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => 'InstitutionBuildings',
            'institutionId' => $institutionId
        ];
        $url = array_merge($url, $this->request->query);
        $url = $this->setQueryString($url, ['institution_land_id' => $entity->id, 'institution_land_name' => $entity->code]);

        return $event->subject()->HtmlField->link($entity->code, $url);
    }

    public function onGetInfrastructureLevel(Event $event, Entity $entity)
    {
        return $this->levelOptions[$this->landLevel];
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
        $this->landLevel = $this->Levels->getFieldByCode('LAND', 'id');
        $this->setFieldOrder(['code', 'name', 'institution_id', 'infrastructure_level', 'land_type_id', 'land_status_id']);
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
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('infrastructure_condition_id', ['visible' => false]);
        $this->field('previous_institution_land_id', ['visible' => false]);

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

        // Academic Period
        list($periodOptions, $selectedPeriod) = array_values($this->getPeriodOptions());
        $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        // End

        // Land Types
        list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true]));
        if ($selectedType != '-1') {
            $query->where([$this->aliasField('land_type_id') => $selectedType]);
        }
        $this->controller->set(compact('typeOptions', 'selectedType'));
        // End

        // Land Statuses
        list($statusOptions, $selectedStatus) = array_values($this->getStatusOptions([
            'conditions' => [
                'code IN' => ['IN_USE', 'END_OF_USAGE']
            ],
            'withAll' => true
        ]));
        if ($selectedStatus != '-1') {
            $query->where([$this->aliasField('land_status_id') => $selectedStatus]);
        } else {
            // default show In Use and End Of Usage
            $query->matching('LandStatuses', function ($q) {
                return $q->where([
                    'LandStatuses.code IN' => ['IN_USE', 'END_OF_USAGE']
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
        $query->contain(['AcademicPeriods', 'LandTypes', 'InfrastructureConditions']);
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
            $inUseId = $this->LandStatuses->getIdByCode('IN_USE');
            $endOfUsageId = $this->LandStatuses->getIdByCode('END_OF_USAGE');

            if ($entity->land_status_id == $inUseId) {
                $session->write($sessionKey, $this->alias().'.in_use.restrictEdit');
            } elseif ($entity->land_status_id == $endOfUsageId) {
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

                // Not allowed to change land type in the same day
                if ($diff->days == 0) {
                    $session->write($sessionKey, $this->alias().'.change_in_land_type.restrictEdit');

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

        $inUseId = $this->LandStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->LandStatuses->getIdByCode('END_OF_USAGE');

        if (!$isDeletable) {
            $session = $this->request->session();
            $sessionKey = $this->registryAlias() . '.warning';
            if ($entity->land_status_id == $inUseId) {
                $session->write($sessionKey, $this->alias().'.in_use.restrictDelete');
            } elseif ($entity->land_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->alias().'.end_of_usage.restrictDelete');
            }

            $url = $this->url('index', 'QUERY');
            $event->stopPropagation();

            return $this->controller->redirect($url);
        }

        $entity->name = $entity->code;
        $extra['excludedModels'] = [
            //$this->CustomFieldValues->alias(),
            $this->InstitutionBuildings->alias()
        ];

        // check if the same land is copy from / copy to other academic period, then not allow user to delete
        //POCOR-5330 starts
        $currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();
        $this->currentAcademicPeriod = $this->AcademicPeriods->get($currentAcademicPeriodId);
        $resultQuery = $this->find()->where([$this->aliasField('academic_period_id') => $currentAcademicPeriodId]);
        //POCOR-5330 ends
        $results = $resultQuery
            ->select([
                'academic_period_name' => 'AcademicPeriods.name',
                'count' => $resultQuery->func()->count($this->aliasField('id'))
            ])
            ->contain(['AcademicPeriods'])
            ->where([
                $this->aliasField('code') => $entity->code,
                $this->aliasField('land_status_id') => $inUseId,
                $this->aliasField('id <> ') => $entity->id
            ])
            ->group($this->aliasField('academic_period_id'))
            ->order([$this->aliasField('start_date')])
            ->all();

        if (!$results->isEmpty()) {
            foreach ($results as $obj) {
                $title = $this->alias() . ' - ' . $obj->academic_period_name;
                $extra['associatedRecords'][] = [
                    'model' => $title,
                    'count' => $obj->count
                ];
            }
        } else {
            $buildingQuery = $this->InstitutionBuildings
                ->find()
                ->where([
                    $this->InstitutionBuildings->aliasField('institution_land_id') => $entity->id,
                    $this->InstitutionBuildings->aliasField('building_status_id IN ') => [$inUseId, $endOfUsageId]
                ])
                ->all();

            $extra['associatedRecords'][] = [
                'model' => $this->InstitutionBuildings->alias(),
                'count' => $buildingQuery->count()
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

    public function onUpdateFieldLandStatusId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } elseif ($action == 'add') {
            $inUseId = $this->LandStatuses->getIdByCode('IN_USE');
            $attr['value'] = $inUseId;
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
            $parentId = $request->query('parent');
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

    public function onUpdateFieldLandTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $landTypeOptions = $this->LandTypes
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'name'
                ])
                ->find('visible')
                ->order([
                    $this->LandTypes->aliasField('order') => 'ASC'
                ])
                ->toArray();

            $attr['options'] = $landTypeOptions;
            $attr['onChangeReload'] = 'changeLandType';
        } elseif ($action == 'edit') {
            $selectedEditType = $request->query('edit_type');
            if ($selectedEditType == self::END_OF_USAGE) {
                $attr['type'] = 'hidden';
            } else {
                $entity = $attr['entity'];

                $attr['type'] = 'readonly';
                $attr['value'] = $entity->land_type->id;
                $attr['attr']['value'] = $entity->land_type->name;
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

    public function onUpdateFieldNewLandType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $selectedEditType = $request->query('edit_type');
            if ($selectedEditType == self::CHANGE_IN_TYPE) {
                $landTypeOptions = $this->LandTypes
                    ->find('list')
                    ->find('visible')
                    ->where([
                        $this->LandTypes->aliasField('id <>') => $entity->land_type_id
                    ])
                    ->toArray();

                $attr['visible'] = true;
                $attr['options'] = $landTypeOptions;
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
                $attr['null'] = false;// for asterisk to appear
                $attr['type'] = 'readonly';
                $attr['value'] = $today->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($today);
            }
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

    public function addEditOnChangeLandType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['type']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('land_type_id', $request->data[$this->alias()])) {
                    $selectedType = $request->data[$this->alias()]['land_type_id'];
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
            'change_type', 'academic_period_id', 'institution_id', 'code', 'name', 'land_type_id', 'land_status_id', 'area', 'infrastructure_ownership_id', 'year_acquired', 'year_disposed', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id', 'previous_institution_land_id', 'new_land_type', 'new_start_date'
        ]);

        $this->field('change_type');
        $this->field('land_status_id', ['type' => 'hidden']);
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('institution_id');
        $this->field('code');
        $this->field('name');
        $this->field('area');
        $this->field('year_acquired');
        $this->field('year_disposed');
        $this->field('land_type_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('start_date', ['entity' => $entity,
            'attr' => [
                'label' => [
                    'text' => __('Effective date') . ' <i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="bottom" uib-tooltip="' . __($this->effectiveDateTooltip) . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                    'escape' => false,
                    'class' => 'tooltip-desc'
                ]
            ]
        ]);
        $this->field('end_date', ['entity' => $entity]);
        $this->field('infrastructure_ownership_id', ['type' => 'select']);
        $this->field('infrastructure_condition_id', ['type' => 'select']);
        $this->field('previous_institution_land_id', ['type' => 'hidden']);
        $this->field('new_land_type', ['type' => 'select', 'visible' => false, 'entity' => $entity]);
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
        $institutionId = $this->request->session()->read('Institution.Institutions.id');

        $institutionData = $this->Institutions->find()
            ->where([
                $this->Institutions->aliasField($this->Institutions->primaryKey()) => $institutionId
            ])
            ->select([$this->Institutions->aliasField('code')])
            ->first();

        $codePrefix = $institutionData->code.'-';

        // $conditions[] = $this->aliasField('code')." LIKE '" . $codePrefix . "%'";
        $lastRecord = $this->find()
            ->where([
                // $this->aliasField('institution_infrastructure_id') => $parentId,
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
        $toolbarElements = ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => compact('crumbs'), 'options' => [], 'order' => 1];

        return $toolbarElements;
    }

    private function addControlFilterElement()
    {
        $toolbarElements = ['name' => 'Institution.Infrastructure/land_controls', 'data' => compact('typeOptions', 'selectedType'), 'options' => [], 'order' => 2];

        return $toolbarElements;
    }

    private function checkIfCanEditOrDelete($entity)
    {
        $isEditable = true;
        $isDeletable = true;

        $inUseId = $this->LandStatuses->getIdByCode('IN_USE');
        $endOfUsageId = $this->LandStatuses->getIdByCode('END_OF_USAGE');

        if ($entity->land_status_id == $inUseId) {
            // If is in use, not allow to delete if the lands is appear in other academic period
            $count = $this
                ->find()
                ->where([
                    $this->aliasField('previous_institution_land_id') => $entity->id
                ])
                ->count();
            //edit data
            /*if ($count > 0) {
                $isEditable = false;
            }*/
        } elseif ($entity->land_status_id == $endOfUsageId) {// If already end of usage, not allow to edit or delete
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

        $typeOptions = $this->LandTypes
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->find('visible')
            ->toArray();
        if ($withAll && count($typeOptions) > 1) {
            $typeOptions = ['-1' => __('All Land Types')] + $typeOptions;
        }
        $selectedType = $this->queryString('type', $typeOptions);
        $this->advancedSelectOptions($typeOptions, $selectedType);

        return compact('typeOptions', 'selectedType');
    }

    public function getStatusOptions($params = [])
    {
        $conditions = array_key_exists('conditions', $params) ? $params['conditions'] : [];
        $withAll = array_key_exists('withAll', $params) ? $params['withAll'] : false;

        $statusOptions = $this->LandStatuses
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
        // if is new and land status of previous land usage is change in land type then copy all general custom fields
        if ($entity->has('previous_institution_land_id') && !is_null($entity->previous_institution_land_id)) {
            $copyFrom = $entity->previous_institution_land_id;
            $copyTo = $entity->id;
            $previousEntity = $this->get($copyFrom);
            $changeInTypeId = $this->LandStatuses->getIdByCode('CHANGE_IN_TYPE');
            if ($previousEntity->land_status_id == $changeInTypeId) {
                // third parameters set to true means copy general only
                $this->copyCustomFields($copyFrom, $copyTo, true);
                $this->InstitutionBuildings->updateAll([
                    'institution_land_id' => $copyTo
                ], [
                    'institution_land_id' => $copyFrom
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
        $newLandTypeId = $entity->new_land_type;

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
        $newRequestData['land_type_id'] = $newLandTypeId;
        $newRequestData['previous_institution_land_id'] = $oldEntity->id;
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
        $statuses = $this->LandStatuses->findCodeList();
        $status = $statuses[$code];

        $entity = $this->get($primaryKey);
        $entity->land_status_id = $status;
        $this->save($entity);
    }

    public function findInUse(Query $query, array $options)
    {
        $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : null;
        $academicPeriodId = array_key_exists('academic_period_id', $options) ? $options['academic_period_id'] : null;
        $inUseId = $this->LandStatuses->getIdByCode('IN_USE');

        $query->where([
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('land_status_id') => $inUseId
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

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $infrastructureLevels = TableRegistry::get('infrastructure_levels');
        $infrastructureLevelsData = $infrastructureLevels
            ->find()
            ->toArray();
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        //POCOR-7423 start
        $conditions=[];
        $ownerInstitutionIds = $this->getOwnerInstitutionId();

        if (!empty($ownerInstitutionIds)) {
            $conditions[$this->aliasField('institution_id IN ')] = $ownerInstitutionIds;
        }
        else
        {
            $conditions[$this->aliasField('institution_id ')] =$institutionId;
        }
        //POCOR-7423 end
        foreach($infrastructureLevelsData as $key => $val) {
            $infraType = $val->name .'s';
            $sheets[] = [
                'sheetData' => [
                    'institution_land_type' => $val
                ],
                'name' => $infraType,
                'table' => $this,
                'query' => $this
                    ->find()
                    ->where([
                        $conditions,//POCOR-7433
                    ]),
                'orientation' => 'landscape'
            ];
        }

    }

    public function onExcelGetAccessibility(Event $event, Entity $entity)
    {

        $accessibility = '';
        if($entity->land_infrastructure_accessibility == 1) {
            $accessibility ='Accessible';
        } else {
            $accessibility ='Not Accessible';
        }
        return $accessibility;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $infrastructureLevel  = $requestData->infrastructure_level; 
        $newFields = [];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'alias' => 'institution_code',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        // //POCOR-5698 two new columns added here
        $newFields[] = [
            'key' => 'ShiftOptions.name',
            'field' => 'shift_name',
            'type' => 'string',
            'label' => __('Institution Shift')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'region_code',
            'type' => 'string',
            'label' => 'Region Code'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'region_name',
            'type' => 'string',
            'label' => 'Region Name'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_status_name',
            'type' => 'string',
            'label' => __('Institution Status')
        ];

        // /**end here */
        $newFields[] = [
            'key' => '',
            'field' => 'land_infrastructure_code',
            'type' => 'string',
            'label' => __('Infrastructure Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'land_infrastructure_name',
            'type' => 'string',
            'label' => __('Infrastructure Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'land_start_date',
            'type' => 'string',
            'label' => __('Start Date')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'land_infrastructure_type',
            'type' => 'string',
            'label' => __('Infrastructure Type')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'land_infrastructure_ownership',
            'type' => 'string',
            'label' => __('Infrastructure Ownership')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'land_infrastructure_condition',
            'type' => 'string',
            'label' => __('Infrastructure Condition')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'land_infrastructure_status',
            'type' => 'string',
            'label' => __('Infrastructure Status')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'accessibility',
            'type' => 'string',
            'label' => __('Accessibility')
        ];

        //POCOR-6263 start
        $sheetData = $settings['sheet']['sheetData'];
        $landType = $sheetData['institution_land_type'];

        if($landType->name == 'Land'){
            $newFields[] = [
                'key' => '',
                'field' => 'land_area',
                'type' => 'string',
                'label' => __('Land Area')
            ];
        }

        if($landType->name == 'Building'){
            $newFields[] = [
                'key' => '',
                'field' => 'building_area',
                'type' => 'string',
                'label' => __('Building Area')
            ];
        }

        if($landType->name == 'Floor'){
            $newFields[] = [
                'key' => '',
                'field' => 'floor_area',
                'type' => 'string',
                'label' => __('Floor Area')
            ];
        }

        if($landType->name == 'Room'){
            $newFields[] = [
                'key' => '',
                'field' => 'room_area',
                'type' => 'string',
                'label' => __('Room Area')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'room_type',
                'type' => 'string',
                'label' => __('Room Type')
            ];
        }//POCOR-6263 ends
        /*POCOR-6264 starts*/
        $customModules = TableRegistry::get('custom_modules');
        $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
        if ($landType->name == 'Land') {
            $customFieldData =  $InfrastructureCustomFields->find()
                        ->select([
                            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                            'custom_field' => $InfrastructureCustomFields->aliasfield('name')
                        ])
                        ->innerJoin(['InfrastructureCustomFormsFields' => 'infrastructure_custom_forms_fields'],[
                            'InfrastructureCustomFormsFields.infrastructure_custom_field_id = '. $InfrastructureCustomFields->aliasField('id')
                        ])->innerJoin(['InfrastructureCustomForms' => 'infrastructure_custom_forms'],[
                            'InfrastructureCustomForms.id = InfrastructureCustomFormsFields.infrastructure_custom_form_id' 
                        ])
                        ->innerJoin(['CustomModules' => 'custom_modules'],[
                            'CustomModules.id = InfrastructureCustomForms.custom_module_id' 
                        ])->where(['CustomModules.name' => 'Institution > Land'])->group([$InfrastructureCustomFields->aliasfield('id')])->toArray();
        }
        if ($landType->name == 'Floor') {
            $customFieldData =  $InfrastructureCustomFields->find()
                        ->select([
                            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                            'custom_field' => $InfrastructureCustomFields->aliasfield('name')
                        ])
                        ->innerJoin(['InfrastructureCustomFormsFields' => 'infrastructure_custom_forms_fields'],[
                            'InfrastructureCustomFormsFields.infrastructure_custom_field_id = '. $InfrastructureCustomFields->aliasField('id')
                        ])->innerJoin(['InfrastructureCustomForms' => 'infrastructure_custom_forms'],[
                            'InfrastructureCustomForms.id = InfrastructureCustomFormsFields.infrastructure_custom_form_id' 
                        ])
                        ->innerJoin(['CustomModules' => 'custom_modules'],[
                            'CustomModules.id = InfrastructureCustomForms.custom_module_id' 
                        ])->where(['CustomModules.name' => 'Institution > Floor'])
                        ->group([$InfrastructureCustomFields->aliasfield('id')])->toArray();
        }
        if ($landType->name == 'Building') {
            $customFieldData =  $InfrastructureCustomFields->find()
                        ->select([
                            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                            'custom_field' => $InfrastructureCustomFields->aliasfield('name')
                        ])
                        ->innerJoin(['InfrastructureCustomFormsFields' => 'infrastructure_custom_forms_fields'],[
                            'InfrastructureCustomFormsFields.infrastructure_custom_field_id = '. $InfrastructureCustomFields->aliasField('id')
                        ])->innerJoin(['InfrastructureCustomForms' => 'infrastructure_custom_forms'],[
                            'InfrastructureCustomForms.id = InfrastructureCustomFormsFields.infrastructure_custom_form_id' 
                        ])
                        ->innerJoin(['CustomModules' => 'custom_modules'],[
                            'CustomModules.id = InfrastructureCustomForms.custom_module_id' 
                        ])->where(['CustomModules.name' => 'Institution > Building'])
                        ->group([$InfrastructureCustomFields->aliasfield('id')])->toArray();
        }
        if ($landType->name == 'Room') {
            $customFieldData =  $InfrastructureCustomFields->find()
                        ->select([
                            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                            'custom_field' => $InfrastructureCustomFields->aliasfield('name')
                        ])
                        ->innerJoin(['InfrastructureCustomFormsFields' => 'infrastructure_custom_forms_fields'],[
                            'InfrastructureCustomFormsFields.infrastructure_custom_field_id = '. $InfrastructureCustomFields->aliasField('id')
                        ])->innerJoin(['InfrastructureCustomForms' => 'infrastructure_custom_forms'],[
                            'InfrastructureCustomForms.id = InfrastructureCustomFormsFields.infrastructure_custom_form_id' 
                        ])
                        ->innerJoin(['CustomModules' => 'custom_modules'],[
                            'CustomModules.id = InfrastructureCustomForms.custom_module_id' 
                        ])->where(['CustomModules.name' => 'Institution > Room'])
                        ->group([$InfrastructureCustomFields->aliasfield('id')])
                        ->toArray();
        } 
        if(!empty($customFieldData)) {
                foreach($customFieldData as $data) {
                    $custom_field_id = $data->custom_field_id;
                    $custom_field = $data->custom_field;
                    $newFields[] = [
                        'key' => '',
                        'field' => $custom_field_id,
                        'type' => 'string',
                        'label' => __($custom_field)
                    ];
                }
            }
        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
         if (is_null($this->request->query('period_id'))) {
            $this->request->query['period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $academicPeriodId = $this->request->query['period_id'];
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionLands = TableRegistry::get('Institution.InstitutionLands');
        $institutionFloors = TableRegistry::get('Institution.InstitutionFloors');
        $institutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
        $institutionRooms = TableRegistry::get('Institution.InstitutionRooms');
        $buildingTypes = TableRegistry::get('building_types');
        $roomTypes = TableRegistry::get('room_types');//POCOR-6263 
        $infrastructureCondition = TableRegistry::get('infrastructure_conditions');
        $infrastructureStatus = TableRegistry::get('infrastructure_statuses');
        $institutionStatus = TableRegistry::get('institution_statuses');
        $infrastructureOwnerships = TableRegistry::get('infrastructure_ownerships');
        $infrastructureLevels = TableRegistry::get('infrastructure_levels');
        $areas = TableRegistry::get('areas');
        $institutions = TableRegistry::get('institutions');

        $sheetData = $settings['sheet']['sheetData'];
        $landType = $sheetData['institution_land_type'];

        $conditions = [];
        $ownerInstitutionIds = $this->getOwnerInstitutionId();//POCOR-7423
        
        if ($landType->name == 'Land') {
            //POCOR-7423 start
            if (!empty($ownerInstitutionIds)) {
                $conditions[$this->aliasField('institution_id IN ')] = $ownerInstitutionIds;
                $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
            }
            else if (!empty($institutionId)) {
                $conditions[$this->aliasField('institution_id')] = $institutionId;
                $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
            }
           //POCOR-7423 end
            $query
                ->select([
                    'land_infrastructure_code'=>$this->aliasField('code'),
                    'land_infrastructure_name'=>$this->aliasField('name'),
                    'institution_code' =>'Institutions.code',
                    'institution_name' =>'Institutions.name',
                    'area_id' => 'Institutions.area_id',
                    'area_code' => $areas->aliasField('code'),
                    'area_name' => $areas->aliasField('name'),
                    'level_id'=> $this->aliasField('id'),
                    'land_start_date'=>$this->aliasField('start_date'),
                    'land_infrastructure_condition'=>$infrastructureCondition->aliasField('name'),
                    'land_infrastructure_ownership'=>$infrastructureOwnerships->aliasField('name'),
                    'land_infrastructure_type'=>$buildingTypes->aliasField('name'),
                    'land_infrastructure_status'=>$infrastructureStatus->aliasField('name'),
                    'shift_name' => 'ShiftOptions.name',
                    'institution_status_name'=> 'InstitutionStatuses.name',
                    'land_area'=>$this->aliasField('area'),//POCOR-6263
                ])
                ->LeftJoin([$buildingTypes->alias() => $buildingTypes->table()], [
                    $this->aliasField('land_type_id').' = ' . $buildingTypes->aliasField('id'),
                ])
                ->LeftJoin([$infrastructureCondition->alias() => $infrastructureCondition->table()], [
                    $this->aliasField('infrastructure_condition_id'). '= ' . $infrastructureCondition->aliasField('id'),
                ])
                ->LeftJoin([$infrastructureStatus->alias() => $infrastructureStatus->table()], [
                    $this->aliasField('land_status_id'). '= ' . $infrastructureStatus->aliasField('id'),
                ])
                //POCOR-5698 two new columns added here
                //status
                ->innerJoin(['Institutions' => $institutions->table()], [
                    // $this->aliasField('institution_id').' = Institutions.id',
                    $this->aliasField('institution_id') .' = Institutions.id',
                ])
                ->LeftJoin([$areas->alias() => $areas->table()], [
                    'Institutions.area_id = ' . $areas->aliasField('id'),
                ])
                ->LeftJoin(['InstitutionStatuses' => $institutionStatus->table()], [
                    'InstitutionStatuses.id = Institutions.institution_status_id',
                ])
                // //shift
                ->LeftJoin(['InstitutionShifts' => 'institution_shifts'],[
                    $this->aliasField('institution_id').' = InstitutionShifts.institution_id',
                    $this->aliasField('academic_period_id').' = InstitutionShifts.academic_period_id'
                ])
                ->LeftJoin(['ShiftOptions' => 'shift_options'],[
                    'ShiftOptions.id = InstitutionShifts.shift_option_id'
                ])
                //POCOR-5698 two new columns ends here
                ->LeftJoin([$infrastructureOwnerships->alias() => $infrastructureOwnerships->table()], [
                    $this->aliasField('land_status_id').'  = ' . $infrastructureOwnerships->aliasField('id'),
                ])
                ->where($conditions)
                /*POCOR-6628 starts - filter result on the bases institution's shift*/
                ->group([
                    $this->aliasField('institution_id'),
                    $this->aliasField('id')
                ]);
                /*POCOR-ends*/
        } else {
            if($landType->name == 'Building') { $level = "Buildings"; $type ='building';}
            if($landType->name == 'Floor') { $level = "Floors"; $type ='floor';}
            if($landType->name == 'Room') { $level = "Rooms"; $type ='room'; }
            //POCOR-7423 start
            if (!empty($ownerInstitutionIds)) {
                $conditions['Institution'.$level.'.'.'institution_id IN '] = $ownerInstitutionIds;
                $conditions['Institution'.$level.'.'.'academic_period_id'] = $academicPeriodId;
                $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
            }
            else if(!empty($institutionId)) {
                $conditions['Institution'.$level.'.'.'institution_id'] = $institutionId;
                $conditions['Institution'.$level.'.'.'academic_period_id'] = $academicPeriodId;
                $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
            }
            //POCOR-7423 end
            //POCOR-6263 start
            if($landType->name == 'Room') { 
            $query
                ->select([
                    'room_type'=> $roomTypes->aliasField('name'),
                    'institutions_room_id' => 'Institution'.$level.'.'.'id'
                ]);
            }//POCOR-6263 ends

            $query
                ->select(['land_infrastructure_code'=>'Institution'.$level.'.'.'code',
                    'land_infrastructure_name'=>'Institution'.$level.'.'.'name',
                    'institution_code' =>'Institutions.code',
                    'institution_name' =>'Institutions.name',
                    'area_id' => 'Institutions.area_id',
                    'area_code' => $areas->aliasField('code'),
                    'area_name' => $areas->aliasField('name'),
                    'level_id'=> 'Institution'.$level.'.'.'id', 
                    'land_start_date'=>'Institution'.$level.'.'.'start_date',
                    'land_infrastructure_type'=>$buildingTypes->aliasField('name'),
                    'land_infrastructure_condition'=>$infrastructureCondition->aliasField('name'),
                    'land_infrastructure_status'=>$infrastructureStatus->aliasField('name'),
                    'shift_name' => 'ShiftOptions.name',
                    'institution_status_name'=> 'InstitutionStatuses.name',
                    'land_infrastructure_ownership'=>$infrastructureOwnerships->aliasField('name'),
                    'land_infrastructure_accessibility' => 'Institution'.$level.'.'.'accessibility',
                    $type.'_area'=>'Institution'.$level.'.'.'area',
                ])
                ->LeftJoin([ 'Institution'.$level => 'institution_'.lcfirst($level) ], [
                    'Institution'.$level.'.'.'institution_id = ' . $this->aliasField('institution_id'),
                ])
                ->LeftJoin([$buildingTypes->alias() => $buildingTypes->table()], [
                    'Institution'.$level.'.'.$type.'_type_id = ' . $buildingTypes->aliasField('id'),
                ])
                ->LeftJoin([$infrastructureCondition->alias() => $infrastructureCondition->table()], [
                    'Institution'.$level.'.'.'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                ])
                ->LeftJoin([$infrastructureStatus->alias() => $infrastructureStatus->table()], [
                    'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureStatus->aliasField('id'),
                ])
                ->LeftJoin(['Institutions' => $institutions->table()], [
                    'Institution'.$level.'.'.'institution_id = Institutions.id',
                ])
                ->LeftJoin([$areas->alias() => $areas->table()], [
                    'Institutions.area_id = ' . $areas->aliasField('id'),
                ])
                ->LeftJoin(['InstitutionStatuses' => $institutionStatus->table()], [
                    'InstitutionStatuses.id = Institutions.institution_status_id',
                ])
                //shift
                ->LeftJoin(['InstitutionShifts' => 'institution_shifts'],[
                    'Institution'.$level.'.'.'institution_id = InstitutionShifts.institution_id',
                    'Institution'.$level.'.'.'academic_period_id = InstitutionShifts.academic_period_id'
                ])
                ->LeftJoin(['ShiftOptions' => 'shift_options'],[
                    'ShiftOptions.id = InstitutionShifts.shift_option_id'
                ])
                ->LeftJoin([$infrastructureOwnerships->alias() => $infrastructureOwnerships->table()], [
                    'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                ]);
                //POCOR-6263 start
                if($landType->name == 'Room'){
                    $query->LeftJoin([$roomTypes->alias() => $roomTypes->table()], [
                        'Institution'.$level.'.'.$type.'_type_id = ' . $roomTypes->aliasField('id')
                    ]);
                } //POCOR-6263 end 
                /*POCOR-6628 starts - adding condition to get on In Use room status id records*/
                if($landType->name == 'Room') {
                    $query->where(['Institution'.$level.'.'.$type.'_status_id' => 1]);
                }
                /*POCOR-6628 ends*/   
            $query->where($conditions);
            /*POCOR-6628 starts - filter result on the bases institution's shift*/
            $query->group([
                'Institution'.$level.'.'.'institution_id',
                'Institution'.$level.'.'.'id'
            ]);
            /*POCOR-6628 ends*/
        }

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($landType) {
            return $results->map(function ($row) use ($landType) {

                $areas1 = TableRegistry::get('areas');
                $areasData = $areas1
                    ->find()
                    ->where([$areas1->alias('code')=>$row->area_code])
                    ->first();
                $row['region_code'] = '';
                $row['region_name'] = '';
                if(!empty($areasData)){
                    $areas = TableRegistry::get('areas');
                    $areaLevels = TableRegistry::get('area_levels');
                    $institutions = TableRegistry::get('institutions');
                    $val = $areas
                        ->find()
                        ->select([
                            $areas1->aliasField('code'),
                            $areas1->aliasField('name'),
                        ])
                        ->leftJoin(
                            [$areaLevels->alias() => $areaLevels->table()],
                            [
                                $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                            ]
                        )
                        ->leftJoin(
                            [$institutions->alias() => $institutions->table()],
                            [
                                $areas->aliasField('id  = ') . $institutions->aliasField('area_id')
                            ]
                        )
                        ->where([
                            $areaLevels->aliasField('level !=') => 1,
                            $areas->aliasField('id') => $areasData->parent_id
                        ])->first();

                    if (!empty($val->name) && !empty($val->code)) {
                        $row['region_code'] = $val->code;
                        $row['region_name'] = $val->name;
                    }
                }
                
                $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
                if (!empty($landType->name)) {
                    $customFieldData = $InfrastructureCustomFields->find()
                        ->select([
                            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                            'custom_field' => $InfrastructureCustomFields->aliasfield('name'),
                            'field_type' => $InfrastructureCustomFields->aliasfield('field_type'),
                            'text_value' => 'CustomFieldValues.text_value',
                            'number_value' => 'CustomFieldValues.number_value',
                            'decimal_value' => 'CustomFieldValues.decimal_value',
                            'textarea_value' => 'CustomFieldValues.textarea_value',
                            'date_value' => 'CustomFieldValues.date_value',
                            'time_value' => 'CustomFieldValues.time_value'
                        ])
                        ->innerJoin(['CustomFieldValues' => lcfirst($landType->name).'_custom_field_values' ], [
                            'CustomFieldValues.infrastructure_custom_field_id = ' . $InfrastructureCustomFields->aliasField('id'),
                            'CustomFieldValues.institution_'.lcfirst($landType->name).'_id  = ' . $row['level_id']
                        ])
                        ->toArray();
                }
                if(!empty($customFieldData)) {
                    foreach($customFieldData as $data) {
                        if(!empty($data->text_value)) {
                            $row[$data->custom_field_id] = $data->text_value;
                        } 
                        if(!empty($data->number_value) && $data->field_type == 'CHECKBOX') {
                            /*POCOR-6376 starts*/
                            $infrastructureCustomFieldOptions = TableRegistry::get('infrastructure_custom_field_options');
                            $infrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
                            $fieldValue = $infrastructureCustomFieldOptions->find()
                                            ->select([$infrastructureCustomFieldOptions->aliasField('name')])
                                            ->innerJoin([$infrastructureCustomFields->alias() => $infrastructureCustomFields->table()],[
                                                $infrastructureCustomFields->aliasField('id').' = ' . $infrastructureCustomFieldOptions->aliasField('infrastructure_custom_field_id')
                                            ])
                                            ->innerJoin(['CustomFieldValues' => lcfirst($landType->name).'_custom_field_values' ], [
                                                'CustomFieldValues.infrastructure_custom_field_id = ' . $infrastructureCustomFieldOptions->aliasField('infrastructure_custom_field_id'),
                                                'CustomFieldValues.institution_'.lcfirst($landType->name).'_id  = ' . $row['level_id'],
                                                'CustomFieldValues.number_value  = ' . $infrastructureCustomFieldOptions->aliasField('id')
                                            ])
                                            ->where([
                                                $infrastructureCustomFields->alias('field_type') => 'CHECKBOX',
                                                // $infrastructureCustomFieldOptions->aliasField('id') => $data->number_value,
                                                'CustomFieldValues.infrastructure_custom_field_id = ' . $data->custom_field_id,
                                                'CustomFieldValues.institution_'.lcfirst($landType->name).'_id  = ' . $row['level_id']])
                                            ->group([$infrastructureCustomFieldOptions->aliasField('name')])
                                            ->toArray();
                            if (!empty($fieldValue)) {
                                foreach ($fieldValue as $numValue) {
                                    $optVal[] = $numValue->name;
                                }
                            }
                            $str = implode(',', $optVal);
                            $row[$data->custom_field_id] = $str;
                            unset($optVal);
                        } 
                        if (!empty($data->number_value) && $data->field_type != 'CHECKBOX') {
                            $optvalue = TableRegistry::get('infrastructure_custom_field_options');
                            if($optvalue->exists(['id'=>$data->number_value])){
                                $fieldVal = $optvalue->get($data->number_value);
                                if (!empty($fieldVal)) {
                                    $opt = $fieldVal->name;
                                } else {
                                    $opt = '';
                                }
                                $row[$data->custom_field_id] = $opt;
                            }
                        }
                        
                        if(!empty($data->number_value) && $data->field_type == 'NUMBER') {
                            $row[$data->custom_field_id] = $data->number_value;
                        }
                        /*POCOR-6376 ends*/
                        if(!empty($data->decimal_value)) {
                            $row[$data->custom_field_id] = $data->decimal_value;
                        }
                        if(!empty($data->textarea_value)) {
                            $row[$data->custom_field_id] = $data->textarea_value;
                        }   
                        if(!empty($data->date_value)) {
                            $row[$data->custom_field_id] = $data->date_value;
                            
                        }
                        if(!empty($data->time_value)) {
                            $row[$data->custom_field_id] = $data->time_value;
                            
                        }
                    }
                }
                return $row;
            });
        });
    }
}
