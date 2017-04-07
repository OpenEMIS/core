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
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionLandsTable extends AppTable
{
    use OptionsTrait;
    const UPDATE_DETAILS = 1;    // In Use
    const END_OF_USAGE = 2;
    const CHANGE_IN_TYPE = 3;

    private $Levels = null;
    private $levelOptions = [];
    private $landLevel = null;

    private $canUpdateDetails = true;
    private $currentAcademicPeriod = null;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('LandStatuses', ['className' => 'Infrastructure.InfrastructureStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('LandTypes', ['className' => 'Infrastructure.LandTypes']);
        $this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
        $this->belongsTo('PreviousLands', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'previous_institution_land_id']);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        // $this->addBehavior('CustomField.Record', [
        //     'fieldKey' => 'infrastructure_custom_field_id',
        //     'tableColumnKey' => null,
        //     'tableRowKey' => null,
        //     'fieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFields'],
        //     'formKey' => 'infrastructure_custom_form_id',
        //     'filterKey' => 'infrastructure_custom_filter_id',
        //     'formFieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFields'],
        //     'formFilterClass' => ['className' => 'Infrastructure.RoomCustomFormsFilters'],
        //     'recordKey' => 'institution_room_id',
        //     'fieldValueClass' => ['className' => 'Infrastructure.RoomCustomFieldValues', 'foreignKey' => 'institution_room_id', 'dependent' => true, 'cascadeCallbacks' => true],
        //     'tableCellClass' => null
        // ]);
        // $this->addBehavior('Institution.InfrastructureShift');

        $this->Levels = TableRegistry::get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $this->Levels->find('list')->toArray();
        $this->landLevel = $this->Levels->getFieldByCode('LAND', 'id');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('code', [
                'ruleUnique' => [
                    'rule' => ['validateUnique', ['scope' => ['start_date', 'institution_id']]],
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
        ;
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
            $statuses = $this->LandStatuses->find('list', ['keyField' => 'id', 'valueField' => 'code'])->toArray();
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
        // logic to copy custom fields (general only) where new land is created when change in land type
        $this->processCopy($entity);
    }

    public function onGetInfrastructureLevel(Event $event, Entity $entity)
    {
        return $this->levelOptions[$this->landLevel];
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

    public function beforeAction(Event $event)
    {
        // For breadcrumb to build the baseUrl
        $this->controller->set('breadcrumbPlugin', 'Institution');
        $this->controller->set('breadcrumbController', 'Institutions');
        $this->controller->set('breadcrumbAction', 'Infrastructures');
        // End
    }

    public function indexBeforeAction(Event $event)
    {
        $this->ControllerAction->setFieldOrder(['code', 'name', 'institution_id', 'infrastructure_level', 'land_type_id', 'land_status_id']);
        $this->fields['area']['visible'] = false;
        $this->fields['comment']['visible'] = false;
        $this->fields['infrastructure_ownership_id']['visible'] = false;
        $this->fields['year_acquired']['visible'] = false;
        $this->fields['year_disposed']['visible'] = false;
        $this->ControllerAction->field('institution_id');
        $this->ControllerAction->field('infrastructure_level', ['after' => 'name']);
        $this->ControllerAction->field('start_date', ['visible' => false]);
        $this->ControllerAction->field('start_year', ['visible' => false]);
        $this->ControllerAction->field('end_date', ['visible' => false]);
        $this->ControllerAction->field('end_year', ['visible' => false]);
        $this->ControllerAction->field('academic_period_id', ['visible' => false]);
        $this->ControllerAction->field('infrastructure_condition_id', ['visible' => false]);
        $this->ControllerAction->field('previous_institution_land_id', ['visible' => false]);

        $toolbarElements = [];
        $toolbarElements = $this->addBreadcrumbElement($toolbarElements);
        // $toolbarElements = $this->addControlFilterElement($toolbarElements);
        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        // get the list of owner institution id
        // $ownerInstitutionIds = $this->getOwnerInstitutionId();

        // if (!empty($ownerInstitutionIds)) {
        //     $conditions = [];
        //     $conditions[$this->aliasField('institution_id IN ')] = $ownerInstitutionIds;
        //     $query->where($conditions, [], true);
        // }

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

    public function indexAfterAction(Event $event, $data)
    {
        $session = $this->request->session();

        $sessionKey = $this->registryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $this->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['AcademicPeriods', 'LandTypes', 'InfrastructureConditions']);
    }

    public function editBeforeAction(Event $event)
    {
        $session = $this->request->session();

        $sessionKey = $this->registryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $this->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function editAfterQuery(Event $event, Entity $entity)
    {
        list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

        $session = $this->request->session();
        $sessionKey = $this->registryAlias() . '.warning';
        if (!$isEditable) {
            $inUseId = $this->LandStatuses->getIdByCode('IN_USE');
            $endOfUsageId = $this->LandStatuses->getIdByCode('END_OF_USAGE');

            if ($entity->land_status_id == $inUseId) {
                $session->write($sessionKey, $this->aliasField('in_use.restrictEdit'));
            } elseif ($entity->land_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->aliasField('end_of_usage.restrictEdit'));
            }

            $url = $this->ControllerAction->url('index');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        } else {
            $selectedEditType = $this->request->query('edit_type');
            if ($selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
                $today = new DateTime();
                $diff = date_diff($entity->start_date, $today);

                // Not allowed to change land type in the same day
                if ($diff->days == 0) {
                    $session->write($sessionKey, $this->aliasField('change_in_land_type.restrictEdit'));

                    $url = $this->ControllerAction->url('edit');
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

        if (!$isDeletable) {
            $inUseId = $this->LandStatuses->getIdByCode('IN_USE');
            $endOfUsageId = $this->LandStatuses->getIdByCode('END_OF_USAGE');

            $session = $this->request->session();
            $sessionKey = $this->registryAlias() . '.warning';
            if ($entity->land_status_id == $inUseId) {
                $session->write($sessionKey, $this->aliasField('in_use.restrictDelete'));
            } elseif ($entity->land_status_id == $endOfUsageId) {
                $session->write($sessionKey, $this->aliasField('end_of_usage.restrictDelete'));
            }

            $url = $this->ControllerAction->url('index');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }

        $extra['excludedModels'] = [$this->CustomFieldValues->alias()];
    }

    public function addEditBeforeAction(Event $event)
    {
        $toolbarElements = $this->addBreadcrumbElement();
        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
        if ($this->action == 'add') {
            $this->fields['year_disposed']['visible'] = false;
        }
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $selectedEditType = $this->request->query('edit_type');
        if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_LAND_TYPE) {
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
            $editTypeOptions = $this->getSelectOptions($this->aliasField('change_types'));
            $selectedEditType = $this->queryString('edit_type', $editTypeOptions);
            $this->advancedSelectOptions($editTypeOptions, $selectedEditType);
            $this->controller->set(compact('editTypeOptions'));

            if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_LAND_TYPE) {
                $this->canUpdateDetails = false;
            }

            $attr['type'] = 'element';
            $attr['element'] = 'Institution.Room/change_type';

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

    // public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    // {
    //     if ($action == 'index' || $action == 'view') {
    //         if (!empty($this->getOwnerInstitutionId())) {
    //             $attr['type'] = 'select';
    //         }
    //     }

    //     return $attr;
    // }

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
            $classificationOptions = $this->getSelectOptions('RoomTypes.classifications');
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

    public function onUpdateFieldPreviousId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['value'] = 0;
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
                $attr['null'] = false;    // for asterisk to appear
                $attr['type'] = 'readonly';
                $attr['value'] = $today->format('Y-m-d');
                $attr['attr']['value'] = $this->formatDate($today);
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
        $this->ControllerAction->setFieldOrder([
            'change_type', 'academic_period_id', 'institution_id', 'code', 'name', 'land_type_id', 'land_status_id', 'year_acquired', 'area', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_ownership_id', 'infrastructure_condition_id', 'previous_institution_land_id', 'new_land_type', 'new_start_date'
        ]);

        $this->ControllerAction->field('change_type');
        $this->ControllerAction->field('land_status_id', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_id', ['entity' => $entity]);
        $this->ControllerAction->field('institution_id');
        $this->ControllerAction->field('code');
        $this->ControllerAction->field('name');
        $this->ControllerAction->field('land_type_id', ['type' => 'select', 'entity' => $entity]);
        $this->ControllerAction->field('start_date', ['entity' => $entity]);
        $this->ControllerAction->field('end_date', ['entity' => $entity]);
        $this->ControllerAction->field('infrastructure_condition_id', ['type' => 'select']);
        $this->ControllerAction->field('previous_institution_land_id', ['type' => 'hidden']);
        $this->ControllerAction->field('new_land_type', ['type' => 'select', 'visible' => false, 'entity' => $entity]);
        $this->ControllerAction->field('new_start_date', ['type' => 'date', 'visible' => false, 'entity' => $entity]);
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

    private function addBreadcrumbElement($toolbarElements = [])
    {
        $parentId = $this->request->query('parent');
        $crumbs = [];
        $toolbarElements[] = ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => compact('crumbs'), 'options' => []];

        return $toolbarElements;
    }

    // private function addControlFilterElement($toolbarElements = [])
    // {
    //     $toolbarElements[] = ['name' => 'Institution.Room/controls', 'data' => compact('typeOptions', 'selectedType'), 'options' => []];

    //     return $toolbarElements;
    // }

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

            if ($count > 0) {
                $isEditable = false;
            }

            $count = $this
                ->find()
                ->where([$this->aliasField('code') => $entity->code])
                ->count();

            if ($count > 1) {
                $isDeletable = false;
            }
        } elseif ($entity->land_status_id == $endOfUsageId) {    // If already end of usage, not allow to edit or delete
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
        if ($entity->isNew()) {
            if ($entity->has('previous_institution_land_id') && $entity->previous_institution_land_id != 0) {
                $copyFrom = $entity->previous_institution_land_id;
                $copyTo = $entity->id;

                $previousEntity = $this->get($copyFrom);
                $changeInTypeId = $this->LandStatuses->getIdByCode('CHANGE_IN_TYPE');

                if ($previousEntity->land_status_id == $changeInTypeId) {
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
}
