<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

use Institution\Model\Table\Institutions;

class InstitutionShiftsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('ShiftOptions', ['className' => 'Institution.ShiftOptions']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('LocationInstitutions', ['className' => 'Institution.LocationInstitutions']);
        $this->belongsTo('PreviousShifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'previous_shift_id']);

        $this->hasMany('InstitutionShiftPeriods', ['className' => 'InstitutionShiftPeriods', 'foreignKey' => 'institution_shift_period_id']); //POCOR-5281
        $this->hasMany('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_shift_id']);
        $this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'location_institution_id']);
        $this->addBehavior('OpenEmis.Autocomplete');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ClassStudents' => ['index'],
            'Staff' => ['index', 'add'],
            'InstitutionStaffAttendances' => ['index', 'add', 'edit']
        ]);

        $this->behaviors()->get('ControllerAction')->config([
            'actions' => ['search' => false],
        ]);
        $this->setDeleteStrategy('restrict');

        $this->addBehavior('ContactExcel', ['excludes' => ['start_time', 'end_time', 'academic_period_id', 'previous_shift_id'], 'pages' => ['index']]); //POCOR-6898 change Excel to ContactExcel Behaviour

    }
    //POCOR-8158
    public function beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $InsShiftPeriodTable = TableRegistry::get('institution_shift_periods');
        $InsClassesDataTable = TableRegistry::get('institution_classes');
        $InsShiftPeriodData = $InsShiftPeriodTable->find('all',['conditions'=> ['institution_shift_period_id'=> $entity->id]])->toArray();
        $InsClassesData = $InsClassesDataTable->find('all',['conditions'=> ['institution_shift_id'=> $entity->id]])->toArray();
        if (!empty($InsClassesData) || !empty($InsShiftPeriodData)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }
    //POCOR-8158

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('start_time', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_time', true]
            ])
            //POCOR-7840 CHECK SHIFTS
            ->add('shift_option_id',
                'ruleSameShiftExists', [
                    'rule' => function ($value, $context) {
                        $context = isset($context['data']) ? $context['data'] : [];
                        $shift_option_id = $value;
                        $another_institution_id = isset($context['location_institution_id']) ? $context['location_institution_id'] : 0;
                        $institution_id = isset($context['location_institution_id']) ? $context['location_institution_id'] : 0;
                        $academic_period_id = isset($context['academic_period_id']) ? $context['academic_period_id'] : 0;;
                        $institution_shifts = TableRegistry::get('institution_shifts');
                        $where = [
                            'shift_option_id' => $shift_option_id,
                            'academic_period_id' => $academic_period_id,
                            'OR' => [
                                'location_institution_id IN' => [$institution_id, $another_institution_id],
                                'institution_id  IN' => [$institution_id, $another_institution_id],
                            ]
                        ];
                        $occupied = $institution_shifts->find('all')
                            ->where($where)->count();
                        if ($occupied) {
                            return false;
                        }
                        return true;
                    },
                    'message' => __('This Shift Is Already In Use')
                ])
            //Start:POCOR-5065 Commented that validation
            // ->add('start_time', 'ruleCheckShiftAvailable', [
            //         'rule' => ['checkShiftAvailable'],
            //         'on' => function ($context) {
            //              //validate when only location_institution_id is not empty
            //                 return !empty($context['data']['location_institution_id']);
            //         }
            //     ])
            //END:POCOR-5065
            // ->add('location_institution_id', 'ruleCheckLocationInstitutionId', [
            //      'rule' => ['checkInstitutionLocation']
            //  ])
            ->requirePresence('location_institution_id');
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxInstitutionsAutocomplete'] = 'ajaxInstitutionsAutocomplete';
        return $events;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'remove') {
            $shiftName = $this->ShiftOptions->get($extra['entity']->shift_option_id); //since institution_shifts does not have field 'name', then need to pass shift name that will be use on remove action
            $extra['entity']->name = __($shiftName->name);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(); //to show list of academic period for selection
        $institutionId = $this->Session->read('Institution.Institutions.id');

        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);

        $extra['elements']['control'] = [
            'name' => 'Institution.Shifts/controls',
            'data' => [
                'periodOptions' => $academicPeriodOptions,
                'selectedPeriodOption' => $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];

        //logic to remove 'add' button if the institution has received shift from other based on the academic period
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        // if ($this->isOccupier($institutionId, $this->AcademicPeriods->getCurrent())) { //if occupier, then remove the 'add' button
        //     unset($toolbarButtonsArray['add']);
        // }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        //show error when occupier tried to access add/edit page.
        // if (isset($this->request->query['occupier']) && $this->request->query['occupier']) {
        //     $this->Alert->error($this->aliasField('noAccessToShift'));
        // }

        $this->field('institution_id', ['type' => 'integer']); //this is to show owner (set in label table), by default the default is hidden
        $this->field('previous_shift_id', ['visible' => 'false']);

        $this->setFieldOrder([
            'academic_period_id', 'shift_option_id', 'start_time', 'end_time', 'institution_id', 'period', 'location_institution_id' //POCOR-5281
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //echo $query; exit;
        $institutionId = $this->Session->read('Institution.Institutions.id');
        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                'OR' => [
                    [$this->aliasField('location_institution_id') => $institutionId],
                    [$this->aliasField('institution_id') => $institutionId]
                ],
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
            ], [], true); //this parameter will remove all where before this and replace it with new where.
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $currentInstitutionId = $this->Session->read('Institution.Institutions.id');
        $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (!$this->isOccupier($currentInstitutionId, $selectedAcademicPeriod)) { //if occupier, then redirect from trying to access add/edit page
            if (($entity->institution->id) == ($entity->location_institution->id)) {
                return $buttons;
            } else {
                unset($buttons['remove']);
                unset($buttons['edit']);
            }
        } else {
            return $buttons;
        }
        return $buttons;

        //logic that if the owner != occupier then if the active session is the occupier, then remove edit and delete button.
        // if (($entity->institution->id) != ($entity->location_institution->id)) {
        //     if (($entity->institution->id) != $currentInstitutionId) {
        //         unset($buttons['remove']);
        //         unset($buttons['edit']);
        //     }
        // }

        // return $buttons;
    }

    //Start:POCOR-5281
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $InstitutionShiftsTable = TableRegistry::get('student_attendance_per_day_periods');
        $shiftOptions = $InstitutionShiftsTable->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        $this->field('period', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Period')
            ]
        ]);
        $this->fields['period']['options'] = $shiftOptions;
    }

    //End:POCOR-5281
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');

        if ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        } elseif ($this->action == 'edit') {
            $selectedAcademicPeriod = $entity->academic_period_id;
        }

        // if ($this->isOccupier($institutionId, $selectedAcademicPeriod)) { //if occupier, then redirect from trying to access add/edit page
        //     $url = $this->url('index');
        //     $url['occupier'] = 1;
        //     $event->stopPropagation();
        //     return $this->controller->redirect($url);
        // }

        $this->setupFields($entity);
    }

    //Start:POCOR-5281
    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['InstitutionShiftPeriods']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $arr = [];
                foreach ($row->institution_shift_periods as $key => $period) {
                    $arr[$key] = ['id' => $period['period_id']];
                }
                $row['period'] = $arr;
                return $row;
            });
        });

    }
    //End:POCOR-5281

    /******************************************************************************************************************
     **
     ** view action methods
     **
     ******************************************************************************************************************/

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $currentInstitutionId = $this->Session->read('Institution.Institutions.id');
        $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        // if ($this->isOccupier($institutionId, $entity->academic_period_id)) { //if occupier, then remove the 'delete / edit' button
        //     unset($toolbarButtonsArray['edit']);
        //     unset($toolbarButtonsArray['remove']);
        // }

        if (!$this->isOccupier($currentInstitutionId, $selectedAcademicPeriod)) { //if occupier, then redirect from trying to access add/edit page
            if (($entity->institution->id) == ($entity->location_institution->id)) {
                $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
            } else {
                unset($toolbarButtonsArray['edit']);
                unset($toolbarButtonsArray['remove']);
            }
        } else {
            $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->field('institution_id', ['type' => 'integer']); //this is to show owner (set in label table), by default the default is hidden
        $this->field('previous_shift_id', ['visible' => 'false']);

        $this->setFieldOrder([
            'academic_period_id', 'shift_option_id', 'start_time', 'end_time', 'institution_id', 'period', 'location_institution_id' //POCOR-5281
        ]);
    }

    /******************************************************************************************************************
     **
     ** addEdit action methods
     **
     ******************************************************************************************************************/

    public function onGetShiftOptionId(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $ControllerActionHelper = $event->subject();
            $htmlHelper = $event->subject()->Html;
            $url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Shifts', 'view'];
            $url[] = $ControllerActionHelper->paramsEncode(['id' => $entity->id]);
            return $htmlHelper->link(__($entity->shift_option->name), $url);
        }
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        $ControllerActionHelper = $event->subject();
        return $event->subject()->Html->link($entity->institution->name, [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => 'dashboard',
            $ControllerActionHelper->paramsEncode(['id' => $entity->institution_id])
        ]);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($this->request->params['pass'][0] == 'add' || $this->request->params['pass'][0] == 'edit') {
            switch ($field) {
                case 'location_institution_id':
                    return __('Owner');
                default:
                    return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
            }
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetLocationInstitutionId(Event $event, Entity $entity)
    {
        $ControllerActionHelper = $event->subject();
        return $event->subject()->Html->link($entity->location_institution->name, [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => 'dashboard',
            $ControllerActionHelper->paramsEncode(['id' => $entity->location_institution_id])
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $attr['type'] = 'readonly';

        if ($action == 'add') { //set the academic period to thecurrent and readonly
            $attr['attr']['value'] = $academicPeriodOptions[$this->getSelectedAcademicPeriod($this->request)];
            $attr['value'] = $this->getSelectedAcademicPeriod($this->request);
        } elseif ($action == 'edit') {
            $attr['attr']['value'] = $academicPeriodOptions[$attr['entity']->academic_period_id];
            $attr['value'] = $attr['entity']->academic_period_id;
        }

        return $attr;
    }

    public function onUpdateFieldShiftOptionId(Event $event, array $attr, $action, $request)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        // POCOR-7840 REFACTURED COMMON VARIABLES
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod($this->request);
        $checkisOccupier = $this->isOccupier($institutionId, $selectedAcademicPeriod);
        //this is default condition to get the all shift.
        $options = $this->ShiftOptions
            ->find('list')
            ->find('visible')
            ->find('order');

        if ($action == 'add') {

            if ($checkisOccupier == 0) {
                if (!empty($selectedAcademicPeriod)) {
                    //during add then need to exclude used shifts based on school and academic period
                    $options = $options
                        ->find('availableShifts', ['institution_id' => $institutionId, 'academic_period_id' => $selectedAcademicPeriod])
                        ->toArray();
                    $attr['options'] = $options;
                    $attr['onChangeReload'] = 'changeShiftOption';

                    if (empty($options)) {
                        $this->Alert->warning('InstitutionShifts.allShiftsUsed');
                    }
                }
            } else {
                if (!empty($selectedAcademicPeriod)) {
                    //during add then need to exclude used shifts based on school and academic period
                    $options = $options
                        ->find('availableShiftsOccupier', ['institution_id' => $institutionId, 'academic_period_id' => $selectedAcademicPeriod])
                        ->toArray();
                    $attr['options'] = $options;
                    $attr['onChangeReload'] = 'changeShiftOption';

                    if (empty($options)) {
                        $this->Alert->warning('InstitutionShifts.allShiftsUsed');
                    }
                }
            }
        } elseif ($action == 'edit') {
            //for edit since it is read only, then no need to put conditions and get the value from the options populated.
            // $options = $options->toArray();
            // $attr['type'] = 'readonly';
            // $attr['attr']['value'] = __($options[$attr['entity']->shift_option_id]);
            // $attr['value'] = $attr['entity']->shift_option_id;
            // POCOR-7840 MOVED TO TOP
//            $institutionId = $this->Session->read('Institution.Institutions.id');
//            $selectedAcademicPeriod = $this->getSelectedAcademicPeriod($this->request);
//            $checkisOccupier = $this->isOccupier($institutionId, $selectedAcademicPeriod);
            // POCOR-7840 UNCOMMENTED AND EDITED
            $selectedShiftId = $attr['entity']->shift_option_id;
            $allOptions = $options
                ->find('all')
                ->toArray();
            if ($checkisOccupier == 0) {
                $options = $options
                    ->find('availableShifts', ['institution_id' => $institutionId, 'academic_period_id' => $selectedAcademicPeriod])
                    ->toArray();
            } else {
                $options = $options
                    ->find('availableShiftsOccupier', ['institution_id' => $institutionId, 'academic_period_id' => $selectedAcademicPeriod])
                    ->toArray();
            }
            $options[$selectedShiftId] = $allOptions[$selectedShiftId];
            if (empty($options)) {
                $this->Alert->warning('InstitutionShifts.allShiftsUsed');
            }
            $attr['attr']['value'] = $selectedShiftId;
            $attr['select'] = false;
            $attr['value'] = $selectedShiftId;
            // POCOR-7840 COMMENTED
//            $options = $options
//                        ->find('all', ['institution_id' => $institutionId, 'academic_period_id' => $selectedAcademicPeriod])
//                        ->toArray();
            // POCOR-7840 END
            $attr['options'] = $options;
            $attr['onChangeReload'] = 'changeShiftOption';
        }
        //pr($options);
        return $attr;
    }

    public function onUpdateFieldStartTime(Event $event, array $attr, $action, Request $request)
    {
        if ($request->data) {
            $submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
            if ($submit == 'changeShiftOption') {
                if (!empty($request->query['shiftoption'])) {
                    $shiftOption = $request->query['shiftoption'];
                    $attr['value'] = $this->ShiftOptions->getStartEndTime($shiftOption, 'start')->format('H:i');
                    return $attr;
                }
            }
        }
    }

    public function onUpdateFieldEndTime(Event $event, array $attr, $action, Request $request)
    {
        if ($request->data) {
            $submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
            if ($submit == 'changeShiftOption') {
                if (!empty($request->query['shiftoption'])) {
                    $shiftOption = $request->query['shiftoption'];
                    $attr['value'] = $this->ShiftOptions->getStartEndTime($shiftOption, 'end')->format('H:i');
                    return $attr;
                }
            }
        }
    }

    public function onUpdateFieldLocation(Event $event, array $attr, $action, $request)
    {

        $attr['options'] = ['CURRENT' => __('This Institution'), 'OTHER' => __('Other Institution')];
        if ($action == 'add') {
            if (!Configure::read('schoolMode')) {
                $attr['onChangeReload'] = 'changeLocation';
                $attr['default'] = 'CURRENT'; //set the default selected location as Current Institution
                $attr['select'] = false;
            } else {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['options']['CURRENT'];
                $attr['value'] = 'CURRENT';
            }
        } elseif ($action == 'edit') {
            if ($attr['entity']->institution_id != $attr['entity']->location_institution_id) {
                $attr['onChangeReload'] = 'changeLocation';
                $attr['default'] = 'OTHER'; //set the default selected location as Current Institution
                $attr['select'] = false;
            } else if (!Configure::read('schoolMode')) {
                $attr['onChangeReload'] = 'changeLocation';
                $attr['default'] = 'CURRENT'; //set the default selected location as Current Institution
                $attr['select'] = false;
            } else {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['options']['CURRENT'];
                $attr['value'] = 'CURRENT';
            }
        }
        return $attr;
    }

    public function onUpdateFieldLocationInstitutionId(Event $event, array $attr, $action, $request)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        if ($action == 'add') {
            if ($request->data) {
                $data = $request->data[$this->alias()];
                if ($data['location'] == 'OTHER') {
                    $attr['type'] = 'autocomplete';
                    $attr['target'] = ['key' => 'location_institution_id', 'name' => $this->aliasField('location_institution_id')];
                    $attr['noResults'] = __('No Institutions found');
                    $attr['attr'] = ['placeholder' => __('Institution Code or Name')];
                    if (isset($data['location_institution_id']) && !empty($data['location_institution_id'])) { //this is to regain institution name after validation / reload
                        if ($data['location_institution_id'] == $institutionId) {
                            $attr['attr']['value'] = '';
                        } else {
                            $institutionDetails = $this->Institutions->findById($data['location_institution_id'])->first();
                            $attr['attr']['value'] = $institutionDetails['code'] . " - " . $institutionDetails['name'];
                        }
                    }
                    $attr['url'] = ['academicperiod' => $this->getSelectedAcademicPeriod($this->request), 'controller' => 'Institutions', 'action' => 'Shifts', 'ajaxInstitutionsAutocomplete'];
                } elseif ($data['location'] == 'CURRENT') {
                    $attr['type'] = 'hidden'; //default is hidden as location default also "CURRENT"
                    $attr['value'] = $institutionId; //default is current institution ID
                }
            }
        } elseif ($action == 'edit') {
            // $attr['onChangeReload'] = 'changeLocation';
            if ($event->data[0]['entity']->institution_id != $event->data[0]['entity']->location_institution_id) {
                $attr['type'] = 'autocomplete';
                $attr['target'] = ['key' => 'location_institution_id', 'name' => $this->aliasField('location_institution_id')];
            }
            $Institutions = TableRegistry::get('Institution.Institutions');
            $occupier = $Institutions->findById($attr['entity']->location_institution_id)->first();
            $attr['attr']['value'] = $occupier->name;
            $data = $request->data[$this->alias()];

            if ($event->data[0]['entity']->institution_id != $event->data[0]['entity']->location_institution_id && $event->data[0]['entity']->location != 'CURRENT') {
                //POCOR-6587 added one more condition to get data
                $attr['type'] = 'autocomplete';
                $attr['target'] = ['key' => 'location_institution_id', 'name' => $this->aliasField('location_institution_id')];
                $attr['noResults'] = __('No Institutions found');
                $attr['attr'] = ['placeholder' => __('Institution Code or Name')];
                if (isset($event->data[0]['entity']->location_institution_id) && !empty($event->data[0]['entity']->location_institution_id)) { //this is to regain institution name after validation / reload
                    if ($event->data[0]['entity']->institution_id == $institutionId) {
                        $attr['attr']['value'] = '';
                    } else {
                        $entity->location_institution_id = $event->data[0]['entity']->location_institution_id;
                        $institutionDetails = $this->Institutions->findById($event->data[0]['entity']->institution_id)->first();
                        $attr['attr']['value'] = $institutionDetails['code'] . " - " . $institutionDetails['name'];
                    }
                }
                $attr['url'] = ['academicperiod' => $this->getSelectedAcademicPeriod($this->request), 'controller' => 'Institutions', 'action' => 'Shifts', 'ajaxInstitutionsAutocomplete'];
            } elseif ($data['location'] == 'CURRENT') {
                $attr['type'] = 'hidden'; //default is hidden as location default also "CURRENT"
                $attr['value'] = $institutionId; //default is current institution ID
            }
            // if (isset($data['location_institution_id']) && !empty($data['location_institution_id'])) { //this is to regain institution name after validation / reload
            //     if ($data['location_institution_id'] == $institutionId) {
            //         $attr['attr']['value'] = '';
            //     } else {
            //         $institutionDetails = $this->Institutions->findById($data['location_institution_id'])->first();
            //         $attr['attr']['value'] = $institutionDetails['code'] . " - " . $institutionDetails['name'];
            //     }
            // }
            // $attr['url'] = ['academicperiod' => $this->getSelectedAcademicPeriod($this->request), 'controller' => 'Institutions', 'action' => 'Shifts', 'ajaxInstitutionsAutocomplete'];
        }
        // pr($attr['value']);
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function addEditOnChangeShiftOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['shiftoption']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('shift_option_id', $request->data[$this->alias()])) {
                    $request->query['shiftoption'] = $request->data[$this->alias()]['shift_option_id'];
                }
            }
        }
    }

    public function addEditOnChangeLocation(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $data['InstitutionShifts']['location_institution_id'] = ''; //value has to be reset each time location being updated.
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-6618 starts 
        if (!empty($entity->id) && $entity->location) { //this will work when edit any shift
            $institutionShifts = TableRegistry::get('institution_shifts')
                ->find()
                ->where(['id' => $entity->id])->first();
            //location_institution_id belongs to `occupier` and  institution_id belongs to `owner`
            if ($entity->location == 'OTHER' && ($institutionShifts->location_institution_id == $this->request->data['InstitutionShifts']['location_institution_id'])) {
                $entity->institution_id = $entity->location_institution_id;
                $entity->location_institution_id = $institutionShifts->institution_id;
            } else if ($entity->location == 'OTHER' && ($institutionShifts->location_institution_id != $this->request->data['InstitutionShifts']['location_institution_id'])) {
                $entity->institution_id = $entity->institution_id;
                $entity->location_institution_id = $this->request->data['InstitutionShifts']['location_institution_id'];
            }
            //when the occupier and the owner are same 
            if ($entity->location == 'CURRENT' && ($this->request->data['InstitutionShifts']['institution_id'] == $institutionShifts->institution_id) && ($institutionShifts->institution_id == $institutionShifts->location_institution_id)) {
                $entity->location_institution_id = $this->request->data['InstitutionShifts']['institution_id'];
            }
        }//POCOR-6618 ends

    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //Start:POCOR-5281
        $PeriodShiftTable = TableRegistry::get('institution_shift_periods');
        if ($this->request->params['pass'][0] == 'edit') {
            $PeriodShiftData = $PeriodShiftTable->find()->where(['institution_shift_period_id' => $entity->id])->toArray();
            foreach ($PeriodShiftData as $PeriodShiftDataEntity) {
                $deleteEntity = $PeriodShiftTable->delete($PeriodShiftDataEntity);
            }
        }
        foreach ($entity->period['_ids'] as $one) {
            $PeriodShiftEntity = [
                'institution_shift_period_id' => $entity->id,
                'period_id' => $one
            ];
            $PeriodShift = $PeriodShiftTable->newEntity($PeriodShiftEntity);
            if ($PeriodShiftResult = $PeriodShiftTable->save($PeriodShift)) {

            }
        }
        //End:POCOR-5281
        if ($this->AcademicPeriods->getCurrent() == $entity->academic_period_id) { //if the one that being added / edited is the current academic period
            // $owner = $entity->institution_id;
            // $occupier = $entity->location_institution_id;
            $owner = $entity->location_institution_id;
            $occupier = $entity->institution_id;

            if ($owner == $occupier) {
                $ownerEqualOccupier = true;
            } else {
                $ownerEqualOccupier = false;
            }

            //owner need to be updated for all operation
            $shiftType = 0;
            $ownerOwnedShift = $this->find()
                ->where([
                    $this->aliasField('institution_id') . ' = ' . $owner,
                    $this->aliasField('academic_period_id') . ' = ' . $entity->academic_period_id
                ])
                ->count();

            if ($ownerOwnedShift > 1) {
                $shiftType = InstitutionsTable::MULTIPLE_OWNER;
            } elseif ($ownerOwnedShift == 1) {
                $shiftType = InstitutionsTable::SINGLE_OWNER;
            }
            $this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $owner]);

            //update new occupier
            if (!$ownerEqualOccupier) {
                $shiftType = 0;
                $occupierOccupiedShift = $this->find()
                    ->where([
                        $this->aliasField('institution_id') . ' != ' . $occupier,
                        $this->aliasField('location_institution_id') . ' = ' . $occupier,
                        $this->aliasField('academic_period_id') . ' = ' . $entity->academic_period_id
                    ])
                    ->count();

                if ($occupierOccupiedShift > 1) {
                    $shiftType = InstitutionsTable::MULTIPLE_OCCUPIER;
                } elseif ($occupierOccupiedShift == 1) {
                    $shiftType = InstitutionsTable::SINGLE_OCCUPIER;
                }
                $this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $occupier]);
                $this->updateAll(['institution_id' => $owner, 'location_institution_id' => $occupier], ['id' => $entity->id]);
            }
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($this->AcademicPeriods->getCurrent() == $entity->academic_period_id) { //update of shift_type only if deletion is done on the current academic period shift
            $owner = $entity->institution_id;
            $occupier = $entity->location_institution_id;

            //update owner
            $shiftType = 0;
            $ownerOwnedShift = $this->find()
                ->where([
                    $this->aliasField('institution_id') . ' = ' . $owner,
                    $this->aliasField('academic_period_id') . ' = ' . $entity->academic_period_id
                ])
                ->count();

            if ($ownerOwnedShift > 1) {
                $shiftType = InstitutionsTable::MULTIPLE_OWNER;
            } elseif ($ownerOwnedShift == 1) {
                $shiftType = InstitutionsTable::SINGLE_OWNER;
            }
            $this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $owner]);

            //update occupier if not equal to owner
            if ($owner != $occupier) {
                $shiftType = 0;
                $occupierOccupiedShift = $this->find()
                    ->where([
                        $this->aliasField('institution_id') . ' != ' . $occupier,
                        $this->aliasField('location_institution_id') . ' = ' . $occupier,
                        $this->aliasField('academic_period_id') . ' = ' . $entity->academic_period_id
                    ])
                    ->count();

                if ($occupierOccupiedShift > 1) {
                    $shiftType = InstitutionsTable::MULTIPLE_OCCUPIER;
                } elseif ($occupierOccupiedShift == 1) {
                    $shiftType = InstitutionsTable::SINGLE_OCCUPIER;
                }
                $this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $occupier]);
            }
        }
    }

    public function checkShiftExist($institutionId, $academicPeriodId) //if not exist then return false, else return array of shifts
    {
        $query = $this->find()
            ->where([
                'academic_period_id' => $academicPeriodId,
                'OR' => [
                    'location_institution_id' => $institutionId,
                    'institution_id' => $institutionId
                ]
            ]);
        if ($query->count() > 0) {
            return $query->toArray();
        } else {
            return false;
        }
    }

    public function getPreviousPeriodWithShift($institutionId, $latestAcademicPeriod)
    {
        return $query = $this->find()
            ->innerJoinWith('AcademicPeriods')
            ->select(['academicPeriodId' => 'AcademicPeriods.id'])
            ->where([
                'OR' => [
                    //'location_institution_id' => $institutionId,
                    'institution_id' => $institutionId
                ],
                'AcademicPeriods.id' . ' <> ' . $latestAcademicPeriod
            ])
            ->order(['start_date DESC'])
            ->distinct()
            ->first();
    }

    public function findUnitOptions(Query $query, array $options)
    {

        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];

        $institutionClasses = TableRegistry::get('institution_units');
        // $query11 = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        $query11 = $institutionClasses->find('all', ['fields' => ['id', 'name']]);
        echo json_encode($query11->toArray());
        die;

        //return $query11;
    }

    public function findShiftOptions(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];

        return $query
            ->innerJoinWith('ShiftOptions')
            ->innerJoinWith('Institutions')
            ->select([
                'institutionShiftId' => 'InstitutionShifts.id',
                'institutionId' => 'Institutions.id',
                'institutionCode' => 'Institutions.code',
                'institutionName' => 'Institutions.name',
                'shiftOptionName' => 'ShiftOptions.name'
            ])
            ->where([
                'location_institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId
            ])
            ->formatResults(function ($results) use ($institutionId) {
                $returnArr = [];
                foreach ($results as $result) {
                    if ($result->institutionId == $institutionId) { //if the shift owned by itself, then no need to show the shift owner
                        $shiftName = __($result->shiftOptionName);
                    } else {
                        $shiftName = $result->institutionCode . " - " . $result->institutionName . " - " . __($result->shiftOptionName);
                    }
                    $returnArr[] = [
                        'id' => intval($result->institutionShiftId),
                        'name' => $shiftName
                    ];
                }
                return $returnArr;
            });
    }

    /*
    * Function to get staff shift option
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return array
    * @ticket POCOR-6971
    */

    public function findStaffShiftOptions(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];

        return $query
            ->innerJoinWith('ShiftOptions')
            ->innerJoinWith('Institutions')
            ->select([
                'institutionShiftId' => 'InstitutionShifts.id',
                'institutionShiftStartTime' => 'InstitutionShifts.start_time',
                'institutionShiftEndTime' => 'InstitutionShifts.end_time',
                'institutionShiftsId' => 'InstitutionShifts.shift_option_id',
                'institutionId' => 'Institutions.id',
                'institutionCode' => 'Institutions.code',
                'institutionName' => 'Institutions.name',
                'shiftOptionName' => 'ShiftOptions.name'
            ])
            ->where([
                'location_institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId
            ])
            ->formatResults(function ($results) use ($institutionId) {
                $returnArr = [];
                foreach ($results as $result) {
                    if ($result->institutionId == $institutionId) { //if the shift owned by itself, then no need to show the shift owner
                        $shiftName = __($result->shiftOptionName);
                    } else {
                        $shiftName = $result->institutionCode . " - " . $result->institutionName . " - " . __($result->shiftOptionName);
                    }
                    $returnArr[] = [
                        'id' => intval($result->institutionShiftsId),
                        'name' => $shiftName . ': ' . $result->institutionShiftStartTime . ' - ' . $result->institutionShiftEndTime,
                        'start_time' => $result->institutionShiftStartTime,
                        'end_time' => $result->institutionShiftEndTime
                    ];
                }
                $defaultSelect = ['id' => '-1', 'name' => __('-- All --')];
                $defaultSelect['selected'] = true;
                array_unshift($returnArr, $defaultSelect);
                return $returnArr;
            });
    }

    //this is to be called by institution class to get the available shift.
    public function getShiftOptions($institutionsId, $periodId)
    {
        $query = $this->find()
            ->innerJoinWith('ShiftOptions')
            ->innerJoinWith('Institutions')
            ->select([
                'institutionShiftId' => 'InstitutionShifts.id',
                'institutionId' => 'Institutions.id',
                'institutionCode' => 'Institutions.code',
                'institutionName' => 'Institutions.name',
                'shiftOptionName' => 'ShiftOptions.name'
            ])
            ->where([
                'location_institution_id' => $institutionsId,
                'academic_period_id' => $periodId
            ]);

        $data = $query->toArray();

        $list = [];
        foreach ($data as $key => $obj) {
            if ($obj->institutionId == $institutionsId) { //if the shift owned by itself, then no need to show the shift owner
                $shiftName = __($obj->shiftOptionName);
            } else {
                $shiftName = $obj->institutionCode . " - " . $obj->institutionName . " - " . __($obj->shiftOptionName);
            }

            $list[$obj->institutionShiftId] = $shiftName;
        }

        return $list;
    }

    //to check whether an institution is occupier or not
    public function isOccupier($institutionId, $academicPeriod)
    {
        return $this->find()
            ->where([
                'AND' => [
                    [$this->aliasField('location_institution_id') . " = " . $institutionId],
                    [$this->aliasField('institution_id') . ' != ' . $institutionId],
                    [$this->aliasField('academic_period_id') . ' = ' . $academicPeriod]
                ]
            ])
            ->count();
    }

    //to check whether an institution is owner or not
    public function isOwner($institutionId, $academicPeriod)
    {
        return $this->find()
            ->where([
                'AND' => [
                    [$this->aliasField('institution_id') . ' = ' . $institutionId],
                    [$this->aliasField('academic_period_id') . ' = ' . $academicPeriod]
                ]
            ])
            ->count();
    }

    public function getOwnerList($selectedAcademicPeriodOptions)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');

        return $this->find()
            ->select([
                'institution_id'
            ])
            ->where([
                'AND' => [
                    [$this->aliasField('location_institution_id') . ' = ' . $institutionId],
                    [$this->aliasField('academic_period_id') . ' = ' . $selectedAcademicPeriodOptions]
                ]
            ])
            ->distinct()
            ->toArray();
    }

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            if (isset($request->query) && array_key_exists('period', $request->query)) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    }

    public function ajaxInstitutionsAutocomplete(Event $mainEvent, ArrayObject $extra)
    {
        $this->ControllerAction->autoRender = false;
        $this->controller->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $Institutions = $this->Institutions;

            $term = trim($this->request->query['term']);
            $selectedAcademicPeriod = trim($this->request->query['academicperiod']);
            $search = $term . '%';

            $query = $Institutions->find()
                ->select([
                    $Institutions->aliasField('code'),
                    $Institutions->aliasField('id'),
                    $Institutions->aliasField('name')
                ])
                ->where([
                    'EXISTS (' .
                    $this->find('list')
                        ->where([
                            $this->aliasField('institution_id') . ' = ' . $Institutions->aliasField('id'),
                            'OR' => [ //if owner has shift for themself or for others
                                $this->aliasField('institution_id') . ' != ' . $this->aliasField('location_institution_id'),
                                $this->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id')
                            ],
                            $this->aliasField('academic_period_id') . ' = ' . $selectedAcademicPeriod
                        ])
                    . ')',
                    'OR' => [
                        $Institutions->aliasField('code') . ' LIKE ' => $search,
                        $Institutions->aliasField('name') . ' LIKE ' => $search
                    ],
                    $Institutions->aliasField('institution_status_id') => 1 // POCOR-7598
                ])
                ->order([$Institutions->aliasField('name')]);

            $list = $query->toArray();

            $data = [];
            //pr($list);
            foreach ($list as $id => $value) {
                $label = $value['code'] . ' - ' . $value['name'];
                $data[] = ['label' => $label, 'value' => $value['id']];
            }

            echo json_encode($data);
            return true;
        }
    }

    private function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('shift_option_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('start_time', ['type' => 'time']);
        $this->field('end_time', ['type' => 'time']);
        $this->field('period'); //POCOR-5281

        $this->field('location', [
            'after' => 'period', //POCOR-5281
            'visible' => [
                'index' => false, 'view' => false, 'add' => true, 'edit' => true
            ],
            'entity' => $entity
        ]);
        $this->field('location_institution_id', [
            'type' => 'hidden',
            'after' => 'institution_id',
            'entity' => $entity
        ]);
        $this->field('previous_shift_id', ['visible' => 'false']);

    }

    public function findShiftTime(Query $query, array $options)
    {
        // if its students, it will have classId
        // it will used the classId to get the institutionId and get the shift time.
        $classId = array_key_exists('institution_class_id', $options) ? $options['institution_class_id'] : null;

        // for staff, they dont have the classId, so will used the academic periodId and institutionId to get the shift time.
        $academicPeriodId = array_key_exists('academic_period_id', $options) ? $options['academic_period_id'] : null;
        $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : null;

        if (!is_null($classId)) {
            $InstitutionClasses = $this->InstitutionClasses;

            $query->innerJoin(
                [$InstitutionClasses->alias() => $InstitutionClasses->table()],
                [
                    $InstitutionClasses->aliasField('institution_shift_id = ') . $this->aliasField('id'),
                    $InstitutionClasses->aliasField('id') => $classId
                ]);
        }

        $where = [];
        if (!is_null($academicPeriodId)) {
            $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!is_null($institutionId)) {
            $where[$this->aliasField('location_institution_id')] = $institutionId;
        }

        if (!empty($where)) {
            $query->where($where);
        }

        return $query;
    }

    public function findStaffShiftsAttendancedata(Query $query, array $options)
    {
        $staffId = $options['staff_id'];
        $institutionStaffShifts = TableRegistry::get('Institution.InstitutionStaffShifts');
        $institutionStaff = TableRegistry::get('institution_staff');
        $positions = TableRegistry::get('Institution.InstitutionPositions');
        $shiftOption = TableRegistry::get('shift_options');
        $staffShiftsData = $query
            ->leftJoin(
                [$institutionStaffShifts->alias() => $institutionStaffShifts->table()],
                [
                    $institutionStaffShifts->aliasField('shift_id = ') . $this->aliasField('id')
                ]
            )->
            leftJoin(
                [$positions->alias() => $positions->table()],
                [
                    $positions->aliasField('id = ') . $institutionStaff->aliasField('institution_position_id')
                ])
            ->leftJoin(
                [$shiftOption->alias() => $shiftOption->table()],
                [
                    $shiftOption->aliasField('id = ') . $positions->aliasField('shift_id')
                ]
            )
            ->select([
                'institutionShiftId' => $this->aliasField('id'),
                'startTime' => $this->aliasField('start_time'),
                'endTime' => $this->aliasField('end_time'),
            ])
            ->where([
                $institutionStaffShifts->aliasField('staff_id') => $staffId
            ])->first();

        return $staffShiftsData;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => 'Academic Period'
        ];

        $newFields[] = [
            'key' => 'shift_option_id',
            'field' => 'shift_option_id',
            'type' => 'string',
            'label' => 'Shift'
        ];

        $newFields[] = [
            'key' => 'InstitutionShifts.start_time',
            'field' => 'shift_start_time',
            'type' => 'string',
            'label' => 'Start Time'
        ];

        $newFields[] = [
            'key' => 'InstitutionShifts.end_time',
            'field' => 'shift_end_time',
            'type' => 'string',
            'label' => 'End Time'
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'Owner',
            'type' => 'string',
            'label' => 'Owner'
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'Occupier',
            'type' => 'string',
            'label' => 'Occupier'
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $extra, Query $query)
    {
        $academicPeriod = $this->request->query['period'];
        $institutionId = $this->Session->read('Institution.Institutions.id');

        if (empty($academicPeriod)) {
            $academicPeriod = $this->InstitutionShifts->AcademicPeriods->getCurrent();
        }

        if ($academicPeriod != '') {
            $query
                ->select(['academic_period' => 'AcademicPeriods.name', 'shift_name' => 'ShiftOptions.name', 'shift_start_time' => 'InstitutionShifts.start_time', 'shift_end_time' => 'InstitutionShifts.end_time', 'Owner' => 'Institutions.name', 'Occupier' => 'Institutions.name'])
                ->LeftJoin([$this->Institutions->alias() => $this->Institutions->table()], [
                    $this->Institutions->aliasField('id') . ' = ' . 'InstitutionShifts.institution_id'
                ])
                ->LeftJoin([$this->AcademicPeriods->alias() => $this->AcademicPeriods->table()], [
                    $this->AcademicPeriods->aliasField('id') . ' = ' . 'InstitutionShifts.academic_period_id'
                ])
                ->LeftJoin([$this->ShiftOptions->alias() => $this->ShiftOptions->table()], [
                    $this->ShiftOptions->aliasField('id') . ' = ' . $this->InstitutionShifts->aliasField('shift_option_id')
                ])
                ->where([
                    'OR' => [
                        [$this->aliasField('location_institution_id') => $institutionId],
                        [$this->aliasField('institution_id') => $institutionId]
                    ],
                    $this->aliasField('academic_period_id') => $academicPeriod
                ]);
        }

    }
}
