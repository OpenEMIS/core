<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\TableLocator;
use DateTime;

class ScheduleIntervalsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_schedule_intervals');
        parent::initialize($config);

        $this->belongsTo('Institutions', [
            'className' => 'Institution.Institutions'
        ]);

        $this->belongsTo('AcademicPeriods', [
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        $this->belongsTo('Shifts', [
            'className' => 'Institution.InstitutionShifts',
            'foreignKey' => 'institution_shift_id'
        ]);

        $this->hasMany('Timeslots', [
            'className' => 'Schedule.ScheduleTimeslots',
            'foreignKey' => 'institution_schedule_interval_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('Timetables', [
            'className' => 'Schedule.ScheduleTimetables',
            'foreignKey' => 'institution_schedule_interval_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);


        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index', 'view', 'edit']
        ]);
        $this->addBehavior('Schedule.Schedule');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['ScheduleIntervals' =>['id']
            ]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        $validator
            // POCOR-8985 start
            ->notEmptyString('name')
            ->notEmptyString('institution_shift_id')
            // POCOR-8985 end
            ->requirePresence('timeslots', 'create');

        return $validator;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'institution_shift_id':
                return __('Shift');
            case 'academic_period_id':
                return __('Academic Period');
            case 'name':
                return __('Name');
            case 'institution_shift_id':
                return __('Shift');
            case 'intervals':
                return __('Intervals');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Shifts.ShiftOptions']);

        if (isset($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
            ]);
        }

        if (isset($extra['selectedShiftOptions']) && $extra['selectedShiftOptions'] != -1) {
            $query->where([
                $this->aliasField('institution_shift_id') => $extra['selectedShiftOptions']
            ]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('name');
        $this->field('institution_shift_id');
        $this->setFieldOrder(['name', 'institution_shift_id']);

        // filter options
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $requestQuery = $this->request->getQuery();

        if (isset($requestQuery) && isset($requestQuery['period'])) {
            $selectedPeriodId = $requestQuery['period']; // POCOR-8985
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $shiftOptions = $this->getShiftOptions($selectedPeriodId, true);

        if (isset($requestQuery) && isset($requestQuery['shift'])) {
            $selectedShiftId = $requestQuery['shift']; // POCOR-8985
        } else {
            $selectedShiftId = -1;
        }

        $extra['selectedShiftOptions'] = $selectedShiftId;
        $extra['selectedAcademicPeriodOptions'] = $selectedPeriodId;

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['control'] = [
            'name' => 'Schedule.Intervals/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions'],
                'shiftOptions' => $shiftOptions,
                'selectedShiftOption' => $selectedShiftId
            ],
            'order' => 3
        ];


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Intervals','Schedules');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('name');
        $this->field('institution_shift_id', ['type' => 'select']);
        $this->field('intervals', [
            'type' => 'element',
            'element' => 'Schedule.Intervals/interval_timeslots'
        ]);
        $this->setFieldOrder(['academic_period_id', 'name', 'institution_shift_id', 'intervals']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Shifts.ShiftOptions',
                'Timeslots'
            ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('name');
        $this->field('institution_shift_id');
        $this->field('intervals', [
            'type' => 'element',
            'element' => 'Schedule.Intervals/interval_timeslots'
        ]);

        $this->setFieldOrder(['academic_period_id', 'name', 'institution_shift_id', 'intervals']);
    }


    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {

        $intervalId = $data['id'];
         if(empty($intervalId)){
            // for updating of the start/end time of the timeslots on render
            if (isset($data['submit']) && in_array($data['submit'], ['changeInterval', 'addTimeslot', 'changeShiftId', 'save']) && !empty($data['timeslots'])) {
                $institutionShiftId = $data['institution_shift_id'];
                $startTime = $this->Shifts->get($institutionShiftId)->start_time;
                if (!($startTime instanceof DateTime)) {
                    $startTime = new DateTime($startTime);
                }

                $hasEmpty = false;
                foreach ($data['timeslots'] as $i => $timeslot) {
                    if (!$hasEmpty) {
                        if (isset($timeslot['interval']) && !empty($timeslot['interval'])) {
                            $timeslotInterval = $timeslot['interval'];
                            $data['timeslots'][$i]['start_time_add'] = $this->formatTime($startTime);
                            $modifyString = '+' . $timeslotInterval . ' minutes';
                            $data['timeslots'][$i]['end_time_add'] = $this->formatTime($startTime->modify($modifyString));
                        } else {
                            $hasEmpty = true;
                        }
                    }
                }
            }

            // for patching the order of the timeslots based on the array index
            if (isset($data['timeslots']) && !empty($data['timeslots'])) {
                foreach ($data['timeslots'] as $i => $timeslot) {
                    $data['timeslots'][$i]['order'] = $i + 1;
                }
            }

            // for adding timeslots end time validation as here will have all the informations needed to do the validations
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $options['associated'] = [
                    'Timeslots' => ['validate' => true]
                ];

                $institutionShiftId = $data['institution_shift_id'];
                $shiftEntity = $this->Shifts->get($institutionShiftId);
                $shiftStartTime = $shiftEntity->start_time;
                if (!($shiftStartTime instanceof \DateTime)) {
                    $shiftStartTime = new \DateTime($shiftStartTime);
                }
                $shiftEndTime = $shiftEntity->end_time;

                $timeslotList = [];
                if (isset($data['timeslots']) && !empty($data['timeslots'])) {

                    $hasEmpty = false;
                    $totalInterval = 0;
                    foreach ($data['timeslots'] as $i => $timeslot) {
                        if (!$hasEmpty) {
                            if (isset($timeslot['interval']) && !empty($timeslot['interval'])) {
                                $totalInterval += $timeslot['interval'];
                                $timeslotList[$timeslot['order']] = $totalInterval;
                            } else {
                                $hasEmpty = true;
                            }
                        }

                        if ($hasEmpty) {
                            $timeslotList[$timeslot['order']] = null;
                        }
                    }
                }
                $timeslotValidator = $this->Timeslots->getValidator();
                $timeslotValidator
                    ->add('interval', 'checkEndTime', [
                        'rule' => function($value, $context) use ($shiftStartTime, $shiftEndTime, $timeslotList) {
                            $order = $context['data']['order'];
                            $totalInterval = $timeslotList[$order];
                            if (!is_null($totalInterval)) {
                                $intervalStartTime = clone $shiftStartTime;
                                $modifyString = '+' . $totalInterval . ' minutes';
                                $intervalEndTime = $intervalStartTime->modify($modifyString);
                                if (!($shiftEndTime instanceof \DateTime)) {
                                    $shiftEndTime = new \DateTime($shiftEndTime);
                                }
                                return $intervalEndTime <= $shiftEndTime;
                            }
                            return true;
                        },
                        'on' => 'create',
                        'message' => __('Value entered exceed the end time of the shift selected.')
                    ])
                    ->requirePresence('institution_schedule_interval_id', false);

            } else {
                // for non-save actions so the timeslot entity can be patched
                $options['associated'] = [
                    'Timeslots' => ['validate' => false]
                ];
            }
        }else{ //POCOR-8254
            if (isset($data['submit']) && in_array($data['submit'], ['changeInterval', 'addTimeslot', 'changeShiftId', 'save']) && !empty($data['timeslots'])) {
                $institutionShiftId = $data['institution_shift_id'];
                $startTime = $this->Shifts->get($institutionShiftId)->start_time;

                $hasEmpty = false;
                foreach ($data['timeslots'] as $i => $timeslot) {
                    if (!$hasEmpty) {
                        $data['timeslots'][$i]['institution_schedule_interval_id'] = $intervalId;
                        if (isset($timeslot['interval']) && !empty($timeslot['interval'])) {
                            $timeslotInterval = $timeslot['interval'];
                            $data['timeslots'][$i]['start_time_add'] = $this->formatTime($startTime);
                            $modifyString = '+' . $timeslotInterval . ' minutes';
                            $data['timeslots'][$i]['end_time_add'] = $this->formatTime($startTime->modify($modifyString));
                        } else {
                            $hasEmpty = true;
                        }
                    }
                }
            }
            // POCOR-8985 start
             $alias = 'ScheduleIntervals';
             $requestData = $this->request->getData();
            $timeslotList = $requestData[$alias]['timeslots'];
             // POCOR-8985 end
            $tableLocator = new TableLocator();
            $institutionSchedule = $tableLocator->get('institution_schedule_timeslots');
            // $institutionSchedule =  TableRegistry::get('institution_schedule_timeslots');
            $findRecord = $institutionSchedule->find()
                        ->where(['institution_schedule_interval_id'=>$intervalId])->toArray();

            foreach ($findRecord as $value) {
                foreach ($timeslotList as $val) {
                    $institutionScheduledata = $institutionSchedule->updateAll(
                        ['interval' => $val['interval']], // Field
                        ['id' => $value['id']] // Condition
                    );
                }
            }
            if (isset($data['timeslots']) && !empty($data['timeslots'])) {
                foreach ($data['timeslots'] as $i => $timeslot) {
                    $data['timeslots'][$i]['order'] = $i + 1;
                }
            }

            // for adding timeslots end time validation as here will have all the informations needed to do the validations
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $options['associated'] = [
                    'Timeslots' => ['validate' => true]
                    ];

                    $institutionShiftId = $data['institution_shift_id'];
                    $shiftEntity = $this->Shifts->get($institutionShiftId);
                    $shiftStartTime = $shiftEntity->start_time;
                    $shiftEndTime = $shiftEntity->end_time;

                    $timeslotList = [];
                    if (isset($data['timeslots']) && !empty($data['timeslots'])) {

                        $hasEmpty = false;
                        $totalInterval = 0;
                        foreach ($data['timeslots'] as $i => $timeslot) {
                            if (!$hasEmpty) {
                                if (isset($timeslot['interval']) && !empty($timeslot['interval'])) {
                                    $totalInterval += $timeslot['interval'];
                                    $timeslotList[$timeslot['order']] = $totalInterval;
                                } else {
                                    $hasEmpty = true;
                                }
                            }

                            if ($hasEmpty) {
                                $timeslotList[$timeslot['order']] = null;
                            }
                        }
                    }

            }
        }
       // echo "<pre>"; print_r($data);die;
    }

    // OnGet Events
    public function onGetInstitutionShiftId(Event $event, Entity $entity)
    {
        return $entity->shift->shift_option->name;
    }

    // OnUpdate Events
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $academicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $ScheduleIntervals = TableRegistry::get('Schedule.ScheduleIntervals');
        if ($action == 'add') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery['period']));
            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = true;
            $attr['default'] = $selectedPeriod;
        } else if ($action == 'edit') {
            //POCOR-8254 start
            $scheduleId = $this->paramsDecode($request->getAttribute('params')['pass'][1])['id'];
            $academicPeriodId= $ScheduleIntervals->find()
                                    ->where(['id' => $scheduleId])
                                    ->first()->academic_period_id;
            $academicPeriodName = $academicPeriod->find()
                                    ->where(['id' => $academicPeriodId])
                                    ->first()->name;
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $academicPeriodName;
            //POCOR-8254 end
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionShiftId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $ShiftOptions = TableRegistry::get('Institution.ShiftOptions');
        $ScheduleIntervals = TableRegistry::get('Schedule.ScheduleIntervals');
        if ($action == 'add') {
            $requestData = $request->getData();
            if (isset($requestData) && isset($requestData[$this->getAlias()]) && array_key_exists('academic_period_id', $requestData[$this->getAlias()])) {
                $selectedPeriodId = $requestData[$this->getAlias()]['academic_period_id'];
            } else {
                $selectedPeriodId = $this->AcademicPeriods->getCurrent();
            }

            $attr['type'] = 'select';
            $attr['options'] = $this->getShiftOptions($selectedPeriodId);
            $attr['onChangeReload'] = 'changeShiftId';
            return $attr;
        } elseif ($action == 'edit') {
            //POCOR-8254 start
            $scheduleId = $this->paramsDecode($request->getAttribute('params')['pass'][1])['id'];
            $InstitutionShiftId = $ScheduleIntervals->find()
                                    ->where(['id' => $scheduleId])
                                    ->first()->institution_shift_id;
            $shiftOptionId = $InstitutionShifts->find()
                                    ->where(['id' => $InstitutionShiftId])
                                    ->first()->shift_option_id;
            $ShiftOptionName = $ShiftOptions->find()
                                    ->where(['id' => $shiftOptionId])
                                    ->first()->name;
            $attr['type'] = 'readonly';
            $entity = $attr['entity'];
            $attr['value'] = $entity->institution_shift_id;
            $attr['attr']['value'] = $ShiftOptionName;
            //POCOR-8254 end
        }
        return $attr;
    }

    // Change Events
    public function addOnAddTimeslot(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fieldKey = 'timeslots';

        if (empty($data[$this->getAlias()][$fieldKey])) {
            $data[$this->getAlias()][$fieldKey] = [];
        }

        if ($data->offsetExists($this->getAlias())) {
            $data[$this->getAlias()][$fieldKey][] = [
                'intervals' => '',
            ];
        }

        $options['associated'] = [
            'Timeslots' => ['validate' => false]
        ];
    }

    public function addOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data[$this->getAlias()]['institution_shift_id'] = '';
        unset($data[$this->getAlias()]['timeslots']);
    }

    // Get Options
    public function getShiftOptions($academicPeriodId, $allShiftOption = false, $institutionId='')
    {
        if($institutionId == null){
            $institutionId = $this->getInstitutionID();
           }

        $shiftOptions = $this->Shifts
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->select([
                'id' => $this->Shifts->aliasField('id'),
                'name' => 'ShiftOptions.name'
            ])
            ->contain('ShiftOptions')
            ->where([
                $this->Shifts->aliasField('academic_period_id') => $academicPeriodId,
                $this->Shifts->aliasField('institution_id') => $institutionId // POCOR-8985
            ])
            ->toArray();

        if (!empty($shiftOptions) && $allShiftOption) {
            $shiftOptions = ['-1' => '-- ' . __('All Shifts') . ' --'] + $shiftOptions;
        }

        return $shiftOptions;
    }

    public function getStaffShiftOptions($academicPeriodId, $allShiftOption = false, $institutionId = null)
    {
        $query = $this->Shifts->find('list', [
            'keyField' => 'id',
            'valueField' => 'name'
        ])
        ->select([
            'id' => $this->Shifts->aliasField('id'),
            'name' => 'ShiftOptions.name'
        ])
        ->contain('ShiftOptions');

        $conditions = [
            $this->Shifts->aliasField('academic_period_id') => $academicPeriodId
        ];

        if ($institutionId !== null) {
            $conditions[$this->Shifts->aliasField('Institution_id')] = $institutionId;
        } else {
            $conditions[$this->Shifts->aliasField('Institution_id IS')] = null;
        }

        $shiftOptions = $query->where($conditions)->toArray();

        if (!empty($shiftOptions) && $allShiftOption) {
            $shiftOptions = ['-1' => '-- ' . __('All Shifts') . ' --'] + $shiftOptions;
        }

        return $shiftOptions;
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    //POCOR-8254
    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
         $entity['timeslots'] = array();

    }

    //POCOR-8254
    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        $timeslotList = $this->request->getData()['ScheduleIntervals']['timeslots'];
        // $institutionSchedule = TableRegistry::get('institution_schedule_timeslots');
        $tableLocator = new TableLocator();
        $institutionSchedule = $tableLocator->get('institution_schedule_timeslots');
        $findRecord = $institutionSchedule->find()
            ->where(['institution_schedule_interval_id' => $entity->id])
            ->toArray();
        // Check if the number of records matches the number of timeslots
        if (count($findRecord) === count($timeslotList)) {
            foreach ($findRecord as $key => $value) {
                $val = $timeslotList[$key]; // Get the corresponding timeslot
                $institutionScheduledata = $institutionSchedule->updateAll(
                    ['interval' => $val['interval']], // Field
                    ['id' => $value['id']] // Condition
                );
            }
        } else {
            //return false;
        }

    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

}
